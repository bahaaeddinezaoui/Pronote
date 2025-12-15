<?php
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// --- Get POST data ---
$query = $_POST['query'] ?? '';
$sections = $_POST['sections'] ?? [];

if (empty($query) || empty($sections)) {
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($sections), '?'));
$types = str_repeat('i', count($sections));
$sql = "SELECT STUDENT_FIRST_NAME, STUDENT_LAST_NAME
        FROM student
        WHERE SECTION_ID IN ($placeholders)
          AND (STUDENT_FIRST_NAME LIKE CONCAT('%', ?, '%')
               OR STUDENT_LAST_NAME LIKE CONCAT('%', ?, '%'))";

$stmt = $conn->prepare($sql);
$params = [...$sections, $query, $query];
$stmt->bind_param($types . 'ss', ...$params);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'first_name' => $row['STUDENT_FIRST_NAME'],
        'last_name' => $row['STUDENT_LAST_NAME']
    ];
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
