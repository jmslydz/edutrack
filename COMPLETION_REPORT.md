# EduTrack Backend — Completion Report

Date: 2026-08-15
This report covers the implementation required by the backend prompt, page by
page, with the specific items requested: files created/modified, database
changes, routes, features implemented vs. placeholder, tests actually run, and
known remaining issues. It complements `AUDIT_REPORT.md` (full A–I audit).

---

## 1. `auth/login.php` — Auth::login() — COMPLETE
- **Files:** `application/controllers/Auth.php`, `application/views/auth/login.php`,
  `application/models/Login_attempt_model.php`, `application/models/User_model.php`.
- **DB changes:** none (uses `users`, `login_attempts` as designed).
- **Routes:** `auth/login` (GET form / POST process).
- **Implemented:** form posts to the real controller (no `action="#"`, no
  blocking placeholder JS — client validation only prevents submit on
  obviously-invalid input); server validation (email format, password
  required, `min_length[8]`/`max_length[72]`); `password_verify()`; generic
  "Invalid email or password" (never reveals account existence); role-based
  redirect to `admin/dashboard`, `teacher/dashboard`, `student/dashboard`;
  role comes only from `users.role` after successful auth; session
  regeneration on login; every attempt recorded in `login_attempts`
  (email, ip, success); lockout after 5 failed attempts within a rolling
  15-min window with a countdown message.
- **Fixed this pass:** `Login_attempt_model::prune()` (30-day history cleanup)
  is now called on every login POST (was defined but never invoked).
- **Tests:** login success for all three roles; wrong password, inactive
  account, and lockout message verified in earlier suites; `test_pages.php`,
  `test2.php`, `authz_matrix.php` all green after the prune change.

## 2. `auth/forgot_password.php` — Auth::forgot_password() / reset_password() — COMPLETE
- **Files:** `application/controllers/Auth.php`, `application/models/Password_reset_model.php`,
  `application/views/auth/forgot_password.php`, `application/views/auth/reset_password.php`,
  `application/config/email.php`.
- **DB changes:** none (uses `password_resets` as designed).
- **Routes:** `auth/forgot_password`, `auth/reset_password/(:any)`.
- **Implemented:** token is `random_bytes()`-based, only the SHA-256 hash is
  stored, 60-min expiry, single-use; generic "check your inbox" shown whether
  or not the account exists; `reset_password` validates token (used=0,
  expires_at > now), hashes the new password, clears `must_change_password`,
  marks the token used. SMTP send attempted via Gmail config (see notes).
- **Fixed this pass:** after a successful reset, all other outstanding tokens
  for that email are now invalidated (`Password_reset_model::invalidate_all_for_email()`,
  called in `Auth::reset_password()`). Also rewrote `config/email.php` to Gmail
  SMTP (TLS 587) per the agreed approach — with placeholder credentials.
- **Tests:** bogus token → graceful error; two valid tokens → reset with one
  marks BOTH used (verified: tokA used=1, tokB used=1); login with new
  password succeeds; seed password restored afterward. `test2.php` forgot
  password / bogus token checks pass.

## 3. `admin/dashboard.php` — Admin::dashboard() — COMPLETE (real data)
- **Files:** `application/controllers/Admin.php`, `application/views/admin/dashboard.php`,
  `application/models/Grade_model.php`, `Academic_model.php`.
- **Implemented:** Total Students / Total Teachers / Active School Year /
  Semester from real queries; "Recent Activity" from `grade_logs` joins (last 5
  changes); "Grade Submission Status" bars from real `# grades entered / #
  enrolled students` per assignment per period (Midterm + Final). No
  placeholder data.
- **Tests:** renders stat cards, activity list, and progress bars; verified in
  `test_pages.php`, `test_full.php`, and the browser sweep.

## 4. `admin/manage_users.php` — Admin_Users (Admin::users/store/update/reset/delete) — COMPLETE
- **Files:** `application/controllers/Admin.php`, `application/views/admin/manage_users.php`,
  `application/models/User_model.php`, `Enrollment_model.php`.
- **DB changes:** `users.must_change_password` already present.
- **Routes:** `admin/users`, `admin/users/store`, `admin/users/update/(:num)`,
  `admin/users/delete/(:num)`, `admin/users/reset_password/(:num)`.
