<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifications
 *
 * Endpoints behind the topbar notification bell. Available to any
 * authenticated user; every operation is scoped to the session user so a
 * modified notification id can only ever act on the caller's own rows.
 */
class Notifications extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_require_login();
		$this->load->model('Notification_model');
	}

	/**
	 * Full notification list page (the bell's "View all" link).
	 */
	public function index()
	{
		$this->data['active_page'] = 'notifications';
		$this->_render('notifications/index', array(
			'notifications' => $this->Notification_model->recent($this->current_user->id, 50),
			'unread_count'  => $this->Notification_model->unread_count($this->current_user->id),
			'subtitle'      => 'Notifications',
		));
	}

	/**
	 * Mark one notification as read, then follow its link (or the user's
	 * dashboard). Always ownership-scoped via the session user id.
	 *
	 * @param int $id
	 */
	public function read($id)
	{
		$id  = (int) $id;
		$row = $this->_owned($id);

		if ($row)
		{
			$this->Notification_model->mark_read($id, $this->current_user->id);
		}

		$this->_redirect_after($row);
	}

	/**
	 * Fetch the message for one notification so the bell can show it in a
	 * popup without leaving the page. Scoped to the session user and marks
	 * the notification as read.
	 *
	 * AJAX requests (X-Requested-With: XMLHttpRequest) receive JSON;
	 * a plain GET falls back to the same read-then-follow behavior as
	 * read(), so the bell still works without JavaScript.
	 *
	 * @param int $id
	 */
	public function view($id)
	{
		$id  = (int) $id;
		$row = $this->_owned($id);

		if ($this->input->is_ajax_request())
		{
			if ( ! $row)
			{
				$this->output->set_status_header(404)
					->set_content_type('application/json')
					->set_output(json_encode(array(
						'ok'      => FALSE,
						'message' => 'Notification not found.',
					)));
				return;
			}

			$this->Notification_model->mark_read($id, $this->current_user->id);
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array(
					'ok'    => TRUE,
					'id'    => (int) $row->id,
					'title' => $row->title,
					'body'  => $row->body !== NULL ? $row->body : '',
					'time'  => $this->_human_time($row->created_at),
					'type'  => isset($row->type) ? $row->type : 'system',
					'link'  => ! empty($row->link) ? site_url($row->link) : NULL,
				)));
			return;
		}

		if ($row)
		{
			$this->Notification_model->mark_read($id, $this->current_user->id);
		}

		$this->_redirect_after($row);
	}

	/**
	 * Mark all notifications as read (POST from the bell dropdown).
	 */
	public function read_all()
	{
		$this->_require_post();
		$this->Notification_model->mark_all_read($this->current_user->id);
		redirect($_SERVER['HTTP_REFERER'] ?? $this->session->userdata('last_page') ?? 'dashboard');
	}

	/**
	 * Load a single notification row, ownership-scoped to the session user.
	 * @param int $id
	 * @return object|null
	 */
	private function _owned($id)
	{
		return $this->db->where('id', (int) $id)
			->where('user_id', (int) $this->current_user->id)
			->get('notifications')
			->row();
	}

	/**
	 * Follow a notification's context link, or land on the user's dashboard
	 * when it has no link (the same fallback as read()/view()).
	 * @param object|null $row
	 */
	private function _redirect_after($row)
	{
		if ($row && ! empty($row->link))
		{
			redirect($row->link);
		}

		switch ($this->current_user->role)
		{
			case 'admin':   redirect('admin/dashboard'); break;
			case 'teacher': redirect('teacher/dashboard'); break;
			default:        redirect('student/dashboard');
		}
	}

	/**
	 * Compact relative timestamp matching the bell dropdown
	 * ("just now", "5m ago", ...).
	 * @param string|null $dt
	 * @return string
	 */
	private function _human_time($dt)
	{
		if (empty($dt))
		{
			return '';
		}
		$diff = time() - strtotime($dt);
		if ($diff < 60) return 'just now';
		if ($diff < 3600) return floor($diff / 60) . 'm ago';
		if ($diff < 86400) return floor($diff / 3600) . 'h ago';
		if ($diff < 86400 * 7) return floor($diff / 86400) . 'd ago';
		return date('M j', strtotime($dt));
	}
}