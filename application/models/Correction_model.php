<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Correction_model
 *
 * Handles grade correction requests for past semesters.
 * Teacher submits requests, Admin reviews and approves/denies.
 * Approved requests trigger Grade_model::upsert() for the actual grade change.
 */
class Correction_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Create a new correction request (teacher-initiated).
	 *
	 * @param int    $teacher_user_id
	 * @param int    $student_id
	 * @param int    $subject_id
	 * @param int    $grading_period_id
	 * @param float|null $old_value
	 * @param float  $requested_value
	 * @param string $reason
	 * @return int|FALSE insert id or FALSE on failure
	 */
	public function create_request($teacher_user_id, $student_id, $subject_id, $grading_period_id, $old_value, $requested_value, $reason)
	{
		$data = array(
			'teacher_user_id'     => (int) $teacher_user_id,
			'student_id'          => (int) $student_id,
			'subject_id'          => (int) $subject_id,
			'grading_period_id'   => (int) $grading_period_id,
			'old_value'           => $old_value !== NULL ? (float) $old_value : NULL,
			'requested_value'     => (float) $requested_value,
			'reason'              => (string) $reason,
			'status'              => 'pending',
			'created_at'          => date('Y-m-d H:i:s'),
		);

		$this->db->insert('grade_correction_requests', $data);
		if ($this->db->affected_rows() === 0) {
			return FALSE;
		}
		return (int) $this->db->insert_id();
	}

	/**
	 * Check if a pending request already exists for the same student/subject/period by this teacher.
	 *
	 * @param int $teacher_user_id
	 * @param int $student_id
	 * @param int $subject_id
	 * @param int $grading_period_id
	 * @return bool
	 */
	public function has_pending_request($teacher_user_id, $student_id, $subject_id, $grading_period_id)
	{
		return $this->db->where('teacher_user_id', (int) $teacher_user_id)
			->where('student_id', (int) $student_id)
			->where('subject_id', (int) $subject_id)
			->where('grading_period_id', (int) $grading_period_id)
			->where('status', 'pending')
			->count_all_results('grade_correction_requests') > 0;
	}

	/**
	 * Get a single correction request by ID (admin view).
	 *
	 * @param int $request_id
	 * @return object|null
	 */
	public function get_request($request_id)
	{
		return $this->db->select('gcr.*, subjects.code AS subject_code, subjects.title AS subject_title')
			->from('grade_correction_requests gcr')
			->join('subjects', 'subjects.id = gcr.subject_id', 'left')
			->where('gcr.id', (int) $request_id)
			->get()
			->row();
	}

	/**
	 * Get all pending correction requests (admin queue).
	 *
	 * @return array of objects with joined user/subject/period details
	 */
	public function get_pending_requests()
	{
		return $this->db->select(
				'gcr.*, ' .
				'CONCAT(tu.first_name, " ", tu.last_name) AS teacher_name, ' .
				'CONCAT(su.first_name, " ", su.last_name) AS student_name, ' .
				's.student_no, ' .
				'subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'grading_periods.period_name, grading_periods.semester_id, ' .
				'semesters.name AS semester_name, semesters.year_label'
			)
			->from('grade_correction_requests gcr')
			->join('users tu', 'tu.id = gcr.teacher_user_id')
			->join('students s', 's.id = gcr.student_id')
			->join('users su', 'su.id = s.user_id')
			->join('subjects', 'subjects.id = gcr.subject_id')
			->join('grading_periods', 'grading_periods.id = gcr.grading_period_id')
			->join('semesters', 'semesters.id = grading_periods.semester_id')
			->where('gcr.status', 'pending')
			->order_by('gcr.created_at', 'ASC')
			->get()
			->result();
	}

	/**
	 * Get a teacher's own correction requests (for their history page).
	 *
	 * @param int $teacher_user_id
	 * @return array
	 */
	public function get_teacher_requests($teacher_user_id)
	{
		return $this->db->select(
				'gcr.*, ' .
				'CONCAT(su.first_name, " ", su.last_name) AS student_name, ' .
				's.student_no, ' .
				'subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'grading_periods.period_name, ' .
				'semesters.name AS semester_name, semesters.year_label'
			)
			->from('grade_correction_requests gcr')
			->join('students s', 's.id = gcr.student_id')
			->join('users su', 'su.id = s.user_id')
			->join('subjects', 'subjects.id = gcr.subject_id')
			->join('grading_periods', 'grading_periods.id = gcr.grading_period_id')
			->join('semesters', 'semesters.id = grading_periods.semester_id')
			->where('gcr.teacher_user_id', (int) $teacher_user_id)
			->order_by('gcr.created_at', 'DESC')
			->get()
			->result();
	}

	/**
	 * Get a request for admin review (includes all needed data).
	 *
	 * @param int $request_id
	 * @return object|null
	 */
	public function get_request_for_review($request_id)
	{
		return $this->db->select(
				'gcr.*, ' .
				'CONCAT(tu.first_name, " ", tu.last_name) AS teacher_name, ' .
				'CONCAT(su.first_name, " ", su.last_name) AS student_name, ' .
				's.student_no, ' .
				'subjects.code AS subject_code, subjects.title AS subject_title, ' .
				'grading_periods.period_name, grading_periods.semester_id, ' .
				'semesters.name AS semester_name, semesters.year_label, ' .
				'CONCAT(ru.first_name, " ", ru.last_name) AS reviewer_name'
			)
			->from('grade_correction_requests gcr')
			->join('users tu', 'tu.id = gcr.teacher_user_id')
			->join('students s', 's.id = gcr.student_id')
			->join('users su', 'su.id = s.user_id')
			->join('subjects', 'subjects.id = gcr.subject_id')
			->join('grading_periods', 'grading_periods.id = gcr.grading_period_id')
			->join('semesters', 'semesters.id = grading_periods.semester_id')
			->join('users ru', 'ru.id = gcr.reviewed_by', 'left')
			->where('gcr.id', (int) $request_id)
			->get('grade_correction_requests')
			->row();
	}

	/**
	 * Approve a correction request — applies the grade change via Grade_model::upsert()
	 * and updates the request status.
	 *
	 * @param int    $request_id
	 * @param int    $admin_user_id
	 * @param string|null $admin_notes
	 * @return bool
	 */
	public function approve_request($request_id, $admin_user_id, $admin_notes = NULL)
	{
		$request = $this->get_request($request_id);
		if ( ! $request || $request->status !== 'pending')
		{
			return FALSE;
		}

		$this->load->model('Grade_model');
		$this->load->model('Notification_model');

		$this->db->trans_start();

		// Apply the grade change using the existing Grade_model::upsert()
		$grade_id = $this->Grade_model->upsert(
			$request->subject_id,
			$request->student_id,
			$request->grading_period_id,
			$request->teacher_user_id, // teacher who owns the assignment
			(float) $request->requested_value,
			$admin_user_id // encoded_by = admin who approved
		);

		if ( ! $grade_id)
		{
			$this->db->trans_rollback();
			return FALSE;
		}

		// Update the request status
		$this->db->where('id', (int) $request_id)
			->update('grade_correction_requests', array(
				'status'        => 'approved',
				'reviewed_by'   => (int) $admin_user_id,
				'reviewed_at'   => date('Y-m-d H:i:s'),
				'admin_notes'   => $admin_notes !== NULL ? $admin_notes : NULL,
			));

		$this->db->trans_complete();

		if ( ! $this->db->trans_status())
		{
			return FALSE;
		}

// Notify the teacher
		$this->load->model('Notification_model');
		$this->Notification_model->create(
			$request->teacher_user_id,
			'Grade Correction Approved',
			'Your correction request for ' . $request->subject_code . ' (' . $request->period_name . ') has been approved. The grade has been updated.',
			'teacher/my_subjects',
			'system'
		);

		return TRUE;
	}

	/**
	 * Deny a correction request.
	 *
	 * @param int    $request_id
	 * @param int    $admin_user_id
	 * @param string|null $admin_notes
	 * @return bool
	 */
	public function deny_request($request_id, $admin_user_id, $admin_notes = NULL)
	{
		$request = $this->get_request($request_id);
		if ( ! $request || $request->status !== 'pending')
		{
			return FALSE;
		}

		$this->db->where('id', (int) $request_id)
			->update('grade_correction_requests', array(
				'status'        => 'denied',
				'reviewed_by'   => (int) $admin_user_id,
				'reviewed_at'   => date('Y-m-d H:i:s'),
				'admin_notes'   => $admin_notes !== NULL ? $admin_notes : NULL,
			));

		if ($this->db->affected_rows() === 0)
		{
			return FALSE;
		}

		// Notify the teacher
		$this->load->model('Notification_model');
		$this->Notification_model->create(
			$request->teacher_user_id,
			'Grade Correction Denied',
			'Your correction request for ' . $request->subject_code . ' (' . $request->period_name . ') has been denied.' .
				($admin_notes ? ' Admin notes: ' . $admin_notes : ''),
			'teacher/my_subjects',
			'system'
		);

		return TRUE;
	}

	/**
	 * Get a student's current grade for a subject/period (to populate old_value).
	 *
	 * @param int $student_id
	 * @param int $subject_id
	 * @param int $grading_period_id
	 * @return float|null
	 */
	public function get_current_grade($student_id, $subject_id, $grading_period_id)
	{
		$row = $this->db->select('grade_value')
			->where('student_id', (int) $student_id)
			->where('subject_id', (int) $subject_id)
			->where('grading_period_id', (int) $grading_period_id)
			->get('grades')
			->row();
		return $row ? (float) $row->grade_value : NULL;
	}

	/**
	 * Check if a teacher owns an assignment (for validation in request_correction).
	 *
	 * @param int $teacher_user_id
	 * @param int $subject_id
	 * @param int $section_id
	 * @param int $semester_id
	 * @return bool
	 */
	public function teacher_owns_assignment($teacher_user_id, $subject_id, $section_id, $semester_id)
	{
		return $this->db->where('teacher_user_id', (int) $teacher_user_id)
			->where('subject_id', (int) $subject_id)
			->where('section_id', (int) $section_id)
			->where('semester_id', (int) $semester_id)
			->count_all_results('teacher_subject_assignments') > 0;
	}
}