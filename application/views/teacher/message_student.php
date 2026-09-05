<?php
$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Message a Student</h2>
      <p class="page-subtitle">Send a direct message to one of your students</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <?php if (empty($my_students)): ?>
    <div class="card" style="padding:32px; text-align:center; color:#94A3B8;">
      <p>You don't have any students assigned to you yet.</p>
      <a href="<?php echo site_url('teacher/dashboard'); ?>" class="btn-primary" style="display:inline-block; margin-top:12px; text-decoration:none;">Back to Dashboard</a>
    </div>
  <?php else: ?>
    <div class="card">
      <?php echo form_open('teacher/message_student', array('novalidate' => 'novalidate')); ?>
      <div style="padding:24px;">
        <div class="form-group">
          <label class="form-label" for="recipient_id">Recipient <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="recipient_id" name="recipient_id" required>
            <option value="">Select a student...</option>
            <?php foreach ($my_students as $stu): ?>
              <option value="<?php echo (int) $stu->id; ?>" <?php echo $this->input->post('recipient_id') == $stu->id ? 'selected' : ''; ?>><?php echo html_escape($stu->first_name . ' ' . $stu->last_name); ?> (<?php echo html_escape($stu->student_no); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
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
          <input class="form-input" id="subject" name="subject" placeholder="Brief summary" value="<?php echo html_escape($this->input->post('subject')); ?>" maxlength="150" required>
          <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Maximum 150 characters</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="message">Message <span style="color:#EF4444;">*</span></label>
          <textarea class="form-input" id="message" name="message" rows="8" placeholder="Write your message to the student..." maxlength="5000" required><?php echo html_escape($this->input->post('message')); ?></textarea>
          <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Maximum 5000 characters</div>
        </div>
        <div style="display:flex; gap:12px; margin-top:24px;">
          <a href="<?php echo site_url('teacher/tickets'); ?>" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">Cancel</a>
          <button type="submit" class="btn-primary" style="flex:1;">Send Message</button>
        </div>
      </div>
      <?php echo form_close(); ?>
    </div>
  <?php endif; ?>
