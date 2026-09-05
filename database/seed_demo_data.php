<?php
/**
 * EduTrack — Demo Data Seeder
 * 
 * Creates 25 students across all 4 strands with:
 * - Realistic Filipino names
 * - Student numbers
 * - Section enrollments (Semester 1 & 2)
 * - Grades across multiple subjects
 * - Grade logs
 *
 * Run: php database/seed_demo_data.php
 */

$mysqli = new mysqli('127.0.0.1', 'root', '', 'edutrack', 3307);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset('utf8mb4');

echo "=== EduTrack Demo Data Seeder ===\n\n";

// Check if data already exists
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student'");
$row = $result->fetch_assoc();
if ($row['cnt'] > 1) {
    echo "WARNING: There are already {$row['cnt']} student users.\n";
    echo "This seeder will ADD more students (not replace existing ones).\n\n";
}

// Default password hash for all demo students
$default_password = password_hash('student123', PASSWORD_DEFAULT);

// ── Student Data ──────────────────────────────────────────────
// Distributed across 4 strands, 2 sections each
$students = [
    // STEM — 11-STEM-1 (section 33) — 4 students
    ['first' => 'Maria',      'last' => 'Santos',      'email' => 'maria.santos@student.edu',     'section' => 33, 'sy' => 1],
    ['first' => 'Juan',       'last' => 'Dela Cruz',   'email' => 'juan.delacruz@student.edu',    'section' => 33, 'sy' => 1],
    ['first' => 'Ana',        'last' => 'Reyes',       'email' => 'ana.reyes@student.edu',        'section' => 33, 'sy' => 1],
    ['first' => 'Carlos',     'last' => 'Garcia',      'email' => 'carlos.garcia@student.edu',    'section' => 33, 'sy' => 1],

    // STEM — 11-STEM-2 (section 34) — 3 students
    ['first' => 'Rose',       'last' => 'Torres',      'email' => 'rose.torres@student.edu',      'section' => 34, 'sy' => 1],
    ['first' => 'Miguel',     'last' => 'Lim',         'email' => 'miguel.lim@student.edu',       'section' => 34, 'sy' => 1],
    ['first' => 'Sofia',      'last' => 'Rivera',      'email' => 'sofia.rivera@student.edu',     'section' => 34, 'sy' => 1],

    // ABM — 11-ABM-1 (section 38) — 4 students
    ['first' => 'Joshua',     'last' => 'Mendoza',     'email' => 'joshua.mendoza@student.edu',   'section' => 38, 'sy' => 1],
    ['first' => 'Patricia',   'last' => 'Cruz',        'email' => 'patricia.cruz@student.edu',    'section' => 38, 'sy' => 1],
    ['first' => 'Angelo',     'last' => 'Bautista',    'email' => 'angelo.bautista@student.edu',  'section' => 38, 'sy' => 1],
    ['first' => 'Camille',    'last' => 'Villanueva',  'email' => 'camille.villanueva@student.edu','section' => 38, 'sy' => 1],

    // ABM — 11-ABM-2 (section 39) — 2 students
    ['first' => 'Daniel',     'last' => 'Fernandez',   'email' => 'daniel.fernandez@student.edu', 'section' => 39, 'sy' => 1],
    ['first' => 'Nicole',     'last' => 'Pascual',     'email' => 'nicole.pascual@student.edu',   'section' => 39, 'sy' => 1],

    // HUMSS — 11-HUMSS-1 (section 43) — 4 students
    ['first' => 'Bianca',     'last' => 'Aquino',      'email' => 'bianca.aquino@student.edu',    'section' => 43, 'sy' => 1],
    ['first' => 'Rafael',     'last' => 'Morales',     'email' => 'rafael.morales@student.edu',   'section' => 43, 'sy' => 1],
    ['first' => 'Ivana',      'last' => 'Gonzales',    'email' => 'ivana.gonzales@student.edu',   'section' => 43, 'sy' => 1],
    ['first' => 'Mark',       'last' => 'Santiago',    'email' => 'mark.santiago@student.edu',    'section' => 43, 'sy' => 1],

    // HUMSS — 11-HUMSS-2 (section 44) — 2 students
    ['first' => 'Chloe',      'last' => 'Diaz',        'email' => 'chloe.diaz@student.edu',       'section' => 44, 'sy' => 1],
    ['first' => 'Kevin',      'last' => 'Ramos',       'email' => 'kevin.ramos@student.edu',      'section' => 44, 'sy' => 1],

    // GAS — 11-GAS-1 (section 48) — 4 students
    ['first' => 'Princess',   'last' => 'Torres',      'email' => 'princess.torres@student.edu',  'section' => 48, 'sy' => 1],
    ['first' => 'Andre',      'last' => 'Castro',      'email' => 'andre.castro@student.edu',     'section' => 48, 'sy' => 1],
    ['first' => 'Angel',      'last' => 'Navarro',     'email' => 'angel.navarro@student.edu',    'section' => 48, 'sy' => 1],
    ['first' => 'Jerome',     'last' => 'Padilla',     'email' => 'jerome.padilla@student.edu',   'section' => 48, 'sy' => 1],

    // GAS — 11-GAS-2 (section 49) — 2 students
    ['first' => 'Trisha',     'last' => 'Gomez',       'email' => 'trisha.gomez@student.edu',     'section' => 49, 'sy' => 1],
    ['first' => 'Luis',       'last' => 'Hernandez',   'email' => 'luis.hernandez@student.edu',   'section' => 49, 'sy' => 1],
];

