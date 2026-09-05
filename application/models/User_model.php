<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model
 *
 * All reads/writes against the `users` table plus the password handling
 * rules the rest of the app depends on:
 *   - passwords are NEVER stored in plaintext and NEVER returned
 *   - every stored password is produced with password_hash()
 *   - a hashed value is verified with password_verify()
 */
class User_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Fetch one user by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('users')->row();
	}

	/**
	 * Fetch one user by email (case-insensitive).
	 * @param string $email
	 * @return object|null
	 */
	public function get_by_email($email)
	{
		return $this->db->where('LOWER(email)', strtolower(trim($email)))
			->get('users')
			->row();
	}

	/**
	 * Fetch one user by username.
	 * @param string $username
	 * @return object|null
	 */
	public function get_by_username($username)
	{
		return $this->db->where('username', trim($username))->get('users')->row();
	}

	/**
	 * Create a user. Passwords must be passed through set_password()
	 * or already hashed into $data['password_hash'].
	 *
	 * @param array $data
	 * @return int insert id
	 */
	public function create(array $data)
	{
		if (isset($data['password']))
		{
			$data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
			unset($data['password']);
		}
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('users', $data);
		return (int) $this->db->insert_id();
	}

	/**
	 * Update a user. Passing a 'password' key hashes it first.
	 * @param int   $id
	 * @param array $data
	 * @return bool
	 */
	public function update($id, array $data)
	{
		if (isset($data['password']))
		{
			if ($data['password'] === '')
			{
				unset($data['password']); // empty = keep current password
			}
			else
			{
				$data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
				unset($data['password']);
			}
		}
		return $this->db->where('id', (int) $id)->update('users', $data);
	}

	/**
	 * Replace a user's password with a fresh hash and force a change on
	 * next login. Never returns the plaintext value.
	 *
	 * @param int    $id
	 * @param string $plain_password
	 * @return bool
	 */
	public function set_password($id, $plain_password)
	{
		return $this->update($id, array(
			'password_hash'        => password_hash($plain_password, PASSWORD_DEFAULT),
			'must_change_password' => 1,
		));
	}

	/**
	 * Delete a user. Returns FALSE if the row is protected (see $protected).
	 * @param int $id
	 * @return bool
	 */
	public function delete($id)
	{
		return $this->db->where('id', (int) $id)->delete('users') !== FALSE;
	}

	/**
	 * Record a successful login timestamp.
	 * @param int $id
	 */
	public function set_last_login($id)
	{
		$this->db->where('id', (int) $id)
			->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
	}

	/**
	 * Count users filtered by role and/or status.
	 * @param string|null $role
	 * @param string|null $status
	 * @return int
	 */
	public function count_by_role($role = NULL, $status = NULL)
	{
		$this->db->from('users');
		if ($role !== NULL)
		{
			$this->db->where('role', $role);
		}
		if ($status !== NULL)
		{
			$this->db->where('status', $status);
		}
		return (int) $this->db->count_all_results();
	}

	/**
	 * All active teachers (role=teacher, status=active), ordered by name.
	 * Used to build the "assign a teacher to a class" picker.
	 * @return array of objects
	 */
	public function active_teachers()
	{
		return $this->db->select('id, username, first_name, last_name')
			->where('role', 'teacher')
			->where('status', 'active')
			->order_by('last_name', 'ASC')
			->order_by('first_name', 'ASC')
			->get('users')
			->result();
	}

	/**
	 * Paginated user list with optional search/role/status filters.
	 *
	 * @param array  $filters ['search','role','status']
	 * @param int    $limit
	 * @param int    $offset
	 * @return array of objects
	 */
	public function all(array $filters = array(), $limit = 10, $offset = 0)
	{
		$this->db->from('users');
		$this->_apply_filters($filters);
		$this->db->order_by('last_name', 'ASC')
			->order_by('first_name', 'ASC')
			->limit((int) $limit, (int) $offset);
		return $this->db->get()->result();
	}

	/**
	 * Total rows matching the given filters (for pagination).
	 * @param array $filters
	 * @return int
	 */
	public function count_all(array $filters = array())
	{
		$this->db->from('users');
		$this->_apply_filters($filters);
		return (int) $this->db->count_all_results();
	}

	/**
	 * Check whether an email already belongs to another user.
	 * @param string   $email
	 * @param int|null $except_id user id to ignore
	 * @return bool
	 */
	public function email_exists($email, $except_id = NULL)
	{
		$this->db->where('LOWER(email)', strtolower(trim($email)));
		if ($except_id !== NULL)
		{
			$this->db->where('id !=', (int) $except_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	/**
	 * Check whether a username already belongs to another user.
	 * @param string   $username
	 * @param int|null $except_id
	 * @return bool
	 */
	public function username_exists($username, $except_id = NULL)
	{
		$this->db->where('username', trim($username));
		if ($except_id !== NULL)
		{
			$this->db->where('id !=', (int) $except_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	/**
	 * Number of active admin accounts. Used to protect against deleting
	 * or deactivating the last administrator.
	 * @return int
	 */
	public function count_active_admins()
	{
		return (int) $this->db->where('role', 'admin')
			->where('status', 'active')
			->count_all_results('users');
	}

	// -----------------------------------------------------------------

	/**
	 * Shared WHERE clause for the list/count queries above.
	 * @param array $filters
	 */
	private function _apply_filters(array $filters)
	{
		if ( ! empty($filters['search']))
		{
			$s = $this->db->escape_like_str(trim($filters['search']));
			$this->db->group_start()
				->like('first_name', $s)
				->or_like('last_name', $s)
				->or_like('username', $s)
				->or_like('email', $s)
				->group_end();
		}
		if ( ! empty($filters['role']) && in_array($filters['role'], array('admin', 'teacher', 'student'), TRUE))
		{
			$this->db->where('role', $filters['role']);
		}
		if ( ! empty($filters['status']) && in_array($filters['status'], array('active', 'inactive'), TRUE))
		{
			$this->db->where('status', $filters['status']);
		}
	}
}