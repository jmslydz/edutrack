<?php
$request = isset($request) ? $request : NULL;
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Review Correction Request</h2>
      <p class="page-subtitle"><?php echo html_escape($request->subject_code . ' — ' . $request->subject_title . ' (' . $request->period_name . ')'); ?></p>
    </div>
    <a href="<?php echo site_url('admin/correction_requests'); ?>" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.8rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Queue
    </a>
  </div>

  <?php if ($this->session->flashdata('admin_success')): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($this->session->flashdata('admin_success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('admin_error')): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($this->session->flashdata('admin_error')); ?></div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:24px;">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9;">
      <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <span class="badge" style="background:#F0F9FF;color:#0D9488;font-size:0.75rem;"><?php echo html_escape($request->subject_code); ?></span>
        <span class="badge <?php echo $request->status === 'pending' ? 'badge' : ($request->status === 'approved' ? 'badge-success' : 'badge-danger'); ?>" style="font-size:0.75rem;"><?php echo ucfirst(html_escape($request->status)); ?></span>
        <span class="text-faint" style="font-size:0.75rem;">Submitted <?php echo date('M j, Y g:i A', strtotime($request->created_at)); ?></span>
        <span class="text-faint" style="font-size:0.75rem;"><?php echo html_escape($request->semester_name . ' — ' . $request->year_label); ?></span>
      </div>
      <div style="margin-bottom:12px;">
        <strong style="color:#1E293B;">Student:</strong> <?php echo html_escape($request->student_name); ?>
        <span style="color:#64748B; font-weight:normal; margin-left:8px;"><?php echo html_escape($request->student_no); ?></span>
      </div>
      <div style="margin-bottom:12px;">
        <strong style="color:#1E293B;">Teacher:</strong> <?php echo html_escape($request->teacher_name); ?>
      </div>
      <p style="margin:12px 0 0; color:#334155; font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo html_escape($request->reason); ?></p>
    </div>
  </div>

  <div class="card" style="margin-bottom:24px;">
    <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9; display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em;">Current Grade</span>
        <span style="font-family:var(--font-display); font-weight:700; font-size:1.5rem; color:<?php echo ($request->old_value !== NULL && (float)$request->old_value <= 3.0) ? '#16A34A' : (($request->old_value !== NULL && (float)$request->old_value > 3.0) ? '#EF4444' : '#CBD5E1'); ?>">
          <?php echo $request->old_value !== NULL ? number_format((float) $request->old_value, 2) : '—'; ?>
        </span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em;">Requested Grade</span>
        <span style="font-family:var(--font-display); font-weight:700; font-size:1.5rem; color:#0D9488;">
          <?php echo number_format((float) $request->requested_value, 2); ?>
        </span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em;">Change</span>
        <?php if ($request->old_value !== NULL): ?>
          <span style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:#0D9488;">
            <?php echo number_format((float) $request->requested_value - (float) $request->old_value, 2); ?>
          </span>
        <?php else: ?>
          <span style="color:#94A3B8; font-size:0.85rem;">New grade</span>
        <?php endif; ?>
      </div>
    </div>
    <div style="padding:24px;">
      <div style="margin-bottom:16px; padding:12px; background:#FAFAFA; border-radius:8px; font-size:0.85rem; color:#374151; line-height:1.6; white-space:pre-wrap;">
        <strong style="color:#1E293B;">Reason:</strong> <?php echo html_escape($request->reason); ?>
      </div>
      <?php if ($request->admin_notes): ?>
        <div style="margin-bottom:16px; padding:12px; background:#FEF3C7; border-radius:8px; font-size:0.85rem; color:#92400E; line-height:1.6;">
          <strong style="color:#92400E;">Admin Notes:</strong> <?php echo html_escape($request->admin_notes); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($request->status === 'pending'): ?>
    <div class="card" style="margin-bottom:24px;">
      <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Take Action</h3>
      </div>
      <?php echo form_open('admin/correction_requests/' . (int) $request->id, array('novalidate' => 'novalidate')); ?>
        <input type="hidden" name="action" value="approve">
        <div style="padding:24px;">
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label" for="admin_notes">Admin Notes (optional)</label>
            <textarea class="form-input" id="admin_notes" name="admin_notes" rows="3" placeholder="Optional: Add notes for the teacher (e.g., why approved/denied)..."></textarea>
            <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Notes will be visible to the teacher in the notification.</div>
          </div>
          <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="submit" name="action" value="deny" class="btn-secondary" style="flex:1; max-width:180px;" onclick="return confirm('Deny this correction request? The grade will remain unchanged.');">Deny</button>
            <button type="submit" name="action" value="approve" class="btn-primary" style="flex:1; max-width:180px;" onclick="return confirm('Approve this correction? The grade will be updated immediately.');">Approve & Update Grade</button>
          </div>
        </div>
      <?php echo form_close(); ?>
    </div>
  <?php elseif ($request->status === 'approved'): ?>
    <div class="card" style="margin-bottom:24px; background:#F0FDFA; border:1px solid #99F6E4;">
      <div style="padding:24px; display:flex; align-items:center; gap:12px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0F766E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 0-5.94-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#0F766E; margin:0 0 4px;">Request Approved</h3>
          <p style="color:#0F766E; margin:0; font-size:0.85rem;">This request was approved by <?php echo html_escape($request->reviewer_name ?? 'an admin'); ?> on <?php echo date('M j, Y g:i A', strtotime($request->reviewed_at)); ?>.</p>
        </div>
      </div>
    </div>
  <?php elseif ($request->status === 'denied'): ?>
    <div class="card" style="margin-bottom:24px; background:#FEF2F2; border:1px solid #FCA5A5;">
      <div style="padding:24px; display:flex; align-items:center; gap:12px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div>
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#B91C1C; margin:0 0 4px;">Request Denied</h3>
          <p style="color:#B91C1C; margin:0; font-size:0.85rem;">This request was denied by <?php echo html_escape($request->reviewer_name ?? 'an admin'); ?> on <?php echo date('M j, Y g:i A', strtotime($request->reviewed_at)); ?>.</p>
        </div>
      </div>
      <?php if ($request->admin_notes): ?>
        <div style="margin-top:16px; padding:16px; background:#FEF2F2; border-radius:8px; font-size:0.85rem; color:#991B1B; line-height:1.6;">
          <strong style="color:#991B1B;">Admin Notes:</strong> <?php echo html_escape($request->admin_notes); ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>