// ── Insert Students ──────────────────────────────────────────
echo "Creating " . count($students) . " students...\n";
$inserted = 0;
$student_ids = [];

foreach ($students as $s) {
    // Create user account
    $username = strtolower(str_replace(' ', '.', $s['first'] . '.' . $s['last']));
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name, status, must_change_password) VALUES (?, ?, ?, 'student', ?, ?, 'active', 0)");
    $stmt->bind_param('sssss', $username, $s['email'], $default_password, $s['first'], $s['last']);
    
    if ($stmt->execute()) {
        $user_id = $mysqli->insert_id;
        
        // Create student record
        $student_no = '2026-' . str_pad($inserted + 2, 4, '0', STR_PAD_LEFT);
        $stmt2 = $mysqli->prepare("INSERT INTO students (user_id, student_no, section_id) VALUES (?, ?, ?)");
        $stmt2->bind_param('isi', $user_id, $student_no, $s['section']);
        
        if ($stmt2->execute()) {
            $student_id = $mysqli->insert_id;
            $student_ids[] = ['id' => $student_id, 'user_id' => $user_id, 'section' => $s['section'], 'sy' => $s['sy']];
            $inserted++;
        }
        $stmt2->close();
    }
    $stmt->close();
}
echo "  Created $inserted students\n\n";

// ── Create Enrollments ───────────────────────────────────────
echo "Creating enrollments...\n";
$enrolled = 0;
foreach ($student_ids as $stu) {
    // Enroll in Semester 1 (S.Y. 2025-2026)
    $stmt = $mysqli->prepare("INSERT IGNORE INTO enrollments (student_id, semester_id, section_id) VALUES (?, 1, ?)");
    $stmt->bind_param('ii', $stu['id'], $stu['section']);
    if ($stmt->execute() && $mysqli->affected_rows > 0) {
        $enrolled++;
    }
    $stmt->close();
    
    // Also enroll in Semester 2 (active) — same section
    $stmt = $mysqli->prepare("INSERT IGNORE INTO enrollments (student_id, semester_id, section_id) VALUES (?, 2, ?)");
    $stmt->bind_param('ii', $stu['id'], $stu['section']);
    $stmt->execute();
    $stmt->close();
}
echo "  Created $enrolled enrollments for Semester 1\n\n";

// ── Create Grades ────────────────────────────────────────────
echo "Creating grades...\n";

// Get subjects for each section (semester 1)
$grade_count = 0;
$grade_values = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00];

foreach ($student_ids as $stu) {
    // Get section subjects for semester 1
    $result = $mysqli->query("
        SELECT ss.subject_id 
        FROM section_subjects ss 
        WHERE ss.section_id = {$stu['section']} AND ss.semester_id = 1
    ");
    
    while ($subj = $result->fetch_assoc()) {
        $subject_id = $subj['subject_id'];
        
        // Get grading period IDs for semester 1
        $gp_result = $mysqli->query("SELECT id, period_name FROM grading_periods WHERE semester_id = 1");
        
        $midterm_grade = $grade_values[array_rand($grade_values)];
        $final_grade = $grade_values[array_rand($grade_values)];
        
        while ($gp = $gp_result->fetch_assoc()) {
            $grade = ($gp['period_name'] === 'Midterm') ? $midterm_grade : $final_grade;
            $period_id = $gp['id'];
            
            // Find a teacher assigned to this section/subject
            $teacher_result = $mysqli->query("
                SELECT tsa.teacher_user_id 
                FROM teacher_subject_assignments tsa 
                WHERE tsa.section_id = {$stu['section']} 
                AND tsa.subject_id = $subject_id 
                AND tsa.semester_id = 1 
                LIMIT 1
            ");
            $teacher_row = $teacher_result->fetch_assoc();
            $teacher_id = $teacher_row ? $teacher_row['teacher_user_id'] : 2; // default to mlopez
            
            // Upsert grade
            $stmt = $mysqli->prepare("
                INSERT INTO grades (student_id, subject_id, teacher_id, grading_period_id, grade_value)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE grade_value = VALUES(grade_value), updated_at = NOW()
            ");
            $stmt->bind_param('iiidd', $stu['id'], $subject_id, $teacher_id, $period_id, $grade);
            if ($stmt->execute()) {
                $grade_count++;
                $grade_id = $mysqli->insert_id;
                // Create grade log entry (grade_logs references grade_id)
                if ($grade_id > 0) {
                    $log = $mysqli->prepare("INSERT INTO grade_logs (grade_id, old_value, new_value, changed_by) VALUES (?, NULL, ?, ?)");
                    $log->bind_param('idi', $grade_id, $grade, $teacher_id);
                    $log->execute();
                    $log->close();
                }
            }
            $stmt->close();
        }
    }
}
echo "  Created $grade_count grade records\n\n";

// ── Summary ──────────────────────────────────────────────────
echo "=== Final Database State ===\n";
$tables = ['users', 'students', 'enrollments', 'grades', 'grade_logs', 'tickets', 'notifications'];
foreach ($tables as $t) {
    $r = $mysqli->query("SELECT COUNT(*) as cnt FROM $t");
    $row = $r->fetch_assoc();
    echo "  $t: {$row['cnt']} records\n";
}

$mysqli->close();
echo "\n=== Demo data seeded successfully! ===\n";
echo "Login credentials:\n";
echo "  Admin:  admin@school.edu / admin123\n";
echo "  Teacher: mlopez@school.edu / Teacher@1234\n";
echo "  Student: maria.santos@student.edu / student123\n";
