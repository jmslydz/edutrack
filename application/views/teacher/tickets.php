<?php
$admin_tickets  = isset($admin_tickets)  ? $admin_tickets  : array();
$from_students  = isset($from_students)  ? $from_students  : array();
$to_students    = isset($to_students)    ? $to_students    : array();
$counts         = isset($counts)         ? $counts         : array('Open' => 0, 'In Progress' => 0, 'Resolved' => 0);

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');

function _status_badge($status) {
    $cls = 'badge-neutral';
    if ($status === 'Open')        $cls = 'badge-success';
    elseif ($status === 'In Progress') $cls = 'badge';
    return '<span class="badge ' . $cls . '" style="font-size:0.75rem;">' . html_escape($status) . '</span>';
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Tickets &amp; Messages</h2>
      <p class="page-subtitle">Your support tickets to Admin, messages from students, and messages you sent to students</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="<?php echo site_url('teacher/ticket_submit'); ?>" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.82rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Ticket to Admin
      </a>
      <a href="<?php echo site_url('teacher/message_student'); ?>" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.82rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
        Message a Student
      </a>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <!-- ================================================================
       SECTION 1 — My Tickets to Admin
       ================================================================ -->
  <div style="margin-bottom:8px; display:flex; align-items:center; gap:10px;">
    <h3 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0;">My Tickets to Admin</h3>
    <div style="display:flex; gap:8px;">
      <span class="badge badge-success" style="font-size:0.75rem;">Open: <?php echo (int) $counts['Open']; ?></span>
      <span class="badge" style="background:#FFF7ED;color:#F97316;font-size:0.75rem;">In Progress: <?php echo (int) $counts['In Progress']; ?></span>
      <span class="badge badge-neutral" style="font-size:0.75rem;">Resolved: <?php echo (int) $counts['Resolved']; ?></span>
    </div>
  </div>
  <div class="card" style="margin-bottom:28px;">
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Subject</th><th>Category</th><th>Status</th><th>Created</th><th>Last Update</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($admin_tickets)): ?>
            <tr class="table-row">
              <td colspan="6" style="text-align:center; color:#94A3B8; padding:24px;">
                No tickets submitted to Admin yet.
                <a href="<?php echo site_url('teacher/ticket_submit'); ?>" style="color:#0D9488;">Submit one</a>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($admin_tickets as $t): ?>
              <tr class="table-row">
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($t->subject); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($t->category); ?></td>
                <td><?php echo _status_badge($t->status); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->created_at)); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->updated_at)); ?></td>
                <td><a href="<?php echo site_url('teacher/ticket_view/' . (int) $t->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">View</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================================================================
       SECTION 2 — Messages from Students (student → this teacher)
       ================================================================ -->
  <h3 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 8px;">
    Messages from Students
    <?php if ( ! empty($from_students)): ?>
      <span class="badge" style="background:#FDF4FF;color:#9333EA;font-size:0.7rem;margin-left:6px;"><?php echo count($from_students); ?></span>
    <?php endif; ?>
  </h3>
  <div class="card" style="margin-bottom:28px;">
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>From</th><th>Subject</th><th>Category</th><th>Status</th><th>Created</th><th>Last Update</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($from_students)): ?>
            <tr class="table-row">
              <td colspan="7" style="text-align:center; color:#94A3B8; padding:24px;">No messages from students yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($from_students as $t): ?>
              <tr class="table-row">
                <td style="font-size:0.85rem;color:#1E293B;font-weight:600;"><?php echo html_escape($t->submitter_first_name . ' ' . $t->submitter_last_name); ?></td>
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($t->subject); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($t->category); ?></td>
                <td><?php echo _status_badge($t->status); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->created_at)); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->updated_at)); ?></td>
                <td><a href="<?php echo site_url('teacher/ticket_view/' . (int) $t->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">View</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================================================================
       SECTION 3 — Messages I Sent to Students (this teacher → student)
       ================================================================ -->
  <h3 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 8px;">Messages I Sent to Students</h3>
  <div class="card">
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>To</th><th>Subject</th><th>Category</th><th>Status</th><th>Created</th><th>Last Update</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($to_students)): ?>
            <tr class="table-row">
              <td colspan="7" style="text-align:center; color:#94A3B8; padding:24px;">
                No messages sent to students yet.
                <a href="<?php echo site_url('teacher/message_student'); ?>" style="color:#0D9488;">Send one</a>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($to_students as $t): ?>
              <tr class="table-row">
                <td style="font-size:0.85rem;color:#1E293B;font-weight:600;"><?php echo html_escape($t->recipient_first_name . ' ' . $t->recipient_last_name); ?></td>
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($t->subject); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($t->category); ?></td>
                <td><?php echo _status_badge($t->status); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->created_at)); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->updated_at)); ?></td>
                <td><a href="<?php echo site_url('teacher/ticket_view/' . (int) $t->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">View</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
