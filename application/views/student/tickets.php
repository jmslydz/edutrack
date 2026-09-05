<?php
$tickets = isset($tickets) ? $tickets : array();
$counts  = isset($counts) ? $counts : array('Open' => 0, 'In Progress' => 0, 'Resolved' => 0);

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">My Tickets</h2>
      <p class="page-subtitle">Tickets you submitted and messages received from instructors</p>
    </div>
    <a href="<?php echo site_url('student/ticket_submit'); ?>" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Submit a Ticket
    </a>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9; display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
      <span class="badge badge-success" style="font-size:0.8rem;">Open: <?php echo (int) $counts['Open']; ?></span>
      <span class="badge" style="background:#FFF7ED;color:#F97316;font-size:0.8rem;">In Progress: <?php echo (int) $counts['In Progress']; ?></span>
      <span class="badge badge-neutral" style="font-size:0.8rem;">Resolved: <?php echo (int) $counts['Resolved']; ?></span>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Direction</th>
            <th>Subject</th>
            <th>Category</th>
            <th>Recipient / Sender</th>
            <th>Status</th>
            <th>Created</th>
            <th>Last Update</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tickets)): ?>
            <tr class="table-row">
              <td colspan="8" style="text-align:center; color:#94A3B8; padding:24px;">
                No tickets yet. <a href="<?php echo site_url('student/ticket_submit'); ?>" style="color:#0D9488;">Submit one</a>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <?php
                $is_sent = (isset($t->direction) && $t->direction === 'sent')
                           || ($t->submitted_by == $this->session->userdata('user_id'));

                $status_class = 'badge-neutral';
                if ($t->status === 'Open')        $status_class = 'badge-success';
                elseif ($t->status === 'In Progress') $status_class = 'badge';

                if ($is_sent)
                {
                    $dir_badge = '<span class="badge" style="background:#EFF6FF;color:#2563EB;font-size:0.7rem;white-space:nowrap;">You &#8594;</span>';
                    if ($t->recipient_type === 'admin')
                    {
                        $party = '<span style="font-size:0.82rem;color:#64748B;">Admin</span>';
                    }
                    else
                    {
                        $rname = html_escape(trim($t->recipient_first_name . ' ' . $t->recipient_last_name));
                        $party = '<span style="font-size:0.82rem;color:#64748B;">' . ($rname !== '' ? $rname : 'Instructor') . '</span>';
                    }
                }
                else
                {
                    $dir_badge = '<span class="badge" style="background:#FDF4FF;color:#9333EA;font-size:0.7rem;white-space:nowrap;">&#8594; You</span>';
                    $sname = html_escape(trim($t->submitter_first_name . ' ' . $t->submitter_last_name));
                    $party = '<span style="font-size:0.82rem;color:#64748B;">' . ($sname !== '' ? $sname : 'Instructor') . '</span>';
                }
              ?>
              <tr class="table-row">
                <td><?php echo $dir_badge; ?></td>
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($t->subject); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($t->category); ?></td>
                <td><?php echo $party; ?></td>
                <td><span class="badge <?php echo $status_class; ?>" style="font-size:0.75rem;"><?php echo html_escape($t->status); ?></span></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->created_at)); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->updated_at)); ?></td>
                <td>
                  <a href="<?php echo site_url('student/ticket_view/' . (int) $t->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
