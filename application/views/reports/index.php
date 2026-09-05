<?php
$ctx = isset($ctx) ? $ctx : array(
	'role' => 'admin', 'semester_id' => 0,
	'section_id' => NULL, 'section_options' => array(), 'allow_all' => TRUE,
	'report_type' => 'grade_summary', 'empty' => TRUE,
);
$rows           = isset($rows) ? $rows : array();
$tiles          = isset($tiles) ? $tiles : array('total' => 0, 'passed' => 0, 'failed' => 0, 'honors' => 0, 'class_gwa' => NULL);
$semesters      = isset($semesters) ? $semesters : array();
$section_options = isset($section_options) ? $section_options : array();
$report_title   = isset($report_title) ? $report_title : 'Report';
$generated_at   = isset($generated_at) ? $generated_at : date('F j, Y');

$report_types = array(
	'grade_summary'       => 'Grade Summary',
	'honor_roll'          => 'Honor Roll',
	'subject_performance' => 'Subject Performance',
);

$export_query = array('report_type' => $ctx['report_type']);
if ((int) $ctx['semester_id'])    $export_query['semester']    = (int) $ctx['semester_id'];
if ($ctx['section_id'] !== NULL)  $export_query['section']     = (int) $ctx['section_id'];
$export_query_str = http_build_query($export_query);

