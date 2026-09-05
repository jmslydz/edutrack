<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Class List</h2>
      <p class="page-subtitle">View students in your assigned sections</p>
    </div>
  </div>

  <?php if (empty($sections)): ?>
    <div class="card" style="padding:48px 24px; text-align:center;">
      <p style="font-family:var(--font-display); font-size:1.05rem; font-weight:700; color:#1E293B; margin:0 0 6px;">No Sections Assigned</p>
      <p class="text-faint" style="font-size:0.85rem; margin:0 0 18px;">You don't have any sections assigned to you for the current semester.</p>
      <a href="<?php echo site_url('teacher/dashboard'); ?>" class="btn-primary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">Back to Dashboard</a>
    </div>
  <?php else: ?>

    <div class="card filter-card">
      <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Select Section</div>
      <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <?php foreach ($sections as $sec): $is_selected = $selected_section && $selected_section['name'] === $sec['name']; ?>
          <a href="<?php echo site_url('teacher/class_list?section=' . rawurlencode($sec['name'])); ?>"
             class="<?php echo $is_selected ? 'btn-primary' : 'btn-secondary'; ?>"
             style="border-radius:8px; padding:7px 14px; font-size:0.8rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center;">
            <?php echo html_escape($sec['name']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($selected_section): ?>
      <div class="card" style="overflow:hidden; margin-bottom:24px;">
        <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Section: <?php echo html_escape($selected_section['name']); ?></h3>
          <span class="badge badge-neutral"><?php echo count($students); ?> Students</span>
        </div>
        <div style="padding:20px 24px;">
          <?php if ( ! empty($section_subjects)): ?>
            <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;">Subjects in This Section</div>
            <div style="margin-bottom:22px;">
              <?php foreach ($section_subjects as $subj): ?>
                <span class="badge" style="background:#E0F2FE; color:#0369A1; margin:0 6px 6px 0;">
                  <?php echo html_escape($subj->subject_code); ?> — <?php echo html_escape($subj->subject_title); ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;">Student List</div>

          <?php if (empty($students)): ?>
            <div class="text-faint" style="text-align:center; padding:28px 0; font-size:0.85rem;">No students enrolled in this section.</div>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:44px;">#</th>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Grade Level</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1; foreach ($students as $student): ?>
                  <tr class="table-row">
                    <td style="color:#94A3B8;"><?php echo $i++; ?></td>
                    <td style="font-family:monospace; font-size:0.8rem; color:#64748B;"><?php echo html_escape($student->student_no); ?></td>
                    <td style="font-weight:600; color:#1E293B;"><?php echo html_escape($student->first_name . ' ' . $student->last_name); ?></td>
                    <td><span class="badge badge-neutral">Grade <?php echo (int) $student->grade_level; ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:24px;">
      <a href="<?php echo site_url('teacher/dashboard'); ?>" class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Dashboard
      </a>
    </div>
  <?php endif; ?>
<!-- END PAGE CONTENT -->
