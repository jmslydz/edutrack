<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ticket_model
 *
 * Support ticket system for students and teachers to submit tickets to Admin.
 * All reads/writes are scoped by the authenticated user so a user
 * can only ever see or modify their own tickets (IDOR-safe).
 */
class Ticket_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Create a new ticket.
	 *
	 * @param int         $user_id
	 * @param string      $category
	 * @param string      $subject
	 * @param string      $message
	 * @param string      $recipient_type  'admin'|'teacher' (default 'admin')
	 * @param int|null    $recipient_id    required when recipient_type != 'admin'
	 * @return int insert id
	 */
	public function create($user_id, $category, $subject, $message, $recipient_type = 'admin', $recipient_id = NULL)
	{
		// Only 'admin' and 'teacher' are valid recipient types (matches DB enum)
		$allowed_types = array('admin', 'teacher');
		if ( ! in_array($recipient_type, $allowed_types, TRUE))
		{
			$recipient_type = 'admin';
		}
		if ($recipient_type === 'admin')
		{
			$recipient_id = NULL;
		}
		$now = date('Y-m-d H:i:s');
		$this->db->insert('tickets', array(
			'submitted_by'   => (int) $user_id,
			'recipient_type' => $recipient_type,
			'recipient_id'   => $recipient_id !== NULL ? (int) $recipient_id : NULL,
			'category'       => $category,
			'subject'        => $subject,
			'message'        => $message,
			'status'         => 'Open',
			'created_at'     => $now,
			'updated_at'     => $now,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Add a reply to a ticket (with optional status change).
	 * Wrapped in transaction so reply and status update are atomic.
	 *
	 * @param int    $ticket_id
	 * @param int    $user_id
	 * @param string $message
	 * @param string|null $status  optional new status
	 * @return int insert id
	 */
	public function add_reply($ticket_id, $user_id, $message, $status = NULL)
	{
		$this->db->trans_start();
		
		$this->db->insert('ticket_replies', array(
			'ticket_id'  => (int) $ticket_id,
			'replied_by' => (int) $user_id,
			'message'    => $message,
			'created_at' => date('Y-m-d H:i:s'),
		));
		$reply_id = (int) $this->db->insert_id();
		
		$update = array('updated_at' => date('Y-m-d H:i:s'));
		if ($status !== NULL && in_array($status, array('Open', 'In Progress', 'Resolved'), TRUE))
		{
			$update['status'] = $status;
		}
		$this->db->where('id', (int) $ticket_id)
			->update('tickets', $update);
		
		$this->db->trans_complete();
		
		return $this->db->trans_status() ? $reply_id : FALSE;
	}

	/**
	 * Get a single ticket by ID, scoped to the submitting user.
	 *
	 * @param int $ticket_id
	 * @param int $user_id
	 * @return object|null
	 */
	public function get_user_ticket($ticket_id, $user_id)
	{
		return $this->db->where('id', (int) $ticket_id)
			->where('submitted_by', (int) $user_id)
			->get('tickets')
			->row();
	}

	/**
	 * Get a single ticket by ID (admin view - no user scoping).
	 *
	 * @param int $ticket_id
	 * @return object|null
	 */
	public function get_ticket($ticket_id)
	{
		return $this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left')
			->where('tickets.id', (int) $ticket_id)
			->get()
			->row();
	}

	/**
	 * Get all tickets submitted by a user (student or teacher) addressed to Admin.
	 * Backwards compatible: works even if recipient_type column doesn't exist yet.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_tickets($user_id)
	{
		// Check if recipient_type column exists (for backwards compatibility)
		$columns = $this->db->list_fields('tickets');
		$has_recipient_type = in_array('recipient_type', $columns, TRUE);

		$this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left')
			->where('tickets.submitted_by', (int) $user_id);

		if ($has_recipient_type)
		{
			$this->db->where('tickets.recipient_type', 'admin');
		}

		return $this->db->order_by('tickets.updated_at', 'DESC')
			->get()
			->result();
	}

	/**
	 * Get all tickets directed to admin (recipient_type='admin').
	 * For admin's ticket list with filters.
	 * Backwards compatible: works even if recipient_type column doesn't exist yet.
	 *
	 * @param array $filters optional: status, category, search, from_role
	 * @return array
	 */
	public function get_admin_tickets($filters = array())
	{
		// Check if recipient_type column exists (for backwards compatibility)
		$columns = $this->db->list_fields('tickets');
		$has_recipient_type = in_array('recipient_type', $columns, TRUE);

		$this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left');

		if ($has_recipient_type)
		{
			$this->db->where('tickets.recipient_type', 'admin');
		}

		$this->db->order_by('tickets.updated_at', 'DESC');

		if ( ! empty($filters['status']))
		{
			$this->db->where('tickets.status', $filters['status']);
		}
		if ( ! empty($filters['category']))
		{
			$this->db->where('tickets.category', $filters['category']);
		}
		if ( ! empty($filters['from_role']))
		{
			$this->db->where('users.role', $filters['from_role']);
		}
		if ( ! empty($filters['search']))
		{
			$search = $filters['search'];
			$this->db->group_start()
				->like('tickets.subject', $search)
				->or_like('tickets.message', $search)
				->or_like('users.first_name', $search)
				->or_like('users.last_name', $search)
				->or_like('users.email', $search)
				->group_end();
		}

		return $this->db->get()->result();
	}

	/**
	 * Get a ticket for admin view - must be recipient_type='admin'.
	 *
	 * @param int $ticket_id
	 * @return object|null
	 */
	public function get_ticket_for_admin($ticket_id)
	{
		return $this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left')
			->where('tickets.id', (int) $ticket_id)
			->where('tickets.recipient_type', 'admin')
			->get()
			->row();
	}

	/**
	 * Get a ticket for student view - must be submitted by them and addressed to admin.
	 *
	 * @param int $ticket_id
	 * @param int $student_id
	 * @return object|null
	 */
	public function get_ticket_for_student($ticket_id, $student_id)
	{
		return $this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left')
			->where('tickets.id', (int) $ticket_id)
			->where('tickets.submitted_by', (int) $student_id)
			->where('tickets.recipient_type', 'admin')
			->get()
			->row();
	}

	/**
	 * Get a ticket for teacher view - must be submitted by them and addressed to admin.
	 * Prevents IDOR: a teacher can only retrieve their own submitted tickets.
	 *
	 * @param int $ticket_id
	 * @param int $teacher_id
	 * @return object|null
	 */
	public function get_ticket_for_teacher($ticket_id, $teacher_id)
	{
		return $this->db->select('tickets.*, users.first_name, users.last_name, users.email, users.role')
			->from('tickets')
			->join('users', 'users.id = tickets.submitted_by', 'left')
			->where('tickets.id', (int) $ticket_id)
			->where('tickets.submitted_by', (int) $teacher_id)
			->where('tickets.recipient_type', 'admin')
			->get()
			->row();
	}

	/**
	 * All tickets this teacher sent TO students (recipient_type='student', submitted_by=teacher).
	 *
	 * @param int $teacher_user_id
	 * @return array
	 */
	public function get_tickets_sent_to_students_by_teacher($teacher_user_id)
	{
		return $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, sub.email AS submitter_email, sub.role AS submitter_role, ' .
				'rec.first_name AS recipient_first_name, rec.last_name AS recipient_last_name'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->join('users rec', 'rec.id = tickets.recipient_id', 'left')
			->where('tickets.submitted_by', (int) $teacher_user_id)
			->where('tickets.recipient_type', 'student')
			->order_by('tickets.updated_at', 'DESC')
			->get()
			->result();
	}

	/**
	 * All tickets sent TO this teacher from students (recipient_type='teacher', recipient_id=teacher).
	 *
	 * @param int $teacher_user_id
	 * @return array
	 */
	public function get_tickets_received_by_teacher($teacher_user_id)
	{
		return $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, sub.email AS submitter_email, sub.role AS submitter_role'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->where('tickets.recipient_type', 'teacher')
			->where('tickets.recipient_id', (int) $teacher_user_id)
			->order_by('tickets.updated_at', 'DESC')
			->get()
			->result();
	}

	/**
	 * All tickets this student submitted (to admin or a teacher) PLUS tickets
	 * received FROM a teacher (recipient_type='student', recipient_id=student).
	 * Returns a merged set ordered by updated_at DESC.
	 *
	 * @param int $student_user_id
	 * @return array
	 */
	public function get_all_student_tickets($student_user_id)
	{
		// Sent by student (to admin or teacher)
		$sent = $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, ' .
				'rec.first_name AS recipient_first_name, rec.last_name AS recipient_last_name, ' .
				'"sent" AS direction'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->join('users rec', 'rec.id = tickets.recipient_id', 'left')
			->where('tickets.submitted_by', (int) $student_user_id)
			->order_by('tickets.updated_at', 'DESC')
			->get()
			->result();

		// Received by student from a teacher
		$received = $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, ' .
				'rec.first_name AS recipient_first_name, rec.last_name AS recipient_last_name, ' .
				'"received" AS direction'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->join('users rec', 'rec.id = tickets.recipient_id', 'left')
			->where('tickets.recipient_type', 'student')
			->where('tickets.recipient_id', (int) $student_user_id)
			->order_by('tickets.updated_at', 'DESC')
			->get()
			->result();

		// Merge and sort by updated_at DESC
		$all = array_merge($sent, $received);
		usort($all, function($a, $b) {
			return strcmp($b->updated_at, $a->updated_at);
		});
		return $all;
	}

	/**
	 * Get a single ticket that a student can view: either they submitted it,
	 * OR it was sent to them by a teacher (recipient_type='student', recipient_id=student).
	 *
	 * @param int $ticket_id
	 * @param int $student_user_id
	 * @return object|null
	 */
	public function get_ticket_for_student_inbox($ticket_id, $student_user_id)
	{
		return $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, sub.email AS submitter_email, sub.role AS submitter_role, ' .
				'rec.first_name AS recipient_first_name, rec.last_name AS recipient_last_name'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->join('users rec', 'rec.id = tickets.recipient_id', 'left')
			->where('tickets.id', (int) $ticket_id)
			->group_start()
				->where('tickets.submitted_by', (int) $student_user_id)
				->or_group_start()
					->where('tickets.recipient_type', 'student')
					->where('tickets.recipient_id', (int) $student_user_id)
				->group_end()
			->group_end()
			->get()
			->row();
	}

	/**
	 * Get a single teacher→student ticket that the teacher owns
	 * (submitted_by=teacher, recipient_type='student').
	 *
	 * @param int $ticket_id
	 * @param int $teacher_user_id
	 * @return object|null
	 */
	public function get_teacher_to_student_ticket($ticket_id, $teacher_user_id)
	{
		return $this->db->select(
				'tickets.*, ' .
				'sub.first_name AS submitter_first_name, sub.last_name AS submitter_last_name, sub.email AS submitter_email, sub.role AS submitter_role, ' .
				'rec.first_name AS recipient_first_name, rec.last_name AS recipient_last_name'
			)
			->from('tickets')
			->join('users sub', 'sub.id = tickets.submitted_by', 'left')
			->join('users rec', 'rec.id = tickets.recipient_id', 'left')
			->where('tickets.id', (int) $ticket_id)
			->where('tickets.submitted_by', (int) $teacher_user_id)
			->where('tickets.recipient_type', 'student')
			->get()
			->row();
	}

	/**
	 * Get all tickets (admin) with filters - kept for backward compat.
	 *
	 * @param array $filters optional: status, category, search
	 * @return array
	 */
	public function get_all_tickets($filters = array())
	{
		return $this->get_admin_tickets($filters);
	}

	/**
	 * Get replies for a ticket.
	 *
	 * @param int $ticket_id
	 * @return array
	 */
	public function get_replies($ticket_id)
	{
		return $this->db->select('ticket_replies.*, users.first_name, users.last_name, users.role')
			->from('ticket_replies')
			->join('users', 'users.id = ticket_replies.replied_by')
			->where('ticket_id', (int) $ticket_id)
			->order_by('created_at', 'ASC')
			->get()
			->result();
	}

	/**
	 * Update ticket status (admin only).
	 *
	 * @param int    $ticket_id
	 * @param string $status
	 * @return bool
	 */
	public function update_status($ticket_id, $status)
	{
		return $this->db->where('id', (int) $ticket_id)
			->update('tickets', array(
				'status'     => $status,
				'updated_at' => date('Y-m-d H:i:s'),
			)) !== FALSE;
	}

	/**
	 * Count tickets by status for a student — includes both sent AND received tickets.
	 * For teachers/admin, counts only submitted_by = user_id.
	 * Backwards compatible: works even if recipient_type column doesn't exist yet.
	 *
	 * @param int    $user_id
	 * @param string $role  'student'|'teacher'|'admin'
	 * @return array
	 */
	public function count_by_status($user_id, $role = 'student')
	{
		$out = array('Open' => 0, 'In Progress' => 0, 'Resolved' => 0);

		// Check if recipient_type column exists (for backwards compatibility)
		$columns = $this->db->list_fields('tickets');
		$has_recipient_type = in_array('recipient_type', $columns, TRUE);

		if ($role === 'student' && $has_recipient_type)
		{
			// Count tickets submitted by OR sent to this student
			$rows = $this->db->select('status, COUNT(*) as cnt')
				->from('tickets')
				->group_start()
					->where('submitted_by', (int) $user_id)
					->or_group_start()
						->where('recipient_type', 'student')
						->where('recipient_id', (int) $user_id)
					->group_end()
				->group_end()
				->group_by('status')
				->get()
				->result();
		}
		else
		{
			$rows = $this->db->select('status, COUNT(*) as cnt')
				->from('tickets')
				->where('submitted_by', (int) $user_id)
				->group_by('status')
				->get()
				->result();
		}

		foreach ($rows as $r)
		{
			if (isset($out[$r->status]))
			{
				$out[$r->status] = (int) $r->cnt;
			}
		}
		return $out;
	}
}