<?php
// mark_all_observations_read.php - Mark all observations as read for the current admin
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

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

    // Insert all unread observations for this admin into admin_read_observation
    $sql = "
        INSERT IGNORE INTO admin_read_observation (OBSERVATION_ID, ADMINISTRATOR_ID)
        SELECT tmo.OBSERVATION_ID, ?
        FROM teacher_makes_an_observation_for_a_student tmo
        WHERE NOT EXISTS (
            SELECT 1 
            FROM admin_read_observation aro 
            WHERE aro.OBSERVATION_ID = tmo.OBSERVATION_ID 
            AND aro.ADMINISTRATOR_ID = ?
        )
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $adminId, $adminId);

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
$conn->close();
?>
