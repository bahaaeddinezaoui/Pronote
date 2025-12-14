<?php
// submit_observation.php - Handle observation submission
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_serial = $_SESSION['user_id'];
$session_id = $_SESSION['current_study_session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'No active session found. Please submit attendance first.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$student_serial = $input['studentSerial'] ?? '';
$motif = $input['motif'] ?? '';
$note = $input['note'] ?? '';

if (empty($student_serial) || empty($motif)) {
    echo json_encode(['success' => false, 'message' => 'Student and motif are required.']);
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Generate new Observation ID
function nextId($conn, $table, $column) {
    $result = $conn->query("SELECT MAX($column) AS max_id FROM $table");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}

$observation_id = nextId($conn, 'teacher_makes_an_observation_for_a_student', 'OBSERVATION_ID');
$date_time = date('Y-m-d H:i:s');
$is_new = 1;

$stmt = $conn->prepare("INSERT INTO teacher_makes_an_observation_for_a_student 
    (STUDENT_SERIAL_NUMBER, OBSERVATION_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_ID, OBSERVATION_DATE_AND_TIME, OBSERVATION_MOTIF, OBSERVATION_NOTE, IS_NEW_FOR_ADMIN)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sisisssi", $student_serial, $observation_id, $teacher_serial, $session_id, $date_time, $motif, $note, $is_new);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Observation submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving observation: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
