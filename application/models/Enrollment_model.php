<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Enrollment_model
 *
 * Student records and teacher<->subject<->section assignments.
 *
 * Ownership checks are the core of the grade-encoding IDOR protection:
 * every assignment lookup that a teacher page performs must go through
 * assignments_for_teacher() / assignment_for_teacher() so a teacher can
 * never reach a class that was not assigned to them.
 */
class Enrollment_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * The student record bound to a user account.
	 * @param int $user_id
	 * @return object|null
	 */
	public function student_by_user_id($user_id)
	{
		return $this->db->where('user_id', (int) $user_id)->get('students')->row();
	}

	/**
	 * The student record by primary key, joined with the user.
	 * @param int $student_id
	 * @return object|null
	 */
	public function student_by_id($student_id)
	{
		return $this->db->select('students.*, users.username, users.email, users.first_name, users.last_name')
			->from('students')
			->join('users', 'users.id = students.user_id')
			->where('students.id', (int) $student_id)
			->get()
			->row();
	}

	/**
	 * Every student in a section, joined with user details.
	 * @param int $section_id
	 * @return array
	 */
	public function students_in_section($section_id)
	{
		return $this->db->select('students.id AS student_id, students.student_no, users.first_name, users.last_name')
			->from('students')
			->join('users', 'users.id = students.user_id')
			->where('students.section_id', (int) $section_id)
			->where('users.status', 'active')
			->order_by('students.student_no', 'ASC')
			->get()
			->result();
	}

	/**
	 * All of a teacher's assignments for a given term.
	 *
	 * @param int $teacher_user_id
	 * @param int $semester_id
	 * @return array assignment objects (with subject/section details)
	 */
	public function assignments_for_teacher($teacher_user_id, $semester_id)
	{
		return $this->db->select(
				'tsa.id AS assignment_id, tsa.schedule, tsa.room, ' .
				'subjects.code AS subject_code, subjects.title AS subject_title, subjects.units, ' .
				'sections.name AS section_name, sections.id AS section_id'
			)
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('sections', 'sections.id = tsa.section_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->where('tsa.semester_id', (int) $semester_id)
			->order_by('subjects.code', 'ASC')
			->order_by('sections.name', 'ASC')
			->get()
			->result();
	}

	/**
	 * Fetch one assignment (joined) whose teacher MUST be $teacher_user_id.
	 * Returns NULL when the assignment does not exist or is not owned by
	 * that teacher — never raise an error with the caller-supplied id.
	 *
	 * @param int $assignment_id
	 * @param int $teacher_user_id
	 * @return object|null
	 */
	public function assignment_for_teacher($assignment_id, $teacher_user_id)
	{
		return $this->db->select(
				'tsa.id AS assignment_id, tsa.teacher_user_id, tsa.semester_id, ' .
				'tsa.schedule, tsa.room, ' .
				'subjects.id AS subject_id, subjects.code AS subject_code, subjects.title AS subject_title, subjects.units, ' .
				'sections.id AS section_id, sections.name AS section_name'
			)
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('sections', 'sections.id = tsa.section_id')
			->where('tsa.id', (int) $assignment_id)
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->get()
			->row();
	}

	/**
	 * Resolve a teacher's assignment by subject code + section name for a
	 * term. Used by the encode-grades filter reload (?subject=&section=).
	 *
	 * @param int    $teacher_user_id
	 * @param string $subject_code
	 * @param string $section_name
	 * @param int    $semester_id
	 * @return object|null
	 */
	public function assignment_for_teacher_by_key($teacher_user_id, $subject_code, $section_name, $semester_id)
	{
		return $this->db->select(
				'tsa.id AS assignment_id, tsa.teacher_user_id, tsa.semester_id, ' .
				'tsa.schedule, tsa.room, ' .
				'subjects.id AS subject_id, subjects.code AS subject_code, subjects.title AS subject_title, subjects.units, ' .
				'sections.id AS section_id, sections.name AS section_name'
			)
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('sections', 'sections.id = tsa.section_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->where('tsa.semester_id', (int) $semester_id)
			->where('subjects.code', $subject_code)
			->where('sections.name', $section_name)
			->get()
			->row();
	}

	/**
	 * All distinct active students taught by a teacher in the active semester.
	 * Used for the teacher→student ticket recipient picker.
	 * Returns objects with: id (user_id), first_name, last_name, student_no, section_name.
	 *
	 * @param int $teacher_user_id
	 * @return array
	 */
	public function get_students_taught_by_teacher($teacher_user_id)
	{
		$sem = $this->db->where('is_active', 1)->get('semesters')->row();
		if ( ! $sem)
		{
			return array();
		}
		return $this->db->select(
				'u.id, u.first_name, u.last_name, s.student_no, sec.name AS section_name'
			)
			->distinct()
			->from('teacher_subject_assignments tsa')
			->join('section_subjects ss',
				'ss.section_id = tsa.section_id AND ss.subject_id = tsa.subject_id AND ss.semester_id = tsa.semester_id')
			->join('students s', 's.section_id = ss.section_id')
			->join('users u', 'u.id = s.user_id')
			->join('sections sec', 'sec.id = ss.section_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->where('tsa.semester_id', (int) $sem->id)
			->where('u.status', 'active')
			->order_by('u.last_name', 'ASC')
			->order_by('u.first_name', 'ASC')
			->get()
			->result();
	}

	/**
	 * Verify a student (by user_id) is currently taught by a teacher
	 * in the active semester. IDOR guard for teacher→student tickets.
	 *
	 * @param int $student_user_id   users.id of the student
	 * @param int $teacher_user_id   users.id of the teacher
	 * @return bool
	 */
	public function student_is_taught_by_teacher($student_user_id, $teacher_user_id)
	{
		$sem = $this->db->where('is_active', 1)->get('semesters')->row();
		if ( ! $sem)
		{
			return FALSE;
		}
		$this->db->from('teacher_subject_assignments tsa')
			->join('section_subjects ss',
				'ss.section_id = tsa.section_id AND ss.subject_id = tsa.subject_id AND ss.semester_id = tsa.semester_id')
			->join('students s', 's.section_id = ss.section_id')
			->join('users u', 'u.id = s.user_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->where('tsa.semester_id', (int) $sem->id)
			->where('u.id', (int) $student_user_id)
			->where('u.status', 'active');
		return $this->db->count_all_results() > 0;
	}

	/**
	 * The distinct sections a teacher is assigned to for a term. Used to
	 * scope the reports page for teachers (they never see "All Sections").
	 *
	 * @param int $teacher_user_id
	 * @param int $semester_id
	 * @return array of section objects
	 */
	public function sections_for_teacher($teacher_user_id, $semester_id)
	{
		return $this->db->select('DISTINCT sections.id, sections.name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('sections', 'sections.id = tsa.section_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->where('tsa.semester_id', (int) $semester_id)
			->order_by('sections.name', 'ASC')
			->get()
			->result();
	}

	/**
	 * The distinct terms (semester with year_label) a teacher has any
	 * assignment in, most recent first. Used by the My Subjects page.
	 *
	 * @param int $teacher_user_id
	 * @return array of term objects (semester_id, year_label, sem_name)
	 */
	public function terms_for_teacher($teacher_user_id)
	{
		return $this->db->select(
				'DISTINCT tsa.semester_id, semesters.year_label, semesters.name AS sem_name', FALSE
			)
			->from('teacher_subject_assignments tsa')
			->join('semesters', 'semesters.id = tsa.semester_id')
			->where('tsa.teacher_user_id', (int) $teacher_user_id)
			->order_by('semesters.year_label', 'DESC')
			// semesters.id equals tsa.semester_id via the join, and ordering by
			// tsa.semester_id keeps the second sort key inside the DISTINCT
			// select list (MySQL error 3065 otherwise).
			->order_by('tsa.semester_id', 'ASC')
			->get()
			->result();
	}

	/**
	 * Number of students in a section.
	 * @param int $section_id
	 * @return int
	 */
	public function count_students_in_section($section_id)
	{
		return (int) $this->db->where('section_id', (int) $section_id)
			->count_all_results('students');
	}

	/**
	 * Verify a student actually belongs to the section a given assignment
	 * teaches (defence against posting grades for unenrolled students).
	 *
	 * @param int $student_id
	 * @param int $assignment_id
	 * @return bool
	 */
	public function student_in_assignment_section($student_id, $assignment_id)
	{
		$this->db->from('students s')
			->join('teacher_subject_assignments tsa', 'tsa.section_id = s.section_id')
			->where('s.id', (int) $student_id)
			->where('tsa.id', (int) $assignment_id);
		return $this->db->count_all_results() > 0;
	}

	/**
	 * Generate the next student number (incrementing the numeric suffix).
	 * @return string e.g. "2023-0011"
	 */
	public function next_student_no()
	{
		$row = $this->db->select('student_no')
			->order_by('student_no', 'DESC')
			->limit(1)
			->get('students')
			->row();

		if ( ! $row || ! preg_match('/^([0-9]{4})-([0-9]+)$/', $row->student_no, $m))
		{
			return date('Y') . '-0001';
		}
		return $m[1] . '-' . str_pad((int) $m[2] + 1, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Create a student record bound to a user account.
	 * @param int $user_id
	 * @param int $section_id
	 * @return int insert id
	 */
	public function create_student($user_id, $section_id, $student_no = NULL)
	{
		$this->db->insert('students', array(
			'user_id'    => (int) $user_id,
			'student_no' => $student_no !== NULL ? $student_no : $this->next_student_no(),
			'section_id' => (int) $section_id,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Remove a student record. Grade rows are protected academic records and
	 * block deletion; related administrative rows (enrollments, correction
	 * requests) are removed first so the student row can be deleted without
	 * hitting a foreign-key constraint.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function delete_student_by_user($user_id)
	{
		$student = $this->student_by_user_id($user_id);
		if ( ! $student)
		{
			return TRUE;
		}
		$student_id = (int) $student->id;

		$has_grades = $this->db->where('student_id', $student_id)->count_all_results('grades') > 0;
		if ($has_grades)
		{
			return FALSE;
		}

		$this->db->trans_start();
		$this->db->where('student_id', $student_id)->delete('enrollments');
		$this->db->where('student_id', $student_id)->delete('grade_correction_requests');
		$this->db->where('id', $student_id)->delete('students');
		$this->db->trans_complete();

		return $this->db->trans_status() !== FALSE;
	}

	/**
	 * Get the semester_id of the student's most recent enrollment.
	 * Returns NULL if the student has never enrolled (new student).
	 *
	 * @param int $student_id
	 * @return int|null
	 */
	public function current_enrollment_semester_id($student_id)
	{
		$row = $this->db->select('semester_id')
			->where('student_id', (int) $student_id)
			->order_by('enrolled_at', 'DESC')
			->limit(1)
			->get('enrollments')
			->row();
		return $row ? (int) $row->semester_id : NULL;
	}

	/**
	 * Get the enrollment record for a student in a specific semester.
	 * Returns the enrollment row (with section_id) or NULL if not enrolled.
	 *
	 * @param int $student_id
	 * @param int $semester_id
	 * @return object|null
	 */
	public function get_enrolled_section($student_id, $semester_id)
	{
		return $this->db->where('student_id', (int) $student_id)
			->where('semester_id', (int) $semester_id)
			->get('enrollments')
			->row();
	}

	/**
	 * Check if a student is eligible to enroll in the next semester.
	 *
	 * @param int $student_id
	 * @return array ['eligible' => bool, 'reason' => string|null, 'target_semester' => object|null]
	 */
	public function is_eligible_for_next_semester($student_id)
	{
		$this->load->model('Academic_model');
		$this->load->model('Grade_model');

		$active_sem = $this->Academic_model->active_semesters();
		if ( ! $active_sem)
		{
			return array('eligible' => FALSE, 'reason' => 'No active semester configured.', 'target_semester' => NULL);
		}

		$student = $this->student_by_id($student_id);
		if ( ! $student)
		{
			return array('eligible' => FALSE, 'reason' => 'Student record not found.', 'target_semester' => NULL);
		}

		// ── 1. Already enrolled in the active semester? ──
		//    (Enrollment page is for the NEXT semester, so being enrolled
		//     in the active semester is expected — we only block if enrolled
		//     in the TARGET semester, which is the one after active.)
		$already_enrolled_active = $this->db->where('student_id', (int) $student_id)
			->where('semester_id', (int) $active_sem->id)
			->count_all_results('enrollments') > 0;
		// Not blocking here — student IS expected to be in the active semester.
		// We only need to find the NEXT semester to enroll into.

		// ── 2. Find the target semester (the one AFTER the active semester) ──
		$target_sem = $this->db->where('id >', (int) $active_sem->id)
			->order_by('id', 'ASC')
			->limit(1)
			->get('semesters')
			->row();

		if ( ! $target_sem)
		{
			return array('eligible' => FALSE, 'reason' => 'No upcoming semester available for enrollment.', 'target_semester' => NULL);
		}

		// Already enrolled in the target semester?
		$already_enrolled_target = $this->db->where('student_id', (int) $student_id)
			->where('semester_id', (int) $target_sem->id)
			->count_all_results('enrollments') > 0;
		if ($already_enrolled_target)
		{
			return array('eligible' => FALSE, 'reason' => 'You are already enrolled for the upcoming semester.', 'target_semester' => NULL);
		}

		// ── 3. Enrollment deadline ──
		if ( ! empty($target_sem->enrollment_deadline))
		{
			$deadline = new DateTime($target_sem->enrollment_deadline);
			$now = new DateTime();
			if ($now > $deadline)
			{
				return array('eligible' => FALSE, 'reason' => 'The enrollment deadline has passed. Please contact the registrar for next school year.', 'target_semester' => NULL);
			}
		}

		// ── 4. Find the student's CURRENT enrollment (the semester they are in now) ──
		$prev_enrollment = $this->db->select('e.semester_id, e.section_id')
			->from('enrollments e')
			->where('e.student_id', (int) $student_id)
			->order_by('e.semester_id', 'DESC')
			->limit(1)
			->get()
			->row();

		if ( ! $prev_enrollment)
		{
			// No prior enrollment at all — new student, eligible
			return array('eligible' => TRUE, 'reason' => NULL, 'target_semester' => $target_sem);
		}

		$prev_semester_id = (int) $prev_enrollment->semester_id;
		$prev_section_id  = (int) $prev_enrollment->section_id;

		// ── 5. Cross-year exemption ──
		$prev_sem_obj    = $this->db->select('year_label')->where('id', $prev_semester_id)->get('semesters')->row();
		$prev_year_label = $prev_sem_obj ? $prev_sem_obj->year_label : '';
		$cross_year = ($prev_year_label !== ''
			&& $target_sem->year_label !== ''
			&& $prev_year_label !== $target_sem->year_label);
		if ($cross_year)
		{
			return array('eligible' => TRUE, 'reason' => NULL, 'target_semester' => $target_sem);
		}

		// ── 6. Check grades for the current semester ──
		$grade_rows = $this->Grade_model->student_term_grades(
			$prev_section_id,
			$prev_semester_id,
			$student_id
		);

		if (empty($grade_rows))
		{
			return array('eligible' => TRUE, 'reason' => NULL, 'target_semester' => $target_sem);
		}

		// ── 7. Scan for incomplete or failed subjects ──
		$incomplete_subjects = array();
		$failed_subjects     = array();
		foreach ($grade_rows as $g)
		{
			if ($g->final_grade === NULL)
			{
				$incomplete_subjects[] = $g;
			}
			else
			{
				$remarks = $this->Grade_model->remarks_for_average($g->final_grade);
				if ($remarks === 'Failed')
				{
					$failed_subjects[] = $g;
				}
			}
		}

		if ( ! empty($incomplete_subjects) || ! empty($failed_subjects))
		{
			$affected_codes = array();
			foreach (array_merge($incomplete_subjects, $failed_subjects) as $g)
			{
				$affected_codes[] = isset($g->code) ? $g->code : (isset($g->subject_code) ? $g->subject_code : '');
			}
			$instructor_info = $this->_instructors_for_section($prev_section_id, $prev_semester_id, $affected_codes);

			if ( ! empty($incomplete_subjects))
			{
				$reason = 'Your grades for the previous semester are not yet complete.';
				if ( ! empty($instructor_info))
				{
					$reason .= ' Please contact your instructor(s) to encode the missing grades:' . PHP_EOL . $instructor_info;
				}
				else
				{
					$reason .= ' Please contact your instructor(s) or the registrar.';
				}
				return array('eligible' => FALSE, 'reason' => $reason, 'target_semester' => NULL);
			}
			if ( ! empty($failed_subjects))
			{
				$reason = 'Your grades for the previous semester show a failed subject.';
				if ( ! empty($instructor_info))
				{
					$reason .= ' Please contact your instructor(s):' . PHP_EOL . $instructor_info;
				}
				else
				{
					$reason .= ' Please contact your instructor(s) or the registrar.';
				}
				return array('eligible' => FALSE, 'reason' => $reason, 'target_semester' => NULL);
			}
		}

		// ── All checks passed ──
		return array('eligible' => TRUE, 'reason' => NULL, 'target_semester' => $target_sem);
	}

	/**
	 * Get eligible sections for a student for a given semester.
	 * Sections must be in the student's current program and have subjects for the semester.
	 *
	 * @param int $student_id
	 * @param int $semester_id
	 * @return array of section objects
	 */
	public function eligible_sections_for_student($student_id, $semester_id)
	{
		$student = $this->student_by_id($student_id);
		if ( ! $student)
		{
			return array();
		}

		// Lock strand to the student's MOST RECENT enrollment section.
		// This prevents strand-switching between semesters: a GAS student
		// can only enroll in GAS sections regardless of students.section_id.
		$prev_enrollment = $this->db->select('e.section_id')
			->from('enrollments e')
			->where('e.student_id', (int) $student_id)
			->order_by('e.semester_id', 'DESC')
			->limit(1)
			->get()
			->row();

		$locked_section_id = $prev_enrollment
			? (int) $prev_enrollment->section_id
			: (int) $student->section_id;

		$locked_section = $this->Academic_model->get_section($locked_section_id);
		if ( ! $locked_section || $locked_section->program_id === NULL)
		{
			return array();
		}

		$program_id = $locked_section->program_id;

		// Auto-sync subjects for sections that don't have them yet
		// This ensures sections appear as eligible even if subjects weren't synced
		$semester = $this->Academic_model->get_semester($semester_id);
		if ($semester && $semester->semester_number !== NULL)
		{
			$synced_ids = array_column(
				$this->db->query(
					'SELECT DISTINCT section_id FROM section_subjects WHERE semester_id = ?',
					array((int) $semester_id)
				)->result_array(),
				'section_id'
			);

			$builder = $this->db->select('sections.id, sections.program_id, sections.year_level')
				->from('sections')
				->join('programs', 'programs.id = sections.program_id', 'left')
				->where('sections.program_id', (int) $program_id)
				->where('sections.is_active', 1)
				->where('sections.year_level IS NOT NULL');

			if ( ! empty($synced_ids))
			{
				$builder->where_not_in('sections.id', $synced_ids);
			}

			$unsynced = $builder->get()->result();

			foreach ($unsynced as $sec)
			{
				$this->Academic_model->sync_section_subjects($sec->id, $semester_id);
			}
		}

		// Only show sections matching the locked section's year level
		$current_year_level = $locked_section->year_level;

		// Get active sections in the same program + same year level that have subjects for this semester
		return $this->db->select('sections.id, sections.name, sections.program_id, sections.year_level')
			->from('sections')
			->join('section_subjects', 'section_subjects.section_id = sections.id')
			->where('sections.program_id', (int) $program_id)
			->where('sections.is_active', 1)
			->where('section_subjects.semester_id', (int) $semester_id)
			->where('sections.year_level', (int) $current_year_level)
			->group_by('sections.id')
			->order_by('sections.name', 'ASC')
			->get()
			->result();
	}

	/**
	 * Enroll a student in a section for a semester.
	 *
	 * @param int $student_id
	 * @param int $section_id
	 * @param int $semester_id
	 * @return bool
	 */
	public function enroll($student_id, $section_id, $semester_id)
	{
		// Re-validate: confirm the section is eligible for this student
		$eligible = $this->eligible_sections_for_student($student_id, $semester_id);
		$valid_section_ids = array_map(function($s) { return (int) $s->id; }, $eligible);
		if ( ! in_array((int) $section_id, $valid_section_ids, TRUE))
		{
			return FALSE;
		}

		$this->db->trans_start();

		// Insert enrollment record
		$this->db->insert('enrollments', array(
			'student_id'      => (int) $student_id,
			'section_id'      => (int) $section_id,
			'semester_id'     => (int) $semester_id,
		));

		if ($this->db->affected_rows() === 0)
		{
			// Likely UNIQUE constraint violation (already enrolled)
			$this->db->trans_rollback();
			return FALSE;
		}

		// Update the student's current section
		$this->db->where('id', (int) $student_id)
			->update('students', array('section_id' => (int) $section_id));

		// Auto-advance the active semester: when a student successfully
		// enrolls into a term AFTER the currently active one (the normal
		// "enroll for next semester" flow), that term becomes the school's
		// new active semester. Dashboards, grade lists and report cards all
		// read the active semester, so they switch to the new term right away.
		$this->load->model('Academic_model');
		$active_sem = $this->Academic_model->active_semesters();
		if ($active_sem && (int) $active_sem->id < (int) $semester_id)
		{
			$this->db->set('is_active', 0)->update('semesters');
			$this->db->where('id', (int) $semester_id)
				->update('semesters', array('is_active' => 1));
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * Build a contact string for instructors teaching specific subjects
	 * in a section/semester. Used when enrollment is blocked due to
	 * incomplete grades so the student knows who to contact.
	 *
	 * @param int    $section_id
	 * @param int    $semester_id
	 * @param array  $subject_codes  e.g. array('ORALCOM', 'GENMATH')
	 * @return string  formatted contact info, e.g. "- Prof. Juan Dela Cruz (juan@school.edu) — ORALCOM"
	 */
	private function _instructors_for_section($section_id, $semester_id, array $subject_codes)
	{
		if (empty($subject_codes)) return '';

		$this->load->model('Academic_model');

		$assignments = $this->db->select(
				'tsa.subject_id, subjects.code AS subject_code, '
				. 'users.first_name, users.last_name, users.email'
		)
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id')
			->where('tsa.section_id', (int) $section_id)
			->where('tsa.semester_id', (int) $semester_id)
			->get()
			->result();

		// Deduplicate by teacher email
		$seen = array();
		$lines = array();
		foreach ($assignments as $a)
		{
			if ( ! in_array($a->subject_code, $subject_codes, TRUE)) continue;
			$key = $a->email;
			if (isset($seen[$key])) continue;
			$seen[$key] = TRUE;
			$lines[] = '- ' . $a->first_name . ' ' . $a->last_name . ' (' . $a->email . ')';
		}

		return implode(PHP_EOL, $lines);
	}
}