<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
    exit;
}

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
if ($sectionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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

// Get all majors from the database
$sql = $conn->prepare("
    SELECT MAJOR_ID, MAJOR_NAME_EN 
    FROM MAJOR 
    ORDER BY MAJOR_NAME_EN
");
$sql->execute();
$res = $sql->get_result();

$majors = [];
while ($r = $res->fetch_assoc()) {
    $majors[] = [
        'id' => $r['MAJOR_ID'],
        'name' => $r['MAJOR_NAME_EN'],
    ];
}
$sql->close();
$conn->close();

echo json_encode(['success' => true, 'majors' => $majors]);
