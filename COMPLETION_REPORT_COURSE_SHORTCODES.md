# EduTrack — Completion Report: Course Short Codes + Auto-Suggested Section Names

Date: 2026-08-18
Scope: `programs.short_code` + "Course" labels + section-name auto-fill
(`{short_code}{year}R{sequence}`). Display-label change only — the `programs`
table and its existing columns/routes/controller method names were NOT renamed.

---

## 1. Migration applied — `short_code` column confirmed

- New file: `database/migration_2026_programs_short_codes.sql`

  ```sql
  ALTER TABLE programs
      ADD COLUMN short_code VARCHAR(10) NULL AFTER program_code,
      ADD UNIQUE KEY uq_program_shortcode (short_code);
  ```

- Applied to the live `edutrack` DB. Confirmed via `SHOW COLUMNS FROM programs`:

  | Field | Type | Null | Key | Extra |
  |---|---|---|---|---|
  | id | int(10) unsigned | NO | PRI | auto_increment |
  | program_code | varchar(20) | NO | UNI | |
  | **short_code** | **varchar(10)** | **YES** | **UNI** | |
  | program_name | varchar(150) | NO | | |

- `database/edutrack.sql` updated so a fresh import ships the same column
  (and seed short codes, see §2), keeping schema-in-file == schema-in-DB.

## 2. Backfill suggestions applied (ADMIN-REVIEWABLE — not final)

- New file: `database/backfill_2026_programs_short_codes.php` (idempotent CLI;
  never overwrites a set short_code).
- Rule: strip a known leading degree prefix (`BS`, `AB`, `BA`, `BE`) from the
  program code; unknown prefixes keep the code; empty/unsafe results are left
  NULL and flagged. Applied values:

  - **BSIT → IT**
  - **BSCS → CS**
  - **BSECE → ECE**

- These are **suggestions only**. The admin should review them on the Manage
  Courses page and can change any of them; the field is editable and
  authoritative. (An earlier draft stripped `BSECE` → `CE` because `BSE` was in
  the prefix list; that prefix was removed so the strip yields `ECE`.)

## 3. Manage Courses page — `short_code` field, add/edit, duplicate rejection

There was no program CRUD page in the app yet, so one was built following the
existing `subjects`/`sections` patterns:

- **Routes** (`application/config/routes.php`):
  `academic/programs`, `academic/programs/store`, `academic/programs/update/(:num)`,
  `academic/programs/delete/(:num)`.
- **Controller** (`application/controllers/Academic.php`): `programs()`,
  `program_store()`, `program_update()`, `program_delete()` + `_validate_program()`.
  Delete is guarded (blocked when sections or curriculum rows reference the course).
- **Model** (`application/models/Academic_model.php`): `create_program()`,
  `update_program()`, `delete_program()`; `sections()` now also selects
  `programs.short_code` (needed by the section-name auto-fill).
- **View**: `application/views/admin/programs.php` (new) — "Manage Courses",
  table (Code / Course Name / Short Code / Sections / Actions), Add/Edit modal
  with a `short_code` input + suggestion JS.
