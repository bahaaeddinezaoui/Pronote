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

// Get Administrator ID
$stmtAdmin = $conn->prepare("SELECT ADMINISTRATOR_ID FROM administrator WHERE USER_ID = ?");
$stmtAdmin->bind_param("i", $_SESSION['user_id']);
$stmtAdmin->execute();
$resultAdmin = $stmtAdmin->get_result();

if ($rowAdmin = $resultAdmin->fetch_assoc()) {
    $adminId = $rowAdmin['ADMINISTRATOR_ID'];

    // Insert into admin_read_observation
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_read_observation (OBSERVATION_ID, ADMINISTRATOR_ID) VALUES (?, ?)");
    $stmt->bind_param("ii", $obsId, $adminId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Administrator not found']);
}
$stmtAdmin->close();

$stmt->close();
$conn->close();
?>
