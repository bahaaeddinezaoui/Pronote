<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
    exit;
}

if (empty($_SESSION['needs_onboarding'])) {
    echo json_encode(['success' => false, 'message' => t('access_denied')]);
    exit;
}

if (empty($_SESSION['onboarding_password_changed'])) {
    echo json_encode(['success' => false, 'message' => t('access_denied')]);
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

    $check = $conn->prepare("
        SELECT 1
        FROM STUDIES SD
        WHERE SD.SECTION_ID = ?
          AND SD.MAJOR_ID = ?
        LIMIT 1
    ");

    $ins = $conn->prepare("INSERT INTO TEACHES (MAJOR_ID, TEACHER_SERIAL_NUMBER) VALUES (?, ?)");

    $majorsToInsert = [];

    foreach ($assignments as $a) {
        $majorId = $a['major_id'] ?? '';
        $sectionIds = $a['section_ids'] ?? [];
        if ($majorId === '' || !is_array($sectionIds) || count($sectionIds) === 0) {
            throw new Exception('Invalid assignment payload.');
        }

        $majorsToInsert[$majorId] = true;

        foreach ($sectionIds as $sectionId) {
            $sectionId = (int)$sectionId;
            if ($sectionId <= 0) {
                continue;
            }

            $check->bind_param('is', $sectionId, $majorId);
            $check->execute();
            $resCheck = $check->get_result();
            if (!$resCheck || $resCheck->num_rows === 0) {
                throw new Exception('Invalid selection.');
            }
        }
    }

    foreach (array_keys($majorsToInsert) as $majorId) {
        $ins->bind_param('ss', $majorId, $teacherSerial);
        $ins->execute();
    }

    $check->close();
    $ins->close();

    $upd = $conn->prepare("UPDATE user_account SET LAST_LOGIN_AT = NOW() WHERE USER_ID = ?");
    $upd->bind_param('i', $userId);
    $upd->execute();
    $upd->close();

    $conn->commit();

    $_SESSION['needs_onboarding'] = false;
    unset($_SESSION['onboarding_password_changed']);
    unset($_SESSION['onboarding_welcome_seen']);

    echo json_encode(['success' => true, 'redirect' => 'teacher_home.php']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
