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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('access_denied')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$oldPassword = $input['oldPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => t('fields_required')]);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => t('password_mismatch')]);
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

$stmt = $conn->prepare("SELECT PASSWORD_HASH FROM USER_ACCOUNT WHERE USER_ID = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row || !isset($row['PASSWORD_HASH'])) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    exit;
}

if (!password_verify($oldPassword, $row['PASSWORD_HASH'])) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => t('old_password_incorrect')]);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE USER_ACCOUNT SET PASSWORD_HASH = ? WHERE USER_ID = ?");
$upd->bind_param('si', $newHash, $userId);
$ok = $upd->execute();
$upd->close();
$conn->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    exit;
}

$_SESSION['onboarding_password_changed'] = true;

echo json_encode(['success' => true]);
