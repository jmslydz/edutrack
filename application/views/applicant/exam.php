<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$applicant    = isset($applicant) ? $applicant : NULL;
$questions    = isset($questions) ? $questions : array();
$exam_minutes = isset($exam_minutes) ? (int) $exam_minutes : 20;
$exam_started = isset($exam_started) ? (int) $exam_started : 0;
$notice       = isset($notice) ? $notice : '';
$error        = isset($error) ? $error : '';
?>

<style>
.exam-topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
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
</style>

<div class="page-header">
  <h1 class="page-title">Admission Exam</h1>
  <p class="page-subtitle">
    <?php echo html_escape($applicant ? $applicant->full_name : ''); ?> — answer all <?php echo count($questions); ?> questions
  </p>
</div>

<div class="exam-topbar">
  <a href="<?php echo site_url('applicant/dashboard'); ?>" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
    Back to Dashboard
  </a>
  <span style="font-size:.78rem; color:var(--color-text-faint);">
    The exam submits automatically when time runs out.
  </span>
</div>

<?php if ($notice !== ''): ?>
  <div class="alert-success visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($notice); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
  <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($error); ?></div>
<?php endif; ?>

<?php if ( ! empty($questions)): ?>

  <div class="exam-timer" id="examTimerBar">
    <div class="t-label">Time remaining</div>
    <div class="t-time" id="examTimer">--:--</div>
  </div>

  <div class="card">
    <div class="card-header" style="border-bottom:1px solid var(--color-border); padding-bottom:14px; margin-bottom:18px;">
      <h2 style="font-size:1rem; font-weight:700; color:var(--color-text);">
        Admission Exam — <?php echo count($questions); ?> questions
      </h2>
      <p style="font-size:.8rem; color:var(--color-text-faint); margin-top:4px;">
        Answer all questions. The exam submits automatically when time runs out.
      </p>
    </div>

    <?php echo form_open('applicant/submit_exam', array('id' => 'examForm')); ?>
      <?php $i = 0; foreach ($questions as $q): $i++; $opts = array('A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d); ?>
      <div class="q-card">
        <div class="q-num">Question <?php echo $i; ?> of <?php echo count($questions); ?></div>
        <div class="q-text"><?php echo html_escape($q->question); ?></div>
        <?php foreach ($opts as $letter => $text): ?>
          <label class="q-option">
            <input type="radio" name="q_<?php echo (int) $q->question_id; ?>" value="<?php echo $letter; ?>">
            <span><strong><?php echo $letter; ?>.</strong> <?php echo html_escape($text); ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>

      <div style="display:flex; gap:12px; margin-top:8px;">
        <button type="submit" class="btn-primary">Submit Exam</button>
        <button type="button" class="btn" onclick="submitEarly()">Submit early</button>
      </div>
    <?php echo form_close(); ?>
  </div>

<?php else: ?>
  <div class="alert-error">The exam has no questions yet. Please inform the registrar.</div>
<?php endif; ?>

<script>
(function () {
  // Highlight the selected radio option
  document.querySelectorAll('.q-option input').forEach(function (input) {
    input.addEventListener('change', function () {
      var name = input.name;
      document.querySelectorAll('input[name="' + name + '"]').forEach(function (r) {
        r.closest('.q-option').classList.toggle('selected', r.checked);
      });
    });
  });

  // Exam countdown with auto-submit at zero
  var timerEl = document.getElementById('examTimer');
  var barEl = document.getElementById('examTimerBar');
  if (timerEl && barEl) {
    var deadline = (<?php echo (int) $exam_started; ?> + <?php echo (int) $exam_minutes; ?> * 60) * 1000;

    function tick() {
      var remain = deadline - Date.now();
      if (remain <= 0) {
        timerEl.textContent = '00:00';
        barEl.classList.add('warning');
        var form = document.getElementById('examForm');
        if (form) form.submit();
        return;
      }
      var m = Math.floor(remain / 60000);
      var s = Math.floor((remain % 60000) / 1000);
      timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      if (remain <= 60 * 1000) barEl.classList.add('warning');
    }
    tick();
    setInterval(tick, 1000);
  }

  window.submitEarly = function () {
    if (confirm('Submit your exam now? You cannot change your answers after submitting.')) {
      document.getElementById('examForm').submit();
    }
  };
})();
</script>