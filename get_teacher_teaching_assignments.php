<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
    exit;
}

$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => t('db_connection_failed')]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmtTeacher = $conn->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
$stmtTeacher->bind_param('i', $userId);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher ? $resTeacher->fetch_assoc() : null;
$stmtTeacher->close();

if (!$rowTeacher || empty($rowTeacher['TEACHER_SERIAL_NUMBER'])) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Teacher record not found.']);
    exit;
}

$teacherSerial = $rowTeacher['TEACHER_SERIAL_NUMBER'];

$stmt = $conn->prepare("
    SELECT DISTINCT sd.MAJOR_ID, sd.SECTION_ID
    FROM STUDIES sd
    INNER JOIN TEACHES th ON th.MAJOR_ID = sd.MAJOR_ID
    WHERE th.TEACHER_SERIAL_NUMBER = ?
");
$stmt->bind_param('s', $teacherSerial);
$stmt->execute();
$res = $stmt->get_result();

$assignments = [];
while ($r = $res->fetch_assoc()) {
    $assignments[] = [
        'major_id' => $r['MAJOR_ID'],
        'section_id' => (int)$r['SECTION_ID']
    ];
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'assignments' => $assignments]);
