<?php
$rows        = isset($rows) ? $rows : array();
$edit        = isset($edit) ? $edit : NULL;
$year_labels = isset($year_labels) ? $year_labels : array();
$filter_year = isset($filter_year) ? $filter_year : '';
$stats       = isset($stats) ? $stats : array('total' => 0, 'active' => 'None', 'unique_years' => 0);

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Semesters</h2>
      <p class="page-subtitle">Manage terms (each with a school year label)</p>
    </div>
    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;" onclick="openModal('semModal')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Add Semester
    </button>
  </div>

  <?php if ($flash_success): ?>
    <div style="background:#F0FDFA; border:1px solid #99F6E4; color:#0F766E; margin-bottom:16px; border-radius:12px; padding:12px 16px; font-size:0.82rem; font-weight:500; display:flex; align-items:center; gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?php echo html_escape($flash_success); ?>
    </div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; margin-bottom:16px; border-radius:12px; padding:12px 16px; font-size:0.82rem; font-weight:500; display:flex; align-items:center; gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?php echo html_escape($flash_error); ?>
    </div>
  <?php endif; ?>

  <!-- Stats Strip -->
  <div class="tile-strip" style="margin-bottom:24px;">
    <div class="tile" style="background:#F0FDFA; border-color:#99F6E4; border-radius:14px; padding:16px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:40px; height:40px; border-radius:10px; background:#CCFBF1; display:flex; align-items:center; justify-content:center; color:#0D9488; flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#0F766E; font-size:1.3rem;"><?php echo (int) $stats['total']; ?></div>
          <div class="tile-label">Total Semesters</div>
        </div>
      </div>
    </div>
    <div class="tile" style="background:#ECFDF5; border-color:#A7F3D0; border-radius:14px; padding:16px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:40px; height:40px; border-radius:10px; background:#D1FAE5; display:flex; align-items:center; justify-content:center; color:#059669; flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#047857; font-size:1.3rem; font-size:0.85rem; font-weight:700; line-height:1.3;"><?php echo html_escape($stats['active']); ?></div>
          <div class="tile-label">Active Semester</div>
        </div>
      </div>
    </div>
    <div class="tile" style="background:#EFF6FF; border-color:#BFDBFE; border-radius:14px; padding:16px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:40px; height:40px; border-radius:10px; background:#DBEAFE; display:flex; align-items:center; justify-content:center; color:#2563EB; flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#1D4ED8; font-size:1.3rem;"><?php echo (int) $stats['unique_years']; ?></div>
          <div class="tile-label">School Years</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('academic/semesters'); ?>">
    <div class="list-toolbar">
      <div style="display:flex; align-items:center; gap:8px; color:var(--color-text-faint);">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span style="font-size:0.8rem; font-weight:600;">Filter:</span>
      </div>
      <select class="form-input filter-select" name="year_label" onchange="this.form.submit()" aria-label="Filter by school year" style="width:auto; min-width:180px; padding:8px 14px; border-radius:8px; font-size:0.82rem;">
        <option value="">All School Years</option>
        <?php foreach ($year_labels as $yl): ?>
          <option value="<?php echo html_escape($yl); ?>" <?php echo $filter_year === $yl ? 'selected' : ''; ?>><?php echo html_escape($yl); ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filter_year): ?>
        <a href="<?php echo site_url('academic/semesters'); ?>" style="display:inline-flex; align-items:center; gap:4px; font-size:0.78rem; font-weight:600; color:#EF4444; text-decoration:none; padding:6px 12px; border-radius:8px; background:#FEF2F2; border:1px solid #FECACA; transition:all 0.15s;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Clear
        </a>
      <?php endif; ?>
    </div>
    </form>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px;"></th>
          <th>Semester</th>
          <th>School Year</th>
          <th>Sem. #</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="table-row">
            <td colspan="6" style="text-align:center; color:#94A3B8; padding:40px 20px;">
              <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span style="font-size:0.85rem; font-weight:500;">No semesters match your filter.</span>
                <?php if ($filter_year): ?>
                  <a href="<?php echo site_url('academic/semesters'); ?>" style="font-size:0.78rem; color:var(--color-primary); font-weight:600; text-decoration:none;">Show all semesters</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($rows as $idx => $sem): ?>
        <tr class="table-row" style="transition:background 0.12s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
          <td style="color:#CBD5E1; font-size:0.72rem; font-weight:600; text-align:center; width:40px;"><?php echo $idx + 1; ?></td>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <?php if ($sem->is_active): ?>
                <div style="width:8px; height:8px; border-radius:50%; background:#10B981; flex-shrink:0; box-shadow:0 0 0 3px rgba(16,185,129,0.2);"></div>
              <?php else: ?>
                <div style="width:8px; height:8px; border-radius:50%; background:#CBD5E1; flex-shrink:0;"></div>
              <?php endif; ?>
              <div>
                <div style="font-weight:600; color:#1E293B; font-size:0.85rem; line-height:1.3;"><?php echo html_escape($sem->name); ?></div>
                <?php if ($sem->semester_number !== NULL): ?>
                  <div style="font-size:0.72rem; color:#94A3B8; margin-top:1px;">Grading periods: Midterm &amp; Final</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <span style="background:#EFF6FF; color:#1D4ED8; padding:3px 10px; border-radius:20px; font-size:0.78rem; font-weight:600;">
              <?php echo html_escape($sem->year_label); ?>
            </span>
          </td>
          <td style="color:#64748B; font-size:0.85rem; font-weight:500;">
            <?php if ($sem->semester_number !== NULL): ?>
              <?php echo (int) $sem->semester_number === 1 ? '1st' : '2nd'; ?>
            <?php else: ?>
              <span style="color:#94A3B8;">Summer</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($sem->is_active): ?>
              <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:4px;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Active
              </span>
            <?php else: ?>
              <span class="badge badge-neutral">Inactive</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right;">
            <div style="display:flex; gap:6px; align-items:center; justify-content:flex-end;">
              <?php if ( ! $sem->is_active): ?>
                <?php echo form_open('academic/semesters/activate/' . (int) $sem->id, array('style' => 'display:inline;')); ?>
                  <button type="submit" class="icon-btn" style="background:#DCFCE7;color:#16A34A;" aria-label="Activate" title="Set as active semester" onclick="return confirm('Activate <?php echo html_escape($sem->name); ?> (<?php echo html_escape($sem->year_label); ?>) as the current semester?\n\nThis will deactivate the current active semester.');">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  </button>
                <?php echo form_close(); ?>
              <?php else: ?>
                <span style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:#F0FDF4; color:#16A34A;" title="Currently active">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
              <?php endif; ?>
              <a class="icon-btn icon-btn--edit" href="<?php echo site_url('academic/semesters?edit=' . (int) $sem->id); ?>" aria-label="Edit" title="Edit" onclick="openModal('semModal')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              </a>
              <?php if ( ! $sem->is_active): ?>
                <?php echo form_open('academic/semesters/delete/' . (int) $sem->id, array('style' => 'display:inline;')); ?>
                  <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Delete" title="Delete" onclick="return confirm('Delete this semester?\n\nThis cannot be undone if the semester has no classes or grades.');">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                  </button>
                <?php echo form_close(); ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php if ( ! empty($rows)): ?>
      <div class="table-footer">
        <span style="font-size:0.78rem; color:#94A3B8;">Showing <?php echo count($rows); ?> semester<?php echo count($rows) !== 1 ? 's' : ''; ?></span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Add/Edit Semester Modal -->
  <div class="modal-overlay<?php echo ($edit ? ' visible' : ''); ?>" id="semModal">
    <div class="modal" style="max-width:480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;">
            <?php echo $edit ? 'Edit Semester' : 'Add Semester'; ?>
          </h3>
          <p style="font-size:0.78rem; color:#94A3B8; margin:4px 0 0;">
            <?php echo $edit ? 'Update the semester details below.' : 'Create a new academic term.'; ?>
          </p>
        </div>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;transition:all 0.15s;" onclick="closeModal('semModal')" aria-label="Close" onmouseover="this.style.background='#E2E8F0'" onmouseout="this.style.background='#F1F5F9'">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      $form_action = $edit ? 'academic/semesters/update/' . (int) $edit->id : 'academic/semesters/store';
      echo form_open($form_action, array('novalidate' => 'novalidate'));
      ?>
        <div class="form-group">
          <label class="form-label" for="semYear">School Year <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="semYear" name="year_label" required>
            <option value="">Select school year...</option>
            <?php
            $currentYear = (int) date('Y');
            for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++) {
                $label = 'S.Y. ' . $y . '-' . ($y + 1);
                $selected = ($edit && $edit->year_label === $label) || (!$edit && $filter_year === $label) ? 'selected' : '';
                echo '<option value="' . html_escape($label) . '" ' . $selected . '>' . html_escape($label) . '</option>';
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="semName">Semester <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="semName" name="name" required>
            <option value="1st Semester" <?php echo $edit && $edit->name === '1st Semester' ? 'selected' : ''; ?>>1st Semester</option>
            <option value="2nd Semester" <?php echo $edit && $edit->name === '2nd Semester' ? 'selected' : ''; ?>>2nd Semester</option>
            <option value="Summer" <?php echo $edit && $edit->name === 'Summer' ? 'selected' : ''; ?>>Summer</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="semNum">Semester Number</label>
          <select class="form-input" id="semNum" name="semester_number">
            <option value="">(optional)</option>
            <option value="1" <?php echo $edit && $edit->semester_number == 1 ? 'selected' : ''; ?>>1</option>
            <option value="2" <?php echo $edit && $edit->semester_number == 2 ? 'selected' : ''; ?>>2</option>
          </select>
          <div style="font-size:0.72rem; color:#94A3B8; margin-top:6px; display:flex; align-items:center; gap:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Use for 1st/2nd Semester; leave blank for Summer.
          </div>
        </div>

        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <div style="font-size:0.78rem; color:#64748B; line-height:1.5;">
            <strong style="color:#1E293B;">Grading periods</strong> (Midterm &amp; Final) are created automatically when you add or activate a semester.
          </div>
        </div>

        <div style="display:flex; gap:12px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('semModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;"><?php echo $edit ? 'Save Changes' : 'Create Semester'; ?></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->