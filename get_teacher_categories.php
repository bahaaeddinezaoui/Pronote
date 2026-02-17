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

// First try to get categories based on teacher's assignments
$sql = $conn->prepare("
    SELECT DISTINCT C.CATEGORY_ID, C.CATEGORY_NAME_EN
    FROM CATEGORY C
    INNER JOIN SECTION SE ON SE.CATEGORY_ID = C.CATEGORY_ID
    INNER JOIN STUDIES SD ON SD.SECTION_ID = SE.SECTION_ID
    INNER JOIN TEACHES TH ON TH.MAJOR_ID = SD.MAJOR_ID
    WHERE TH.TEACHER_SERIAL_NUMBER = ?
    ORDER BY C.CATEGORY_NAME_EN
");
$sql->bind_param('s', $teacherSerial);
$sql->execute();
$res = $sql->get_result();

$categories = [];
while ($r = $res->fetch_assoc()) {
    $categories[] = [
        'id' => (int)$r['CATEGORY_ID'],
        'name' => $r['CATEGORY_NAME_EN'],
    ];
}
$sql->close();

// If no categories found (teacher has no assignments), get all categories
if (empty($categories)) {
    $sqlAll = $conn->prepare("SELECT CATEGORY_ID, CATEGORY_NAME_EN FROM category ORDER BY CATEGORY_NAME_EN");
    $sqlAll->execute();
    $resAll = $sqlAll->get_result();
    
    while ($r = $resAll->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$r['CATEGORY_ID'],
            'name' => $r['CATEGORY_NAME_EN'],
        ];
    }
    $sqlAll->close();
}
$conn->close();

echo json_encode(['success' => true, 'categories' => $categories]);
