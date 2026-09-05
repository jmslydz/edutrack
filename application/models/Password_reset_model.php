<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password_reset_model
 *
 * Password reset tokens. Only the SHA-256 hash of the raw token is ever
 * stored — the raw token is sent to the user in the reset email and is
 * never persisted. Tokens expire and can be used only once.
 */
class Password_reset_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Create a reset token record.
	 * @param string $email
	 * @param string $raw_token  returned to the caller for the email body
	 * @param int    $ttl_minutes
	 * @return string the raw token
	 */
	public function create($email, $raw_token, $ttl_minutes = 60)
	{
		$this->db->insert('password_resets', array(
			'email'        => mb_substr($email, 0, 120),
			'token_hash'   => hash('sha256', $raw_token),
			'expires_at'   => date('Y-m-d H:i:s', time() + $ttl_minutes * 60),
			'used'         => 0,
			'created_at'   => date('Y-m-d H:i:s'),
		));
		return $raw_token;
	}

	/**
	 * Fetch a valid, unused, unexpired token record (by raw token).
	 * @param string $raw_token
	 * @return object|null
	 */
	public function get_valid($raw_token)
	{
		if (empty($raw_token))
		{
			return NULL;
		}
		return $this->db->where('token_hash', hash('sha256', $raw_token))
			->where('used', 0)
			->where('expires_at >', date('Y-m-d H:i:s'))
			->order_by('id', 'DESC')
			->limit(1)
			->get('password_resets')
			->row();
	}

	/**
	 * Mark a token record as consumed.
	 * @param int $id
	 */
	public function mark_used($id)
	{
		$this->db->where('id', (int) $id)->update('password_resets', array('used' => 1));
	}

	/**
	 * Invalidate every outstanding (unused, unexpired) token for an email so
	 * a successful reset revokes any other reset links that were still valid.
	 * @param string $email
	 */
	public function invalidate_all_for_email($email)
	{
		$this->db->where('email', mb_substr($email, 0, 120))
			->where('used', 0)
			->update('password_resets', array('used' => 1));
	}

	/**
	 * Opportunistic cleanup of expired/used tokens.
	 * @param int $days
	 */
	public function prune($days = 7)
	{
		$this->db->group_start()
			->where('expires_at <', date('Y-m-d H:i:s'))
			->or_where('used', 1)
			->group_end()
			->delete('password_resets');
	}
}