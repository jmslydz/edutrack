<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth
 *
 * Login / logout / password reset / forced password change.
 *
 * Security notes implemented here:
 *   - The role is always taken from the database `users.role` row, never
 *     from request input.
 *   - Failed logins are throttled via login_attempts (5 failures in a
 *     15-minute window locks the account for the remainder of the window).
 *   - Error messages are deliberately generic ("Invalid email or password")
 *     so the endpoint cannot be used to enumerate registered emails.
 *   - Forgot-password never reveals whether an email exists.
 *   - Reset tokens are stored hashed and are single-use with a TTL.
 */
class Auth extends MY_Controller
{
	const MAX_ATTEMPTS = 5;
	const ATTEMPT_WINDOW = 15; // minutes

	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Login_attempt_model');
		$this->load->model('Password_reset_model');
		$this->load->config('email');
	}

	/**
	 * Default route. Logged-in users go to their dashboard, everyone
	 * else to the login page.
	 */
	public function index()
	{
		if ($this->current_user !== NULL)
		{
			redirect($this->_dashboard_uri());
		}
		redirect('auth/login');
	}

	/**
	 * GET: login form. POST: authenticate.
	 */
	public function login()
	{
		if ($this->current_user !== NULL)
		{
			redirect($this->_dashboard_uri());
		}

		$data = array(
			'title'        => 'Sign In — EduTrack',
			'login_error'  => $this->session->flashdata('login_error'),
			'email_value'  => '',
		);

		if ($this->input->method() === 'post')
		{
			$this->Login_attempt_model->prune();

			$this->load->library('form_validation');
			$this->form_validation->set_rules('email', 'Email Address', 'required|valid_email|max_length[120]');
			$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[72]');

			if ($this->form_validation->run() === TRUE)
			{
				$email    = trim($this->input->post('email'));
				$password = $this->input->post('password');
				$ip       = $this->input->ip_address();

				if ($this->_is_locked_out($email))
				{
					$attempt = $this->Login_attempt_model->last_failed_time($email);
					$remaining = $attempt
						? max(1, self::ATTEMPT_WINDOW - (int) ((time() - strtotime($attempt)) / 60))
						: self::ATTEMPT_WINDOW;
					$this->Login_attempt_model->record($email, $ip, FALSE);
					$this->session->set_flashdata('login_error',
						'Too many failed attempts. Try again in about ' . $remaining . ' minute(s).');
					redirect('auth/login');
				}

				$user = $this->User_model->get_by_email($email);

				if ($user && password_verify($password, $user->password_hash))
				{
					if ($user->status !== 'active')
					{
						$this->Login_attempt_model->record($email, $ip, FALSE);
						$this->session->set_flashdata('login_error', 'Invalid email or password.');
						redirect('auth/login');
					}

					// Success: clear failures, start a fresh session.
					$this->Login_attempt_model->record($email, $ip, TRUE);
					$this->session->sess_regenerate(TRUE);
					$this->session->set_userdata('user_id', (int) $user->id);
					$this->User_model->set_last_login($user->id);

					if ($user->must_change_password == 1)
					{
						$this->session->set_flashdata('notice', 'You must set a new password before continuing.');
						redirect('auth/change_password');
					}
					redirect($this->_dashboard_uri($user->role));
				}

				$this->Login_attempt_model->record($email, $ip, FALSE);
				$this->session->set_flashdata('login_error', 'Invalid email or password.');
			}
			else
			{
				$data['email_value'] = $this->input->post('email');
				$this->session->set_flashdata('login_error', validation_errors('', ' '));
			}
			redirect('auth/login');
		}

		$this->load->view('auth/login', $data);
	}

	/**
	 * GET: public registration form for admission applicants.
	 * POST: create an applicant account (role=applicant, status=pending_exam).
	 * The applicant is NOT a student yet — they must come to campus and take
	 * the admission exam before an admin can admit them.
	 */
	public function register()
	{
		if ($this->current_user !== NULL)
		{
			redirect($this->_dashboard_uri());
		}

		$data = array(
			'title'       => 'Create Account — EduTrack',
			'reg_error'   => $this->session->flashdata('reg_error'),
			'first_name'  => '',
			'last_name'   => '',
			'email'       => '',
		);

		if ($this->input->method() === 'post')
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('first_name', 'First Name', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('email', 'Email Address', 'required|valid_email|trim|max_length[120]');
			$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[72]');
			$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');

			$data['first_name'] = trim($this->input->post('first_name'));
			$data['last_name']  = trim($this->input->post('last_name'));
			$data['email']      = trim($this->input->post('email'));

			if ($this->form_validation->run() === TRUE)
			{
				$email = strtolower($data['email']);

				if ($this->User_model->email_exists($email))
				{
					$this->session->set_flashdata('reg_error', 'An account with that email already exists. Please sign in instead.');
					redirect('auth/register');
				}

				// Unique username derived from the email local-part.
				$base = preg_replace('/[^a-z0-9._-]/', '', strtolower($data['first_name'] . '.' . $data['last_name']));
				if ($base === '')
				{
					$base = substr($email, 0, strpos($email, '@'));
				}
				$username = $base;
				$i = 2;
				while ($this->User_model->username_exists($username))
				{
					$username = $base . '.' . $i++;
				}

				$user_id = $this->User_model->create(array(
					'username'             => $username,
					'email'                => $email,
					'password'             => $this->input->post('password'),
					'role'                 => 'applicant',
					'first_name'           => $data['first_name'],
					'last_name'            => $data['last_name'],
					'status'               => 'active',
					'must_change_password' => 0,
				));

				// Applicant record starts as pending_exam — no exam code yet.
				$this->db->insert('applicants', array(
					'user_id' => $user_id,
					'status'  => 'pending_exam',
				));

				// Log them in straight away and drop them on the applicant portal.
				$this->session->sess_regenerate(TRUE);
				$this->session->set_userdata('user_id', $user_id);
				$this->session->set_flashdata('notice', 'Welcome! Your admission application has been created.');
				redirect('applicant/dashboard');
			}
			else
			{
				$this->session->set_flashdata('reg_error', validation_errors('', ' '));
				redirect('auth/register');
			}
		}

		$this->load->view('auth/register', $data);
	}

	/**
	 * Destroy the session and return to the login page.
	 */
	public function logout()
	{
		$this->session->sess_destroy();
		redirect('auth/login');
	}

	/**
	 * GET: forgot-password form (request state).
	 * POST: validate the email, create a reset token, "send" the email and
	 *       always redirect to the confirm state — whether or not the email
	 *       actually exists in the database.
	 */
	public function forgot_password()
	{
		if ($this->input->method() === 'post')
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('email', 'Email Address', 'required|valid_email|max_length[120]');

			$email = trim($this->input->post('email'));

			if ($this->form_validation->run() === TRUE)
			{
				$this->Password_reset_model->prune();

				// Only create a token for known accounts, but ALWAYS show
				// the confirm state so we never reveal account existence.
				$user = $this->User_model->get_by_email($email);
				if ($user && $user->status === 'active')
				{
					$raw = bin2hex(random_bytes(32));
					$this->Password_reset_model->create($user->email, $raw);
					$this->_send_reset_email($user, $raw);
				}

				$this->session->set_flashdata('reset_requested', TRUE);
				$this->session->set_flashdata('reset_email', $email);
			}
			redirect('auth/forgot_password');
		}

		$data = array(
			'title'          => 'Forgot Password — EduTrack',
			'reset_requested' => $this->session->flashdata('reset_requested'),
			'reset_email'    => $this->session->flashdata('reset_email'),
		);
		$this->load->view('auth/forgot_password', $data);
	}

	/**
	 * GET: reset form (valid token required).
	 * POST: set the new password if the token is still valid.
	 */
	public function reset_password($token = NULL)
	{
		$token = trim($token);

		if ($this->input->method() === 'post')
		{
			$token = trim($this->input->post('token'));
			$this->load->library('form_validation');
			$this->form_validation->set_rules('token', 'Reset Token', 'required');
			$this->form_validation->set_rules('password', 'New Password', 'required|min_length[8]|max_length[72]');
			$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');

			if ($this->form_validation->run() === FALSE)
			{
				$data = array(
					'title'     => 'Reset Password — EduTrack',
					'token'     => $token,
					'error'     => validation_errors('', ' '),
				);
				$this->load->view('auth/reset_password', $data);
				return;
			}

			$record = $this->Password_reset_model->get_valid($token);
			if ( ! $record)
			{
				$data = array(
					'title' => 'Reset Password — EduTrack',
					'token' => '',
					'error' => 'This reset link is invalid or has expired. Please request a new one.',
				);
				$this->load->view('auth/reset_password', $data);
				return;
			}

			$user = $this->User_model->get_by_email($record->email);
			if ( ! $user || $user->status !== 'active')
			{
				$data = array(
					'title' => 'Reset Password — EduTrack',
					'token' => '',
					'error' => 'This reset link is invalid or has expired. Please request a new one.',
				);
				$this->load->view('auth/reset_password', $data);
				return;
			}

			$this->User_model->update($user->id, array(
				'password_hash'        => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
				'must_change_password' => 0,
			));
			$this->Password_reset_model->mark_used($record->id);
			$this->Password_reset_model->invalidate_all_for_email($record->email);

			$this->session->set_flashdata('login_error', 'Your password has been reset. Please sign in with your new password.');
			redirect('auth/login');
		}

		$record = $this->Password_reset_model->get_valid($token);
		if ( ! $record)
		{
			$this->load->view('auth/reset_password', array(
				'title' => 'Reset Password — EduTrack',
				'token' => '',
				'error' => 'This reset link is invalid or has expired. Please request a new one.',
			));
			return;
		}

		$this->load->view('auth/reset_password', array(
			'title' => 'Reset Password — EduTrack',
			'token' => $token,
			'error' => '',
		));
	}

	/**
	 * Forced password change (first login / after admin reset).
	 * Any authenticated user may change their own password here.
	 */
	public function change_password()
	{
		$this->_require_login();

		$data = array(
			'title' => 'Change Password — EduTrack',
			'notice' => $this->session->flashdata('notice'),
			'error' => '',
		);

		if ($this->input->method() === 'post')
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('current_password', 'Current Password', 'required');
			$this->form_validation->set_rules('password', 'New Password', 'required|min_length[8]|max_length[72]');
			$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');

			if ($this->form_validation->run() === TRUE)
			{
				if (password_verify($this->input->post('current_password'), $this->current_user->password_hash))
				{
					$this->User_model->update($this->current_user->id, array(
						'password_hash'        => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
						'must_change_password' => 0,
					));
					$this->session->set_flashdata('login_error', 'Your password has been updated.');
					redirect($this->_dashboard_uri());
				}
				$data['error'] = 'Your current password is incorrect.';
			}
			else
			{
				$data['error'] = validation_errors('', ' ');
			}
		}

		$this->load->view('auth/change_password', $data);
	}

	// -----------------------------------------------------------------

	/**
	 * Whether the email is currently locked out.
	 * @param string $email
	 * @return bool
	 */
	private function _is_locked_out($email)
	{
		return $this->Login_attempt_model->count_failed_for_email($email, self::ATTEMPT_WINDOW) >= self::MAX_ATTEMPTS;
	}

	/**
	 * Best-effort reset email. Failures are logged, never shown.
	 * @param object $user
	 * @param string $raw_token
	 */
	private function _send_reset_email($user, $raw_token)
	{
		$link = site_url('auth/reset_password/' . $raw_token);

		$this->email->from($this->config->item('smtp_user'), 'EduTrack');
		$this->email->to($user->email);
		$this->email->subject('EduTrack — Reset your password');
		$this->email->message(
			'<p>Hello ' . html_escape($user->first_name) . ',</p>'
			. '<p>A password reset was requested for your EduTrack account.</p>'
			. '<p><a href="' . $link . '">Reset your password</a></p>'
			. '<p>This link expires in 60 minutes. If you did not request it, ignore this email.</p>'
		);

		if ( ! $this->email->send())
		{
			// Do not surface mail errors to the user. Log for admins.
			log_message('error', 'Password reset email failed for user ' . $user->id . ': ' . $this->email->print_debugger());
		}
	}

	/**
	 * Dashboard URI for a role (defaults to the logged-in user's role).
	 * @param string|null $role
	 * @return string
	 */
	private function _dashboard_uri($role = NULL)
	{
		$role = $role ?: ($this->current_user ? $this->current_user->role : 'student');
		switch ($role)
		{
			case 'admin':     return 'admin/dashboard';
			case 'teacher':   return 'teacher/dashboard';
			case 'applicant': return 'applicant/dashboard';
			default:          return 'student/dashboard';
		}
	}
}