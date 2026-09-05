<?php
$stats = isset($stats) ? $stats : array('students' => 0, 'teachers' => 0, 'sy_name' => '—', 'sem_name' => '—');
$recent_activity = isset($recent_activity) ? $recent_activity : array();
$progress_items = isset($progress_items) ? $progress_items : array();
$applicant_stats = isset($applicant_stats) ? $applicant_stats : array('total' => 0, 'pending_exam' => 0, 'passed_exam' => 0, 'failed_exam' => 0, 'admitted' => 0, 'rejected' => 0, 'waiting_code' => 0);

// Admissions pipeline bar colors
$app_pipeline = array(
	'pending_exam' => array('Waiting for Exam', '#F59E0B'),
	'passed_exam'  => array('Passed Exam', '#0D9488'),
	'failed_exam'  => array('Failed Exam', '#DC2626'),
	'admitted'     => array('Admitted', '#10B981'),
	'rejected'     => array('Rejected', '#64748B'),
);
$app_max = max(1, max(array($applicant_stats['pending_exam'], $applicant_stats['passed_exam'], $applicant_stats['failed_exam'], $applicant_stats['admitted'], $applicant_stats['rejected'])));
$insights = isset($insights) ? $insights : array(
	'grade_bands'    => array('1.00' => 0, '1.25 – 1.50' => 0, '1.75 – 2.00' => 0, '2.25 – 2.50' => 0, '2.75 – 3.00' => 0, '3.01 – 5.00' => 0),
	'strand_dist'    => array(),
	'rooms'          => array(),
	'room_overall'   => NULL,
	'at_risk'        => array(),
	'has_grade_data' => FALSE,
);
$band_colors = array('1.00' => '#10B981', '1.25 – 1.50' => '#0D9488', '1.75 – 2.00' => '#0891B2', '2.25 – 2.50' => '#F59E0B', '2.75 – 3.00' => '#F97316', '3.01 – 5.00' => '#DC2626');
$strand_colors = array('#0D9488', '#0891B2', '#F97316', '#7C3AED', '#F59E0B', '#64748B');

