<?php
$program    = isset($program) ? $program : NULL;
$year_level = isset($year_level) ? (int) $year_level : 11;
$slots      = isset($slots) ? $slots : array(11 => array(1 => array(), 2 => array()), 12 => array(1 => array(), 2 => array()));
$available  = isset($available) ? $available : array();

if ( ! function_exists('_grade_label_sd'))
{
	function _grade_label_sd($y) { return 'Grade ' . (int) $y; }
}
if ( ! function_exists('_semester_label_sd'))
{
	function _semester_label_sd($n) { return $n == 1 ? '1st Semester' : '2nd Semester'; }
}

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <a href="<?php echo site_url('academic/programs'); ?>" class="text-faint" style="font-size:0.75rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">&larr; Back to Strands</a>
      <h2 class="page-title" style="margin-top:2px;">
        <?php echo html_escape($program->short_code); ?>
        <span class="text-faint" style="font-weight:400;font-size:0.9rem;">· <?php echo html_escape($program->program_code); ?></span>
      </h2>
      <p class="page-subtitle"><?php echo html_escape($program->program_name); ?> — create subjects and build this strand's curriculum in one place</p>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="inpage-tabs" role="tablist" aria-label="Choose grade level">
    <?php foreach (array(11, 12) as $y): ?>
      <a class="inpage-tab<?php echo $year_level === $y ? ' active' : ''; ?>" role="tab" aria-selected="<?php echo $year_level === $y ? 'true' : 'false'; ?>" href="<?php echo site_url('academic/strands/' . (int) $program->id . '?year_level=' . $y); ?>"><?php echo _grade_label_sd($y); ?></a>
    <?php endforeach; ?>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(380px, 1fr)); gap:16px;">
    <?php foreach (array(1, 2) as $sem): $slot_rows = isset($slots[$year_level][$sem]) ? $slots[$year_level][$sem] : array(); ?>
    <div class="card" style="overflow:hidden;">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; border-bottom:1px solid #F1F5F9; flex-wrap:wrap;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;"><?php echo _semester_label_sd($sem); ?></h3>
          <p class="text-faint" style="font-size:0.75rem; margin:2px 0 0;"><?php echo count($slot_rows); ?> subject<?php echo count($slot_rows) === 1 ? '' : 's'; ?></p>
        </div>
        <button type="button" class="btn-primary asm-add-btn" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;"
          data-year="<?php echo (int) $year_level; ?>" data-sem="<?php echo (int) $sem; ?>">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
          Add Subject
        </button>
      </div>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Code</th><th>Subject Title</th><th>Units</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($slot_rows)): ?>
            <tr class="table-row"><td colspan="4" style="text-align:center; color:#94A3B8; padding:22px; font-size:0.82rem;">No subjects for this slot yet. Use "Add Subject" to link one or create a new one.</td></tr>
          <?php endif; ?>
          <?php foreach ($slot_rows as $r): ?>
          <tr class="table-row">
            <td><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($r->code); ?></span></td>
            <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($r->title); ?></td>
            <td style="color:#64748B;font-size:0.85rem;"><?php echo number_format((float) $r->units, 1); ?></td>
            <td>
              <div style="display:flex;gap:6px;align-items:center;">
                <button type="button" class="asm-edit-btn" aria-label="Edit subject details" title="Edit subject (applies everywhere it is used)"
                  style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #CBD5E1;background:#fff;color:#334155;cursor:pointer;"
                  data-subj-id="<?php echo (int) $r->subject_id; ?>"
                  data-subj-code="<?php echo html_escape($r->code); ?>"
                  data-subj-title="<?php echo html_escape($r->title); ?>"
                  data-subj-units="<?php echo (float) $r->units; ?>">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                </button>
                <?php echo form_open('academic/strands/remove/' . (int) $r->id, array('style' => 'display:inline;')); ?>
                  <button type="submit" aria-label="Remove from this strand" title="Remove from this strand's curriculum (keeps the subject row)"
                    style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #CBD5E1;background:#fff;color:#B45309;cursor:pointer;"
                    onclick="return confirm('Remove <?php echo html_escape($r->code); ?> from this strand\'s <?php echo html_escape(_grade_label_sd($year_level)); ?>, <?php echo html_escape(_semester_label_sd($sem)); ?>? The subject itself is kept and can still be used by other strands.');">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.33 22.26a.7.7 0 0 1-1 0l-.18-.19a.7.7 0 0 1 0-1l6.25-6.25a.7.7 0 0 0 0-1l-6.25-6.25a.7.7 0 0 1 0-1l.18-.19a.7.7 0 0 1 1 0l8.87 8.87a.7.7 0 0 1 0 1Z"/><path d="m2.33 22.26a.7.7 0 0 1-1 0l-.18-.19a.7.7 0 0 1 0-1l6.25-6.25a.7.7 0 0 0 0-1L1.15 7.57a.7.7 0 0 1 0-1l.18-.19a.7.7 0 0 1 1 0l8.87 8.87a.7.7 0 0 1 0 1Z"/></svg>
                  </button>
                <?php echo form_close(); ?>
                <?php echo form_open('academic/strands/subject_delete/' . (int) $r->subject_id, array('style' => 'display:inline;')); ?>
                  <input type="hidden" name="program_id" value="<?php echo (int) $program->id; ?>">
                  <input type="hidden" name="year_level" value="<?php echo (int) $year_level; ?>">
                  <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Delete subject permanently" title="Delete subject (only if not used anywhere)"
                    onclick="return confirm('Permanently delete subject <?php echo html_escape($r->code); ?>? This only works when it is not linked to any strand and has no records.');">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                  </button>
                <?php echo form_close(); ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="text-faint" style="font-size:0.75rem; margin-top:14px;">
    Subjects can be shared across strands — editing a subject here updates it everywhere it is used. Removing it from this strand keeps the subject row; deleting deletes the row entirely (only when it has no links or records).
  </p>

  <!-- Add Subject Modal: link an existing subject OR create + link a new one -->
  <div class="modal-overlay" id="addSubjectModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0;">Add Subject</h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('addSubjectModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-faint" style="font-size:0.8rem; margin:0 0 14px;">Adding to <strong id="asmLabel" style="color:#0D9488;"></strong></p>
      <?php echo form_open('academic/strands/add_subject', array('id' => 'addSubjectForm', 'novalidate' => 'novalidate')); ?>
        <input type="hidden" name="program_id" id="asmProgramId" value="<?php echo (int) $program->id; ?>">
        <input type="hidden" name="year_level" id="asmYearLevel" value="<?php echo (int) $year_level; ?>">
        <input type="hidden" name="semester_number" id="asmSemesterNumber" value="">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
          <label class="asmModeCard" style="cursor:pointer; border:1px solid #0D9488; background:#F0FDFA; border-radius:10px; padding:12px; display:flex; gap:10px; align-items:flex-start;">
            <input type="radio" name="subject_mode" value="existing" checked style="margin-top:3px;">
            <span>
              <span style="display:block; font-weight:700; font-size:0.85rem; color:#0F172A;">Link an existing subject</span>
              <span style="display:block; font-size:0.75rem; color:#64748B; margin-top:2px;">Reuse a subject already on file — shared core subjects stay unique.</span>
            </span>
          </label>
          <label class="asmModeCard" style="cursor:pointer; border:1px solid #CBD5E1; border-radius:10px; padding:12px; display:flex; gap:10px; align-items:flex-start;">
            <input type="radio" name="subject_mode" value="new" style="margin-top:3px;">
            <span>
              <span style="display:block; font-weight:700; font-size:0.85rem; color:#0F172A;">Create a new subject</span>
              <span style="display:block; font-size:0.75rem; color:#64748B; margin-top:2px;">Add a brand-new subject and link it in one step.</span>
            </span>
          </label>
        </div>

        <div class="form-group" id="asmExistingBlock">
          <label class="form-label" for="asmSubject">Subject</label>
          <select class="form-input" id="asmSubject" name="subject_id" required></select>
          <div class="text-faint" id="asmEmpty" style="font-size:0.75rem;margin-top:4px;"></div>
        </div>

        <div id="asmNewBlock" style="display:none;">
          <div style="display:grid; grid-template-columns:1fr 120px; gap:16px; margin-bottom:16px;">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" for="asmCode">Subject Code</label>
              <input class="form-input" id="asmCode" name="code" placeholder="CS101" required>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" for="asmUnits">Units</label>
              <input class="form-input" id="asmUnits" name="units" type="number" step="0.5" min="0.5" max="12" value="3.0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="asmTitle">Subject Title</label>
            <input class="form-input" id="asmTitle" name="title" placeholder="Introduction to Computing" required>
          </div>
        </div>

        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('addSubjectModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;" id="asmSubmitBtn">Add to Strand</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <!-- Edit Subject Modal (applies everywhere the subject is used) -->
  <div class="modal-overlay" id="editSubjectModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0;">Edit Subject</h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('editSubjectModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-faint" style="font-size:0.8rem; margin:0 0 14px;">Changes apply everywhere this subject is used (it may be shared across strands).</p>
      <?php echo form_open('academic/strands/subject_update/0', array('id' => 'editSubjectForm', 'novalidate' => 'novalidate')); ?>
        <input type="hidden" name="program_id" value="<?php echo (int) $program->id; ?>">
        <input type="hidden" name="year_level" value="<?php echo (int) $year_level; ?>">
        <div style="display:grid; grid-template-columns:1fr 120px; gap:16px; margin-bottom:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="esmCode">Subject Code</label>
            <input class="form-input" id="esmCode" name="code" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="esmUnits">Units</label>
            <input class="form-input" id="esmUnits" name="units" type="number" step="0.5" min="0.5" max="12" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="esmTitle">Subject Title</label>
          <input class="form-input" id="esmTitle" name="title" required>
        </div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('editSubjectModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;">Save Changes</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <script type="application/json" id="strandSubjectData">
  <?php echo json_encode(array(
      'program_id' => (int) $program->id,
      'short_code' => $program->short_code,
      'available'  => $available,
  )); ?>
  </script>

  <script>
  (function () {
    var data = { available: {} };
    var dataEl = document.getElementById('strandSubjectData');
    if (dataEl) { try { data = JSON.parse(dataEl.textContent) || {}; } catch (e) { data = {}; } }
    data.available = data.available || {};

    function gradeLabel(y) { return 'Grade ' + y; }
    function semLabel(n) { return n == 1 ? '1st Semester' : '2nd Semester'; }

    // ---- Add modal: per-slot setup ----
    var addForm    = document.getElementById('addSubjectForm');
    var existingEl = document.getElementById('asmSubject');
    var emptyNote  = document.getElementById('asmEmpty');

    function setAddTarget(btn) {
      var year = btn.getAttribute('data-year');
      var sem  = btn.getAttribute('data-sem');
      document.getElementById('asmYearLevel').value = year;
      document.getElementById('asmSemesterNumber').value = sem;
      document.getElementById('asmLabel').textContent =
        (data.short_code || '') + ' · ' + gradeLabel(year) + ' · ' + semLabel(sem);

      var list = (data.available[year] && data.available[year][sem]) || [];
      var html = '';
      for (var i = 0; i < list.length; i++) {
        html += '<option value="' + list[i].id + '">' + list[i].label + '</option>';
      }
      existingEl.innerHTML = html;
      emptyNote.textContent = list.length === 0
        ? 'Every subject on file is already in this slot — create a new one below.'
        : '';
    }

    var addButtons = document.querySelectorAll('.asm-add-btn');
    for (var b = 0; b < addButtons.length; b++) {
      addButtons[b].addEventListener('click', function () {
        setMode('existing');
        setAddTarget(this);
        openModal('addSubjectModal');
      });
    }

    function setMode(mode) {
      var existingBlock = document.getElementById('asmExistingBlock');
      var newBlock      = document.getElementById('asmNewBlock');
      var radios = addForm.querySelectorAll('input[name=subject_mode]');
      for (var i = 0; i < radios.length; i++) {
        radios[i].checked = (radios[i].value === mode);
        if (radios[i].value === mode) { radios[i].closest('.asmModeCard').style.borderColor = '#0D9488'; radios[i].closest('.asmModeCard').style.background = '#F0FDFA'; }
        else { radios[i].closest('.asmModeCard').style.borderColor = '#CBD5E1'; radios[i].closest('.asmModeCard').style.background = '#fff'; }
      }
      existingBlock.style.display = mode === 'existing' ? '' : 'none';
      newBlock.style.display      = mode === 'new' ? '' : 'none';
      var existingInputs = existingBlock.querySelectorAll('input, select');
      var newInputs      = newBlock.querySelectorAll('input');
      for (var x = 0; x < existingInputs.length; x++) { existingInputs[x].disabled = (mode !== 'existing'); }
      for (var y = 0; y < newInputs.length; y++) { newInputs[y].disabled = (mode !== 'new'); }
      document.getElementById('asmSubmitBtn').textContent = mode === 'existing' ? 'Link to Strand' : 'Create & Link';
    }

    var radios = addForm.querySelectorAll('input[name=subject_mode]');
    for (var r = 0; r < radios.length; r++) {
      radios[r].addEventListener('change', function () { setMode(this.value); });
    }

    // ---- Edit modal: populate from the clicked row ----
    var editForm = document.getElementById('editSubjectForm');
    var editButtons = document.querySelectorAll('.asm-edit-btn');
    for (var e = 0; e < editButtons.length; e++) {
      editButtons[e].addEventListener('click', function () {
        editForm.action = this.getAttribute('data-subj-id')
          ? '<?php echo site_url('academic/strands/subject_update/'); ?>' + this.getAttribute('data-subj-id')
          : editForm.action;
        document.getElementById('esmCode').value  = this.getAttribute('data-subj-code') || '';
        document.getElementById('esmTitle').value = this.getAttribute('data-subj-title') || '';
        document.getElementById('esmUnits').value = this.getAttribute('data-subj-units') || '3.0';
        openModal('editSubjectModal');
      });
    }

    // Establish the default "link existing" state (disables the create-new
    // inputs so their required fields do not block the existing-subject path).
    setMode('existing');
  })();
  </script>
<!-- END PAGE CONTENT -->