<?php
$rows             = isset($rows) ? $rows : array();
$stats            = isset($stats) ? $stats : array();
$section_subjects = isset($section_subjects) ? $section_subjects : array();
$active_sem       = isset($active_sem) ? $active_sem : NULL;
$active_teachers  = isset($active_teachers) ? $active_teachers : array();
$programs         = isset($programs) ? $programs : array();
$buildings_all    = isset($buildings_all) ? $buildings_all : array();
$sections_by_building = isset($sections_by_building) ? $sections_by_building : array();
$filters          = isset($filters) ? $filters : array('program_id' => 0, 'status' => '');
$has_filter       = ! empty($filters['program_id']) || ! empty($filters['status']);
$edit             = isset($edit) ? $edit : NULL;

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Sections</h2>
      <p class="page-subtitle">Manage class sections and their members</p>
    </div>
    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;" onclick="openModal('secModal')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Add Section
    </button>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <div class="card" style="padding:48px; text-align:center;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
      <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">No sections found</h3>
      <p class="text-faint" style="font-size:0.85rem; margin:0;"><?php echo $has_filter ? 'Try adjusting your filters.' : 'Add a section to get started.'; ?></p>
    </div>
  <?php else: ?>
    <!-- Group sections by program -->
    <?php
    $grouped = array();
    foreach ($rows as $sec) {
        $pid = $sec->program_id !== NULL ? (int) $sec->program_id : 0;
        if (!isset($grouped[$pid])) {
            $grouped[$pid] = array(
                'code'     => $sec->program_code ?: 'Unassigned',
                'name'     => isset($sec->program_name) ? $sec->program_name : '',
                'sections' => array(),
            );
        }
        $grouped[$pid]['sections'][] = $sec;
    }
    // Sort groups alphabetically by code
    uksort($grouped, function($a, $b) use ($grouped) {
        return strcmp($grouped[$a]['code'], $grouped[$b]['code']);
    });
    ?>
    <?php foreach ($grouped as $pid => $group): ?>
      <?php
      $g11 = array();
      $g12 = array();
      foreach ($group['sections'] as $sec) {
          if ((int) $sec->year_level === 12) { $g12[] = $sec; }
          else { $g11[] = $sec; }
      }
      $total = count($group['sections']);
      $active_count = 0;
      foreach ($group['sections'] as $sec) { if ((int) $sec->is_active === 1) { $active_count++; } }
      ?>
      <div class="strand-group" style="margin-bottom:16px;">
        <!-- Strand Header (clickable accordion) -->
        <div class="strand-header" onclick="toggleStrandGroup(this)" style="display:flex; align-items:center; gap:14px; padding:16px 20px; background:white; border-radius:14px; box-shadow:0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); cursor:pointer; transition:box-shadow 0.15s; user-select:none;">
          <div style="width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,#0D9488,#0891B2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <span style="color:white; font-family:var(--font-display); font-weight:800; font-size:0.85rem;"><?php echo html_escape(substr($group['code'], 0, 2)); ?></span>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:#1E293B;"><?php echo html_escape($group['code']); ?></div>
            <div style="font-size:0.78rem; color:#64748B; margin-top:2px;"><?php echo html_escape($group['name']); ?></div>
          </div>
          <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
            <span class="badge badge-success" style="font-size:0.72rem;"><?php echo $active_count; ?> active</span>
            <span class="badge badge-neutral" style="font-size:0.72rem;"><?php echo $total; ?> total</span>
          </div>
          <svg class="strand-caret" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; transition:transform 0.2s;"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <!-- Strand Body (collapsible) -->
        <div class="strand-body" style="display:none; padding:0 8px;">
          <?php foreach (array(11 => $g11, 12 => $g12) as $yr => $yr_sections): ?>
            <?php if (empty($yr_sections)) continue; ?>
            <div style="margin-top:12px;">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; padding-left:8px;">
                <span style="font-family:var(--font-display); font-weight:700; font-size:0.82rem; color:#64748B;">Grade <?php echo $yr; ?></span>
                <span class="badge badge-neutral" style="font-size:0.65rem;"><?php echo count($yr_sections); ?></span>
              </div>
              <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:8px;">
                <?php foreach ($yr_sections as $sec): $s = isset($stats[$sec->id]) ? $stats[$sec->id] : array('students' => 0, 'classes' => 0); ?>
                  <div style="background:<?php echo (int) $sec->is_active === 1 ? '#FAFFFE' : '#F8FAFC'; ?>; border:1px solid <?php echo (int) $sec->is_active === 1 ? '#CCFBF1' : '#E2E8F0'; ?>; border-radius:10px; padding:12px 14px; display:flex; flex-direction:column; gap:8px;<?php echo (int) $sec->is_active === 0 ? ' opacity:0.6;' : ''; ?>" data-section-id="<?php echo (int) $sec->id; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                      <span class="badge badge-student" style="font-family:var(--font-display);font-weight:700;font-size:0.82rem; cursor:pointer;" onclick="toggleSectionDetail(<?php echo (int) $sec->id; ?>)"><?php echo html_escape($sec->name); ?></span>
                      <span class="badge <?php echo (int) $sec->is_active === 1 ? 'badge-success' : 'badge-neutral'; ?>" style="font-size:0.65rem;"><?php echo (int) $sec->is_active === 1 ? 'Open' : 'Closed'; ?></span>
                    </div>
                    <div style="display:flex; gap:12px; font-size:0.72rem; color:#64748B; flex-wrap:wrap; align-items:center;">
                      <span><?php echo (int) $s['students']; ?> students</span>
                      <span><?php echo (int) $s['classes']; ?> classes</span>
                      <?php if (isset($sec->building_name) && $sec->building_name): ?>
                        <span title="Home building" style="display:inline-flex; align-items:center; gap:4px; background:#F0FDFA; border:1px solid #99F6E4; color:#0F766E; border-radius:7px; padding:2px 8px; font-weight:600;">
                          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg>
                          <?php echo html_escape($sec->building_name); ?>
                        </span>
                      <?php else: ?>
                        <span title="Set this section's Home Building so its classes can be assigned to rooms" style="display:inline-flex; align-items:center; gap:4px; background:#FEF3C7; border:1px solid #FDE68A; color:#B45309; border-radius:7px; padding:2px 8px; font-weight:600;">
                          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                          No Home Building
                        </span>
                      <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:4px;">
                      <a class="icon-btn icon-btn--edit" href="<?php echo site_url('academic/sections?edit=' . (int) $sec->id); ?>" aria-label="Edit" title="Edit" onclick="openModal('secModal')" style="width:28px;height:28px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                      </a>
                      <?php echo form_open('academic/sections/status/' . (int) $sec->id, array('style' => 'display:inline;')); ?>
                        <?php if ((int) $sec->is_active === 1): ?>
                          <button type="submit" class="btn-secondary" style="padding:4px 8px;font-size:0.65rem;border-radius:6px;border:1px solid #CBD5E1;cursor:pointer;color:#B45309;" title="Close for enrollment — students can no longer enroll into this section; its data is kept." onclick="return confirm('Close this section for enrollment? Students can no longer enroll into it (its data is kept). You can reopen it anytime.');">Close</button>
                          <input type="hidden" name="status" value="inactive">
                        <?php else: ?>
                          <button type="submit" class="btn-secondary" style="padding:4px 8px;font-size:0.65rem;border-radius:6px;border:1px solid #CBD5E1;cursor:pointer;color:#0D9488;" title="Open for enrollment" onclick="return confirm('Open this section for enrollment again?');">Open</button>
                          <input type="hidden" name="status" value="active">
                        <?php endif; ?>
                      <?php echo form_close(); ?>
                    </div>
                    <!-- Expanded detail (classes) -->
                    <div class="section-detail" id="section-detail-<?php echo (int) $sec->id; ?>" style="display:none; border-top:1px solid #E2E8F0; padding-top:8px;">
                      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <span style="font-size:0.72rem; font-weight:600; color:#334155;">Classes</span>
                        <?php echo form_open('academic/sections/sync/' . (int) $sec->id, array('style' => 'display:inline;')); ?>
                          <button type="submit" class="btn-secondary" style="padding:3px 8px;font-size:0.65rem;border-radius:6px;cursor:pointer;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                            Sync
                          </button>
                        <?php echo form_close(); ?>
                      </div>
                      <?php $subs = isset($section_subjects[(int) $sec->id]) ? $section_subjects[(int) $sec->id] : array(); ?>
                      <?php if (empty($subs)): ?>
                        <p style="font-size:0.72rem; color:#94A3B8; margin:0;">No subjects linked yet.</p>
                      <?php else: ?>
                        <?php foreach ($subs as $sub): ?>
                          <?php
                          $has_teacher  = ! empty($sub['teacher_name']);
                          $has_schedule = $has_teacher
                              && $sub['assignment_id'] !== NULL
                              && $sub['day_bits'] !== NULL
                              && $sub['start_min'] !== NULL
                              && $sub['end_min'] !== NULL;
                          $room_label = '';
                          if ($has_schedule)
                          {
                              $room_label = trim(($sub['building_name'] ? $sub['building_name'] . ' · ' : '') . ($sub['room_name'] ? $sub['room_name'] : ''));
                          }
                          ?>
                          <div style="padding:6px 0; border-bottom:1px solid #F1F5F9;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                              <div style="min-width:0;">
                                <span style="font-family:monospace;font-weight:700;color:#0D9488;font-size:0.7rem;"><?php echo html_escape($sub['code']); ?></span>
                                <span style="font-size:0.7rem; color:#64748B; margin-left:4px;"><?php echo html_escape($sub['title']); ?></span>
                              </div>
                              <button type="button" class="assign-teacher-btn" style="border:none;background:none;padding:0;cursor:pointer;flex-shrink:0;"
                                data-section-id="<?php echo (int) $sec->id; ?>"
                                data-subject-id="<?php echo (int) $sub['subject_id']; ?>"
                                data-semester-id="<?php echo (int) $sub['semester_id']; ?>"
                                data-teacher-id="<?php echo $sub['teacher_user_id'] !== NULL ? (int) $sub['teacher_user_id'] : ''; ?>"
                                data-teacher-name="<?php echo html_escape($has_teacher ? $sub['teacher_name'] : ''); ?>"
                                data-subject-label="<?php echo html_escape($sub['code'] . ' — ' . $sub['title']); ?>"
                                data-section-name="<?php echo html_escape($sec->name); ?>">
                                <span class="<?php echo $has_teacher ? 'badge badge-success' : 'badge badge-neutral'; ?>" style="font-size:0.65rem;white-space:nowrap;">
                                  <?php echo $has_teacher ? html_escape($sub['teacher_name']) : 'No teacher'; ?>
                                </span>
                              </button>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap;">
                              <?php if ( ! $has_teacher): ?>
                                <span style="font-size:0.68rem; color:#94A3B8; display:inline-flex; align-items:center; gap:4px;">
                                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                  Assign a teacher first to schedule this class
                                </span>
                              <?php else: ?>
                                <button type="button" class="schedule-class-btn"
                                  data-assignment-id="<?php echo (int) $sub['assignment_id']; ?>"
                                  data-teacher-id="<?php echo $sub['teacher_user_id'] !== NULL ? (int) $sub['teacher_user_id'] : ''; ?>"
                                  data-room-id="<?php echo $sub['room_id'] !== NULL ? (int) $sub['room_id'] : ''; ?>"
                                  data-day-bits="<?php echo $sub['day_bits'] !== NULL ? (int) $sub['day_bits'] : ''; ?>"
                                  data-start-min="<?php echo $sub['start_min'] !== NULL ? (int) $sub['start_min'] : ''; ?>"
                                  data-end-min="<?php echo $sub['end_min'] !== NULL ? (int) $sub['end_min'] : ''; ?>"
                                  data-schedule-text="<?php echo html_escape($has_schedule && $sub['schedule_text'] ? $sub['schedule_text'] : ''); ?>"
                                  data-room-label="<?php echo html_escape($room_label); ?>"
                                  data-class-label="<?php echo html_escape($sub['code'] . ' — ' . $sub['title']); ?>"
                                  data-section-name="<?php echo html_escape($sec->name); ?>"
                                  data-section-building="<?php echo $sec->building_id !== NULL ? (int) $sec->building_id : ''; ?>"
                                  style="border:none;background:none;padding:0;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                                  <?php if ($has_schedule): ?>
                                    <span class="badge badge-student" style="font-size:0.65rem;white-space:nowrap;">
                                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                      <?php echo html_escape($sub['schedule_text']); ?><?php echo $room_label ? ' · ' . html_escape($room_label) : ''; ?>
                                    </span>
                                  <?php else: ?>
                                    <span class="badge badge-warning" style="font-size:0.65rem;white-space:nowrap;">No schedule yet</span>
                                  <?php endif; ?>
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <div style="margin-top:6px;">
                        <?php echo form_open('academic/sections/delete/' . (int) $sec->id, array('style' => 'display:inline;')); ?>
                          <button type="submit" class="btn-danger" style="font-size:0.65rem;padding:3px 8px;" onclick="return confirm('Delete this section?');">Delete Section</button>
                        <?php echo form_close(); ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Add/Edit Section Modal -->
  <div class="modal-overlay<?php echo ($edit ? ' visible' : ''); ?>" id="secModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;"><?php echo $edit ? 'Edit Section' : 'Add Section'; ?></h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('secModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      $form_action = $edit ? 'academic/sections/update/' . (int) $edit->id : 'academic/sections/store';
      echo form_open($form_action, array('novalidate' => 'novalidate'));
      ?>
        <div class="form-group">
          <label class="form-label" for="secName">Section Name</label>
          <input class="form-input" id="secName" name="name" placeholder="11-STEM-1" value="<?php echo html_escape($edit ? $edit->name : ''); ?>" required>
          <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Auto-fills as "11-STEM-1" when you pick a strand and grade — you can edit it freely. Letters, numbers and dashes.</div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label" for="secProgram">Strand</label>
            <select class="form-input" id="secProgram" name="program_id" required>
              <option value="">Select strand...</option>
              <?php foreach ($programs as $p): ?>
                <option value="<?php echo (int) $p->id; ?>" <?php echo ($edit && (int) $edit->program_id === (int) $p->id) ? 'selected' : ''; ?>><?php echo html_escape($p->program_code . ' — ' . $p->program_name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="secYear">Grade Level</label>
            <select class="form-input" id="secYear" name="year_level" required>
              <option value="">Select grade...</option>
              <option value="11" <?php echo ($edit && (int) $edit->year_level === 11) ? 'selected' : ''; ?>>Grade 11</option>
              <option value="12" <?php echo ($edit && (int) $edit->year_level === 12) ? 'selected' : ''; ?>>Grade 12</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="secBuilding">Home Building <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="secBuilding" name="building_id" required>
            <option value="">Select a building...</option>
            <?php foreach ($buildings_all as $b): ?>
              <?php if ( ! (int) $b->is_active) { continue; } ?>
              <option value="<?php echo (int) $b->id; ?>" <?php echo ($edit && (int) $edit->building_id === (int) $b->id) ? 'selected' : ''; ?>><?php echo html_escape($b->name); ?></option>
            <?php endforeach; ?>
          </select>
          <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">The building where this section is based. Its classes can <strong>only</strong> be assigned to rooms in this building. Don't see the building you need? Create it in Rooms &amp; Buildings first.</div>
        </div>
        <div class="text-faint" style="font-size:0.75rem; margin-top:2px;">Creating a section auto-populates its subjects from the curriculum for the active semester.</div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('secModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;"><?php echo $edit ? 'Save Changes' : 'Create Section'; ?></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <script type="application/json" id="secAutoData">
  <?php
  $auto_programs = array();
  foreach ($programs as $p)
  {
      $auto_programs[] = array(
          'id'          => (int) $p->id,
          'program_code'=> $p->program_code,
          'short_code'  => $p->short_code,
      );
  }
  $auto_sections = array();
  foreach ($rows as $sec)
  {
      $auto_sections[] = array(
          'name'       => $sec->name,
          'program_id' => $sec->program_id !== NULL ? (int) $sec->program_id : NULL,
          'year_level' => $sec->year_level !== NULL ? (int) $sec->year_level : NULL,
      );
  }
  echo json_encode(array('programs' => $auto_programs, 'sections' => $auto_sections));
  ?>
  </script>

  <!-- Assign Teacher Modal -->
  <div class="modal-overlay" id="assignTeacherModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;">Assign Teacher</h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('assignTeacherModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <p style="margin:0 0 16px; line-height:1.5; font-size:0.85rem; color:#334155;">
        <strong id="atSubjectLabel" style="color:#0D9488; font-family:monospace; font-size:0.82rem;"></strong><br>
        <span id="atSectionLabel" class="text-faint"></span>
      </p>
      <div class="form-group">
        <label class="form-label" for="atSearch">Search teacher by name</label>
        <input class="form-input" id="atSearch" type="text" placeholder="Type to filter..." autocomplete="off">
      </div>
      <div class="form-group">
        <label class="form-label" for="atTeacher">Teacher</label>
        <select class="form-input" id="atTeacher" size="6" style="height:auto;"></select>
        <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Only active teachers are listed.</div>
      </div>
      <div id="atError" style="display:none; background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; margin-top:12px; border-radius:10px; padding:10px 14px; font-size:0.82rem;"></div>
      <div style="display:flex; gap:12px; margin-top:20px;">
        <button type="button" class="btn-danger" id="atRemoveBtn" style="flex:1; display:none;">Remove Teacher</button>
        <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('assignTeacherModal')">Cancel</button>
        <button type="button" class="btn-primary" style="flex:1;" id="atConfirmBtn">Assign Teacher</button>
      </div>
    </div>
  </div>

  <?php echo form_open('academic/sections/assign_teacher', array('id' => 'assignTeacherForm', 'style' => 'display:none;')); ?>
    <input type="hidden" name="section_id" id="atSectionId" value="">
    <input type="hidden" name="subject_id" id="atSubjectId" value="">
    <input type="hidden" name="teacher_user_id" id="atTeacherId" value="">
  <?php echo form_close(); ?>

  <?php echo form_open('academic/sections/remove_teacher', array('id' => 'removeTeacherForm', 'style' => 'display:none;')); ?>
    <input type="hidden" name="section_id" id="rtSectionId" value="">
    <input type="hidden" name="subject_id" id="rtSubjectId" value="">
  <?php echo form_close(); ?>

  <script type="application/json" id="assignTeacherData">
  <?php
  $at_teachers = array();
  foreach ($active_teachers as $t)
  {
      $at_teachers[] = array(
          'id'   => (int) $t->id,
          'name' => $t->first_name . ' ' . $t->last_name,
      );
  }
  echo json_encode($at_teachers);
  ?>
  </script>

  <!-- Rooms + occupancy data for the schedule modal (active rooms only) -->
  <script type="application/json" id="scheduleData">
  <?php
  $sd_buildings = array();
  foreach ($rooms_grouped as $building_id => $room_list)
  {
      $rooms_arr = array();
      foreach ($room_list as $rr)
      {
          $rooms_arr[] = array(
              'id'   => (int) $rr->id,
              'name' => $rr->name,
          );
      }
      $sd_buildings[] = array(
          'id'    => (int) $building_id,
          'name'  => $rr->building_name,
          'rooms' => $rooms_arr,
      );
  }
  $sd_occupancy = array();
  foreach ($room_occupancy as $room_id => $slots)
  {
      $sd_occupancy[(int) $room_id] = $slots;
  }
  echo json_encode(array(
      'buildings' => $sd_buildings,
      'occupancy' => $sd_occupancy,
      'day_bits'  => array(1 => 'Mon', 2 => 'Tue', 4 => 'Wed', 8 => 'Thu', 16 => 'Fri'),
  ));
  ?>
  </script>

  <!-- Schedule Class Modal -->
  <div class="modal-overlay" id="scheduleModal">
    <div class="modal" style="max-width:560px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0;">Class Schedule</h3>
          <p style="margin:4px 0 0; line-height:1.4; font-size:0.82rem; color:#334155;">
            <strong id="schClassLabel" style="color:#0D9488; font-family:monospace; font-size:0.8rem;"></strong><br>
            <span id="schSectionLabel" class="text-faint"></span>
          </p>
        </div>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('scheduleModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="form-group">
        <label class="form-label" for="schRoom">Room <span style="color:#EF4444;">*</span></label>
        <select class="form-input" id="schRoom" required>
          <option value="">Select a room...</option>
        </select>
        <div class="text-faint" style="font-size:0.72rem;margin-top:4px;">Only active rooms in this section's <strong>Home Building</strong> are listed — a section can only be assigned to rooms in its own building.</div>
      </div>

      <div id="schRoomOccupancy" style="display:none; margin-bottom:16px;"></div>

      <div class="form-group">
        <label class="form-label">Days <span style="color:#EF4444;">*</span></label>
        <div style="display:flex; gap:6px; flex-wrap:wrap;" id="schDays">
          <button type="button" class="day-pill" data-bit="1">Mon</button>
          <button type="button" class="day-pill" data-bit="2">Tue</button>
          <button type="button" class="day-pill" data-bit="4">Wed</button>
          <button type="button" class="day-pill" data-bit="8">Thu</button>
          <button type="button" class="day-pill" data-bit="16">Fri</button>
        </div>
        <div class="text-faint" style="font-size:0.72rem;margin-top:4px;">Pick every weekday this class meets. Selecting multiple days is allowed.</div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label" for="schStart">Starts <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="schStart" required></select>
        </div>
        <div class="form-group">
          <label class="form-label" for="schEnd">Ends <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="schEnd" required></select>
          <div class="text-faint" style="font-size:0.72rem;margin-top:4px;">1–3 hours per session, 6:00 AM – 8:00 PM.</div>
        </div>
      </div>

      <div id="schConflict" style="display:none; background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; margin-bottom:16px; border-radius:10px; padding:10px 14px; font-size:0.8rem; line-height:1.5;"></div>

      <div style="display:flex; gap:12px; margin-top:4px;">
        <button type="button" class="btn-danger" id="schRemoveBtn" style="flex:1; display:none;">Remove Schedule</button>
        <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('scheduleModal')">Cancel</button>
        <button type="button" class="btn-primary" style="flex:1;" id="schSaveBtn">Save Schedule</button>
      </div>
    </div>
  </div>

  <?php echo form_open('academic/sections/schedule_save', array('id' => 'scheduleSaveForm', 'style' => 'display:none;')); ?>
    <input type="hidden" name="assignment_id" id="schAssignmentId" value="">
    <input type="hidden" name="room_id" id="schRoomId" value="">
    <input type="hidden" name="day_bits" id="schDayBits" value="">
    <input type="hidden" name="start_min" id="schStartMin" value="">
    <input type="hidden" name="end_min" id="schEndMin" value="">
  <?php echo form_close(); ?>

  <?php echo form_open('academic/sections/schedule_remove', array('id' => 'scheduleRemoveForm', 'style' => 'display:none;')); ?>
    <input type="hidden" name="assignment_id" id="schRemoveAssignmentId" value="">
  <?php echo form_close(); ?>

  <script>
  // Toggle strand group accordion
  function toggleStrandGroup(header) {
    var group = header.closest('.strand-group');
    var body = group.querySelector('.strand-body');
    var caret = header.querySelector('.strand-caret');
    if (body.style.display === 'none' || body.style.display === '') {
      body.style.display = 'block';
      if (caret) caret.style.transform = 'rotate(180deg)';
      header.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04)';
    } else {
      body.style.display = 'none';
      if (caret) caret.style.transform = 'rotate(0deg)';
      header.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04)';
    }
  }
  // Toggle section detail (classes)
  function toggleSectionDetail(id) {
    var el = document.getElementById('section-detail-' + id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
  }
  (function () {
    var semName = <?php echo json_encode($active_sem ? $active_sem->name : ''); ?>;
    var teachers = [];
    var dataEl = document.getElementById('assignTeacherData');
    if (dataEl) { try { teachers = JSON.parse(dataEl.textContent) || []; } catch (e) { teachers = []; } }

    var assignForm   = document.getElementById('assignTeacherForm');
    var removeForm   = document.getElementById('removeTeacherForm');
    var confirmBtn   = document.getElementById('atConfirmBtn');
    var removeBtn    = document.getElementById('atRemoveBtn');
    var errorBox     = document.getElementById('atError');
    var currentBtn   = null;
    var busy         = false;

    function csrf() {
      // CI3 injects a hidden CSRF input into forms rendered by form_open().
      if (!assignForm) { return null; }
      for (var i = 0; i < assignForm.elements.length; i++) {
        var el = assignForm.elements[i];
        if (el.name && el.name.indexOf('csrf') === 0 && el.value) {
          return { name: el.name, value: el.value };
        }
      }
      return null;
    }

    function refreshCsrf(payload) {
      if (!payload || !payload.csrf_token_name || payload.csrf_hash === undefined) { return; }
      var forms = [assignForm, removeForm];
      for (var f = 0; f < forms.length; f++) {
        if (!forms[f]) { continue; }
        var input = forms[f].querySelector('input[name="' + payload.csrf_token_name + '"]');
        if (input) { input.value = payload.csrf_hash; }
      }
    }

    function showError(message) {
      errorBox.textContent = message;
      errorBox.style.display = 'block';
    }
    function clearError() {
      errorBox.style.display = 'none';
      errorBox.textContent = '';
    }

    // Update ONLY the clicked subject card with the new teacher state.
    function updateCard(teacherId, teacherName) {
      if (!currentBtn) { return; }
      currentBtn.setAttribute('data-teacher-id', teacherId || '');
      currentBtn.setAttribute('data-teacher-name', teacherName || '');
      var badge = currentBtn.querySelector('span');
      if (!badge) { return; }
      if (teacherName) {
        badge.textContent = teacherName;
        badge.className = 'badge badge-success';
        badge.style.whiteSpace = 'nowrap';
        currentBtn.title = 'Change or remove the assigned teacher';
      } else {
        badge.textContent = 'No teacher assigned yet';
        badge.className = 'badge badge-neutral';
        badge.style.whiteSpace = 'nowrap';
        currentBtn.title = 'Assign a teacher to this class';
      }
    }

    function post(url, data, done) {
      var body = new FormData();
      body.append('section_id', document.getElementById('atSectionId').value);
      body.append('subject_id', document.getElementById('atSubjectId').value);
      for (var i = 0; i < data.length; i++) {
        body.append(data[i][0], data[i][1]);
      }
      var tok = csrf();
      if (tok) { body.append(tok.name, tok.value); }

      fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: body
      }).then(function (resp) {
        return resp.json().catch(function () { return null; }).then(function (payload) {
          done({ status: resp.status, payload: payload });
        });
      }).catch(function (err) {
        done({ status: 0, payload: null });
      });
    }

    function setBusy(flag) {
      busy = flag;
      confirmBtn.disabled = flag;
      removeBtn.disabled = flag;
    }

    function renderTeacherOptions(currentId) {
      var sel = document.getElementById('atTeacher');
      var q = (document.getElementById('atSearch').value || '').toLowerCase().trim();
      var html = '';
      for (var i = 0; i < teachers.length; i++) {
        var t = teachers[i];
        if (q && t.name.toLowerCase().indexOf(q) === -1) { continue; }
        html += '<option value="' + t.id + '"' + (String(t.id) === String(currentId) ? ' selected' : '') + '>' + t.name + '</option>';
      }
      sel.innerHTML = html;
    }

    var buttons = document.querySelectorAll('.assign-teacher-btn');
    for (var b = 0; b < buttons.length; b++) {
      buttons[b].addEventListener('click', function () {
        currentBtn = this;
        var teacherId   = this.getAttribute('data-teacher-id') || '';
        var teacherName = this.getAttribute('data-teacher-name') || '';

        document.getElementById('atSubjectLabel').textContent = this.getAttribute('data-subject-label');
        document.getElementById('atSectionLabel').textContent = this.getAttribute('data-section-name') + (semName ? ' · ' + semName : '');
        document.getElementById('atSectionId').value = this.getAttribute('data-section-id');
        document.getElementById('atSubjectId').value = this.getAttribute('data-subject-id');
        document.getElementById('rtSectionId').value = this.getAttribute('data-section-id');
        document.getElementById('rtSubjectId').value = this.getAttribute('data-subject-id');
        document.getElementById('atSearch').value = '';
        clearError();

        var hasTeacher = teacherName !== '';
        removeBtn.style.display = hasTeacher ? 'block' : 'none';
        confirmBtn.textContent = hasTeacher ? 'Save Changes' : 'Assign Teacher';
        removeBtn.disabled = false;
        confirmBtn.disabled = false;
        busy = false;

        renderTeacherOptions(teacherId);
        openModal('assignTeacherModal');
      });
    }

    var search = document.getElementById('atSearch');
    if (search) {
      search.addEventListener('input', function () {
        renderTeacherOptions(document.getElementById('atTeacher').value);
      });
    }

    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        if (busy) { return; }
        var teacherId = document.getElementById('atTeacher').value;
        if (!teacherId) {
          showError('Please select a teacher first.');
          return;
        }
        clearError();
        setBusy(true);
        post('<?php echo site_url('academic/sections/assign_teacher'); ?>',
          [['teacher_user_id', teacherId]],
          function (r) {
            var payload = r.payload || {};
            refreshCsrf(payload);
            setBusy(false);
            if (r.status === 200 && payload.ok) {
              updateCard(payload.teacher_id, payload.teacher_name);
              closeModal('assignTeacherModal');
            } else {
              showError(payload.message || 'Could not save the assignment. Please try again.');
            }
          });
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        if (busy) { return; }
        clearError();
        setBusy(true);
        post('<?php echo site_url('academic/sections/remove_teacher'); ?>', [], function (r) {
          var payload = r.payload || {};
          refreshCsrf(payload);
          setBusy(false);
          if (r.status === 200 && payload.ok) {
            updateCard(null, null);
            closeModal('assignTeacherModal');
          } else {
            showError(payload.message || 'Could not remove the teacher. Please try again.');
          }
        });
      });
    }
  })();
  </script>

  <!-- Class scheduling (room + day + time) with overlap = occupied blocking -->
  <script>
  (function () {
    var semName = <?php echo json_encode($active_sem ? $active_sem->name : ''); ?>;
    var DAY_TOKENS = { 1: 'Mon', 2: 'Tue', 4: 'Wed', 8: 'Thu', 16: 'Fri' };
    var TOKEN_TO_BIT = { Mon: 1, Tue: 2, Wed: 4, Thu: 8, Fri: 16 };
    var roomsByBuilding = [];   // [{id, name, rooms:[{id,name}]}]
    var occupancyByRoom = {};   // room_id -> [{day, start_min, end_min, section_name, subject_code, teacher_name}]
    var dataEl = document.getElementById('scheduleData');
    if (dataEl) {
      try {
        var parsed = JSON.parse(dataEl.textContent) || {};
        roomsByBuilding = parsed.buildings || [];
        occupancyByRoom = parsed.occupancy || {};
      } catch (e) { roomsByBuilding = []; occupancyByRoom = {}; }
    }

    var saveForm    = document.getElementById('scheduleSaveForm');
    var removeForm  = document.getElementById('scheduleRemoveForm');
    var roomSel     = document.getElementById('schRoom');
    var startSel    = document.getElementById('schStart');
    var endSel      = document.getElementById('schEnd');
    var occBox      = document.getElementById('schRoomOccupancy');
    var conflictBox = document.getElementById('schConflict');
    var saveBtn     = document.getElementById('schSaveBtn');
    var removeBtn   = document.getElementById('schRemoveBtn');
    var dayPills    = document.querySelectorAll('#schDays .day-pill');
    var currentAssignmentId = null;
    var currentTeacherId = 0;
    var currentSectionBuilding = 0;
    var currentBtn  = null;
    var busy        = false;
    var PILL_ON  = 'background:#0D9488;color:#fff;border:1px solid #0D9488;';
    var PILL_OFF = 'background:#fff;color:#475569;border:1px solid #CBD5E1;';
    var pillBase = 'border-radius:20px;padding:6px 14px;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.15s;';

    function fmt(min) {
      min = parseInt(min, 10) || 0;
      var h24 = Math.floor(min / 60);
      var m = min % 60;
      var ampm = h24 >= 12 ? 'PM' : 'AM';
      var h = h24 % 12; if (h === 0) { h = 12; }
      return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }

    function renderRoomOptions(selectedId) {
      var html = '<option value="">Select a room...</option>';
      var matched = false;
      for (var i = 0; i < roomsByBuilding.length; i++) {
        var b = roomsByBuilding[i];
        // A section belongs to its own building: only rooms in that building
        // are assignable. Sections without a Home Building can't be scheduled
        // at all (handled in openScheduleModal).
        if (currentSectionBuilding > 0 && (parseInt(b.id, 10) !== currentSectionBuilding)) { continue; }
        if (!b.rooms || !b.rooms.length) { continue; }
        matched = true;
        html += '<optgroup label="' + String(b.name).replace(/"/g, '&quot;') + '">';
        for (var j = 0; j < b.rooms.length; j++) {
          var r = b.rooms[j];
          html += '<option value="' + r.id + '"' + (String(r.id) === String(selectedId) ? ' selected' : '') + '>' + r.name + '</option>';
        }
        html += '</optgroup>';
      }
      roomSel.innerHTML = currentSectionBuilding > 0 && !matched ? '<option value="">No rooms in this building yet</option>' : html;
    }

    // Occupied time ranges in the currently selected room, restricted to the
    // currently selected days, excluding this class's own current slot.
    function blockedRanges() {
      var roomId = parseInt(roomSel.value, 10) || 0;
      var bits = selectedBits();
      var out = [];
      var slots = occupancyByRoom[roomId] || [];
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        if (isOwnSlot(s)) { continue; }
        if (bits & (TOKEN_TO_BIT[s.day] || 0)) { out.push({ start: s.start_min, end: s.end_min }); }
      }
      return out;
    }

    function timeBlocked(start, end, ranges) {
      for (var i = 0; i < ranges.length; i++) {
        if (ranges[i].start < end && ranges[i].end > start) { return true; }
      }
      return false;
    }

    function startUsable(m, ranges) {
      for (var add = 60; add <= 180; add += 30) {
        var e = m + add;
        if (e > 20 * 60) { continue; }
        if (!timeBlocked(m, e, ranges)) { return true; }
      }
      return false;
    }

    function renderStartOptions(selectedMin) {
      var ranges = blockedRanges();
      var html = '';
      for (var m = 6 * 60; m <= 19 * 60; m += 30) {
        var usable = startUsable(m, ranges);
        html += '<option value="' + m + '"' + (String(m) === String(selectedMin) ? ' selected' : '') + (usable ? '' : ' disabled') + '>' + fmt(m) + (usable ? '' : ' \u2014 blocked') + '</option>';
      }
      startSel.innerHTML = html;
    }

    // Only end-times that leave the room free on all selected days are offered.
    function renderEndOptions(selectedMin) {
      var start = parseInt(startSel.value, 10) || 0;
      var ranges = blockedRanges();
      var html = '';
      var any = false;
      for (var add = 60; add <= 180; add += 30) {
        var m = start + add;
        if (m > 20 * 60) { continue; }
        if (timeBlocked(start, m, ranges)) { continue; }
        any = true;
        html += '<option value="' + m + '"' + (String(m) === String(selectedMin) ? ' selected' : '') + '>' + fmt(m) + '</option>';
      }
      if (!any) {
        endSel.innerHTML = '<option value="">No free slot</option>';
      } else {
        endSel.innerHTML = html;
      }
    }

    function snapStartIfNeeded() {
      var cur = startSel.value;
      if (cur) {
        var opt = startSel.querySelector('option[value="' + cur + '"]');
        if (opt && !opt.disabled) { return; }
      }
      for (var i = 0; i < startSel.options.length; i++) {
        if (!startSel.options[i].disabled) { startSel.value = startSel.options[i].value; return; }
      }
    }

    function freeMinutes() {
      var roomId = parseInt(roomSel.value, 10) || 0;
      var slots = occupancyByRoom[roomId] || [];
      var perDay = { 1: [], 2: [], 4: [], 8: [], 16: [] };
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        if (isOwnSlot(s)) { continue; }
        var bit = TOKEN_TO_BIT[s.day] || 0;
        if (!perDay[bit]) { perDay[bit] = []; }
        perDay[bit].push({ start: s.start_min, end: s.end_min });
      }
      var total = 0;
      var dayStart = 6 * 60, dayEnd = 20 * 60;
      for (var b in perDay) {
        if (!perDay.hasOwnProperty(b)) { continue; }
        var rs = perDay[b].sort(function (a, c) { return a.start - c.start; });
        var merged = [];
        for (var j = 0; j < rs.length; j++) {
          var last = merged.length - 1;
          if (!merged.length || rs[j].start >= merged[last].end) { merged.push(rs[j]); }
          else if (rs[j].end > merged[last].end) { merged[last].end = rs[j].end; }
        }
        var booked = 0;
        for (var k = 0; k < merged.length; k++) {
          booked += Math.max(0, Math.min(merged[k].end, dayEnd) - Math.max(merged[k].start, dayStart));
        }
        total += (dayEnd - dayStart) - booked;
      }
      return total;
    }
    function freeLabel(min) {
      var h = Math.floor(min / 60), m = min % 60, parts = [];
      if (h > 0) { parts.push(h + 'h'); }
      if (m > 0) { parts.push(m + 'm'); }
      return parts.length ? parts.join(' ') : '0h';
    }

    function setPills(bits) {
      bits = parseInt(bits, 10) || 0;
      for (var i = 0; i < dayPills.length; i++) {
        var bit = parseInt(dayPills[i].getAttribute('data-bit'), 10);
        var on = (bits & bit) !== 0;
        dayPills[i].setAttribute('data-on', on ? '1' : '0');
        dayPills[i].style.cssText = pillBase + (on ? PILL_ON : PILL_OFF);
      }
    }
    function selectedBits() {
      var bits = 0;
      for (var i = 0; i < dayPills.length; i++) {
        var pill = dayPills[i];
        if (pill.getAttribute('data-on') === '1') { bits |= parseInt(pill.getAttribute('data-bit'), 10); }
      }
      return bits;
    }

    function togglePill(pill) {
      var on = pill.getAttribute('data-on') === '1';
      pill.setAttribute('data-on', on ? '0' : '1');
      pill.style.cssText = pillBase + (on ? PILL_OFF : PILL_ON);
      // Availability depends on which days are selected: re-filter the times.
      renderStartOptions(parseInt(startSel.value, 10) || 0);
      snapStartIfNeeded();
      renderEndOptions(parseInt(endSel.value, 10) || 0);
      checkConflict();
    }

    // Is this occupancy entry the class currently being edited? (Its own
    // current booking must not count as an "occupied" clash when updating.)
    function isOwnSlot(s) {
      if (!currentBtn) { return false; }
      if (s.section_name !== (currentBtn.getAttribute('data-section-name') || '')) { return false; }
      var code = currentSubjectCode();
      if (s.subject_code !== code) { return false; }
      var bits = parseInt(currentBtn.getAttribute('data-day-bits'), 10) || 0;
      var start = parseInt(currentBtn.getAttribute('data-start-min'), 10) || 0;
      var end = parseInt(currentBtn.getAttribute('data-end-min'), 10) || 0;
      return (TOKEN_TO_BIT[s.day] & bits) !== 0 && s.start_min === start && s.end_min === end;
    }

    function showRoomOccupancy() {
      var roomId = parseInt(roomSel.value, 10) || 0;
      var slots = (occupancyByRoom[roomId] || []).slice();
      if (!roomId) { occBox.style.display = 'none'; occBox.innerHTML = ''; return; }
      if (!slots.length) {
        occBox.innerHTML = '<div style="background:#F0FDFA;border:1px solid #99F6E4;color:#0F766E;border-radius:10px;padding:8px 12px;font-size:0.75rem;font-weight:500;">This room is currently free all week.</div>';
        occBox.style.display = 'block';
        return;
      }
      var order = { Mon: 0, Tue: 1, Wed: 2, Thu: 3, Fri: 4 };
      slots.sort(function (a, b) { return (order[a.day] - order[b.day]) || (a.start_min - b.start_min); });
      var chips = '';
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        chips += '<span style="display:inline-flex;align-items:center;gap:4px;background:' + (isOwnSlot(s) ? '#CCFBF1' : '#F1F5F9') + ';border:1px solid ' + (isOwnSlot(s) ? '#5EEAD4' : '#E2E8F0') + ';color:#475569;border-radius:8px;padding:3px 8px;font-size:0.7rem;">'
          + '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
          + s.day + ' ' + fmt(s.start_min) + '\u2013' + fmt(s.end_min)
          + ' <strong>' + (s.section_name || '') + ' \u00b7 ' + (s.subject_code || '') + '</strong>'
          + (s.teacher_name ? ' (' + s.teacher_name + ')' : '') + (isOwnSlot(s) ? ' <em>(this class)</em>' : '') + '</span>';
      }
      occBox.innerHTML = '<div style="margin-bottom:6px;font-size:0.72rem;font-weight:700;color:#64748B;">Booked in this room (' + slots.length + '):</div><div style="display:flex;flex-wrap:wrap;gap:4px;">' + chips + '</div>'
        + '<div style="margin-top:8px;font-size:0.72rem;color:#64748B;">Free for this class this week: <strong style="color:#0D9488;">' + freeLabel(freeMinutes()) + '</strong> <span style="color:#94A3B8;">(school hours, Mon\u2013Fri). Blocked times are greyed out below.</span></div>';
      occBox.style.display = 'block';
    }

    // Overlap = occupied: surface the clash (room or teacher) and let the
    // server re-check before saving.
    function checkConflict() {
      var roomId = parseInt(roomSel.value, 10) || 0;
      var bits = selectedBits();
      var start = parseInt(startSel.value, 10) || 0;
      var end = parseInt(endSel.value, 10) || 0;
      conflictBox.style.display = 'none';
      if (!roomId || !bits || !start || !end) { return; }
      var slots = occupancyByRoom[roomId] || [];
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        if (isOwnSlot(s)) { continue; }
        var sBit = TOKEN_TO_BIT[s.day] || 0;
        var overlapDay = (bits & sBit) !== 0;
        var overlapTime = s.start_min < end && s.end_min > start;
        if (overlapDay && overlapTime) {
          conflictBox.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
            + ' This room is <strong>already occupied</strong> ' + s.day + ' ' + fmt(s.start_min) + '\u2013' + fmt(s.end_min)
            + ' by ' + (s.section_name || 'another class') + ' \u00b7 ' + (s.subject_code || '') + '. Pick a free time slot.';
          conflictBox.style.display = 'block';
          return;
        }
      }
      // A teacher cannot be in two rooms at once.
      if (currentTeacherId > 0) {
        for (var rid in occupancyByRoom) {
          if (!occupancyByRoom.hasOwnProperty(rid)) { continue; }
          var tSlots = occupancyByRoom[rid];
          for (var t = 0; t < tSlots.length; t++) {
            var ts = tSlots[t];
            if (String(ts.teacher_user_id) !== String(currentTeacherId)) { continue; }
            if (isOwnSlot(ts)) { continue; }
            if ((bits & (TOKEN_TO_BIT[ts.day] || 0)) && ts.start_min < end && ts.end_min > start) {
              conflictBox.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                + ' This teacher is <strong>already teaching</strong> ' + ts.day + ' ' + fmt(ts.start_min) + '\u2013' + fmt(ts.end_min)
                + ' in ' + (ts.section_name || 'another section') + ' \u00b7 ' + (ts.subject_code || '') + '. A teacher cannot be in two rooms at once.';
              conflictBox.style.display = 'block';
              return;
            }
          }
        }
      }
    }

    function csrfFrom(form) {
      if (!form) { return null; }
      for (var i = 0; i < form.elements.length; i++) {
        var el = form.elements[i];
        if (el.name && el.name.indexOf('csrf') === 0 && el.value) { return { name: el.name, value: el.value }; }
      }
      return null;
    }
    function refreshCsrf(payload) {
      if (!payload || !payload.csrf_token_name || payload.csrf_hash === undefined) { return; }
      var forms = [saveForm, removeForm];
      for (var f = 0; f < forms.length; f++) {
        if (!forms[f]) { continue; }
        var input = forms[f].querySelector('input[name="' + payload.csrf_token_name + '"]');
        if (input) { input.value = payload.csrf_hash; }
      }
    }
    function post(url, data, done) {
      var body = new FormData();
      for (var i = 0; i < data.length; i++) { body.append(data[i][0], data[i][1]); }
      var tok = csrfFrom(saveForm) || csrfFrom(removeForm);
      if (tok) { body.append(tok.name, tok.value); }
      fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: body
      }).then(function (resp) {
        return resp.json().catch(function () { return null; }).then(function (payload) {
          done({ status: resp.status, payload: payload });
        });
      }).catch(function () { done({ status: 0, payload: null }); });
    }

    function refreshCardChip(payload, removed) {
      if (!currentBtn) { return; }
      var span = currentBtn.querySelector('span');
      if (!span) { return; }
      if (removed) {
        currentBtn.removeAttribute('data-room-id');
        currentBtn.removeAttribute('data-day-bits');
        currentBtn.removeAttribute('data-start-min');
        currentBtn.removeAttribute('data-end-min');
        span.className = 'badge badge-warning';
        span.innerHTML = 'No schedule yet';
        span.style.whiteSpace = 'nowrap';
      } else if (payload && payload.ok) {
        currentBtn.setAttribute('data-schedule-text', payload.schedule || '');
        currentBtn.setAttribute('data-room-label', payload.room_label || '');
        currentBtn.setAttribute('data-room-id', document.getElementById('schRoomId').value);
        currentBtn.setAttribute('data-day-bits', document.getElementById('schDayBits').value);
        currentBtn.setAttribute('data-start-min', document.getElementById('schStartMin').value);
        currentBtn.setAttribute('data-end-min', document.getElementById('schEndMin').value);
        var lbl = payload.schedule || 'No schedule yet';
        if (payload.room_label) { lbl += ' \u00b7 ' + payload.room_label; }
        span.className = 'badge badge-student';
        span.innerHTML = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' + lbl;
        span.style.whiteSpace = 'nowrap';
      }
    }

    function openScheduleModal(btn) {
      currentBtn = btn;
      currentAssignmentId = btn.getAttribute('data-assignment-id');
      currentTeacherId = parseInt(btn.getAttribute('data-teacher-id'), 10) || 0;
      currentSectionBuilding = parseInt(btn.getAttribute('data-section-building'), 10) || 0;
      var roomId = btn.getAttribute('data-room-id');
      var bits = btn.getAttribute('data-day-bits');
      var start = btn.getAttribute('data-start-min');
      var end = btn.getAttribute('data-end-min');
      var hasSchedule = !!(roomId && bits && start && end);

      document.getElementById('schClassLabel').textContent = btn.getAttribute('data-class-label');
      document.getElementById('schSectionLabel').textContent = btn.getAttribute('data-section-name') + ' \u00b7 ' + semName;
      document.getElementById('schAssignmentId').value = currentAssignmentId;
      document.getElementById('schRemoveAssignmentId').value = currentAssignmentId;
      document.getElementById('schRoomId').value = roomId || '';
      document.getElementById('schDayBits').value = bits || '';
      document.getElementById('schStartMin').value = start || '';
      document.getElementById('schEndMin').value = end || '';

      renderRoomOptions(hasSchedule ? roomId : 0);
      // Pills must be set before rendering times: blocked slots depend on the selected days.
      setPills(hasSchedule ? bits : 0);
      renderStartOptions(hasSchedule ? start : 8 * 60);
      snapStartIfNeeded();
      renderEndOptions(hasSchedule ? end : 9 * 60);
      removeBtn.style.display = hasSchedule ? 'block' : 'none';
      saveBtn.textContent = hasSchedule ? 'Update Schedule' : 'Save Schedule';
      busy = false;
      if ( ! currentSectionBuilding) {
        // A section without a Home Building cannot be scheduled anywhere.
        conflictBox.innerHTML = 'This section has no <strong>Home Building</strong> yet. Set its Home Building in Sections first — a section can only be assigned to rooms in its own building.';
        conflictBox.style.display = 'block';
        saveBtn.disabled = true;
      } else {
        saveBtn.disabled = false;
        conflictBox.style.display = 'none';
      }
      showRoomOccupancy();
      openModal('scheduleModal');
    }

    // The class being edited (before the update) - section + subject key.
    function ownKey(s) {
      if (!currentBtn) { return false; }
      return s.section_name === (currentBtn.getAttribute('data-section-name') || '')
        && s.subject_code === currentSubjectCode();
    }

    // Refresh the local occupancy snapshot after a successful save: drop this
    // class's previous slot for the new room (if any) and add the new booking.
    function addLocalOccupancy(roomId, bits, start, end) {
      var oldRoom = currentBtn ? parseInt(currentBtn.getAttribute('data-room-id'), 10) || 0 : 0;
      // Remove the class's old slot from its previous room on overlapping days.
      if (oldRoom && oldRoom !== roomId && occupancyByRoom[oldRoom]) {
        var oldBits = parseInt(currentBtn.getAttribute('data-day-bits'), 10) || 0;
        var oldStart = parseInt(currentBtn.getAttribute('data-start-min'), 10) || 0;
        var oldEnd = parseInt(currentBtn.getAttribute('data-end-min'), 10) || 0;
        for (var i = 0; i < occupancyByRoom[oldRoom].length; i++) {
          var os = occupancyByRoom[oldRoom][i];
          if (ownKey(os) && (TOKEN_TO_BIT[os.day] & oldBits) && os.start_min === oldStart && os.end_min === oldEnd) {
            occupancyByRoom[oldRoom].splice(i, 1); i--;
          }
        }
      }
      if (!occupancyByRoom[roomId]) { occupancyByRoom[roomId] = []; }
      for (var bit in DAY_TOKENS) {
        if (DAY_TOKENS.hasOwnProperty(bit) && (bits & parseInt(bit, 10)) !== 0) {
          var day = DAY_TOKENS[bit];
          // Drop this class's own booking on that day in this room, then re-add.
          for (var y = 0; y < occupancyByRoom[roomId].length; y++) {
            var o = occupancyByRoom[roomId][y];
            if (ownKey(o) && o.day === day) {
              occupancyByRoom[roomId].splice(y, 1); y--;
            }
          }
          occupancyByRoom[roomId].push({
            day: day,
            start_min: start,
            end_min: end,
            section_name: currentBtn ? (currentBtn.getAttribute('data-section-name') || '') : '',
            subject_code: currentSubjectCode(),
            teacher_name: ''
          });
        }
      }
    }
    function removeLocalOccupancy(roomId, bits, start, end) {
      if (!occupancyByRoom[roomId]) { return; }
      for (var i = 0; i < occupancyByRoom[roomId].length; i++) {
        var s = occupancyByRoom[roomId][i];
        if (ownKey(s) && (TOKEN_TO_BIT[s.day] & bits) && s.start_min === start && s.end_min === end) {
          occupancyByRoom[roomId].splice(i, 1); i--;
        }
      }
    }
    function currentSubjectCode() {
      if (!currentBtn) { return ''; }
      var raw = currentBtn.getAttribute('data-class-label') || '';
      return raw.split(' \u2014 ')[0] || raw;
    }

    var scheduleBtns = document.querySelectorAll('.schedule-class-btn');
    for (var i = 0; i < scheduleBtns.length; i++) {
      scheduleBtns[i].addEventListener('click', function () { openScheduleModal(this); });
    }

    for (var d = 0; d < dayPills.length; d++) {
      dayPills[d].addEventListener('click', function () { togglePill(this); });
    }

    roomSel.addEventListener('change', function () {
      renderStartOptions(parseInt(startSel.value, 10) || 0);
      snapStartIfNeeded();
      renderEndOptions(parseInt(endSel.value, 10) || 0);
      showRoomOccupancy();
      checkConflict();
    });
    startSel.addEventListener('change', function () {
      renderEndOptions(0);
      checkConflict();
    });
    endSel.addEventListener('change', function () { checkConflict(); });

    saveBtn.addEventListener('click', function () {
      if (busy) { return; }
      var roomId = parseInt(roomSel.value, 10) || 0;
      var bits = selectedBits();
      var start = parseInt(startSel.value, 10) || 0;
      var end = parseInt(endSel.value, 10) || 0;
      if (!roomId) { conflictBox.textContent = 'Please pick a room.'; conflictBox.style.display = 'block'; return; }
      if (!bits) { conflictBox.textContent = 'Pick at least one weekday.'; conflictBox.style.display = 'block'; return; }
      if (!start || !end || end <= start) { conflictBox.textContent = 'Pick a valid time range.'; conflictBox.style.display = 'block'; return; }
      document.getElementById('schRoomId').value = roomId;
      document.getElementById('schDayBits').value = bits;
      document.getElementById('schStartMin').value = start;
      document.getElementById('schEndMin').value = end;
      busy = true;
      saveBtn.disabled = true;
      post('<?php echo site_url('academic/sections/schedule_save'); ?>', [
        ['assignment_id', currentAssignmentId],
        ['room_id', roomId],
        ['day_bits', bits],
        ['start_min', start],
        ['end_min', end]
      ], function (r) {
        var payload = r.payload || {};
        refreshCsrf(payload);
        busy = false;
        saveBtn.disabled = false;
        if (r.status === 200 && payload.ok) {
          addLocalOccupancy(roomId, bits, start, end);
          refreshCardChip(payload, false);
          closeModal('scheduleModal');
        } else {
          conflictBox.innerHTML = (payload.message || 'Could not save the schedule. Please try again.');
          conflictBox.style.display = 'block';
        }
      });
    });

    removeBtn.addEventListener('click', function () {
      if (busy || !currentAssignmentId) { return; }
      busy = true;
      removeBtn.disabled = true;
      post('<?php echo site_url('academic/sections/schedule_remove'); ?>', [
        ['assignment_id', currentAssignmentId]
      ], function (r) {
        var payload = r.payload || {};
        refreshCsrf(payload);
        busy = false;
        removeBtn.disabled = false;
        if (r.status === 200 && payload.ok) {
          var roomId = parseInt(document.getElementById('schRoomId').value, 10) || 0;
          var bits = parseInt(document.getElementById('schDayBits').value, 10) || 0;
          var start = parseInt(document.getElementById('schStartMin').value, 10) || 0;
          var end = parseInt(document.getElementById('schEndMin').value, 10) || 0;
          removeLocalOccupancy(roomId, bits, start, end);
          refreshCardChip(null, true);
          closeModal('scheduleModal');
        } else {
          conflictBox.innerHTML = (payload.message || 'Could not remove the schedule. Please try again.');
          conflictBox.style.display = 'block';
        }
      });
    });
  })();
  </script>
<!-- END PAGE CONTENT -->