- **Implemented:** real pagination (CI3 offset semantics), search + role/status
  filters wired; add/edit modal validates first/last name, unique username,
  email, role, section-required-for-student; temporary password hashed and
  shown once; `must_change_password` forced on create and on admin reset;
  status toggle persists to `users.status`; delete confirms via JS `confirm()`.
- **Delete decision (flagged, per prompt):** `user_delete` is a **hard DELETE**
  but is blocked (with a message) whenever the user has related records
  (`teacher_subject_assignments`, grades, `grade_logs`, student grade rows) —
  the guard explicitly tells the admin to set the account to **Inactive**
  instead, which is the soft-delete path. So records are never orphaned and
  `grade_logs` accountability is preserved.
- **Tests:** CRUD create/edit/status toggle/reset-password/delete, filters,
  pagination page 2, edit-modal section preselection — verified earlier
  (browser) and by `test2.php` (create → forced change → re-login).

## 5. `teacher/dashboard.php` — Teacher::dashboard() — COMPLETE
- **Files:** `application/controllers/Teacher.php`, `application/views/teacher/dashboard.php`,
  `application/models/Enrollment_model.php`.
- **Implemented:** subject cards scoped to `session.user_id` via
  `teacher_subject_assignments` for the active semester (never request-supplied
  teacher_id); progress bars from real counts for **Midterm + Final** (Prelim
  removed this pass). "My Subjects" page and Encode links are functional.
- **Tests:** subject cards + progress bars verified (`test_pages.php`,
  `qa_2period.php`).

## 6. `teacher/encode_grades.php` — Teacher::encode_grades() / save_grades() — COMPLETE (highest security priority)
- **Files:** `application/controllers/Teacher.php`, `application/views/teacher/encode_grades.php`,
  `application/models/Grade_model.php`, `Enrollment_model.php`,
  `assets/js/dashboard.js`.
- **Routes:** `teacher/encode_grades`, `teacher/save_grades`.
- **Implemented:** selected subject+section+semester ownership verified against
  `teacher_subject_assignments` before rendering (403 on tamper); on save every
  value re-validated (numeric, 1.00–5.00, student actually enrolled); upsert
  on the `UNIQUE(student_id, subject_id, grading_period_id)` natural key (no
  duplicate inserts) via `Grade_model::upsert()`; every insert/update writes a
  `grade_logs` row (old/new value, changed_by = session user); `_require_post()`
  on save (405 on GET); the save-confirmation banner renders only from real
  flashdata after a successful POST — never optimistic.
- **Two-period model (updated this pass):** only **Midterm** and **Final** are
  offered in the Grading Period filter (Prelim removed). The grading period is
  resolved server-side from the class's semester via the `grading_periods`
  table (`Academic_model::grading_period_id()`), and grades are stored against
  `grades.grading_period_id` with the class's `teacher_id`.
- **Clear All note (flagged):** "Clear All" clears the form inputs client-side
  only (UX); actual clearing of stored grades happens server-side through the
  normal Save flow (blank fields → NULL upsert → grade_logs). There is no
  separate server-side clear-all endpoint; clearing is never persisted without
  a real Save, and the success banner never shows without a successful POST.
- **Tests:** ownership rejection (foreign subject/section → 403/redirect),
  range validation (6.0 / 1.12 rejected, no partial save), save + audit trail,
  IDOR save on a truly foreign assignment (id 4, MATH101 — owned by a
  different teacher) rejected — verified by `test_pages.php` / `test_full.php`
  / `authz_matrix.php` / `qa_change_cycle.php`.

## 7. `student/dashboard.php` — Student::dashboard() — COMPLETE (highest security priority)
- **Files:** `application/controllers/Student.php`, `application/views/student/dashboard.php`,
  `application/models/Grade_model.php`.
- **Implemented:** grades and GWA filtered **only** by the logged-in user's
  resolved student row (never by URL/form/student_id input — no IDOR surface).
- **Two-period grading (updated this pass):** the table shows **Midterm** and
  **Final** per subject plus a computed **Final Grade** column = the 50/50
  average of Midterm + Final (shown only when both periods exist, otherwise
  "—"). GWA is the **credit-unit weighted average of subject Final Grades
  across subjects with a complete Final Grade**; honors derive from GWA using
  the school's thresholds (`≤1.20` With Highest Honors, `≤1.45` With High
  Honors, `≤1.75` With Honors), identical to the Reports logic.
