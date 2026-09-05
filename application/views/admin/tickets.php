<?php
$tickets = isset($tickets) ? $tickets : array();
$filters = isset($filters) ? $filters : array('status' => '', 'category' => '', 'from_role' => '', 'search' => '');

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');

$categories = array('Technical Issue', 'Grade Concern', 'Account Issue', 'Missing Activity', 'Other');
$statuses   = array('Open', 'In Progress', 'Resolved');
$roles      = array('student' => 'From Students', 'teacher' => 'From Instructors');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Support Tickets</h2>
      <p class="page-subtitle">Manage support tickets submitted to Admin</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('admin/tickets'); ?>">
    <div class="list-toolbar">
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
        <input class="form-input" placeholder="Search tickets..." aria-label="Search tickets" name="search" value="<?php echo html_escape($filters['search']); ?>">
      </div>
      <select class="form-input filter-select" name="status" onchange="this.form.submit()" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?php echo html_escape($s); ?>" <?php echo $filters['status'] === $s ? 'selected' : ''; ?>><?php echo html_escape($s); ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-input filter-select" name="category" onchange="this.form.submit()" aria-label="Filter by category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo html_escape($c); ?>" <?php echo $filters['category'] === $c ? 'selected' : ''; ?>><?php echo html_escape($c); ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-input filter-select" name="from_role" onchange="this.form.submit()" aria-label="Filter by role">
        <option value="">All Roles</option>
        <?php foreach ($roles as $k => $v): ?>
          <option value="<?php echo html_escape($k); ?>" <?php echo $filters['from_role'] === $k ? 'selected' : ''; ?>><?php echo html_escape($v); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn-primary" type="submit" style="padding:8px 14px; font-size:0.8rem;">Search</button>
      <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['category']) || !empty($filters['from_role'])): ?>
        <a href="<?php echo site_url('admin/tickets'); ?>" class="btn-secondary" style="padding:8px 14px; font-size:0.8rem; text-decoration:none;">Clear</a>
      <?php endif; ?>
    </div>
    </form>
  </div>

  <div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Subject</th><th>Category</th><th>Submitted By</th><th>Status</th><th>Created</th><th>Last Update</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($tickets)): ?>
            <tr class="table-row"><td colspan="7" style="text-align:center; color:#94A3B8; padding:24px;">No tickets match your filter.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <?php
                $status_class = 'badge-neutral';
                if ($t->status === 'Open') $status_class = 'badge-success';
                elseif ($t->status === 'In Progress') $status_class = 'badge';
              ?>
              <tr class="table-row">
                <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($t->subject); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($t->category); ?></td>
                <td style="color:#64748B;font-size:0.85rem;">
                  <?php echo html_escape($t->first_name . ' ' . $t->last_name); ?>
                  <div style="font-size:0.7rem; color:#94A3B8;"><?php echo html_escape($t->email); ?></div>
                  <div style="font-size:0.65rem; color:#94A3B8;"><?php echo html_escape($t->role); ?></div>
                </td>
                <td><span class="badge <?php echo $status_class; ?>" style="font-size:0.75rem;"><?php echo html_escape($t->status); ?></span></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->created_at)); ?></td>
                <td style="color:#64748B;font-size:0.85rem;"><?php echo date('M j, Y', strtotime($t->updated_at)); ?></td>
                <td>
                  <a href="<?php echo site_url('admin/ticket_view/' . (int) $t->id); ?>" class="btn-secondary" style="font-size:0.78rem;padding:5px 10px;">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>