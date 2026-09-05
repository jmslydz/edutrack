<?php
$entries        = isset($entries) ? $entries : array();
$teachers       = isset($teachers) ? $teachers : array();
$filters        = isset($filters) ? $filters : array('teacher' => '', 'from' => '', 'to' => '');
$filtered_total = isset($filtered_total) ? (int) $filtered_total : 0;

$flash_error = $this->session->flashdata('admin_error');

function _audit_value($v)
{
	if ($v === NULL || $v === '')
	{
		return '<span class="text-faint">—</span>';
	}
	return number_format((float) $v, 2, '.', '');
}
function _audit_datetime($dt)
{
	if (empty($dt)) return '—';
	$ts = strtotime($dt);
	return $ts ? date('M j, Y g:i A', $ts) : html_escape($dt);
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Activity Log</h2>
      <p class="page-subtitle">Complete audit trail of every grade change</p>
    </div>
  </div>

  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('admin/activity_log'); ?>">
    <div class="list-toolbar" style="flex-wrap:wrap; gap:10px;">
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
        <input class="form-input" type="date" name="from" aria-label="From date" value="<?php echo html_escape($filters['from']); ?>">
      </div>
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
        <input class="form-input" type="date" name="to" aria-label="To date" value="<?php echo html_escape($filters['to']); ?>">
      </div>
      <select class="form-input filter-select" name="teacher" aria-label="Filter by teacher">
        <option value="">All Teachers</option>
        <?php foreach ($teachers as $t): ?>
          <option value="<?php echo (int) $t->id; ?>" <?php echo (int) $filters['teacher'] === (int) $t->id ? 'selected' : ''; ?>><?php echo html_escape($t->first_name . ' ' . $t->last_name); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-secondary" style="border-radius:10px; cursor:pointer;">Filter</button>
    </div>
    </form>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Date/Time</th><th>Changed By</th><th>Student</th><th>Subject</th><th>Grading Period</th><th>Old → New</th></tr></thead>
      <tbody>
        <?php if (empty($entries)): ?>
          <tr class="table-row"><td colspan="6" style="text-align:center; color:#94A3B8; padding:24px;">No audit entries match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($entries as $e): ?>
        <tr class="table-row">
          <td style="color:#64748B;font-size:0.8rem; white-space:nowrap;"><?php echo _audit_datetime($e->changed_at); ?></td>
          <td><span style="font-weight:600;color:#2563EB;font-size:0.85rem;"><?php echo html_escape($e->teacher_name); ?></span></td>
          <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($e->student_name); ?></td>
          <td>
            <span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($e->subject_code); ?></span>
          </td>
          <td><span class="badge badge-neutral"><?php echo html_escape($e->period_name); ?></span></td>
          <td>
            <span class="text-faint" style="text-decoration:line-through; font-size:0.8rem;"><?php echo _audit_value($e->old_value); ?></span>
            <span style="color:#94A3B8; margin:0 6px;">→</span>
            <span style="font-weight:700; color:#0D9488; font-size:0.85rem;"><?php echo _audit_value($e->new_value); ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="table-footer">
      <span class="text-faint" style="font-size:0.78rem;">Showing <?php echo count($entries); ?> of <?php echo $filtered_total; ?> entries</span>
      <?php echo $pagination; ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->