<?php
$buildings  = isset($buildings) ? $buildings : array();
$rooms      = isset($rooms) ? $rooms : array();
$occupancy  = isset($occupancy) ? $occupancy : array();
$grid_hours = isset($grid_hours) ? $grid_hours : array();
$day_tokens = isset($day_tokens) ? $day_tokens : array(1 => 'Mon', 2 => 'Tue', 4 => 'Wed', 8 => 'Thu', 16 => 'Fri');
$active_sem = isset($active_sem) ? $active_sem : NULL;
$stats      = isset($stats) ? $stats : array('buildings' => 0, 'rooms' => 0, 'booked_rooms' => 0);
$schedulable = isset($schedulable) ? $schedulable : array();

// Group schedulable classes by section for the assign picker, carrying each
// section's home building so we can restrict to "their own building".
$book_sections = array();
$sections_home = isset($sections_by_building) ? $sections_by_building : array();
$section_home_map = array();
foreach ($sections_home as $bid => $secs)
{
    foreach ($secs as $sec)
    {
        $section_home_map[(int) $sec->id] = array(
            'building_id'   => (int) $sec->building_id,
            'building_name' => $sec->building_name !== NULL ? $sec->building_name : '',
        );
    }
}
foreach ($schedulable as $cl)
{
    $sid = (int) $cl->section_id;
    if ( ! isset($book_sections[$sid]))
    {
        $home = isset($section_home_map[$sid]) ? $section_home_map[$sid] : array('building_id' => 0, 'building_name' => '');
        $book_sections[$sid] = array(
            'id'            => $sid,
            'name'          => $cl->section_name,
            'building_id'   => $home['building_id'],
            'building_name' => $home['building_name'],
            'classes'       => array(),
        );
    }
    $book_sections[$sid]['classes'][] = array(
        'assignment_id'   => (int) $cl->assignment_id,
        'subject'         => $cl->subject_code . ' — ' . $cl->subject_title,
        'teacher'         => $cl->teacher_name !== NULL ? $cl->teacher_name : '',
        'teacher_user_id' => (int) $cl->teacher_user_id,
    );
}

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');

if ( ! function_exists('_rooms_clock'))
{
    function _rooms_clock($minutes)
    {
        $minutes = (int) $minutes;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }
}

// Minutes still free in this room across Mon-Fri (school hours 6:00 AM - 8:00 PM).
if ( ! function_exists('_rooms_free_minutes'))
{
    function _rooms_free_minutes($slots)
    {
        $per_day = array();
        foreach ($slots as $s)
        {
            $per_day[$s['day']][] = array((int) $s['start_min'], (int) $s['end_min']);
        }
        $day_start = 6 * 60;
        $day_end   = 20 * 60;
        $total = 0;
        foreach ($per_day as $ranges)
        {
            usort($ranges, function ($a, $b) { return $a[0] - $b[0]; });
            $merged = array();
            foreach ($ranges as $r)
            {
                $last = count($merged) - 1;
                if ( ! $merged || $r[0] >= $merged[$last][1]) { $merged[] = $r; }
                elseif ($r[1] > $merged[$last][1]) { $merged[$last][1] = $r[1]; }
            }
            $booked = 0;
            foreach ($merged as $m)
            {
                $booked += max(0, min($m[1], $day_end) - max($m[0], $day_start));
            }
            $total += ($day_end - $day_start) - $booked;
        }
        return $total;
    }
}

if ( ! function_exists('_rooms_free_label'))
{
    function _rooms_free_label($minutes)
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        $parts = array();
        if ($h > 0) { $parts[] = $h . 'h'; }
        if ($m > 0) { $parts[] = $m . 'm'; }
        return $parts ? implode(' ', $parts) : '0h';
    }
}

// Rooms grouped by building for display (keeps active + inactive rooms visible;
// the pickers in the Sections page only show active ones via rooms_grouped_by_building).
$by_building = array();
foreach ($buildings as $b)
{
    $by_building[(int) $b->id] = array(
        'building' => $b,
        'rooms'    => array(),
    );
}
foreach ($rooms as $r)
{
    $bid = (int) $r->building_id;
    if ( ! isset($by_building[$bid]))
    {
        $by_building[$bid] = array('building' => NULL, 'rooms' => array());
    }
    $by_building[$bid]['rooms'][] = $r;
}

