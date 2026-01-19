<?php
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// --- Get selected section IDs ---
if (!isset($_POST['sections']) || empty($_POST['sections'])) {
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$sections = $_POST['sections'];
$placeholders = implode(',', array_fill(0, count($sections), '?'));
$types = str_repeat('i', count($sections));

// --- Fetch students belonging to the selected sections ---
$stmt = $conn->prepare("SELECT STUDENT_SERIAL_NUMBER, STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN 
                        FROM student 
                        WHERE SECTION_ID IN ($placeholders)");
$stmt->bind_param($types, ...$sections);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'serial_number' => $row['STUDENT_SERIAL_NUMBER'],
        'first_name' => $row['STUDENT_FIRST_NAME_EN'],
        'last_name' => $row['STUDENT_LAST_NAME_EN']
    ];
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
