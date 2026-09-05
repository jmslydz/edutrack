<?php
$student_name   = isset($student_name) ? $student_name : '';
$section_name   = isset($section_name) ? $section_name : '—';
$sy_label       = isset($sy_label) ? $sy_label : '—';
$semester_label = isset($semester_label) ? $semester_label : '—';
$student_no     = isset($student_no) ? $student_no : '—';
$schedule_data  = isset($schedule_data) ? $schedule_data : array();
$gwa            = isset($gwa) ? (float) $gwa : 0.0;
$honor_label    = isset($honor_label) ? $honor_label : 'Good Standing';
$honor_color    = isset($honor_color) ? $honor_color : '#64748B';
$total_units    = isset($total_units) ? $total_units : 0;
$ticket_counts  = isset($ticket_counts) ? $ticket_counts : array('Open' => 0, 'In Progress' => 0, 'Resolved' => 0);
$recent_tickets = isset($recent_tickets) ? $recent_tickets : array();
$is_enrolled_in_active = isset($is_enrolled_in_active) ? $is_enrolled_in_active : FALSE;

// ─── Schedule parser ───
function _parse_schedule($raw)
{
    $raw = trim($raw);
    if ($raw === '' || $raw === null) return array('days' => array(), 'time' => '', 'raw' => '');
    $days_map = array(
        'M' => 'Mon', 'Mon' => 'Mon', 'Monday' => 'Mon',
        'T' => 'Tue', 'Tue' => 'Tue', 'Tuesday' => 'Tue',
        'W' => 'Wed', 'Wed' => 'Wed', 'Wednesday' => 'Wed',
        'Th' => 'Thu', 'Thu' => 'Thu', 'Thursday' => 'Thu',
        'F' => 'Fri', 'Fri' => 'Fri', 'Friday' => 'Fri',
        'S' => 'Sat', 'Sat' => 'Sat', 'Saturday' => 'Sat',
    );
    $days = array();
    $time = $raw;
    if (preg_match('/^([A-Za-z]+)\s*(\d{1,2}:\d{2}(?:\s*(?:AM|PM))?\s*[-–]\s*\d{1,2}:\d{2}(?:\s*(?:AM|PM))?.*)$/iu', $raw, $m)) {
        $day_part = $m[1];
        $time = trim($m[2]);
    } elseif (preg_match('/^([A-Za-z]+)(\d)/iu', $raw, $m)) {
        $day_part = $m[1];
        $time = trim(mb_substr($raw, mb_strlen($m[1])));
    } else {
        $day_part = $raw;
        $time = '';
    }
    $i = 0;
    $len = strlen($day_part);
    while ($i < $len) {
        $matched = false;
        if ($i + 1 < $len) {
            $two = substr($day_part, $i, 2);
            if (isset($days_map[$two])) { $days[] = $days_map[$two]; $i += 2; $matched = true; }
        }
        if (!$matched && $i < $len) {
            $one = $day_part[$i];
            if (isset($days_map[$one])) { $days[] = $days_map[$one]; }
            $i += 1;
        }
    }
    return array('days' => array_unique($days), 'time' => $time, 'raw' => $raw);
}

// Preserve the original AM/PM from the database — do not alter it.
// Only convert from 24h if AM/PM is missing.
function _format_ampm($time_str)
{
    $time_str = trim($time_str);
    if ($time_str === '' || $time_str === null) return $time_str;
    // Already has AM/PM — return as-is (preserve DB values exactly)
    if (preg_match('/AM|PM/i', $time_str)) {
        return $time_str;
    }
    // 24h format without AM/PM — convert correctly
    // 00:00–11:59 = AM, 12:00–23:59 = PM
    if (preg_match('/^(\d{1,2}):(\d{2})\s*[-–]\s*(\d{1,2}):(\d{2})$/u', $time_str, $m)) {
        $h1 = (int)$m[1]; $h2 = (int)$m[3];
        $s1 = ($h1 >= 0 && $h1 < 12) ? 'AM' : 'PM';
        $s2 = ($h2 >= 0 && $h2 < 12) ? 'AM' : 'PM';
        $h1_12 = $h1 % 12; if ($h1_12 === 0) $h1_12 = 12;
        $h2_12 = $h2 % 12; if ($h2_12 === 0) $h2_12 = 12;
        return $s1 . ' ' . $h1_12 . ':' . $m[2] . ' – ' . $s2 . ' ' . $h2_12 . ':' . $m[4];
    }
    return $time_str;
}