$day_list = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Rooms &amp; Buildings</h2>
      <p class="page-subtitle" style="font-size:0.95rem; color:#64748B;">Each building has rooms. A section belongs to one building, and its classes can only be assigned to rooms in that building.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <?php echo form_open('academic/rooms/demo', array('style' => 'display:inline;')); ?>
        <button type="submit" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; cursor:pointer; padding:9px 14px; font-size:0.85rem; font-weight:600;" onclick="return confirm('Create a Demo Building + Demo Room 101 and assign a sample class into it (if a teacher-assigned class exists), so you can see how assigning works?');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M18.36 5.64l-2.12 2.12M21 12h-3M18.36 18.36l-2.12-2.12M12 21v-3M5.64 18.36l2.12-2.12M3 12h3M5.64 5.64l2.12 2.12"/></svg>
          Load demo
        </button>
      <?php echo form_close(); ?>
      <button type="button" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; cursor:pointer; padding:9px 14px; font-size:0.85rem; font-weight:600;" onclick="openBuildingModal()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg>
        Add Building
      </button>
      <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; padding:9px 14px; font-size:0.85rem; font-weight:600;" onclick="openRoomModal()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
        Add Room
      </button>
    </div>
  </div>

  <!-- How it works -->
  <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:14px; padding:16px 20px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      <span style="font-size:0.95rem; font-weight:700; color:#1E40AF;">How assigning works</span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <span style="width:26px; height:26px; border-radius:50%; background:#2563EB; color:#fff; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">1</span>
        <div style="font-size:0.88rem; color:#1E40AF; line-height:1.55;">
          <strong>Set each section's Home Building</strong> — in Sections, every section must pick the building it belongs to.
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <span style="width:26px; height:26px; border-radius:50%; background:#2563EB; color:#fff; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">2</span>
        <div style="font-size:0.88rem; color:#1E40AF; line-height:1.55;">
          <strong>Click Assign on a room</strong> — pick a <strong>section from this building</strong>, its class, and a <strong>free</strong> day/time. Occupied times are greyed out automatically, and a teacher can never be in two rooms at once.
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <span style="width:26px; height:26px; border-radius:50%; background:#2563EB; color:#fff; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">3</span>
        <div style="font-size:0.88rem; color:#1E40AF; line-height:1.55;">
          <strong>Watch the weekly board update</strong> below the room. Try <strong>Load demo</strong> to see it pre-filled.
        </div>
      </div>
    </div>
  </div>

  <?php if ($flash_success): ?>
    <div style="background:#F0FDFA; border:1px solid #99F6E4; color:#0F766E; margin-bottom:16px; border-radius:12px; padding:12px 16px; font-size:0.88rem; font-weight:500; display:flex; align-items:center; gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?php echo html_escape($flash_success); ?>
    </div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; margin-bottom:16px; border-radius:12px; padding:12px 16px; font-size:0.88rem; font-weight:500; display:flex; align-items:center; gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?php echo html_escape($flash_error); ?>
    </div>
  <?php endif; ?>

  <!-- Stats Strip -->
  <div class="tile-strip" style="margin-bottom:24px;">
    <div class="tile" style="background:#EFF6FF; border-color:#BFDBFE; border-radius:14px; padding:18px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:44px; height:44px; border-radius:12px; background:#DBEAFE; display:flex; align-items:center; justify-content:center; color:#2563EB; flex-shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#1D4ED8; font-size:1.5rem;"><?php echo (int) $stats['buildings']; ?></div>
          <div class="tile-label" style="font-size:0.82rem;">Buildings</div>
        </div>
      </div>
    </div>
    <div class="tile" style="background:#F0FDFA; border-color:#99F6E4; border-radius:14px; padding:18px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:44px; height:44px; border-radius:12px; background:#CCFBF1; display:flex; align-items:center; justify-content:center; color:#0D9488; flex-shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#0F766E; font-size:1.5rem;"><?php echo (int) $stats['rooms']; ?></div>
          <div class="tile-label" style="font-size:0.82rem;">Total Rooms</div>
        </div>
      </div>
    </div>
    <div class="tile" style="background:#ECFDF5; border-color:#A7F3D0; border-radius:14px; padding:18px 20px; flex:1;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:44px; height:44px; border-radius:12px; background:#D1FAE5; display:flex; align-items:center; justify-content:center; color:#059669; flex-shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
        </div>
        <div>
          <div class="tile-value" style="color:#047857; font-size:1.5rem;"><?php echo (int) $stats['booked_rooms']; ?><span style="font-size:0.85rem; color:#64748B; margin-left:4px;">/ <?php echo (int) $stats['rooms']; ?></span></div>
          <div class="tile-label" style="font-size:0.82rem;">Rooms with classes <?php echo $active_sem ? '· ' . html_escape($active_sem->name) : ''; ?></div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($buildings)): ?>
    <div class="card" style="padding:56px; text-align:center;">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/></svg>
      <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0 0 10px;">No buildings yet</h3>
      <p class="text-faint" style="font-size:0.92rem; margin:0 0 18px;">Add a building (e.g. Main Building, Science Lab), add rooms to it, then set each section's Home Building — or load the demo to see how assigning works.</p>
      <div style="display:flex; gap:10px; justify-content:center;">
        <?php echo form_open('academic/rooms/demo', array('style' => 'display:inline;')); ?>
          <button type="submit" class="btn-secondary" style="cursor:pointer; font-size:0.85rem; padding:9px 14px;" onclick="return confirm('Load demo data (sample building + room + assigned class)?');">Load demo</button>
        <?php echo form_close(); ?>
        <button type="button" class="btn-primary" style="border:none; border-radius:10px; cursor:pointer; font-size:0.85rem; padding:9px 14px;" onclick="openBuildingModal()">Add your first building</button>
      </div>
    </div>
  <?php else: ?>
    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:12px 18px;">
      <span style="font-size:0.85rem; font-weight:700; color:#64748B;">Legend</span>
      <span style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; color:#475569;"><span style="width:11px; height:11px; border-radius:50%; background:#10B981; display:inline-block;"></span> Has scheduled classes</span>
      <span style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; color:#475569;"><span style="width:11px; height:11px; border-radius:50%; background:#F59E0B; display:inline-block;"></span> Free this week</span>
      <span style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; color:#475569;"><span style="width:11px; height:11px; border-radius:50%; background:#CBD5E1; display:inline-block;"></span> Under maintenance</span>
    </div>
    <?php foreach ($by_building as $bid => $group): ?>
      <?php if ( ! $group['building']) { continue; } ?>
      <?php $b = $group['building']; $building_rooms = $group['rooms']; ?>
      <div class="card" style="margin-bottom:20px; overflow:hidden;">
        <div style="display:flex; align-items:center; gap:14px; padding:18px 22px; border-bottom:1px solid #F1F5F9; flex-wrap:wrap;">
          <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#0D9488,#0891B2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg>
          </div>
          <div style="flex:1; min-width:220px;">
            <div style="font-family:var(--font-display); font-weight:700; font-size:1.08rem; color:#1E293B; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
              <?php echo html_escape($b->name); ?>
            </div>
            <div style="font-size:0.88rem; color:#64748B; margin-top:3px; line-height:1.5;">
              <?php echo count($building_rooms); ?> room<?php echo count($building_rooms) !== 1 ? 's' : ''; ?>
              <?php
              $b_booked = 0;
              foreach ($building_rooms as $rr) { if ( ! empty($occupancy[(int) $rr->id])) { $b_booked++; } }
              if ($b_booked > 0) { echo ' · ' . $b_booked . ' scheduled'; }
              ?>
              <?php
              $b_sections = isset($sections_by_building[(int) $b->id]) ? $sections_by_building[(int) $b->id] : array();
              if ( ! empty($b_sections))
              {
                  $sec_names = array();
                  foreach ($b_sections as $bsec) { $sec_names[] = $bsec->name; }
                  echo ' · <strong style="color:#0D9488;">Sections:</strong> ' . html_escape(implode(', ', $sec_names));
              }
              ?>
            </div>
          </div>
          <div style="display:flex; gap:8px; flex-shrink:0; flex-wrap:wrap;">
            <button type="button" class="btn-secondary" style="padding:7px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600;" onclick="openRoomModal(<?php echo (int) $b->id; ?>)">+ Room</button>
            <button type="button" class="btn-secondary" style="padding:7px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:5px;" onclick="openBuildingModal(<?php echo (int) $b->id; ?>, '<?php echo html_escape($b->name, 'js'); ?>')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              Edit
            </button>
            <?php echo form_open('academic/rooms/buildings/delete/' . (int) $b->id, array('style' => 'display:inline;')); ?>
              <button type="submit" class="btn-secondary" style="padding:7px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600; background:#FEF2F2; color:#DC2626; border:1px solid #FECACA; display:inline-flex; align-items:center; gap:5px;" onclick="return confirm('Delete this building? Buildings that still contain rooms cannot be deleted.');">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                Delete
              </button>
            <?php echo form_close(); ?>
          </div>
        </div>

        <?php if (empty($building_rooms)): ?>
          <div style="padding:24px; text-align:center; font-size:0.88rem; color:#94A3B8;">No rooms in this building yet — click <strong>+ Room</strong> to add one.</div>
        <?php else: ?>
          <div style="padding:16px 20px; display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($building_rooms as $i => $r): ?>
              <?php $slots = isset($occupancy[(int) $r->id]) ? $occupancy[(int) $r->id] : array(); ?>
              <?php $maintenance = ! (int) $r->is_active; ?>
              <div style="border:1px solid <?php echo $maintenance ? '#E2E8F0' : ( ! empty($slots) ? '#CCFBF1' : '#FDE68A55'); ?>; border-radius:14px; padding:16px 18px; background:<?php echo $maintenance ? '#F8FAFC' : '#FFFFFF'; ?>;">
                <!-- Room header -->
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
                  <?php if ($maintenance): ?>
                    <div style="width:12px; height:12px; border-radius:50%; background:#CBD5E1; flex-shrink:0;"></div>
                  <?php elseif ( ! empty($slots)): ?>
                    <div style="width:12px; height:12px; border-radius:50%; background:#10B981; flex-shrink:0; box-shadow:0 0 0 4px rgba(16,185,129,0.15);"></div>
                  <?php else: ?>
                    <div style="width:12px; height:12px; border-radius:50%; background:#F59E0B; flex-shrink:0;"></div>
                  <?php endif; ?>
                  <div style="font-weight:700; color:#1E293B; font-size:1.02rem; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <?php echo html_escape($r->name); ?>
                    <?php if ($maintenance): ?>
                      <span class="badge badge-neutral" style="font-size:0.72rem;">Under Maintenance</span>
                    <?php endif; ?>
                  </div>
                  <div style="flex:1; min-width:120px;"></div>
                  <div style="font-size:0.85rem; color:#64748B;">
                    <?php if ($maintenance): ?>
                      <span style="color:#B45309; font-weight:600;">Hidden from scheduling</span>
                    <?php else: ?>
                      <?php echo count($slots); ?> booked slot<?php echo count($slots) !== 1 ? 's' : ''; ?> ·
                      <span style="color:#0D9488; font-weight:700;"><?php echo _rooms_free_label(_rooms_free_minutes($slots)); ?> free this week</span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Weekly occupancy board -->
                <?php
                $occ_map = array();
                foreach ($day_list as $d) { $occ_map[$d] = array(); foreach ($grid_hours as $gh) { $occ_map[$d][(int) $gh] = array(); } }
                foreach ($slots as $s)
                {
                    foreach ($grid_hours as $gh)
                    {
                        if ((int) $s['start_min'] < (int) $gh + 60 && (int) $s['end_min'] > (int) $gh)
                        {
                            $occ_map[$s['day']][(int) $gh][] = $s;
                        }
                    }
                }
                ?>
                <div style="overflow-x:auto; margin-bottom:12px;">
                  <table style="border-collapse:collapse; width:100%; table-layout:fixed; min-width:480px;">
                    <tr>
                      <td style="width:44px;"></td>
                      <?php foreach ($day_list as $d): ?>
                        <td style="text-align:center; font-size:0.78rem; font-weight:700; color:#475569; padding:2px 4px 6px;"><?php echo $d; ?></td>
                      <?php endforeach; ?>
                    </tr>
                    <?php foreach ($grid_hours as $gh): ?>
                      <tr>
                        <td style="font-size:0.7rem; color:#64748B; font-weight:600; padding:1px 8px 1px 0; text-align:right; white-space:nowrap;"><?php echo _rooms_clock($gh); ?></td>
                        <?php foreach ($day_list as $d): ?>
                          <?php $cell = $occ_map[$d][(int) $gh]; ?>
                          <td style="padding:1px 2px;">
                            <?php if ( ! empty($cell)): ?>
                              <div style="background:#0D9488; border-radius:5px; height:22px; cursor:default; box-shadow:inset 0 0 0 1px rgba(13,148,136,0.35);" title="<?php
                              $tits = array();
                              foreach ($cell as $cc) { $tits[] = $cc['day'] . ' ' . _rooms_clock($cc['start_min']) . '–' . _rooms_clock($cc['end_min']) . ' · ' . $cc['section_name'] . ' · ' . $cc['subject_code'] . ( ! empty($cc['teacher_name']) ? ' · ' . $cc['teacher_name'] : ''); }
                              echo html_escape(implode(' ', $tits));
                              ?>"></div>
                            <?php else: ?>
                              <div style="background:#F1F5F9; border-radius:5px; height:22px;"></div>
                            <?php endif; ?>
                          </td>
                        <?php endforeach; ?>
                      </tr>
                    <?php endforeach; ?>
                  </table>
                </div>

                <!-- Booking chips -->
                <?php if (empty($slots)): ?>
                  <div style="font-size:0.85rem; color:#64748B; margin-bottom:12px;">Free all week — click <strong>Assign</strong> to put this room to its first class.</div>
                <?php else: ?>
                  <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
                    <?php
                    usort($slots, function ($a, $b) {
                        $day_order = array('Mon' => 0, 'Tue' => 1, 'Wed' => 2, 'Thu' => 3, 'Fri' => 4);
                        $da = isset($day_order[$a['day']]) ? $day_order[$a['day']] : 9;
                        $db = isset($day_order[$b['day']]) ? $day_order[$b['day']] : 9;
                        if ($da !== $db) { return $da - $db; }
                        return $a['start_min'] - $b['start_min'];
                    });
                    foreach ($slots as $slot):
                    ?>
                      <span style="display:inline-flex; align-items:center; gap:5px; background:#F0FDFA; border:1px solid #99F6E4; color:#0F766E; border-radius:9px; padding:4px 10px; font-size:0.8rem; font-weight:600;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo html_escape($slot['day'] . ' ' . _rooms_clock($slot['start_min']) . '–' . _rooms_clock($slot['end_min'])); ?>
                        <span style="font-weight:500; color:#475569;">· <?php echo html_escape($slot['section_name'] . ' · ' . $slot['subject_code']); ?><?php if ( ! empty($slot['teacher_name'])): ?> · <?php echo html_escape($slot['teacher_name']); ?><?php endif; ?></span>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <!-- Actions -->
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; border-top:1px solid #F1F5F9; padding-top:12px;">
                  <?php if ( ! $maintenance): ?>
                    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; padding:8px 14px; font-size:0.83rem; border-radius:9px; border:none; cursor:pointer; font-weight:600;" onclick="openBookModal(<?php echo (int) $r->id; ?>, '<?php echo html_escape($r->name, 'js'); ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                      Assign
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn-secondary" style="display:flex; align-items:center; gap:5px; padding:8px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600;" onclick="openRoomModal(<?php echo (int) $r->building_id; ?>, <?php echo (int) $r->id; ?>, '<?php echo html_escape($r->name, 'js'); ?>')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    Edit
                  </button>
                  <?php echo form_open('academic/rooms/status/' . (int) $r->id, array('style' => 'display:inline;')); ?>
                    <?php if ( ! $maintenance): ?>
                      <button type="submit" class="btn-secondary" style="display:flex; align-items:center; gap:5px; padding:8px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600; background:#FEF3C7; color:#B45309; border:1px solid #FDE68A;" onclick="return confirm('Mark this room as under maintenance? It will be hidden from class scheduling until you mark it available again. Existing bookings are kept.');">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Maintenance
                      </button>
                      <input type="hidden" name="status" value="maintenance">
                    <?php else: ?>
                      <button type="submit" class="btn-secondary" style="display:flex; align-items:center; gap:5px; padding:8px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600; background:#DCFCE7; color:#15803D; border:1px solid #BBF7D0;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Mark Available
                      </button>
                      <input type="hidden" name="status" value="available">
                    <?php endif; ?>
                  <?php echo form_close(); ?>
                  <?php echo form_open('academic/rooms/delete/' . (int) $r->id, array('style' => 'display:inline;')); ?>
                    <button type="submit" class="btn-secondary" style="display:flex; align-items:center; gap:5px; padding:8px 12px; font-size:0.82rem; border-radius:9px; cursor:pointer; font-weight:600; background:#FEF2F2; color:#DC2626; border:1px solid #FECACA;" onclick="return confirm('Delete this room? Rooms that still have scheduled classes cannot be deleted.');">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                      Delete
                    </button>
                  <?php echo form_close(); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Building Modal -->
  <div class="modal-overlay" id="buildingModal">
    <div class="modal" style="max-width:460px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#1E293B; margin:0;" id="buildingModalTitle">Add Building</h3>
          <p style="font-size:0.88rem; color:#64748B; margin:6px 0 0;">Buildings group rooms that share a location.</p>
        </div>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('buildingModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      // Dynamic action set by JS: store (new) or update/{id} (edit).
      echo form_open('academic/rooms/buildings/store', array('id' => 'buildingForm', 'novalidate' => 'novalidate'));
      ?>
        <div class="form-group">
          <label class="form-label" for="buildingName" style="font-size:0.88rem;">Building Name <span style="color:#EF4444;">*</span></label>
          <input class="form-input" id="buildingName" name="name" placeholder="e.g. Main Building, Science Building" required>
        </div>
        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          <div style="font-size:0.85rem; color:#64748B; line-height:1.5;">Room names only need to be unique <strong>inside</strong> a building — e.g. Room 101 can exist in both Main and Annex.</div>
        </div>
        <div style="display:flex; gap:12px;">
          <button type="button" class="btn-secondary" style="flex:1; font-size:0.85rem; padding:9px;" onclick="closeModal('buildingModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1; font-size:0.85rem; padding:9px;" id="buildingModalSubmit">Add Building</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <!-- Room Modal -->
  <div class="modal-overlay" id="roomModal">
    <div class="modal" style="max-width:460px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#1E293B; margin:0;" id="roomModalTitle">Add Room</h3>
          <p style="font-size:0.88rem; color:#64748B; margin:6px 0 0;">Rooms receive the sections based in the same building.</p>
        </div>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('roomModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <?php
      // Dynamic action set by JS: store (new) or update/{id} (edit).
      echo form_open('academic/rooms/store', array('id' => 'roomForm', 'novalidate' => 'novalidate'));
      ?>
        <div class="form-group">
          <label class="form-label" for="roomBuilding" style="font-size:0.88rem;">Building <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="roomBuilding" name="building_id" required>
            <option value="">Select building...</option>
            <?php foreach ($buildings as $b): ?>
              <?php if ( ! (int) $b->is_active) { continue; } ?>
              <option value="<?php echo (int) $b->id; ?>"><?php echo html_escape($b->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="roomName" style="font-size:0.88rem;">Room Name <span style="color:#EF4444;">*</span></label>
          <input class="form-input" id="roomName" name="name" placeholder="e.g. Room 101, Physics Lab" required>
        </div>
        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
          <div style="font-size:0.85rem; color:#64748B; line-height:1.5;">Rooms that are temporarily unusable (e.g. under repair) can be marked <strong>Under Maintenance</strong> from the room list — they stay in the system with their history but disappear from class scheduling until marked available.</div>
        </div>
        <div style="display:flex; gap:12px;">
          <button type="button" class="btn-secondary" style="flex:1; font-size:0.85rem; padding:9px;" onclick="closeModal('roomModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1; font-size:0.85rem; padding:9px;" id="roomModalSubmit">Add Room</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <script>
  function openBuildingModal(id, name) {
    var title  = document.getElementById('buildingModalTitle');
    var submit = document.getElementById('buildingModalSubmit');
    var input  = document.getElementById('buildingName');
    var form   = document.getElementById('buildingForm');
    if (id) {
      title.textContent = 'Edit Building';
      submit.textContent = 'Save Changes';
      form.action = '<?php echo site_url('academic/rooms/buildings/update'); ?>/' + id;
      input.value = name || '';
    } else {
      title.textContent = 'Add Building';
      submit.textContent = 'Add Building';
      form.action = '<?php echo site_url('academic/rooms/buildings/store'); ?>';
      input.value = '';
    }
    openModal('buildingModal');
    input.focus();
  }
  function openRoomModal(buildingId, roomId, name) {
    var title  = document.getElementById('roomModalTitle');
    var submit = document.getElementById('roomModalSubmit');
    var bsel   = document.getElementById('roomBuilding');
    var input  = document.getElementById('roomName');
    var form   = document.getElementById('roomForm');
    if (roomId) {
      title.textContent = 'Edit Room';
      submit.textContent = 'Save Changes';
      form.action = '<?php echo site_url('academic/rooms/update'); ?>/' + roomId;
      if (bsel) bsel.value = String(buildingId);
      input.value = name || '';
    } else {
      title.textContent = 'Add Room';
      submit.textContent = 'Add Room';
      form.action = '<?php echo site_url('academic/rooms/store'); ?>';
      if (buildingId && bsel) bsel.value = String(buildingId);
      else if (bsel) bsel.value = '';
      input.value = '';
    }
    openModal('roomModal');
    input.focus();
  }
  </script>

  <?php
  // Rooms grouped by building (active only) for the assign modal picker.
  $book_rooms = array();
  foreach ($rooms as $rr)
  {
      if ( ! (int) $rr->is_active || ! (int) $rr->building_active) { continue; }
      $bid = (int) $rr->building_id;
      if ( ! isset($book_rooms[$bid]))
      {
          $book_rooms[$bid] = array('name' => $rr->building_name, 'rooms' => array());
      }
      $book_rooms[$bid]['rooms'][] = array('id' => (int) $rr->id, 'name' => $rr->name);
  }
  // Map room -> its building id for "own building" filtering.
  $room_building_map = array();
  foreach ($rooms as $rr)
  {
      $room_building_map[(int) $rr->id] = (int) $rr->building_id;
  }
  ?>
  <script type="application/json" id="bookData">
  <?php echo json_encode(array(
      'sections'  => array_values($book_sections),
      'rooms'     => array_values($book_rooms),
      'room_building' => $room_building_map,
      'occupancy' => $occupancy,
      'day_tokens'=> $day_tokens,
      'has_sections' => ! empty($schedulable),
  )); ?>
  </script>

  <!-- Assign a Section Modal -->
  <div class="modal-overlay" id="bookModal">
    <div class="modal" style="max-width:580px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
        <div>
          <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#1E293B; margin:0;">Assign Section to Room</h3>
          <p style="margin:6px 0 0; line-height:1.5; font-size:0.92rem; color:#334155;">Room: <strong id="bkRoomLabel" style="color:#0D9488;"></strong> — only sections based in this building can be assigned here.</p>
        </div>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('bookModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <?php if (empty($schedulable)): ?>
        <div style="background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; border-radius:10px; padding:14px 18px; font-size:0.88rem; line-height:1.6;">
          No classes are available to assign yet. A class only becomes assignable once a <strong>teacher is assigned</strong> to it in the active semester — do that from <a href="<?php echo site_url('academic/sections'); ?>" style="color:inherit; font-weight:700;">Sections</a>.
        </div>
      <?php else: ?>
        <div class="form-group">
          <label class="form-label" for="bkSection" style="font-size:0.88rem;">Section <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="bkSection" required style="font-size:0.92rem; padding:10px 12px;">
            <option value="">Select a section...</option>
          </select>
          <div class="text-faint" style="font-size:0.82rem;margin-top:5px;">Sections based in <strong>this building</strong> are listed. Sections with no Home Building must be given one in Sections before they can be assigned anywhere.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="bkClass" style="font-size:0.88rem;">Class <span style="color:#EF4444;">*</span></label>
          <select class="form-input" id="bkClass" required style="font-size:0.92rem; padding:10px 12px;">
            <option value="">Select a section first...</option>
          </select>
          <div class="text-faint" style="font-size:0.82rem;margin-top:5px;">Only classes with an assigned teacher in <?php echo $active_sem ? html_escape($active_sem->name) : 'the active semester'; ?> can be assigned.</div>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-size:0.88rem;">Days <span style="color:#EF4444;">*</span></label>
          <div style="display:flex; gap:8px; flex-wrap:wrap;" id="bkDays">
            <button type="button" class="day-pill" data-bit="1">Mon</button>
            <button type="button" class="day-pill" data-bit="2">Tue</button>
            <button type="button" class="day-pill" data-bit="4">Wed</button>
            <button type="button" class="day-pill" data-bit="8">Thu</button>
            <button type="button" class="day-pill" data-bit="16">Fri</button>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
          <div class="form-group">
            <label class="form-label" for="bkStart" style="font-size:0.88rem;">Starts <span style="color:#EF4444;">*</span></label>
            <select class="form-input" id="bkStart" required style="font-size:0.92rem; padding:10px 12px;"></select>
          </div>
          <div class="form-group">
            <label class="form-label" for="bkEnd" style="font-size:0.88rem;">Ends <span style="color:#EF4444;">*</span></label>
            <select class="form-input" id="bkEnd" required style="font-size:0.92rem; padding:10px 12px;"></select>
            <div class="text-faint" style="font-size:0.82rem;margin-top:5px;">1–3 hours, 6:00 AM – 8:00 PM. Occupied times are greyed out automatically.</div>
          </div>
        </div>

        <div id="bkOccupancy" style="display:none; margin-bottom:16px;"></div>
        <div id="bkConflict" style="display:none; background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; margin-bottom:16px; border-radius:10px; padding:12px 16px; font-size:0.88rem; line-height:1.6;"></div>

        <div style="display:flex; gap:12px;">
          <button type="button" class="btn-secondary" style="flex:1; font-size:0.88rem; padding:10px;" onclick="closeModal('bookModal')">Cancel</button>
          <button type="button" class="btn-primary" style="flex:1; font-size:0.88rem; padding:10px; border:none;" id="bkSaveBtn">Assign to Room</button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php echo form_open('academic/sections/schedule_save', array('id' => 'bookSaveForm', 'style' => 'display:none;')); ?>
    <input type="hidden" name="assignment_id" id="bkAssignmentId" value="">
    <input type="hidden" name="room_id" id="bkRoomId" value="">
    <input type="hidden" name="day_bits" id="bkDayBits" value="">
    <input type="hidden" name="start_min" id="bkStartMin" value="">
    <input type="hidden" name="end_min" id="bkEndMin" value="">
  <?php echo form_close(); ?>

  <script>
  (function () {
    if ( ! document.getElementById('bookSaveForm')) { return; }
    var DAY_TOKENS = { 1: 'Mon', 2: 'Tue', 4: 'Wed', 8: 'Thu', 16: 'Fri' };
    var TOKEN_TO_BIT = { Mon: 1, Tue: 2, Wed: 4, Thu: 8, Fri: 16 };
    var sections = [];
    var roomsByBuilding = [];
    var occupancyByRoom = {};
    var roomBuildingMap = {};
    try {
      var parsed = JSON.parse(document.getElementById('bookData').textContent) || {};
      sections = parsed.sections || [];
      roomsByBuilding = parsed.rooms || [];
      occupancyByRoom = parsed.occupancy || {};
      roomBuildingMap = parsed.room_building || {};
    } catch (e) {}
    var sectionsById = {};
    for (var si = 0; si < sections.length; si++) { sectionsById[sections[si].id] = sections[si]; }

    var saveForm    = document.getElementById('bookSaveForm');
    var sectionSel  = document.getElementById('bkSection');
    var classSel    = document.getElementById('bkClass');
    var roomLabel   = document.getElementById('bkRoomLabel');
    var startSel    = document.getElementById('bkStart');
    var endSel      = document.getElementById('bkEnd');
    var occBox      = document.getElementById('bkOccupancy');
    var conflictBox = document.getElementById('bkConflict');
    var saveBtn     = document.getElementById('bkSaveBtn');
    var dayPills    = document.querySelectorAll('#bkDays .day-pill');
    var bookRoomId  = 0;
    var busy        = false;
    var PILL_ON  = 'background:#0D9488;color:#fff;border:1px solid #0D9488;';
    var PILL_OFF = 'background:#fff;color:#475569;border:1px solid #CBD5E1;';
    var pillBase = 'border-radius:22px;padding:8px 16px;font-size:0.85rem;font-weight:600;cursor:pointer;transition:all 0.15s;';

    function fmt(min) {
      min = parseInt(min, 10) || 0;
      var h24 = Math.floor(min / 60);
      var m = min % 60;
      var ampm = h24 >= 12 ? 'PM' : 'AM';
      var h = h24 % 12; if (h === 0) { h = 12; }
      return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
    function selectedBits() {
      var bits = 0;
      for (var i = 0; i < dayPills.length; i++) {
        if (dayPills[i].getAttribute('data-on') === '1') { bits |= parseInt(dayPills[i].getAttribute('data-bit'), 10); }
      }
      return bits;
    }
    function selectedAssignmentId() { return parseInt(classSel.value, 10) || 0; }
    function selectedTeacherId() {
      var o = classSel.options[classSel.selectedIndex];
      return o ? parseInt(o.getAttribute('data-teacher'), 10) || 0 : 0;
    }
    // A section belongs to ONE building: only sections based in this room's
    // building are assignable here. Sections without a Home Building are shown
    // but disabled (they must be given a Home Building in Sections first).
    function renderSectionOptions(roomId) {
      var home = roomBuildingMap[roomId] || 0;
      var own = [], none = [], other = [];
      for (var i = 0; i < sections.length; i++) {
        var s = sections[i];
        var sb = parseInt(s.building_id, 10) || 0;
        if (sb === home && home > 0) { own.push(s); }
        else if (sb === 0) { none.push(s); }
        else { other.push(s); }
      }
      var html = '<option value="">Select a section...</option>';
      var groups = [];
      if (own.length) { groups.push(['Sections in this building', own, false]); }
      if (none.length) { groups.push(['Sections with no Home Building (set one in Sections first)', none, true]); }
      if (other.length) { groups.push(['Sections in another building (not assignable here)', other, true]); }
      for (var g = 0; g < groups.length; g++) {
        html += '<optgroup label="' + groups[g][0] + '">';
        var list = groups[g][1];
        var disabled = groups[g][2];
        for (var j = 0; j < list.length; j++) {
          var s = list[j];
          html += '<option value="' + s.id + '"' + (disabled ? ' disabled' : '') + ' data-building="' + (parseInt(s.building_id, 10) || 0) + '">'
            + String(s.name).replace(/\"/g, '&quot;')
            + (s.building_name ? ' — ' + String(s.building_name).replace(/\"/g, '&quot;') : '')
            + (disabled ? ' (not assignable)' : '') + '</option>';
        }
        html += '</optgroup>';
      }
      sectionSel.innerHTML = html;
      classSel.innerHTML = '<option value="">Select a section first...</option>';
    }
    function renderClassOptions() {
      var sid = parseInt(sectionSel.value, 10) || 0;
      var sec = sectionsById[sid];
      var html = '<option value="">Select a class...</option>';
      if (sec && sec.classes) {
        for (var i = 0; i < sec.classes.length; i++) {
          var cl = sec.classes[i];
          html += '<option value="' + cl.assignment_id + '" data-teacher="' + (parseInt(cl.teacher_user_id, 10) || 0) + '">'
            + String(cl.subject).replace(/\"/g, '&quot;')
            + (cl.teacher ? ' — ' + String(cl.teacher).replace(/\"/g, '&quot;') : '') + '</option>';
        }
      }
      classSel.innerHTML = html;
    }
    function blockedRanges() {
      var bits = selectedBits();
      var out = [];
      var slots = occupancyByRoom[bookRoomId] || [];
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
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
        html += '<option value="' + m + '"' + (String(m) === String(selectedMin) ? ' selected' : '') + (usable ? '' : ' disabled') + '>' + fmt(m) + (usable ? '' : ' — blocked') + '</option>';
      }
      startSel.innerHTML = html;
    }
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
      endSel.innerHTML = any ? html : '<option value="">No free slot</option>';
    }
    function snapStartIfNeeded() {
      var cur = startSel.value;
      var ok = false;
      for (var i = 0; i < startSel.options.length; i++) {
        if (!startSel.options[i].disabled) { if (!ok) { startSel.value = startSel.options[i].value; ok = true; } if (String(startSel.options[i].value) === String(cur)) { startSel.value = cur; return; } }
      }
      if (!ok) { startSel.value = ''; }
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
    function togglePill(pill) {
      var on = pill.getAttribute('data-on') === '1';
      pill.setAttribute('data-on', on ? '0' : '1');
      pill.style.cssText = pillBase + (on ? PILL_OFF : PILL_ON);
      renderStartOptions(parseInt(startSel.value, 10) || 0);
      snapStartIfNeeded();
      renderEndOptions(parseInt(endSel.value, 10) || 0);
      checkConflict();
    }
    function freeMinutes() {
      var slots = occupancyByRoom[bookRoomId] || [];
      var perDay = { 1: [], 2: [], 4: [], 8: [], 16: [] };
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        var bit = TOKEN_TO_BIT[s.day] || 0;
        if (!perDay[bit]) { perDay[bit] = []; }
        perDay[bit].push({ start: s.start_min, end: s.end_min });
      }
      var total = 0, dayStart = 6 * 60, dayEnd = 20 * 60;
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
        for (var k = 0; k < merged.length; k++) { booked += Math.max(0, Math.min(merged[k].end, dayEnd) - Math.max(merged[k].start, dayStart)); }
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
    function showRoomOccupancy() {
      var slots = occupancyByRoom[bookRoomId] || [];
      if (!slots.length) {
        occBox.innerHTML = '<div style="background:#F0FDFA;border:1px solid #99F6E4;color:#0F766E;border-radius:10px;padding:10px 14px;font-size:0.85rem;font-weight:500;">This room is currently free all week.</div>';
      } else {
        var order = { Mon: 0, Tue: 1, Wed: 2, Thu: 3, Fri: 4 };
        slots.slice().sort(function (a, b) { return (order[a.day] - order[b.day]) || (a.start_min - b.start_min); });
        var chips = '';
        for (var i = 0; i < slots.length; i++) {
          var s = slots[i];
          chips += '<span style="display:inline-flex;align-items:center;gap:5px;background:#F1F5F9;border:1px solid #E2E8F0;color:#475569;border-radius:9px;padding:4px 9px;font-size:0.8rem;">' + s.day + ' ' + fmt(s.start_min) + '–' + fmt(s.end_min) + ' <strong>' + (s.section_name || '') + ' · ' + (s.subject_code || '') + '</strong></span>';
        }
        occBox.innerHTML = '<div style="margin-bottom:6px;font-size:0.82rem;font-weight:700;color:#64748B;">Booked in this room (' + slots.length + '):</div><div style="display:flex;flex-wrap:wrap;gap:6px;">' + chips + '</div>'
          + '<div style="margin-top:8px;font-size:0.82rem;color:#64748B;">Free this week: <strong style="color:#0D9488;">' + freeLabel(freeMinutes()) + '</strong> — blocked times are greyed out below.</div>';
      }
      occBox.style.display = 'block';
    }
    function checkConflict() {
      var bits = selectedBits();
      var start = parseInt(startSel.value, 10) || 0;
      var end = parseInt(endSel.value, 10) || 0;
      conflictBox.style.display = 'none';
      if (!bookRoomId || !bits || !start || !end) { return; }
      var slots = occupancyByRoom[bookRoomId] || [];
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        if ((bits & (TOKEN_TO_BIT[s.day] || 0)) && s.start_min < end && s.end_min > start) {
          conflictBox.innerHTML = 'This room is <strong>already occupied</strong> ' + s.day + ' ' + fmt(s.start_min) + '–' + fmt(s.end_min) + ' by ' + (s.section_name || 'another class') + ' · ' + (s.subject_code || '') + '. Pick a free time slot.';
          conflictBox.style.display = 'block';
          return;
        }
      }
      var tid = selectedTeacherId();
      if (tid) {
        for (var rid in occupancyByRoom) {
          if (!occupancyByRoom.hasOwnProperty(rid)) { continue; }
          var tSlots = occupancyByRoom[rid];
          for (var t = 0; t < tSlots.length; t++) {
            var ts = tSlots[t];
            if (String(ts.teacher_user_id) !== String(tid)) { continue; }
            if ((bits & (TOKEN_TO_BIT[ts.day] || 0)) && ts.start_min < end && ts.end_min > start) {
              conflictBox.innerHTML = 'This teacher is <strong>already teaching</strong> ' + ts.day + ' ' + fmt(ts.start_min) + '–' + fmt(ts.end_min) + ' in ' + (ts.section_name || 'another section') + ' · ' + (ts.subject_code || '') + '. A teacher cannot be in two rooms at once.';
              conflictBox.style.display = 'block';
              return;
            }
          }
        }
      }
    }
    function csrfFrom() {
      for (var i = 0; i < saveForm.elements.length; i++) {
        var el = saveForm.elements[i];
        if (el.name && el.name.indexOf('csrf') === 0 && el.value) { return { name: el.name, value: el.value }; }
      }
      return null;
    }
    function post(url, data, done) {
      var body = new FormData();
      for (var i = 0; i < data.length; i++) { body.append(data[i][0], data[i][1]); }
      var tok = csrfFrom();
      if (tok) { body.append(tok.name, tok.value); }
      fetch(url, {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: body
      }).then(function (resp) {
        return resp.json().catch(function () { return null; }).then(function (payload) { done({ status: resp.status, payload: payload }); });
      }).catch(function () { done({ status: 0, payload: null }); });
    }

    function openBookModal(roomId, roomName) {
      bookRoomId = parseInt(roomId, 10) || 0;
      roomLabel.textContent = roomName || '';
      document.getElementById('bkRoomId').value = bookRoomId;
      document.getElementById('bkAssignmentId').value = '';
      document.getElementById('bkDayBits').value = '';
      document.getElementById('bkStartMin').value = '';
      document.getElementById('bkEndMin').value = '';
      renderSectionOptions(bookRoomId);
      renderClassOptions();
      setPills(0);
      renderStartOptions(8 * 60);
      renderEndOptions(9 * 60);
      showRoomOccupancy();
      conflictBox.style.display = 'none';
      saveBtn.disabled = false;
      busy = false;
      openModal('bookModal');
    }
    window.openBookModal = openBookModal;
    if (sectionSel) {
      sectionSel.addEventListener('change', function () {
        renderClassOptions();
        checkConflict();
      });
    }

    for (var d = 0; d < dayPills.length; d++) {
      dayPills[d].addEventListener('click', function () { togglePill(this); });
    }
    startSel.addEventListener('change', function () { renderEndOptions(0); checkConflict(); });
    endSel.addEventListener('change', function () { checkConflict(); });
    saveBtn.addEventListener('click', function () {
      if (busy) { return; }
      var assignmentId = selectedAssignmentId();
      if (!assignmentId) { conflictBox.textContent = 'Please pick a class.'; conflictBox.style.display = 'block'; return; }
      var bits = selectedBits();
      var start = parseInt(startSel.value, 10) || 0;
      var end = parseInt(endSel.value, 10) || 0;
      if (!bits) { conflictBox.textContent = 'Pick at least one weekday.'; conflictBox.style.display = 'block'; return; }
      if (!start || !end || end <= start) { conflictBox.textContent = 'Pick a valid time range.'; conflictBox.style.display = 'block'; return; }
      document.getElementById('bkAssignmentId').value = assignmentId;
      document.getElementById('bkDayBits').value = bits;
      document.getElementById('bkStartMin').value = start;
      document.getElementById('bkEndMin').value = end;
      busy = true;
      saveBtn.disabled = true;
      post('<?php echo site_url('academic/sections/schedule_save'); ?>', [
        ['assignment_id', assignmentId], ['room_id', bookRoomId], ['day_bits', bits], ['start_min', start], ['end_min', end]
      ], function (r) {
        busy = false;
        saveBtn.disabled = false;
        if (r.status === 200 && r.payload && r.payload.ok) {
          window.location.reload();
        } else {
          conflictBox.innerHTML = (r.payload && r.payload.message) || 'Could not save the schedule. Please try again.';
          conflictBox.style.display = 'block';
        }
      });
    });
  })();
  </script>
<!-- END PAGE CONTENT -->