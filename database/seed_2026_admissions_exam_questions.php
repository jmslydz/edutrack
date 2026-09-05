<?php
/**
 * EduTrack — Admission Exam Question Seeder
 * Creates a bank of general-ability questions for the admission exam.
 * Run: php database/seed_2026_admissions_exam_questions.php
 */

$mysqli = new mysqli('127.0.0.1', 'root', '', 'edutrack', 3306);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset('utf8mb4');

$questions = [
    // ── English ──
    ['Choose the correct sentence:', 'She go to school every day.', 'She goes to school every day.', 'She going to school every day.', 'She gone to school every day.', 'B'],
    ['The word "benevolent" most nearly means:', 'Cruel', 'Generous', 'Lazy', 'Fearful', 'B'],
    ['Which of the following is a noun?', 'Run', 'Beautiful', 'Happiness', 'Quickly', 'C'],
    ['Identify the antonym of "ancient":', 'Old', 'Modern', 'Historic', 'Antique', 'B'],
    ['Choose the correct spelling:', 'Recieve', 'Receeve', 'Receive', 'Receve', 'C'],
    ['What is the plural of "child"?', 'Childs', 'Children', 'Childrens', 'Childes', 'B'],
    ['The phrase "break the ice" means:', 'To destroy frozen water', 'To start a conversation', 'To cause trouble', 'To end a friendship', 'B'],
    ['Select the correct form: "If I ___ rich, I would travel the world."', 'am', 'was', 'were', 'be', 'C'],

    // ── Mathematics ──
    ['What is 15% of 200?', '15', '25', '30', '35', 'C'],
    ['Solve: 3x + 5 = 20. What is x?', '3', '5', '7', '15', 'B'],
    ['What is the next number in the sequence: 2, 6, 12, 20, ___?', '28', '30', '32', '36', 'B'],
    ['A rectangle has length 8 cm and width 5 cm. What is its area?', '13 cm²', '26 cm²', '40 cm²', '80 cm²', 'C'],
    ['What is the value of 7! (7 factorial)?', '5040', '720', '2520', '504', 'A'],
    ['If a car travels 60 km in 1.5 hours, what is its average speed?', '30 km/h', '40 km/h', '45 km/h', '90 km/h', 'B'],
    ['Simplify: 12 ÷ 3 × 2', '2', '4', '8', '18', 'C'],

    // ── Science ──
    ['What is the powerhouse of the cell?', 'Nucleus', 'Ribosome', 'Mitochondria', 'Golgi apparatus', 'C'],
    ['Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'B'],
    ['Water boils at what temperature at sea level?', '90°C', '95°C', '100°C', '110°C', 'C'],
    ['What is the chemical symbol for gold?', 'Go', 'Gd', 'Au', 'Ag', 'C'],
    ['Which gas do plants absorb during photosynthesis?', 'Oxygen', 'Carbon dioxide', 'Nitrogen', 'Hydrogen', 'B'],

    // ── Logical Reasoning ──
    ['If all roses are flowers and some flowers fade quickly, which statement is true?', 'All flowers are roses', 'Some roses may fade quickly', 'No roses fade quickly', 'All flowers fade quickly', 'B'],
    ['Find the odd one out:', 'Apple', 'Banana', 'Carrot', 'Orange', 'C'],
    ['All students in class wear uniforms. Juan is a student in that class. What can we conclude?', 'Juan wears a uniform', 'Juan is a teacher', 'Juan does not wear a uniform', 'Juan is from another school', 'A'],
    ['Which number does not belong: 2, 4, 6, 9, 10', '4', '6', '9', '10', 'C'],
];

echo "=== Admission Exam Question Seeder ===\n\n";

// Check existing
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM exam_questions");
$row = $result->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "WARNING: {$row['cnt']} questions already exist.\n";
    echo "Run this DELETE first to reseed:\n  DELETE FROM exam_questions;\n\n";
}

$inserted = 0;
$stmt = $mysqli->prepare("INSERT INTO exam_questions (question, option_a, option_b, option_c, option_d, correct_answer, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
foreach ($questions as $q) {
    $stmt->bind_param('ssssss', $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
    if ($stmt->execute()) {
        $inserted++;
    }
}
$stmt->close();

echo "Inserted $inserted questions\n";
$r = $mysqli->query("SELECT COUNT(*) as cnt FROM exam_questions");
$row = $r->fetch_assoc();
echo "Total in bank: {$row['cnt']}\n";

$mysqli->close();
echo "\n=== Done! ===\n";