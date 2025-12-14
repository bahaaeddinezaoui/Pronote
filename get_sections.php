<?php
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// --- GET PARAMETERS ---
$major_id = isset($_GET['major_id']) ? $_GET['major_id'] : null;
$teacher_serial = isset($_GET['teacher_serial']) ? $_GET['teacher_serial'] : null;

if (!$major_id || !$teacher_serial) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

// --- QUERY ---
// Get sections that study the selected major and belong to the category that studies that major
$sql = $conn->prepare("
    SELECT DISTINCT SE.SECTION_ID, SE.SECTION_NAME
    FROM TEACHER T
    INNER JOIN TEACHES TH ON T.TEACHER_SERIAL_NUMBER = TH.TEACHER_SERIAL_NUMBER
    INNER JOIN MAJOR M ON TH.MAJOR_ID = M.MAJOR_ID
    INNER JOIN STUDIES SD ON M.MAJOR_ID = SD.MAJOR_ID
    INNER JOIN SECTION SE ON SD.SECTION_ID = SE.SECTION_ID
    WHERE T.TEACHER_SERIAL_NUMBER = ? AND M.MAJOR_ID = ?
");
$sql->bind_param("ss", $teacher_serial, $major_id);
$sql->execute();
$result = $sql->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = [
        "id" => $row["SECTION_ID"],
        "name" => $row["SECTION_NAME"]
    ];
}

echo json_encode(["success" => true, "sections" => $sections]);

$sql->close();
$conn->close();
