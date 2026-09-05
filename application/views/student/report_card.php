<?php
function _rc_grade_color($val)
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
function _rc_grade_bg($val)
{
    if ($val === NULL) return '#F8FAFC';
    $n = (float) $val;
    if ($n >= 3.25) return '#FEF2F2';  // Failed bg
    if ($n <= 1.00) return '#ECFDF5';  // Perfect bg
    $t = ($n - 1.0) / 2.25;
    $br = min(255, (int) round(5 + $t * 215) + 200);
    $bg_g = min(255, (int) round(150 - $t * 112) + 80);
    $bb = min(255, (int) round(105 - $t * 67) + 100);
    return sprintf('#%02X%02X%02X', $br, $bg_g, $bb);
}
$first_name = isset($user) ? $user->first_name : '';
$last_name  = isset($user) ? $user->last_name : '';
$full_name  = trim($first_name . ' ' . $last_name);
$program    = $section ? $this->Academic_model->get_program($section->program_id) : NULL;
$program_name = $program ? $program->program_name : '—';
$total_units = 0;
$gwa_sum = 0;
$gwa_units = 0;
foreach ($grades as $g) {
    $total_units += $g->units;
    if ($g->final_grade !== NULL) {
        $gwa_sum += $g->final_grade * $g->units;
        $gwa_units += $g->units;
    }
}
?>

<!-- Print button (hidden when printing) -->
<div class="page-header" id="printHeader">
    <div class="page-title">
        <h1>Report Card (Form 138)</h1>
        <p class="text-muted" style="font-size:0.85rem; margin-top:4px;">Official academic record for the current semester</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Report Card
        </button>
    </div>
</div>

