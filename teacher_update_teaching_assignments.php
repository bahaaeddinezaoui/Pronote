<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('access_denied')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$assignments = $input['assignments'] ?? null;

if (!is_array($assignments) || count($assignments) === 0) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one section and major.']);
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

$conn->autocommit(false);
try {
    $del = $conn->prepare("DELETE FROM TEACHES WHERE TEACHER_SERIAL_NUMBER = ?");
    $del->bind_param('s', $teacherSerial);
    $del->execute();
    $del->close();

    $checkMajor = $conn->prepare("SELECT 1 FROM MAJOR WHERE MAJOR_ID = ? LIMIT 1");
    $checkSection = $conn->prepare("SELECT 1 FROM SECTION WHERE SECTION_ID = ? LIMIT 1");
    $insStudies = $conn->prepare("INSERT IGNORE INTO STUDIES (SECTION_ID, MAJOR_ID) VALUES (?, ?)");
    $ins = $conn->prepare("INSERT INTO TEACHES (MAJOR_ID, TEACHER_SERIAL_NUMBER) VALUES (?, ?)");

    $majorsToInsert = [];

    foreach ($assignments as $a) {
        $majorId = $a['major_id'] ?? '';
        $sectionIds = $a['section_ids'] ?? [];
        if ($majorId === '' || !is_array($sectionIds) || count($sectionIds) === 0) {
            throw new Exception('Invalid assignment payload.');
        }

        $checkMajor->bind_param('s', $majorId);
        $checkMajor->execute();
        $resMajor = $checkMajor->get_result();
        if (!$resMajor || $resMajor->num_rows === 0) {
            throw new Exception('Invalid major selected.');
        }

        $majorsToInsert[$majorId] = true;

        foreach ($sectionIds as $sectionId) {
            $sectionId = (int)$sectionId;
            if ($sectionId <= 0) {
                continue;
            }

            $checkSection->bind_param('i', $sectionId);
            $checkSection->execute();
            $resSection = $checkSection->get_result();
            if (!$resSection || $resSection->num_rows === 0) {
                throw new Exception('Invalid section selected.');
            }

            $insStudies->bind_param('is', $sectionId, $majorId);
            $insStudies->execute();
        }
    }

    foreach (array_keys($majorsToInsert) as $majorId) {
        $ins->bind_param('ss', $majorId, $teacherSerial);
        $ins->execute();
    }

    $checkMajor->close();
    $checkSection->close();
    $insStudies->close();
    $ins->close();

    $conn->commit();

    echo json_encode(['success' => true, 'redirect' => 'options.php']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
