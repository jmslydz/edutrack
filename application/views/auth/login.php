<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — EduTrack Academic Records System</title>

<link rel="stylesheet" href="<?php echo base_url('assets/css/design-system.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>">
</head>
<body>

<div class="login-page">
  <div class="bg-blob bg-blob--1"></div>
  <div class="bg-blob bg-blob--2"></div>
  <div class="bg-blob bg-blob--3"></div>

  <div class="login-brand">
    <div class="login-brand-icon" aria-hidden="true">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 10 12 5 2 10l10 5 10-5Z"/>
        <path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>
      </svg>
    </div>
    <h1>EduTrack</h1>
    <p>Academic Records System</p>
  </div>

  <div class="login-card">
    <h2>Welcome back</h2>
    <p>Sign in to your account to continue</p>

    <?php echo form_open('auth/login', array('id' => 'loginForm', 'class' => 'login-form', 'novalidate' => 'novalidate')); ?>

      <div class="alert-error<?php echo (isset($login_error) && $login_error !== '') ? ' visible' : ''; ?>" id="serverError" role="alert">
        <?php echo isset($login_error) ? html_escape($login_error) : ''; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-icon-wrap">
          <span class="field-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>
            </svg>
          </span>
          <input
            class="form-input"
            type="email"
            id="email"
            name="email"
            placeholder="admin@school.edu"
            autocomplete="username"
            required
          >
        </div>
        <div class="form-error-text" id="emailError">Please enter a valid email address.</div>
      </div>

      <div class="form-group">
        <div class="password-row">
          <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
          <a href="<?php echo site_url('auth/forgot_password'); ?>" class="forgot-link">Forgot password?</a>
        </div>
        <div class="input-icon-wrap">
          <span class="field-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input
            class="form-input"
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
            minlength="8"
          >
        </div>
        <div class="form-error-text" id="passwordError">Password must be at least 8 characters.</div>
      </div>

      <div class="remember-row">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Keep me signed in</label>
      </div>

      <button type="submit" class="btn-primary login-submit" id="loginSubmitBtn">Sign In</button>
    </form>

    <p class="login-switch">New applicant? <a href="<?php echo site_url('auth/register'); ?>">Create an account</a></p>
  </div>

  <div class="login-footer">
    <p>EduTrack Academic Records System &bull; v1.0.0</p>
    <p>&copy; <span id="year"></span> School Grading System. All rights reserved.</p>
  </div>
</div>

<script>
  document.getElementById('year').textContent = new Date().getFullYear();

  /*
   * CLIENT-SIDE VALIDATION — UX CONVENIENCE ONLY.
   *
   * This is NOT a security boundary. It only exists so the user gets
   * instant feedback instead of waiting on a round trip. The backend
   * MUST re-validate everything below on the server before touching
   * the database — never trust that this code ran.
   */
  (function () {
    var form = document.getElementById('loginForm');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var emailError = document.getElementById('emailError');
    var passwordError = document.getElementById('passwordError');

    function isValidEmail(value) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function validateField(input, errorEl, isValid) {
      var valid = isValid(input.value.trim());
      input.classList.toggle('has-error', !valid);
      errorEl.classList.toggle('visible', !valid);
      return valid;
    }

    email.addEventListener('blur', function () {
      validateField(email, emailError, isValidEmail);
    });

    password.addEventListener('blur', function () {
      validateField(password, passwordError, function (v) { return v.length >= 8; });
    });

    form.addEventListener('submit', function (e) {
      var emailValid = validateField(email, emailError, isValidEmail);
      var passwordValid = validateField(password, passwordError, function (v) { return v.length >= 8; });

      if (!emailValid || !passwordValid) {
        e.preventDefault();
      }
    });
  })();
</script>

</body>
</html>
