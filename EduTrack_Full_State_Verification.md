# FULL PROJECT & DATABASE STATE DUMP — for independent verification

I want to verify the actual current state myself instead of relying on
summaries. Please output the following, completely and literally (not
paraphrased, not summarized) so I can check it directly.

---

## PART 1 — FILE INVENTORY

Run and paste the raw output of:

```bash
find application/controllers application/models application/config -type f -name "*.php" | sort
```

Then for EACH file listed, paste:
```bash
wc -l <filename>
```

(Just the file list + line counts — I don't need full content of every
file yet, just the inventory so I know what exists.)

---

## PART 2 — FULL CONTENT of these specific files (paste completely, no truncation)

- `application/config/config.php` — specifically confirm the current
  values of: `cookie_httponly`, `csrf_protection`, `encryption_key`
  (do NOT paste the actual encryption key value — just confirm it is
  set / not empty)
- `application/config/database.php` — confirm connection settings
  (do NOT paste the actual DB password — just confirm `password` is
  not blank)
- `application/config/routes.php` — full content

---

## PART 3 — LIVE DATABASE STATE (query the actual running database, not the seed file)

For each table, run and paste the output of:

```sql
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM students;
SELECT COUNT(*) FROM sections;
SELECT COUNT(*) FROM subjects;
SELECT COUNT(*) FROM school_years;
SELECT COUNT(*) FROM semesters;
SELECT COUNT(*) FROM grading_periods;
SELECT COUNT(*) FROM teacher_subject_assignments;
SELECT COUNT(*) FROM grades;
SELECT COUNT(*) FROM grade_logs;
SELECT COUNT(*) FROM login_attempts;
SELECT COUNT(*) FROM password_resets;
SELECT COUNT(*) FROM notifications;
```

Then paste the FULL result (all rows) of these — this is the part I
most want to see directly:

```sql
SELECT id, username, email, role, status, must_change_password FROM users ORDER BY id;

SELECT * FROM grade_logs ORDER BY id;

SELECT g.id, s.full_name AS student, sub.subject_code, gp.period_name,
       g.grade_value, g.date_recorded
FROM grades g
JOIN students s ON s.id = g.student_id
JOIN subjects sub ON sub.id = g.subject_id
JOIN grading_periods gp ON gp.id = g.grading_period_id
ORDER BY g.id;

SELECT * FROM teacher_subject_assignments;
```

(Do NOT paste the `password`/`password_hash` column values from
`users` — everything else in that table is fine to show.)

---

## PART 4 — SCHEMA CONSISTENCY CHECK

For each table, run and paste:
```sql
SHOW CREATE TABLE users;
SHOW CREATE TABLE grades;
SHOW CREATE TABLE grade_logs;
SHOW CREATE TABLE grading_periods;
```

I want to confirm the LIVE database schema actually matches what's in
`database/edutrack.sql` — sometimes a database drifts from its seed
file after manual fixes. If there's ANY difference between what
`SHOW CREATE TABLE` returns and what's in the `.sql` file, point it out
explicitly rather than letting me find it myself.

---

## PART 5 — FILE ENCODING CHECK (re-confirm the earlier BOM fix)

```bash
file database/edutrack.sql
```

Paste the exact output. It must say `UTF-8 text` — if it says
`UTF-8 (with BOM) text`, the earlier fix did not stick and needs to be
redone.

---

## WHY I'M ASKING FOR ALL OF THIS

Previous reports summarized things as "done" or "PASS" that turned out
to still have unresolved issues underneath (e.g. the temp password fix
that was flagged as a decision three separate times without being
implemented). Raw output — actual file content, actual query results —
is easier to verify honestly than a written summary, and it's faster
for both of us than going back and forth when something doesn't match.

Please don't summarize or interpret the query results — just paste
what the database actually returns.
