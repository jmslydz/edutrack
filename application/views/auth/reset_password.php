<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — EduTrack</title>
<link rel="stylesheet" href="<?php echo base_url('assets/css/design-system.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>">
</head>
<body>

<div class="login-page">
  <div class="bg-blob bg-blob--1"></div>
  <div class="bg-blob bg-blob--2"></div>

  <div class="login-brand">
    <div class="login-brand-icon" aria-hidden="true">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>
      </svg>
    </div>
    <h1>EduTrack</h1>
  </div>

  <div class="login-card">
    <h2>Set a new password</h2>
    <p>Choose a strong password to finish resetting your account.</p>

    <?php if ( ! empty($error)): ?>
      <div class="alert-error visible" id="serverError" role="alert">
        <?php echo html_escape($error); ?>
      </div>
    <?php endif; ?>

    <?php if ( ! empty($token)): ?>
      <?php echo form_open('auth/reset_password', array('class' => 'login-form', 'novalidate' => 'novalidate')); ?>
        <?php echo form_hidden('token', $token); ?>

        <div class="form-group">
          <label class="form-label" for="password">New Password</label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input class="form-input" type="password" id="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" required minlength="8">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirm Password</label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input class="form-input" type="password" id="password_confirm" name="password_confirm" placeholder="Re-enter your password" autocomplete="new-password" required minlength="8">
          </div>
        </div>

        <button type="submit" class="btn-primary login-submit">Update Password</button>
      <?php echo form_close(); ?>
    <?php endif; ?>

    <a href="<?php echo site_url('auth/login'); ?>" class="forgot-link" style="display:flex;align-items:center;gap:6px;margin-top:20px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
      Back to login
    </a>
  </div>

  <div class="login-footer">
    <p>EduTrack Academic Records System &bull; v1.0.0</p>
  </div>
</div>

</body>
</html>