// Normalize a time range string to a 24h sortable value (start time in minutes).
// Used for chronological sorting so AM < PM regardless of display format.
function _time_sort_key($time_str)
{
    $time_str = trim($time_str);
    if ($time_str === '' || $time_str === null) return 9999;
    // Match "AM 7:30 – PM 8:30" or "7:30 AM – 8:30 AM"
    if (preg_match('/^(?:AM|PM)?\s*(\d{1,2}):(\d{2})/iu', $time_str, $m)) {
        $h = (int)$m[1]; $min = (int)$m[2];
        // Determine AM/PM from the string
        $is_pm = (stripos($time_str, 'PM') !== false);
        $is_am = (stripos($time_str, 'AM') !== false);
        // If AM/PM present, use it; otherwise infer from hour
        if ($is_pm) {
            if ($h < 12) $h += 12; // 1 PM = 13:00
        } elseif ($is_am) {
            if ($h === 12) $h = 0;  // 12 AM = 00:00
        } else {
            // No AM/PM — assume 24h: 0-11=AM, 12-23=PM
            if ($h >= 12) $h = $h; // already 24h
        }
        return $h * 60 + $min;
    }
    return 9999;
}

// ─── Build schedule grid ───
$days_order = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
$day_labels = array('Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday');
$schedule_by_day = array('Mon' => array(), 'Tue' => array(), 'Wed' => array(), 'Thu' => array(), 'Fri' => array());
foreach ($schedule_data as $item) {
    $parsed = _parse_schedule($item->schedule);
    foreach ($parsed['days'] as $day) {
        if (isset($schedule_by_day[$day])) {
            $schedule_by_day[$day][] = array(
                'code' => $item->code, 'title' => $item->title,
                'teacher' => $item->teacher_name ? $item->teacher_name : 'TBA',
                'time' => $parsed['time'], 'room' => $item->room ? $item->room : '',
            );
        }
    }
}

$ring_pct = $gwa > 0 ? max(0.0, min(1.0, (5.0 - $gwa) / 4.0)) : 0.0;
$ring_offset = 339.3 * (1 - $ring_pct);

// Color palette
$palette = array(
    array('bg' => '#F0FDFA', 'border' => '#99F6E4', 'text' => '#0D9488', 'accent' => '#0D9488'),
    array('bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#2563EB', 'accent' => '#2563EB'),
    array('bg' => '#FFF7ED', 'border' => '#FED7AA', 'text' => '#EA580C', 'accent' => '#EA580C'),
    array('bg' => '#F5F3FF', 'border' => '#DDD6FE', 'text' => '#7C3AED', 'accent' => '#7C3AED'),
    array('bg' => '#FDF2F8', 'border' => '#FBCFE8', 'text' => '#DB2777', 'accent' => '#DB2777'),
    array('bg' => '#ECFDF5', 'border' => '#A7F3D0', 'text' => '#059669', 'accent' => '#059669'),
    array('bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#DC2626', 'accent' => '#DC2626'),
    array('bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#D97706', 'accent' => '#D97706'),
);
$code_color_map = array();
$color_idx = 0;
?>

