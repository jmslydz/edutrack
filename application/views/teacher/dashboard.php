<?php
$teacher_name   = isset($teacher_name) ? $teacher_name : '';
$subjects       = isset($subjects) ? $subjects : array();
$assigned_count = isset($assigned_count) ? $assigned_count : 0;
$total_students = isset($total_students) ? $total_students : 0;
$semester_label = isset($semester_label) ? $semester_label : '';
$progress_colors = array('Midterm' => '#0891B2', 'Final' => '#F97316');
function _progress_pct_color($pct) { return $pct >= 90 ? '#10B981' : ($pct >= 80 ? '#0891B2' : '#94A3B8'); }
?>
<!-- BEGIN PAGE CONTENT (everything below belongs inside .content-area) -->
  <div class="welcome-banner">
    <div class="content">
      <p class="eyebrow">Good morning,</p>
      <h2><?php echo html_escape($teacher_name); ?></h2>
      <p class="sub">Department of Computer &amp; Information Science &bull; <?php echo html_escape($semester_label); ?></p>
    </div>
  </div>

  <div class="stat-grid stat-grid--3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0FDFA; color:#0D9488;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg></div>
      <div><div class="stat-label">Assigned Subjects</div><div class="stat-value"><?php echo $assigned_count; ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0F9FF; color:#0891B2;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
      <div><div class="stat-label">Total Students</div><div class="stat-value"><?php echo $total_students; ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FFF7ED; color:#F97316;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></div>
      <div><div class="stat-label">Current Semester</div><div class="stat-value"><?php echo html_escape($semester_label); ?></div></div>
    </div>
  </div>

  <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 16px;">My Subjects This Semester</h3>

  <div class="subject-grid">
    <?php if (empty($subjects)): ?>
      <div class="card subject-card" style="grid-column:1 / -1;">
        <p class="text-faint" style="font-size:0.85rem; margin:0;">You have no subjects assigned for the active school term.</p>
      </div>
    <?php endif; ?>
    <?php foreach ($subjects as $s): ?>
    <div class="card subject-card">
      <div>
        <span class="subject-code"><?php echo html_escape($s['code']); ?></span>
        <h4 class="subject-title"><?php echo html_escape($s['title']); ?></h4>
        <div style="display:flex; gap:8px; align-items:center;">
          <span class="subject-section-badge"><?php echo html_escape($s['section']); ?></span>
          <span class="subject-meta"><?php echo $s['student_count']; ?> students</span>
        </div>
      </div>
      <div class="subject-meta"><?php echo html_escape($s['schedule']); ?> &bull; <?php echo html_escape($s['room']); ?></div>
      <div class="grade-progress-block">
        <div class="grade-progress-title">Grade Submission</div>
        <div class="grade-progress-row">
          <?php foreach (array('Midterm', 'Final') as $period): $p = $s['progress'][$period]; $pct = $p['total'] > 0 ? (int) round($p['encoded'] / $p['total'] * 100) : 0; ?>
          <div class="progress-item"><div class="g-label"><?php echo $period; ?></div><div class="progress-track"><div class="progress-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $progress_colors[$period]; ?>;"></div></div><div class="progress-pct" style="color:<?php echo _progress_pct_color($pct); ?>;"><?php echo $pct; ?>%</div></div>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?php echo site_url('teacher/encode_grades?subject=' . rawurlencode($s['code']) . '&section=' . rawurlencode($s['section']) . '&period=Midterm'); ?>" class="btn-primary" style="width:100%; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
        Encode Grades
      </a>
    </div>
    <?php endforeach; ?>
  </div>
<!-- END PAGE CONTENT -->