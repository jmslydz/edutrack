<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Grade_model
 *
 * Reads/writes against `grades` plus the `grade_logs` audit trail.
 *
 * Grades now live against the two-period model: a row is keyed by
 * (student_id, subject_id, grading_period_id) where grading_period_id
 * points into grading_periods (Midterm / Final per semester). Every save
 * goes through upsert(), which:
 *   - validates the period is one of the allowed enum values
 *   - writes a grade_logs row (old value, new value, changed_by) whenever
 *     an existing grade actually changes
 *   - records who encoded the grade
 */
class Grade_model extends CI_Model
{
	const PERIODS = array('Midterm', 'Final');

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Fetch a single grade row.
	 * @param int $grade_id
	 * @return object|null
	 */
	public function get($grade_id)
	{
		return $this->db->where('id', (int) $grade_id)->get('grades')->row();
	}

	/**
	 * Fetch a grade by its natural key.
	 * @param int $subject_id
	 * @param int $student_id
	 * @param int $grading_period_id
	 * @return object|null
	 */
	public function get_by_key($subject_id, $student_id, $grading_period_id)
	{
		return $this->db->where('subject_id', (int) $subject_id)
			->where('student_id', (int) $student_id)
			->where('grading_period_id', (int) $grading_period_id)
			->get('grades')
			->row();
	}

	/**
	 * Insert or update a grade for one student/period and audit any change.
	 *
	 * @param int        $subject_id
	 * @param int        $student_id
	 * @param int        $grading_period_id
	 * @param int        $teacher_id        user id of the teacher who owns the class
	 * @param float|null $grade             NULL = clear/missing grade
	 * @param int        $encoded_by        user id making the change
	 * @return bool
	 */
	public function upsert($subject_id, $student_id, $grading_period_id, $teacher_id, $grade, $encoded_by)
	{
		$grade = ($grade === NULL) ? NULL : (float) $grade;
		$now   = date('Y-m-d H:i:s');

		$existing = $this->get_by_key($subject_id, $student_id, $grading_period_id);

		if ($existing)
		{
			$old = $existing->grade_value;
			$changed = $this->_value_changed($old, $grade);

			$this->db->where('id', $existing->id)->update('grades', array(
				'grade_value'  => $grade,
				'teacher_id'   => (int) $teacher_id,
				'date_recorded'=> $now,
				'updated_at'   => $now,
			));

			if ($changed)
			{
				$this->_log($existing->id, $old, $grade, $encoded_by);
			}
			return TRUE;
		}

		$this->db->insert('grades', array(
			'student_id'         => (int) $student_id,
			'subject_id'         => (int) $subject_id,
			'teacher_id'         => (int) $teacher_id,
			'grading_period_id'  => (int) $grading_period_id,
			'grade_value'        => $grade,
			'date_recorded'      => $now,
			'created_at'         => $now,
		));
		$new_id = $this->db->insert_id();
		$this->_log($new_id, NULL, $grade, $encoded_by);
		return TRUE;
	}

	/**
	 * All grades for a subject + grading period, keyed by student_id.
	 * @param int $subject_id
	 * @param int $grading_period_id
	 * @return array student_id => grade value|null
	 */
	public function grades_for_subject_period($subject_id, $grading_period_id)
	{
		$result = array();
		$rows = $this->db->select('student_id, grade_value')
			->where('subject_id', (int) $subject_id)
			->where('grading_period_id', (int) $grading_period_id)
			->get('grades')
			->result();
		foreach ($rows as $r)
		{
			$result[(int) $r->student_id] = $r->grade_value;
		}
		return $result;
	}

	/**
	 * A student's subject grades for a term (student dashboard).
	 * Returns one row per subject the student's section offers, with
	 * Midterm/Final values, instructor name and the computed Final Grade
	 * (50/50 average of Midterm + Final; NULL until both exist).
	 *
	 * @param int $student_section_id
	 * @param int $semester_id
	 * @param int $student_id
	 * @return array
	 */
	public function student_term_grades($student_section_id, $semester_id, $student_id)
	{
		// Use section_subjects to get ALL subjects from curriculum,
		// LEFT JOIN teacher_subject_assignments for instructor info
		$rows = $this->db->select(
				'subjects.id AS subject_id, subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'subjects.units, ' .
				'CASE WHEN users.id IS NOT NULL THEN CONCAT(users.first_name, " ", users.last_name) ELSE NULL END AS instructor, ' .
				'tsa.id AS assignment_id'
			)
			->from('section_subjects ss')
			->join('subjects', 'subjects.id = ss.subject_id')
			->join('teacher_subject_assignments tsa',
				'tsa.section_id = ss.section_id AND tsa.subject_id = ss.subject_id AND tsa.semester_id = ss.semester_id', 'left')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('ss.section_id', (int) $student_section_id)
			->where('ss.semester_id', (int) $semester_id)
			->order_by('subjects.code', 'ASC')
			->get()
			->result();

		$grades = $this->db->select(
				'grades.subject_id, grades.grade_value, grading_periods.period_name'
			)
			->from('grades')
			->join('grading_periods', 'grading_periods.id = grades.grading_period_id')
			->where('grades.student_id', (int) $student_id)
			// Scope to the requested semester's grading periods: subjects repeat
			// every school year under the same subject_id, so without this a
			// grade from a previous S.Y. can shadow the current one (stale
			// grades on dashboard / report card / enrollment history / grades).
			->where('grading_periods.semester_id', (int) $semester_id)
			->get()
			->result();

		$byKey = array();
		foreach ($grades as $g)
		{
			$byKey[(int) $g->subject_id . '|' . $g->period_name] = $g->grade_value;
		}

		$out = array();
		foreach ($rows as $row)
		{
			$mid   = isset($byKey[$row->subject_id . '|Midterm']) ? $byKey[$row->subject_id . '|Midterm'] : NULL;
			$final = isset($byKey[$row->subject_id . '|Final'])   ? $byKey[$row->subject_id . '|Final']   : NULL;

			$final_grade = ($mid !== NULL && $final !== NULL)
				? round(((float) $mid + (float) $final) / 2.0, 2)
				: NULL;

			$out[] = (object) array(
				'code'        => $row->subject_code,
				'title'       => $row->subject_title,
				'units'       => (float) $row->units,
				'instructor'  => $row->instructor,
				'midterm'     => $mid,
				'final'       => $final,
				'final_grade' => $final_grade,
				'remarks'     => $this->remarks_for_average($final_grade),
			);
		}
		return $out;
	}

