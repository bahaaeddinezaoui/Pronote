<?php
// mark_observation_read.php - Mark an observation as read
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get input
$data = json_decode(file_get_contents('php://input'), true);
$obsId = $data['observation_id'] ?? null;

if (!$obsId) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Update IS_NEW_FOR_ADMIN = 0
$stmt = $conn->prepare("UPDATE teacher_makes_an_observation_for_a_student SET IS_NEW_FOR_ADMIN = 0 WHERE OBSERVATION_ID = ?");
$stmt->bind_param("i", $obsId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>
