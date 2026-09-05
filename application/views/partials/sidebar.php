<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Rendered server-side from the session-derived $role / $active_page. The
 * backend independently enforces access in the controllers — the sidebar
 * never decides authorization.
 */
$role = isset($role) ? $role : '';
$active = isset($active_page) ? $active_page : '';
if ( ! function_exists('_nav_item'))
{
	function _nav_item($href, $page, $active, $svg, $label)
	{
		$cls = ($page === $active) ? 'sidebar-item active' : 'sidebar-item';
		return '<a href="' . $href . '" class="' . $cls . '" data-page="' . $page . '">'
			. $svg . $label . '</a>';
	}
}
?>
<aside class="sidebar" data-page="<?php echo html_escape($active); ?>">
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
      </svg>
    </div>
    <div class="sidebar-brand-text">
      <div class="name">EduTrack</div>
      <div class="tagline">ACADEMIC RECORDS</div>
    </div>
  </div>

  <div class="sidebar-section-label">
    <?php
    $section_label = $role === 'admin' ? 'Administration' : ($role === 'teacher' ? 'Faculty' : ($role === 'applicant' ? 'Admission' : 'Student Portal'));
    echo html_escape($section_label);
    ?>
  </div>

  <nav class="sidebar-nav" aria-label="Main navigation">

    <?php if ($role === 'admin'): ?>
    <div data-nav="admin">
      <?php
      /*
       * Academic Setup is a collapsible group. It starts expanded when the
       * current page is one of its 4 sub-pages, otherwise collapsed; the
       * admin's manual expand/collapse choice is persisted in localStorage
       * (see assets/js/dashboard.js). Subject and curriculum management now
       * live inside each strand (see Strand Detail) so they are not listed
       * here as separate pages.
       */
      $academic_pages = array('semesters', 'programs', 'sections', 'rooms');
      $acad_open = in_array($active, $academic_pages, TRUE);

      echo _nav_item(site_url('admin/dashboard'), 'dashboard', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
        'Dashboard');

      $svg_folder = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 8 4.5-8 4.5-8-4.5L12 2z"/><path d="m4 11 8 4.5 8-4.5"/><path d="m4 15.5 8 4.5 8-4.5"/></svg>';
      $svg_caret = '<svg class="caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
      ?>
      <div class="sidebar-group<?php echo $acad_open ? ' open' : ''; ?>" id="acadGroup">
        <button type="button" class="sidebar-item sidebar-group-toggle" id="acadGroupToggle"
          aria-expanded="<?php echo $acad_open ? 'true' : 'false'; ?>" aria-controls="acadGroupItems" onclick="toggleAcademicGroup()">
          <span style="display:flex; align-items:center; gap:10px; min-width:0;"><?php echo $svg_folder; ?><span>Academic Setup</span></span>
          <?php echo $svg_caret; ?>
        </button>
        <div class="sidebar-subgroup" id="acadGroupItems"<?php echo $acad_open ? '' : ' style="display:none;"'; ?>>
          <?php
          echo _nav_item(site_url('academic/semesters'), 'semesters', $active,
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'Semesters');
          echo _nav_item(site_url('academic/programs'), 'programs', $active,
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>',
            'Strands');
          echo _nav_item(site_url('academic/sections'), 'sections', $active,
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
            'Sections');
          echo _nav_item(site_url('academic/rooms'), 'rooms', $active,
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/></svg>',
            'Rooms & Buildings');
          ?>
        </div>
      </div>

      <?php
      echo _nav_item(site_url('admin/applicants'), 'applicants', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'Applicants');
      echo _nav_item(site_url('admin/exam_questions'), 'exam_questions', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
        'Exam Questions');
      echo _nav_item(site_url('admin/users'), 'manage_users', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'Manage Users');
      echo _nav_item(site_url('reports/index'), 'reports', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>',
        'Reports');
      echo _nav_item(site_url('admin/announcements'), 'announcements', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
        'Announcements');
      echo _nav_item(site_url('admin/tickets'), 'tickets', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'Tickets');
      echo _nav_item(site_url('admin/grade_submission_status'), 'grade_submission_status', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/></svg>',
        'Grade Submission Status');
      echo _nav_item(site_url('admin/correction_requests'), 'correction_requests', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
        'Correction Requests');
echo _nav_item(site_url('admin/activity_log'), 'activity_log', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'Activity Log');
      ?>
    </div>
    <?php elseif ($role === 'applicant'): ?>
    <div data-nav="applicant">
      <?php
      echo _nav_item(site_url('applicant/dashboard'), 'applicant_dashboard', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
        'My Application');
      ?>
    </div>
    <?php elseif ($role === 'teacher'): ?>
    <div data-nav="teacher">
      <?php
      echo _nav_item(site_url('teacher/dashboard'), 'dashboard', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
        'Dashboard');
      echo _nav_item(site_url('teacher/my_subjects'), 'my_subjects', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>',
        'My Subjects');
      echo _nav_item(site_url('teacher/class_list'), 'class_list', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'Class List');
      echo _nav_item(site_url('teacher/encode_grades'), 'encode_grades', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
        'Encode Grades');
      echo _nav_item(site_url('reports/index'), 'reports', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>',
        'Reports');
      echo _nav_item(site_url('teacher/ticket_submit'), 'submit_ticket', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
        'Submit a Ticket');
      echo _nav_item(site_url('teacher/tickets'), 'my_tickets', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'My Tickets');
      ?>
    </div>
    <?php else: ?>
    <div data-nav="student">
      <?php
      echo _nav_item(site_url('student/dashboard'), 'dashboard', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
        'Dashboard');
      echo _nav_item(site_url('student/schedule'), 'schedule', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'Grades');
      echo _nav_item(site_url('student/enrollment_history'), 'enrollment_history', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'Enrollment History');
      echo _nav_item(site_url('student/report_card'), 'report_card', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        'Report Card');
      echo _nav_item(site_url('student/enroll_next_semester'), 'enroll_next_semester', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/></svg>',
        'Enroll Next Semester');
      echo _nav_item(site_url('student/ticket_submit'), 'submit_ticket', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
        'Submit a Ticket');
      echo _nav_item(site_url('student/tickets'), 'my_tickets', $active,
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'My Tickets');
      ?>
    </div>
    <?php endif; ?>

  </nav>

  <div class="sidebar-footer">
    <a href="<?php echo site_url('auth/logout'); ?>" class="sidebar-item" style="color: white; font-weight: 700;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>