<!-- ═══════ WELCOME BANNER ═══════ -->
<div style="margin-bottom:28px;">
    <div class="welcome-banner" style="margin-bottom:0; border-radius:20px; padding:28px 32px; background:linear-gradient(135deg, #0D9488 0%, #0891B2 50%, #0284C7 100%); position:relative; overflow:hidden;">
        <!-- Decorative circles -->
        <div style="position:absolute; right:-30px; top:-30px; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,0.06);"></div>
        <div style="position:absolute; right:80px; bottom:-40px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.04);"></div>
        <div style="position:absolute; left:50%; top:10px; width:60px; height:60px; border-radius:50%; background:rgba(255,255,255,0.03);"></div>

        <div style="position:relative; z-index:1;">
            <p style="color:rgba(255,255,255,0.7); font-size:0.82rem; margin:0 0 4px; font-weight:500;">Welcome back,</p>
            <h2 style="color:white; font-family:var(--font-display); font-size:1.6rem; font-weight:800; margin:0 0 12px; letter-spacing:-0.01em;"><?php echo html_escape($student_name); ?></h2>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div>
                    <div style="color:rgba(255,255,255,0.5); font-size:0.65rem; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:2px;">Section</div>
                    <div style="color:white; font-size:0.85rem; font-weight:700;"><?php echo html_escape($section_name); ?></div>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,0.5); font-size:0.65rem; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:2px;">School Year</div>
                    <div style="color:white; font-size:0.85rem; font-weight:700;"><?php echo html_escape($sy_label); ?></div>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,0.5); font-size:0.65rem; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:2px;">Semester</div>
                    <div style="color:white; font-size:0.85rem; font-weight:700;"><?php echo html_escape($semester_label); ?></div>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,0.5); font-size:0.65rem; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:2px;">Student No.</div>
                    <div style="color:white; font-size:0.85rem; font-weight:700;"><?php echo html_escape($student_no); ?></div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if (!$is_enrolled_in_active): ?>
<!-- ═══════ NOT ENROLLED ═══════ -->
<?php $enroll_block_reason = isset($enroll_block_reason) ? $enroll_block_reason : NULL; ?>
<div class="card" style="padding:40px 32px; text-align:center; background:linear-gradient(135deg, #FFF7ED 0%, #FFFBEB 100%); border:1px solid #FED7AA; border-radius:20px;">
    <div style="width:64px; height:64px; border-radius:50%; background:#FFF7ED; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 9v4m0 4h.01"/></svg>
    </div>
    <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0 0 8px;">Enrollment Required</h3>
    <?php if ($enroll_block_reason): ?>
        <div style="color:#92400E; margin:0 auto 20px; font-size:0.88rem; max-width:520px; text-align:left; white-space:pre-line; line-height:1.6; background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:14px 18px;">
            <?php echo html_escape($enroll_block_reason); ?>
        </div>
        <a href="<?php echo site_url('student/dashboard'); ?>" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; font-size:0.9rem; font-weight:600; border-radius:12px; text-decoration:none; background:#64748B; color:white; transition:background 0.15s;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>
    <?php else: ?>
        <p style="color:#92400E; margin:0 0 6px; font-size:0.9rem;">The new semester has started. You need to enroll to see your schedule.</p>
        <p style="color:#B45309; margin:0 0 24px; font-size:0.82rem;">Choose your section for the upcoming semester to continue.</p>
        <a href="<?php echo site_url('student/enroll_next_semester'); ?>" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; font-size:0.9rem; font-weight:600; border-radius:12px; text-decoration:none; background:#F97316; color:white; transition:background 0.15s;" onmouseover="this.style.background='#EA580C'" onmouseout="this.style.background='#F97316'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Enroll for Next Semester
        </a>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ═══════ WEEKLY SCHEDULE ═══════ -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h3 style="font-family:var(--font-display); font-size:1.05rem; font-weight:700; color:#1E293B; margin:0; display:flex; align-items:center; gap:10px;">
        <div style="width:32px; height:32px; border-radius:10px; background:linear-gradient(135deg, #0D9488, #0891B2); display:flex; align-items:center; justify-content:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        Weekly Class Schedule
    </h3>
    <span class="badge" style="background:#F0FDFA; color:#0D9488; font-size:0.78rem; padding:4px 12px;">
        <?php echo count($schedule_data); ?> subject<?php echo count($schedule_data) !== 1 ? 's' : ''; ?>
    </span>
