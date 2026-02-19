<?php
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/lang/i18n.php';

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

// --- Get POST data ---
$query = $_POST['query'] ?? '';
$sections = $_POST['sections'] ?? [];

if (empty($query) || empty($sections)) {
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($sections), '?'));
$types = str_repeat('i', count($sections));
$sql = "SELECT STUDENT_SERIAL_NUMBER, STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN, STUDENT_FIRST_NAME_AR, STUDENT_LAST_NAME_AR, STUDENT_PHOTO
        FROM student
        WHERE SECTION_ID IN ($placeholders)
          AND (STUDENT_FIRST_NAME_EN LIKE CONCAT('%', ?, '%')
               OR STUDENT_LAST_NAME_EN LIKE CONCAT('%', ?, '%')
               OR STUDENT_FIRST_NAME_AR LIKE CONCAT('%', ?, '%')
               OR STUDENT_LAST_NAME_AR LIKE CONCAT('%', ?, '%'))";

$stmt = $conn->prepare($sql);
$params = [...$sections, $query, $query, $query, $query];
$stmt->bind_param($types . 'ssss', ...$params);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $firstName = ($LANG === 'ar' && !empty($row['STUDENT_FIRST_NAME_AR'])) ? $row['STUDENT_FIRST_NAME_AR'] : $row['STUDENT_FIRST_NAME_EN'];
    $lastName = ($LANG === 'ar' && !empty($row['STUDENT_LAST_NAME_AR'])) ? $row['STUDENT_LAST_NAME_AR'] : $row['STUDENT_LAST_NAME_EN'];
    
    $label = $firstName . ' ' . $lastName;
    
    $students[] = [
        'serial_number' => $row['STUDENT_SERIAL_NUMBER'],
        'first_name' => $firstName,
        'last_name' => $lastName,
        'photo' => $row['STUDENT_PHOTO'],
        'label' => $label
    ];
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