$dot_colors = array('#0D9488', '#0891B2', '#F97316', '#7C3AED', '#64748B');
function _rel_time($datetime)
{
	$diff = time() - strtotime($datetime);
	if ($diff < 60) return 'just now';
	if ($diff < 3600) return floor($diff / 60) . ' minute' . (floor($diff / 60) === 1 ? '' : 's') . ' ago';
	if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) === 1 ? '' : 's') . ' ago';
	if ($diff < 86400 * 30) return floor($diff / 86400) . ' day' . (floor($diff / 86400) === 1 ? '' : 's') . ' ago';
	return floor($diff / (86400 * 30)) . ' month' . (floor($diff / (86400 * 30)) === 1 ? '' : 's') . ' ago';
}
function _progress_color($pct)
{
	if ($pct >= 85) return '#0D9488';
	if ($pct >= 60) return '#0891B2';
	if ($pct >= 40) return '#F97316';
	return '#7C3AED';
}
?>
<!-- BEGIN PAGE CONTENT (everything below belongs inside .content-area) -->
  <div class="stat-grid stat-grid--4">
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0FDFA;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
      </div>
      <div><div class="stat-label">Total Students</div><div class="stat-value"><?php echo number_format($stats['students']); ?></div><div class="stat-change" style="color:#0D9488;">active accounts</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0F9FF;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div><div class="stat-label">Total Teachers</div><div class="stat-value"><?php echo number_format($stats['teachers']); ?></div><div class="stat-change" style="color:#0891B2;">active accounts</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FFF7ED;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      </div>
      <div><div class="stat-label">Active School Year</div><div class="stat-value"><?php echo html_escape($stats['sy_name']); ?></div><div class="stat-change" style="color:#F97316;"><?php echo html_escape($stats['sem_name']); ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FAF5FF;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
      </div>
      <div><div class="stat-label">Active Semester</div><div class="stat-value"><?php echo html_escape($stats['sem_name']); ?></div><div class="stat-change" style="color:#7C3AED;">ongoing</div></div>
    </div>
  </div>

  <div class="card" style="padding:24px; margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px;">
      <div>
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Admissions Pipeline</h3>
        <p style="font-size:0.78rem; color:#94A3B8; margin:4px 0 0;">Applicants registered through the admission portal, by current stage</p>
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
        <span class="badge" style="background:#F0FDFA; color:#0D9488;"><?php echo (int) $applicant_stats['total']; ?> total</span>
        <?php if ($applicant_stats['waiting_code'] > 0): ?>
          <span class="badge" style="background:#FFFBEB; color:#D97706;"><?php echo (int) $applicant_stats['waiting_code']; ?> need code</span>
        <?php endif; ?>
        <a href="<?php echo site_url('admin/applicants'); ?>" style="font-size:0.8rem; color:#0D9488; font-weight:600; text-decoration:none;">Manage Applicants →</a>
      </div>
    </div>

    <?php if ((int) $applicant_stats['total'] === 0): ?>
      <div class="text-faint" style="font-size:0.82rem; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:16px; text-align:center;">
        No applicants yet. Applicants register through the public "Create an account" page.
      </div>
    <?php else: ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px;">
        <?php foreach ($app_pipeline as $key => $meta): ?>
          <?php $cnt = (int) $applicant_stats[$key]; ?>
          <div style="background:#F8FAFC; border-radius:10px; padding:14px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:8px;">
              <span style="font-size:0.78rem; color:#64748B; font-weight:600;"><?php echo $meta[0]; ?></span>
              <span style="font-size:1.25rem; font-weight:800; color:<?php echo $meta[1]; ?>;"><?php echo $cnt; ?></span>
            </div>
            <div style="background:#E2E8F0; border-radius:6px; height:8px; overflow:hidden;">
              <div style="width:<?php echo round($cnt / $app_max * 100, 1); ?>%; background:<?php echo $meta[1]; ?>; border-radius:6px; height:8px;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
    <div class="card" style="padding:24px;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 16px;">Recent Activity</h3>
      <?php if (empty($recent_activity)): ?>
        <div class="text-faint" style="font-size:0.8rem;">No activity recorded yet.</div>
      <?php else: ?>
        <?php $i = 0; foreach ($recent_activity as $r): $color = $dot_colors[$i % count($dot_colors)]; $i++; ?>
        <div style="display:flex; gap:12px; align-items:flex-start; padding-bottom:14px; margin-bottom:14px; border-bottom:1px solid #F1F5F9; cursor:pointer;"
             onclick="openActivityModal('<?php echo (int) $r->id; ?>')"
             title="Click to view details">
          <div style="width:8px; height:8px; border-radius:50%; background:<?php echo $color; ?>; margin-top:6px; flex-shrink:0;"></div>
          <div><div style="font-size:0.8rem; color:#374151; font-weight:500;"><?php echo html_escape($r->teacher_name); ?> changed <?php echo html_escape($r->student_name); ?>'s grade in <?php echo html_escape($r->subject_code . ' — ' . $r->section_name . ' (' . $r->period_name . ')'); ?></div><div style="font-size:0.72rem; color:#94A3B8; margin-top:2px;"><?php echo html_escape(_rel_time($r->changed_at)); ?></div></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card" style="padding:24px;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 16px;">Tickets Report</h3>
      <div style="background:#FAFAFF; border-radius:8px; padding:16px; margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <span style="font-size:0.85rem; color:#374151; font-weight:500;">Open</span><span style="font-size:2rem; font-weight:700; color:#0D9488;"><?php echo isset($tickets_open) ? (int) $tickets_open : 0; ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="font-size:0.85rem; color:#374151; font-weight:500;">In Progress</span><span style="font-size:2rem; font-weight:700; color:#0891B2;"><?php echo isset($tickets_in_progress) ? (int) $tickets_in_progress : 0; ?></span>
        </div>
      </div>
      <a href="<?php echo site_url('admin/tickets'); ?>" style="display:inline-block; padding:8px 16px; background:#E2E8F0; border-radius:6px; font-size:0.8rem; color:#1E293B; text-decoration:none;">View All Tickets</a>
    </div>
  </div>

  <?php
  $max_band   = max($insights['grade_bands']) ?: 1;
  $max_strand = max($insights['strand_dist']) ?: 1;
  $sem_label  = isset($stats['sem_name']) && $stats['sem_name'] !== '—' ? html_escape($stats['sem_name']) : 'active semester';
  ?>
  <div style="display:flex; align-items:baseline; gap:10px; margin:28px 0 14px;">
    <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0;">Insights</h3>
    <span style="font-size:0.82rem; color:#64748B;">Live data from the <?php echo $sem_label; ?></span>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:16px;">
    <!-- Grade distribution -->
    <div class="card" style="padding:20px;">
      <h4 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 4px;">Grade Distribution</h4>
      <p style="font-size:0.78rem; color:#94A3B8; margin:0 0 16px;">Subject final grades (Midterm + Final average), 1.00–5.00 scale — <?php echo $sem_label; ?></p>
      <?php if ( ! $insights['has_grade_data']): ?>
        <div class="text-faint" style="font-size:0.82rem; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:16px; text-align:center;">No grades encoded yet for the <?php echo $sem_label; ?> — this chart fills in as teachers record grades.</div>
      <?php else: ?>
        <?php foreach ($insights['grade_bands'] as $band => $count): ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
              <span style="font-size:0.8rem; color:#475569; font-weight:600;"><?php echo html_escape($band); ?></span>
              <span style="font-size:0.8rem; color:#1E293B; font-weight:700;"><?php echo (int) $count; ?></span>
            </div>
            <div style="background:#F1F5F9; border-radius:6px; height:12px; overflow:hidden;">
              <div style="width:<?php echo round($count / $max_band * 100, 1); ?>%; background:<?php echo $band_colors[$band]; ?>; border-radius:6px; height:12px; transition:width 0.3s;"></div>
            </div>
          </div>
        <?php endforeach; ?>
        <div style="display:flex; gap:14px; flex-wrap:wrap; margin-top:14px; padding-top:12px; border-top:1px solid #F1F5F9; font-size:0.75rem; color:#64748B;">
          <span style="display:inline-flex; align-items:center; gap:6px;"><span style="width:9px; height:9px; border-radius:3px; background:#DC2626; display:inline-block;"></span> Failed</span>
          <span style="display:inline-flex; align-items:center; gap:6px;"><span style="width:9px; height:9px; border-radius:3px; background:#F97316; display:inline-block;"></span> Passing</span>
          <span style="display:inline-flex; align-items:center; gap:6px;"><span style="width:9px; height:9px; border-radius:3px; background:#0891B2; display:inline-block;"></span> Satisfactory–Good</span>
          <span style="display:inline-flex; align-items:center; gap:6px;"><span style="width:9px; height:9px; border-radius:3px; background:#10B981; display:inline-block;"></span> Very Good–Excellent</span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Enrollment by strand -->
    <div class="card" style="padding:20px;">
      <h4 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 4px;">Enrollment by Strand</h4>
      <p style="font-size:0.78rem; color:#94A3B8; margin:0 0 16px;">Students enrolled in the <?php echo $sem_label; ?> per strand</p>
      <?php if (empty($insights['strand_dist'])): ?>
        <div class="text-faint" style="font-size:0.82rem; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:16px; text-align:center;">No enrollments recorded for the <?php echo $sem_label; ?> yet.</div>
      <?php else: ?>
        <?php $si = 0; foreach ($insights['strand_dist'] as $code => $count): ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
              <span style="font-size:0.8rem; color:#475569; font-weight:600;"><?php echo html_escape($code); ?></span>
              <span style="font-size:0.8rem; color:#1E293B; font-weight:700;"><?php echo (int) $count; ?></span>
            </div>
            <div style="background:#F1F5F9; border-radius:6px; height:12px; overflow:hidden;">
              <div style="width:<?php echo round($count / $max_strand * 100, 1); ?>%; background:<?php echo $strand_colors[$si % count($strand_colors)]; ?>; border-radius:6px; height:12px;"></div>
            </div>
          </div>
          <?php $si++; ?>
        <?php endforeach; ?>
        <div style="font-size:0.78rem; color:#94A3B8; margin-top:14px; padding-top:12px; border-top:1px solid #F1F5F9;"><?php echo (int) array_sum($insights['strand_dist']); ?> students total</div>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:16px;">
    <!-- Room utilization -->
    <div class="card" style="padding:20px;">
      <h4 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 4px;">Room Utilization</h4>
      <p style="font-size:0.78rem; color:#94A3B8; margin:0 0 16px;">Booked hours vs school hours (Mon–Fri, 6 AM – 8 PM) for <?php echo $sem_label; ?></p>
      <?php if ($insights['room_overall'] === NULL): ?>
        <div class="text-faint" style="font-size:0.82rem; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:16px; text-align:center;">No rooms yet — add buildings and rooms in Rooms &amp; Buildings, then assign classes.</div>
      <?php else: ?>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:18px; background:#F8FAFC; border-radius:12px; padding:14px 16px;">
          <div style="font-size:1.9rem; font-weight:800; color:<?php echo $insights['room_overall'] >= 60 ? '#0D9488' : ($insights['room_overall'] >= 30 ? '#F97316' : '#DC2626'); ?>;"><?php echo $insights['room_overall']; ?>%</div>
          <div style="flex:1;">
            <div style="background:#E2E8F0; border-radius:8px; height:12px; overflow:hidden;">
              <div style="width:<?php echo min(100, $insights['room_overall']); ?>%; background:<?php echo $insights['room_overall'] >= 60 ? '#0D9488' : ($insights['room_overall'] >= 30 ? '#F97316' : '#DC2626'); ?>; border-radius:8px; height:12px;"></div>
            </div>
            <div style="font-size:0.75rem; color:#64748B; margin-top:6px;">across <?php echo count($insights['rooms']); ?> room<?php echo count($insights['rooms']) !== 1 ? 's' : ''; ?></div>
          </div>
        </div>
        <?php $shown = 0; foreach ($insights['rooms'] as $ru): ?>
          <?php if ($shown >= 6) { break; } $shown++; ?>
          <div style="margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
              <span style="font-size:0.8rem; color:#475569; font-weight:600;"><?php echo html_escape($ru['name']); ?><?php echo $ru['building'] ? ' <span style="color:#94A3B8; font-weight:400;">· ' . html_escape($ru['building']) . '</span>' : ''; ?><?php echo $ru['active'] ? '' : ' <span class="badge badge-neutral" style="font-size:0.6rem;">Maintenance</span>'; ?></span>
              <span style="font-size:0.8rem; color:#1E293B; font-weight:700;"><?php echo $ru['pct']; ?>%</span>
            </div>
            <div style="background:#F1F5F9; border-radius:6px; height:10px; overflow:hidden;">
              <div style="width:<?php echo $ru['pct']; ?>%; background:<?php echo $ru['pct'] >= 60 ? '#0D9488' : ($ru['pct'] >= 30 ? '#F59E0B' : ($ru['pct'] > 0 ? '#F97316' : '#E2E8F0')); ?>; border-radius:6px; height:10px;"></div>
            </div>
          </div>
        <?php endforeach; ?>
        <a href="<?php echo site_url('academic/rooms'); ?>" style="display:inline-block; margin-top:12px; font-size:0.8rem; color:#0D9488; font-weight:600; text-decoration:none;">Manage rooms &amp; assignments →</a>
      <?php endif; ?>
    </div>

    <!-- At-risk students -->
    <div class="card" style="padding:20px;">
      <h4 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 4px;">At-Risk Students</h4>
      <p style="font-size:0.78rem; color:#94A3B8; margin:0 0 16px;">Average above 3.00 or at least one failing subject (1.00–5.00 scale, passing ≤ 3.00) — <?php echo $sem_label; ?></p>
      <?php if ( ! $insights['has_grade_data']): ?>
        <div class="text-faint" style="font-size:0.82rem; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:16px; text-align:center;">No grades encoded yet for the <?php echo $sem_label; ?>.</div>
      <?php elseif (empty($insights['at_risk'])): ?>
        <div style="font-size:0.85rem; background:#F0FDFA; border:1px solid #99F6E4; color:#0F766E; border-radius:10px; padding:14px 16px; font-weight:500;">No at-risk students — every encoded final grade is passing (3.00 or better).</div>
      <?php else: ?>
        <table class="data-table" style="width:100%;">
          <thead>
            <tr>
              <th>Student</th>
              <th>Section</th>
              <th style="text-align:right;">Average</th>
              <th style="text-align:right;">Failing</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($insights['at_risk'] as $ar): ?>
              <tr>
                <td style="font-weight:600; color:#1E293B;"><?php echo html_escape($ar['student_name']); ?></td>
                <td style="color:#64748B;"><?php echo html_escape($ar['section_name']); ?></td>
                <td style="text-align:right; font-weight:700; color:<?php echo $ar['average'] > 3.00 ? '#DC2626' : '#0D9488'; ?>;"><?php echo number_format($ar['average'], 2); ?></td>
                <td style="text-align:right;">
                  <?php if ($ar['failing'] > 0): ?>
                    <span class="badge" style="background:#FEF2F2; color:#DC2626; font-size:0.72rem;"><?php echo (int) $ar['failing']; ?> subject<?php echo $ar['failing'] !== 1 ? 's' : ''; ?></span>
                  <?php else: ?>
                    <span class="badge badge-warning" style="font-size:0.72rem;">0</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (count($insights['at_risk']) === 8): ?>
          <div style="font-size:0.75rem; color:#94A3B8; margin-top:10px;">Showing the 8 highest (worst) averages.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Activity Detail Modals -->
  <?php foreach ($recent_activity as $r): ?>
    <div class="modal-overlay" id="activity-modal-<?php echo (int) $r->id; ?>">
      <div class="modal" style="max-width:500px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #F1F5F9;">
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Grade Change Detail</h3>
          <button type="button" onclick="closeActivityModal('<?php echo (int) $r->id; ?>')" style="background:none;border:none;padding:8px;cursor:pointer;color:#64748B;" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <div style="padding:4px 0;">
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Student</span>
            <span style="font-weight:600; color:#1E293B;"><?php echo html_escape($r->student_name); ?></span>
          </div>
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Changed by</span>
            <span style="font-weight:600; color:#1E293B;"><?php echo html_escape($r->teacher_name); ?></span>
          </div>
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Subject / Section</span>
            <span style="color:#374151;"><?php echo html_escape($r->subject_code . ' — ' . $r->section_name); ?></span>
          </div>
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Grading Period</span>
            <span style="color:#374151;"><?php echo html_escape($r->period_name); ?></span>
          </div>
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Timestamp</span>
            <span style="color:#374151;"><?php echo date('M j, Y g:i A', strtotime($r->changed_at)); ?></span>
          </div>
          <div style="margin-bottom:12px;">
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">Old Value</span>
            <span style="color:#374151;"><?php echo $r->old_value !== NULL ? html_escape($r->old_value) : '<span style="color:#94A3B8;">—</span>'; ?></span>
          </div>
          <div>
            <span style="display:block; font-size:0.75rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:2px;">New Value</span>
            <span style="color:#374151;"><?php echo $r->new_value !== NULL ? html_escape($r->new_value) : '<span style="color:#94A3B8;">—</span>'; ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <script>
  function openActivityModal(id) {
    const modal = document.getElementById('activity-modal-' + id);
    if (modal) {
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }
  function closeActivityModal(id) {
    const modal = document.getElementById('activity-modal-' + id);
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }
  // Close modal on overlay click
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
      overlay.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
          document.body.style.overflow = '';
        }
      });
    });
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
          overlay.style.display = 'none';
        });
        document.body.style.overflow = '';
      }
    });
  });
  </script>
<!-- END PAGE CONTENT -->