</div>

<div class="card" style="overflow:hidden; padding:0; border-radius:16px;">
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; min-width:720px;">
            <thead>
                <tr>
                    <?php foreach ($days_order as $day): ?>
                        <th style="text-align:center; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#64748B; padding:10px 8px; border-bottom:1.5px solid #E2E8F0; background:#FAFCFF; width:20%;"><?php echo $day_labels[$day]; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $all_times = array();
                foreach ($schedule_by_day as $day => $items) {
                    foreach ($items as $item) {
                        $t = $item['time'] ?: 'No Schedule';
                        if (!isset($all_times[$t])) $all_times[$t] = array();
                        $all_times[$t][] = $day;
                    }
                }
                // Sort by time chronologically (AM before PM)
                uksort($all_times, function($a, $b) {
                    return _time_sort_key($a) - _time_sort_key($b);
                });

                if (empty($all_times)):
                ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:32px 24px;">
                            <div style="width:56px; height:56px; border-radius:50%; background:#F1F5F9; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <p style="font-size:0.9rem; font-weight:600; color:#64748B; margin:0 0 4px;">No Schedule Available</p>
                            <p style="font-size:0.8rem; color:#94A3B8; margin:0;">Your teachers have not set schedules yet.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_times as $time => $active_days): ?>
                        <tr style="border-bottom:1px solid #F1F5F9;">
                            <?php foreach ($days_order as $day): ?>
                                <td style="padding:6px 6px; vertical-align:top; text-align:center; border-left:1px solid #F8FAFC;">
                                    <?php
                                    $found = false;
                                    foreach ($schedule_by_day[$day] as $item) {
                                        $item_time = $item['time'] ?: 'No Schedule';
                                        if ($item_time === $time) {
                                            if (!isset($code_color_map[$item['code']])) {
                                                $code_color_map[$item['code']] = $palette[$color_idx % count($palette)];
                                                $color_idx++;
                                            }
                                            $c = $code_color_map[$item['code']];
                                            $found = true;
                                            $display_time = _format_ampm($item['time']);
                                    ?>
                                        <div style="background:<?php echo $c['bg']; ?>; border:1px solid <?php echo $c['border']; ?>; border-left:3px solid <?php echo $c['accent']; ?>; border-radius:8px; padding:6px 8px; margin-bottom:4px; text-align:left;">
                                            <div style="font-size:0.72rem; font-weight:700; color:<?php echo $c['text']; ?>; margin-bottom:2px; letter-spacing:0.02em;"><?php echo html_escape($item['code']); ?></div>
                                            <?php if ($item['time']): ?>
                                                <div style="display:flex; align-items:center; gap:3px; font-size:0.65rem; color:#334155; font-weight:600; margin-bottom:2px;">
                                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                    <?php echo html_escape($display_time); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($item['room']): ?>
                                                <div style="display:flex; align-items:center; gap:3px;">
                                                    <span style="display:inline-flex; align-items:center; gap:2px; font-size:0.6rem; font-weight:600; color:<?php echo $c['accent']; ?>; background:rgba(255,255,255,0.7); border:1px solid <?php echo $c['border']; ?>; border-radius:3px; padding:1px 4px;">
                                                        <svg width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                                        <?php echo html_escape($item['room']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php
                                            }
                                        }
                                        if (!$found):
                                    ?>
                                        <span style="color:#E2E8F0; font-size:0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ═══════ ENROLL BANNER ═══════ -->
<?php if (isset($can_enroll_next_semester) && $can_enroll_next_semester): ?>
    <div class="card" style="margin-top:24px; padding:0; background:linear-gradient(135deg, #F0FDFA 0%, #ECFDF5 100%); border:1px solid #99F6E4; border-radius:16px; overflow:hidden;">
        <div style="padding:20px 28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:40px; height:40px; border-radius:12px; background:#0D9488; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0 0 2px;">Enroll for Next Semester</h3>
                    <p style="color:#0D9488; margin:0; font-size:0.82rem;">Enrollment is now open. Choose your section to continue.</p>
                </div>
            </div>
            <a href="<?php echo site_url('student/enroll_next_semester'); ?>" style="flex-shrink:0; padding:10px 24px; font-size:0.85rem; font-weight:600; border-radius:10px; text-decoration:none; background:#0D9488; color:white; transition:background 0.15s;" onmouseover="this.style.background='#0F766E'" onmouseout="this.style.background='#0D9488'">Enroll Now</a>
        </div>
    </div>
<?php endif; ?>

<!-- ═══════ MY TICKETS ═══════ -->
<div class="card" style="margin-top:24px; padding:0; border-radius:16px; overflow:hidden;">
    <div style="padding:20px 24px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0; display:flex; align-items:center; gap:8px;">
            <div style="width:28px; height:28px; border-radius:8px; background:#EFF6FF; display:flex; align-items:center; justify-content:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            My Tickets
        </h3>
        <a href="<?php echo site_url('student/tickets'); ?>" style="font-size:0.78rem; color:#0D9488; text-decoration:none; font-weight:600;">View All →</a>
    </div>
    <div style="padding:16px 24px; display:flex; gap:12px; border-bottom:1px solid #F1F5F9;">
        <span class="badge badge-success" style="font-size:0.78rem;">Open: <?php echo (int) $ticket_counts['Open']; ?></span>
        <span class="badge" style="background:#FFF7ED;color:#F97316;font-size:0.78rem;">In Progress: <?php echo (int) $ticket_counts['In Progress']; ?></span>
        <span class="badge badge-neutral" style="font-size:0.78rem;">Resolved: <?php echo (int) $ticket_counts['Resolved']; ?></span>
    </div>
    <div style="padding:0;">
        <?php if (empty($recent_tickets)): ?>
            <div style="text-align:center; padding:32px 24px;">
                <div style="width:48px; height:48px; border-radius:50%; background:#F1F5F9; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <p style="font-size:0.85rem; color:#94A3B8; margin:0;">No tickets yet. <a href="<?php echo site_url('student/ticket_submit'); ?>" style="color:#0D9488; font-weight:600;">Submit one</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($recent_tickets as $t): ?>
                <?php
                    $status_bg = '#F1F5F9'; $status_color = '#64748B';
                    if ($t->status === 'Open') { $status_bg = '#DCFCE7'; $status_color = '#16A34A'; }
                    elseif ($t->status === 'In Progress') { $status_bg = '#FFF7ED'; $status_color = '#F97316'; }
                    $recipient_label = $t->recipient_type === 'admin' ? 'Admin' : 'Teacher';
                ?>
                <a href="<?php echo site_url('student/ticket_view/' . (int) $t->id); ?>" style="display:flex; justify-content:space-between; align-items:center; padding:14px 24px; text-decoration:none; color:inherit; transition:background 0.12s; border-bottom:1px solid #F8FAFC;" onmouseover="this.style.background='#FAFCFF'" onmouseout="this.style.background='transparent'">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:<?php echo $status_bg; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo $status_color; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div>
                            <div style="font-weight:600; color:#1E293B; font-size:0.85rem; margin-bottom:2px;"><?php echo html_escape($t->subject); ?></div>
                            <div style="font-size:0.72rem; color:#94A3B8; display:flex; gap:8px;">
                                <span><?php echo html_escape($t->category); ?></span>
                                <span>→ <?php echo $recipient_label; ?></span>
                                <span class="badge" style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>; font-size:0.65rem; padding:1px 6px;"><?php echo html_escape($t->status); ?></span>
                            </div>
                        </div>
                    </div>
                    <div style="font-size:0.72rem; color:#94A3B8; white-space:nowrap;"><?php echo date('M j', strtotime($t->created_at)); ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div style="height:24px;"></div>
