# COMPLETION REPORT — SHS Pivot: College Programs → Senior High School Strands

This replaces the earlier college-context "short code + IT4R1 auto-naming" work.
All college test data (BSIT/BSCS/BSECE + related rows) was permanently deleted
(explicitly approved; no backup was requested or taken). The existing
`short_code` column and auto-naming code were ADAPTED to the SHS design rather
than rebuilt a second time.

## 1. Deletion — row counts removed per table, no orphaned student accounts

Schema was inspected live first (`information_schema`) and the FK-safe delete
order was derived from the actual relationships (children before parents).
Rows removed by `database/delete_2026_college_data.php`:

| Table                        | Rows removed |
|------------------------------|-------------:|
| grade_logs                   | 14*          |
| grades                       | 88           |
| section_subjects             | 11           |
| teacher_subject_assignments  | 7            |
| curriculum_subjects          | 6            |
| students                     | 13           |
| sections                     | 7            |
| programs                     | 3            |
| subjects                     | 6            |
| users (student logins)       | 13           |
| **TOTAL**                    | **168**      |

\* `grade_logs` was deleted during the script's first invocation, which then
hit a PHP return-type bug (`q()` declared `mysqli_result` but `DELETE` returns
bool) and aborted after the very first DELETE. The bug was fixed
(`q(): mixed`, asserts on failure) and the script re-ran cleanly to
completion; the successful pass reported `grade_logs 0` because it was already
empty, and the original pre-pivot inspection counted 14.

- **No orphaned student accounts:** after deletion, `SELECT COUNT(*) FROM users
  WHERE role='student'` → **0**. The 13 student logins were captured from
  `students.user_id` before the student rows were removed and deleted together
  with them.
- **Kept (untouched):** teacher/admin `users` (7), `school_years` (4),
  `semesters` (8), `grading_periods` (12).

## 2. Migration — `short_code` confirmed already present

The column was added by the earlier college-context migration
(`database/migration_2026_programs_short_codes.sql`). Verified live:

```
Field: short_code   Type: varchar(10)   Null: YES   Key: UNI
```

No duplicate-column DDL was run.

## 3. Seed — 4 SHS strands inserted

`database/seed_2026_shs_strands.sql` (INSERT IGNORE, idempotent). Verified via
`SELECT program_code, short_code FROM programs`:

```
STEM   STEM
ABM    ABM
HUMSS  HUMSS
GAS    GAS
```

`short_code` IS the strand name itself.

## 4. Validation — `year_level` now rejects anything outside 11–12

Server-side (all centralized in `application/controllers/Academic.php`):

- `_validate_section_program_slot()`: `$year_level < 11 || $year_level > 12` →
  `"Year level must be 11 or 12 (Grade 11/Grade 12)."` (used by section create/update)
- `_validate_curriculum_slot()`: same rule (used by curriculum slot add)
- `curriculum()` GET filter: `$year_level >= 11 && $year_level <= 12` else slot invalid

Views now use **Grade 11 / Grade 12** option lists instead of 1–6 numeric
pickers (`admin/sections.php`, `admin/curriculum.php`).

Verified: POST section `year_level=5` and `year_level=0` → rejected; POST
curriculum `year_level=5` → rejected; GET curriculum `year_level=5` → shows the
"Select a strand, grade level and semester…" placeholder.

## 5. Section auto-naming — `{grade_level}-{short_code}-{sequence}` (e.g. 11-STEM-1)

`assets/js/dashboard.js` `bindSectionNameSuggestion()` changed from
`{short}{year}R{seq}` ("IT4R1") to **`{year_level}-{short_code}-{sequence}`**
("11-STEM-1"), grade first, dashes, no "R". Sequence logic keeps the previous
principle: highest existing sequence for that grade+strand combo (max-seq,
NOT count). Field remains fully editable (manual edits are never clobbered).

Browser (Playwright) verification, creating sections via the UI under STEM
Grade 11:

- fresh combo auto-fills **`11-STEM-1`**
- second section auto-fills **`11-STEM-2`**
- third auto-fills `11-STEM-3`; deleting `11-STEM-2` then re-opening the modal
  auto-fills **`11-STEM-4`** (gap-safe: max-seq 3, not count 2)
- section name input is **not** readonly/disabled; a manual value survives a
  grade change; clearing it restores auto-fill
