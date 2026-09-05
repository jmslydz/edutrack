<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — EduTrack Academic Records System</title>

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

  <div class="login-card login-card--wide">
    <h2>Create your account</h2>
    <p>Register to apply for admission. You will need to take the admission exam at the campus.</p>

    <?php echo form_open('auth/register', array('id' => 'registerForm', 'class' => 'login-form', 'novalidate' => 'novalidate')); ?>

      <div class="alert-error<?php echo (isset($reg_error) && $reg_error !== '') ? ' visible' : ''; ?>" id="serverError" role="alert">
        <?php echo isset($reg_error) ? html_escape($reg_error) : ''; ?>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label" for="first_name">First Name</label>
          <input
            class="form-input"
            type="text"
            id="first_name"
            name="first_name"
            placeholder="Maria"
            autocomplete="given-name"
            required
            value="<?php echo isset($first_name) ? html_escape($first_name) : ''; ?>"
          >
          <div class="form-error-text" id="firstNameError">Please enter your first name.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="last_name">Last Name</label>
          <input
            class="form-input"
            type="text"
            id="last_name"
            name="last_name"
            placeholder="Santos"
            autocomplete="family-name"
            required
            value="<?php echo isset($last_name) ? html_escape($last_name) : ''; ?>"
          >
          <div class="form-error-text" id="lastNameError">Please enter your last name.</div>
        </div>
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
            placeholder="maria.santos@email.com"
            autocomplete="username"
            required
            value="<?php echo isset($email) ? html_escape($email) : ''; ?>"
          >
        </div>
        <div class="form-error-text" id="emailError">Please enter a valid email address.</div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
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
              placeholder="At least 8 characters"
              autocomplete="new-password"
              required
              minlength="8"
            >
          </div>
          <div class="form-error-text" id="passwordError">Password must be at least 8 characters.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirm Password</label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input
              class="form-input"
              type="password"
              id="password_confirm"
              name="password_confirm"
              placeholder="Repeat your password"
              autocomplete="new-password"
              required
              minlength="8"
            >
          </div>
          <div class="form-error-text" id="confirmError">Passwords do not match.</div>
        </div>
      </div>

      <button type="submit" class="btn-primary login-submit" id="registerSubmitBtn">Create Account</button>
    </form>

    <p class="login-switch">Already have an account? <a href="<?php echo site_url('auth/login'); ?>">Sign in</a></p>
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
   * The backend re-validates everything on the server.
   */
  (function () {
    var form = document.getElementById('registerForm');
    var fields = {
      first_name:  { el: document.getElementById('first_name'),  err: document.getElementById('firstNameError'), test: function (v) { return v.trim().length > 0; } },
      last_name:   { el: document.getElementById('last_name'),   err: document.getElementById('lastNameError'),  test: function (v) { return v.trim().length > 0; } },
      email:       { el: document.getElementById('email'),       err: document.getElementById('emailError'),     test: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); } },
      password:    { el: document.getElementById('password'),    err: document.getElementById('passwordError'),  test: function (v) { return v.length >= 8; } },
      password_confirm: { el: document.getElementById('password_confirm'), err: document.getElementById('confirmError'), test: function (v) { return v.length >= 8; } }
    };

    function validateField(key) {
      var f = fields[key];
      var valid = f.test(f.el.value.trim());
      f.el.classList.toggle('has-error', !valid);
      f.err.classList.toggle('visible', !valid);
      return valid;
    }

    Object.keys(fields).forEach(function (key) {
      fields[key].el.addEventListener('blur', function () { validateField(key); });
    });

    form.addEventListener('submit', function (e) {
      var ok = Object.keys(fields).every(function (key) { return validateField(key); });
      var pw = document.getElementById('password').value;
      var cf = document.getElementById('password_confirm').value;
      var match = pw === cf && pw.length >= 8;
      document.getElementById('password_confirm').classList.toggle('has-error', !match);
      document.getElementById('confirmError').classList.toggle('visible', !match);
      if (!ok || !match) e.preventDefault();
    });
  })();
</script>

</body>
</html>