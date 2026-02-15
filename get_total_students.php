<?php
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$sections = $_POST['sections'] ?? [];

if (empty($sections)) {
    echo json_encode(['success' => false, 'total' => 0]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($sections), '?'));
$types = str_repeat('i', count($sections));

$sql = "SELECT COUNT(*) AS total FROM student WHERE SECTION_ID IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$sections);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['success' => true, 'total' => intval($result['total'])]);
$conn->close();
?>
