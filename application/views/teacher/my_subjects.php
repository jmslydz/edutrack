<?php
$groups = isset($groups) ? $groups : array();
$teacher_name = isset($teacher_name) ? $teacher_name : '';
$progress_colors = array('Midterm' => '#0891B2', 'Final' => '#F97316');
function _ms_progress_pct_color($pct) { return $pct >= 90 ? '#10B981' : ($pct >= 80 ? '#0891B2' : '#94A3B8'); }
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">My Subjects</h2>
      <p class="page-subtitle">All subject assignments for <?php echo html_escape($teacher_name); ?></p>
    </div>
  </div>

  <?php if (empty($groups)): ?>
    <div class="card subject-card" style="padding:24px;">
      <p class="text-faint" style="font-size:0.85rem; margin:0;">You have no subject assignments yet. Contact an administrator to assign classes.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($groups as $g): ?>
    <div style="margin-bottom:28px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;"><?php echo html_escape($g['sy_name'] . ' — ' . $g['sem_name']); ?></h3>
        <?php if ($g['is_active']): ?>
          <span class="badge badge-success">Current term</span>
        <?php endif; ?>
      </div>

      <div class="subject-grid">
        <?php foreach ($g['items'] as $s): ?>
        <div class="card subject-card">
          <div>
            <span class="subject-code"><?php echo html_escape($s['code']); ?></span>
            <h4 class="subject-title"><?php echo html_escape($s['title']); ?></h4>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              <span class="subject-section-badge"><?php echo html_escape($s['section']); ?></span>
              <span class="subject-meta"><?php echo (int) $s['student_count']; ?> students &bull; <?php echo number_format((float) $s['units'], 1); ?> units</span>
            </div>
          </div>
          <div class="subject-meta"><?php echo html_escape($s['schedule'] ? $s['schedule'] : 'Schedule TBA'); ?> &bull; <?php echo html_escape($s['room'] ? $s['room'] : 'Room TBA'); ?></div>
          <div class="grade-progress-block">
            <div class="grade-progress-title">Grade Submission</div>
            <div class="grade-progress-row">
              <?php foreach (array('Midterm', 'Final') as $period): $p = $s['progress'][$period]; $pct = $p['total'] > 0 ? (int) round($p['encoded'] / $p['total'] * 100) : 0; ?>
              <div class="progress-item"><div class="g-label"><?php echo $period; ?></div><div class="progress-track"><div class="progress-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $progress_colors[$period]; ?>;"></div></div><div class="progress-pct" style="color:<?php echo _ms_progress_pct_color($pct); ?>;"><?php echo $pct; ?>%</div></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div style="display:flex; gap:8px; margin-top:12px;">
            <a href="<?php echo site_url('teacher/encode_grades?subject=' . rawurlencode($s['code']) . '&section=' . rawurlencode($s['section']) . '&period=Midterm'); ?>" class="btn-primary" style="flex:1; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              Encode Grades
            </a>
            <?php if ( ! $s['is_active']): ?>
              <a href="<?php echo site_url('teacher/request_correction/' . (int) $s['assignment_id']); ?>" class="btn-secondary" style="flex:1; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                Request Correction
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
<!-- END PAGE CONTENT -->