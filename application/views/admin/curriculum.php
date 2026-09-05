<?php
$programs      = isset($programs) ? $programs : array();
$available     = isset($available) ? $available : array();
$slot_subjects = isset($slot_subjects) ? $slot_subjects : array();
$filter        = isset($filter) ? $filter : array('program_id' => 0, 'year_level' => 0, 'semester' => 0);
$slot_selected = isset($slot_selected) ? (bool) $slot_selected : FALSE;
$slot_program  = isset($slot_program) ? $slot_program : NULL;

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');

function _semester_label_cur($n)
{
	return $n == 1 ? '1st Semester' : '2nd Semester';
}
function _grade_label_cur($y)
{
	return 'Grade ' . (int) $y;
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Manage Curriculum</h2>
      <p class="page-subtitle">Define the subjects each strand teaches per grade level and semester</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('academic/curriculum'); ?>">
    <div class="list-toolbar">
      <select class="form-input filter-select" name="program_id" onchange="this.form.submit()" aria-label="Filter by strand">
        <option value="">Select strand…</option>
        <?php foreach ($programs as $p): ?>
          <option value="<?php echo (int) $p->id; ?>" <?php echo $filter['program_id'] === (int) $p->id ? 'selected' : ''; ?>><?php echo html_escape($p->program_code . ' — ' . $p->program_name); ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-input filter-select" name="year_level" onchange="this.form.submit()" aria-label="Filter by grade level">
        <option value="">Grade Level</option>
        <option value="11" <?php echo $filter['year_level'] === 11 ? 'selected' : ''; ?>>Grade 11</option>
        <option value="12" <?php echo $filter['year_level'] === 12 ? 'selected' : ''; ?>>Grade 12</option>
      </select>
      <select class="form-input filter-select" name="semester" onchange="this.form.submit()" aria-label="Filter by semester">
        <option value="">Semester</option>
        <option value="1" <?php echo $filter['semester'] === 1 ? 'selected' : ''; ?>>1st Semester</option>
        <option value="2" <?php echo $filter['semester'] === 2 ? 'selected' : ''; ?>>2nd Semester</option>
      </select>
      <span class="text-faint" style="font-size:0.75rem; align-self:center;">Sections auto-inherit subjects from the chosen slot via "Sync Subjects".</span>
    </div>
    </form>

    <?php if ( ! $slot_selected): ?>
      <div style="text-align:center; color:#94A3B8; padding:44px 20px; font-size:0.85rem;">
        Select a strand, grade level and semester to view and edit its curriculum.
      </div>
    <?php else: ?>
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px 0; flex-wrap:wrap;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;"><?php echo html_escape($slot_program->program_code . ' — ' . $slot_program->short_code . ' — ' . _grade_label_cur($filter['year_level'])); ?></h3>
          <p class="text-faint" style="font-size:0.78rem; margin:2px 0 0;"><?php echo html_escape($slot_program->program_name); ?> · <?php echo _semester_label_cur($filter['semester']); ?></p>
        </div>
        <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; <?php echo empty($available) ? 'opacity:.5; pointer-events:none;' : ''; ?>" onclick="openModal('curriculumModal')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
          Add Subject
        </button>
      </div>

      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Code</th><th>Subject Title</th><th>Units</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($slot_subjects)): ?>
            <tr class="table-row"><td colspan="4" style="text-align:center; color:#94A3B8; padding:24px;">No subjects in this curriculum slot yet. Add one to get started.</td></tr>
          <?php endif; ?>
          <?php foreach ($slot_subjects as $r): ?>
          <tr class="table-row">
            <td><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($r->code); ?></span></td>
            <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($r->title); ?></td>
            <td style="color:#64748B;font-size:0.85rem;"><?php echo (float) $r->units; ?></td>
            <td>
              <?php echo form_open('academic/curriculum/delete/' . (int) $r->id, array('style' => 'display:inline;')); ?>
                <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Remove" title="Remove from curriculum" onclick="return confirm('Remove this subject from the curriculum?' );">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                </button>
              <?php echo form_close(); ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($slot_selected): ?>
  <!-- Add Subject to Curriculum Modal -->
  <div class="modal-overlay" id="curriculumModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0;">Add Subject to Curriculum</h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('curriculumModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-faint" style="font-size:0.8rem; margin:0 0 14px;">
        Adding to <?php echo html_escape($slot_program->program_code . ' · ' . _grade_label_cur($filter['year_level']) . ' · ' . _semester_label_cur($filter['semester'])); ?>
      </p>
      <?php echo form_open('academic/curriculum/store'); ?>
        <input type="hidden" name="program_id" value="<?php echo (int) $filter['program_id']; ?>">
        <input type="hidden" name="year_level" value="<?php echo (int) $filter['year_level']; ?>">
        <input type="hidden" name="semester_number" value="<?php echo (int) $filter['semester']; ?>">
        <div class="form-group">
          <label class="form-label" for="curSubject">Subject</label>
          <?php if (empty($available)): ?>
            <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;border-radius:10px;padding:10px 14px;font-size:0.82rem;">Every subject is already in this curriculum slot.</div>
          <?php else: ?>
          <select class="form-input" id="curSubject" name="subject_id" required>
            <?php foreach ($available as $s): ?>
              <option value="<?php echo (int) $s->id; ?>"><?php echo html_escape($s->code . ' — ' . $s->title); ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('curriculumModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;" <?php echo empty($available) ? 'disabled' : ''; ?>>Add to Curriculum</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
  <?php endif; ?>
<!-- END PAGE CONTENT -->