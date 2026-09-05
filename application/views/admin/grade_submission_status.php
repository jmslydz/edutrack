<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Grade Submission Status — grouped by Section with expandable subjects
 */
$active_page = isset($active_page) ? $active_page : 'grade_submission_status';
?>
<!-- BEGIN PAGE CONTENT -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
  <div>
    <h1 style="font-family:var(--font-display); font-size:1.5rem; font-weight:700; color:#1E293B; margin:0 0 4px;">Grade Submission Status</h1>
    <p style="color:#64748B; margin:0; font-size:0.9rem;">Overall completion by section for the active term.</p>
  </div>
  <div style="display:flex; gap:8px; align-items:center;">
    <span class="badge" style="background:#F0FDFA; color:#0D9488; padding:6px 12px; border-radius:6px; font-size:0.75rem; font-weight:600;">
      <?php echo isset($active_semester) ? html_escape($active_semester->name . ' — ' . $active_semester->year_label) : 'No active semester'; ?>
    </span>
  </div>
</div>

<?php if (isset($error)): ?>
  <div class="card" style="padding:24px; background:#FEF2F2; border:1px solid #FECACA; border-radius:12px; color:#991B1B;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:8px; vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?php echo html_escape($error); ?>
  </div>
<?php elseif (empty($sections)): ?>
  <div class="card" style="padding:48px; text-align:center; background:#FAFAFA; border:1px solid #F1F5F9; border-radius:12px;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;"><path d="M12 2v20M2 12h20"/><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
    <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">No sections with assigned subjects</h3>
    <p style="color:#64748B; margin:0; font-size:0.9rem;">Assign teachers to subjects for the active semester to track grade submission progress.</p>
  </div>
