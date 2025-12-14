<?php
// --- SESSION & CONFIG ---
session_start();
date_default_timezone_set('Africa/Algiers');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

// --- RETRIEVE TEACHER INFO ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    die(json_encode(["success" => false, "message" => "Teacher not logged in."]));
}
$teacher_serial = $_SESSION['user_id'];

// --- RETRIEVE FORM DATA ---
$class_id = $_POST['class_id'] ?? null;

$sections = $_POST['sections'] ?? [];
if (!is_array($sections)) $sections = [$sections];

$session_date = $_POST['session_date'] ?? date('Y-m-d');

$motifs = $_POST['motif'] ?? [];
if (!is_array($motifs)) $motifs = [$motifs];

$observations = $_POST['observation'] ?? [];
if (!is_array($observations)) $observations = [$observations];

$first_names = $_POST['first_name'] ?? [];
if (!is_array($first_names)) $first_names = [$first_names];

$last_names = $_POST['last_name'] ?? [];
if (!is_array($last_names)) $last_names = [$last_names];

// --- HANDLE TIME SLOT FIELD ---
$time_slot = $_POST['time_slot'] ?? '';
$start_time = null;
$end_time = null;

if (!empty($time_slot) && strpos($time_slot, ' - ') !== false) {
    list($start_time, $end_time) = array_map('trim', explode(' - ', $time_slot));
}

// --- VALIDATE REQUIRED FIELDS ---
if (empty($class_id)) {
    die(json_encode(["success" => false, "message" => "Class selection is required."]));
}
if (empty($sections)) {
    die(json_encode(["success" => false, "message" => "Please select at least one section."]));
}
if (!$start_time || !$end_time) {
    die(json_encode(["success" => false, "message" => "Start and end times are required."]));
}

// --- HELPER FUNCTION ---
function nextId($conn, $table, $column)
{
    $result = $conn->query("SELECT MAX($column) AS max_id FROM $table");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}

// --- BEGIN TRANSACTION (manual rollback) ---
$conn->autocommit(false);

try {
    // 1️⃣ Insert into study_session
    $session_id = nextId($conn, 'study_session', 'STUDY_SESSION_ID');
    $stmt = $conn->prepare("INSERT INTO study_session 
        (STUDY_SESSION_ID, CLASS_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_DATE, STUDY_SESSION_START_TIME, STUDY_SESSION_END_TIME)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $session_id, $class_id, $teacher_serial, $session_date, $start_time, $end_time);
    $stmt->execute();

    // Store the current study session ID in the PHP session
    $_SESSION['current_study_session_id'] = $session_id;

    // 2️⃣ Link sections to session (studies_in)
    $stmtSection = $conn->prepare("INSERT INTO studies_in (SECTION_ID, STUDY_SESSION_ID) VALUES (?, ?)");
    foreach ($sections as $section_id) {
        $stmtSection->bind_param("ii", $section_id, $session_id);
        $stmtSection->execute();
    }

    // 3️⃣ Insert absences
    $stmtAbs = $conn->prepare("INSERT INTO absence 
        (ABSENCE_ID, STUDY_SESSION_ID, ABSENCE_DATE_AND_TIME, ABSENCE_MOTIF, ABSENCE_OBSERVATION)
        VALUES (?, ?, ?, ?, ?)");
    $stmtStudentAbs = $conn->prepare("INSERT INTO student_gets_absent 
        (STUDENT_SERIAL_NUMBER, ABSENCE_ID) VALUES (?, ?)");

    $absence_date = date('Y-m-d H:i:s');

    for ($i = 0; $i < count($first_names); $i++) {
        $f = trim($first_names[$i]);
        $l = trim($last_names[$i]);
        if ($f === '' || $l === '') continue;

        // Find student serial number
        $stmtFind = $conn->prepare("SELECT STUDENT_SERIAL_NUMBER FROM student WHERE STUDENT_FIRST_NAME = ? AND STUDENT_LAST_NAME = ?");
        $stmtFind->bind_param("ss", $f, $l);
        $stmtFind->execute();
        $result = $stmtFind->get_result();
        if ($result->num_rows === 0) continue;
        $student = $result->fetch_assoc();
        $student_serial = $student['STUDENT_SERIAL_NUMBER'];

        // New absence ID
        $absence_id = nextId($conn, 'absence', 'ABSENCE_ID');
        $motif = $motifs[$i] ?? '';
        $obs = $observations[$i] ?? '';

        // Insert absence
        $stmtAbs->bind_param("iisss", $absence_id, $session_id, $absence_date, $motif, $obs);
        $stmtAbs->execute();

        // Link student <-> absence
        $stmtStudentAbs->bind_param("si", $student_serial, $absence_id);
        $stmtStudentAbs->execute();
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Form submitted successfully!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->autocommit(true);
$conn->close();
?>