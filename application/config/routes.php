<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| EduTrack explicit routes
| -------------------------------------------------------------------------
| Routes are declared explicitly so the roles served by each controller
| can be enforced before any method runs (see MY_Controller guards).
*/
$route['auth/login']              = 'auth/login';
$route['auth/register']           = 'auth/register';
$route['auth/logout']             = 'auth/logout';
$route['auth/forgot_password']    = 'auth/forgot_password';
$route['auth/reset_password']     = 'auth/reset_password';
$route['auth/reset_password/(:any)'] = 'auth/reset_password/$1';

$route['admin/dashboard']           = 'admin/dashboard';
$route['admin/users']               = 'admin/users';
$route['admin/users/store']         = 'admin/user_store';
$route['admin/users/update/(:num)'] = 'admin/user_update/$1';
$route['admin/users/delete/(:num)'] = 'admin/user_delete/$1';
$route['admin/users/reset_password/(:num)'] = 'admin/user_reset_password/$1';
$route['admin/teachers']            = 'admin/teachers';
$route['admin/students']            = 'admin/students';
$route['admin/activity_log']        = 'admin/activity_log';
// $route['admin/run_ticket_migration'] = 'admin/run_ticket_migration'; // Disabled: exposed migration endpoint

$route['academic/semesters']            = 'academic/semesters';
$route['academic/semesters/store']      = 'academic/semester_store';
$route['academic/semesters/update/(:num)'] = 'academic/semester_update/$1';
$route['academic/semesters/delete/(:num)'] = 'academic/semester_delete/$1';
$route['academic/semesters/activate/(:num)'] = 'academic/semester_activate/$1';

$route['academic/sections']            = 'academic/sections';
$route['academic/sections/store']      = 'academic/section_store';
$route['academic/sections/update/(:num)'] = 'academic/section_update/$1';
$route['academic/sections/delete/(:num)'] = 'academic/section_delete/$1';
$route['academic/sections/status/(:num)'] = 'academic/section_status/$1';
$route['academic/sections/sync/(:num)'] = 'academic/section_sync_subjects/$1';
$route['academic/sections/assign_teacher'] = 'academic/section_assign_teacher';
$route['academic/sections/remove_teacher'] = 'academic/section_remove_teacher';
$route['academic/sections/schedule_save']  = 'academic/section_schedule_save';
$route['academic/sections/schedule_remove'] = 'academic/section_schedule_remove';

$route['academic/rooms']                     = 'academic/rooms';
$route['academic/rooms/demo']                = 'academic/room_demo';
$route['academic/rooms/buildings/store']     = 'academic/building_store';
$route['academic/rooms/buildings/update/(:num)'] = 'academic/building_update/$1';
$route['academic/rooms/buildings/status/(:num)'] = 'academic/building_status/$1';
$route['academic/rooms/buildings/delete/(:num)'] = 'academic/building_delete/$1';
$route['academic/rooms/store']               = 'academic/room_store';
$route['academic/rooms/update/(:num)']       = 'academic/room_update/$1';
$route['academic/rooms/status/(:num)']       = 'academic/room_status/$1';
$route['academic/rooms/delete/(:num)']       = 'academic/room_delete/$1';

$route['academic/programs']            = 'academic/programs';
$route['academic/programs/store']      = 'academic/program_store';
$route['academic/programs/update/(:num)'] = 'academic/program_update/$1';
$route['academic/programs/delete/(:num)'] = 'academic/program_delete/$1';

/*
 * Strand Detail — subjects + curriculum merged into the Strands flow.
 * The standalone Subjects/Curriculum GET pages now just forward to the
 * Strands list; their write actions still work (strand_* routes reuse the
 * same model/validation logic).
 */
$route['academic/strands/(:num)']                = 'academic/strand_detail/$1';
$route['academic/strands/add_subject']           = 'academic/strand_add_subject';
$route['academic/strands/remove/(:num)']         = 'academic/strand_remove/$1';
$route['academic/strands/subject_update/(:num)'] = 'academic/strand_subject_update/$1';
$route['academic/strands/subject_delete/(:num)'] = 'academic/strand_subject_delete/$1';

$route['academic/subjects']            = 'academic/subjects_redirect';
$route['academic/subjects/store']      = 'academic/subject_store';
$route['academic/subjects/update/(:num)'] = 'academic/subject_update/$1';
$route['academic/subjects/delete/(:num)'] = 'academic/subject_delete/$1';

