<?php
/**
 * EduTrack — Feature: Programs, Curriculum & Auto-Populated Section Subjects
 * Backfill (Part 2). One-time CLI script.
 *
 * Derives data from what is already in the database — it does not hard-code
 * any business facts beyond generic parsing rules and obvious program names.
 *
 *   php database/backfill_2026_programs_curriculum.php
 *
 * Exit code 0 = success, 1 = one or more steps reported issues.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edutrack');

// Reasonable expansions for obvious prefixes. Unknown prefixes get the code
// itself as the name and are flagged for the project owner to rename.
$PROGRAM_NAMES = array(
	'BSIT' => 'BS Information Technology',
	'BSCS' => 'BS Computer Science',
	'BSECE' => 'BS Electronics Engineering',
);

$report = array();
$warnings = array();

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

function q(string $sql): mysqli_result
{
	return db()->query($sql);
}

/**
 * Parse a section name like "BSIT-1A" into (prefix, year_level) or NULL.
 */
function parse_section_name(string $name): ?array
{
	if (preg_match('/^([A-Z]{2,6})-([1-9])([A-Z])$/', trim($name), $m))
	{
		return array('prefix' => $m[1], 'year' => (int) $m[2]);
	}
	return NULL;
}

// =====================================================================
// 1. Populate `programs` from distinct prefixes in sections.section_name
// =====================================================================
$sections = q("SELECT id, name FROM sections ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$parsed = array();
$ambiguous = array();
foreach ($sections as $sec)
{
	$p = parse_section_name($sec['name']);
	if ($p === NULL)
	{
		$ambiguous[] = $sec['name'];
		continue;
	}
	$parsed[$sec['id']] = array('prefix' => $p['prefix'], 'year' => $p['year']);
}

$prefixes = array();
foreach ($parsed as $info)
{
	$prefixes[$info['prefix']] = TRUE;
}

$program_id_by_prefix = array();
foreach (array_keys($prefixes) as $prefix)
{
	$name = isset($PROGRAM_NAMES[$prefix]) ? $PROGRAM_NAMES[$prefix] : $prefix;
	$stmt = db()->prepare("INSERT INTO programs (program_code, program_name) VALUES (?, ?)");
	$stmt->bind_param('ss', $prefix, $name);
	$stmt->execute();
	$program_id_by_prefix[$prefix] = (int) db()->insert_id;
	if ( ! isset($PROGRAM_NAMES[$prefix]))
	{
		$warnings[] = "Program \"$prefix\" has no known expansion — created with program_name = \"$prefix\". Rename it via the project owner.";
	}
}
$report['programs_created'] = array_keys($program_id_by_prefix);

// =====================================================================
// 2. Backfill sections.program_id + sections.year_level
// =====================================================================
$upd = db()->prepare("UPDATE sections SET program_id = ?, year_level = ? WHERE id = ?");
$updated_sections = 0;
foreach ($parsed as $sec_id => $info)
{
	$pid = $program_id_by_prefix[$info['prefix']];
	$upd->bind_param('iii', $pid, $info['year'], $sec_id);
	$upd->execute();
	$updated_sections++;
}
$report['sections_backfilled'] = $updated_sections;
$report['sections_ambiguous'] = $ambiguous;

// =====================================================================
// 3. Backfill semesters.semester_number from name ("1st" -> 1, "2nd" -> 2)
// =====================================================================
$semesters = q("SELECT id, name FROM semesters ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$sem_upd = db()->prepare("UPDATE semesters SET semester_number = ? WHERE id = ?");
$summer_ids = array();
$numbered = 0;
foreach ($semesters as $sem)
{
	$num = NULL;
	if (strpos($sem['name'], '1st') !== FALSE)
	{
		$num = 1;
	}
	elseif (strpos($sem['name'], '2nd') !== FALSE)
	{
		$num = 2;
	}
	if ($num !== NULL)
	{
		$sem_upd->bind_param('ii', $num, $sem['id']);
		$sem_upd->execute();
		$numbered++;
	}
	else
	{
		// e.g. "Summer" — ambiguous, left NULL and flagged.
		$summer_ids[] = (int) $sem['id'];
	}
}
$report['semesters_numbered'] = $numbered;
$report['semesters_ambiguous_ids'] = $summer_ids;
$report['semesters_ambiguous_count'] = count($summer_ids);

// =====================================================================
// 4. Starter curriculum from existing assignments/grades (active term)
//    curriculum_subjects (program_id, year_level, semester_number, subject_id)
// =====================================================================
// Active semester number drives the curriculum slot we seed.
$active_sem = q("SELECT id, semester_number FROM semesters WHERE is_active = 1 LIMIT 1")->fetch_assoc();
if ( ! $active_sem)
{
	fwrite(STDERR, "FATAL: no active semester found; cannot derive starter curriculum.\n");
	exit(1);
}
$active_sem_number = (int) $active_sem['semester_number'];

// Subjects currently tied to each section via assignments OR grades.
$rows = q(
	"SELECT DISTINCT sec.program_id, sec.year_level, tsa.subject_id
	   FROM teacher_subject_assignments tsa
	   JOIN sections sec ON sec.id = tsa.section_id
	  WHERE tsa.school_year_id = (SELECT id FROM school_years WHERE is_active = 1 LIMIT 1)
	    AND tsa.semester_id = " . (int) $active_sem['id']
)->fetch_all(MYSQLI_ASSOC);

$curriculum_ins = db()->prepare(
	"INSERT IGNORE INTO curriculum_subjects (program_id, year_level, semester_number, subject_id)
	 VALUES (?, ?, ?, ?)"
);
$seeded = 0;
foreach ($rows as $r)
{
	if ($r['program_id'] === NULL || $r['year_level'] === NULL)
	{
		continue;
	}
	$curriculum_ins->bind_param('iiii', $r['program_id'], $r['year_level'], $active_sem_number, $r['subject_id']);
	$curriculum_ins->execute();
	$seeded += db()->affected_rows;
}
$report['curriculum_seeded_rows'] = $seeded;
$report['curriculum_seeded_for_semester_number'] = $active_sem_number;
$report['curriculum_note'] = 'STARTER SEED derived from existing teacher_subject_assignments in the active term — needs admin review via the Manage Curriculum page.';

// =====================================================================
// 5. Backfill section_subjects for existing sections (active semester)
//    Reuses the same logic as the Sync button (idempotent).
// =====================================================================
$active_sem_id = (int) $active_sem['id'];
$sec_rows = q("SELECT id, program_id, year_level FROM sections ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$secsub_ins = db()->prepare(
	"INSERT IGNORE INTO section_subjects (section_id, subject_id, semester_id)
	 SELECT ?, subject_id, ?
	   FROM curriculum_subjects
	  WHERE program_id = ? AND year_level = ? AND semester_number = ?"
);
$synced_sections = 0;
$synced_rows = 0;
foreach ($sec_rows as $s)
{
	if ($s['program_id'] === NULL || $s['year_level'] === NULL)
	{
		continue;
	}
	$secsub_ins->bind_param('iiiii', $s['id'], $active_sem_id, $s['program_id'], $s['year_level'], $active_sem_number);
	$secsub_ins->execute();
	$added = db()->affected_rows;
	$synced_rows += $added;
	$synced_sections++;
}
$report['section_subjects_sections_synced'] = $synced_sections;
$report['section_subjects_rows_inserted'] = $synced_rows;

// =====================================================================
// Output
// =====================================================================
echo "BACKFILL REPORT\n";
echo "===============\n";
foreach ($report as $k => $v)
{
	echo str_pad($k, 42, ' ') . ' : ' . json_encode($v) . "\n";
}
if ( ! empty($warnings))
{
	echo "\nWARNINGS\n--------\n";
	foreach ($warnings as $w)
	{
		echo " - $w\n";
	}
}
exit(0);