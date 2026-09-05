<?php
$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
$status_labels = array(
	'pending_exam' => 'Waiting for Exam',
	'passed_exam'  => 'Passed Exam',
	'failed_exam'  => 'Failed Exam',
	'admitted'     => 'Admitted',
	'rejected'     => 'Rejected',
);
$status_colors = array(
	'pending_exam' => '#F59E0B',
	'passed_exam'  => '#0D9488',
	'failed_exam'  => '#DC2626',
	'admitted'     => '#10B981',
	'rejected'     => '#64748B',
);
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Applicants</h2>
      <p class="page-subtitle">Review admission applicants, issue exam codes, and admit successful applicants</p>
    </div>
    <a href="<?php echo site_url('admin/exam_questions'); ?>" class="btn" style="white-space:nowrap;">Manage Exam Questions</a>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Admission Applications</h3>
      <span class="badge" style="background:#F0FDFA;color:#0D9488;"><?php echo count($applicants); ?> total</span>
    </div>
    <div style="padding:24px;">
      <?php if (empty($applicants)): ?>
        <div style="text-align:center; color:#94A3B8; font-size:0.85rem; padding:20px;">
          No applicants yet. Applicants register through the public "Create an account" page.
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.85rem; min-width:900px;">
            <thead>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Applicant</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Status</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Exam Score</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Preferred Program</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Applied</th>
                <th style="text-align:left; padding:12px 16px; font-weight:600; color:#64748B;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applicants as $a): ?>
              <tr style="border-bottom:1px solid #F1F5F9;">
                <td style="padding:12px 16px;">
                  <div style="font-weight:700; color:#1E293B;"><?php echo html_escape($a->first_name . ' ' . $a->last_name); ?></div>
                  <div style="color:#94A3B8; font-size:0.75rem;"><?php echo html_escape($a->email); ?></div>
                </td>
                <td style="padding:12px 16px;">
                  <span class="badge" style="background:<?php echo $status_colors[$a->status]; ?>1A; color:<?php echo $status_colors[$a->status]; ?>;">
                    <?php echo html_escape($status_labels[$a->status]); ?>
                  </span>
                  <?php if ($a->status === 'admitted' && $a->admitted_section_name): ?>
                    <div style="color:#94A3B8; font-size:0.72rem; margin-top:4px;"><?php echo html_escape($a->admitted_section_name); ?></div>
                  <?php endif; ?>
                </td>
                <td style="padding:12px 16px; color:#475569;">
                  <?php if ($a->exam_score !== NULL): ?>
                    <strong><?php echo (int) $a->exam_score; ?> / <?php echo (int) $a->exam_total; ?></strong>
                    <span style="color:<?php echo $a->exam_passed ? '#10B981' : '#DC2626'; ?>;">
                      (<?php echo $a->exam_passed ? 'Passed' : 'Failed'; ?>)
                    </span>
                  <?php else: ?>
                    <span style="color:#CBD5E1;">—</span>
                  <?php endif; ?>
                  <?php if ($a->exam_code !== NULL): ?>
                    <div style="color:#0D9488; font-size:0.72rem; margin-top:4px; font-weight:700; letter-spacing:.05em;">CODE: <?php echo html_escape($a->exam_code); ?></div>
                  <?php endif; ?>
                </td>
                <td style="padding:12px 16px; color:#475569;">
                  <?php echo $a->preferred_program_name ? html_escape($a->preferred_program_name) : '<span style="color:#CBD5E1;">—</span>'; ?>
                </td>
                <td style="padding:12px 16px; color:#64748B;"><?php echo date('M j, Y', strtotime($a->created_at)); ?></td>
                <td style="padding:12px 16px;">
                  <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <?php if ($a->status === 'pending_exam' && $a->exam_code === NULL): ?>
                      <form method="post" action="<?php echo site_url('admin/applicants/generate_code/' . (int) $a->id); ?>" style="display:inline;" onsubmit="return confirm('Issue a one-time exam code to this applicant?');">
                        <?php echo $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : ''; ?>
                        <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem;">Issue Exam Code</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($a->status === 'passed_exam' || $a->status === 'rejected'): ?>
                      <button type="button" class="btn" style="padding:6px 12px; font-size:0.75rem; color:#0D9488;" onclick="openAdmitModal(<?php echo (int) $a->id; ?>, '<?php echo html_escape($a->first_name . ' ' . $a->last_name); ?>')">Admit</button>
                    <?php endif; ?>

                    <?php if ($a->status === 'failed_exam' || $a->status === 'rejected'): ?>
                      <form method="post" action="<?php echo site_url('admin/applicants/retake/' . (int) $a->id); ?>" style="display:inline;" onsubmit="return confirm('Allow this applicant to retake the exam? Their previous attempt will be cleared and they will need a new exam code.');">
                        <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
                        <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem; color:#F59E0B;">Retake</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($a->status === 'pending_exam' || $a->status === 'passed_exam' || $a->status === 'failed_exam'): ?>
                      <form method="post" action="<?php echo site_url('admin/applicants/reject/' . (int) $a->id); ?>" style="display:inline;" onsubmit="return confirm('Reject this applicant? They will be notified.');">
                        <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
                        <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem; color:#DC2626;">Reject</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($a->status !== 'admitted'): ?>
                      <form method="post" action="<?php echo site_url('admin/applicants/delete/' . (int) $a->id); ?>" style="display:inline;" onsubmit="return confirm('Delete this applicant permanently? This cannot be undone.');">
                        <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
                        <button type="submit" class="btn" style="padding:6px 12px; font-size:0.75rem; color:#94A3B8;">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ( ! empty($applicants)): ?>
  <div class="card" style="margin-top:24px;">
    <div style="padding:20px 24px;">
      <h3 style="font-family:var(--font-display); font-size:0.95rem; font-weight:700; color:#1E293B; margin:0 0 10px;">How the admission flow works</h3>
      <ol style="font-size:0.82rem; color:#64748B; line-height:1.9; padding-left:20px; margin:0;">
        <li>Applicants register on the public page — they are <strong>not</strong> students yet.</li>
        <li>They come to the campus; you click <strong>Issue Exam Code</strong> and hand them the code in person (prevents cheating).</li>
        <li>They take the timed exam on the portal; it auto-scores and they pick a preferred program if they pass.</li>
        <li>Applicants who <strong>failed</strong> (or were rejected) can be given another chance with <strong>Retake</strong> — their attempt is cleared and you issue a fresh exam code.</li>
        <li>You verify credentials, then <strong>Admit</strong> them into a section. The system creates their student account, enrolls them, notifies them in-app, and emails the result.</li>
      </ol>
    </div>
  </div>
  <?php endif; ?>

  <!-- Admit modal -->
  <div id="admitModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:1000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:16px; max-width:440px; width:100%; padding:26px; box-shadow:0 24px 64px rgba(0,0,0,.2);">
      <h3 style="font-family:var(--font-display); font-size:1.05rem; font-weight:700; color:#1E293B; margin:0 0 6px;">Admit applicant</h3>
      <p style="font-size:0.83rem; color:#64748B; margin:0 0 18px;">
        Admitting <strong id="admitName">—</strong>. They will get a student account, be enrolled, and receive a notification + email.
      </p>
      <form method="post" id="admitForm" action="">
        <?php echo '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" for="admit_section">Assign to Section <span style="color:#EF4444;">*</span></label>
          <select class="form-input" name="section_id" id="admit_section" required>
            <option value="">Select section...</option>
            <?php foreach ($sections as $sec): ?>
              <option value="<?php echo (int) $sec->id; ?>">
                <?php echo html_escape($sec->name); ?><?php if (isset($sec->program_code) && $sec->program_code): ?> — <?php echo html_escape($sec->program_code); ?><?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:0.75rem; color:#94A3B8; margin-top:6px;">
            Choose the section this applicant should join. Prefer a section in their chosen program when possible.
          </div>
        </div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" class="btn" onclick="document.getElementById('admitModal').style.display='none';">Cancel</button>
          <button type="submit" class="btn-primary">Admit Applicant</button>
        </div>
      </form>
    </div>
  </div>

<script>
function openAdmitModal(id, name) {
  document.getElementById('admitName').textContent = name;
  document.getElementById('admitForm').action = '<?php echo site_url('admin/applicants/admit'); ?>/' + id;
  document.getElementById('admitModal').style.display = 'flex';
}
document.getElementById('admitModal').addEventListener('click', function (e) {
  if (e.target === this) this.style.display = 'none';
});
</script>