- **Tests:** rows + remarks badges + Final Grade column + GWA + honor (Juan
  Dela Cruz, 4 of 5 subjects complete → GWA 1.44 "With High Honors", units 14),
  `#grades` anchor scroll — verified by `test_pages.php`, `qa_2period.php`.

## 8. `reports/index.php` — Reports::index() / export_pdf() / export_csv() — COMPLETE
- **Files:** `application/controllers/Reports.php`, `application/models/Report_model.php`,
  `application/views/reports/index.php`, `composer.json`.
- **DB changes:** none.
- **Routes:** `reports/index`, `reports/export_csv`, `reports/export_pdf`.
- **Implemented:** role scope — admin can view/filter any section/teacher;
  teacher queries are always constrained server-side to their own
  `teacher_subject_assignments` regardless of submitted filters; CSV via native
  `fputcsv()` streamed with correct headers; PDF via **mPDF 8** (Composer,
  `vendor/` installed, CI `composer_autoload` enabled) generated from the same
  filtered dataset as the page. Export links are GET (read-only, no state
  change — CSRF not applicable).
- **Two-period grading (updated this pass):** grade summary GWA and honor roll
  are computed from subject **Final Grades** (unit-weighted average across
  subjects with a complete Final Grade); subject performance reports the
  average **Final Grade** per subject with passed/failed by Final Grade. Grades
  are pulled through `grading_periods` (Midterm/Final only).
- **Tests:** CSV content-type + rows (Juan GWA 1.44, units 14, "With High
  Honors" verified), PDF content-type + size, teacher scoping ("No All Sections
  option" + server-side enforcement), student access blocked (403) — verified
  by `test2.php`, `test_full.php`, `authz_matrix.php`, `qa_csv_gwa.php`.

---

## Cross-cutting requirements — status
1. **Session & route protection:** `MY_Controller` + `Admin_Controller` /
   `Teacher_Controller` / `Student_Controller` enforce session role on every
   request (unauth → login; wrong role → 403). Reports uses
   `_require_roles(['admin','teacher'])`.
2. **CSRF:** `csrf_protection = TRUE` in `config.php`; all POST forms use
   `form_open()`; no CSRF exclusions.
3. **Server-side validation:** present on every POST path, independent of the
   client-side JS (which is documented as UX only).
4. **Error handling:** `MY_Exceptions::show_php_error()` logs full detail and
   shows a generic 500 page outside `development`; no SQL/paths/backtraces to
   visitors.
5. **Parameterized queries:** CI3 Query Builder / bound params everywhere; no
   string-concatenated SQL with user input.

## Two-period migration (this pass)
- **Schema (now authoritative, matches the updated prompt):**
  - `grading_periods(id, semester_id FK, period_name ENUM('Midterm','Final'), weight_percent)` —
    seeded 50.00/50.00 for every semester.
  - `grades(id, student_id FK, subject_id FK, teacher_id FK, grading_period_id FK,
    grade_value, date_recorded, UNIQUE(student_id, subject_id, grading_period_id))`.
  - Prelim removed everywhere (schema, views, filters, progress bars, reports).
- **Live data migrated** keeping grade ids stable (grade_logs FKs intact):
  88 grades kept (50 Midterm + 38 Final), 53 Prelim rows dropped, orphaned
  logs cleaned → 4 audit rows remain.
- **Code refactor:** `Grade_model` (natural-key upsert on student+subject+period,
  `student_term_grades` returns midterm/final/final_grade, progress for 2
  periods, recent-activity joins via students), `Enrollment_model` (unchanged
  API — still ownership-scoped), `Academic_model` (+grading-period lookups),
  `Teacher` encode/save (period resolved from the semester), `Student`
  dashboard (GWA over complete Final Grades), `Report_model` (Final Grade
  based GWA/honor roll/subject performance), and the affected views.

## Tests actually run (after the two-period refactor)
- `test_pages.php` (27), `authz_matrix.php` (18 — IDOR test now targets a truly
  foreign assignment, id 4/MATH101), `test_full.php` (20), `test2.php` (10),
  `test_academic_crud2.php` (26) — ALL PASS.
- `qa_2period.php` (20) — student table has Midterm/Final/Final Grade columns
  and no Prelim/Average; encode filter offers only Midterm/Final; teacher
  dashboard progress shows 2 periods; admin reports render.
- `qa_change_cycle.php` + DB check — save a changed Midterm grade → `grade_logs`
  old/new/changed_by written, `teacher_id` persisted, `date_recorded` bumped;
  DB restored to seed afterward.
- `qa_csv_gwa.php` — grade_summary CSV shows Juan GWA 1.44 / units 14 / "With
  High Honors", matching the unit-weighted Final Grade calculation.
- Fresh import of `database/edutrack.sql` into a scratch DB: 88 grades
(38 Midterm + 50 Final, 0 Prelim), 12 grading periods, 5 grade_logs.
- All changed PHP files pass `php -l`.
- DB left in pristine seed state (88 grades, 5 grade_logs, 0 notifications,
  0 leftover QA rows).

## Bug-fix pass (2026-08-15): grade submission % >100% and CS201 navigation
- **Bug #1 (fixed):** Grade submission percentage could exceed 100% (e.g.
  CS101–BSIT-1B showed 500% Final on the teacher dashboard and 300% on the
  admin dashboard). Root cause in `Grade_model::progress_for_assignment()`: the
  numerator counted **every** non-null grade for the subject+period across
  **all** sections, while the denominator was the count of students in only one
  section. Fix: the numerator is now scoped to the section's own students
  (`where_in('student_id', ids)` from `students.section_id`) and the function
  short-circuits with 0 encoded when the roster is empty (divide-by-zero guard).
  `overall_progress_for_assignment()` (admin dashboard) inherits the fix; the
  views already guarded `total > 0`. Verified against manual SQL counts:
  CS101–BSIT-1A 0%/91% (0+10 of 11), CS101–BSIT-1B 100%/0% (2+0 of 2), CS201
  100%/0% (1 of 1), NSTP101 73% (8 of 11), admin CS101–BSIT-1B overall 50%
  (was 300%). No percentage exceeds 100% anywhere.
