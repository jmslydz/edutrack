<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Exceptions
 *
 * Production-safe error handling. In development the standard CodeIgniter
 * error page (with message, file, line, backtrace) is shown so bugs are
 * easy to diagnose. In any other environment the full detail is logged
 * for the operator but visitors only ever see a generic 500 page — no
 * internal file paths, SQL fragments, or messages are exposed.
 */
class MY_Exceptions extends CI_Exceptions
{
	public function show_php_error($severity, $message, $filepath, $line)
	{
		log_message('error', 'Severity: ' . $severity . ' --> ' . $message . ' ' . $filepath . ' ' . $line);

		if (ENVIRONMENT !== 'development')
		{
			return $this->show_error(
				'Unexpected Error',
				'Something went wrong while processing your request. Please try again later.',
				'error_general',
				500
			);
		}

		return parent::show_php_error($severity, $message, $filepath, $line);
	}
}
