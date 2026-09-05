<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin
 *
 * Admin dashboard and user management. Every method is guarded by
 * Admin_Controller, so only role=admin (from the session/database) can
 * reach any of these endpoints.
 *
 * Password security rules enforced here:
 *   - passwords are always hashed with password_hash() before storage
 *   - a generated/reset password is NEVER echoed back into HTML or logs
 *   - new accounts and admin resets force a password change on first login
 */
class Admin extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Academic_model');
		$this->load->model('Enrollment_model');
		$this->load->model('Grade_model');
		$this->load->library('pagination');
	}

	// -----------------------------------------------------------------
	// Dashboard
	// -----------------------------------------------------------------

	public function dashboard()
	{
		$sem  = $this->Academic_model->active_semesters();
		$year_label = $sem ? $sem->year_label : '';

		$stats = array(
			'students' => $this->User_model->count_by_role('student', 'active'),
			'teachers' => $this->User_model->count_by_role('teacher', 'active'),
			'sy_name'  => $year_label,
			'sem_name' => $sem ? $sem->name : '—',
		);

		$recent = $this->Grade_model->recent_activity(5);

		// Tickets summary for dashboard widget
		$tickets_open     = $this->db->where('status', 'Open')->count_all_results('tickets');
		$tickets_in_progress = $this->db->where('status', 'In Progress')->count_all_results('tickets');

		// Admissions pipeline (live applicant counts by status)
		$applicant_stats = array(
			'total'          => 0,
			'pending_exam'   => 0,
			'passed_exam'    => 0,
			'failed_exam'    => 0,
			'admitted'       => 0,
			'rejected'       => 0,
			'waiting_code'   => 0, // pending_exam without a code yet
		);
		$applicant_rows = $this->db->select('status, exam_code')->get('applicants')->result();
		$applicant_stats['total'] = count($applicant_rows);
		foreach ($applicant_rows as $ap)
		{
			if (isset($applicant_stats[$ap->status])) { $applicant_stats[$ap->status]++; }
			if ($ap->status === 'pending_exam' && $ap->exam_code === NULL) { $applicant_stats['waiting_code']++; }
		}

		// -----------------------------------------------------------------
		// Insights (all computed from live data for the active semester)
		// -----------------------------------------------------------------
		// Grade scale is 1.00 (best) – 5.00 (worst); 3.00 is the passing line
		// (see Grade_model::remarks_for_average and the encode-grades page).
		$insights = array(
			'grade_bands'    => array('1.00' => 0, '1.25 – 1.50' => 0, '1.75 – 2.00' => 0, '2.25 – 2.50' => 0, '2.75 – 3.00' => 0, '3.01 – 5.00' => 0),
			'strand_dist'    => array(),
			'rooms'          => array(),
			'room_overall'   => NULL,
			'at_risk'        => array(),
			'has_grade_data' => FALSE,
		);

		if ($sem)
		{
			// -- Grade distribution + at-risk students ----------------------
			// A subject's final grade = (Midterm + Final) / 2, scoped to the
			// semester through grading_periods (same rule as the report card).
			$grade_rows = $this->db->select('grades.student_id, grades.subject_id, grades.grade_value, grading_periods.period_name')
				->from('grades')
				->join('grading_periods', 'grading_periods.id = grades.grading_period_id')
				->where('grading_periods.semester_id', (int) $sem->id)
				->get()
				->result();

			$per_subject = array();
			foreach ($grade_rows as $g)
			{
				$key = (int) $g->student_id . '|' . (int) $g->subject_id;
				if ( ! isset($per_subject[$key])) { $per_subject[$key] = array('mid' => NULL, 'final' => NULL); }
				if ($g->period_name === 'Midterm') { $per_subject[$key]['mid'] = (float) $g->grade_value; }
				if ($g->period_name === 'Final')   { $per_subject[$key]['final'] = (float) $g->grade_value; }
			}

			$finals_by_student = array();
			foreach ($per_subject as $key => $parts)
			{
				if ($parts['mid'] === NULL || $parts['final'] === NULL) { continue; }
				$final = round(($parts['mid'] + $parts['final']) / 2.0, 2);
				$sid   = (int) explode('|', $key)[0];
				if ( ! isset($finals_by_student[$sid])) { $finals_by_student[$sid] = array(); }
				$finals_by_student[$sid][] = $final;

				if ($final <= 1.00) { $insights['grade_bands']['1.00']++; }
				elseif ($final <= 1.50) { $insights['grade_bands']['1.25 – 1.50']++; }
				elseif ($final <= 2.00) { $insights['grade_bands']['1.75 – 2.00']++; }
				elseif ($final <= 2.50) { $insights['grade_bands']['2.25 – 2.50']++; }
				elseif ($final <= 3.00) { $insights['grade_bands']['2.75 – 3.00']++; }
				else { $insights['grade_bands']['3.01 – 5.00']++; }
			}
			$insights['has_grade_data'] = count($finals_by_student) > 0;

			if ( ! empty($finals_by_student))
			{
				$enr_rows = $this->db->select('e.student_id, sec.name AS section_name, CONCAT(u.first_name, " ", u.last_name) AS student_name')
					->from('enrollments e')
					->join('students s', 's.id = e.student_id')
					->join('users u', 'u.id = s.user_id')
					->join('sections sec', 'sec.id = e.section_id')
					->where('e.semester_id', (int) $sem->id)
					->get()
					->result();
				$name_map = array();
				foreach ($enr_rows as $er) { $name_map[(int) $er->student_id] = $er; }

				$at_risk = array();
				foreach ($finals_by_student as $sid => $finals)
				{
					$avg = round(array_sum($finals) / count($finals), 2);
					$failing = 0;
					foreach ($finals as $f) { if ($f > 3.00) { $failing++; } }
					if ($failing > 0 || $avg > 3.00)
					{
						$info = isset($name_map[$sid]) ? $name_map[$sid] : NULL;
						$at_risk[] = array(
							'student_name' => $info ? $info->student_name : 'Student #' . $sid,
						'section_name' => $info ? $info->section_name : '—',
						'average'      => $avg,
						'failing'      => $failing,
					);
				}
			}				// On the 1.00-5.00 scale a HIGHER average is worse: worst first.
				usort($at_risk, function ($a, $b) { return $b['average'] - $a['average']; });
			$insights['at_risk'] = array_slice($at_risk, 0, 8);
			}

			// -- Enrollment by strand --------------------------------------
			$strand_rows = $this->db->select('programs.program_code, COUNT(*) AS cnt')
				->from('enrollments e')
				->join('sections sec', 'sec.id = e.section_id')
				->join('programs', 'programs.id = sec.program_id')
				->where('e.semester_id', (int) $sem->id)
				->group_by('programs.program_code')
				->order_by('cnt', 'DESC')
				->get()
				->result();
			foreach ($strand_rows as $sr) { $insights['strand_dist'][$sr->program_code] = (int) $sr->cnt; }

			// -- Room utilization ------------------------------------------
			// School hours = Mon-Fri 6:00 AM - 8:00 PM (14h x 5 days). A
			// booking contributes (end - start) minutes per weekday it covers.
			$rooms_all  = $this->Academic_model->rooms();
			$occupancy  = $this->Academic_model->occupancy_by_room((int) $sem->id);
			$week_minutes = 14 * 60 * 5;
			$total_booked = 0;
			$total_capacity = 0;
			foreach ($rooms_all as $rm)
			{
				$booked = 0;
				if (isset($occupancy[(int) $rm->id]))
				{
					foreach ($occupancy[(int) $rm->id] as $slot)
					{
						$booked += (int) $slot['end_min'] - (int) $slot['start_min'];
					}
				}
				$total_booked += $booked;
				$total_capacity += $week_minutes;
				$insights['rooms'][] = array(
					'id'       => (int) $rm->id,
					'name'     => $rm->name,
					'building' => $rm->building_name,
					'pct'      => round(min(100, $booked / $week_minutes * 100), 1),
					'booked'   => $booked,
					'active'   => (int) $rm->is_active,
				);
			}
			if ($total_capacity > 0)
			{
				$insights['room_overall'] = round($total_booked / $total_capacity * 100, 1);
			}
			usort($insights['rooms'], function ($a, $b) { return $b['pct'] - $a['pct']; });
		}

		$this->data['active_page'] = 'dashboard';
		$this->load->model('Academic_model');
		$this->_render('admin/dashboard', array(
			'stats'               => $stats,
			'recent_activity'     => $recent,
			'tickets_open'        => $tickets_open,
			'tickets_in_progress' => $tickets_in_progress,
			'applicant_stats'     => $applicant_stats,
			'insights'            => $insights,
			'subtitle'            => 'Administration',
		));
	}

	// -----------------------------------------------------------------
	// User management
	// -----------------------------------------------------------------

	public function users()
	{
		$filters = array(
			'search' => trim($this->input->get('search')),
			'role'   => $this->input->get('role'),
			'status' => $this->input->get('status'),
		);

		// Tab filtering (merged Teachers/Students). The value MUST come from
		// the allowed set — an arbitrary string falls back to "all" so it can
		// never reach the query builder or produce unexpected behavior.
		$tab = strtolower((string) $this->input->get('tab'));
		if ( ! in_array($tab, array('all', 'teacher', 'student', 'admin'), TRUE))
		{
			$tab = 'all';
		}
		if ($tab !== 'all')
		{
			$filters['role'] = $tab;
		}
		$filters['tab'] = $tab;

		// Edit preload: admin/users?edit=ID renders the modal open with
		// the selected user's data so the same form is reused.
		$edit_user = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit_user = $this->User_model->get($edit_id);
		}

		$per_page = 8;
		$total = $this->User_model->count_all($filters);

		$this->pagination->initialize(array(
			'base_url'            => site_url('admin/users') . $this->_filter_query_string($filters),
			'total_rows'          => $total,
			'per_page'            => $per_page,
			'page_query_string'   => TRUE,
			'query_string_segment' => 'page',
			'num_links'           => 2,
			'first_link'          => FALSE,
			'last_link'           => FALSE,
			'first_tag_open'      => '',
			'first_tag_close'     => '',
			'last_tag_open'       => '',
			'last_tag_close'      => '',
			'prev_link'           => '&lsaquo;',
			'next_link'           => '&rsaquo;',
			'prev_tag_open'       => '<button class="page-btn" type="button">',
			'prev_tag_close'      => '</button>',
			'next_tag_open'       => '<button class="page-btn" type="button">',
			'next_tag_close'      => '</button>',
			'full_tag_open'       => '<div class="pagination">',
			'full_tag_close'      => '</div>',
			'num_tag_open'        => '<button class="page-btn" type="button">',
			'num_tag_close'       => '</button>',
			'cur_tag_open'        => '<button class="page-btn active" type="button">',
			'cur_tag_close'       => '</button>',
		));

		// CI pagination in page_query_string mode puts the OFFSET in the
		// "page" query param (0, 8, 16...), not a 1-based page number.
		$offset = max(0, (int) $this->input->get('page'));
		$users = $this->User_model->all($filters, $per_page, $offset);

		$student_section = array();
		$student_nos = array();
		foreach ($users as $u)
		{
			$s = $this->Enrollment_model->student_by_user_id($u->id);
			$student_section[$u->id] = $s ? $this->Academic_model->get_section($s->section_id)->name : NULL;
			$student_nos[$u->id]     = $s ? $s->student_no : '';
		}

		// The edit target may not be on the current page; always resolve its
		// section so the modal preselects the real value instead of silently
		// defaulting to the first section in the dropdown.
		if ($edit_user && ! isset($student_section[$edit_user->id]))
		{
			$s = $this->Enrollment_model->student_by_user_id($edit_user->id);
			$student_section[$edit_user->id] = $s ? $this->Academic_model->get_section($s->section_id)->name : NULL;
			$student_nos[$edit_user->id]     = $s ? $s->student_no : '';
		}

		// Teachers tab: class (assignment) count for the active term — the
		// exact logic the old Admin::teachers() page computed per row.
		$assign_counts = array();
		if ($tab === 'teacher' && $users)
		{
			$active_sy  = $this->Academic_model->active_school_year();
			$active_sem = $this->Academic_model->active_semesters();
			foreach ($users as $t)
			{
				$assign_counts[$t->id] = ($active_sy && $active_sem)
					? count($this->Enrollment_model->assignments_for_teacher($t->id, $active_sy->id, $active_sem->id))
					: 0;
			}
		}

		$tiles = array(
			'total'    => $this->User_model->count_by_role(),
			'admins'   => $this->User_model->count_by_role('admin'),
			'teachers' => $this->User_model->count_by_role('teacher'),
			'students' => $this->User_model->count_by_role('student'),
			'active'   => $this->User_model->count_by_role(NULL, 'active'),
		);

		// Only active sections are offered when creating/enrolling students,
		// so deactivated sections cannot receive new students.
		$section_list = $this->Academic_model->sections(TRUE);

		// If the admin is editing a student whose section is currently
		// inactive, keep that section in the dropdown so it preselects
		// instead of silently reassigning the student on save.
		if ($edit_user && $edit_user->role === 'student')
		{
			$active_ids = array();
			foreach ($section_list as $sec)
			{
				$active_ids[(int) $sec->id] = TRUE;
			}
			$st = $this->Enrollment_model->student_by_user_id($edit_user->id);
			if ($st && $st->section_id && ! isset($active_ids[(int) $st->section_id]))
			{
				$cur = $this->Academic_model->get_section($st->section_id);
				if ($cur)
				{
					$section_list[] = $cur;
				}
			}
		}

		$this->data['active_page'] = 'manage_users';
		$this->_render('admin/manage_users', array(
			'users'           => $users,
			'student_section' => $student_section,
			'student_nos'     => $student_nos,
			'assign_counts'   => $assign_counts,
			'active_tab'      => $tab,
			'edit_user'       => $edit_user,
			'tiles'           => $tiles,
			'filtered_total'  => $total,
			'filters'         => $filters,
			'pagination'      => $this->pagination->create_links(),
			'sections'        => $section_list,
			'subtitle'        => 'Administration',
		));
	}

	public function user_store()
	{
		$this->_require_post();
		$data = $this->_user_form_data();
		$errors = $this->_validate_user_form($data);

		if (empty($errors))
		{
			$password = $data['temp_password'] !== '' ? $data['temp_password'] : $this->_random_password();
			$user_id = $this->User_model->create(array(
				'username'             => $data['username'],
				'email'                => $data['email'],
				'role'                 => $data['role'],
				'first_name'           => $data['first_name'],
				'last_name'            => $data['last_name'],
				'status'               => $data['status'],
				'must_change_password' => 1,
				'password'             => $password,
			));

			if ($data['role'] === 'student')
			{
				$this->Enrollment_model->create_student($user_id, $data['section_id']);
			}

			$auto = ($data['temp_password'] === '');
			$this->load->model('Notification_model');
			$this->Notification_model->create(
				$user_id,
				'Welcome to EduTrack',
				'Your account has been created. You will be asked to set a new password on your first login.',
				'auth/login'
			);

			$msg = 'User account created. The new user will be required to set a password on first login.';
			if ($auto)
			{
				$msg = 'User account created. Temporary password: ' . $password .
					' — copy this now and share it with the user directly. ' .
					'It will not be shown again after you leave this page.';
			}
			$this->session->set_flashdata('admin_success', $msg);
			redirect('admin/users');
		}

		$this->_flash_errors($errors);
		redirect('admin/users');
	}

	public function user_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->User_model->get($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'User not found.');
			redirect('admin/users');
		}

		$data = $this->_user_form_data();
		$errors = $this->_validate_user_form($data, $id);

		if (empty($errors))
		{
			$update = array(
				'username'   => $data['username'],
				'email'      => $data['email'],
				'role'       => $data['role'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
				'status'     => $data['status'],
			);

			if ($data['temp_password'] !== '')
			{
				$update['password'] = $data['temp_password'];
				$update['must_change_password'] = 1;
			}

			// Prevent demoting the last active administrator.
			if ($target->role === 'admin' && $data['role'] !== 'admin' && $this->User_model->count_active_admins() <= 1)
			{
				$errors[] = 'Cannot change the role of the last active administrator.';
			}
			else
			{
				// Keep the students record in sync with the role.
				if ($data['role'] === 'student')
				{
					$student = $this->Enrollment_model->student_by_user_id($id);
					if ( ! $student)
					{
						$this->Enrollment_model->create_student($id, $data['section_id']);
					}
					else
					{
						$this->db->where('id', $student->id)->update('students', array('section_id' => $data['section_id']));
					}
				}
				elseif ($target->role === 'student')
				{
					if ( ! $this->Enrollment_model->delete_student_by_user($id))
					{
						$errors[] = 'Cannot change role: this student already has grade records.';
					}
				}

				if (empty($errors))
				{
					$this->User_model->update($id, $update);
					$this->session->set_flashdata('admin_success', 'User account updated.');
					redirect('admin/users');
				}
			}
		}

		$this->_flash_errors($errors);
		redirect('admin/users?edit=' . $id);
	}

	public function user_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		if ($id === (int) $this->current_user->id)
		{
			$this->session->set_flashdata('admin_error', 'You cannot delete your own account.');
			redirect('admin/users');
		}

		$target = $this->User_model->get($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'User not found.');
			redirect('admin/users');
		}

		if ($target->role === 'admin' && $this->User_model->count_active_admins() <= 1)
		{
			$this->session->set_flashdata('admin_error', 'Cannot delete the last active administrator.');
			redirect('admin/users');
		}

		if ($this->_has_dependencies($id))
		{
			$this->session->set_flashdata('admin_error',
				'This user has related records (grades, assignments, or audit logs). Set the account to Inactive instead of deleting.');
			redirect('admin/users');
		}

		if ($target->role === 'student')
		{
			$this->Enrollment_model->delete_student_by_user($id);
		}
		$this->User_model->delete($id);
		$this->session->set_flashdata('admin_success', 'User account deleted.');
		redirect('admin/users');
	}

	public function user_reset_password($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->User_model->get($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'User not found.');
			redirect('admin/users');
		}

		// Random temporary password, hashed before storage. The plaintext
		// is never shown to anyone or written to the logs.
		$temp = $this->_random_password();
		$this->User_model->set_password($id, $temp);

		$this->load->model('Notification_model');
		$this->Notification_model->create(
			$id,
			'Your password was reset',
			'An administrator reset your password. Use the temporary password your administrator gave you and set a new one on your next login.',
			'auth/login'
		);

		$this->session->set_flashdata('admin_success',
			'Password reset for ' . $target->first_name . ' ' . $target->last_name . '. ' .
			'Temporary password: ' . $temp . ' — copy this now and share it with the user directly. ' .
			'It will not be shown again after you leave this page.');
		redirect('admin/users');
	}

	// -----------------------------------------------------------------
	// Teachers / Students (legacy URLs — now tabs inside Manage Users)
	// The old directories were merged into admin/users (see users()).
	// These routes stay live purely as forwards so nothing that links
	// to them 404s.
	// -----------------------------------------------------------------

	public function teachers()
	{
		$this->_redirect_to_users_tab('teacher');
	}

	public function students()
	{
		$this->_redirect_to_users_tab('student');
	}

	// -----------------------------------------------------------------
	// Activity Log (read-only grade_logs audit trail)
	// -----------------------------------------------------------------

	public function activity_log()
	{
		$teacher = (int) $this->input->get('teacher');

		// Date filters must be well-formed, real Y-m-d values; anything else is
		// ignored (a bogus string must never reach the query). createFromFormat
		// silently normalizes overflow (9999-99-99), so round-trip the parsed
		// value back to a string and require an exact match.
		$from_date = '';
		$from = trim($this->input->get('from'));
		if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from))
		{
			$d = DateTime::createFromFormat('Y-m-d', $from);
			if ($d && $d->format('Y-m-d') === $from)
			{
				$from_date = $from;
			}
		}
		$to_date = '';
		$to = trim($this->input->get('to'));
		if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))
		{
			$d = DateTime::createFromFormat('Y-m-d', $to);
			if ($d && $d->format('Y-m-d') === $to)
			{
				$to_date = $to;
			}
		}

		$filters = array(
			'teacher' => $teacher > 0 ? $teacher : '',
			'from'    => $from_date,
			'to'      => $to_date,
		);

		$per_page = 15;
		$total = $this->Grade_model->count_activity_log($filters);

		$this->pagination->initialize(array(
			'base_url'            => site_url('admin/activity_log') . $this->_filter_query_string($filters),
			'total_rows'          => $total,
			'per_page'            => $per_page,
			'page_query_string'   => TRUE,
			'query_string_segment' => 'page',
			'num_links'           => 2,
			'first_link'          => FALSE,
			'last_link'           => FALSE,
			'first_tag_open'      => '',
			'first_tag_close'     => '',
			'last_tag_open'       => '',
			'last_tag_close'      => '',
			'prev_link'           => '&lsaquo;',
			'next_link'           => '&rsaquo;',
			'prev_tag_open'       => '<button class="page-btn" type="button">',
			'prev_tag_close'      => '</button>',
			'next_tag_open'       => '<button class="page-btn" type="button">',
			'next_tag_close'      => '</button>',
			'full_tag_open'       => '<div class="pagination">',
			'full_tag_close'      => '</div>',
			'num_tag_open'        => '<button class="page-btn" type="button">',
			'num_tag_close'       => '</button>',
			'cur_tag_open'        => '<button class="page-btn active" type="button">',
			'cur_tag_close'       => '</button>',
		));

		$offset = max(0, (int) $this->input->get('page'));
		$entries = $this->Grade_model->activity_log_entries($filters, $per_page, $offset);

		// Teacher picker for the "Changed By" filter.
		$this->load->model('User_model');
		$teachers = $this->User_model->all(array('role' => 'teacher'), 1000, 0);

		$this->data['active_page'] = 'activity_log';
		$this->_render('admin/activity_log', array(
			'entries'        => $entries,
			'teachers'       => $teachers,
			'filters'        => $filters,
			'filtered_total' => $total,
			'pagination'     => $this->pagination->create_links(),
			'subtitle'       => 'Administration',
		));
	}

	// -----------------------------------------------------------------
	// Announcements
	// -----------------------------------------------------------------

	public function announcements()
	{
		if ($this->input->method() !== 'post')
		{
			$this->data['active_page'] = 'announcements';
			$this->_render('admin/announcements', array('subtitle' => 'Administration'));
			return;
		}

		$this->_require_post();

		$title = trim($this->input->post('title'));
		$message = trim($this->input->post('message'));
		$audience = $this->input->post('audience');

		$errors = array();
		if ($title === '')
		{
			$errors[] = 'Title is required.';
		}
		if ($message === '')
		{
			$errors[] = 'Message is required.';
		}
		if ( ! in_array($audience, array('all', 'teachers', 'students'), TRUE))
		{
			$errors[] = 'Invalid audience selection.';
		}

		if (empty($errors))
		{
			$this->load->model('User_model');
			$this->load->model('Notification_model');

			if ($audience === 'all')
			{
				$users = $this->User_model->all(array('status' => 'active'));
			}
			elseif ($audience === 'teachers')
			{
				$users = $this->User_model->all(array('role' => 'teacher', 'status' => 'active'));
			}
			else
			{
				$users = $this->User_model->all(array('role' => 'student', 'status' => 'active'));
			}

			$user_ids = array_map(function ($u) { return (int) $u->id; }, $users);

			if ( ! empty($user_ids))
			{
				$this->Notification_model->create_many($user_ids, $title, $message, NULL, 'announcement');
			}

			$this->session->set_flashdata('admin_success', 'Announcement sent to ' . count($user_ids) . ' user(s).');
		}
		else
		{
			$this->_flash_errors($errors);
		}

		redirect('admin/announcements');
	}

	// -----------------------------------------------------------------
	// Tickets
	// -----------------------------------------------------------------

	public function tickets()
	{
		$this->load->model('Ticket_model');

		$filters = array(
			'status'     => $this->input->get('status'),
			'category'   => $this->input->get('category'),
			'from_role'  => $this->input->get('from_role'),
			'search'     => trim($this->input->get('search')),
		);

		$tickets = $this->Ticket_model->get_admin_tickets($filters);

		$this->data['active_page'] = 'tickets';
		$this->_render('admin/tickets', array(
			'tickets'  => $tickets,
			'filters'  => $filters,
			'subtitle' => 'Administration',
		));
	}

	public function ticket_view($id)
	{
		$this->load->model('Ticket_model');
		$this->load->model('Notification_model');
		$this->load->model('User_model');

		$id = (int) $id;
		$ticket = $this->Ticket_model->get_ticket_for_admin($id);

		if ( ! $ticket)
		{
			show_error('Ticket not found.', 404, 'Not Found');
		}

		$replies = $this->Ticket_model->get_replies($id);

		// Handle admin reply + status change (combined)
		if ($this->input->method() === 'post' && $this->input->post('admin_reply'))
		{
			$this->_require_post();
			$message = trim($this->input->post('message'));
			$status = $this->input->post('status');
			if ($message !== '')
			{
				$result = $this->Ticket_model->add_reply($id, $this->current_user->id, $message, $status);
				if ($result !== FALSE)
				{
					// Notify the ticket submitter
					$this->Notification_model->create(
						$ticket->submitted_by,
						'Reply on your ticket: ' . $ticket->subject,
						'An admin has replied to your ticket "' . $ticket->subject . '".',
						'student/ticket_view/' . $id,
						'system'
					);
					$this->session->set_flashdata('admin_success', 'Reply sent.');
				}
				else
				{
					$this->session->set_flashdata('admin_error', 'Failed to send reply.');
				}
			}
			redirect('admin/ticket_view/' . $id);
		}

		$this->data['active_page'] = 'tickets';
		$this->_render('admin/ticket_view', array(
			'ticket'  => $ticket,
			'replies' => $replies,
			'subtitle'=> 'Administration',
		));
	}

	// -----------------------------------------------------------------
	// Grade Submission Status
	// -----------------------------------------------------------------

	public function grade_submission_status()
	{
		$this->load->model('Grade_model');
		$this->load->model('Notification_model');
		$this->load->model('User_model');
		$this->load->model('Academic_model');

		$sem = $this->Academic_model->active_semesters();
		if ( ! $sem)
		{
			$this->data['active_page'] = 'grade_submission_status';
			$this->_render('admin/grade_submission_status', array(
				'sections' => array(),
				'subtitle' => 'Administration',
				'error'    => 'No active semester found.',
			));
			return;
		}

		// Get all sections that have subjects assigned for this semester
		$sections = $this->db->select('sections.id, sections.name, sections.program_id, sections.year_level, programs.program_code')
			->from('sections')
			->join('programs', 'programs.id = sections.program_id', 'left')
			->where('sections.is_active', 1)
			->order_by('programs.program_code', 'ASC')
			->order_by('sections.year_level', 'ASC')
			->order_by('sections.name', 'ASC')
			->get()
			->result();

		$section_data = array();

		foreach ($sections as $sec)
		{
			// Get all subject assignments for this section + semester
			$assignments = $this->db->select(
					'tsa.id AS assignment_id, tsa.subject_id, subjects.code AS subject_code, ' .
					'subjects.title AS subject_title, tsa.teacher_user_id, ' .
					'CONCAT(teacher.first_name, " ", teacher.last_name) AS teacher_name, ' .
					'teacher.email AS teacher_email'
				)
				->from('teacher_subject_assignments tsa')
				->join('subjects', 'subjects.id = tsa.subject_id')
				->join('users teacher', 'teacher.id = tsa.teacher_user_id', 'left')
				->where('tsa.section_id', $sec->id)
				->where('tsa.semester_id', $sem->id)
				->order_by('subjects.code', 'ASC')
				->get()
				->result();

			if (empty($assignments))
			{
				continue;
			}

			// Get students in this section
			$students = $this->db->select('id, user_id')
				->where('section_id', $sec->id)
				->get('students')
				->result();

			$student_ids = array();
			foreach ($students as $s)
			{
				$student_ids[] = $s->id;
			}

			$total_students = count($student_ids);

			// For each assignment, calculate progress per grading period
			$subjects = array();
			foreach ($assignments as $a)
			{
				// Get grading periods for this semester
				$periods = $this->db->where('semester_id', $sem->id)->get('grading_periods')->result();

				$period_progress = array();
				$all_encoded = 0;
				$all_total = 0;

				foreach ($periods as $p)
				{
					if ($total_students === 0)
					{
						$encoded = 0;
					}
					else
					{
						$encoded = (int) $this->db->where('subject_id', $a->subject_id)
							->where('grading_period_id', $p->id)
							->where('grade_value IS NOT NULL')
							->where_in('student_id', $student_ids)
							->count_all_results('grades');
					}
					$total = $total_students * count($periods); // per period
					$period_progress[$p->period_name] = array(
						'encoded' => $encoded,
						'total'   => $total_students,
						'pct'     => $total_students > 0 ? (int) round($encoded / $total_students * 100) : 0,
					);
					$all_encoded += $encoded;
					$all_total += $total_students;
				}

				$overall_pct = $all_total > 0 ? (int) round($all_encoded / $all_total * 100) : 0;

				$subjects[] = array(
					'assignment_id'  => $a->assignment_id,
					'subject_id'     => $a->subject_id,
					'subject_code'   => $a->subject_code,
					'subject_title'  => $a->subject_title,
					'teacher_user_id'=> $a->teacher_user_id,
					'teacher_name'   => $a->teacher_name,
					'teacher_email'  => $a->teacher_email,
					'period_progress'=> $period_progress,
					'overall_pct'    => $overall_pct,
					'is_complete'    => $overall_pct === 100,
				);
			}

			// Section overall completion: average of subject overall percentages
			$section_encoded = 0;
			$section_total = 0;
			foreach ($subjects as $subj)
			{
				$section_encoded += $subj['overall_pct'];
				$section_total++;
			}
			$section_overall = $section_total > 0 ? (int) round($section_encoded / $section_total) : 0;

			$section_data[] = array(
				'id'          => $sec->id,
				'name'        => $sec->name,
				'program_code'=> $sec->program_code,
				'year_level'  => $sec->year_level,
				'subjects'    => $subjects,
				'overall_pct' => $section_overall,
				'total_students' => $total_students,
			);
		}

		$this->data['active_page'] = 'grade_submission_status';
		$this->_render('admin/grade_submission_status', array(
			'sections' => $section_data,
			'subtitle' => 'Administration',
			'active_semester' => $sem,
		));
	}

	/**
	 * Notify instructor for a subject with missing grades.
	 * POST: admin/grade_submission_status/notify
	 */
	public function grade_submission_status_notify()
	{
		$this->_require_post();

		$assignment_id = (int) $this->input->post('assignment_id');
		$section_id = (int) $this->input->post('section_id');
		$period_name = $this->input->post('period_name'); // optional: specific period or 'all'

		$tsa = $this->db->select('tsa.*, subjects.code AS subject_code, subjects.title AS subject_title')
			->from('teacher_subject_assignments tsa')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->where('tsa.id', $assignment_id)
			->get()
			->row();

		if ( ! $tsa)
		{
			echo json_encode(array('success' => false, 'message' => 'Assignment not found.'));
			return;
		}

		if ( ! $tsa->teacher_user_id)
		{
			echo json_encode(array('success' => false, 'message' => 'No teacher assigned.'));
			return;
		}

		$sem = $this->Academic_model->active_semesters();
		$period_text = $period_name ? ' (' . $period_name . ')' : '';

		$this->Notification_model->create(
			$tsa->teacher_user_id,
			'Grade Submission Reminder',
			'Grades are still needed for ' . $tsa->subject_code . ' — ' . $tsa->subject_title . $period_text . ' for section ' . $this->db->select('name')->where('id', $section_id)->get('sections')->row()->name . '. Please encode the missing grades.',
			'teacher/encode_grades',
			'system'
		);

		echo json_encode(array('success' => true, 'message' => 'Reminder sent to ' . $this->db->select('CONCAT(first_name, " ", last_name) AS name')->where('id', $tsa->teacher_user_id)->get('users')->row()->name . '.'));
	}

	// -----------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------

	/**
	 * Forward a legacy directory URL (admin/teachers, admin/students) to its
	 * equivalent tab on Manage Users. Carries the search term across so the
	 * admin does not lose their query.
	 * @param string $tab 'teacher'|'student'
	 */
	private function _redirect_to_users_tab($tab)
	{
		$qs = array('tab' => $tab);
		$search = trim($this->input->get('search'));
		if ($search !== '')
		{
			$qs['search'] = $search;
		}
		redirect('admin/users?' . http_build_query($qs));
	}

	private function _user_form_data()
	{
		$section_id = (int) $this->input->post('section');
		$status = $this->input->post('status');

		return array(
			'first_name'    => trim($this->input->post('first_name')),
			'last_name'     => trim($this->input->post('last_name')),
			'username'      => trim($this->input->post('username')),
			'email'         => trim($this->input->post('email')),
			'role'          => $this->input->post('role'),
			'section_id'    => $section_id,
			'status'        => in_array($status, array('active', 'inactive'), TRUE) ? $status : 'active',
			'temp_password' => (string) $this->input->post('temp_password'),
		);
	}

	/**
	 * Server-side validation for the add/edit user form. Returns an array
	 * of error strings (empty = valid).
	 *
	 * @param array    $d
	 * @param int|null $except_id user id being edited (for uniqueness)
	 * @return array
	 */
	private function _validate_user_form(array $d, $except_id = NULL)
	{
		$errors = array();

		if (preg_match('/^[A-Za-z\s\-\'\.]+$/u', $d['first_name']) !== 1)
		{
			$errors[] = 'First name must contain letters only.';
		}
		if (preg_match('/^[A-Za-z\s\-\'\.]+$/u', $d['last_name']) !== 1)
		{
			$errors[] = 'Last name must contain letters only.';
		}
		if ( ! preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $d['username']))
		{
			$errors[] = 'Username must be 3-50 characters (letters, numbers, dot, dash, underscore).';
		}
		elseif ($this->User_model->username_exists($d['username'], $except_id))
		{
			$errors[] = 'That username is already taken.';
		}
		if ( ! filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 120)
		{
			$errors[] = 'A valid email address is required.';
		}
		elseif ($this->User_model->email_exists($d['email'], $except_id))
		{
			$errors[] = 'That email address is already registered.';
		}
		if ( ! in_array($d['role'], array('admin', 'teacher', 'student'), TRUE))
		{
			$errors[] = 'Invalid role selected.';
		}
		if ($d['role'] === 'student')
		{
			$section = $this->Academic_model->get_section($d['section_id']);
			if ( ! $section)
			{
				$errors[] = 'A valid section is required for student accounts.';
			}
		}
		if ($d['temp_password'] !== '' && (strlen($d['temp_password']) < 8 || strlen($d['temp_password']) > 72))
		{
			$errors[] = 'Temporary password must be between 8 and 72 characters.';
		}
		return $errors;
	}

	protected function _flash_errors(array $errors)
	{
		if (empty($errors))
		{
			return;
		}
		$this->session->set_flashdata('admin_error', implode(' ', array_map('htmlspecialchars', $errors)));
	}

	private function _random_password($length = 12)
	{
		$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$out = '';
		$max = strlen($chars) - 1;
		for ($i = 0; $i < $length; $i++)
		{
			$out .= $chars[random_int(0, $max)];
		}
		return $out;
	}

	/**
	 * Whether a user row is referenced by other tables (grades,
	 * assignments, audit logs, students-with-grades).
	 * @param int $user_id
	 * @return bool
	 */
	private function _has_dependencies($user_id)
	{
		$checks = array(
			'teacher_subject_assignments' => array('teacher_user_id' => $user_id),
			'grades'                      => array('teacher_id' => $user_id),
			'grade_logs'                  => array('changed_by' => $user_id),
			'grade_correction_requests'   => array('reviewed_by' => $user_id),
			'tickets'                     => array('submitted_by' => $user_id),
		);
		foreach ($checks as $table => $where)
		{
			if ($this->db->where($where)->count_all_results($table) > 0)
			{
				return TRUE;
			}
		}
		$student = $this->Enrollment_model->student_by_user_id($user_id);
		if ($student && $this->db->where('student_id', $student->id)->count_all_results('grades') > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Preserve the list filters across pagination links.
	 * @param array $filters
	 * @return string
	 */
	/**
	 * Run the ticket recipient migration (one-time use).
	 * Remove or comment out after running.
	 */
	public function run_ticket_migration()
	{
		$sql = file_get_contents(APPPATH . '../database/migration_2026_tickets_recipient.sql');
		if ($this->db->multi_query($sql)) {
			do {
				if ($result = $this->db->store_result()) {
					$result->free();
				}
			} while ($this->db->more_results() && $this->db->next_result());
			echo "Migration executed successfully!";
		} else {
			echo "Migration ERROR: " . $this->db->error();
		}
	}

	private function _filter_query_string(array $filters)
	{
		$parts = array();
		foreach ($filters as $k => $v)
		{
			if ($v !== '' && $v !== NULL)
			{
				$parts[] = $k . '=' . rawurlencode($v);
			}
		}
		return $parts ? '?' . implode('&', $parts) : '';
	}

	// -----------------------------------------------------------------
	// Grade Correction Requests (Admin review queue)
	// -----------------------------------------------------------------

	public function correction_requests()
	{
		$this->load->model('Correction_model');
		$requests = $this->Correction_model->get_pending_requests();

		$this->data['active_page'] = 'correction_requests';
		$this->_render('admin/correction_requests', array(
			'requests' => $requests,
			'subtitle' => 'Administration',
		));
	}

	// -----------------------------------------------------------------
	// Admissions — applicants + admission exam
	// -----------------------------------------------------------------

	/**
	 * List all applicants with their exam status and actions.
	 */
	public function applicants()
	{
		$this->load->model('Notification_model');

		$applicants = $this->db->select(
				'applicants.*, users.first_name, users.last_name, users.email, '
				. 'programs.program_name AS preferred_program_name, '
				. 'sections.name AS admitted_section_name'
			)
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->join('programs', 'programs.id = applicants.preferred_program_id', 'left')
			->join('sections', 'sections.id = applicants.admitted_section_id', 'left')
			->order_by('applicants.created_at', 'DESC')
			->get()
			->result();

		// Sections available for the admit flow (all active sections).
		$sections = $this->Academic_model->sections(TRUE);

		$this->data['active_page'] = 'applicants';
		$this->_render('admin/applicants', array(
			'applicants' => $applicants,
			'sections'   => $sections,
			'subtitle'   => 'Administration',
		));
	}

	/**
	 * POST: generate a fresh one-time exam code for an applicant.
	 */
	public function applicant_generate_code($id)
	{
		$this->_require_post();
		$id = (int) $id;

		$applicant = $this->db->select('applicants.*, users.first_name, users.last_name')
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->where('applicants.id', $id)
			->get()
			->row();

		if ( ! $applicant)
		{
			$this->session->set_flashdata('admin_error', 'Applicant not found.');
			redirect('admin/applicants');
		}

		if ($applicant->status !== 'pending_exam')
		{
			$this->session->set_flashdata('admin_error', 'An exam code can only be issued to an applicant waiting for the exam.');
			redirect('admin/applicants');
		}

		$code = strtoupper(bin2hex(random_bytes(4))); // e.g. 16 hex chars

		$this->db->where('id', $id)->update('applicants', array('exam_code' => $code));

		$this->session->set_flashdata('admin_success',
			'Exam code generated for ' . $applicant->first_name . ' ' . $applicant->last_name . ': CODE ' . $code .
			' — give this code to the applicant in person at the campus. It can only be used once.');
		redirect('admin/applicants');
	}

	/**
	 * POST: admit an applicant — create the student record + enrollment,
	 * notify in-app, and email the acceptance.
	 */
	public function applicant_admit($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$section_id = (int) $this->input->post('section_id');

		$this->load->model('Notification_model');
		$this->load->config('email');

		$applicant = $this->db->select('applicants.*, users.first_name, users.last_name, users.email')
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->where('applicants.id', $id)
			->get()
			->row();

		if ( ! $applicant)
		{
			$this->session->set_flashdata('admin_error', 'Applicant not found.');
			redirect('admin/applicants');
		}

		if ($applicant->status === 'admitted')
		{
			$this->session->set_flashdata('admin_error', 'This applicant is already admitted.');
			redirect('admin/applicants');
		}

		$section = $this->Academic_model->get_section($section_id);
		if ( ! $section)
		{
			$this->session->set_flashdata('admin_error', 'Please select a valid section to admit the applicant into.');
			redirect('admin/applicants');
		}

		$this->db->trans_start();

		// Upgrade the user to student role.
		$this->User_model->update($applicant->user_id, array('role' => 'student'));

		// Create the student record + enroll in the active semester.
		$student_id = $this->Enrollment_model->create_student($applicant->user_id, $section_id);
		$sem = $this->Academic_model->active_semesters();
		if ($sem)
		{
			$this->db->insert('enrollments', array(
				'student_id'  => $student_id,
				'semester_id' => $sem->id,
				'section_id'  => $section_id,
			));
		}

		// Mark the applicant admitted.
		$this->db->where('id', $id)->update('applicants', array(
			'status'              => 'admitted',
			'admitted_section_id' => $section_id,
		));

		$this->db->trans_complete();

		if ( ! $this->db->trans_status())
		{
			$this->session->set_flashdata('admin_error', 'Could not admit the applicant. Please try again.');
			redirect('admin/applicants');
		}

		// In-app notification.
		$this->Notification_model->create(
			$applicant->user_id,
			'You are admitted! Welcome to EduTrack',
			'Congratulations! Your application has been approved. You are now enrolled in section ' . $section->name . '.',
			'student/dashboard'
		);

		// Email the acceptance (best effort — failures are logged, never shown).
		$this->_send_admission_email($applicant, 'admitted', $section->name);

		$this->session->set_flashdata('admin_success',
			$applicant->first_name . ' ' . $applicant->last_name . ' admitted to section ' . $section->name . ' and notified.');
		redirect('admin/applicants');
	}

	/**
	 * POST: reject an applicant.
	 */
	public function applicant_reject($id)
	{
		$this->_require_post();
		$id = (int) $id;

		$this->load->model('Notification_model');
		$this->load->config('email');

		$applicant = $this->db->select('applicants.*, users.first_name, users.last_name, users.email')
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->where('applicants.id', $id)
			->get()
			->row();

		if ( ! $applicant)
		{
			$this->session->set_flashdata('admin_error', 'Applicant not found.');
			redirect('admin/applicants');
		}

		$this->db->where('id', $id)->update('applicants', array('status' => 'rejected'));

		$this->Notification_model->create(
			$applicant->user_id,
			'Admission update',
			'After reviewing your application, we are unable to admit you at this time.',
			'applicant/dashboard'
		);
		$this->_send_admission_email($applicant, 'rejected');

		$this->session->set_flashdata('admin_success', $applicant->first_name . ' ' . $applicant->last_name . ' has been rejected and notified.');
		redirect('admin/applicants');
	}

	/**
	 * POST: allow an applicant to retake the admission exam.
	 * Wipes the previous attempt (answers, score, timestamps, program
	 * choice) and resets them to pending_exam so a fresh code can be
	 * issued. The applicant is notified in-app.
	 */
	public function applicant_retake($id)
	{
		$this->_require_post();
		$id = (int) $id;

		$this->load->model('Notification_model');

		$applicant = $this->db->select('applicants.*, users.first_name, users.last_name, users.email')
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->where('applicants.id', $id)
			->get()
			->row();

		if ( ! $applicant)
		{
			$this->session->set_flashdata('admin_error', 'Applicant not found.');
			redirect('admin/applicants');
		}

		if ( ! in_array($applicant->status, array('failed_exam', 'rejected'), TRUE))
		{
			$this->session->set_flashdata('admin_error',
				'Retake is only available for applicants who failed the exam or were not admitted.');
			redirect('admin/applicants');
		}

		$this->db->trans_start();

		// Remove the previous attempt's snapshot answers.
		$this->db->where('applicant_id', $id)->delete('exam_answers');

		// Reset to pending_exam with all exam state cleared.
		$this->db->where('id', $id)->update('applicants', array(
			'status'              => 'pending_exam',
			'exam_code'           => NULL,
			'exam_started_at'     => NULL,
			'exam_finished_at'    => NULL,
			'exam_score'          => NULL,
			'exam_total'          => NULL,
			'exam_passed'         => NULL,
			'preferred_program_id'=> NULL,
			'admitted_section_id' => NULL,
		));

		$this->db->trans_complete();

		if ( ! $this->db->trans_status())
		{
			$this->session->set_flashdata('admin_error', 'Could not reset the applicant for retake. Please try again.');
			redirect('admin/applicants');
		}

		$this->Notification_model->create(
			$applicant->user_id,
			'You may retake the admission exam',
			'You have been allowed to retake the admission exam. Please visit the Registrar\'s Office at the campus to receive a new one-time exam code.',
			'applicant/dashboard'
		);

		$this->session->set_flashdata('admin_success',
			$applicant->first_name . ' ' . $applicant->last_name . ' can now retake the exam. Issue a new exam code for them.');
		redirect('admin/applicants');
	}

	/**
	 * POST: delete an applicant (only before they are admitted).
	 */
	public function applicant_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;

		$applicant = $this->db->where('id', $id)->get('applicants')->row();
		if ( ! $applicant)
		{
			$this->session->set_flashdata('admin_error', 'Applicant not found.');
			redirect('admin/applicants');
		}

		if ($applicant->status === 'admitted')
		{
			$this->session->set_flashdata('admin_error', 'Admitted applicants cannot be deleted. Deactivate the user account instead.');
			redirect('admin/applicants');
		}

		// applicants/exam_answers cascade via FK; remove the user too.
		$this->db->where('id', $id)->delete('applicants');
		$this->User_model->delete($applicant->user_id);

		$this->session->set_flashdata('admin_success', 'Applicant deleted.');
		redirect('admin/applicants');
	}

	// -----------------------------------------------------------------
	// Admission exam — question bank
	// -----------------------------------------------------------------

	public function exam_questions()
	{
		$questions = $this->db->order_by('id', 'ASC')->get('exam_questions')->result();

		$edit_q = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit_q = $this->db->where('id', $edit_id)->get('exam_questions')->row();
		}

		$this->data['active_page'] = 'exam_questions';
		$this->_render('admin/exam_questions', array(
			'questions'      => $questions,
			'edit_q'         => $edit_q,
			// Keep in sync with Applicant::EXAM_TIME_MINUTES / QUESTIONS_PER_EXAM / PASS_PERCENT
			'exam_minutes'   => 20,
			'exam_per_exam'  => 15,
			'exam_pass_pct'  => 70,
			'subtitle'       => 'Administration',
		));
	}

	public function exam_question_store()
	{
		$this->_require_post();
		$data = $this->_exam_question_form_data();
		$errors = $this->_validate_exam_question($data);

		if (empty($errors))
		{
			$this->db->insert('exam_questions', $data);
			$this->session->set_flashdata('admin_success', 'Question added to the exam bank.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('admin/exam_questions');
	}

	public function exam_question_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$data = $this->_exam_question_form_data();
		$errors = $this->_validate_exam_question($data);

		if (empty($errors))
		{
			$this->db->where('id', $id)->update('exam_questions', $data);
			$this->session->set_flashdata('admin_success', 'Question updated.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('admin/exam_questions');
	}

	public function exam_question_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$this->db->where('id', $id)->delete('exam_questions');
		$this->session->set_flashdata('admin_success', 'Question deleted from the exam bank.');
		redirect('admin/exam_questions');
	}

	public function exam_question_toggle($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$q = $this->db->where('id', $id)->get('exam_questions')->row();
		if ($q)
		{
			$this->db->where('id', $id)->update('exam_questions', array('is_active' => $q->is_active ? 0 : 1));
		}
		redirect('admin/exam_questions');
	}

	// -----------------------------------------------------------------
	// Admissions helpers
	// -----------------------------------------------------------------

	/**
	 * Read the question form into a safe array.
	 * @return array
	 */
	private function _exam_question_form_data()
	{
		$correct = strtoupper(trim((string) $this->input->post('correct_answer')));
		if ( ! in_array($correct, array('A', 'B', 'C', 'D'), TRUE))
		{
			$correct = 'A';
		}
		return array(
			'question'       => trim((string) $this->input->post('question')),
			'option_a'       => trim((string) $this->input->post('option_a')),
			'option_b'       => trim((string) $this->input->post('option_b')),
			'option_c'       => trim((string) $this->input->post('option_c')),
			'option_d'       => trim((string) $this->input->post('option_d')),
			'correct_answer' => $correct,
		);
	}

	/**
	 * Validate exam question form data.
	 * @param array $d
	 * @return array of error strings
	 */
	private function _validate_exam_question(array $d)
	{
		$errors = array();
		if ($d['question'] === '')
		{
			$errors[] = 'Question text is required.';
		}
		foreach (array('option_a', 'option_b', 'option_c', 'option_d') as $opt)
		{
			if ($d[$opt] === '')
			{
				$errors[] = 'All four answer choices are required.';
				break;
			}
		}
		return $errors;
	}

	/**
	 * Best-effort admission result email. Failures are logged, never shown
	 * to the admin (the SMTP account may still be a placeholder).
	 *
	 * @param object      $applicant  applicants row + user fields
	 * @param string      $outcome    'admitted'|'rejected'
	 * @param string|null $section_name
	 */
	private function _send_admission_email($applicant, $outcome, $section_name = NULL)
	{
		$name = $applicant->first_name . ' ' . $applicant->last_name;

		if ($outcome === 'admitted')
		{
			$subject = 'Congratulations — you have been admitted to EduTrack!';
			$body = '<p>Dear ' . html_escape($name) . ',</p>'
				. '<p>Congratulations! We are pleased to inform you that you have been <strong>admitted</strong> '
				. 'to EduTrack Senior High School' . ($section_name ? ' (Section ' . html_escape($section_name) . ')' : '') . '.</p>'
				. '<p>You can now sign in to the student portal with the account you registered with to see your class schedule and grades.</p>'
				. '<p>Welcome aboard!</p>';
		}
		else
		{
			$subject = 'Update on your EduTrack application';
			$body = '<p>Dear ' . html_escape($name) . ',</p>'
				. '<p>Thank you for applying to EduTrack Senior High School. After careful review of your application, '
				. 'we regret to inform you that we are unable to admit you at this time.</p>'
				. '<p>If you believe this is a mistake, please contact the Registrar\'s Office.</p>';
		}

		$this->email->from($this->config->item('smtp_user'), 'EduTrack Admissions');
		$this->email->to($applicant->email);
		$this->email->subject($subject);
		$this->email->message($body);

		if ( ! $this->email->send())
		{
			log_message('error', 'Admission email failed for applicant ' . $applicant->id . ': ' . $this->email->print_debugger());
		}
	}

	public function review_correction_request($request_id)
	{
		$request_id = (int) $request_id;
		$this->load->model('Correction_model');
		$request = $this->Correction_model->get_request_for_review($request_id);

		if ( ! $request)
		{
			show_error('Correction request not found.', 404, 'Not Found');
		}

		if ($this->input->method() === 'post')
		{
			$this->_require_post();
			$action = $this->input->post('action');
			$admin_notes = trim((string) $this->input->post('admin_notes'));

			if ($action === 'approve')
			{
				$ok = $this->Correction_model->approve_request($request_id, $this->current_user->id, $admin_notes);
				if ($ok)
				{
					$this->session->set_flashdata('admin_success', 'Correction request approved and grade updated.');
				}
				else
				{
					$this->session->set_flashdata('admin_error', 'Failed to approve correction request.');
				}
			}
			elseif ($action === 'deny')
			{
				$ok = $this->Correction_model->deny_request($request_id, $this->current_user->id, $admin_notes);
				if ($ok)
				{
					$this->session->set_flashdata('admin_success', 'Correction request denied.');
				}
				else
				{
					$this->session->set_flashdata('admin_error', 'Failed to deny correction request.');
				}
			}
			redirect('admin/correction_requests');
		}

		$this->data['active_page'] = 'correction_requests';
		$this->_render('admin/correction_request_view', array(
			'request' => $request,
			'subtitle' => 'Administration',
		));
	}
}