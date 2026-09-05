<?php
$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
$edit_q        = isset($edit_q) ? $edit_q : NULL;
$exam_minutes  = isset($exam_minutes) ? (int) $exam_minutes : 20;
$exam_per_exam = isset($exam_per_exam) ? (int) $exam_per_exam : 15;
$exam_pass_pct = isset($exam_pass_pct) ? (int) $exam_pass_pct : 70;
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Exam Questions</h2>
      <p class="page-subtitle">Manage the admission exam question bank (<?php echo count($questions); ?> questions)</p>
    </div>
    <a href="<?php echo site_url('admin/applicants'); ?>" class="btn" style="white-space:nowrap;">Back to Applicants</a>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">
        <?php echo $edit_q ? 'Edit Question' : 'Add a Question'; ?>
      </h3>
      <p style="font-size:0.8rem; color:#94A3B8; margin:4px 0 0;">
        Each applicant gets a random set of <?php echo (int) $exam_per_exam; ?> active questions with <?php echo (int) $exam_minutes; ?> minutes. Passing requires <?php echo (int) $exam_pass_pct; ?>% or higher.
      </p>
    </div>
    <div style="padding:24px;">
      <?php echo form_open($edit_q ? site_url('admin/exam_questions/update/' . (int) $edit_q->id) : site_url('admin/exam_questions/store'), array('novalidate' => 'novalidate')); ?>
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" for="question">Question <span style="color:#EF4444;">*</span></label>
          <textarea class="form-input" id="question" name="question" rows="2" placeholder="Enter the question text..." required><?php echo $edit_q ? html_escape($edit_q->question) : ''; ?></textarea>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="option_a">Option A <span style="color:#EF4444;">*</span></label>
            <input class="form-input" id="option_a" name="option_a" placeholder="First choice" required value="<?php echo $edit_q ? html_escape($edit_q->option_a) : ''; ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="option_b">Option B <span style="color:#EF4444;">*</span></label>
            <input class="form-input" id="option_b" name="option_b" placeholder="Second choice" required value="<?php echo $edit_q ? html_escape($edit_q->option_b) : ''; ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="option_c">Option C <span style="color:#EF4444;">*</span></label>
            <input class="form-input" id="option_c" name="option_c" placeholder="Third choice" required value="<?php echo $edit_q ? html_escape($edit_q->option_c) : ''; ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="option_d">Option D <span style="color:#EF4444;">*</span></label>
            <input class="form-input" id="option_d" name="option_d" placeholder="Fourth choice" required value="<?php echo $edit_q ? html_escape($edit_q->option_d) : ''; ?>">
          </div>
        </div>

        <div class="form-group" style="margin:16px 0;">
          <label class="form-label" for="correct_answer">Correct Answer <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="correct_answer" name="correct_answer" style="max-width:200px;" required>
            <?php foreach (array('A', 'B', 'C', 'D') as $letter): ?>
              <option value="<?php echo $letter; ?>" <?php echo $edit_q && $edit_q->correct_answer === $letter ? 'selected' : ''; ?>>Option <?php echo $letter; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex; gap:12px; margin-top:8px;">
          <button type="submit" class="btn-primary"><?php echo $edit_q ? 'Save Changes' : 'Add Question'; ?></button>
          <?php if ($edit_q): ?>
            <a href="<?php echo site_url('admin/exam_questions'); ?>" class="btn">Cancel</a>
          <?php endif; ?>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Question Bank</h3>
    </div>
    <div style="padding:24px;">
      <?php if (empty($questions)): ?>
        <div style="text-align:center; color:#94A3B8; font-size:0.85rem; padding:20px;">No questions yet — add one above.</div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.85rem; min-width:760px;">
            <thead>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">#</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Question</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Answer</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Status</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($questions as $q): ?>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <td style="padding:12px 16px; color:#94A3B8;"><?php echo (int) $q->id; ?></td>
                <td style="padding:12px 16px; color:#1E293B; max-width:420px;">
                  <div style="font-weight:600;"><?php echo html_escape($q->question); ?></div>
                  <div style="color:#94A3B8; font-size:0.75rem; margin-top:3px;">
                    A. <?php echo html_escape($q->option_a); ?> &middot; B. <?php echo html_escape($q->option_b); ?> &middot; C. <?php echo html_escape($q->option_c); ?> &middot; D. <?php echo html_escape($q->option_d); ?>
                  </div>
                </td>
                <td style="padding:12px 16px;"><span class="badge" style="background:#F0FDFA;color:#0D9488;"><?php echo html_escape($q->correct_answer); ?></span></td>
                <td style="padding:12px 16px;">
                  <?php if ($q->is_active): ?>
                    <span class="badge" style="background:#ECFDF5;color:#059669;">Active</span>
                  <?php else: ?>
                    <span class="badge" style="background:#F1F5F9;color:#94A3B8;">Disabled</span>
                  <?php endif; ?>
                </td>
                <td style="padding:12px 16px;">
                  <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <a href="<?php echo site_url('admin/exam_questions?edit=' . (int) $q->id); ?>" class="btn" style="padding:6px 12px; font-size:0.75rem;">Edit</a>
                    <form method="post" action="<?php echo site_url('admin/exam_questions/toggle/' . (int) $q->id); ?>" style="display:inline;">
                      <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
                      <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem; color:<?php echo $q->is_active ? '#F59E0B' : '#059669'; ?>;"><?php echo $q->is_active ? 'Disable' : 'Enable'; ?></button>
                    </form>
                    <form method="post" action="<?php echo site_url('admin/exam_questions/delete/' . (int) $q->id); ?>" style="display:inline;" onsubmit="return confirm('Delete this question permanently?');">
                      <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
                      <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem; color:#DC2626;">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>