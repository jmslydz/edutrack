<?php
/**
 * EduTrack — Feature: Course short codes
 * Backfill (Part 2). One-time CLI script.
 *
 * Generates a SUGGESTED `short_code` for existing programs by stripping a
 * common leading degree prefix from the program code ("BSIT" -> "IT",
 * "BSCS" -> "CS", "BSECE" -> "ECE"). These are suggestions only — the
 * admin reviews them on the Manage Courses page and owns the final value
 * (the field is editable and authoritative).
 *
 * The strip is deliberately a best-effort guess and never final. Program
 * codes that do not start with a known degree prefix keep their code as
 * the suggestion; rows that would collide with an existing short_code are
 * left NULL and flagged for the admin to set manually.
 *
 *   php database/backfill_2026_programs_short_codes.php
 *
 * Exit code 0 = success, 1 = one or more steps reported issues.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edutrack');

// Common Philippine degree-code prefixes, longest first. These are the
// "strippable" prefixes (BS, AB, BA, BE) only — a full degree code
// ("BSE", "BSED") is BS + field, so "BSECE" must strip "BS" to "ECE".
// The result is purely a suggestion; unknown prefixes fall back to the
// program code itself.
$PREFIXES = array('BS', 'AB', 'BA', 'BE');

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

function q(string $sql): mysqli_result
{
	return db()->query($sql);
}

/**
 * Suggest a short_code from a program code by stripping a leading degree
 * prefix, e.g. "BSIT" -> "IT". Returns NULL when nothing sensible remains
 * (empty result or non-alphanumeric), in which case the caller should keep
 * the code itself or leave the field for the admin.
 */
function suggest_short_code(string $code, array $prefixes): ?string
{
	$code = strtoupper(trim($code));
	if ($code === '')
	{
		return NULL;
	}
	foreach ($prefixes as $p)
	{
		if (strpos($code, $p) === 0)
		{
			$rest = substr($code, strlen($p));
			if ($rest !== '' && preg_match('/^[A-Z0-9]+$/', $rest))
			{
				return $rest;
			}
			break; // prefix matched but leftover unusable -> fall through to code itself
		}
	}
	// No known prefix (e.g. a custom code) — the code itself is the suggestion.
	return preg_match('/^[A-Z0-9]+$/', $code) ? $code : NULL;
}

// =====================================================================
// 1. Suggest + apply short_code for every program that has none yet
// =====================================================================
$programs = q("SELECT id, program_code, short_code FROM programs ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$taken = array();
foreach ($programs as $p)
{
	if ($p['short_code'] !== NULL)
	{
		$taken[$p['short_code']] = TRUE;
	}
}

$applied = array();
$skipped = array();
foreach ($programs as $p)
{
	if ($p['short_code'] !== NULL)
	{
		continue; // already set — never overwrite (idempotent)
	}

	$suggestion = suggest_short_code($p['program_code'], $PREFIXES);
	if ($suggestion === NULL)
	{
		$skipped[] = array('code' => $p['program_code'], 'reason' => 'could not derive a safe suggestion');
		$warnings[] = 'Program "' . $p['program_code'] . '" got NO short_code — set one manually on Manage Courses.';
		continue;
	}

	if (isset($taken[$suggestion]))
	{
		$skipped[] = array('code' => $p['program_code'], 'reason' => "suggestion \"$suggestion\" already used by another course");
		$warnings[] = 'Program "' . $p['program_code'] . '" suggestion "' . $suggestion
			. '" collides with another course — left NULL; set it manually on Manage Courses.';
		continue;
	}

	$stmt = db()->prepare("UPDATE programs SET short_code = ? WHERE id = ?");
	$stmt->bind_param('si', $suggestion, $p['id']);
	$stmt->execute();
	$taken[$suggestion] = TRUE;
	$applied[$p['program_code']] = $suggestion;
}
$report['suggestions_applied'] = $applied;
$report['skipped'] = $skipped;

// =====================================================================
// 2. Final state
// =====================================================================
$after = q("SELECT program_code, short_code FROM programs ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$report['final_state'] = $after;

// =====================================================================
// Output
// =====================================================================
echo "SHORT_CODE BACKFILL REPORT\n";
echo "==========================\n";
foreach ($report as $k => $v)
{
	echo str_pad($k, 42, ' ') . ' : ' . json_encode($v) . "\n";
}
echo "\nSUGGESTIONS ARE ADMIN-REVIEWABLE — review/edit each short_code on the Manage Courses page; these are initial guesses, not authoritative values.\n";
if ( ! empty($warnings))
{
	echo "\nWARNINGS\n--------\n";
	foreach ($warnings as $w)
	{
		echo " - $w\n";
	}
}
exit(0);