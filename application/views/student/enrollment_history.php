<?php
function _grade_color($val)
{
    if ($val === NULL) return '#CBD5E1';
    $n = (float) $val;
    if ($n >= 3.25) return '#DC2626';  // Failed
    if ($n <= 1.00) return '#059669';  // Perfect
    // Interpolate green → amber → red across 1.0–3.25
    $t = ($n - 1.0) / 2.25;
    $r = (int) round(5 + $t * 215);
    $g = (int) round(150 - $t * 112);
    $b = (int) round(105 - $t * 67);
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Enrollment History</h1>
        <p class="text-muted" style="font-size:0.85rem; margin-top:4px;">View your past and current enrollments</p>
    </div>
</div>

<?php if (empty($enrollments)): ?>
    <div class="card" style="padding:48px 24px; text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <h5 style="color:#64748B; font-weight:600; margin-bottom:8px;">No Enrollment History</h5>
        <p style="color:#94A3B8; font-size:0.85rem; margin-bottom:20px;">You haven't enrolled in any semesters yet.</p>
        <a href="<?= site_url('student/dashboard') ?>" class="btn-primary" style="display:inline-block; text-decoration:none;">Back to Dashboard</a>
    </div>
<?php else: ?>
    <?php foreach ($enrollments as $enr): ?>
        <div class="card" style="margin-bottom:24px;">
            <!-- Header -->
            <div style="padding:20px 24px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center;">
                <h5 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                    <?= html_escape($enr->sem_name) ?> — <?= html_escape($enr->year_label) ?>
                </h5>
                <span class="badge" style="background:<?= $enr->semester_number == 1 ? '#DBEAFE; color:#2563EB' : '#CCFBF1; color:#0D9488' ?>;">
                    Semester <?= $enr->semester_number ?>
                </span>
            </div>

            <!-- Enrollment Details -->
            <div style="padding:20px 24px; display:grid; grid-template-columns:repeat(3, 1fr); gap:20px;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Section</div>
                    <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= $enr->section_name ? html_escape($enr->section_name) : '—' ?></div>
                </div>
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Strand</div>
                    <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= $enr->strand_name ? html_escape($enr->strand_name) : '—' ?></div>
                </div>
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">Enrolled</div>
                    <div style="font-size:0.9rem; font-weight:600; color:#1E293B;"><?= date('M d, Y', strtotime($enr->enrolled_at)) ?></div>
                </div>
            </div>

            <!-- Grades Table -->
            <?php if (isset($grades_by_semester[$enr->semester_id]) && !empty($grades_by_semester[$enr->semester_id])): ?>
                <div style="padding:0 24px 24px;">
                    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Grades</div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Code</th>
                                    <th style="text-align:left; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Subject</th>
                                    <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Units</th>
                                    <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Midterm</th>
                                    <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Final</th>
                                    <th style="text-align:center; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:10px 14px; border-bottom:1.5px solid #E2E8F0;">Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_units = 0;
                                $gwa_sum = 0;
                                $gwa_units = 0;
                                foreach ($grades_by_semester[$enr->semester_id] as $grade):
                                    $total_units += $grade->units;
                                    if ($grade->final_grade !== NULL) {
                                        $gwa_sum += $grade->final_grade * $grade->units;
                                        $gwa_units += $grade->units;
                                    }
                                    $mid_color = _grade_color($grade->midterm);
                                    $fin_color = _grade_color($grade->final_grade);
                                ?>
                                    <tr style="border-bottom:1px solid #F1F5F9;">
                                        <td style="padding:10px 14px; font-size:0.85rem; font-weight:700; color:#0D9488;"><?= html_escape($grade->code) ?></td>
                                        <td style="padding:10px 14px; font-size:0.85rem; color:#334155;"><?= html_escape($grade->title) ?></td>
                                        <td style="padding:10px 14px; font-size:0.85rem; color:#334155; text-align:center;"><?= $grade->units ?></td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <?php if ($grade->midterm !== NULL): ?>
                                                <span style="font-size:0.85rem; font-weight:700; color:<?= $mid_color ?>;">
                                                    <?= number_format($grade->midterm, 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#CBD5E1; font-size:0.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <?php if ($grade->final_grade !== NULL): ?>
                                                <span style="font-size:0.85rem; font-weight:700; color:<?= $fin_color ?>;">
                                                    <?= number_format($grade->final_grade, 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#CBD5E1; font-size:0.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <?php if ($grade->final_grade !== NULL): ?>
                                                <strong style="font-size:0.85rem; font-weight:700; color:<?= $fin_color ?>;"><?= number_format($grade->final_grade, 2) ?></strong>
                                            <?php else: ?>
                                                <span style="color:#CBD5E1; font-size:0.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#F8FFFE; border-top:2px solid #E2E8F0;">
                                    <td colspan="2" style="padding:12px 14px; font-weight:700; color:#1E293B; font-size:0.875rem;">General Weighted Average (GWA)</td>
                                    <td style="padding:12px 14px; font-weight:700; color:#1E293B; text-align:center;"><?= $total_units ?></td>
                                    <td colspan="2"></td>
                                    <td style="padding:12px 14px; text-align:center;">
                                        <?php
                                        $gwa = $gwa_units > 0 ? round($gwa_sum / $gwa_units, 2) : 0;
                                        $gwa_display = $gwa > 0 ? number_format($gwa, 2) : '—';
                                        $gwa_color = '#CBD5E1';
                                        if ($gwa > 0) {
                                            if ($gwa >= 3.25) $gwa_color = '#DC2626';
                                            elseif ($gwa <= 1.00) $gwa_color = '#059669';
                                            else {
                                                $t = ($gwa - 1.0) / 2.25;
                                                $gr = (int) round(5 + $t * 215);
                                                $gg = (int) round(150 - $t * 112);
                                                $gb = (int) round(105 - $t * 67);
                                                $gwa_color = sprintf('#%02X%02X%02X', $gr, $gg, $gb);
                                            }
                                        }
                                        ?>
                                        <strong style="font-size:1rem; color:<?= $gwa_color ?>;"><?= $gwa_display ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding:0 24px 24px;">
                    <p style="color:#94A3B8; font-size:0.85rem;">No grades available for this semester.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div style="text-align:center; margin-top:32px; margin-bottom:24px;">
        <a href="<?= site_url('student/dashboard') ?>" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Dashboard
        </a>
    </div>
<?php endif; ?>
