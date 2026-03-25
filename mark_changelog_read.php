<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$changelog_id = $_POST['changelog_id'] ?? 0;

if ($changelog_id > 0) {
    // DB details
    $servername = "localhost";
    $username_db = "root";
    $password_db = "08212001";
    $dbname = "edutrack";

    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE user_account SET LAST_SEEN_CHANGELOG_ID = ? WHERE USER_ID = ?");
    $stmt->bind_param("ii", $changelog_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
?>
