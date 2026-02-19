<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => t('unauthorized')]);
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

if ($oldPassword === $newPassword) {
    echo json_encode(['success' => false, 'message' => t('password_same_as_old')]);
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
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    $conn->close();
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row || !isset($row['PASSWORD_HASH'])) {
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    $conn->close();
    exit;
}

$hash = $row['PASSWORD_HASH'];
if (!password_verify($oldPassword, $hash)) {
    echo json_encode(['success' => false, 'message' => t('old_password_incorrect')]);
    $conn->close();
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE USER_ACCOUNT SET PASSWORD_HASH = ? WHERE USER_ID = ?");
if (!$upd) {
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    $conn->close();
    exit;
}

$upd->bind_param("si", $newHash, $userId);
$ok = $upd->execute();
$upd->close();
$conn->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => t('password_change_failed')]);
    exit;
}

echo json_encode(['success' => true, 'message' => t('password_updated_logout')]);
