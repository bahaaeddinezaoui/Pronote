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
$password_db = "08212001";
$dbname = "edutrack";

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
        STUDENT_FIRST_NAME_EN,
        STUDENT_LAST_NAME_EN,
        SECTION_ID,
        STUDENT_PHOTO
    FROM student
    ORDER BY STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN
";

$result = $conn->query($query);

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Handle photo - check if it's a file path or blob data
        $photoData = null;
        if (!empty($row['STUDENT_PHOTO'])) {
            // Check if it's a file path (new format) or blob data (old format)
            if (filter_var($row['STUDENT_PHOTO'], FILTER_VALIDATE_URL) || file_exists(__DIR__ . '/' . $row['STUDENT_PHOTO'])) {
                // It's a file path
                $photoData = $row['STUDENT_PHOTO'];
            } else {
                // It's blob data (legacy)
                $photoData = base64_encode($row['STUDENT_PHOTO']);
            }
        }
        
        $students[] = [
            'serial_number' => $row['STUDENT_SERIAL_NUMBER'],
            'first_name' => htmlspecialchars($row['STUDENT_FIRST_NAME_EN']),
            'last_name' => htmlspecialchars($row['STUDENT_LAST_NAME_EN']),
            'section_id' => $row['SECTION_ID'],
            'photo' => $photoData
        ];
    }
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
