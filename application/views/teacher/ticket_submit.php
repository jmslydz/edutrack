<?php
$categories = isset($categories) ? $categories : array('Technical Issue', 'Grade Concern', 'Account Issue', 'Other');

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Submit a Ticket</h2>
      <p class="page-subtitle">Create a new support ticket</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <?php echo form_open('teacher/ticket_submit', array('novalidate' => 'novalidate')); ?>
    <div style="padding:24px;">
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
        <a href="<?php echo site_url('teacher/tickets'); ?>" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">Cancel</a>
        <button type="submit" class="btn-primary" style="flex:1;">Submit Ticket</button>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>