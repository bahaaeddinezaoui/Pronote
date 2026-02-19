<?php
// get_majors.php - AJAX endpoint to fetch majors based on category and teacher

session_start();
require_once __DIR__ . '/lang/i18n.php';

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get parameters from request
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$teacher_serial = isset($_GET['teacher_serial']) ? $_GET['teacher_serial'] : '';

// Validate inputs
if ($category_id <= 0 || empty($teacher_serial)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Query to get majors that the teacher teaches in the selected category
$sql_majors = $conn->prepare("
    SELECT DISTINCT M.MAJOR_ID, M.MAJOR_NAME_EN, M.MAJOR_NAME_AR
    FROM MAJOR M
    INNER JOIN TEACHES TH ON TH.MAJOR_ID = M.MAJOR_ID
    INNER JOIN STUDIES SD ON SD.MAJOR_ID = M.MAJOR_ID
    INNER JOIN SECTION SE ON SD.SECTION_ID = SE.SECTION_ID
    WHERE TH.TEACHER_SERIAL_NUMBER = ? AND SE.CATEGORY_ID = ?
    ORDER BY M.MAJOR_NAME_EN
");

$sql_majors->bind_param("si", $teacher_serial, $category_id);
$sql_majors->execute();
$result_majors = $sql_majors->get_result();

$majors = [];
if ($result_majors->num_rows > 0) {
    while ($row = $result_majors->fetch_assoc()) {
        $majorName = ($LANG === 'ar' && !empty($row['MAJOR_NAME_AR'])) ? $row['MAJOR_NAME_AR'] : $row['MAJOR_NAME_EN'];
        $majors[] = [
            'id' => $row['MAJOR_ID'],
            'name' => $majorName
        ];
    }
}

$sql_majors->close();
$conn->close();

// Return JSON response
echo json_encode([
    'success' => true,
    'majors' => $majors
]);
?>