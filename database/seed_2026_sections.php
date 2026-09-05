<?php
/**
 * EduTrack — automatic SHS section seeder.
 * One-time CLI script (idempotent — safe to re-run).
 *
 * Creates a full batch of class sections for the Senior High School
 * strands: SECTIONS_PER_STRAND sections per strand, named in the app's
 * standard "{grade}-{strand}-{n}" format (e.g. 11-STEM-1 … 11-STEM-5,
 * matching the "Add Section" auto-suggestion) at DEFAULT_YEAR_LEVEL
 * (Grade 11). Sections are created ACTIVE by default so they are
 * immediately available for enrollment.
 *
 * For each strand section it also syncs the predefined curriculum of the
 * ACTIVE semester into `section_subjects` (same logic as pressing
 * "Sync Subjects from Curriculum" on the Manage Sections page), so the
 * sections come out ready-to-use.
 *
 *   php database/seed_2026_sections.php
 *
 * Exit code 0 = success, 1 = one or more steps reported issues.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edutrack');

// --- Tuning (edit these for a different deployment) --------------------
define('DEFAULT_YEAR_LEVEL', 11);               // grade level of every generated section
define('SECTIONS_PER_STRAND', 5);               // e.g. 5 -> 11-STEM-1 .. 11-STEM-5
define('DEFAULT_IS_ACTIVE', 1);                 // sections are usable immediately
define('STRANDS', array('STEM', 'ABM', 'HUMSS', 'GAS')); // sections are generated per strand
// -----------------------------------------------------------------------

$report   = array();
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

// =====================================================================
// 1. Active semester (needed for the subject sync step)
// =====================================================================
$active_sem = db()->query(
	"SELECT id, semester_number FROM semesters WHERE is_active = 1 LIMIT 1"
)->fetch_assoc();

if ( ! $active_sem)
{
	$warnings[] = 'No active semester. Sections will be created, but their subjects cannot be synced until you activate a semester.';
}
elseif ($active_sem['semester_number'] === NULL)
{
	$warnings[] = 'Active semester "' . $active_sem['id'] . '" has no semester_number. Subject sync needs a numbered (1st/2nd) semester.';
	$active_sem = NULL;
}

// Existing sections by name, so the seed never duplicates or mis-files them.
$existing = array();
foreach (db()->query("SELECT id, name, program_id, year_level FROM sections")->fetch_all(MYSQLI_ASSOC) as $r)
{
	$existing[$r['name']] = $r;
}

// =====================================================================
// 2. Generate sections per strand
// =====================================================================
$per_strand   = array();
$created      = array(); // rows ['name','program_id'] actually inserted
$synced_count = 0;
$sync_stmt    = NULL;

foreach (STRANDS as $code)
{
	$pid = db()->query("SELECT id FROM programs WHERE program_code = '$code'")->fetch_row();
	if ( ! $pid)
	{
		$warnings[] = "Strand \"$code\" is not in `programs` — skipping it. Add the program (Manage Courses) then re-run.";
		$per_strand[$code] = array('created' => 0, 'existing' => 0, 'skipped' => 0);
		continue;
	}
	$pid = (int) $pid[0];

	$insert = db()->prepare(
		'INSERT IGNORE INTO sections (name, program_id, year_level, is_active) VALUES (?, ?, ?, ?)'
	);
	if ( ! $sync_stmt && $active_sem)
	{
		// One prepared statement reused for every freshly created section.
		$sync_stmt = db()->prepare(
			'INSERT IGNORE INTO section_subjects (section_id, subject_id, semester_id)
			 SELECT ?, subject_id, ?
			   FROM curriculum_subjects
			  WHERE program_id = ? AND year_level = ? AND semester_number = ?'
		);
	}

	$t = array('created' => 0, 'existing' => 0, 'skipped' => 0);
	for ($i = 1; $i <= SECTIONS_PER_STRAND; $i++)
	{
		$name = DEFAULT_YEAR_LEVEL . '-' . $code . '-' . $i;

		// Flag sections created by an older version of this seeder that
		// lacked the grade prefix (e.g. "STEM-1" instead of "11-STEM-1").
		$legacy = $code . '-' . $i;
		if (isset($existing[$legacy]) && (int) $existing[$legacy]['program_id'] === $pid)
		{
			$warnings[] = "Found legacy-named section \"$legacy\" (no grade prefix). " .
				"Rename it to \"$name\" on Manage Sections to match the app convention.";
		}

		if (isset($existing[$name]))
		{
			$row = $existing[$name];
			if ((int) $row['program_id'] === $pid && (int) $row['year_level'] === (int) DEFAULT_YEAR_LEVEL)
			{
				$t['existing']++; // same strand + grade, nothing to do
			}
			else
			{
				$t['skipped']++;
				$warnings[] = "Section \"$name\" already exists with a different strand/grade (program_id " .
					(int) $row['program_id'] . ", grade " . (int) $row['year_level'] . ") — left untouched.";
			}
			continue;
		}

		$year = (int) DEFAULT_YEAR_LEVEL;
		$active_flag = (int) DEFAULT_IS_ACTIVE;
		$insert->bind_param('siii', $name, $pid, $year, $active_flag);
		$insert->execute();

		if (db()->affected_rows === 0)
		{
			$t['skipped']++;
			continue; // a concurrent/missing unique row slipped in
		}
		$t['created']++;
		$created[] = array('name' => $name, 'program_id' => $pid);

		if ($sync_stmt && $active_sem)
		{
			$new_id = (int) db()->insert_id;
			$sync_stmt->bind_param('iiiii', $new_id, $active_sem['id'], $pid, $year, $active_sem['semester_number']);
			$sync_stmt->execute();
			$synced_count += db()->affected_rows;
		}
	}
	$per_strand[$code] = $t;
}

$report['sections_per_strand'] = $per_strand;

// =====================================================================
// 3. Totals
// =====================================================================
$report['sections_total'] = (int) db()->query('SELECT COUNT(*) FROM sections')->fetch_row()[0];
$report['sections_created'] = count($created);
$report['subjects_synced'] = $synced_count;
$report['sections'] = array_column($created, 'name');

// Sections "ensured" (created this run or already present correctly).
$ensured = 0;
foreach ($per_strand as $t)
{
	$ensured += (int) $t['created'] + (int) $t['existing'];
}

// =====================================================================
// Output
// =====================================================================
echo "AUTOMATIC SECTIONS SEED REPORT\n";
echo "==============================\n";
foreach ($report as $k => $v)
{
	echo str_pad($k, 22, ' ') . ' : ' . json_encode($v) . "\n";
}
if ( ! empty($warnings))
{
	echo "\nWARNINGS\n--------\n";
	foreach ($warnings as $w)
	{
		echo " - $w\n";
	}
}

$fail = ! empty($warnings) || $ensured === 0;
echo $fail ? "\nRESULT: FAIL\n" : "\nRESULT: PASS — " . $ensured .
	" auto-generated section(s) are in place (" . count($created) . " created this run) and ready for enrollment.\n";
exit($fail ? 1 : 0);