	/**
	 * Grade-encoding progress for a single assignment: how many of the
	 * section's students already have a (non-null) grade per period
	 * (Midterm, Final).
	 *
	 * @param int $assignment_id
	 * @param int $section_id
	 * @return array period => ['encoded' => int, 'total' => int]
	 */
	public function progress_for_assignment($assignment_id, $section_id)
	{
		// Roster = students enrolled in THIS section (the denominator).
		$student_ids = array();
		$students = $this->db->select('id')
			->where('section_id', (int) $section_id)
			->get('students')
			->result();
		foreach ($students as $s)
		{
			$student_ids[] = (int) $s->id;
		}
		$total = count($student_ids);

		$out = array();
		foreach (self::PERIODS as $period)
		{
			$out[$period] = array('encoded' => 0, 'total' => $total);
		}

		// Resolve the assignment's subject + semester to map grading periods.
		$tsa = $this->db->select('subject_id, semester_id')
			->where('id', (int) $assignment_id)
			->get('teacher_subject_assignments')
			->row();

		// No class or no students → 0 encoded (avoids divide-by-zero).
		if ( ! $tsa || $total === 0)
		{
			return $out;
		}

		$subject_id = (int) $tsa->subject_id;
		$period_rows = $this->db->where('semester_id', (int) $tsa->semester_id)->get('grading_periods')->result();
		foreach ($period_rows as $p)
		{
			$name = $p->period_name;
			if ( ! isset($out[$name]))
			{
				continue;
			}
			// Numerator is scoped to THIS section's students only, so a
			// grade for the same subject in another section is never
			// counted here (was the cause of >100% values).
			$encoded = (int) $this->db->where('subject_id', $subject_id)
				->where('grading_period_id', (int) $p->id)
				->where('grade_value IS NOT NULL')
				->where_in('student_id', $student_ids)
				->count_all_results('grades');
			$out[$name]['encoded'] = $encoded;
		}
		return $out;
	}

	/**
	 * Overall encoded percentage for an assignment across all periods
	 * (used by the admin dashboard's Grade Submission Status widget).
	 *
	 * @param int $assignment_id
	 * @param int $section_id
	 * @return int 0-100
	 */
	public function overall_progress_for_assignment($assignment_id, $section_id)
	{
		$progress = $this->progress_for_assignment($assignment_id, $section_id);
		$encoded  = 0;
		$total    = 0;
		foreach ($progress as $p)
		{
			$encoded += $p['encoded'];
			$total   += $p['total'];
		}
		return $total > 0 ? (int) round($encoded / $total * 100) : 0;
	}

	/**
	 * Recent grade changes (for the admin dashboard "Recent Activity").
	 * @param int $limit
	 * @return array of objects
	 */
	public function recent_activity($limit = 5)
	{
		return $this->db->select(
				'grade_logs.id, subjects.code AS subject_code, sections.name AS section_name, ' .
				'CONCAT(u.first_name, " ", u.last_name) AS teacher_name, ' .
				'CONCAT(stu.first_name, " ", stu.last_name) AS student_name, ' .
				'grade_logs.changed_at, grade_logs.new_value, grade_logs.old_value, ' .
				'grading_periods.period_name'
			)
			->from('grade_logs')
			->join('grades g', 'g.id = grade_logs.grade_id')
			->join('students st', 'st.id = g.student_id')
			->join('users stu', 'stu.id = st.user_id')
			->join('sections', 'sections.id = st.section_id')
			->join('subjects', 'subjects.id = g.subject_id')
			->join('users u', 'u.id = grade_logs.changed_by')
			->join('grading_periods', 'grading_periods.id = g.grading_period_id')
			->order_by('grade_logs.changed_at', 'DESC')
			->limit((int) $limit)
			->get()
			->result();
	}

