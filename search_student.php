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
$sql = "SELECT STUDENT_SERIAL_NUMBER, STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN, STUDENT_FIRST_NAME_AR, STUDENT_LAST_NAME_AR
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
    $label = $row['STUDENT_FIRST_NAME_EN'] . ' ' . $row['STUDENT_LAST_NAME_EN'];
    if (!empty($row['STUDENT_FIRST_NAME_AR'])) {
        $label .= ' (' . $row['STUDENT_FIRST_NAME_AR'] . ' ' . $row['STUDENT_LAST_NAME_AR'] . ')';
    }
    $students[] = [
        'serial_number' => $row['STUDENT_SERIAL_NUMBER'],
        'first_name' => $row['STUDENT_FIRST_NAME_EN'],
        'last_name' => $row['STUDENT_LAST_NAME_EN'],
        'label' => $label
    ];
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
