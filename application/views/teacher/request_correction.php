<?php
$assignment         = isset($assignment) ? $assignment : NULL;
$students           = isset($students) ? $students : array();
$grading_periods    = isset($grading_periods) ? $grading_periods : array();
$grades_by_student  = isset($grades_by_student) ? $grades_by_student : array();
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Request Grade Correction</h2>
      <p class="page-subtitle">Request a grade correction for a past semester (<?php echo html_escape($assignment->subject_code . ' — ' . $assignment->section_name); ?>)</p>
    </div>
    <a href="<?php echo site_url('teacher/my_subjects'); ?>" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.8rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to My Subjects
    </a>
  </div>

  <?php $flash_success = $this->session->flashdata('grade_success'); ?>
  <?php $flash_error   = $this->session->flashdata('grade_error'); ?>
  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:24px;">
      <div style="margin-bottom:24px; padding:16px; background:#FFF7ED; border-radius:8px; border:1px solid #FCD34D;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 4px;">Grade Correction Request</h3>
        <p style="margin:0; font-size:0.85rem; color:#92400E;">This semester has ended. To correct a grade, submit a request for Admin review. The grade will only be changed after Admin approval.</p>
      </div>

      <div style="margin-bottom:24px; padding:16px; background:#FAFAFA; border-radius:8px;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">Class: <?php echo html_escape($assignment->subject_code . ' — ' . $assignment->section_name); ?></h3>
        <p style="margin:0; color:#64748B; font-size:0.85rem;">Semester: <?php echo html_escape($assignment->sem_name ?? '—'); ?></p>
      </div>

      <?php if (empty($students)): ?>
        <div style="padding:16px; background:#FAFAFA; border-radius:8px; color:#94A3B8; font-size:0.85rem; text-align:center;">No students found in this class.</div>
      <?php else: ?>
        <?php echo form_open('teacher/submit_correction_request', array('novalidate' => 'novalidate')); ?>
          <input type="hidden" name="assignment_id" value="<?php echo (int) $assignment->assignment_id; ?>">
          <input type="hidden" name="subject_id" value="<?php echo (int) $assignment->subject_id; ?>">
          <input type="hidden" name="period_name" value="Midterm"> <!-- Will be overridden by the select below -->

          <div style="margin-bottom:24px;">
            <label class="form-label">Select Student <span style="color:#EF4444;">*</span></label>
            <select class="form-input" name="student_id" required onchange="updateGrades(this.value)">
              <option value="">Select a student...</option>
              <?php foreach ($students as $s): ?>
                <option value="<?php echo (int) $s->student_id; ?>">
                  <?php echo html_escape($s->last_name . ', ' . $s->first_name . ' (' . $s->student_no . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Grading Period <span style="color:#EF4444;">*</span></label>
            <select class="form-input" name="period_name" id="period_name" required onchange="updateGradesForPeriod(this.value)">
              <option value="">Select a period...</option>
              <?php foreach ($grading_periods as $gp): ?>
                <option value="<?php echo html_escape($gp->period_name); ?>"><?php echo html_escape($gp->period_name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Current Grade (Auto-filled)</label>
            <input class="form-input" type="text" id="current_grade" readonly style="background:#F1F5F9; color:#64748B;" placeholder="Select student and period">
            <input type="hidden" name="old_value" id="old_value">
          </div>

          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label" for="requested_value">Requested Grade <span style="color:#EF4444;">*</span></label>
            <select class="form-input" name="requested_value" id="requested_value" required>
              <option value="">Select grade...</option>
              <?php 
              for ($v = 1.00; $v <= 5.00; $v += 0.25) {
                  $val = number_format($v, 2, '.', '');
                  echo '<option value="' . $val . '">' . $val . '</option>';
              }
              ?>
            </select>
            <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Select the corrected grade value (1.00–5.00 in 0.25 increments)</div>
          </div>

          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label" for="reason">Reason for Correction <span style="color:#EF4444;">*</span></label>
            <textarea class="form-input" id="reason" name="reason" rows="4" placeholder="Explain why this grade needs to be corrected (minimum 10 characters)..." required></textarea>
            <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Minimum 10 characters. Be specific about why this correction is needed.</div>
          </div>

          <div style="display:flex; gap:12px; margin-top:24px;">
            <a href="<?php echo site_url('teacher/my_subjects'); ?>" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">Cancel</a>
            <button type="submit" class="btn-primary" style="flex:1;">Submit Correction Request</button>
          </div>
        <?php echo form_close(); ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
  // Global data for JS
  var gradesData = <?php echo json_encode($grades_by_student); ?>;
  var studentsData = <?php echo json_encode($students); ?>;

  function updateGrades(studentId) {
    var period = document.getElementById('period_name').value;
    var gradeInput = document.getElementById('current_grade');
    var oldValueInput = document.getElementById('old_value');
    
    if (!studentId || !period) {
      gradeInput.value = '';
      oldValueInput.value = '';
      return;
    }
    
    var grade = (gradesData[studentId] && gradesData[studentId][period]) ? gradesData[studentId][period] : null;
    if (grade !== null && grade !== undefined) {
      gradeInput.value = grade.toFixed(2);
      oldValueInput.value = grade.toFixed(2);
    } else {
      gradeInput.value = '—';
      oldValueInput.value = '';
    }
  }
  function updateGradesForPeriod(period) {
    var studentId = document.querySelector('select[name="student_id"]').value;
    updateGrades(studentId);
  }
  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    var studentSelect = document.querySelector('select[name="student_id"]');
    if (studentSelect) {
      studentSelect.addEventListener('change', function() {
        updateGrades(this.value);
      });
    }
    var periodSelect = document.getElementById('period_name');
    if (periodSelect) {
      periodSelect.addEventListener('change', function() {
        var studentId = document.querySelector('select[name="student_id"]').value;
        updateGradesForPeriod(this.value);
      });
    }
  });
  </script>