	/**
	 * Full grade_logs audit trail with student/subject/period/teacher
	 * details (the admin Activity Log page).
	 *
	 * @param array $filters ['teacher' => changed_by id, 'from' => Y-m-d, 'to' => Y-m-d]
	 * @param int   $limit
	 * @param int   $offset
	 * @return array of objects
	 */
	public function activity_log_entries(array $filters = array(), $limit = 15, $offset = 0)
	{
		$this->_apply_log_filters($filters);
		return $this->db->select(
				'grade_logs.changed_at, grade_logs.old_value, grade_logs.new_value, ' .
				'CONCAT(teacher.first_name, " ", teacher.last_name) AS teacher_name, ' .
				'CONCAT(student.first_name, " ", student.last_name) AS student_name, ' .
				'students.student_no, subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'grading_periods.period_name'
			)
			->from('grade_logs')
			->join('grades g', 'g.id = grade_logs.grade_id')
			->join('students', 'students.id = g.student_id')
			->join('users student', 'student.id = students.user_id')
			->join('subjects', 'subjects.id = g.subject_id')
			->join('grading_periods', 'grading_periods.id = g.grading_period_id')
			->join('users teacher', 'teacher.id = grade_logs.changed_by')
			->order_by('grade_logs.changed_at', 'DESC')
			->limit((int) $limit, (int) $offset)
			->get()
			->result();
	}

	/**
	 * Total grade_logs rows matching the given filters (for pagination).
	 * @param array $filters same shape as activity_log_entries()
	 * @return int
	 */
	public function count_activity_log(array $filters = array())
	{
		$this->_apply_log_filters($filters);
		return (int) $this->db->from('grade_logs')
			->join('grades g', 'g.id = grade_logs.grade_id')
			->count_all_results();
	}

	/**
	 * Get grade history (grade_logs) for a specific student.
	 * Scoped by student_id for IDOR protection.
	 * @param int $student_id
	 * @return array of objects
	 */
	public function student_grade_history($student_id)
	{
		return $this->db->select(
				'grade_logs.changed_at, grade_logs.old_value, grade_logs.new_value, ' .
				'CONCAT(teacher.first_name, " ", teacher.last_name) AS teacher_name, ' .
				'subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'grading_periods.period_name'
			)
			->from('grade_logs')
			->join('grades g', 'g.id = grade_logs.grade_id')
			->join('students', 'students.id = g.student_id')
			->join('subjects', 'subjects.id = g.subject_id')
			->join('grading_periods', 'grading_periods.id = g.grading_period_id')
			->join('users teacher', 'teacher.id = grade_logs.changed_by')
			->where('students.id', (int) $student_id)
			->order_by('grade_logs.changed_at', 'DESC')
			->get()
			->result();
	}

	/**
	 * Shared WHERE clause for the activity-log list/count queries.
	 * @param array $filters
	 */
	private function _apply_log_filters(array $filters)
	{
		if ( ! empty($filters['teacher']))
		{
			$this->db->where('grade_logs.changed_by', (int) $filters['teacher']);
		}
		if ( ! empty($filters['from']))
		{
			$this->db->where('grade_logs.changed_at >=', $filters['from'] . ' 00:00:00');
		}
		if ( ! empty($filters['to']))
		{
			$this->db->where('grade_logs.changed_at <=', $filters['to'] . ' 23:59:59');
		}
	}

	// -----------------------------------------------------------------

	/**
	 * Validate/normalise a period value against the allowed enum.
	 * @param string $period
	 * @return string
	 */
	public function valid_period($period)
	{
		$period = ucfirst(strtolower(trim((string) $period)));
		if ( ! in_array($period, self::PERIODS, TRUE))
		{
			show_error('Invalid grading period.', 400, 'Bad Request');
		}
		return $period;
	}

	/**
	 * Compare two grade values for change (NULL vs numeric aware).
	 * @param float|null $old
	 * @param float|null $new
	 * @return bool
	 */
	private function _value_changed($old, $new)
	{
		if ($old === NULL || $new === NULL)
		{
			return $old !== $new;
		}
		return abs((float) $old - (float) $new) > 0.0001;
	}

	/**
	 * Write an audit row.
	 * @param int        $grade_id
	 * @param float|null $old
	 * @param float|null $new
	 * @param int        $changed_by
	 */
	private function _log($grade_id, $old, $new, $changed_by)
	{
		$this->db->insert('grade_logs', array(
			'grade_id'   => (int) $grade_id,
			'old_value'  => $old,
			'new_value'  => $new,
			'changed_by' => (int) $changed_by,
			'changed_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Remarks label for a subject final grade.
	 * @param float|null $final_grade
	 * @return string|null
	 */
	public function remarks_for_average($final_grade)
	{
		if ($final_grade === NULL)
		{
			return NULL;
		}
		return $final_grade <= 3.0 ? 'Passed' : 'Failed';
	}
}