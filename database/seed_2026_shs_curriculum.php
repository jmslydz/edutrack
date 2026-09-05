<?php
/**
 * EduTrack — Senior High School predefined curriculum seed.
 * One-time CLI script (idempotent — safe to re-run).
 *
 * Populates `subjects` and `curriculum_subjects` with the standard DepEd SHS
 * curriculum: shared Core + Applied-Track subjects and strand-specific
 * specialized subjects, laid out per Strand (STEM/ABM/HUMSS/GAS) x Grade
 * (11/12) x Semester (1st/2nd). Units per the standard (3.0 academic, 2.0 PE).
 *
 * This is the authoritative "predefined curriculum". Sections auto-inherit it
 * via Academic_model::sync_section_subjects() when they are created or synced,
 * so the administrator does not add subjects per section. The Manage
 * Curriculum page was removed — adjust the predefined curriculum here
 * (re-run this script, or edit the tables) if the school's offerings differ.
 *
 *   php database/seed_2026_shs_curriculum.php
 *
 * Exit code 0 = success, 1 = one or more steps reported issues.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edutrack');

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

// subject code => [title, units]
$SUBJECTS = array(
	// ---- Core (shared by every strand) ----
	'ORALCOM'  => array('Oral Communication in Context', 3.0),
	'KOMFIL'   => array('Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 3.0),
	'GENMATH'  => array('General Mathematics', 3.0),
	'ELS'      => array('Earth and Life Science', 3.0),
	'PERDEV'   => array('Personal Development', 3.0),
	'UCSP'     => array('Understanding Culture, Society and Politics', 3.0),
	'PEH1'     => array('Physical Education and Health 1', 2.0),
	'READWRIT' => array('Reading and Writing', 3.0),
	'LIT21'    => array('21st Century Literature from the Philippines and the World', 3.0),
	'STATPROB' => array('Statistics and Probability', 3.0),
	'PHYSCI'   => array('Physical Science', 3.0),
	'PHILO'    => array('Introduction to the Philosophy of the Human Person', 3.0),
	'MIL'      => array('Media and Information Literacy', 3.0),
	'PEH2'     => array('Physical Education and Health 2', 2.0),
	'CPA'      => array('Contemporary Philippine Arts from the Regions', 3.0),
	'PEH3'     => array('Physical Education and Health 3', 2.0),
	'PEH4'     => array('Physical Education and Health 4', 2.0),

	// ---- Applied track (shared by every strand) ----
	'EAPP'     => array('English for Academic and Professional Purposes', 3.0),
	'EMPTECH'  => array('Empowerment Technologies', 3.0),
	'PRACRES1' => array('Practical Research 1', 3.0),
	'PRACRES2' => array('Practical Research 2', 3.0),
	'PWKD'     => array('Pagsulat sa Filipino sa Piling Larangan', 3.0),
	'INQUIRY'  => array('Inquiries, Investigation and Immersion', 3.0),

	// ---- STEM specialized ----
	'STEM-PRECAL' => array('Pre-Calculus', 3.0),
	'STEM-CHEM1'  => array('General Chemistry 1', 3.0),
	'STEM-BIO1'   => array('General Biology 1', 3.0),
	'STEM-CALC'   => array('Basic Calculus', 3.0),
	'STEM-PHY1'   => array('General Physics 1', 3.0),
	'STEM-BIO2'   => array('General Biology 2', 3.0),
	'STEM-CHEM2'  => array('General Chemistry 2', 3.0),
	'STEM-PHY2'   => array('General Physics 2', 3.0),
	'STEM-RES'    => array('Research/Capstone Project', 3.0),

	// ---- ABM specialized ----
	'BUSMATH' => array('Business Mathematics', 3.0),
	'FABM1'   => array('Fundamentals of Accountancy, Business and Management 1', 3.0),
	'ORGMAN'  => array('Organization and Management', 3.0),
	'FABM2'   => array('Fundamentals of Accountancy, Business and Management 2', 3.0),
	'MARKTNG' => array('Principles of Marketing', 3.0),
	'APECON'  => array('Applied Economics', 3.0),
	'BIZFIN'  => array('Business Finance', 3.0),
	'BIZETH'  => array('Business Ethics and Social Responsibility', 3.0),
	'BIZSIM'  => array('Business Enterprise Simulation', 3.0),

	// ---- HUMSS specialized ----
	'PHILPOL' => array('Philippine Politics and Governance', 3.0),
	'INTWORL' => array('Introduction to World Religions and Belief Systems', 3.0),
	'TRENDS'  => array('Trends, Networks, and Critical Thinking in the 21st Century', 3.0),
	'COMMENG' => array('Community Engagement, Solidarity, and Citizenship', 3.0),
	'DISS'    => array('Disciplines and Ideas in the Social Sciences', 3.0),
	'DIASS'   => array('Disciplines and Ideas in the Applied Social Sciences', 3.0),
	'CRENON'  => array('Creative Nonfiction', 3.0),
	'HUMSRES' => array('Humanities and Social Sciences Research/Capstone', 3.0),
	'CREWRI'  => array('Creative Writing', 3.0),

	// ---- GAS specialized (GAS reuses several codes listed above) ----
	'GASRES'  => array('Academic Research Project', 3.0),
);

// subject code => [grade => [semesters]] (placement shared by every strand
// that includes the subject).
$PLACEMENT = array(
	'ORALCOM'  => array(11 => array(1)),  'KOMFIL'   => array(11 => array(1)),
	'GENMATH'  => array(11 => array(1)),  'ELS'      => array(11 => array(1)),
	'PERDEV'   => array(11 => array(1)),  'UCSP'     => array(11 => array(1)),
	'PEH1'     => array(11 => array(1)),
	'READWRIT' => array(11 => array(2)),  'LIT21'    => array(11 => array(2)),
	'STATPROB' => array(11 => array(2)),  'PHYSCI'   => array(11 => array(2)),
	'PHILO'    => array(11 => array(2)),  'MIL'      => array(11 => array(2)),
	'PEH2'     => array(11 => array(2)),
	'CPA'      => array(12 => array(1)),  'PEH3'     => array(12 => array(1)),
	'PEH4'     => array(12 => array(2)),
	'EAPP'     => array(11 => array(1)),
	'EMPTECH'  => array(11 => array(2)),  'PRACRES1' => array(11 => array(2)),
	'PRACRES2' => array(12 => array(1)),  'PWKD'     => array(12 => array(1)),
	'INQUIRY'  => array(12 => array(2)),
	'STEM-PRECAL' => array(11 => array(1)), 'STEM-CHEM1' => array(11 => array(1)),
	'STEM-BIO1'   => array(11 => array(1)), 'STEM-CALC'  => array(11 => array(2)),
	'STEM-PHY1'   => array(11 => array(2)), 'STEM-BIO2'  => array(11 => array(2)),
	'STEM-CHEM2'  => array(12 => array(1)), 'STEM-PHY2'  => array(12 => array(1)),
	'STEM-RES'    => array(12 => array(2)),
	'BUSMATH' => array(11 => array(1)), 'FABM1'  => array(11 => array(1)),
	'ORGMAN'  => array(11 => array(1)), 'FABM2'  => array(11 => array(2)),
	'MARKTNG' => array(11 => array(2)), 'APECON' => array(11 => array(2)),
	'BIZFIN'  => array(12 => array(1)), 'BIZETH' => array(12 => array(1)),
	'BIZSIM'  => array(12 => array(2)),
	'PHILPOL' => array(11 => array(1)), 'INTWORL' => array(11 => array(1)),
	'TRENDS'  => array(11 => array(2)), 'COMMENG' => array(11 => array(2)),
	'DISS'    => array(12 => array(1)), 'DIASS'   => array(12 => array(1)),
	'CRENON'  => array(12 => array(1)), 'HUMSRES' => array(12 => array(2)),
	'CREWRI'  => array(12 => array(2)),
	'GASRES'  => array(12 => array(2)),
);

// strand code => list of subject codes it includes
$STRANDS = array(
	'STEM' => array(
		'ORALCOM', 'KOMFIL', 'GENMATH', 'ELS', 'PERDEV', 'UCSP', 'PEH1',
		'READWRIT', 'LIT21', 'STATPROB', 'PHYSCI', 'PHILO', 'MIL', 'PEH2',
		'CPA', 'PEH3', 'PEH4',
		'EAPP', 'EMPTECH', 'PRACRES1', 'PRACRES2', 'PWKD', 'INQUIRY',
		'STEM-PRECAL', 'STEM-CHEM1', 'STEM-BIO1', 'STEM-CALC', 'STEM-PHY1',
		'STEM-BIO2', 'STEM-CHEM2', 'STEM-PHY2', 'STEM-RES',
	),
	'ABM' => array(
		'ORALCOM', 'KOMFIL', 'GENMATH', 'ELS', 'PERDEV', 'UCSP', 'PEH1',
		'READWRIT', 'LIT21', 'STATPROB', 'PHYSCI', 'PHILO', 'MIL', 'PEH2',
		'CPA', 'PEH3', 'PEH4',
		'EAPP', 'EMPTECH', 'PRACRES1', 'PRACRES2', 'PWKD', 'INQUIRY',
		'BUSMATH', 'FABM1', 'ORGMAN', 'FABM2', 'MARKTNG', 'APECON',
		'BIZFIN', 'BIZETH', 'BIZSIM',
	),
	'HUMSS' => array(
		'ORALCOM', 'KOMFIL', 'GENMATH', 'ELS', 'PERDEV', 'UCSP', 'PEH1',
		'READWRIT', 'LIT21', 'STATPROB', 'PHYSCI', 'PHILO', 'MIL', 'PEH2',
		'CPA', 'PEH3', 'PEH4',
		'EAPP', 'EMPTECH', 'PRACRES1', 'PRACRES2', 'PWKD', 'INQUIRY',
		'PHILPOL', 'INTWORL', 'TRENDS', 'COMMENG', 'DISS', 'DIASS',
		'CRENON', 'HUMSRES', 'CREWRI',
	),
	'GAS' => array(
		'ORALCOM', 'KOMFIL', 'GENMATH', 'ELS', 'PERDEV', 'UCSP', 'PEH1',
		'READWRIT', 'LIT21', 'STATPROB', 'PHYSCI', 'PHILO', 'MIL', 'PEH2',
		'CPA', 'PEH3', 'PEH4',
		'EAPP', 'EMPTECH', 'PRACRES1', 'PRACRES2', 'PWKD', 'INQUIRY',
		'BUSMATH', 'ORGMAN', 'INTWORL', 'APECON', 'TRENDS', 'DISS',
		'CRENON', 'CREWRI', 'GASRES',
	),
);

// =====================================================================
// 1. Insert subjects (idempotent on the unique code)
// =====================================================================
$subj_inserted = 0;
foreach ($SUBJECTS as $code => $data)
{
	$stmt = db()->prepare("INSERT IGNORE INTO subjects (code, title, units) VALUES (?, ?, ?)");
	$stmt->bind_param('ssd', $code, $data[0], $data[1]);
	$stmt->execute();
	$subj_inserted += (int) db()->affected_rows;
}
$report['subjects_inserted'] = $subj_inserted;
$report['subjects_total'] = (int) db()->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];

// =====================================================================
// 2. Insert curriculum rows per strand x grade x semester (idempotent)
// =====================================================================
$cur_inserted = 0;
$cur_expected = 0;
$cur_after = 0;
foreach ($STRANDS as $strand_code => $codes)
{
	$row = db()->query("SELECT id FROM programs WHERE program_code = '$strand_code'")->fetch_row();
	if ( ! $row)
	{
		$warnings[] = "Strand '$strand_code' not found in programs — curriculum NOT seeded for it.";
		continue;
	}
	$program_id = (int) $row[0];

	foreach ($codes as $code)
	{
		if ( ! isset($SUBJECTS[$code]))
		{
			$warnings[] = "Unknown subject code '$code' in strand '$strand_code' — skipped.";
			continue;
		}
		$subject_id = (int) db()->query("SELECT id FROM subjects WHERE code = '$code'")->fetch_row()[0];
		foreach ($PLACEMENT[$code] as $year => $sems)
		{
			foreach ($sems as $sem)
			{
				$cur_expected++;
				$stmt = db()->prepare(
					'INSERT IGNORE INTO curriculum_subjects (program_id, year_level, semester_number, subject_id) VALUES (?, ?, ?, ?)'
				);
				$stmt->bind_param('iiii', $program_id, $year, $sem, $subject_id);
				$stmt->execute();
				$cur_inserted += (int) db()->affected_rows;
			}
		}
	}
}
$report['curriculum_inserted'] = $cur_inserted;
$report['curriculum_expected'] = $cur_expected;
$report['curriculum_total'] = (int) db()->query("SELECT COUNT(*) FROM curriculum_subjects")->fetch_row()[0];
$cur_after = $report['curriculum_total'];

// =====================================================================
// 3. Summary per strand x grade x semester
// =====================================================================
$summary = array();
foreach (array('STEM', 'ABM', 'HUMSS', 'GAS') as $sc)
{
	$pid = (int) db()->query("SELECT id FROM programs WHERE program_code = '$sc'")->fetch_row()[0];
	foreach (array(11, 12) as $year)
	{
		foreach (array(1, 2) as $sem)
		{
			$n = (int) db()->query(
				"SELECT COUNT(*) FROM curriculum_subjects
				 WHERE program_id = $pid AND year_level = $year AND semester_number = $sem"
			)->fetch_row()[0];
			$summary[] = "$sc G$year S$sem = $n subjects";
		}
	}
}
$report['per_slot'] = $summary;

// =====================================================================
// Output
// =====================================================================
echo "SHS PREDEFINED CURRICULUM SEED REPORT\n";
echo "=====================================\n";
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

$fail = ! empty($warnings) || $cur_after < $cur_expected;
echo $fail ? "\nRESULT: FAIL\n" : "\nRESULT: PASS — predefined SHS curriculum is in place.\n";
exit($fail ? 1 : 0);