<!-- Report Card -->
<div class="card" style="padding:0;">
    <div style="padding:20px 28px;">

        <!-- School Header -->
        <div style="text-align:center; margin-bottom:10px;">
            <h2 style="font-family:var(--font-display); font-size:1.25rem; font-weight:800; color:#1E293B; margin-bottom:2px;"><?= html_escape($school_name) ?></h2>
            <p style="color:#64748B; font-size:0.75rem; margin-bottom:6px;"><?= html_escape($school_address) ?></p>
            <h3 style="font-family:var(--font-display); font-size:0.85rem; font-weight:700; color:#0D9488; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:4px;">Report Card</h3>
            <p style="font-size:0.78rem; color:#334155; margin:0;">
                <strong>School Year:</strong> <?= html_escape($semester->year_label) ?> &nbsp;|&nbsp; <strong>Semester:</strong> <?= html_escape($semester->name) ?>
            </p>
        </div>

        <div style="height:1px; background:#E2E8F0; margin:0 0 10px;"></div>

        <!-- Student Info -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; margin-bottom:10px;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0; font-size:0.72rem; color:#64748B; width:90; font-weight:600;">Student No.</td>
                    <td style="padding:2px 0; font-size:0.82rem; color:#1E293B; font-weight:600;"><?= html_escape($student->student_no) ?></td>
                </tr>
                <tr>
                    <td style="padding:2px 0; font-size:0.72rem; color:#64748B; font-weight:600;">Name</td>
                    <td style="padding:2px 0; font-size:0.82rem; color:#1E293B; font-weight:600;"><?= html_escape($full_name) ?></td>
                </tr>
            </table>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0; font-size:0.72rem; color:#64748B; width:90; font-weight:600;">Section</td>
                    <td style="padding:2px 0; font-size:0.82rem; color:#1E293B; font-weight:600;"><?= $section ? html_escape($section->name) : '—' ?></td>
                </tr>
                <tr>
                    <td style="padding:2px 0; font-size:0.72rem; color:#64748B; font-weight:600;">Strand</td>
                    <td style="padding:2px 0; font-size:0.82rem; color:#1E293B; font-weight:600;"><?= html_escape($program_name) ?></td>
                </tr>
            </table>
        </div>

        <div style="height:1px; background:#E2E8F0; margin:0 0 8px;"></div>

        <!-- Grades Table -->
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Code</th>
                    <th style="text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Subject</th>
                    <th style="text-align:center; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Units</th>
                    <th style="text-align:center; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Midterm</th>
                    <th style="text-align:center; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Final</th>
                    <th style="text-align:center; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; padding:4px 6px; border-bottom:1.5px solid #E2E8F0;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr style="border-bottom:0.5px solid #F1F5F9;">
                        <td style="padding:3px 6px; font-size:0.78rem; font-weight:700; color:#0D9488;"><?= html_escape($grade->code) ?></td>
                        <td style="padding:3px 6px; font-size:0.78rem; color:#334155;"><?= html_escape($grade->title) ?></td>
                        <td style="padding:3px 6px; font-size:0.78rem; color:#334155; text-align:center;"><?= $grade->units ?></td>
                        <td style="padding:3px 6px; text-align:center;">
                            <?php if ($grade->midterm !== NULL): ?>
                                <span style="display:inline-block; padding:1px 6px; font-size:0.75rem; font-weight:600; color:<?= _rc_grade_color($grade->midterm) ?>;"><?= number_format($grade->midterm, 2) ?></span>
                            <?php else: ?>
                                <span style="color:#CBD5E1; font-size:0.78rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:3px 6px; text-align:center;">
                            <?php if ($grade->final_grade !== NULL): ?>
                                <span style="display:inline-block; padding:1px 6px; font-size:0.75rem; font-weight:600; color:<?= _rc_grade_color($grade->final_grade) ?>;"><?= number_format($grade->final_grade, 2) ?></span>
                            <?php else: ?>
                                <span style="color:#CBD5E1; font-size:0.78rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:3px 6px; text-align:center;">
                            <?php if ($grade->final_grade !== NULL): ?>
                                <?php if ($grade->final_grade <= 3.00): ?>
                                    <span class="badge badge-success" style="font-size:0.68rem; padding:1px 5px;">PASSED</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#FEF2F2; color:#EF4444; font-size:0.68rem; padding:1px 5px;">FAILED</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge" style="background:#FEF3C7; color:#D97706; font-size:0.68rem; padding:1px 5px;">INC</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="height:1px; background:#E2E8F0; margin:8px 0;"></div>

        <!-- Summary + Grading Scale on same row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <!-- Left: Summary -->
            <div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:2px 0; font-size:0.72rem; color:#64748B; width:140; font-weight:600;">Total Units</td>
                        <td style="padding:2px 0; font-size:0.82rem; color:#1E293B; font-weight:600;"><?= $total_units ?></td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0; font-size:0.72rem; color:#64748B; font-weight:600;">GWA</td>
                        <td style="padding:2px 0;">
                            <?php if ($gwa > 0): ?>
                                <strong style="font-size:0.95rem; color:#0D9488;"><?= number_format($gwa, 2) ?></strong>
                            <?php else: ?>
                                <span style="color:#CBD5E1;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0; font-size:0.72rem; color:#64748B; font-weight:600;">Status</td>
                        <td style="padding:2px 0;">
                            <?php if ($gwa > 0 && $gwa <= 3.00): ?>
                                <span class="badge badge-success" style="font-size:0.68rem; padding:1px 5px;">PASSED</span>
                            <?php elseif ($gwa > 3.00): ?>
                                <span class="badge" style="background:#FEF2F2; color:#EF4444; font-size:0.68rem; padding:1px 5px;">FAILED</span>
                            <?php else: ?>
                                <span class="badge" style="background:#FEF3C7; color:#D97706; font-size:0.68rem; padding:1px 5px;">INCOMPLETE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Right: Signature + Grading Scale -->
            <div>
                <p style="font-size:0.72rem; color:#334155; margin:0 0 30px; text-align:right;"><strong>Date Printed:</strong> <?= date('F d, Y') ?></p>
                <div style="text-align:right; margin-bottom:12px;">
                    <div style="display:inline-block; width:160px; border-top:1px solid #1E293B; padding-top:4px;">
                        <span style="font-size:0.68rem; color:#64748B;">Registrar's Signature</span>
                    </div>
                </div>
                <div style="font-size:0.65rem; color:#94A3B8; line-height:1.4;">
                    <strong style="color:#64748B;">Grading Scale:</strong>
                    1.00 (Exc) | 1.25–1.50 (VG) | 1.75–2.00 (G) |
                    2.25–2.50 (VS) | 2.75–3.00 (S) |
                    3.25–4.00 (F) | 4.25–5.00 (Fail) | INC
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Back Button -->
<div id="backBtnWrap" style="text-align:center; margin-top:24px; margin-bottom:20px;">
    <a href="<?= site_url('student/dashboard') ?>" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to Dashboard
    </a>
</div>

<style>
    @media print {
        @page { margin: 0 !important; size: A4 portrait; }
        html { margin: 0 !important; padding: 0 !important; }

        #printHeader, .page-header, button, .btn-primary, .btn-secondary,
        .sidebar, .topbar, .app-shell > nav, #backBtnWrap { display: none !important; }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
        body { margin: 0 !important; padding: 0 !important; background: white !important; font-size: 9pt !important; }

        .card { width: 100% !important; max-width: 100% !important; box-shadow: none !important; border-radius: 0 !important; border: none !important; margin: 0 !important; }
        .card > div { padding: 6pt 12pt !important; }

        .main-content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .content-area { padding: 6pt 10pt !important; }
        .app-shell { display: block !important; }

        h2 { font-size: 13pt !important; margin-bottom: 1pt !important; }
        h3 { font-size: 10pt !important; margin-bottom: 2pt !important; }

        table { width: 100% !important; border-collapse: collapse !important; }
        thead tr { border-bottom: 1.5pt solid #000 !important; }
        tbody tr { border-bottom: 0.3pt solid #bbb !important; }
        th { font-size: 7pt !important; padding: 2pt 4pt !important; background: #f0f0f0 !important; }
        td { font-size: 8pt !important; padding: 2pt 4pt !important; }

        td span[style*="font-weight:600"] { font-size: 7.5pt !important; }
        .badge { border-radius: 0 !important; font-size: 6.5pt !important; padding: 0.5pt 4pt !important; }

        p { margin: 0 !important; }

        div[style*="border-top: 1px"] { border-top: 1pt solid #000 !important; }
    }
</style>