- fresh combo ABM Grade 12 → `12-ABM-1`
- no console errors

## 6. Label sweep — every file where Program/Course text became Strand

| File | Changes |
|------|---------|
| `application/controllers/Academic.php` | Flash messages & validation strings: "Strand created/updated/deleted/not found", "That strand code already exists", "short code already used by another strand", "Strand name/code must be…", "A valid strand is required", "no strand/year level set", "no curriculum defined for its strand/year/semester", section-name example "11-STEM-1", year-level 11-12 messages; 2 code comments about the display label |
| `application/views/admin/programs.php` | Page title "Manage Strands", subtitle, "Add Strand"/"Edit Strand"/"Create Strand"/"Save Changes", table header "Strand Name", modal labels "Strand Code"/"Strand Name", placeholders STEM + full strand name, short-code hint (SHS short code IS the strand name; used in "11-STEM-1"), delete confirm, empty-state |
| `application/views/admin/sections.php` | Table header "Strand / Grade", form label "Strand", "Select strand…", label "Grade Level", "Select grade…", Grade 11/Grade 12 options, per-row "Grade N", placeholder/hint "11-STEM-1", no-subjects note |
| `application/views/admin/curriculum.php` | Subtitle "…each strand teaches per grade level and semester", filter label "Strand", "Select strand…", "Grade Level" filter with Grade 11/Grade 12 options, slot heading "— Grade 11 — 2nd Semester", placeholder copy |
| `application/views/partials/sidebar.php` | "Manage Courses" → "Manage Strands" |
| `application/models/Academic_model.php` | 2 docblock comments ("displayed to admins as a 'Strand'") |
| `assets/js/dashboard.js` | Removed degree-prefix strip (`SHORT_CODE_PREFIXES`); short-code suggestion now = the code itself (short code IS the strand name); section auto-fill rewritten to the `{grade}-{short}-{seq}` pattern |

Route/table/column/method names were intentionally NOT renamed (labeling
change only, as approved).

## 7. Untouched tables confirmed

- `users`: 7 (1 admin + 6 teachers), 0 students — teachers keep logins but
  now have zero active assignments (known separate gap; read-only UI only,
  not in scope).
- `school_years` (4), `semesters` (8), `grading_periods` (12) — unchanged.

## 8. Syntax checks

- `php -l` clean on all touched PHP files: `Academic.php`,
  `Academic_model.php`, `views/admin/programs.php`, `views/admin/sections.php`,
  `views/admin/curriculum.php`, `views/partials/sidebar.php`,
  `database/delete_2026_college_data.php`
- `node --check` clean on `assets/js/dashboard.js`

## Test evidence

- HTTP/CSRF suite (`et_shs_pivot_test.php`): **61/61 pass** — Manage Strands
  labels, strand CRUD + duplicate short-code/code rejection, sections page
  labels + Grade 11/12 options, section create happy paths (11-STEM-1,
  11-STEM-2, 12-STEM-1), year_level rejection (5 and 0), curriculum page/filter
  behavior, curriculum store rejection, DB end-state (0 students/subjects/
  grades, roles, kept tables).
- Playwright browser suite (`et_shs_browser.py`): **21/21 pass** (item 5 above,
  plus strand short-code suggestion and editability).
- Smoke (`smoke_shs.php`): **14/14 pass** — admin pages + Strand labels,
  teacher login + teacher/reports pages 200.

## Also updated (consistency)

- `database/edutrack.sql` — the canonical schema+seed now matches the pivoted
  state: `users` = admin + 6 teachers only; `programs` = the 4 SHS strands;
  sections/subjects/students/grades/etc. seeded empty (admin defines them);
  school years/semesters/grading periods keep their generic seed. A fresh
  import is FK-clean and equivalent to the live pivoted DB.
- `database/seed_2026_shs_strands.sql` — new, idempotent strand seed (exact
  INSERT from the spec).
- `database/delete_2026_college_data.php` — new, live-schema-inspecting,
  FK-safe delete script (verified PASS: "no orphaned student accounts").
- Curriculum is intentionally left empty for all 4 strands (admin adds
  subjects; no starter SHS curriculum was guessed).

## Known limitations (unchanged from scope)

- No UI yet to create teacher subject assignments (teachers have zero
  assignments until reassigned; read-only display only).
- `short_code` is admin-authoritative; the client pre-fill is only a
  convenience and never locks the field.
