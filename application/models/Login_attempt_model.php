<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Login_attempt_model
 *
 * Brute-force throttling support. Every authentication attempt is
 * recorded; a lockout is applied once too many failures accumulate
 * for a given email/IP within a rolling window.
 */
class Login_attempt_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Record a login attempt (successful or not).
	 * @param string $email
	 * @param string $ip_address
	 * @param bool   $success
	 */
	public function record($email, $ip_address, $success = FALSE)
	{
		$this->db->insert('login_attempts', array(
			'email'        => mb_substr($email, 0, 120),
			'ip_address'   => mb_substr($ip_address, 0, 45),
			'success'      => $success ? 1 : 0,
			'attempted_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Number of failed attempts for an email within the last $minutes.
	 * @param string $email
	 * @param int    $minutes
	 * @return int
	 */
	public function count_failed_for_email($email, $minutes = 15)
	{
		return (int) $this->db
			->where('email', mb_substr($email, 0, 120))
			->where('success', 0)
			->where('attempted_at >=', date('Y-m-d H:i:s', time() - $minutes * 60))
			->count_all_results('login_attempts');
	}

	/**
	 * Number of failed attempts from an IP within the last $minutes.
	 * @param string $ip_address
	 * @param int    $minutes
	 * @return int
	 */
	public function count_failed_for_ip($ip_address, $minutes = 15)
	{
		return (int) $this->db
			->where('ip_address', mb_substr($ip_address, 0, 45))
			->where('success', 0)
			->where('attempted_at >=', date('Y-m-d H:i:s', time() - $minutes * 60))
			->count_all_results('login_attempts');
	}

	/**
	 * Timestamp of the most recent failed attempt for an email (used to
	 * tell the user how long the lockout lasts).
	 * @param string $email
	 * @return string|null
	 */
	public function last_failed_time($email)
	{
		$row = $this->db->select('attempted_at')
			->where('email', mb_substr($email, 0, 120))
			->where('success', 0)
			->order_by('attempted_at', 'DESC')
			->limit(1)
			->get('login_attempts')
			->row();
		return $row ? $row->attempted_at : NULL;
	}

	/**
	 * Delete old records (run opportunistically).
	 * @param int $days keep history this many days
	 */
	public function prune($days = 30)
	{
		$this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - $days * 86400))
			->delete('login_attempts');
	}
}