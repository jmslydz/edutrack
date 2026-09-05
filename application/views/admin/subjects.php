<?php
$rows  = isset($rows) ? $rows : array();
$stats = isset($stats) ? $stats : array();
$edit  = isset($edit) ? $edit : NULL;

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Subjects</h2>
      <p class="page-subtitle">Manage the academic subject catalog</p>
    </div>
    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;" onclick="openModal('subjModal')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Add Subject
    </button>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Code</th><th>Title</th><th>Units</th><th>Classes</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="table-row"><td colspan="5" style="text-align:center; color:#94A3B8; padding:24px;">No subjects yet. Add one to get started.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $subj): $s = isset($stats[$subj->id]) ? $stats[$subj->id] : array('classes' => 0); ?>
        <tr class="table-row">
          <td><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($subj->code); ?></span></td>
          <td style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($subj->title); ?></td>
          <td style="color:#64748B;font-size:0.85rem;"><?php echo number_format((float) $subj->units, 1); ?></td>
          <td style="color:#64748B;font-size:0.85rem;"><?php echo (int) $s['classes']; ?> class(es)</td>
          <td>
            <div style="display:flex;gap:6px;align-items:center;">
              <a class="icon-btn icon-btn--edit" href="<?php echo site_url('academic/subjects?edit=' . (int) $subj->id); ?>" aria-label="Edit" title="Edit" onclick="openModal('subjModal')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              </a>
              <?php echo form_open('academic/subjects/delete/' . (int) $subj->id, array('style' => 'display:inline;')); ?>
                <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Delete" title="Delete" onclick="return confirm('Delete this subject?');">
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

  <!-- Add/Edit Subject Modal -->
  <div class="modal-overlay<?php echo ($edit ? ' visible' : ''); ?>" id="subjModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;"><?php echo $edit ? 'Edit Subject' : 'Add Subject'; ?></h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('subjModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      $form_action = $edit ? 'academic/subjects/update/' . (int) $edit->id : 'academic/subjects/store';
      echo form_open($form_action, array('novalidate' => 'novalidate'));
      ?>
        <div style="display:grid; grid-template-columns:1fr 120px; gap:16px; margin-bottom:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="subjCode">Subject Code</label>
            <input class="form-input" id="subjCode" name="code" placeholder="CS101" value="<?php echo html_escape($edit ? $edit->code : ''); ?>" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="subjUnits">Units</label>
            <input class="form-input" id="subjUnits" name="units" type="number" step="0.5" min="0.5" max="12" value="<?php echo $edit ? number_format((float) $edit->units, 1) : '3.0'; ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="subjTitle">Subject Title</label>
          <input class="form-input" id="subjTitle" name="title" placeholder="Introduction to Computing" value="<?php echo html_escape($edit ? $edit->title : ''); ?>" required>
        </div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('subjModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;"><?php echo $edit ? 'Save Changes' : 'Create Subject'; ?></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->