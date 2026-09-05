<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification_model
 *
 * In-app notifications stored in the `notifications` table. All reads are
 * scoped by the authenticated user_id so a user can only ever see or
 * modify their own notifications (IDOR-safe: the owner always comes from
 * the server-side session, never from the URL).
 */
class Notification_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Create a notification for one user.
	 *
	 * @param int         $user_id
	 * @param string      $title
	 * @param string|null $body
	 * @param string|null $link   internal route, e.g. "teacher/encode_grades"
	 * @param string      $type   'system'|'announcement'
	 * @return int insert id
	 */
	public function create($user_id, $title, $body = NULL, $link = NULL, $type = 'system')
	{
		$this->db->insert('notifications', array(
			'user_id' => (int) $user_id,
			'title'   => (string) $title,
			'body'    => $body !== NULL ? (string) $body : NULL,
			'link'    => $link !== NULL ? (string) $link : NULL,
			'type'    => $type,
			'is_read' => 0,
			'created_at' => date('Y-m-d H:i:s'),
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Create one notification for each of several users.
	 *
	 * @param array       $user_ids
	 * @param string      $title
	 * @param string|null $body
	 * @param string|null $link
	 * @param string      $type   'system'|'announcement'
	 */
	public function create_many(array $user_ids, $title, $body = NULL, $link = NULL, $type = 'system')
	{
		foreach (array_unique(array_map('intval', $user_ids)) as $uid)
		{
			if ($uid > 0)
			{
				$this->create($uid, $title, $body, $link, $type);
			}
		}
	}

	/**
	 * Unread notification count for a user (badge on the bell).
	 * @param int $user_id
	 * @return int
	 */
	public function unread_count($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)
			->where('is_read', 0)
			->count_all_results('notifications');
	}

	/**
	 * Most recent notifications for a user (dropdown list).
	 * @param int $user_id
	 * @param int $limit
	 * @return array
	 */
	public function recent($user_id, $limit = 8)
	{
		return $this->db->where('user_id', (int) $user_id)
			->order_by('created_at', 'DESC')
			->order_by('id', 'DESC')
			->limit((int) $limit)
			->get('notifications')
			->result();
	}

	/**
	 * Mark one notification as read — only if it belongs to $user_id.
	 * @param int $id
	 * @param int $user_id
	 * @return bool
	 */
	public function mark_read($id, $user_id)
	{
		return $this->db->where('id', (int) $id)
			->where('user_id', (int) $user_id)
			->update('notifications', array('is_read' => 1)) !== FALSE;
	}

	/**
	 * Mark every notification for a user as read.
	 * @param int $user_id
	 * @return bool
	 */
	public function mark_all_read($user_id)
	{
		return $this->db->where('user_id', (int) $user_id)
			->update('notifications', array('is_read' => 1)) !== FALSE;
	}

	/**
	 * Get recent announcements (admin view) with recipient counts.
	 * @param int $limit
	 * @return array
	 */
	public function recent_announcements($limit = 10)
	{
		return $this->db->select(
				'title, body, link, type, created_at, ' .
				'COUNT(*) as recipient_count, ' .
				'CASE WHEN type = \'announcement\' THEN body ELSE \'all\' END as audience_label'
			)
			->from('notifications')
			->where('type', 'announcement')
			->group_by('title, body, link, type, created_at')
			->order_by('created_at', 'DESC')
			->limit((int) $limit)
			->get()
			->result();
	}
}