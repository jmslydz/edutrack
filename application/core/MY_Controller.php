<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller.
 *
 * Every controller in EduTrack extends MY_Controller (or one of its
 * role-specific subclasses). It centralises:
 *   - loading the authenticated user from the session
 *   - session/CSRF data shared by the layout partials
 *   - login + role guards used by every protected page
 *
 * Authentication ("who is this user?") and authorization ("is this user
 * allowed to perform this action?") are treated separately. The role
 * guard subclasses below enforce authorization server-side; the views
 * never decide access control on their own.
 */
class MY_Controller extends CI_Controller
{
	/**
	 * Authenticated user row (object) or NULL when not logged in.
	 * Always derived from the server-side session — never from request input.
	 * @var object|null
	 */
	public $current_user = NULL;

	/**
	 * View data shared by the layout partials (header/sidebar/topbar/footer).
	 * @var array
	 */
	public $data = array();

	public function __construct()
	{
		parent::__construct();

		// Data handed to the layout partials. $this->data['title'] is set
		// per controller/method.
		$this->data['title'] = 'EduTrack';
		$this->data['role'] = '';
		$this->data['active_page'] = '';
		$this->data['csrf'] = array(
			'name' => $this->security->get_csrf_token_name(),
			'hash' => $this->security->get_csrf_hash(),
		);

		// Resolve the authenticated user from the session.
		$user_id = (int) $this->session->userdata('user_id');
		if ($user_id > 0)
		{
			$this->load->model('User_model');
			$this->current_user = $this->User_model->get($user_id);

			// If the account was deactivated or no longer exists, end the
			// session rather than letting a stale login persist.
			if ($this->current_user === NULL || $this->current_user->status !== 'active')
			{
				$this->session->sess_destroy();
				$this->current_user = NULL;
			}
			else
			{
				$this->data['role'] = $this->current_user->role;
			}
		}

		// Enforce the "must change password" flag on every route so a user
		// with a forced reset cannot bypass it by navigating straight to a
		// protected page. The change-password page and logout are exempt to
		// avoid a redirect loop.
		if ($this->current_user !== NULL && (int) $this->current_user->must_change_password === 1)
		{
			$current_route = $this->router->class . '/' . $this->router->method;
			if ($current_route !== 'auth/change_password' && $current_route !== 'auth/logout')
			{
				redirect('auth/change_password');
			}
		}
	}

	/**
	 * Guard: require an authenticated (active) user, else bounce to login.
	 */
	protected function _require_login()
	{
		if ($this->current_user === NULL)
		{
			$this->session->set_flashdata('login_error', 'Please sign in to continue.');
			redirect('auth/login');
		}
	}

	/**
	 * Guard: require an authenticated user with an exact role.
	 * The role always comes from the database row in the session —
	 * never from request input.
	 *
	 * @param string $role 'admin'|'teacher'|'student'
	 */
	protected function _require_role($role)
	{
		$this->_require_login();
		if ($this->current_user->role !== $role)
		{
			show_error('You are not authorized to access this page.', 403, 'Access Denied');
		}
	}

	/**
	 * Guard: require an authenticated user whose role is one of the given
	 * roles. Same server-side semantics as _require_role().
	 *
	 * @param array $roles e.g. array('admin', 'teacher')
	 */
	protected function _require_roles(array $roles)
	{
		$this->_require_login();
		if ( ! in_array($this->current_user->role, $roles, TRUE))
		{
			show_error('You are not authorized to access this page.', 403, 'Access Denied');
		}
	}

	/**
	 * Guard: reject anything that is not a POST request. State-changing
	 * endpoints (create/update/delete/activate) must only run on POST so
	 * they carry the CSRF token and cannot be triggered by a plain link,
	 * <img> tag or prefetch.
	 */
	protected function _require_post()
	{
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		if ($method !== 'POST')
		{
			show_error('Invalid request method.', 405, 'Method Not Allowed');
		}
	}

	/**
	 * Render a dashboard page using the shared layout.
	 *
	 * @param string $view       view name relative to application/views
	 * @param array  $page_data  extra data for the content view
	 */
	protected function _render($view, array $page_data = array())
	{
		$this->load->view('partials/header', $this->data);
		$this->load->view('partials/sidebar', $this->data);
		$this->load->view('partials/topbar', array(
			'user_name'       => $this->current_user ? ($this->current_user->first_name . ' ' . $this->current_user->last_name) : '',
			'user_role_label' => $this->_role_label(),
			'avatar_initials' => $this->_initials(),
			'subtitle'        => isset($page_data['subtitle']) ? $page_data['subtitle'] : '',
			'notif_unread'    => $this->current_user ? $this->_unread_notifications() : 0,
			'notifications'   => $this->current_user ? $this->_recent_notifications() : array(),
		));
		$this->load->view($view, $page_data);
		$this->load->view('partials/footer');
	}

	/**
	 * Unread notification count for the authenticated user.
	 * @return int
	 */
	private function _unread_notifications()
	{
		$this->load->model('Notification_model');
		return $this->Notification_model->unread_count($this->current_user->id);
	}

	/**
	 * Most recent notifications for the authenticated user.
	 * @return array
	 */
	private function _recent_notifications()
	{
		$this->load->model('Notification_model');
		return $this->Notification_model->recent($this->current_user->id, 8);
	}

	/**
	 * Human-readable label for the current user's role.
	 * @return string
	 */
	protected function _role_label()
	{
		if ($this->current_user === NULL)
		{
			return '';
		}
		switch ($this->current_user->role)
		{
			case 'admin':     return 'Administrator';
			case 'teacher':   return 'Faculty';
			case 'applicant': return 'Applicant';
			default:          return 'Student';
		}
	}

	/**
	 * Avatar initials from the authenticated user's name.
	 * @return string
	 */
	protected function _initials()
	{
		if ($this->current_user === NULL)
		{
			return '--';
		}
		$f = mb_substr(trim($this->current_user->first_name), 0, 1);
		$l = mb_substr(trim($this->current_user->last_name), 0, 1);
		return mb_strtoupper($f . $l);
	}

}

/**
 * Base controller for pages restricted to Administrators.
 */
class Admin_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_require_role('admin');
	}
}

/**
 * Base controller for pages restricted to Teachers.
 */
class Teacher_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_require_role('teacher');
	}
}

/**
 * Base controller for pages restricted to Students.
 */
class Student_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_require_role('student');
	}
}