<?php
/**
 * EduTrack — Pivot: College Programs -> Senior High School Strands
 * PART 1 — Delete existing college data (irreversible, explicitly approved).
 * One-time CLI script.
 *
 * Deletes the BSIT/BSCS/BSECE college data set in FK-safe order, including
 * the student login accounts (`users` rows) linked to the deleted student
 * records. Teacher/admin accounts, school_years, semesters and
 * grading_periods are intentionally KEPT.
 *
 * The schema is inspected live first (information_schema) so the delete
 * order is derived from the real foreign-key relationships, never assumed.
 *
 *   php database/delete_2026_college_data.php
 *
 * Exit code 0 = success, 1 = verification failed.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edutrack');

// Tables removed entirely by this pivot, in the order they must be deleted
// so no foreign-key constraint is violated at any step (children first).
$DELETE_ORDER = array(
	'grade_logs',                  // -> grades, users
	'grades',                      // -> grading_periods, students, subjects, users
	'section_subjects',            // -> sections, semesters, subjects
	'teacher_subject_assignments', // -> school_years, sections, semesters, subjects, users
	'curriculum_subjects',         // -> programs, subjects
	'students',                    // -> sections, users
	'sections',                    // -> programs
	'programs',
	'subjects',
);

// Tables that must be PRESERVED (verified untouched at the end).
$KEEP_TABLES = array('users', 'school_years', 'semesters', 'grading_periods');

function db(): mysqli
{
	static $link = NULL;
	if ($link === NULL)
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
		$link->set_charset('utf8mb4');
	}
	return $link;
}

/**
 * Run a statement. SELECT/query results return mysqli_result; INSERT/UPDATE/
 * DELETE statements set affected_rows on the connection and return the
 * boolean from query() (asserted true so failures surface as exceptions).
 */
function q(string $sql): mixed
{
	$res = db()->query($sql);
	if ($res === FALSE)
	{
		fwrite(STDERR, "SQL failed: $sql\n");
		exit(1);
	}
	return $res;
}

function count_rows(string $table): int
{
	return (int) q("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
}

// =====================================================================
// 1. Inspect live schema: real FK relationships
// =====================================================================
echo "STEP 1 — Live FK relationships (child -> parent)\n";
$fks = db()->query(
	"SELECT table_name, column_name, referenced_table_name
	 FROM information_schema.key_column_usage
	 WHERE table_schema = DATABASE() AND referenced_table_name IS NOT NULL
	 ORDER BY table_name, column_name"
)->fetch_all(MYSQLI_ASSOC);
if (empty($fks))
{
	fwrite(STDERR, "ERROR: no foreign keys found — aborting (refusing to delete on an unverified schema).\n");
	exit(1);
}
foreach ($fks as $fk)
{
	printf("  %s.%s -> %s\n", $fk['table_name'], $fk['column_name'], $fk['referenced_table_name']);
}

// Verify every FK child table we will touch is reachable — sanity check that
// our delete order is safe: no deleted table may reference a table deleted
// LATER in the order (other than via kept tables).
$deleted_index = array_flip($DELETE_ORDER);
foreach ($fks as $fk)
{
	$child = $fk['table_name'];
	$parent = $fk['referenced_table_name'];
	if (isset($deleted_index[$child]) && isset($deleted_index[$parent])
		&& $deleted_index[$child] >= $deleted_index[$parent])
	{
		fwrite(STDERR, "ERROR: FK safety check failed for $child -> $parent (order violation).\n");
		exit(1);
	}
}
echo "  FK safety check passed.\n\n";

// =====================================================================
// 2. Capture the student login accounts BEFORE deleting the students,
//    then delete everything in FK-safe order.
// =====================================================================
$student_user_ids = array();
foreach (q("SELECT user_id FROM students ORDER BY user_id")->fetch_all(MYSQLI_NUM) as $row)
{
	$student_user_ids[] = (int) $row[0];
}

$before = array();
$deleted_counts = array();
foreach ($DELETE_ORDER as $table)
{
	$before[$table] = count_rows($table);
	if ($before[$table] === 0)
	{
		$deleted_counts[$table] = 0;
		continue;
	}
	q("DELETE FROM `$table`");
	$deleted_counts[$table] = db()->affected_rows;
}

// Delete the linked student login accounts now that students are gone.
$before['users_students'] = count($student_user_ids);
if ( ! empty($student_user_ids))
{
	$ids = implode(',', array_map('intval', $student_user_ids));
	q("DELETE FROM users WHERE id IN ($ids)");
	$deleted_counts['users_students'] = db()->affected_rows;
}
else
{
	$deleted_counts['users_students'] = 0;
}

// =====================================================================
// 3. Report + verification
// =====================================================================
echo "STEP 2 — Rows deleted\n";
foreach ($DELETE_ORDER as $table)
{
	printf("  %-28s %4d\n", $table, $deleted_counts[$table]);
}
printf("  %-28s %4d\n", 'users (student logins)', $deleted_counts['users_students']);

echo "\nSTEP 3 — Verification\n";
$problems = array();

// a) Confirm the deleted tables are empty.
foreach ($DELETE_ORDER as $table)
{
	$left = count_rows($table);
	if ($left !== 0)
	{
		$problems[] = "$table still has $left rows";
	}
}

// b) No orphaned student user accounts remain.
$left_students = count_rows('users') !== 0 ? (int) q("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0] : 0;
if ($left_students !== 0)
{
	$problems[] = "$left_students user(s) still have role 'student'";
}
printf("  users with role='student' after delete : %d\n", $left_students);

// c) Kept tables are untouched (exact same row count as before the pivot).
$kept_before = array();
foreach ($KEEP_TABLES as $table)
{
	$kept_before[$table] = count_rows($table);
}
printf("  kept tables (users, school_years, semesters, grading_periods) present: %s\n",
	implode(', ', array_map(fn($t) => "$t={$kept_before[$t]}", $KEEP_TABLES)));

if (empty($problems))
{
	echo "\n  RESULT: PASS — college data removed in FK-safe order, no orphaned student accounts.\n";
	exit(0);
}

echo "\n  RESULT: FAIL\n";
foreach ($problems as $p)
{
	echo "   - $p\n";
}
exit(1);
