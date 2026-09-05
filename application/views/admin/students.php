<?php
$rows          = isset($rows) ? $rows : array();
$sections      = isset($sections) ? $sections : array();
$search        = isset($search) ? $search : '';
$filter_section = isset($filter_section) ? (int) $filter_section : 0;
$filtered_total = isset($filtered_total) ? (int) $filtered_total : 0;

function _student_initials($u)
{
	return mb_strtoupper(mb_substr(trim($u->first_name), 0, 1) . mb_substr(trim($u->last_name), 0, 1));
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Students</h2>
      <p class="page-subtitle">Enrolled student accounts by section</p>
    </div>
  </div>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('admin/students'); ?>">
    <div class="list-toolbar">
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
        <input class="form-input" placeholder="Search students..." aria-label="Search students" name="search" value="<?php echo html_escape($search); ?>">
      </div>
      <select class="form-input filter-select" name="section" onchange="this.form.submit()" aria-label="Filter by section">
        <option value="">All Sections</option>
        <?php foreach ($sections as $sec): ?>
          <option value="<?php echo (int) $sec->id; ?>" <?php echo $filter_section === (int) $sec->id ? 'selected' : ''; ?>><?php echo html_escape($sec->name); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    </form>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Student No.</th><th>Name</th><th>Section</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="table-row"><td colspan="4" style="text-align:center; color:#94A3B8; padding:24px;">No students match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $u): ?>
        <tr class="table-row">
          <td><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($u->student_no); ?></span></td>
          <td><div style="display:flex; align-items:center; gap:10px;"><div style="width:34px;height:34px;border-radius:10px;background:#CCFBF1;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:0.85rem;color:#0D9488;"><?php echo html_escape(_student_initials($u)); ?></div><span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($u->first_name . ' ' . $u->last_name); ?></span></div></td>
          <td><span class="badge badge-student"><?php echo html_escape($u->section_name ? $u->section_name : '—'); ?></span></td>
          <td><span class="badge <?php echo $u->status === 'active' ? 'badge-success' : 'badge-neutral'; ?>"><?php echo html_escape(ucfirst($u->status)); ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="table-footer">
      <span class="text-faint" style="font-size:0.78rem;">Showing <?php echo count($rows); ?> of <?php echo $filtered_total; ?> students</span>
      <?php echo $pagination; ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->