- **Bug #2 (investigated, not reproducible):** the CS201 card (BSIT-2A, 1
  student, 100%/0%) was reported as failing to navigate. Checks: (A) no
  NaN/division issue — the code guards empty rosters and CS201 renders 100%/0%;
  (B) the card link is well-formed and identical in shape to CS101's
  (`teacher/encode_grades?subject=CS201&section=BSIT-2A&period=Midterm`); (C)
  data valid — assignment 3 exists, roster = student 13, grade 140 Midterm
  2.00 intact. Real-browser (Playwright) clicks from both `teacher/dashboard`
  and `teacher/my_subjects` navigate to a fully rendered Encode Grades page
  with zero console errors, zero page errors, and zero failed requests. After
  the Bug #1 fix, end-to-end navigation was re-verified. Note: only the
  "Encode Grades" button is a link; the card body itself is not clickable on
  any card (consistent across CS101 and CS201).
- **Regression:** `test_pages.php` (27), `authz_matrix.php` (18), `test_full.php`
  (20), `test2.php` (10), `test_academic_crud2.php` (26), `qa_2period.php`
  (20) — ALL PASS after the fix. All changed files pass `php -l`.

## Full system audit (2026-08-15): per-page results
212 automated checks, 0 FAIL. Counts = checks run (all PASS unless noted).

| Page | Checked | Found broken | Fixed | Re-tested | Still broken |
|---|---|---|---|---|---|
| 1. Login (incl. lockout) | 26 | 0 | – | 26 PASS | 0 |
| 2. Forgot Password | 10 | 0 | – | 10 PASS | 0 |
| 3. Admin Dashboard | 12 | 1 (seed periods 2/9 → bars 0%) | seed remapped to periods 3/4 | 12 PASS | 0 |
| 4. Manage Users | 62 | 0 | – | 62 PASS | 0 |
| 5. Teacher Dashboard | 12 | 0 | – | 12 PASS | 0 |
| 6. My Subjects / Encode Grades | 36 | 1 (ownership warning silently swallowed) | flashdata in `Teacher::encode_grades()` | 36 PASS | 0 |
| 7. Student Dashboard | 16 | 0 | – | 16 PASS | 0 |
| 8. Reports | 24 | 0 | – | 24 PASS | 0 |
| General: session/logout/password | 14 | 0 | – | 14 PASS | 0 |

