<?php
$subjects    = isset($subjects) ? $subjects : array();
$sections    = isset($sections) ? $sections : array();
$periods     = isset($periods) ? $periods : array('Midterm', 'Final');
$selected    = isset($selected) ? $selected : array('assignment_id' => 0, 'subject_code' => '', 'section_name' => '', 'period' => 'Midterm');
$students    = isset($students) ? $students : array();
$student_count = isset($student_count) ? $student_count : 0;
$encoded_count = isset($encoded_count) ? $encoded_count : 0;

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');

function _grade_input_style($value)
{
	if ($value === NULL || $value === '') return 'color:#94A3B8; background:transparent; border-color:#E2E8F0;';
	$n = (float) $value;
	if ($n <= 1.5) return 'color:#16A34A; background:#DCFCE7; border-color:#CCFBF1;';
	if ($n <= 2.5) return 'color:#0891B2; background:#E0F2FE; border-color:#CCFBF1;';
	if ($n <= 3.0) return 'color:#F97316; background:#FFF7ED; border-color:#CCFBF1;';
	return 'color:#EF4444; background:#FEF2F2; border-color:#FECACA;';
}
?>
<!-- BEGIN PAGE CONTENT (everything below belongs inside .content-area) -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Encode Grades</h2>
      <p class="page-subtitle">Enter and save student grades per grading period</p>
    </div>
  </div>

  <div class="card filter-card">
    <form method="get" action="<?php echo site_url('teacher/encode_grades'); ?>">
    <div class="filter-grid">
      <div>
        <label class="form-label" for="filterSubject">Subject</label>
        <select class="form-input" id="filterSubject" name="subject" onchange="this.form.submit()">
          <?php foreach ($subjects as $sub): ?>
            <option value="<?php echo html_escape($sub['code']); ?>" <?php echo $sub['code'] === $selected['subject_code'] ? 'selected' : ''; ?>><?php echo html_escape($sub['code'] . ' — ' . $sub['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" for="filterSection">Section</label>
        <select class="form-input" id="filterSection" name="section" onchange="this.form.submit()">
          <?php foreach ($sections as $sec): ?>
            <option value="<?php echo html_escape($sec['name']); ?>" <?php echo $sec['name'] === $selected['section_name'] ? 'selected' : ''; ?>><?php echo html_escape($sec['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" for="filterPeriod">Grading Period</label>
        <select class="form-input" id="filterPeriod" name="period" onchange="this.form.submit()">
          <?php foreach ($periods as $per): ?>
            <option value="<?php echo html_escape($per); ?>" <?php echo $per === $selected['period'] ? 'selected' : ''; ?>><?php echo html_escape($per); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    </form>
  </div>

  <div class="status-banners">
    <div class="banner banner--info">
      <span style="font-size:0.85rem; font-weight:600; color:#0F766E;"><?php echo html_escape($selected['subject_code'] . ' — ' . $selected['section_name'] . ' — ' . $selected['period']); ?></span>
      <span class="text-faint" style="font-size:0.78rem;"><?php echo $student_count; ?> students</span>
    </div>
    <?php if ($flash_success): ?>
      <div class="banner banner--success" id="savedBanner">
        <span class="banner-dot" style="background:#16A34A;"></span>
        <span style="font-size:0.82rem; font-weight:600; color:#15803D;"><?php echo html_escape($flash_success); ?></span>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="banner banner--warn" id="errorBanner">
        <span class="banner-dot" style="background:#EF4444;"></span>
        <span style="font-size:0.82rem; font-weight:600; color:#B91C1C;"><?php echo html_escape($flash_error); ?></span>
      </div>
    <?php endif; ?>
    <div class="banner banner--warn" id="incompleteBanner" style="<?php echo $encoded_count >= $student_count ? 'display:none;' : ''; ?>">
      <span class="banner-dot" style="background:#F97316;"></span>
      <span style="font-size:0.82rem; font-weight:600; color:#C2410C;"><span id="incompleteCount"><?php echo $student_count - $encoded_count; ?></span> incomplete grades</span>
    </div>
  </div>

  <?php
  echo form_open('teacher/save_grades', array('id' => 'gradesForm', 'novalidate' => 'novalidate'));
  echo form_hidden('assignment_id', (int) $selected['assignment_id']);
  echo form_hidden('period', $selected['period']);
  ?>
  <div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>#</th><th>Student No.</th><th>Student Name</th><th><?php echo html_escape($selected['period']); ?> Grade</th><th>Remarks</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr class="table-row"><td colspan="6" style="text-align:center; color:#94A3B8; padding:24px;">No students are enrolled in this class for the selected term.</td></tr>
        <?php endif; ?>
        <?php $idx = 1; foreach ($students as $st): $has_grade = ($st['grade'] !== NULL && $st['grade'] !== ''); ?>
        <tr class="table-row">
          <td style="color:#94A3B8;font-weight:500;"><?php echo $idx++; ?></td>
          <td style="font-family:monospace;font-size:0.8rem;color:#64748B;"><?php echo html_escape($st['student_no']); ?></td>
          <td style="font-weight:600;color:#1E293B;"><?php echo html_escape($st['name']); ?></td>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <input type="number" min="1.0" max="5.0" step="0.25" name="grades[<?php echo (int) $st['student_id']; ?>]" value="<?php echo $has_grade ? html_escape(number_format((float) $st['grade'], 2)) : ''; ?>" placeholder="1.0–5.0"
                class="grade-input" style="<?php echo _grade_input_style($has_grade ? $st['grade'] : NULL); ?>"
                onchange="onGradeInputChange(this)" oninput="onGradeInputChange(this)">
              <span class="missing-tag" style="<?php echo $has_grade ? 'display:none;' : 'display:inline-flex;'; ?>">Missing</span>
            </div>
          </td>
          <td class="remarks-cell">
            <?php if ($has_grade): $n = (float) $st['grade']; ?>
              <span class="badge" style="<?php echo $n <= 3.0 ? 'background:#DCFCE7;color:#16A34A;' : 'background:#FEF2F2;color:#EF4444;'; ?>"><?php echo $n <= 3.0 ? 'Passed' : 'Failed'; ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex; align-items:center; gap:6px;">
              <div class="status-dot" style="width:6px;height:6px;border-radius:50%;background:<?php echo $has_grade ? '#10B981' : '#CBD5E1'; ?>;"></div>
              <span class="status-text" style="font-size:0.78rem;color:<?php echo $has_grade ? '#16A34A' : '#94A3B8'; ?>;font-weight:500;"><?php echo $has_grade ? 'Encoded' : 'Pending'; ?></span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="table-footer">
      <div style="font-size:0.82rem; color:#64748B;">
        <span style="font-weight:600; color:#0D9488;" id="encodedCount"><?php echo $encoded_count; ?></span> of <span style="font-weight:600;"><?php echo $student_count; ?></span> grades encoded
      </div>
      <div style="display:flex; gap:10px;">
        <button class="btn-secondary" type="button" onclick="clearAllGrades()">Clear All</button>
        <button class="btn-primary" style="border-radius:10px;" type="submit">Save Grades</button>
      </div>
    </div>
  </div>
  <?php echo form_close(); ?>

  <div class="card" style="padding:20px; margin-top:20px;">
    <div style="font-size:0.72rem; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Grade Scale Reference</div>
    <div class="grade-scale-legend">
      <div class="grade-scale-item"><span class="badge" style="background:#DCFCE7;color:#16A34A;">1.00</span><span class="text-muted" style="font-size:0.78rem;">Excellent</span></div>
      <div class="grade-scale-item"><span class="badge" style="background:#DCFCE7;color:#15803D;">1.25–1.50</span><span class="text-muted" style="font-size:0.78rem;">Very Good</span></div>
      <div class="grade-scale-item"><span class="badge" style="background:#E0F2FE;color:#0891B2;">1.75–2.00</span><span class="text-muted" style="font-size:0.78rem;">Good</span></div>
      <div class="grade-scale-item"><span class="badge" style="background:#E0F2FE;color:#0891B2;">2.25–2.50</span><span class="text-muted" style="font-size:0.78rem;">Satisfactory</span></div>
      <div class="grade-scale-item"><span class="badge" style="background:#FFF7ED;color:#F97316;">2.75–3.00</span><span class="text-muted" style="font-size:0.78rem;">Passing</span></div>
      <div class="grade-scale-item"><span class="badge" style="background:#FEF2F2;color:#EF4444;">5.00</span><span class="text-muted" style="font-size:0.78rem;">Failed</span></div>
    </div>
  </div>
<!-- END PAGE CONTENT -->