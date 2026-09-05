<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — EduTrack</title>
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

    <?php if (empty($reset_requested)): ?>

    <div id="requestState">
      <div style="width:52px;height:52px;border-radius:14px;background:var(--color-primary-50);display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
      </div>
      <h2>Reset your password</h2>
      <p>Enter your email address and we'll send you a link to reset your password.</p>

      <?php echo form_open('auth/forgot_password', array('id' => 'forgotForm', 'class' => 'login-form', 'novalidate' => 'novalidate')); ?>
        <div class="form-group">
          <label class="form-label" for="resetEmail">Email Address</label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
            </span>
            <input class="form-input" type="email" id="resetEmail" name="email" placeholder="your@email.edu" required>
          </div>
          <div class="form-error-text" id="resetEmailError">Please enter a valid email address.</div>
        </div>
        <button type="submit" class="btn-primary login-submit">Send Reset Link</button>
      <?php echo form_close(); ?>

      <a href="<?php echo site_url('auth/login'); ?>" class="forgot-link" style="display:flex;align-items:center;gap:6px;margin-top:20px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Back to login
      </a>
    </div>

    <?php else: ?>

    <div id="confirmState" style="text-align:center;">
      <div style="width:64px;height:64px;border-radius:20px;background:var(--color-primary-50);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <h2>Check your inbox</h2>
      <p style="margin-bottom:8px;">We've sent a password reset link to:</p>
      <div style="background:var(--color-primary-50);border:1.5px solid var(--color-primary-light);border-radius:10px;padding:10px 16px;margin-bottom:24px;display:inline-block;">
        <span style="font-weight:600;color:var(--color-primary);font-size:0.875rem;" id="confirmedEmail"><?php echo html_escape($reset_email); ?></span>
      </div>
      <p class="text-faint" style="font-size:0.8rem;">If you don't see it in a few minutes, check your spam folder.</p>
      <a href="<?php echo site_url('auth/login'); ?>" class="forgot-link" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Back to login
      </a>
    </div>

    <?php endif; ?>

  </div>

  <div class="login-footer">
    <p>EduTrack Academic Records System &bull; v1.0.0</p>
  </div>
</div>

</body>
</html>