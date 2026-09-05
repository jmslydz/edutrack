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
      <h2 class="page-title">Manage Strands</h2>
      <p class="page-subtitle">Define the senior high school strands offered — click a strand to manage its subjects and curriculum</p>
    </div>
    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;" onclick="openModal('progModal')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Add Strand
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
      <thead><tr><th>Code</th><th>Strand Name</th><th>Short Code</th><th>Subjects</th><th>Sections</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="table-row"><td colspan="6" style="text-align:center; color:#94A3B8; padding:24px;">No strands yet. Add one to get started.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $prog): $s = isset($stats[$prog->id]) ? $stats[$prog->id] : array('sections' => 0, 'subjects' => 0); ?>
        <tr class="table-row" data-program-id="<?php echo (int) $prog->id; ?>" style="cursor:pointer;">
          <td onclick="window.location.href='<?php echo site_url('academic/strands/' . (int) $prog->id); ?>'" style="cursor:pointer;"><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($prog->program_code); ?></span></td>
          <td onclick="window.location.href='<?php echo site_url('academic/strands/' . (int) $prog->id); ?>'" style="cursor:pointer;">
            <span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($prog->program_name); ?></span>
            <span style="color:#94A3B8;font-size:0.72rem;display:block;">Click to manage subjects &amp; curriculum</span>
          </td>
          <td>
            <?php if ($prog->short_code !== NULL && $prog->short_code !== ''): ?>
              <span style="font-family:monospace;font-weight:700;color:#7C3AED;background:#F5F3FF;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape($prog->short_code); ?></span>
            <?php else: ?>
              <span style="color:#F59E0B;font-size:0.78rem;">Not set</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-student"><?php echo (int) $s['subjects']; ?> curriculum entr<?php echo $s['subjects'] === 1 ? 'y' : 'ies'; ?></span>
          </td>
          <td style="color:#64748B;font-size:0.85rem;"><?php echo (int) $s['sections']; ?> section(s)</td>
          <td>
            <div style="display:flex;gap:6px;align-items:center;">
              <a class="icon-btn icon-btn--edit" href="<?php echo site_url('academic/strands/' . (int) $prog->id); ?>" aria-label="Manage subjects and curriculum" title="Manage subjects &amp; curriculum" style="background:#F0FDFA;border:1px solid #99F6E4;color:#0D9488;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"/><path d="M21 3 11 13"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/></svg>
              </a>
              <a class="icon-btn icon-btn--edit" href="<?php echo site_url('academic/programs?edit=' . (int) $prog->id); ?>" aria-label="Edit" title="Edit" onclick="openModal('progModal')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              </a>
              <?php echo form_open('academic/programs/delete/' . (int) $prog->id, array('style' => 'display:inline;')); ?>
                <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Delete" title="Delete" onclick="return confirm('Delete this strand?');">
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

  <!-- Add/Edit Strand Modal -->
  <div class="modal-overlay<?php echo ($edit ? ' visible' : ''); ?>" id="progModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;"><?php echo $edit ? 'Edit Strand' : 'Add Strand'; ?></h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('progModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      $form_action = $edit ? 'academic/programs/update/' . (int) $edit->id : 'academic/programs/store';
      echo form_open($form_action, array('novalidate' => 'novalidate'));
      ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="progCode">Strand Code</label>
            <input class="form-input" id="progCode" name="program_code" placeholder="STEM" value="<?php echo html_escape($edit ? $edit->program_code : ''); ?>" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="progShort">Short Code</label>
            <input class="form-input" id="progShort" name="short_code" placeholder="STEM" value="<?php echo html_escape($edit ? $edit->short_code : ''); ?>" required style="text-transform:uppercase;">
            <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">1-10 uppercase letters/numbers, e.g. "STEM". For senior high school the short code IS the strand name; used in auto-suggested section names like "11-STEM-1".</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="progName">Strand Name</label>
          <input class="form-input" id="progName" name="program_name" placeholder="Science, Technology, Engineering, and Mathematics" value="<?php echo html_escape($edit ? $edit->program_name : ''); ?>" required>
        </div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('progModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;"><?php echo $edit ? 'Save Changes' : 'Create Strand'; ?></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->