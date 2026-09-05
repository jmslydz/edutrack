# EduTrack Backend — Final Audit & Completion Report

Date: 2026-08-15
Scope: full end-to-end verification of every feature chain (view → route →
controller → validation → model → DB → response) across all three roles,
including the student priority items (clickable grades, working notifications),
the previously-disabled admin pages (School Years, Semesters, Sections,
Subjects, Teachers, Students), Faculty My Subjects, Reports logout, and the
remaining broken items from the earlier audit. No rebuilds or framework
changes were performed; everything was fixed inside the existing CodeIgniter
3.1.13 application.

---

## A. Executive Summary

All ten priority items are implemented and verified. Every sidebar item for
every role now points to a real, working page (no more `#` links or "Soon"
placeholders). The notification bell is live end-to-end: grade changes notify
affected students, the badge updates, items open to the correct page and are
marked read, and "Mark all as read" works — all ownership-scoped and CSRF
protected. Four regression suites (75 checks) plus a 26-check CRUD lifecycle
suite and browser automation all pass with zero console errors or failed
requests.

## B. Problems Found

### Original audit (already fixed, verified still green)
1. Student dashboard 500 (stdClass/array mismatch, remarks case mismatch).
2. "Add New User" button navigation-reload closed the modal.
3. Manage Users pagination treated CI3's offset as a page number.
4. Editing an off-page student could silently change the section.
5. Reports page accessible to students (now role-guarded).
6. Forced password change bypassable; production errors leaked internals.

### New work (this session)
7. **Disabled sidebar pages** — School Years, Semesters, Sections, Subjects,
   Teachers, Students (admin) and My Subjects (teacher) rendered as disabled
   "Soon" items with no controller, no routes, and no views. The sidebar header
   comment claimed all links were wired.
8. **Notification bell was disabled** — the topbar button had `disabled` and a
   `title="No notifications yet"`; no notifications feature existed.
9. **`teacher/my_subjects` returned HTTP 500** — `Enrollment_model` built the
   DISTINCT term query with `select('DISTINCT …')`, which CI3 wraps as
   `SELECT \`DISTINCT ...\`` → SQL 1064 syntax error.
10. **`_rel_time()` collision** — topbar's relative-time helper clashed with the
    unconditional `_rel_time()` defined in `admin/dashboard.php`; the login
    POST crashed with "Cannot redeclare _rel_time()".
11. **`_notif_time()` not hoisted** — after renaming, the helper was wrapped in
    `if (!function_exists())`, so it was defined only when execution reached it,
    but the dropdown calls it earlier → student dashboard 500
    ("Call to undefined function _notif_time()"). Moved to the top of the file.
12. **`$this->input->is_post_request()` did not exist** in this CI 3.1.13 build
    → HTTP 500 on every POST guard. Replaced with a direct
    `$_SERVER['REQUEST_METHOD']` check.
13. **Seed SQL lacked the notifications table** — the topbar queries it on every
    page, so a fresh install would 500. Table DDL added to `database/edutrack.sql`.

## C. Root Causes
- Mockup placeholders ("Soon", disabled bell) were never implemented (7, 8).
- `select()` auto-quoting of a raw `DISTINCT` prefix (9).
- Unconditional vs. conditional function declarations colliding across view
  files loaded in the same request (10, 11).
- Assumed CI 3.1.13 Input API that this copy doesn't ship (12).
- Schema and app evolved out of sync (13).

## D. Fixes Applied
1. `sidebar.php` — every nav item now uses `site_url()` to a real route; the
   disabled/"Soon" branch was removed; corrected the header comment.
2. New **notifications feature** (E. below) wired into `topbar.php`,
   `dashboard.css`, `dashboard.js`, `MY_Controller::_render()`.
3. `Enrollment_model::terms_for_teacher()` — `select('DISTINCT …', FALSE)`.
4. `topbar.php` — helper renamed to `_notif_time()` and defined at the very top
   of the file (before any output) so it is hoisted and cannot collide.
5. `MY_Controller::_require_post()` — POST-only guard using
   `$_SERVER['REQUEST_METHOD']` (405 otherwise); applied to every mutating
   method in `Academic`, `Admin` (user store/update/delete/reset), and
   `Teacher::save_grades`, plus `Notifications::read_all`.
6. `database/edutrack.sql` — added `notifications` table (DDL + drop), verified
   by importing into a scratch database.

## E. New Features Implemented

### E1. Notifications (student priority #2)
- **Schema**: `notifications` (id, user_id FK → users ON DELETE CASCADE,
  title, body, link, is_read, created_at; composite index on
  user_id+is_read+created_at).
- **Model** `Notification_model.php`: `create`, `create_many`, `unread_count`,
  `recent`, `mark_read`, `mark_all_read` — all ownership-scoped by user id.
- **Controller** `Notifications.php`: `read/{id}` (ownership check; foreign id
  is rejected, never leaks) and `read_all` (POST-only).
- **Topbar**: live bell button, unread-count badge (`.notif-dot`), dropdown
  panel listing the 6 most recent notifications with relative timestamps,
  item links → `notifications/read/{id}`, "Mark all as read" CSRF form,
  empty state. `toggleNotifications()` + click-outside close in `dashboard.js`.
