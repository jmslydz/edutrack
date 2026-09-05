<?php
$requests = isset($requests) ? $requests : array();
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Grade Correction Requests</h2>
      <p class="page-subtitle">Review and approve/deny teacher grade correction requests</p>
    </div>
  </div>

  <?php if (empty($requests)): ?>
    <div class="card" style="padding:48px; text-align:center; background:#FAFAFA; border:1px solid #F1F5F9; border-radius:12px;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">No pending requests</h3>
      <p style="color:#64748B; margin:0 0 16px; font-size:0.9rem;">All correction requests have been reviewed.</p>
    </div>
  <?php else: ?>
    <div class="card" style="overflow:hidden;">
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:200px;">Student</th>
              <th style="width:150px;">Subject</th>
              <th style="width:120px;">Period</th>
              <th style="width:80px;">Current</th>
              <th style="width:80px;">Requested</th>
              <th style="width:200px;">Reason</th>
              <th style="width:120px;">Teacher</th>
              <th style="width:140px;">Submitted</th>
              <th style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $r): ?>
              <?php
                $status_class = 'badge-neutral';
                if ($r->status === 'pending') $status_class = 'badge';
              ?>
              <tr class="table-row">
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;">
                  <?php echo html_escape($r->student_name); ?>
                  <div style="font-size:0.7rem; color:#94A3B8;"><?php echo html_escape($r->student_no); ?></div>
                </td>
                <td style="color:#64748B;font-size:0.85rem;">
                  <?php echo html_escape($r->subject_code . ' — ' . $r->subject_title); ?>
                </td>
                <td style="color:#64748B;font-size:0.85rem;">
                  <?php echo html_escape($r->period_name); ?>
                </td>
                <td style="color:#64748B;font-size:0.85rem;">
                  <?php echo $r->old_value !== NULL ? number_format((float) $r->old_value, 2) : '<span style="color:#CBD5E1;">—</span>'; ?>
                </td>
                <td style="color:#64748B;font-size:0.85rem;">
                  <span style="font-weight:600;color:#0D9488;"><?php echo number_format((float) $r->requested_value, 2); ?></span>
                </td>
                <td style="color:#374151;font-size:0.75rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo html_escape($r->reason); ?>">
                  <?php echo html_escape($r->reason); ?>
                </td>
                <td style="color:#64748B;font-size:0.8rem;">
                  <?php echo html_escape($r->teacher_name); ?>
                </td>
                <td style="color:#64748B;font-size:0.8rem;">
                  <?php echo date('M j, Y g:i A', strtotime($r->created_at)); ?>
                </td>
                <td style="text-align:center;">
                  <a href="<?php echo site_url('admin/correction_requests/' . (int) $r->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">Review</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>