- **Seed data bug (real, fixed):** pristine seed grades sat on `grading_period_id`
  2 (sem1 Final) and 9 (sem5 Midterm) while all assignments are `semester_id=2`
  (period 3 = Midterm, 4 = Final), so progress bars showed 0% on a fresh
  import. Regenerated `database/edutrack.sql` grades block (periods 3/4; same
  88 grades: 38 Midterm + 50 Final). Verified via fresh import into a scratch
  DB, and student-facing values unchanged (period names map identically).
- **Ownership-warning bug (real, fixed):** the "not assigned to this
  subject/section" warning in `Teacher::encode_grades()` was assigned to a
  local `$flash_error` variable that was never passed to the view, so the
  warning was silently swallowed. Now uses `set_flashdata('grade_error', ...)`;
  verified the warning renders. `php -l` clean.
- Full regression sweep after fixes: `test_pages.php` (27), `authz_matrix.php`
  (18), `test_full.php` (20), `test2.php` (10), `test_academic_crud2.php` (26),
  `qa_2period.php` (20) — ALL PASS.
- Playwright console sweep (admin, teacher, student, all nav pages): zero
  console errors, zero page errors, zero HTTP >= 400, zero failed requests.
- DB restored to pristine corrected seed (20 users, 88 grades, 5 grade_logs,
  12 grading periods, 0 notifications/login_attempts/password_resets).

## Mandated fixes (2026-08-16) — all three implemented and verified
- **FIX 1 — `config.php`: `cookie_httponly` set to `TRUE`.** Session cookie now
  carries `HttpOnly` (verified in the `Set-Cookie` header) and login/app flow
  still works. Blocks JS from reading `edutrack_session`.
- **FIX 2 — temp password is shown to the admin once.**
  - `Admin::user_reset_password()`: success flash now includes
    `Temporary password: <temp> — copy this now and share it with the user
    directly. It will not be shown again after you leave this page.` The reset
    notification no longer falsely claims the password was "sent" — it now says
    "use the temporary password your administrator gave you".
  - `Admin::user_store()`: when the password field is left blank (auto-generated),
    the success flash includes the generated temp password; when the admin types
    one, the message stays generic (no leak). Verified both branches.
  - Verified end-to-end: reset a user → temp password appears in flash → login as
    that user with the shown temp password succeeds → forced to change password.
  - No plaintext is persisted: only the hash is stored; the temp value exists
    solely in the one-time flash.
- **FIX 3 — `Grade_model::upsert()` now audits initial encodes.** The INSERT
  branch calls `_log($new_id, NULL, $grade, $encoded_by)` after inserting.
  Verified: a brand-new grade row (student/subject/period with no prior grade)
  writes a `grade_logs` row with `old_value = NULL`, `new_value = <entered>`,
  `changed_by = <teacher>`.
- Audit assertions updated to the new mandated behavior (Page 4 flash/notification
  checks + a new "shown temp password logs the user in" check; Page 6 now asserts
  the initial-encode log row instead of its absence). Full sweep re-run:
  Page 1–8 + General = 212 checks, 0 FAIL. Regression suites ALL PASS. `php -l`
  clean on all changed files.
- DB restored to pristine corrected seed (20 users, 88 grades, 5 grade_logs,
  0 notifications/login_attempts/password_resets).

## Known remaining issues / flagged decisions
- **Email delivery:** `config/email.php` now targets Gmail SMTP but the
  username/password are placeholders — must be filled with real credentials
  (ideally an app password) before production. Sends are best-effort and
  failures are logged, never shown to the user (by design).
- **"Clear All"** on Encode Grades is client-side field clearing; persistence
  happens via a normal Save. No standalone server-side clear endpoint.
- **User delete** hard-deletes only when no related records exist; otherwise
  blocked with guidance to set Inactive (soft-delete). The guard, not an
  automatic soft-delete, was chosen deliberately to keep behavior explicit.
- **"Keep me signed in"** login checkbox remains cosmetic (out of scope).
- Google Fonts requests fail offline (visual only). `edutrack_corrupt` datadir
  can be deleted.