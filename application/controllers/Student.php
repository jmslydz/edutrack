<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Student
 *
 * Student dashboard. Requires role=student.
 *
 * IDOR protection: the student identity always comes from the server-side
 * session (users.id), never from a URL/query/POST value. The grade rows
 * and GWA are computed for that exact student record only — changing any
 * request parameter cannot reveal another student's data.
 */
class Student extends Student_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Academic_model');
		$this->load->model('Enrollment_model');
		$this->load->model('Grade_model');
		$this->load->model('Ticket_model');
	}

	public function dashboard()
	{
		// Optional deep-link from a "Grade updated" notification. Lets the
		// dashboard highlight the exact subject/period the notification is
		// about so the student immediately sees the relevant context.
		$focus_code   = strtoupper(trim((string) $this->input->get('grade_focus')));
		$focus_period = trim((string) $this->input->get('grade_period'));
		if ( ! in_array($focus_period, Grade_model::PERIODS, TRUE))
		{
			$focus_period = '';
		}

		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);

		if ( ! $student)
		{
			$this->data['active_page'] = 'dashboard';
			$this->_render('student/dashboard', array(
				'error'         => 'No student record is linked to your account. Please contact the registrar.',
				'grades'        => array(),
				'gwa'           => 0,
				'honor_label'   => 'Good Standing',
				'honor_color'   => '#94A3B8',
				'total_units'   => 0,
				'section_name'  => '—',
				'sy_label'      => '—',
				'semester_label'=> '—',
				'student_no'    => '—',
				'grade_focus'   => $focus_code,
				'grade_period'  => $focus_period,
				'subtitle'      => 'Student Portal',
			));
			return;
		}

		$sy  = $this->Academic_model->active_school_year();
		$sem = $this->Academic_model->active_semesters();

		// Check if student is enrolled in the active semester
		$is_enrolled_in_active = FALSE;
		$enrolled_section = NULL;
		if ($sem)
		{
			$enrolled_section = $this->Enrollment_model->get_enrolled_section($student->id, $sem->id);
			$is_enrolled_in_active = ($enrolled_section !== NULL);
		}

		// Use enrolled section for grades (not the student's default section)
		$section_id_for_grades = $is_enrolled_in_active ? $enrolled_section->section_id : $student->section_id;
		$section = $this->Academic_model->get_section($section_id_for_grades);

		$grade_rows = array();
		$units = 0;
		$gwa   = 0;
		$honor = NULL;

		if ($is_enrolled_in_active && $sem)
		{
			$grade_rows = $this->Grade_model->student_term_grades(
				$enrolled_section->section_id, $sem->id, $student->id
			);

			$sum_units = 0.0;
			$gwa_sum = 0.0;
			$gwa_units = 0.0;
			foreach ($grade_rows as $g)
			{
				$sum_units += $g->units;
				// GWA only counts subjects with a complete Final Grade.
				if ($g->final_grade !== NULL)
				{
					$gwa_sum += $g->final_grade * $g->units;
					$gwa_units += $g->units;
				}
			}
			$units = $sum_units;
			if ($gwa_units > 0)
			{
				$gwa = round($gwa_sum / $gwa_units, 4);
			}
			$honor = $this->_honor_for_gwa($gwa);
		}

		// Get grade history for modals (scoped to this student)
		$grade_history = $sy && $sem ? $this->Grade_model->student_grade_history($student->id) : array();

		// Get weekly schedule for enrolled section
		$schedule_data = array();
		if ($is_enrolled_in_active && $sem)
		{
			$schedule_data = $this->db->select(
				'subjects.code, subjects.title, '
				. 'CONCAT(u.first_name, " ", u.last_name) AS teacher_name, '
				. 'tsa.schedule, tsa.room'
			)
			->from('section_subjects ss')
			->join('subjects', 'subjects.id = ss.subject_id')
			->join('teacher_subject_assignments tsa',
				'tsa.section_id = ss.section_id AND tsa.subject_id = ss.subject_id AND tsa.semester_id = ss.semester_id', 'left')
			->join('users u', 'u.id = tsa.teacher_user_id', 'left')
			->where('ss.section_id', $enrolled_section->section_id)
			->where('ss.semester_id', $sem->id)
			->order_by('subjects.code', 'ASC')
			->get()
			->result();
		}

		// Get ticket counts for dashboard widget
		$ticket_counts = $this->Ticket_model->count_by_status($this->current_user->id);
		$recent_tickets = $this->Ticket_model->get_user_tickets($this->current_user->id);
		$recent_tickets = array_slice($recent_tickets, 0, 5);

		// Check if student can enroll for next semester
		$eligibility = $this->Enrollment_model->is_eligible_for_next_semester($student->id);
		$can_enroll_next_semester = $eligibility['eligible'];
		$enroll_block_reason = $eligibility['eligible'] ? NULL : $eligibility['reason'];

		$honor_color = '#64748B';
		if ($honor !== NULL)
		{
			$honor_color = ($honor === 'With Highest Honors') ? '#7C3AED' : '#F97316';
		}

		$this->data['active_page'] = 'dashboard';
		$this->_render('student/dashboard', array(
			'error'                   => '',
			'student_name'            => $this->current_user->first_name . ' ' . $this->current_user->last_name,
			'grades'                  => $grade_rows,
			'grade_history'           => $grade_history,
			'gwa'                     => $gwa,
			'honor_label'             => $honor ? $honor : 'Good Standing',
			'honor_color'             => $honor_color,
			'total_units'             => $units,
			'section_name'            => $section ? $section->name : '—',
			'sy_label'                => $sy ? $sy->name : '—',
			'semester_label'          => $sem ? $sem->name : '—',
			'student_no'              => $student->student_no,
			'grade_focus'             => $focus_code,
			'grade_period'            => $focus_period,
			'ticket_counts'           => $ticket_counts,
			'recent_tickets'          => $recent_tickets,
			'can_enroll_next_semester' => $can_enroll_next_semester,
			'enroll_block_reason'     => $enroll_block_reason,
			'is_enrolled_in_active'   => $is_enrolled_in_active,
			'active_semester'         => $sem,
			'schedule_data'           => $schedule_data,
			'subtitle'                => 'Student Portal',
		));
	}

	// -----------------------------------------------------------------
	// Tickets
	// -----------------------------------------------------------------

	public function tickets()
	{
		$tickets = $this->Ticket_model->get_all_student_tickets($this->current_user->id);
		$counts  = $this->Ticket_model->count_by_status($this->current_user->id, 'student');

		$this->data['active_page'] = 'my_tickets';
		$this->_render('student/tickets', array(
			'tickets' => $tickets,
			'counts'  => $counts,
			'subtitle'=> 'Student Portal',
		));
	}

	public function ticket_submit()
	{
		$categories = array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');

		// Load the student's enrolled teachers for the recipient picker
		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);
		$my_teachers = array();
		if ($student)
		{
			$sem = $this->Academic_model->active_semesters();
			if ($sem)
			{
				$my_teachers = $this->db->select(
						'tsa.teacher_user_id, CONCAT(u.first_name, " ", u.last_name) AS teacher_name, ' .
						'GROUP_CONCAT(DISTINCT CONCAT(subjects.code, ": ", subjects.title) ORDER BY subjects.code SEPARATOR " | ") AS subjects_label'
					)
					->from('teacher_subject_assignments tsa')
					->join('section_subjects ss', 'ss.section_id = tsa.section_id AND ss.subject_id = tsa.subject_id AND ss.semester_id = tsa.semester_id')
					->join('users u', 'u.id = tsa.teacher_user_id')
					->join('subjects', 'subjects.id = tsa.subject_id')
					->where('ss.section_id', $student->section_id)
					->where('tsa.semester_id', $sem->id)
					->group_by('tsa.teacher_user_id')
					->order_by('teacher_name', 'ASC')
					->get()
					->result();
			}
		}

		$categories = array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');

		if ($this->input->method() === 'post')
		{
			$this->_require_post();
			$recipient_type = $this->input->post('recipient_type');
			$category       = $this->input->post('category');
			$subject        = trim($this->input->post('subject'));
			$message        = trim($this->input->post('message'));
			$recipient_id   = (int) $this->input->post('recipient_id');

			$errors = array();

			if ( ! in_array($recipient_type, array('admin', 'teacher'), TRUE))
			{
				$errors[] = 'Please select a valid recipient.';
			}
			if ($recipient_type === 'teacher' && empty($recipient_id))
			{
				$errors[] = 'Please select a teacher.';
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
					$recipient_type,
					$recipient_type === 'teacher' ? (int) $recipient_id : NULL
				);
				$this->session->set_flashdata('grade_success', 'Your ticket has been submitted. You can check the status on the My Tickets page.');
				redirect('student/tickets');
			}

			$this->_flash_errors($errors);
		}

		$this->data['active_page'] = 'submit_ticket';
		$this->_render('student/ticket_submit', array(
			'categories'  => $categories,
			'my_teachers' => $my_teachers,
			'subtitle'    => 'Student Portal',
		));
	}

	public function ticket_view($id)
	{
		$id = (int) $id;
		// Student can view tickets they submitted OR tickets sent to them by a teacher
		$ticket = $this->Ticket_model->get_ticket_for_student_inbox($id, $this->current_user->id);

		if ( ! $ticket)
		{
			show_error('Ticket not found.', 404, 'Not Found');
		}

		$replies = $this->Ticket_model->get_replies($id);

		// Determine direction for the view
		$direction = ((int) $ticket->submitted_by === (int) $this->current_user->id) ? 'sent' : 'received';

		$this->data['active_page'] = 'my_tickets';
		$this->_render('student/ticket_view', array(
			'ticket'    => $ticket,
			'replies'   => $replies,
			'direction' => $direction,
			'subtitle'  => 'Student Portal',
		));
	}

	public function ticket_reply($id)
	{
		$this->_require_post();
		$id = (int) $id;
		// IDOR: student can only reply to tickets they are a party to
		$ticket = $this->Ticket_model->get_ticket_for_student_inbox($id, $this->current_user->id);

		if ( ! $ticket)
		{
			show_error('Ticket not found.', 404, 'Not Found');
		}

		$message = trim($this->input->post('message'));
		if ($message === '')
		{
			$this->session->set_flashdata('grade_error', 'Reply cannot be empty.');
			redirect('student/ticket_view/' . $id);
		}
		if (strlen($message) > 5000)
		{
			$this->session->set_flashdata('grade_error', 'Message must be 5000 characters or less.');
			redirect('student/ticket_view/' . $id);
		}

		// Student can reply but CANNOT change status
		$result = $this->Ticket_model->add_reply($id, $this->current_user->id, $message, NULL);
		if ($result !== FALSE)
		{
			$this->session->set_flashdata('grade_success', 'Reply sent.');
		}
		else
		{
			$this->session->set_flashdata('grade_error', 'Failed to send reply.');
		}
		redirect('student/ticket_view/' . $id);
	}

	/**
	 * Latin honor from GWA (same thresholds as the reports page).
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

	// -----------------------------------------------------------------
	// Enroll for Next Semester
	// -----------------------------------------------------------------

	public function enroll_next_semester()
	{
		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);
		if ( ! $student)
		{
			$this->session->set_flashdata('grade_error', 'No student record is linked to your account. Please contact the registrar.');
			redirect('student/dashboard');
		}

		$eligibility = $this->Enrollment_model->is_eligible_for_next_semester($student->id);

		if ( ! $eligibility['eligible'])
		{
			$this->data['active_page'] = 'enroll';
			$this->_render('student/enroll', array(
				'eligible'      => FALSE,
				'reason'        => $eligibility['reason'],
				'sections'      => array(),
				'target_semester' => NULL,
				'subtitle'      => 'Student Portal',
			));
			return;
		}

		if ($this->input->method() === 'post')
		{
			$section_id = (int) $this->input->post('section_id');
			if ( ! $section_id)
			{
				$this->session->set_flashdata('grade_error', 'Please select a section.');
				redirect('student/enroll_next_semester');
			}

			$target_semester = $eligibility['target_semester'];
			$ok = $this->Enrollment_model->enroll(
				$student->id,
				$section_id,
				$target_semester->id
			);

			if ($ok)
			{
				$this->session->set_flashdata('grade_success', 'Successfully enrolled for the next semester!');
				redirect('student/dashboard');
			}
			else
			{
				$this->session->set_flashdata('grade_error', 'Could not complete enrollment. Please try again.');
				redirect('student/enroll_next_semester');
			}
		}

		// GET: show eligible sections
		$sections = $this->Enrollment_model->eligible_sections_for_student($student->id, $eligibility['target_semester']->id);

		$this->data['active_page'] = 'enroll';
		$this->_render('student/enroll', array(
			'eligible'        => TRUE,
			'reason'          => NULL,
			'sections'        => $sections,
			'target_semester' => $eligibility['target_semester'],
			'subtitle'        => 'Student Portal',
		));
	}

	// -----------------------------------------------------------------
	// Student Class Schedule
	// -----------------------------------------------------------------

	public function schedule()
	{
		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);
		if ( ! $student)
		{
			$this->session->set_flashdata('grade_error', 'No student record found.');
			redirect('student/dashboard');
		}

		$sem = $this->Academic_model->active_semesters();
		$enrolled_section = NULL;
		$grades = array();

		if ($sem)
		{
			$enrolled_section = $this->Enrollment_model->get_enrolled_section($student->id, $sem->id);
			if ($enrolled_section)
			{
				$grades = $this->Grade_model->student_term_grades(
					$enrolled_section->section_id, $sem->id, $student->id
				);
			}
		}

		$section = $enrolled_section ? $this->Academic_model->get_section($enrolled_section->section_id) : NULL;

		$this->data['active_page'] = 'schedule';
		$this->_render('student/schedule', array(
			'grades'      => $grades,
			'section'     => $section,
			'semester'    => $sem,
			'subtitle'    => 'Student Portal',
		));
	}

	// -----------------------------------------------------------------
	// Enrollment History
	// -----------------------------------------------------------------

	public function enrollment_history()
	{
		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);
		if ( ! $student)
		{
			$this->session->set_flashdata('grade_error', 'No student record found.');
			redirect('student/dashboard');
		}

		// Get all enrollments for this student
		$enrollments = $this->db->select(
					'e.*, s.name AS sem_name, s.year_label, s.semester_number, '
					. 'sec.name AS section_name, p.program_name AS strand_name'
					)
				->from('enrollments e')
				->join('semesters s', 's.id = e.semester_id')
				->join('sections sec', 'sec.id = e.section_id', 'left')
				->join('programs p', 'p.id = sec.program_id', 'left')
				->where('e.student_id', $student->id)
				->order_by('s.year_label', 'DESC')
				->order_by('s.semester_number', 'DESC')
				->get()
				->result();

		// Get grades for each enrollment
		$grades_by_semester = array();
		foreach ($enrollments as $enr)
			{
			$grade_rows = $this->Grade_model->student_term_grades(
				$enr->section_id, $enr->semester_id, $student->id
			);
			$grades_by_semester[$enr->semester_id] = $grade_rows;
			}

		$this->data['active_page'] = 'enrollment_history';
		$this->_render('student/enrollment_history', array(
			'enrollments'        => $enrollments,
			'grades_by_semester' => $grades_by_semester,
			'subtitle'           => 'Student Portal',
		));
	}

	// -----------------------------------------------------------------
	// Report Card (Form 138)
	// -----------------------------------------------------------------

	public function report_card()
	{
		$student = $this->Enrollment_model->student_by_user_id($this->current_user->id);
		if ( ! $student)
		{
			$this->session->set_flashdata('grade_error', 'No student record found.');
			redirect('student/dashboard');
		}

		$sem = $this->Academic_model->active_semesters();
		if ( ! $sem)
		{
			$this->session->set_flashdata('grade_error', 'No active semester.');
			redirect('student/dashboard');
		}

		$enrolled_section = $this->Enrollment_model->get_enrolled_section($student->id, $sem->id);
		if ( ! $enrolled_section)
		{
			$this->session->set_flashdata('grade_error', 'You are not enrolled in the current semester.');
			redirect('student/dashboard');
		}

		$section = $this->Academic_model->get_section($enrolled_section->section_id);
		$grade_rows = $this->Grade_model->student_term_grades(
			$enrolled_section->section_id, $sem->id, $student->id
		);

		// Calculate GWA
		$gwa_sum = 0;
		$gwa_units = 0;
		foreach ($grade_rows as $g)
		{
			if ($g->final_grade !== NULL)
			{
				$gwa_sum += $g->final_grade * $g->units;
				$gwa_units += $g->units;
			}
		}
		$gwa = $gwa_units > 0 ? round($gwa_sum / $gwa_units, 4) : 0;

		// Get school info
		$school_name = 'EduTrack Senior High School';
		$school_address = '123 Education Street, Manila, Philippines';

		$this->data['active_page'] = 'report_card';
		$this->_render('student/report_card', array(
			'student'     => $student,
			'user'        => $this->current_user,
			'section'     => $section,
			'semester'    => $sem,
			'grades'      => $grade_rows,
			'gwa'         => $gwa,
			'school_name' => $school_name,
			'school_address' => $school_address,
			'subtitle'    => 'Student Portal',
		));
	}

	/**
	 * Flash error messages for student views.
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