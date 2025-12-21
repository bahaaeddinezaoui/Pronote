<?php
// get_all_students.php - Fetch all students for autocomplete
session_start();

header('Content-Type: application/json');

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

// Fetch all students
$query = "
    SELECT 
        STUDENT_SERIAL_NUMBER,
        STUDENT_FIRST_NAME,
        STUDENT_LAST_NAME,
        SECTION_ID
    FROM student
    ORDER BY STUDENT_FIRST_NAME, STUDENT_LAST_NAME
";

$result = $conn->query($query);

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'serial_number' => $row['STUDENT_SERIAL_NUMBER'],
            'first_name' => htmlspecialchars($row['STUDENT_FIRST_NAME']),
            'last_name' => htmlspecialchars($row['STUDENT_LAST_NAME']),
            'section_id' => $row['SECTION_ID']
        ];
    }
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
