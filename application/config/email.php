<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email settings (password reset / notifications)
|--------------------------------------------------------------------------
| Gmail SMTP via TLS/SSL, per the agreed approach. The username/password
| below are PLACEHOLDERS — set them to a real Gmail account (ideally an
| app password) on deployment. Mail failures are logged and never surfaced
| to the user (the "check your inbox" message is shown regardless, by
| design). No code change is needed once these are filled in.
*/
$config['protocol'] = 'smtp';
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_crypto'] = 'tls';
$config['smtp_user'] = 'closasjames2@gmail.com';
$config['smtp_pass'] = 'CHANGE_ME_APP_PASSWORD'; // real password lives in email.local.php (gitignored)
$config['mailtype'] = 'html';
$config['charset']  = 'utf-8';
$config['newline']  = "\r\n";
$config['crlf']     = "\r\n";
$config['wordwrap'] = TRUE;

/*
|--------------------------------------------------------------------------
| Local secret overrides (NOT committed)
|--------------------------------------------------------------------------
| Drop a file named email.local.php next to this one with the real SMTP
| app password, e.g.:  $config['smtp_pass'] = 'abcd efgh ijkl mnop';
| It is gitignored, so real credentials never enter the repository.
*/
if (file_exists(__DIR__ . '/email.local.php'))
{
	include __DIR__ . '/email.local.php';
}