<?php else: ?>
  <div class="card" style="border-radius:12px; overflow:hidden; border:1px solid #F1F5F9;">
    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;">
      <thead>
        <tr style="background:#F8FAFC; border-bottom:1px solid #F1F5F9;">
          <th style="padding:14px 16px; text-align:left; font-weight:600; color:#374151; width:40px;"></th>
          <th style="padding:14px 16px; text-align:left; font-weight:600; color:#374151;">Section</th>
          <th style="padding:14px 16px; text-align:left; font-weight:600; color:#374151;">Program / Year</th>
          <th style="padding:14px 16px; text-align:left; font-weight:600; color:#374151;">Subjects</th>
          <th style="padding:14px 16px; text-align:center; font-weight:600; color:#374151;">Students</th>
          <th style="padding:14px 16px; text-align:center; font-weight:600; color:#374151;">Completion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sections as $idx => $sec): ?>
          <tr class="section-row" data-section-id="<?php echo (int) $sec['id']; ?>" style="border-bottom:1px solid #F1F5F9; cursor:pointer; transition:background 0.15s;">
            <td style="padding:14px 16px; text-align:center; vertical-align:middle;">
              <button type="button" class="expand-btn" aria-expanded="false" aria-controls="section-<?php echo (int) $sec['id']; ?>" style="background:none; border:none; padding:4px; cursor:pointer; color:#64748B; display:flex; align-items:center; justify-content:center;">
                <svg class="expand-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </td>
            <td style="padding:14px 16px; vertical-align:middle; font-weight:600; color:#1E293B;"><?php echo html_escape($sec['name']); ?></td>
            <td style="padding:14px 16px; vertical-align:middle; color:#64748B; font-size:0.8rem;"><?php echo html_escape($sec['program_code'] . ' — Grade ' . $sec['year_level']); ?></td>
            <td style="padding:14px 16px; vertical-align:middle; color:#64748B; font-size:0.8rem;"><?php echo count($sec['subjects']); ?> subject(s)</td>
            <td style="padding:14px 16px; vertical-align:middle; text-align:center; color:#374151; font-weight:500;"><?php echo (int) $sec['total_students']; ?></td>
            <td style="padding:14px 16px; vertical-align:middle; text-align:center;">
              <div style="display:inline-flex; align-items:center; gap:8px;">
                <div style="width:80px; height:8px; background:#F1F5F9; border-radius:4px; overflow:hidden;">
                  <div style="width:<?php echo (int) $sec['overall_pct']; ?>%; height:100%; background:<?php echo $sec['overall_pct'] >= 85 ? '#0D9488' : ($sec['overall_pct'] >= 60 ? '#0891B2' : ($sec['overall_pct'] >= 40 ? '#F97316' : '#7C3AED')); ?>; border-radius:4px; transition:width 0.3s ease;"></div>
                </div>
                <span style="font-weight:700; color:<?php echo $sec['overall_pct'] >= 85 ? '#0D9488' : ($sec['overall_pct'] >= 60 ? '#0891B2' : ($sec['overall_pct'] >= 40 ? '#F97316' : '#7C3AED')); ?>; font-size:0.85rem;"><?php echo (int) $sec['overall_pct']; ?>%</span>
              </div>
            </td>
          </tr>
          <tr id="section-<?php echo (int) $sec['id']; ?>" class="section-detail" style="display:none;">
            <td colspan="6" style="padding:0; background:#FAFAFA; border-top:1px solid #F1F5F9;">
              <div style="padding:20px 24px;">
                <div style="overflow-x:auto;">
                  <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                    <thead>
                      <tr style="background:#F1F5F9;">
                        <th style="padding:10px 12px; text-align:left; font-weight:600; color:#374151; width:300px;">Subject</th>
                        <th style="padding:10px 12px; text-align:left; font-weight:600; color:#374151;">Instructor</th>
                        <th style="padding:10px 12px; text-align:center; font-weight:600; color:#374151;">Midterm</th>
                        <th style="padding:10px 12px; text-align:center; font-weight:600; color:#374151;">Final</th>
                        <th style="padding:10px 12px; text-align:center; font-weight:600; color:#374151; width:100px;">Overall</th>
                        <th style="padding:10px 12px; text-align:center; font-weight:600; color:#374151; width:140px;">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($sec['subjects'] as $subj): ?>
                        <?php
                          $mid = $subj['period_progress']['Midterm'] ?? array('encoded'=>0,'total'=>0,'pct'=>0);
                          $fin = $subj['period_progress']['Final']   ?? array('encoded'=>0,'total'=>0,'pct'=>0);
                          $has_missing = !$subj['is_complete'];
                        ?>
                        <tr style="border-bottom:1px solid #F1F5F9; <?php echo $has_missing ? '' : 'opacity:0.6;'; ?>">
                          <td style="padding:12px; font-weight:500; color:#1E293B;"><?php echo html_escape($subj['subject_code'] . ' — ' . $subj['subject_title']); ?></td>
                          <td style="padding:12px; color:#64748B;"><?php echo html_escape($subj['teacher_name'] ?: '—'); ?></td>
                          <td style="padding:12px; text-align:center;">
                            <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                              <span style="font-weight:600; color:<?php echo $mid['pct'] >= 85 ? '#0D9488' : ($mid['pct'] >= 60 ? '#0891B2' : ($mid['pct'] >= 40 ? '#F97316' : '#7C3AED')); ?>;"><?php echo (int) $mid['pct']; ?>%</span>
                              <span style="font-size:0.7rem; color:#94A3B8;"><?php echo (int) $mid['encoded']; ?>/<?php echo (int) $mid['total']; ?></span>
                            </div>
                          </td>
                          <td style="padding:12px; text-align:center;">
                            <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                              <span style="font-weight:600; color:<?php echo $fin['pct'] >= 85 ? '#0D9488' : ($fin['pct'] >= 60 ? '#0891B2' : ($fin['pct'] >= 40 ? '#F97316' : '#7C3AED')); ?>;"><?php echo (int) $fin['pct']; ?>%</span>
                              <span style="font-size:0.7rem; color:#94A3B8;"><?php echo (int) $fin['encoded']; ?>/<?php echo (int) $fin['total']; ?></span>
                            </div>
                          </td>
                          <td style="padding:12px; text-align:center;">
                            <span style="font-weight:700; color:<?php echo $subj['overall_pct'] >= 85 ? '#0D9488' : ($subj['overall_pct'] >= 60 ? '#0891B2' : ($subj['overall_pct'] >= 40 ? '#F97316' : '#7C3AED')); ?>;"><?php echo (int) $subj['overall_pct']; ?>%</span>
                          </td>
                          <td style="padding:12px; text-align:center;">
                            <?php if ($has_missing): ?>
                              <?php if ($subj['teacher_user_id']): ?>
                                <button type="button" class="btn-secondary notify-btn"
                                  data-assignment-id="<?php echo (int) $subj['assignment_id']; ?>"
                                  data-section-id="<?php echo (int) $sec['id']; ?>"
                                  data-teacher-name="<?php echo html_escape($subj['teacher_name']); ?>"
                                  data-subject="<?php echo html_escape($subj['subject_code'] . ' — ' . $subj['subject_title']); ?>"
                                  style="padding:6px 12px; font-size:0.75rem;">
                                  Notify Instructor
                                </button>
                              <?php else: ?>
                                <span class="badge" style="background:#FEF2F2; color:#991B1B; padding:6px 10px; border-radius:6px; font-size:0.7rem; font-weight:600;">No teacher assigned</span>
                              <?php endif; ?>
                            <?php else: ?>
                              <span class="badge" style="background:#F0FDFA; color:#0D9488; padding:6px 10px; border-radius:6px; font-size:0.7rem; font-weight:600;">Complete</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<!-- END PAGE CONTENT -->

<script>
// Expand/collapse section rows
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.section-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('.notify-btn')) return;
      const sectionId = this.dataset.sectionId;
      const detail = document.getElementById('section-' + sectionId);
      const icon = this.querySelector('.expand-icon');
      const isOpen = detail.style.display !== 'none';
      detail.style.display = isOpen ? 'none' : 'table-row';
      icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
      this.querySelector('.expand-btn').setAttribute('aria-expanded', !isOpen);
    });
  });

  // Notify Instructor buttons
  document.querySelectorAll('.notify-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const assignmentId = this.dataset.assignmentId;
      const sectionId = this.dataset.sectionId;
      const teacherName = this.dataset.teacherName;
      const subject = this.dataset.subject;

      if (confirm('Send grade submission reminder to ' + teacherName + ' for ' + subject + '?')) {
        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('<?php echo site_url('admin/grade_submission_status_notify'); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'assignment_id=' + encodeURIComponent(assignmentId) + '&section_id=' + encodeURIComponent(sectionId)
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            this.textContent = 'Sent';
            this.style.background = '#0D9488';
            this.style.color = 'white';
            this.style.borderColor = '#0D9488';
          } else {
            alert('Failed: ' + data.message);
            this.disabled = false;
            this.textContent = 'Notify Instructor';
          }
        })
        .catch(() => {
          alert('Error sending notification');
          this.disabled = false;
          this.textContent = 'Notify Instructor';
        });
      }
    });
  });
});
</script>