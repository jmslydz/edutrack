<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$applicant   = isset($applicant) ? $applicant : NULL;
$programs    = isset($programs) ? $programs : array();
$notice      = isset($notice) ? $notice : '';
$error       = isset($error) ? $error : '';
$status      = $applicant ? $applicant->status : '';
$in_progress = isset($in_progress) ? $in_progress : FALSE;
$questions   = isset($questions) ? $questions : array();
$exam_minutes = isset($exam_minutes) ? (int) $exam_minutes : 20;
?>

<style>
.app-steps { display:flex; gap:10px; margin-bottom:22px; flex-wrap:wrap; }
.app-step { flex:1; min-width:140px; border:1px solid var(--color-border); border-radius:12px; padding:12px 14px; background:#fff; }
.app-step .num { width:22px; height:22px; border-radius:50%; background:var(--color-primary); color:#fff; font-size:.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; margin-bottom:8px; }
.app-step.done .num { background:#10B981; }
.app-step.active .num { background:var(--color-primary); box-shadow:0 0 0 4px rgba(13,148,136,.15); }
.app-step .t { font-size:.85rem; font-weight:700; color:var(--color-text); }
.app-step .d { font-size:.72rem; color:var(--color-text-faint); margin-top:2px; }
.app-step.done .t, .app-step.done .d { color:#9CA3AF; }

.code-box { border:1.5px dashed var(--color-border-strong, #CBD5E1); border-radius:14px; padding:22px; text-align:center; background:#F8FAFC; }
.code-input { width:min(340px, 100%); text-align:center; letter-spacing:.35em; font-size:1.15rem; font-weight:700; text-transform:uppercase; }
.code-hint { font-size:.8rem; color:var(--color-text-faint); margin-top:10px; }

.exam-timer { position:sticky; top:70px; z-index:5; background:var(--color-primary); color:#fff; border-radius:12px; padding:10px 18px; display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; box-shadow:0 6px 18px rgba(13,148,136,.25); }
.exam-timer .t-label { font-size:.75rem; opacity:.85; text-transform:uppercase; letter-spacing:.06em; font-weight:600; }
.exam-timer .t-time { font-size:1.15rem; font-weight:800; font-variant-numeric:tabular-nums; }
.exam-timer.warning { background:#DC2626; box-shadow:0 6px 18px rgba(220,38,38,.25); }

.q-card { border:1px solid var(--color-border); border-radius:12px; padding:16px 18px; margin-bottom:14px; background:#fff; }
.q-card .q-num { font-size:.7rem; font-weight:800; color:var(--color-primary); text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.q-card .q-text { font-size:.95rem; font-weight:600; color:var(--color-text); margin-bottom:12px; }
.q-option { display:flex; align-items:flex-start; gap:10px; padding:9px 12px; border:1px solid var(--color-border); border-radius:10px; margin-bottom:8px; cursor:pointer; font-size:.9rem; transition:border-color .15s, background .15s; }
.q-option:hover { border-color:var(--color-primary); background:#F0FDFA; }
.q-option input { accent-color:var(--color-primary); margin-top:2px; }
.q-option.selected { border-color:var(--color-primary); background:#F0FDFA; }

.status-banner { border-radius:14px; padding:20px 22px; margin-bottom:18px; display:flex; gap:14px; align-items:flex-start; }
.status-banner .sb-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.status-banner h3 { font-size:1.05rem; font-weight:700; margin-bottom:4px; }
.status-banner p { font-size:.88rem; margin:0; color:var(--color-text-muted); }
.sb-green { background:#ECFDF5; border:1px solid #A7F3D0; } .sb-green .sb-icon { background:#10B981; }
.sb-red   { background:#FEF2F2; border:1px solid #FECACA; } .sb-red .sb-icon { background:#DC2626; }
.sb-amber { background:#FFFBEB; border:1px solid #FDE68A; } .sb-amber .sb-icon { background:#F59E0B; }
.sb-blue  { background:#EFF6FF; border:1px solid #BFDBFE; } .sb-blue .sb-icon { background:#3B82F6; }

.program-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px; }
.program-card { border:1px solid var(--color-border); border-radius:12px; padding:16px; cursor:pointer; transition:border-color .15s, box-shadow .15s; background:#fff; }
.program-card:hover { border-color:var(--color-primary); box-shadow:0 4px 14px rgba(13,148,136,.12); }
.program-card input { accent-color:var(--color-primary); margin-right:8px; }
.program-card .p-code { font-size:.68rem; font-weight:800; color:var(--color-primary); letter-spacing:.08em; }
.program-card .p-name { font-size:.9rem; font-weight:700; margin-top:4px; color:var(--color-text); }
</style>

<div class="page-header">
  <h1 class="page-title">Admission Portal</h1>
  <p class="page-subtitle">
    <?php echo html_escape($applicant ? $applicant->full_name : ''); ?> — track your application and take your exam
  </p>
</div>

<?php if ($notice !== ''): ?>
  <div class="alert-success visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($notice); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
  <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($error); ?></div>
<?php endif; ?>

<?php if ($status === 'pending_exam'): ?>

  <?php $step = $in_progress ? 2 : 1; ?>
  <div class="app-steps">
    <div class="app-step <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">
      <div class="num">1</div><div class="t">Register</div><div class="d">Account created</div>
    </div>
    <div class="app-step <?php echo $step >= 2 ? 'active' : ''; ?>">
      <div class="num">2</div><div class="t">Take the exam</div><div class="d">At the campus</div>
    </div>
    <div class="app-step">
      <div class="num">3</div><div class="t">Choose program</div><div class="d">After passing</div>
    </div>
    <div class="app-step">
      <div class="num">4</div><div class="t">Registrar review</div><div class="d">Admission result</div>
    </div>
  </div>

  <?php if ( ! $in_progress): ?>

    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--color-border); padding-bottom:14px; margin-bottom:18px;">
        <h2 style="font-size:1rem; font-weight:700; color:var(--color-text);">Step 2 — Take the admission exam at the campus</h2>
      </div>

      <div class="status-banner sb-amber">
        <div class="sb-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <h3>Please visit the campus</h3>
          <p>
            To prevent cheating, the admission exam is taken <strong>in person at the campus computer lab</strong>.
            Visit the Registrar's Office during office hours and ask for your <strong>one-time exam code</strong>.
            You will use that code below to start your exam. No one can take the exam for you — the code is
            single-use and tied to your account.
          </p>
        </div>
      </div>

      <div class="code-box">
        <h3 style="font-size:.95rem; font-weight:700; color:var(--color-text); margin-bottom:14px;">Enter your exam code</h3>
        <?php echo form_open('applicant/start_exam', array('id' => 'codeForm')); ?>
          <input type="text" name="exam_code" id="exam_code" class="form-input code-input"
                 placeholder="XXXX-XXXX" maxlength="40" autocomplete="off" required>
          <p class="code-hint">The registrar will give you this code when you arrive at the campus.</p>
          <button type="submit" class="btn-primary" style="margin-top:16px;">Start My Exam</button>
        <?php echo form_close(); ?>
      </div>
    </div>

  <?php else: ?>

    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--color-border); padding-bottom:14px; margin-bottom:18px;">
        <h2 style="font-size:1rem; font-weight:700; color:var(--color-text);">
          Exam in progress
        </h2>
        <p style="font-size:.8rem; color:var(--color-text-faint); margin-top:4px;">
          Your <?php echo (int) $exam_minutes; ?>-minute exam is running. The timer does not pause —
          the exam submits automatically when time runs out, whether or not you are on this page.
        </p>
      </div>

      <div class="status-banner sb-blue">
        <div class="sb-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <h3>Time remaining: <span id="dashTimer" style="font-variant-numeric:tabular-nums;">--:--</span></h3>
          <p>You can step away and come back — your answers are saved as you go. Use the button below to resume.</p>
        </div>
      </div>

      <div style="display:flex; gap:12px; margin-top:4px;">
        <a href="<?php echo site_url('applicant/exam'); ?>" class="btn-primary" style="text-decoration:none;">Resume Exam</a>
      </div>
    </div>

  <?php endif; ?>

<?php elseif ($status === 'failed_exam'): ?>

  <div class="status-banner sb-red">
    <div class="sb-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div>
      <h3>Exam not passed</h3>
      <p>
        You scored <strong><?php echo (int) $applicant->exam_score; ?> / <?php echo (int) $applicant->exam_total; ?></strong>,
        which is below the passing mark. If you would like to retake the exam, please contact the
        Registrar's Office at the campus for instructions.
      </p>
    </div>
  </div>

<?php elseif ($status === 'passed_exam'): ?>

  <?php $step = ($applicant->preferred_program_id !== NULL) ? 3 : 3; ?>
  <div class="app-steps">
    <div class="app-step done"><div class="num">1</div><div class="t">Register</div><div class="d">Account created</div></div>
    <div class="app-step done"><div class="num">2</div><div class="t">Take the exam</div><div class="d">Passed!</div></div>
    <div class="app-step <?php echo $applicant->preferred_program_id === NULL ? 'active' : 'done'; ?>">
      <div class="num">3</div><div class="t">Choose program</div><div class="d">Your preference</div>
    </div>
    <div class="app-step"><div class="num">4</div><div class="t">Registrar review</div><div class="d">Admission result</div></div>
  </div>

  <div class="status-banner sb-green">
    <div class="sb-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
      <h3>Congratulations — you passed the exam!</h3>
      <p>Score: <strong><?php echo (int) $applicant->exam_score; ?> / <?php echo (int) $applicant->exam_total; ?></strong>. Now choose the program you would like to enroll in.</p>
    </div>
  </div>

  <?php if ($applicant->preferred_program_id === NULL): ?>

    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--color-border); padding-bottom:14px; margin-bottom:18px;">
        <h2 style="font-size:1rem; font-weight:700; color:var(--color-text);">Step 3 — Choose your preferred program</h2>
        <p style="font-size:.8rem; color:var(--color-text-faint); margin-top:4px;">
          The registrar will still verify your credentials before admitting you.
        </p>
      </div>

      <?php if (empty($programs)): ?>
        <p style="color:var(--color-text-faint); font-size:.9rem;">No programs are available right now. Please check back later.</p>
      <?php else: ?>
        <?php echo form_open('applicant/choose_program', array('id' => 'programForm')); ?>
          <div class="program-grid">
            <?php foreach ($programs as $p): ?>
              <label class="program-card">
                <input type="radio" name="program_id" value="<?php echo (int) $p->id; ?>" required>
                <div class="p-code"><?php echo html_escape($p->short_code !== NULL ? $p->short_code : $p->program_code); ?></div>
                <div class="p-name"><?php echo html_escape($p->program_name); ?></div>
              </label>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="btn-primary" style="margin-top:18px;">Save My Choice</button>
        <?php echo form_close(); ?>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="status-banner sb-blue">
      <div class="sb-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div>
        <h3>Application submitted for review</h3>
        <p>
          Your preferred program is <strong><?php echo html_escape($applicant->preferred_program_name); ?></strong>.
          The registrar will check your credentials and email you the admission result.
        </p>
      </div>
    </div>

  <?php endif; ?>

<?php elseif ($status === 'admitted'): ?>

  <div class="status-banner sb-green">
    <div class="sb-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
      <h3>You are admitted! Welcome to EduTrack.</h3>
      <p>
        Your credentials have been verified and you have been admitted. You can now sign in
        to the <a href="<?php echo site_url('student/dashboard'); ?>" style="color:var(--color-primary); font-weight:600;">Student Portal</a>
        to see your class schedule and grades.
      </p>
    </div>
  </div>

<?php elseif ($status === 'rejected'): ?>

  <div class="status-banner sb-red">
    <div class="sb-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div>
      <h3>Application not admitted</h3>
      <p>
        After reviewing your application, the registrar was unable to admit you at this time.
        If you believe this is a mistake, please contact the Registrar's Office.
      </p>
    </div>
  </div>

<?php else: ?>
  <p style="color:var(--color-text-faint);">Application status: <?php echo html_escape($status); ?></p>
<?php endif; ?>

<script>
(function () {
  // Remaining-time display on the dashboard while the exam is in progress
  var dashTimer = document.getElementById('dashTimer');
  if (dashTimer) {
    var startedAt = <?php echo $in_progress ? (int) $exam_started : 0; ?>;
    var minutes = <?php echo $in_progress ? (int) $exam_minutes : 0; ?>;
    var deadline = (startedAt + minutes * 60) * 1000;

    function tick() {
      var remain = deadline - Date.now();
      if (remain <= 0) {
        dashTimer.textContent = '00:00';
        return;
      }
      var m = Math.floor(remain / 60000);
      var s = Math.floor((remain % 60000) / 1000);
      dashTimer.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    tick();
    setInterval(tick, 1000);
  }
})();
</script>