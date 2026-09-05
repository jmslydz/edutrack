<?php
$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Announcements</h2>
      <p class="page-subtitle">Send broadcast announcements to users</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <?php echo form_open('admin/announcements', array('novalidate' => 'novalidate')); ?>
    <div style="padding:24px;">
      <div class="form-group">
        <label class="form-label" for="title">Title <span style="color:#EF4444;">*</span></label>
        <input class="form-input" id="title" name="title" placeholder="Announcement title" value="<?php echo html_escape($this->input->post('title')); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="message">Message <span style="color:#EF4444;">*</span></label>
        <textarea class="form-input" id="message" name="message" rows="5" placeholder="Enter your announcement message..." required><?php echo html_escape($this->input->post('message')); ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="audience">Audience <span style="color:#EF4444;">*</span></label>
        <select class="form-input" id="audience" name="audience" required>
          <option value="">Select audience...</option>
          <option value="all" <?php echo $this->input->post('audience') === 'all' ? 'selected' : ''; ?>>All Users (Students + Teachers)</option>
          <option value="teachers" <?php echo $this->input->post('audience') === 'teachers' ? 'selected' : ''; ?>>Teachers Only</option>
          <option value="students" <?php echo $this->input->post('audience') === 'students' ? 'selected' : ''; ?>>Students Only</option>
        </select>
      </div>
      <div style="display:flex; gap:12px; margin-top:24px;">
        <button type="submit" class="btn-primary" style="flex:1;">Send Announcement</button>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>

  <div class="card" style="margin-top:24px;">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Recent Announcements</h3>
    </div>
    <div style="padding:24px;">
      <?php
      $this->load->model('Notification_model');
      $recent = $this->Notification_model->recent_announcements(10);
      ?>
      <?php if (empty($recent)): ?>
        <div style="text-align:center; color:#94A3B8; font-size:0.85rem; padding:20px;">No announcements sent yet.</div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
            <thead>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Title</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Audience</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Sent</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Recipients</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $a): ?>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <td style="padding:12px 16px; color:#1E293B;"><?php echo html_escape($a->title); ?></td>
                <td style="padding:12px 16px;">
                  <span class="badge" style="background:#F0F9FF;color:#0D9488;"><?php echo ucfirst(html_escape($a->audience_label)); ?></span>
                </td>
                <td style="padding:12px 16px; color:#64748B;"><?php echo html_escape(_notif_time($a->created_at)); ?></td>
                <td style="padding:12px 16px; color:#64748B;"><?php echo (int) $a->recipient_count; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>