- **Server-side `short_code` validation**: required, `/^[A-Z0-9]{1,10}$/`
  (uppercase alnum only, max 10), uppercase-normalized, and unique using the
  same duplicate-check style as username/email/code elsewhere
  (`where('id !=', $except_id)->count_all_results()` → "That short code is
  already used by another course.").

**Tested (HTTP end-to-end, admin session, CSRF-aware):** create course
BSSE/SE ✅, duplicate short code rejected ✅, duplicate course code rejected ✅,
missing short code rejected ✅, invalid short code (dash) rejected ✅, lowercase
input uppercased on submit ✅, edit prefills and persists ✅, delete of an unused
course ✅, delete of BSIT (in use) blocked ✅.

## 4. "Program"/"Programs" → "Course"/"Courses" — label changes

Visible text only; routes/controller method names/table/columns untouched.

- `application/views/partials/sidebar.php` — new sidebar item "Manage Courses"
  (route `academic/programs`), placed after "Sections".
- `application/views/admin/programs.php` — new page, all labels "Course"
  (page title "Manage Courses", "Add Course" button, table headers
  "Course Name", modal labels "Course Code" / "Course Name" / "Short Code").
- `application/views/admin/curriculum.php` — subtitle "…each course teaches…",
  filter label "Course", "Select course…", empty-state "Select a course, year
  level and semester…".
- `application/views/admin/sections.php` — table header "Course / Year",
  form label "Course", "Select course…", "…set the course/year level first",
  section-name hint now references the "IT4R1" auto-fill format.
- `application/controllers/Academic.php` — admin-facing flash/validation strings:
  "A valid course is required." (×2), "…no course/year/semester…",
  "…no course/year level set…", section-name example now "IT4R1".
- New course CRUD flash messages are already "Course created/updated/deleted."

## 5. Add Section — auto-suggested name (client-side, no AJAX needed)

`assets/js/dashboard.js` `bindSectionNameSuggestion()` + a JSON data block
(`#secAutoData`, server-rendered in `sections.php`) containing each program's
id/short_code and each section's name/program_id/year_level.

- Pattern: `{short_code}{year_level}R{next_sequence}`, e.g. "IT4R1".
- Sequence = **highest existing sequence** parsed from matching section names
  for that exact course+year (regex `^{prefix}{year}R(\d+)$`), then +1 — NOT a
  plain `COUNT(*)`, so a mid-sequence deletion never produces a duplicate name.
- Short code missing → falls back to the program code (defensive; required on
  new courses).

**Verified in a real browser (Playwright):**
- Fresh course+year (no sections yet): BSCS year 5 → **CS5R1** (starts at 1) ✅;
  BSECE year 2 → **ECE2R1** ✅.
- Existing sequence continues: BSIT year 4 with IT4R1 + IT4R3 (IT4R2 deleted
  mid-sequence) → **IT4R4** ✅ (max-seq=3, not count=2).
- Re-selecting the same course/year keeps the correct value (IT4R4) ✅.
- Course with a set short code (XY) → XY1R1 ✅.

## 6. Section Name stays freely editable after auto-fill

- The input is a normal text input — verified no `readonly`/`disabled`
  attributes in the rendered DOM ✅.
- Auto-fill only pre-populates; once the admin types a manual value it stops
  being clobbered (verified: type "CUSTOM", change year → value stays
  "CUSTOM") ✅.
- Server-side validation unchanged: section name remains "required, 2–20
  letters/numbers/dashes" — no strict pattern lock, so a manually typed name
  that doesn't match the auto format is still accepted ✅.

## 7. `php -l` clean on all touched files

```
application/controllers/Academic.php        — No syntax errors
application/models/Academic_model.php       — No syntax errors
application/config/routes.php               — No syntax errors
application/views/admin/programs.php        — No syntax errors
application/views/admin/sections.php        — No syntax errors
application/views/admin/curriculum.php      — No syntax errors
application/views/partials/sidebar.php      — No syntax errors
database/backfill_2026_programs_short_codes.php — No syntax errors
assets/js/dashboard.js                      — node --check clean
```

## Regression

- Admin pages 200 + render (Manage Courses, Manage Curriculum, Sections,
  Subjects) ✅.
- Teacher login, dashboard, My Subjects, Encode Grades, Reports all 200 ✅.
- No console/page errors on the sections and programs pages (Playwright) ✅.
- DB left in pristine seed state + backfilled short codes:
  BSIT/IT, BSCS/CS, BSECE/ECE; 7 original sections intact; all test rows removed.

## Known caveats

- The three backfilled short codes are suggestions (see §2) — ask the admin to
  confirm them on the Manage Courses page.
- If the migration is re-run on an already-migrated DB it will error
  (duplicate column) — it is a one-time migration, same as the previous round's.
