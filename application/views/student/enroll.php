<?php
$eligible         = isset($eligible) ? $eligible : TRUE;
$reason           = isset($reason) ? $reason : '';
$sections         = isset($sections) ? $sections : array();
$target_semester  = isset($target_semester) ? $target_semester : NULL;
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Enroll for Next Semester</h2>
      <p class="page-subtitle">Select your section for the upcoming semester</p>
    </div>
    <a href="<?php echo site_url('student/dashboard'); ?>" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.8rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
  </div>

  <?php if ( ! $eligible): ?>
    <div class="card" style="padding:48px; text-align:center; background:#FAFAFA; border:1px solid #F1F5F9; border-radius:12px;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 9v4m0 4h.01"/></svg>
      <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">Not Eligible for Enrollment</h3>
      <div style="color:#64748B; margin:0; font-size:0.9rem; max-width:500px; margin:0 auto 16px; text-align:left; white-space:pre-line; line-height:1.6;"><?php echo html_escape($reason); ?></div>
      <a href="<?php echo site_url('student/dashboard'); ?>" class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.8rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to Dashboard
      </a>
    </div>
  <?php else: ?>
    <div class="card">
      <div style="padding:24px;">
        <div style="margin-bottom:24px;">
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">Choose Your Section</h3>
          <p class="text-faint" style="margin:0; font-size:0.85rem;">Available sections for <strong><?php echo html_escape($target_semester ? $target_semester->name : 'the upcoming semester'); ?></strong> in your program.</p>
          <?php if (isset($target_semester) && $target_semester && !empty($target_semester->enrollment_deadline)): ?>
            <?php $deadline = new DateTime($target_semester->enrollment_deadline); $now = new DateTime(); $days_left = (int) $now->diff($deadline)->format('%r%a'); ?>
            <div style="margin-top:12px; padding:10px 14px; border-radius:8px; font-size:0.82rem; display:flex; align-items:center; gap:8px; background:<?php echo $days_left <= 3 ? '#FEF2F2' : '#F0FDF4'; ?>; border:1px solid <?php echo $days_left <= 3 ? '#FECACA' : '#BBF7D0'; ?>; color:<?php echo $days_left <= 3 ? '#B91C1C' : '#166534'; ?>;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>Enrollment deadline: <strong><?php echo $deadline->format('F j, Y'); ?></strong> (<?php echo $days_left > 0 ? $days_left . ' day(s) remaining' : 'Today is the last day'; ?>)</span>
            </div>
          <?php endif; ?>
        </div>
        <?php if (empty($sections)): ?>
          <div style="padding:16px; background:#FAFAFA; border-radius:8px; color:#94A3B8; font-size:0.85rem; text-align:center;">No sections available for your program in this semester.</div>
        <?php else: ?>
          <?php echo form_open('student/enroll_next_semester'); ?>
            <div class="form-group" style="margin-bottom:16px;">
              <label class="form-label" for="section_id">Select Section <span style="color:#EF4444;">*</span></label>
              <select class="form-input" id="section_id" name="section_id" required>
                <option value="">Select a section...</option>
                <?php foreach ($sections as $s): ?>
                  <option value="<?php echo (int) $s->id; ?>"><?php echo html_escape($s->name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="display:flex; gap:12px; margin-top:24px;">
              <a href="<?php echo site_url('student/dashboard'); ?>" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">Cancel</a>
              <button type="submit" class="btn-primary" style="flex:1;">Confirm Enrollment</button>
            </div>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>