- **Generation points**: `Teacher::save_grades` (on any student's grade change,
  diffed old vs new, after `trans_complete` → "Grade updated: CS101 — BSIT-1A"
  with link `student/dashboard#grades`); `Admin::user_store` ("Welcome to
  EduTrack"); `Admin::user_reset_password` ("Your password was reset").

### E2. Academic administration (no more "Soon")
- **`Academic.php`** (extends `Admin_Controller`): full CRUD for
  School Years, Semesters, Sections, Subjects — list + add/edit modal +
  delete/activate, all POST-only. Validation: school year must match
  `^S\.Y\.\s\d{4}-\d{4}$`; semester ∈ {1st Semester, 2nd Semester, Summer};
  section `^[A-Z0-9\-]{2,20}$`; subject code `^[A-Z0-9\-]{2,15}$`, title
  3–120 chars, units 0.5–12.0. Duplicates rejected. Delete blocked by FK
  guard when records reference the row. Activation is atomic (transaction
  clears then sets the single active school year / semester).
- **`Academic_model.php`**: write methods for all four entities.
- **Views**: `admin/school_years.php`, `semesters.php`, `sections.php`,
  `subjects.php` (list + modal form + activate/delete POST forms).

### E3. Directories (no more "Soon")
- **`Admin::teachers()` / `Admin::students()`**: read-only role-filtered
  directories with search, pagination, and (students) section filter.
  Views `admin/teachers.php`, `admin/students.php`.

### E4. Faculty My Subjects (no more "Soon")
- **`Teacher::my_subjects()`** + view `teacher/my_subjects.php`: classes grouped
  by school year/term, current-term badge, per-period encoded progress bars
  (Prelim/Midterm/Final %), Encode Grades deep links.

## F. Files Modified / Created
- `application/controllers/Academic.php` (new), `Notifications.php` (new),
  `Admin.php`, `Teacher.php`, `Reports.php`.
- `application/models/Notification_model.php` (new), `Academic_model.php`,
  `Enrollment_model.php`.
- `application/core/MY_Controller.php` (`_render` notification data,
  `_require_post()`, `_require_roles()`).
- `application/config/routes.php` (academic/*, admin/teachers|students,
  teacher/my_subjects, notifications/read/(:num)|read_all).
- `application/views/partials/sidebar.php`, `topbar.php`.
- `application/views/admin/{school_years,semesters,sections,subjects,teachers,students}.php` (new).
- `application/views/teacher/my_subjects.php` (new).
- `assets/css/dashboard.css`, `assets/js/dashboard.js` (notification styles + toggle).
- `database/edutrack.sql` (notifications table).

## G. Verification (all PASS)

### Regression suites
- `test_pages.php` — 27 checks (every page per role 200, grade rows, remarks
  badges, GWA/honor, progress bars, foreign-selection rejection, 404 on
  unknown route).
- `authz_matrix.php` — 18 checks (logged-out → login; cross-role 403s; foreign
  assignment/`?edit=999` graceful; exports scoped).
- `test_full.php` — 20 checks (login/logout, reports tiles + section scoping,
  encode/save flow, grade save flash, IDOR block).
- `test2.php` — 10 checks (CSV/PDF export, forgot-password non-revealing,
  user create → forced change → re-login, IDOR save rejected).
- `test_academic_crud2.php` — 26 checks (create, duplicate + format
  rejections, edit, activate + reactivate, FK delete blocks, full delete
  lifecycle) — **ALL PASS** after POST guards.
- `test_new_pages.php` — all 8 new pages HTTP 200 with expected content and
  the bell enabled.

### Browser automation (real headless browser)
- **Notifications**: teacher changes a grade → student's bell shows badge `1`,
  dropdown lists "Grade updated: CS101 — BSIT-1A" (unread-styled), click opens
  `student/dashboard#grades` with the grades section in view, badge cleared,
  item marked read; "Mark all as read" clears badge and unread items.
  Zero console errors / failed requests.
- **Full sweep**: admin (dashboard, school_years, semesters, sections,
  subjects, teachers, students, reports), teacher (dashboard, my_subjects,
  encode_grades, reports), student (dashboard) — all render 200, no exception
  text, bell enabled on every page. Zero console errors / failed requests.
- All modified/new PHP files pass `php -l`.

## H. Security Verification
- Role guards enforced server-side (`MY_Controller` subclasses); views never
  decide authorization.
- Notifications are ownership-scoped: `read/{id}` resolves against the logged-in
  user; a foreign notification id is rejected.
- All state-changing endpoints (`academic/*` store/update/delete/activate,
  `admin` user mutations, `teacher/save_grades`, `notifications/read_all`) are
  POST-only (`_require_post()`, HTTP 405 on GET) and CSRF-protected via
  `form_open()`.
- IDOR: grades/assignments lookups remain ownership-scoped; tampering resolves
  to empty results and is rejected.
- Passwords hashed with `password_hash()`; reset tokens SHA-256; `grade_logs`
  audit trail intact; login throttling in place; production error pages do not
  expose internals.
- Seed data restored to pristine state (20 users, 13 students, 6 sections, 6
  subjects, active S.Y. 2025-2026 / 2nd Semester); all QA rows removed;
  `database/edutrack.sql` imports cleanly into a scratch database (fresh-install
  path verified including the new notifications table).

## I. Remaining Issues / Notes
- **"Keep me signed in"** login checkbox remains cosmetic (no remember-me
  cookie) — intentionally out of scope.
- Google Fonts requests fail in this offline environment (visual-only).
- `C:\xampp\mysql\data\edutrack_corrupt` (leftover from the earlier crash) can
  be deleted to reclaim space.
- Section **BSCS-1A** was re-inserted after the CRUD lifecycle test deleted it
  (it was legitimately deletable as empty) to restore seed fidelity.
- The `notifications` table currently contains no seed rows; the first
  notifications appear when grades are changed or users are created/reset.