function _honor_badge($honor)
{
	if ($honor === NULL) return '<span style="color:#CBD5E1;">—</span>';
	$map = array(
		'With Highest Honors' => 'background:#FAF5FF;color:#7C3AED;',
		'With High Honors'    => 'background:#FFF7ED;color:#F97316;',
		'With Honors'         => 'background:#FFF7ED;color:#F97316;',
	);
	$style = isset($map[$honor]) ? $map[$honor] : 'background:#FFF7ED;color:#F97316;';
	return '<span class="badge" style="' . $style . '">' . html_escape($honor) . '</span>';
}
function _gwa_color($gwa)
{
	if ($gwa === NULL) return '#CBD5E1';
	$n = (float) $gwa;
	if ($n <= 1.5) return '#16A34A';
	if ($n <= 2.5) return '#0891B2';
	if ($n <= 3.0) return '#F97316';
	return '#EF4444';
}
function _status_badge($status)
{
	if ($status === 'Passed') return '<span class="badge badge-success">Passed</span>';
	return '<span class="badge" style="background:#FEF2F2;color:#EF4444;">Failed</span>';
}
?>
<!-- BEGIN PAGE CONTENT (everything below belongs inside .content-area) -->
  <?php $flash_error = $this->session->flashdata('admin_error') ?: $this->session->flashdata('grade_error'); ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h2 class="page-title">Academic Reports</h2>
      <p class="page-subtitle">Generate and export student academic performance reports</p>
    </div>
    <div style="display:flex; gap:10px;">
      <a href="<?php echo site_url('reports/export_pdf?' . $export_query_str); ?>" style="display:flex; align-items:center; gap:6px; background:#EF4444; color:white; border:none; border-radius:10px; padding:9px 16px; cursor:pointer; font-size:0.85rem; font-weight:600; text-decoration:none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export PDF
      </a>
      <a href="<?php echo site_url('reports/export_csv?' . $export_query_str); ?>" style="display:flex; align-items:center; gap:6px; background:#16A34A; color:white; border:none; border-radius:10px; padding:9px 16px; cursor:pointer; font-size:0.85rem; font-weight:600; text-decoration:none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
      </a>
    </div>
  </div>

  <div class="card filter-card">
    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Filter Report</div>
    <form method="get" action="<?php echo site_url('reports/index'); ?>">
    <div class="filter-grid" style="grid-template-columns:repeat(3,1fr);">
      <div>
        <label class="form-label">Semester</label>
        <select class="form-input" name="semester" onchange="this.form.submit()">
          <?php foreach ($semesters as $sem): ?>
            <option value="<?php echo (int) $sem->id; ?>" <?php echo (int) $sem->id === (int) $ctx['semester_id'] ? 'selected' : ''; ?>><?php echo html_escape($sem->year_label . ' — ' . $sem->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Section</label>
        <select class="form-input" name="section">
          <?php if ($ctx['allow_all']): ?>
            <option value="">All Sections</option>
          <?php endif; ?>
          <?php foreach ($section_options as $sec_id => $sec_name): ?>
            <option value="<?php echo (int) $sec_id; ?>" <?php echo $ctx['section_id'] !== NULL && (int) $ctx['section_id'] === (int) $sec_id ? 'selected' : ''; ?>><?php echo html_escape($sec_name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Report Type</label>
        <select class="form-input" name="report_type">
          <?php foreach ($report_types as $rtype => $rlabel): ?>
            <option value="<?php echo html_escape($rtype); ?>" <?php echo $rtype === $ctx['report_type'] ? 'selected' : ''; ?>><?php echo html_escape($rlabel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:16px; display:flex; justify-content:flex-end;">
      <button class="btn-primary" style="border-radius:10px;" type="submit">Generate Report</button>
    </div>
    </form>
  </div>

  <?php if ($ctx['empty']): ?>
    <div class="card" style="padding:40px; text-align:center;">
      <p class="text-faint" style="margin:0;">No report data is available for the selected filters.</p>
    </div>
  <?php else: ?>

  <div class="tile-strip tile-strip--5">
    <div class="tile" style="background:#F0FDFA; border-color:#0D948822;"><div class="tile-value" style="color:#0D9488;"><?php echo (int) $tiles['total']; ?></div><div class="tile-label">Total Students</div></div>
    <div class="tile" style="background:#DCFCE7; border-color:#16A34A22;"><div class="tile-value" style="color:#16A34A;"><?php echo (int) $tiles['passed']; ?></div><div class="tile-label">Passed</div></div>
    <div class="tile" style="background:#FEF2F2; border-color:#EF444422;"><div class="tile-value" style="color:#EF4444;"><?php echo (int) $tiles['failed']; ?></div><div class="tile-label">Failed</div></div>
    <div class="tile" style="background:#FFF7ED; border-color:#F9731622;"><div class="tile-value" style="color:#F97316;"><?php echo (int) $tiles['honors']; ?></div><div class="tile-label">With Honors</div></div>
    <div class="tile" style="background:#F0F9FF; border-color:#0891B222;"><div class="tile-value" style="color:#0891B2;"><?php echo $tiles['class_gwa'] !== NULL ? number_format((float) $tiles['class_gwa'], 4) : '—'; ?></div><div class="tile-label">Class GWA</div></div>
  </div>

  <div class="card" style="overflow:hidden;">
    <div class="report-print-header">
      <div class="org">EduTrack Academic Records System</div>
      <div class="title"><?php echo html_escape($report_title); ?></div>
      <div class="date">Generated: <?php echo html_escape($generated_at); ?></div>
    </div>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <?php if ($ctx['report_type'] === 'subject_performance'): ?>
      <thead><tr><th>Subject</th><th>Section</th><th>Instructor</th><th>Units</th><th>Students Graded</th><th>Average</th><th>Passed</th><th>Failed</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr class="table-row">
          <td><span style="font-weight:600;color:#1E293B;"><?php echo html_escape($r->subject_code); ?></span> <span style="color:#64748B;font-size:0.82rem;"><?php echo html_escape($r->subject_title); ?></span></td>
          <td><?php echo html_escape($r->section); ?></td>
          <td style="color:#64748B;font-size:0.82rem;"><?php echo html_escape($r->instructor); ?></td>
          <td style="color:#64748B;"><?php echo (int) $r->units; ?></td>
          <td style="color:#64748B;"><?php echo (int) $r->students; ?></td>
          <td><span style="font-family:var(--font-display);font-weight:800;font-size:0.95rem;color:<?php echo _gwa_color($r->average); ?>;"><?php echo $r->average !== NULL ? number_format((float) $r->average, 2) : '—'; ?></span></td>
          <td style="color:#16A34A;font-weight:600;"><?php echo (int) $r->passed; ?></td>
          <td style="color:#EF4444;font-weight:600;"><?php echo (int) $r->failed; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php else: ?>
      <thead><tr><th>Student No.</th><th>Full Name</th><th>Section</th><th>GWA</th><th>Units</th><th>Status</th><th>Latin Honor</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr class="table-row">
          <td style="font-family:monospace;font-size:0.8rem;color:#64748B;"><?php echo html_escape($r->student_no); ?></td>
          <td style="font-weight:600;color:#1E293B;"><?php echo html_escape($r->name); ?></td>
          <td><?php echo html_escape($r->section); ?></td>
          <td><span style="font-family:var(--font-display);font-weight:800;font-size:1rem;color:<?php echo _gwa_color($r->gwa); ?>;"><?php echo $r->gwa !== NULL ? number_format((float) $r->gwa, 2) : '—'; ?></span></td>
          <td style="color:#64748B;"><?php echo (int) $r->units; ?></td>
          <td><?php echo _status_badge($r->status); ?></td>
          <td><?php echo _honor_badge($r->honor); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php endif; ?>
    </table>
    </div>

    <div class="table-footer">
      <span class="text-faint" style="font-size:0.78rem;">Showing <?php echo count($rows); ?> of <?php echo (int) $tiles['total']; ?> records</span>
      <div style="display:flex; gap:10px;">
        <a href="<?php echo site_url('reports/export_pdf?' . $export_query_str); ?>" style="display:flex; align-items:center; gap:6px; background:#EF4444; color:white; border:none; border-radius:8px; padding:7px 14px; cursor:pointer; font-size:0.8rem; font-weight:600; text-decoration:none;">PDF</a>
        <a href="<?php echo site_url('reports/export_csv?' . $export_query_str); ?>" style="display:flex; align-items:center; gap:6px; background:#16A34A; color:white; border:none; border-radius:8px; padding:7px 14px; cursor:pointer; font-size:0.8rem; font-weight:600; text-decoration:none;">CSV</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
<!-- END PAGE CONTENT -->