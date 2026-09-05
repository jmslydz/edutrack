<?php
$categories  = isset($categories)  ? $categories  : array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');
$my_teachers = isset($my_teachers) ? $my_teachers : array();

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Submit a Ticket</h2>
      <p class="page-subtitle">Send a concern to Admin or one of your instructors</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <?php echo form_open('student/ticket_submit', array('novalidate' => 'novalidate')); ?>
    <div style="padding:24px;">
      <div class="form-group">
        <label class="form-label">Recipient <span style="color:#EF4444;">*</span></label>
        <div style="display:flex; flex-direction:column; gap:8px; margin-top:4px;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:12px 16px; border:2px solid #E2E8F0; border-radius:8px; transition:border-color 0.2s;">
            <input type="radio" name="recipient_type" value="admin" checked onchange="toggleTeacherPicker()" style="width:18px;height:18px;accent-color:#0D9488;">
            <span style="font-weight:500;color:#1E293B;">To Admin</span>
            <span class="text-faint" style="font-size:0.75rem;">(General concerns, technical issues, account issues)</span>
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:12px 16px; border:2px solid #E2E8F0; border-radius:8px; transition:border-color 0.2s;">
            <input type="radio" name="recipient_type" value="teacher" onchange="toggleTeacherPicker(this.value)" style="width:18px;height:18px;accent-color:#0D9488;">
            <span style="font-weight:500;color:#1E293B;">To My Instructor</span>
            <span class="text-faint" style="font-size:0.75rem;">(Grade concerns, class-specific questions)</span>
          </label>
        </div>
        <div id="teacher_select_group" style="display:none; margin-top:12px;">
          <label class="form-label" for="recipient_id">Select Teacher <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="recipient_id" name="recipient_id">
            <option value="">Select a teacher...</option>
            <?php foreach ($my_teachers as $t): ?>
              <option value="<?php echo (int) $t->teacher_user_id; ?>" <?php echo $this->input->post('recipient_id') == $t->teacher_user_id ? 'selected' : ''; ?>>
                <?php echo html_escape($t->teacher_name . ($t->subjects_label ? ' — ' . $t->subjects_label : '')); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($my_teachers)): ?>
            <div class="text-faint" style="font-size:0.75rem;margin-top:4px;color:#EF4444;">No teachers assigned to your current classes.</div>
          <?php endif; ?>
        </div>
      </div>

      <hr style="margin:24px 0; border-color:#F1F5F9;">

      <div class="form-group">
        <label class="form-label" for="category">Category <span style="color:#EF4444;">*</span></label>
        <select class="form-input" id="category" name="category" required>
          <option value="">Select a category...</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo html_escape($cat); ?>" <?php echo $this->input->post('category') === $cat ? 'selected' : ''; ?>><?php echo html_escape($cat); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="subject">Subject <span style="color:#EF4444;">*</span></label>
        <input class="form-input" id="subject" name="subject" placeholder="Brief summary of your issue" value="<?php echo html_escape($this->input->post('subject')); ?>" maxlength="150" required>
        <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Maximum 150 characters</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="message">Message <span style="color:#EF4444;">*</span></label>
        <textarea class="form-input" id="message" name="message" rows="8" placeholder="Describe your issue in detail..." maxlength="5000" required><?php echo html_escape($this->input->post('message')); ?></textarea>
        <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Maximum 5000 characters</div>
      </div>
      <div style="display:flex; gap:12px; margin-top:24px;">
        <a href="<?php echo site_url('student/tickets'); ?>" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">Cancel</a>
        <button type="submit" class="btn-primary" style="flex:1;">Submit Ticket</button>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>

  <script>
  function toggleTeacherPicker(val) {
    const teacherGroup = document.getElementById('teacher_select_group');
    const teacherRadio = document.querySelector('input[name="recipient_type"][value="teacher"]');
    teacherGroup.style.display = teacherRadio.checked ? 'block' : 'none';
  }
  // Initialize on load
  document.addEventListener('DOMContentLoaded', toggleRecipientFields);
  </script>