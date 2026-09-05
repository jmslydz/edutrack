<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Teacher
 *
 * Teacher dashboard and grade encoding. All pages require role=teacher.
 *
 * IDOR protection (per the encode_grades view notes):
 *   - A teacher can only ever see/save grades for classes that exist in
 *     teacher_subject_assignments with teacher_user_id = their session id.
 *   - Every assignment lookup on this page is ownership-scoped; a
 *     modified subject/section/assignment_id in the request simply
 *     resolves to NULL and is rejected.
 *   - Every submitted student_id must belong to that assignment's section.
 *   - Every grade is re-validated server-side (numeric, 1.0-5.0, 0.25 steps).
 *   - Changes are written to grade_logs via Grade_model::upsert().
 */
class Teacher extends Teacher_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Academic_model');
		$this->load->model('Enrollment_model');
		$this->load->model('Grade_model');
		$this->load->model('Notification_model');
		$this->load->model('Ticket_model');
	}

	// -----------------------------------------------------------------
	// Dashboard
	// -----------------------------------------------------------------

	public function dashboard()
	{
		$sem = $this->Academic_model->active_semesters();
		$year_label = $sem ? $sem->year_label : '';

		$subjects = array();
		$total_students = 0;
		if ($sem)
		{
			$assignments = $this->Enrollment_model->assignments_for_teacher(
				$this->current_user->id, $sem->id
			);
			foreach ($assignments as $a)
			{
				$progress = $this->Grade_model->progress_for_assignment($a->assignment_id, $a->section_id);
				$subjects[] = array(
					'code'         => $a->subject_code,
					'title'        => $a->subject_title,
					'section'      => $a->section_name,
					'schedule'     => $a->schedule,
					'room'         => $a->room,
					'student_count' => $this->Enrollment_model->count_students_in_section($a->section_id),
					'progress'     => $progress,
				);
				$total_students += $this->Enrollment_model->count_students_in_section($a->section_id);
			}
		}

		$this->data['active_page'] = 'dashboard';
		$this->_render('teacher/dashboard', array(
			'teacher_name'    => $this->current_user->first_name . ' ' . $this->current_user->last_name,
			'subjects'        => $subjects,
			'assigned_count'  => count($subjects),
			'total_students'  => $total_students,
			'semester_label'  => ($sem ? $sem->name : '') . ', ' . $year_label,
			'subtitle'        => 'Faculty',
		));
	}

	// -----------------------------------------------------------------
	// My Subjects
	// -----------------------------------------------------------------

	public function my_subjects()
	{
		$terms = $this->Enrollment_model->terms_for_teacher($this->current_user->id);
		$active_sem = $this->Academic_model->active_semesters();

		$groups = array();
		foreach ($terms as $term)
		{
			$assignments = $this->Enrollment_model->assignments_for_teacher(
				$this->current_user->id, (int) $term->semester_id
			);
			$items = array();
			foreach ($assignments as $a)
			{
				$items[] = array(
					'code'         => $a->subject_code,
					'title'        => $a->subject_title,
					'units'        => $a->units,
					'section'      => $a->section_name,
					'schedule'     => $a->schedule,
					'room'         => $a->room,
					'student_count' => $this->Enrollment_model->count_students_in_section($a->section_id),
					'progress'     => $this->Grade_model->progress_for_assignment($a->assignment_id, $a->section_id),
					'assignment_id' => $a->assignment_id,
					'is_active'    => $active_sem && (int) $term->semester_id === (int) $active_sem->id,
				);
			}
			$groups[] = array(
				'sy_name'    => $term->year_label,
				'sem_name'   => $term->sem_name,
				'sem_id'     => (int) $term->semester_id,
				'is_active'  => $active_sem && (int) $term->semester_id === (int) $active_sem->id,
				'items'      => $items,
			);
		}

		$this->data['active_page'] = 'my_subjects';
		$this->_render('teacher/my_subjects', array(
			'groups'   => $groups,
			'teacher_name' => $this->current_user->first_name . ' ' . $this->current_user->last_name,
			'subtitle' => 'Faculty',
		));
	}

	// -----------------------------------------------------------------
	// Encode grades
	// -----------------------------------------------------------------

	public function encode_grades()
	{
		$sem = $this->Academic_model->active_semesters();

		$assignments = $sem
			? $this->Enrollment_model->assignments_for_teacher($this->current_user->id, $sem->id)
			: array();

		if (empty($assignments))
		{
			$this->session->set_flashdata('grade_error', 'You have no classes assigned for the active school term.');
			$this->data['active_page'] = 'encode_grades';
			$this->_render('teacher/encode_grades', array(
				'subjects'    => array(),
				'sections'    => array(),
				'selected'    => array('assignment_id' => 0, 'subject_code' => '', 'section_name' => '', 'period' => 'Midterm'),
				'students'    => array(),
				'student_count' => 0,
				'encoded_count' => 0,
				'subtitle'    => 'Faculty',
			));
			return;
		}

		// Subject/section filter options limited to this teacher's own classes.
		$subjects = array();
		$sections = array();
		foreach ($assignments as $a)
		{
			$subjects[$a->subject_code] = array('code' => $a->subject_code, 'title' => $a->subject_title);
			$sections[$a->section_name] = array('name' => $a->section_name);
		}
		$subjects = array_values($subjects);
		$sections = array_values($sections);

		// Selected subject/section/period (validated against ownership below).
		$subject_code = trim((string) $this->input->get('subject'));
		$section_name = trim((string) $this->input->get('section'));
		$period       = trim((string) $this->input->get('period'));

		if ( ! in_array($period, Grade_model::PERIODS, TRUE))
		{
			$period = 'Midterm';
		}

		$assignment = NULL;
		if ($subject_code !== '' && $section_name !== '')
		{
			// Ownership-scoped resolution — returns NULL if this teacher
			// has no such class.
			$assignment = $this->Enrollment_model->assignment_for_teacher_by_key(
				$this->current_user->id, $subject_code, $section_name, $sem->id
			);
			if ($assignment === NULL)
			{
				$this->session->set_flashdata('grade_error',
					'You are not assigned to the selected subject/section. Showing your first class instead.');
			}
		}

		if ($assignment === NULL)
		{
			$assignment = $this->Enrollment_model->assignment_for_teacher(
				$assignments[0]->assignment_id, $this->current_user->id
			);
			$subject_code = $assignment ? $assignment->subject_code : '';
			$section_name = $assignment ? $assignment->section_name : '';
		}

		// If even the first assignment fails ownership (should not happen),
		// render an empty state.
		if ($assignment === NULL)
		{
			$this->data['active_page'] = 'encode_grades';
			$this->_render('teacher/encode_grades', array(
				'subjects'    => $subjects,
				'sections'    => $sections,
				'selected'    => array('assignment_id' => 0, 'subject_code' => '', 'section_name' => '', 'period' => $period),
				'students'    => array(),
				'student_count' => 0,
				'encoded_count' => 0,
				'subtitle'    => 'Faculty',
			));
			return;
		}

		$grading_period_id = $sem ? $this->Academic_model->grading_period_id($sem->id, $period) : NULL;

		$students = $this->Enrollment_model->students_in_section($assignment->section_id);
		$grades   = $grading_period_id
			? $this->Grade_model->grades_for_subject_period($assignment->subject_id, $grading_period_id)
			: array();

		$rows = array();
		$encoded = 0;
		foreach ($students as $s)
		{
			$value = isset($grades[(int) $s->student_id]) ? $grades[(int) $s->student_id] : NULL;
			$rows[] = array(
				'student_id'  => (int) $s->student_id,
				'student_no'  => $s->student_no,
				'name'        => $s->last_name . ', ' . $s->first_name,
				'grade'       => $value,
			);
			if ($value !== NULL && $value !== '')
			{
				$encoded++;
			}
		}

		$this->data['active_page'] = 'encode_grades';
		$this->_render('teacher/encode_grades', array(
			'subjects'      => $subjects,
			'sections'      => $sections,
			'selected'      => array(
				'assignment_id' => (int) $assignment->assignment_id,
				'subject_code'  => $assignment->subject_code,
				'section_name'  => $assignment->section_name,
				'period'        => $period,
			),
			'students'      => $rows,
			'student_count' => count($rows),
			'encoded_count' => $encoded,
			'subtitle'      => 'Faculty',
		));
	}

	// -----------------------------------------------------------------
	// Save grades (POST, ownership-checked)
	// -----------------------------------------------------------------

	public function save_grades()
	{
		$this->_require_post();
		$assignment_id = (int) $this->input->post('assignment_id');
		$period        = trim((string) $this->input->post('period'));

		if ( ! in_array($period, Grade_model::PERIODS, TRUE))
		{
			$this->session->set_flashdata('grade_error', 'Invalid grading period.');
			redirect('teacher/encode_grades');
		}

		// Ownership gate: the assignment must belong to this teacher.
		$assignment = $this->Enrollment_model->assignment_for_teacher($assignment_id, $this->current_user->id);
		if ( ! $assignment)
		{
			$this->session->set_flashdata('grade_error', 'You are not assigned to this class. No grades were saved.');
			redirect('teacher/encode_grades');
		}

		// Semester lock: grades for past semesters are permanently locked.
		$active_sem = $this->Academic_model->active_semesters();
		if ( ! $active_sem || (int) $assignment->semester_id !== (int) $active_sem->id)
		{
			$this->session->set_flashdata('grade_error', 'This semester has ended. Grades for past semesters are permanently locked and cannot be edited.');
			redirect('teacher/encode_grades');
		}

		// Resolve the grading period id for the class's semester.
		$grading_period_id = $this->Academic_model->grading_period_id($assignment->semester_id, $period);
		if ( ! $grading_period_id)
		{
			$this->session->set_flashdata('grade_error', 'Invalid grading period.');
			redirect('teacher/encode_grades');
		}

		$submitted = $this->input->post('grades');
		if ( ! is_array($submitted) || empty($submitted))
		{
			$this->session->set_flashdata('grade_error', 'No grades were submitted.');
			redirect('teacher/encode_grades?subject=' . rawurlencode($assignment->subject_code)
				. '&section=' . rawurlencode($assignment->section_name) . '&period=' . $period);
		}

		// 1) Validate everything before writing anything.
		$entries = array();
		$errors  = array();
		foreach ($submitted as $student_id => $raw)
		{
			$student_id = (int) $student_id;
			if ($student_id <= 0)
			{
				$errors[] = 'Invalid student reference detected.';
				break;
			}
			if ( ! $this->Enrollment_model->student_in_assignment_section($student_id, $assignment->assignment_id))
			{
				$errors[] = 'A submitted student is not enrolled in this class.';
				break;
			}

			$value = trim((string) $raw);
			if ($value === '')
			{
				$grade = NULL; // missing grade
			}
			else
			{
				if ( ! is_numeric($value))
				{
					$errors[] = 'All grades must be numeric (1.0 to 5.0).';
					break;
				}
				$grade = (float) $value;
				if ($grade < 1.0 || $grade > 5.0)
				{
					$errors[] = 'Grades must be between 1.0 and 5.0.';
					break;
				}
				if (abs($grade * 4 - round($grade * 4)) > 0.0001)
				{
					$errors[] = 'Grades must be in 0.25 increments (e.g. 1.25, 2.50).';
					break;
				}
			}
			$entries[$student_id] = $grade;
		}

		if ( ! empty($errors))
		{
			$this->session->set_flashdata('grade_error', $errors[0] . ' No grades were saved.');
			redirect('teacher/encode_grades?subject=' . rawurlencode($assignment->subject_code)
				. '&section=' . rawurlencode($assignment->section_name) . '&period=' . $period);
		}

		// 2) Write (audited) inside a transaction.
		$changed_students = array();
		$this->db->trans_start();
		foreach ($entries as $student_id => $grade)
		{
			$previous = $this->Grade_model->get_by_key($assignment->subject_id, $student_id, $grading_period_id);
			$changed  = TRUE;
			if ($previous)
			{
				$old = $previous->grade_value;
				if ($grade === NULL && $old === NULL)
				{
					$changed = FALSE;
				}
				elseif ($grade !== NULL && $old !== NULL && abs((float) $old - $grade) <= 0.0001)
				{
					$changed = FALSE;
				}
			}
			$this->Grade_model->upsert(
				$assignment->subject_id, $student_id, $grading_period_id,
				$assignment->teacher_user_id, $grade, $this->current_user->id
			);
			if ($changed && $grade !== NULL)
			{
				$changed_students[] = (int) $student_id;
			}
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('grade_error', 'Could not save grades. Please try again.');
			redirect('teacher/encode_grades?subject=' . rawurlencode($assignment->subject_code)
				. '&section=' . rawurlencode($assignment->section_name) . '&period=' . $period);
		}

		// Notify the affected students that their grade was updated.
		if ( ! empty($changed_students))
		{
			$user_ids = $this->db->select('user_id')
				->where_in('id', $changed_students)
				->get('students')
				->result();
			$recipients = array();
			foreach ($user_ids as $u)
			{
				$recipients[] = (int) $u->user_id;
			}
			$this->Notification_model->create_many(
				$recipients,
				'Grade updated: ' . $assignment->subject_code . ' — ' . $assignment->section_name,
				'Your ' . $period . ' grade for ' . $assignment->subject_title . ' was updated.',
				'student/dashboard?grade_focus=' . rawurlencode($assignment->subject_code)
					. '&grade_period=' . rawurlencode($period)
			);
		}

		$this->session->set_flashdata('grade_success', 'Grades saved successfully.');
		redirect('teacher/encode_grades?subject=' . rawurlencode($assignment->subject_code)
			. '&section=' . rawurlencode($assignment->section_name) . '&period=' . $period);
	}

	// -----------------------------------------------------------------
	// Tickets
	// -----------------------------------------------------------------

	public function tickets()
	{
		// Tickets this teacher submitted to Admin
		$admin_tickets   = $this->Ticket_model->get_user_tickets($this->current_user->id);
		$counts          = $this->Ticket_model->count_by_status($this->current_user->id);

		// Tickets sent TO this teacher from students
		$from_students   = $this->Ticket_model->get_tickets_received_by_teacher($this->current_user->id);

		// Tickets this teacher sent TO students
		$to_students     = $this->Ticket_model->get_tickets_sent_to_students_by_teacher($this->current_user->id);

		$this->data['active_page'] = 'my_tickets';
		$this->_render('teacher/tickets', array(
			'admin_tickets'  => $admin_tickets,
			'from_students'  => $from_students,
			'to_students'    => $to_students,
			'counts'         => $counts,
			'subtitle'       => 'Faculty',
		));
	}

	public function ticket_submit()
	{
		$categories = array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');

		if ($this->input->method() === 'post')
		{
			$this->_require_post();
			$category = $this->input->post('category');
			$subject  = trim($this->input->post('subject'));
			$message  = trim($this->input->post('message'));

			$errors = array();
			if ( ! in_array($category, $categories, TRUE))
			{
				$errors[] = 'Please select a valid category.';
			}
			if ($subject === '')
			{
				$errors[] = 'Subject is required.';
			}
			if ($message === '')
			{
				$errors[] = 'Message is required.';
			}
			if (strlen($subject) > 150)
			{
				$errors[] = 'Subject must be 150 characters or less.';
			}
			if (strlen($message) > 5000)
			{
				$errors[] = 'Message must be 5000 characters or less.';
			}

			if (empty($errors))
			{
				// Teachers always submit to admin
				$this->Ticket_model->create($this->current_user->id, $category, $subject, $message, 'admin', NULL);
				$this->session->set_flashdata('grade_success', 'Your ticket has been submitted to Admin. You can check the status on the My Tickets page.');
				redirect('teacher/tickets');
			}

			$this->_flash_errors($errors);
		}

		$this->data['active_page'] = 'submit_ticket';
		$this->_render('teacher/ticket_submit', array(
			'categories' => $categories,
			'subtitle'   => 'Faculty',
		));
	}

	public function ticket_view($id)
	{
		$id = (int) $id;

			$categories = array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');
		// (a) tickets they submitted to Admin
		// (b) tickets students sent TO them (recipient_type='teacher', recipient_id=this teacher)
		// (c) tickets they sent to students (recipient_type='student', submitted_by=this teacher)
		$ticket = $this->Ticket_model->get_ticket_for_teacher($id, $this->current_user->id);
		if ( ! $ticket)
		{
			// Try as a received ticket from a student
			$all_received = $this->Ticket_model->get_tickets_received_by_teacher($this->current_user->id);
			foreach ($all_received as $t)
			{
				if ((int) $t->id === $id)
				{
					$ticket = $t;
					break;
				}
			}
		}
		if ( ! $ticket)
		{
			// Try as a teacher→student ticket
			$ticket = $this->Ticket_model->get_teacher_to_student_ticket($id, $this->current_user->id);
		}

		if ( ! $ticket)
		{
			show_error('Ticket not found.', 404, 'Not Found');
		}

		$replies = $this->Ticket_model->get_replies($id);

		$this->data['active_page'] = 'my_tickets';
		$this->_render('teacher/ticket_view', array(
			'ticket'  => $ticket,
			'replies' => $replies,
			'subtitle'=> 'Faculty',
		));
	}

	public function ticket_reply($id)
	{
		$this->_require_post();
		$id = (int) $id;

		// Teacher can reply to: their own admin tickets, received student tickets, sent-to-student tickets
		$ticket = $this->Ticket_model->get_ticket_for_teacher($id, $this->current_user->id);
		if ( ! $ticket)
		{
			$all_received = $this->Ticket_model->get_tickets_received_by_teacher($this->current_user->id);
			foreach ($all_received as $t)
			{
				if ((int) $t->id === $id) { $ticket = $t; break; }
			}
		}
		if ( ! $ticket)
		{
			$ticket = $this->Ticket_model->get_teacher_to_student_ticket($id, $this->current_user->id);
		}

		if ( ! $ticket)
		{
			show_error('Ticket not found.', 404, 'Not Found');
		}

		$message = trim($this->input->post('message'));
		$status = $this->input->post('status');
		if ($message === '')
		{
			$this->session->set_flashdata('grade_error', 'Reply cannot be empty.');
			redirect('teacher/ticket_view/' . $id);
		}
		if (strlen($message) > 5000)
		{
			$this->session->set_flashdata('grade_error', 'Message must be 5000 characters or less.');
			redirect('teacher/ticket_view/' . $id);
		}

		$result = $this->Ticket_model->add_reply($id, $this->current_user->id, $message, $status);
		if ($result !== FALSE)
		{
			$this->session->set_flashdata('grade_success', 'Reply sent.');
		}
		else
		{
			$this->session->set_flashdata('grade_error', 'Failed to send reply.');
		}
		redirect('teacher/ticket_view/' . $id);
	}

	// -----------------------------------------------------------------
	// Message a Student (Teacher → Student ticket)
	// -----------------------------------------------------------------

	public function message_student()
	{
		$categories = array('Grade Concern', 'Missing Activity', 'Other');

		// Get the teacher's current students for the picker
		$my_students = $this->Enrollment_model->get_students_taught_by_teacher($this->current_user->id);

		if ($this->input->method() === 'post')
		{
			$this->_require_post();
			$recipient_id = (int) $this->input->post('recipient_id');
			$category     = $this->input->post('category');
			$subject      = trim($this->input->post('subject'));
			$message      = trim($this->input->post('message'));

			$errors = array();

			// Server-side IDOR guard: student must be taught by this teacher
			if ($recipient_id <= 0 || ! $this->Enrollment_model->student_is_taught_by_teacher($recipient_id, $this->current_user->id))
			{
				$errors[] = 'Invalid recipient. Please select one of your current students.';
			}
			if ( ! in_array($category, $categories, TRUE))
			{
				$errors[] = 'Please select a valid category.';
			}
			if ($subject === '')
			{
				$errors[] = 'Subject is required.';
			}
			if ($message === '')
			{
				$errors[] = 'Message is required.';
			}
			if (strlen($subject) > 150)
			{
				$errors[] = 'Subject must be 150 characters or less.';
			}
			if (strlen($message) > 5000)
			{
				$errors[] = 'Message must be 5000 characters or less.';
			}

			if (empty($errors))
			{
				$this->Ticket_model->create(
					$this->current_user->id,
					$category,
					$subject,
					$message,
					'student',
					$recipient_id
				);
				$this->session->set_flashdata('grade_success', 'Your message has been sent to the student.');
				redirect('teacher/tickets');
			}

			$this->_flash_errors($errors);
		}

		$this->data['active_page'] = 'message_student';
		$this->_render('teacher/message_student', array(
			'categories'  => $categories,
			'my_students' => $my_students,
			'subtitle'    => 'Faculty',
		));
	}

	// -----------------------------------------------------------------
	// Grade Correction Requests (for past semesters only)
	// -----------------------------------------------------------------

	/**
	 * Show the form to request a grade correction for a past semester.
	 * Only accessible for non-active semesters the teacher owns.
	 */
	public function request_correction($assignment_id)
	{
		$assignment_id = (int) $assignment_id;

		// Ownership gate: the assignment must belong to this teacher.
		$assignment = $this->Enrollment_model->assignment_for_teacher($assignment_id, $this->current_user->id);
		if ( ! $assignment)
		{
			show_error('Assignment not found.', 404, 'Not Found');
		}

		// Must be a non-active semester (past semester only)
		$active_sem = $this->Academic_model->active_semesters();
		if ( ! $active_sem || (int) $assignment->semester_id === (int) $active_sem->id)
		{
			$this->session->set_flashdata('grade_error', 'Grade corrections can only be requested for past semesters. Use the regular Encode Grades page for the current term.');
			redirect('teacher/encode_grades?subject=' . rawurlencode($assignment->subject_code) . '&section=' . rawurlencode($assignment->section_name) . '&period=Midterm');
		}

		// Load students in this class
		$students = $this->Enrollment_model->students_in_section($assignment->section_id);

		// Load current grades for this assignment's subject/period
		$this->load->model('Grade_model');
		$grading_periods = $this->Academic_model->grading_periods_for($assignment->semester_id);
		$grades_by_student = array();
		foreach ($grading_periods as $gp)
		{
			$grades = $this->Grade_model->grades_for_subject_period($assignment->subject_id, $gp->id);
			foreach ($grades as $student_id => $val)
			{
				$grades_by_student[(int) $student_id][$gp->period_name] = $val;
			}
		}

		$this->data['active_page'] = 'my_subjects';
		$this->_render('teacher/request_correction', array(
			'assignment'        => $assignment,
			'students'          => $students,
			'grading_periods'   => $grading_periods,
			'grades_by_student' => $grades_by_student,
			'subtitle'          => 'Faculty',
		));
	}

	/**
	 * Submit a grade correction request (POST).
	 */
	public function submit_correction_request()
	{
		$this->_require_post();

		$assignment_id = (int) $this->input->post('assignment_id');
		$student_id    = (int) $this->input->post('student_id');
		$subject_id    = (int) $this->input->post('subject_id');
		$period_name   = trim((string) $this->input->post('period_name'));
		$reason        = trim((string) $this->input->post('reason'));
		$requested_val = $this->input->post('requested_value');

		// Basic validation
		$errors = array();

		if ( ! $assignment_id || ! $student_id || ! $reason || $requested_val === '')
		{
			$errors[] = 'Missing required fields.';
		}

		if ( ! in_array((float) $requested_val, array(1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 3.25, 3.50, 3.75, 4.00, 4.25, 4.50, 4.75, 5.00), TRUE))
		{
			$errors[] = 'Requested grade must be in 0.25 increments between 1.00 and 5.00.';
		}

		if (strlen($reason) < 10)
		{
			$errors[] = 'Reason must be at least 10 characters.';
		}

		if (strlen($reason) > 1000)
		{
			$errors[] = 'Reason must not exceed 1000 characters.';
		}

		// Verify ownership and get assignment details
		$assignment = $this->Enrollment_model->assignment_for_teacher($this->input->post('assignment_id'), $this->current_user->id);
		if ( ! $assignment)
		{
			$errors[] = 'Invalid assignment.';
		}

		// Ensure it's a past semester
		$active_sem = $this->Academic_model->active_semesters();
		if ( ! $active_sem || (int) $assignment->semester_id === (int) $active_sem->id)
		{
			$errors[] = 'Correction requests are only allowed for past semesters.';
		}

		// Validate student is in this class
		if ( ! $this->Enrollment_model->student_in_assignment_section($student_id, $assignment->assignment_id))
		{
			$errors[] = 'Selected student is not enrolled in this class.';
		}

		// Resolve grading period ID
		$this->load->model('Academic_model');
		$grading_period_id = $this->Academic_model->grading_period_id($assignment->semester_id, $this->input->post('period_name'));
		if ( ! $grading_period_id)
		{
			$errors[] = 'Invalid grading period.';
		}

		// Check for duplicate pending request
		$this->load->model('Correction_model');
		if ($grading_period_id && $this->Correction_model->has_pending_request($this->current_user->id, $student_id, $this->input->post('subject_id'), $grading_period_id))
		{
			$errors[] = 'A pending correction request already exists for this student/subject/period.';
		}

		if ( ! empty($errors))
		{
			$this->_flash_errors($errors);
			redirect('teacher/request_correction/' . $assignment_id);
		}

		// Get current grade value using the already-computed grading_period_id and subject_id from $assignment
		$subject_id = $assignment->subject_id;
		$old_value = $this->Correction_model->get_current_grade($student_id, $subject_id, $grading_period_id);

		$requested_value = (float) $this->input->post('requested_value');
		$reason = trim((string) $this->input->post('reason'));

		$this->load->model('Correction_model');
		$request_id = $this->Correction_model->create_request(
			$this->current_user->id,
			$student_id,
			$subject_id,
			$grading_period_id,
			$old_value,
			$requested_value,
			$reason
		);

		if ($request_id === FALSE)
		{
			$this->session->set_flashdata('grade_error', 'Could not create correction request. Please try again.');
			redirect('teacher/request_correction/' . $assignment_id);
		}

		$this->session->set_flashdata('grade_success', 'Correction request submitted. Awaiting Admin review.');
		redirect('teacher/my_subjects');
	}

	// -----------------------------------------------------------------
	// Class List
	// -----------------------------------------------------------------

	public function class_list()
	{
		$sem = $this->Academic_model->active_semesters();
		$assignments = array();
		$selected_section = NULL;
		$selected_subject = NULL;
		$students = array();

		if ($sem)
		{
			$assignments = $this->Enrollment_model->assignments_for_teacher(
				$this->current_user->id, $sem->id
			);

			// Get unique sections from assignments
			$sections = array();
			foreach ($assignments as $a)
				{
				$sections[$a->section_name] = array(
					'name' => $a->section_name,
					'section_id' => $a->section_id
				);
				}
			$sections = array_values($sections);

			// Get selected section from query
			$section_name = trim((string) $this->input->get('section'));
			if ($section_name !== '' && isset($sections[0]))
			{
				foreach ($sections as $s)
				{
					if ($s['name'] === $section_name)
					{
						$selected_section = $s;
						break;
					}
				}
				if ( ! $selected_section)
				{
					$selected_section = $sections[0];
				}
			}
			elseif (isset($sections[0]))
			{
				$selected_section = $sections[0];
			}

			if ($selected_section)
			{
				// Get students in this section
				$students = $this->Enrollment_model->students_in_section($selected_section['section_id']);

				// Get subjects for this section
				$section_subjects = array();
				foreach ($assignments as $a)
				{
					if ($a->section_name === $selected_section['name'])
					{
						$section_subjects[] = $a;
					}
				}
			}
		}

		$this->data['active_page'] = 'class_list';
		$this->_render('teacher/class_list', array(
			'sections'         => isset($sections) ? $sections : array(),
			'selected_section' => $selected_section,
			'students'         => $students,
			'section_subjects' => isset($section_subjects) ? $section_subjects : array(),
			'subtitle'         => 'Faculty',
		));
	}

	/**
	 * Flash error messages for teacher views.
	 */
	protected function _flash_errors(array $errors)
	{
		if (empty($errors))
		{
			return;
		}
		$this->session->set_flashdata('grade_error', implode(' ', array_map('htmlspecialchars', $errors)));
	}
}