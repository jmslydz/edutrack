<?php
$semester = isset($semester) ? $semester : NULL;
$section  = isset($section) ? $section : NULL;
$grades   = isset($grades) ? $grades : array();

// Grade color: green (1.0) → amber (2.5) → orange (3.0) → red (3.25+)
// 1.0–3.0 = passing (green→amber→orange), 3.25+ = failed (red)
function _grade_color($val)
{
    $v = (float) $val;
    // Solid red for failed grades
    if ($v >= 3.25) return array('text' => '#DC2626', 'bg' => '#FEF2F2');
    // Solid green for perfect grade
    if ($v <= 1.0)  return array('text' => '#059669', 'bg' => '#ECFDF5');
    // Interpolate green (1.0) → red (3.25) across 2.25 range
    $t = ($v - 1.0) / 2.25; // 0.0 at 1.0, 1.0 at 3.25
    // Green: #059669 (5,150,105) → Amber: #D97706 (217,119,6) → Red: #DC2626 (220,38,38)
    $r = (int) round(5 + $t * 215);
    $g = (int) round(150 - $t * 112);
    $b = (int) round(105 - $t * 67);
    $text = sprintf('#%02X%02X%02X', $r, $g, $b);
    // Lighter background
    $br = min(255, $r + 200);
    $bg_g = min(255, $g + 80);
    $bb = min(255, $b + 100);
    $bg = sprintf('#%02X%02X%02X', $br, $bg_g, $bb);
    return array('text' => $text, 'bg' => $bg);
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Grades</h1>
        <p class="text-muted" style="font-size:0.85rem; margin-top:4px;">View your grades for the current semester</p>
    </div>
</div>

<?php if (empty($grades)): ?>
    <!-- Empty State -->
    <div class="card" style="padding:48px 24px; text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <h5 style="color:#64748B; font-weight:600; margin-bottom:8px;">No Grades Available</h5>
        <p style="color:#94A3B8; font-size:0.85rem; margin-bottom:20px;">
            <?php if ($semester): ?>
                No grades have been recorded for <?= html_escape($semester->name) ?> yet.
            <?php else: ?>
                No active semester found.
            <?php endif; ?>
        </p>
        <a href="<?= site_url('student/dashboard') ?>" class="btn-primary" style="display:inline-block; text-decoration:none;">Back to Dashboard</a>
    </div>
<?php else: ?>
    <!-- Info Card -->
    <div class="card" style="margin-bottom:24px;">
        <div style="padding:20px 24px; border-bottom:1px solid #F1F5F9;">
            <h5 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                Semester Information
            </h5>
        </div>
        <div style="padding:20px 24px; display:grid; grid-template-columns:repeat(3, 1fr); gap:20px;">
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Semester</div>
                <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= html_escape($semester->name) ?></div>
            </div>
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">School Year</div>
                <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= html_escape($semester->year_label) ?></div>
            </div>
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Section</div>
                <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= $section ? html_escape($section->name) : '—' ?></div>
            </div>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="card">
        <div style="padding:20px 24px; border-bottom:1px solid #F1F5F9;">
            <h5 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                My Grades
            </h5>
        </div>
        <div style="padding:0;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:12px 20px; border-bottom:1.5px solid #F1F5F9;">Code</th>
                            <th style="text-align:left; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:12px 20px; border-bottom:1.5px solid #F1F5F9;">Subject</th>
                            <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:12px 20px; border-bottom:1.5px solid #F1F5F9;">Units</th>
                            <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:12px 20px; border-bottom:1.5px solid #F1F5F9;">Midterm</th>
                            <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:12px 20px; border-bottom:1.5px solid #F1F5F9;">Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $g): ?>
                            <?php
                            $mid_c  = $g->midterm !== NULL ? _grade_color($g->midterm) : NULL;
                            $final_c = $g->final !== NULL ? _grade_color($g->final) : NULL;
                            ?>
                            <tr style="border-bottom:1px solid #F8FAFC; transition:background 0.15s;" onmouseover="this.style.background='#F8FFFE'" onmouseout="this.style.background=''">
                                <td style="padding:12px 20px; font-size:0.85rem; font-weight:700; color:#0D9488;"><?= html_escape($g->code) ?></td>
                                <td style="padding:12px 20px; font-size:0.85rem; color:#334155;"><?= html_escape($g->title) ?></td>
                                <td style="padding:12px 20px; font-size:0.85rem; color:#334155; text-align:center;"><?= $g->units ?></td>
                                <td style="padding:12px 20px; font-size:0.85rem; text-align:center; font-weight:600;">
                                    <?php if ($g->midterm !== NULL): ?>
                                        <span style="color:<?php echo $mid_c['text']; ?>; font-weight:700; display:inline-block;">
                                            <?= number_format((float)$g->midterm, 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#CBD5E1; font-style:italic;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 20px; font-size:0.85rem; text-align:center; font-weight:600;">
                                    <?php if ($g->final !== NULL): ?>
                                        <span style="color:<?php echo $final_c['text']; ?>; font-weight:700; display:inline-block;">
                                            <?= number_format((float)$g->final, 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#CBD5E1; font-style:italic;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- GWA Summary -->
    <?php
    $gwa_sum = 0;
    $gwa_units = 0;
    foreach ($grades as $g) {
        if ($g->final !== NULL) {
            $gwa_sum += (float) $g->final * (float) $g->units;
            $gwa_units += (float) $g->units;
        }
    }
    $gwa = $gwa_units > 0 ? round($gwa_sum / $gwa_units, 4) : 0;
    $total_units = 0;
    foreach ($grades as $g) { $total_units += (float) $g->units; }
    $honor_label = 'Good Standing';
    $honor_color = '#64748B';
    if ($gwa > 0) {
        if ($gwa <= 1.50) { $honor_label = 'Summa Cum Laude'; $honor_color = '#059669'; }
        elseif ($gwa <= 2.00) { $honor_label = 'Magna Cum Laude'; $honor_color = '#0D9488'; }
        elseif ($gwa <= 2.50) { $honor_label = 'Cum Laude'; $honor_color = '#0891B2'; }
        elseif ($gwa <= 3.00) { $honor_label = 'Good Standing'; $honor_color = '#64748B'; }
        else { $honor_label = 'Below Standard'; $honor_color = '#DC2626'; }
    }
    ?>
    <div class="card" style="margin-top:24px;">
        <div style="padding:20px 24px; display:grid; grid-template-columns:repeat(4, 1fr); gap:20px;">
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">GWA</div>
                <div style="font-size:1.1rem; font-weight:800; color:<?php echo $honor_color; ?>; font-family:var(--font-display);"><?= $gwa > 0 ? number_format($gwa, 2) : '—' ?></div>
            </div>
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Standing</div>
                <div style="font-size:0.9rem; font-weight:700; color:<?php echo $honor_color; ?>;"><?= html_escape($honor_label) ?></div>
            </div>
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Total Units</div>
                <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= $total_units ?></div>
            </div>
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Subjects</div>
                <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= count($grades) ?></div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div style="text-align:center; margin-top:32px; margin-bottom:24px;">
        <a href="<?= site_url('student/dashboard') ?>" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Dashboard
        </a>
    </div>
<?php endif; ?>
