<?php
// submit_observation.php - Handle observation submission
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$teacher_serial = null;
$stmtT = null;
// Lookup teacher serial from teacher table using logged in user id
$stmtT = $conn->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
if ($stmtT) {
    $stmtT->bind_param("i", $_SESSION['user_id']);
    $stmtT->execute();
    $resT = $stmtT->get_result();
    if ($resT && $resT->num_rows > 0) {
        $teacher_serial = $resT->fetch_assoc()['TEACHER_SERIAL_NUMBER'];
    }
    $stmtT->close();
}
if (empty($teacher_serial)) {
    echo json_encode(['success' => false, 'message' => 'Teacher record not found for this account.']);
    $conn->close();
    exit;
}
$session_id = $_SESSION['current_study_session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'No active session found. Please submit attendance first.']);
    $conn->close();
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$student_serial = $input['studentSerial'] ?? '';
$motif = $input['motif'] ?? '';
$note = $input['note'] ?? '';

if (empty($student_serial) || empty($motif)) {
    echo json_encode(['success' => false, 'message' => 'Student and motif are required.']);
    $conn->close();
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

$motif_id_int = (int)$motif;
$stmt = $conn->prepare("INSERT INTO teacher_makes_an_observation_for_a_student 
    (STUDENT_SERIAL_NUMBER, OBSERVATION_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_ID, OBSERVATION_DATE_AND_TIME, OBSERVATION_MOTIF_ID, OBSERVATION_NOTE)
    VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sisisis", $student_serial, $observation_id, $teacher_serial, $session_id, $date_time, $motif_id_int, $note);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Observation submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving observation: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