$route['academic/curriculum']            = 'academic/curriculum_redirect';
$route['academic/curriculum/store']      = 'academic/curriculum_store';
$route['academic/curriculum/delete/(:num)'] = 'academic/curriculum_delete/$1';

$route['teacher/dashboard']        = 'teacher/dashboard';
$route['teacher/my_subjects']      = 'teacher/my_subjects';
$route['teacher/encode_grades']    = 'teacher/encode_grades';
$route['teacher/save_grades']      = 'teacher/save_grades';

/*
 * Teacher Tickets & Student Messaging
 */
$route['teacher/tickets']                    = 'teacher/tickets';
$route['teacher/ticket_submit']              = 'teacher/ticket_submit';
$route['teacher/ticket_view/(:num)']         = 'teacher/ticket_view/$1';
$route['teacher/ticket_reply/(:num)']        = 'teacher/ticket_reply/$1';
$route['teacher/message_student']            = 'teacher/message_student';

$route['student/dashboard']        = 'student/dashboard';

/*
 * Student Tickets
 */
$route['student/tickets']                    = 'student/tickets';
$route['notifications']                       = 'notifications/index';
$route['student/ticket_submit']              = 'student/ticket_submit';
$route['student/ticket_view/(:num)']         = 'student/ticket_view/$1';
$route['student/ticket_reply/(:num)']        = 'student/ticket_reply/$1';

/*
 * Student Enrollment
 */
$route['student/enroll_next_semester']       = 'student/enroll_next_semester';

/*
 * Admin Tickets
 */
$route['admin/tickets']                      = 'admin/tickets';
$route['admin/ticket_view/(:num)']           = 'admin/ticket_view/$1';
$route['admin/announcements']                = 'admin/announcements';
$route['admin/announcements/store']          = 'admin/announcement_store';
$route['admin/announcements/delete/(:num)']  = 'admin/announcement_delete/$1';
$route['admin/grade_submission_status']      = 'admin/grade_submission_status';
$route['admin/grade_submission_status_notify'] = 'admin/grade_submission_status_notify';

$route['reports/index']            = 'reports/index';
$route['reports/export_csv']       = 'reports/export_csv';
$route['reports/export_pdf']       = 'reports/export_pdf';

$route['notifications/read/(:num)'] = 'notifications/read/$1';
$route['notifications/read_all']    = 'notifications/read_all';

$route['teacher/request_correction/(:num)'] = 'teacher/request_correction/$1';
$route['teacher/submit_correction_request'] = 'teacher/submit_correction_request';
$route['admin/correction_requests']         = 'admin/correction_requests';
$route['admin/correction_requests/(:num)']  = 'admin/review_correction_request/$1';

/* New features for defense */
$route['student/schedule']            = 'student/schedule';
$route['student/enrollment_history']  = 'student/enrollment_history';
$route['student/report_card']         = 'student/report_card';
$route['teacher/class_list']          = 'teacher/class_list';

/*
 * Admissions — public applicant portal + admin management
 */
$route['applicant/dashboard']        = 'applicant/dashboard';
$route['applicant/exam']             = 'applicant/exam';
$route['applicant/start_exam']       = 'applicant/start_exam';
$route['applicant/submit_exam']      = 'applicant/submit_exam';
$route['applicant/choose_program']   = 'applicant/choose_program';

$route['admin/applicants']           = 'admin/applicants';
$route['admin/applicants/generate_code/(:num)'] = 'admin/applicant_generate_code/$1';
$route['admin/applicants/admit/(:num)']         = 'admin/applicant_admit/$1';
$route['admin/applicants/reject/(:num)']        = 'admin/applicant_reject/$1';
$route['admin/applicants/retake/(:num)']        = 'admin/applicant_retake/$1';
$route['admin/applicants/delete/(:num)']        = 'admin/applicant_delete/$1';

$route['admin/exam_questions']               = 'admin/exam_questions';
$route['admin/exam_questions/store']         = 'admin/exam_question_store';
$route['admin/exam_questions/update/(:num)'] = 'admin/exam_question_update/$1';
$route['admin/exam_questions/delete/(:num)'] = 'admin/exam_question_delete/$1';
$route['admin/exam_questions/toggle/(:num)'] = 'admin/exam_question_toggle/$1';
