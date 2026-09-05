<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report_model
 *
 * Aggregations for the Academic Reports page. Data is always scoped by
 * the caller (a section id, or NULL for "All Sections" which only the
 * Admin controller is allowed to request) — never by a role/teacher_id
 * read from the browser.
 *
 * The reports use the two-period model: per subject a student has a
 * Midterm and a Final grade; the Final Grade is the 50/50 average of the
 * two (only counted once both exist). GWA is the unit-weighted average of
 * subject Final Grades across subjects with a complete Final Grade.
 */
class Report_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Grade-summary / honor-roll rows for a term, optionally scoped to a
	 * single section.
	 *
	 * @param int      $semester_id
	 * @param int|null $section_id NULL = all sections (admin only)
	 * @return array of row objects
	 */
	public function summary($semester_id, $section_id = NULL)
	{
		// All assignments offered in the term (optionally one section).
		$assignments = $this->_term_assignments($semester_id, $section_id);

		if (empty($assignments))
		{
			return array();
		}

		// Section ids involved and subjects offered per section.
		$sections_involved = array();
		foreach ($assignments as $a)
		{
			$sections_involved[(int) $a->section_id] = $a->section_name;
		}

		// Students for the involved sections.
		$students = $this->_students_for_sections(array_keys($sections_involved));

		if (empty($students))
		{
			return array();
		}

		// All grades for these students, grouped by student/subject/period.
		$gradesByStudent = $this->_grades_by_student_subject(
			array_map(function ($s) { return $s->student_id; }, $students)
		);

		$rows = array();
		foreach ($students as $s)
		{
			$section_id = (int) $s->section_id;
			$section_name = $sections_involved[$section_id];

			// Assignments this section offers.
			$offered = array_filter($assignments, function ($a) use ($section_id) {
				return (int) $a->section_id === $section_id;
			});

			$enrolled_units = 0.0;
			$gwa_sum = 0.0;
			$gwa_units = 0.0;
			$graded_subjects = 0;

			foreach ($offered as $a)
			{
				$subject_id = (int) $a->subject_id;
				$enrolled_units += (float) $a->units;

				$periods = isset($gradesByStudent[(int) $s->student_id][$subject_id])
					? $gradesByStudent[(int) $s->student_id][$subject_id]
					: array();

				$final_grade = $this->_final_grade($periods);
				if ($final_grade !== NULL)
				{
					$gwa_sum += $final_grade * (float) $a->units;
					$gwa_units += (float) $a->units;
					$graded_subjects++;
				}
			}

			$gwa = $gwa_units > 0 ? round($gwa_sum / $gwa_units, 4) : NULL;
			$status = $this->_status_for_gwa($gwa);
			$honor  = $this->_honor_for_gwa($gwa);

			$rows[] = (object) array(
				'student_no' => $s->student_no,
				'name'       => $s->last_name . ', ' . $s->first_name,
				'section'    => $section_name,
				'gwa'        => $gwa,
				'units'      => $enrolled_units,
				'status'     => $status,
				'honor'      => $honor,
				'graded'     => $graded_subjects > 0,
			);
		}

		// Sort by name for stable output.
		usort($rows, function ($a, $b) { return strcmp($a->name, $b->name); });
		return $rows;
	}

	/**
	 * Subject performance rows: per subject offered, the number of
	 * students with a complete Final Grade, the average Final Grade, and
	 * passed/failed counts.
	 *
	 * @param int      $semester_id
	 * @param int|null $section_id
	 * @return array of row objects
	 */
	public function subject_performance($semester_id, $section_id = NULL)
	{
		$assignments = $this->_term_assignments($semester_id, $section_id);
		if (empty($assignments))
		{
			return array();
		}

		// Students for the involved sections, so grades are scoped to the
		// actual class rosters (never the whole student body).
		$sections_involved = array();
		foreach ($assignments as $a)
		{
			$sections_involved[(int) $a->section_id] = $a->section_name;
		}
		$students = $this->_students_for_sections(array_keys($sections_involved));
		if (empty($students))
		{
			return array();
		}

		$gradesByStudent = $this->_grades_by_student_subject(
			array_map(function ($s) { return $s->student_id; }, $students)
		);

		$rows = array();
		foreach ($assignments as $a)
		{
			$subject_id = (int) $a->subject_id;

			// Final grades for the students in this assignment's section.
			$vals = array();
			foreach ($students as $s)
			{
				if ((int) $s->section_id !== (int) $a->section_id)
				{
					continue;
				}
				$periods = isset($gradesByStudent[(int) $s->student_id][$subject_id])
					? $gradesByStudent[(int) $s->student_id][$subject_id]
					: array();
				$final_grade = $this->_final_grade($periods);
				if ($final_grade !== NULL)
				{
					$vals[] = $final_grade;
				}
			}

			$passed = 0;
			$failed = 0;
			foreach ($vals as $v)
			{
				$v <= 3.0 ? $passed++ : $failed++;
			}
			$avg = count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : NULL;

			$rows[] = (object) array(
				'subject_code'  => $a->subject_code,
				'subject_title' => $a->subject_title,
				'instructor'    => $a->instructor,
				'section'       => $a->section_name,
				'units'         => (float) $a->units,
				'students'      => count($vals),
				'average'       => $avg,
				'passed'        => $passed,
				'failed'        => $failed,
			);
		}
		return $rows;
	}

	/**
	 * Simple aggregate tiles computed from summary rows.
	 * @param array $rows
	 * @return array
	 */
	public function tiles(array $rows)
	{
		$total = count($rows);
		$graded = array_filter($rows, function ($r) { return $r->graded; });
		$passed = 0;
		$failed = 0;
		$honors = 0;
		$gwa_sum = 0.0;
		$gwa_count = 0;
		foreach ($graded as $r)
		{
			if ($r->gwa === NULL)
			{
				continue;
			}
			$gwa_sum += $r->gwa;
			$gwa_count++;
			$r->gwa <= 3.0 ? $passed++ : $failed++;
			if ($r->honor !== NULL)
			{
				$honors++;
			}
		}
		return array(
			'total'   => $total,
			'passed'  => $passed,
			'failed'  => $failed,
			'honors'  => $honors,
			'class_gwa' => $gwa_count > 0 ? round($gwa_sum / $gwa_count, 4) : NULL,
		);
	}

	// -----------------------------------------------------------------

	/**
	 * Assignments for the term (optionally scoped to one section), with
	 * instructor name and subject/section details.
	 * @return array
	 */
	private function _term_assignments($semester_id, $section_id)
	{
		$this->db->select(
				'tsa.id AS assignment_id, subjects.id AS subject_id, subjects.code AS subject_code, ' .
				'subjects.title AS subject_title, subjects.units, sections.id AS section_id, ' .
				'sections.name AS section_name, CONCAT(users.first_name, " ", users.last_name) AS instructor'
			)
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('sections', 'sections.id = tsa.section_id')
			->join('users', 'users.id = tsa.teacher_user_id')
			->where('tsa.semester_id', (int) $semester_id);
		if ($section_id !== NULL)
		{
			$this->db->where('tsa.section_id', (int) $section_id);
		}
		return $this->db->order_by('sections.name', 'ASC')->order_by('subjects.code', 'ASC')->get()->result();
	}

	/**
	 * Students belonging to the given section ids (joined with users).
	 * @param array $section_ids
	 * @return array
	 */
	private function _students_for_sections(array $section_ids)
	{
		if (empty($section_ids))
		{
			return array();
		}
		return $this->db->select(
				'students.id AS student_id, students.student_no, students.section_id, ' .
				'users.first_name, users.last_name'
			)
			->from('students')
			->join('users', 'users.id = students.user_id')
			->where_in('students.section_id', $section_ids)
			->where('users.status', 'active')
			->order_by('users.last_name', 'ASC')
			->order_by('users.first_name', 'ASC')
			->get()
			->result();
	}

	/**
	 * All grade rows for a set of student ids, grouped as
	 * student_id => subject_id => period_name => grade_value.
	 * @param array $student_ids
	 * @return array
	 */
	private function _grades_by_student_subject(array $student_ids)
	{
		if (empty($student_ids))
		{
			return array();
		}
		$rows = $this->db->select(
				'grades.student_id, grades.subject_id, grades.grade_value, grading_periods.period_name'
			)
			->from('grades')
			->join('grading_periods', 'grading_periods.id = grades.grading_period_id')
			->where_in('grades.student_id', $student_ids)
			->get()
			->result();

		$out = array();
		foreach ($rows as $g)
		{
			$out[(int) $g->student_id][(int) $g->subject_id][$g->period_name] = $g->grade_value;
		}
		return $out;
	}

	/**
	 * Compute the Final Grade for a subject's period map.
	 * Midterm + Final, weighted 50/50; NULL until both exist.
	 * @param array $periods period_name => grade_value|null
	 * @return float|null
	 */
	private function _final_grade(array $periods)
	{
		$mid   = isset($periods['Midterm']) ? $periods['Midterm'] : NULL;
		$final = isset($periods['Final'])   ? $periods['Final']   : NULL;
		if ($mid === NULL || $final === NULL)
		{
			return NULL;
		}
		return round(((float) $mid + (float) $final) / 2.0, 2);
	}

	/**
	 * Overall status from GWA. NULL GWA (no grades yet) = not marked.
	 * @param float|null $gwa
	 * @return string|null
	 */
	private function _status_for_gwa($gwa)
	{
		if ($gwa === NULL)
		{
			return NULL;
		}
		return $gwa <= 3.0 ? 'Passed' : 'Failed';
	}

	/**
	 * Latin honor from GWA (Philippine college convention used by the UI).
	 * @param float|null $gwa
	 * @return string|null
	 */
	private function _honor_for_gwa($gwa)
	{
		if ($gwa === NULL || $gwa > 1.75)
		{
			return NULL;
		}
		if ($gwa <= 1.20)
		{
			return 'With Highest Honors';
		}
		if ($gwa <= 1.45)
		{
			return 'With High Honors';
		}
		return 'With Honors';
	}
}