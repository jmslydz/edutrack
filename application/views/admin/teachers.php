<?php
$rows          = isset($rows) ? $rows : array();
$assign_counts = isset($assign_counts) ? $assign_counts : array();
$search        = isset($search) ? $search : '';
$filtered_total = isset($filtered_total) ? (int) $filtered_total : 0;

function _teacher_initials($u)
{
	return mb_strtoupper(mb_substr(trim($u->first_name), 0, 1) . mb_substr(trim($u->last_name), 0, 1));
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Teachers</h2>
      <p class="page-subtitle">Faculty accounts and current teaching loads</p>
    </div>
  </div>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('admin/teachers'); ?>">
    <div class="list-toolbar">
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
        <input class="form-input" placeholder="Search teachers..." aria-label="Search teachers" name="search" value="<?php echo html_escape($search); ?>">
      </div>
    </div>
    </form>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Email</th><th>Classes (Active Term)</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="table-row"><td colspan="4" style="text-align:center; color:#94A3B8; padding:24px;">No teachers match your search.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $u): ?>
        <tr class="table-row">
          <td><div style="display:flex; align-items:center; gap:10px;"><div style="width:34px;height:34px;border-radius:10px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:0.85rem;color:#2563EB;"><?php echo html_escape(_teacher_initials($u)); ?></div><span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($u->first_name . ' ' . $u->last_name); ?></span></div></td>
          <td style="color:#64748B;font-size:0.85rem;"><?php echo html_escape($u->email); ?></td>
          <td><span class="badge badge-teacher"><?php echo isset($assign_counts[$u->id]) ? (int) $assign_counts[$u->id] : 0; ?> assigned</span></td>
          <td><span class="badge <?php echo $u->status === 'active' ? 'badge-success' : 'badge-neutral'; ?>"><?php echo html_escape(ucfirst($u->status)); ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="table-footer">
      <span class="text-faint" style="font-size:0.78rem;">Showing <?php echo count($rows); ?> of <?php echo $filtered_total; ?> teachers</span>
      <?php echo $pagination; ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->