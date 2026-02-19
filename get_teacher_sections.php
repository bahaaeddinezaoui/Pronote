<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
    exit;
}

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
if ($categoryId <= 0) {
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

// First try to get sections based on teacher's assignments
$sql = $conn->prepare("
    SELECT DISTINCT SE.SECTION_ID, SE.SECTION_NAME_EN, SE.SECTION_NAME_AR
    FROM SECTION SE
    INNER JOIN STUDIES SD ON SD.SECTION_ID = SE.SECTION_ID
    INNER JOIN TEACHES TH ON TH.MAJOR_ID = SD.MAJOR_ID
    WHERE TH.TEACHER_SERIAL_NUMBER = ?
      AND SE.CATEGORY_ID = ?
    ORDER BY SE.SECTION_NAME_EN
");
$sql->bind_param('si', $teacherSerial, $categoryId);
$sql->execute();
$res = $sql->get_result();

$sections = [];
while ($r = $res->fetch_assoc()) {
    $sectionName = ($LANG === 'ar' && !empty($r['SECTION_NAME_AR'])) ? $r['SECTION_NAME_AR'] : $r['SECTION_NAME_EN'];
    $sections[] = [
        'id' => (int)$r['SECTION_ID'],
        'name' => $sectionName,
    ];
}
$sql->close();

// If no sections found (teacher has no assignments for this category), get all sections for the category
if (empty($sections)) {
    $sqlAll = $conn->prepare("
        SELECT SECTION_ID, SECTION_NAME_EN, SECTION_NAME_AR 
        FROM SECTION 
        WHERE CATEGORY_ID = ?
        ORDER BY SECTION_NAME_EN
    ");
    $sqlAll->bind_param('i', $categoryId);
    $sqlAll->execute();
    $resAll = $sqlAll->get_result();
    
    while ($r = $resAll->fetch_assoc()) {
        $sectionName = ($LANG === 'ar' && !empty($r['SECTION_NAME_AR'])) ? $r['SECTION_NAME_AR'] : $r['SECTION_NAME_EN'];
        $sections[] = [
            'id' => (int)$r['SECTION_ID'],
            'name' => $sectionName,
        ];
    }
    $sqlAll->close();
}
$conn->close();

echo json_encode(['success' => true, 'sections' => $sections]);
