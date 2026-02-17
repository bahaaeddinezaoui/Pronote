<?php
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

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
// Get sections linked to the selected major (and ensure the teacher teaches that major)
$sql = $conn->prepare("
    SELECT DISTINCT SE.SECTION_ID, SE.SECTION_NAME_EN
    FROM SECTION SE
    INNER JOIN STUDIES SD ON SD.SECTION_ID = SE.SECTION_ID
    INNER JOIN TEACHES TH ON TH.MAJOR_ID = SD.MAJOR_ID
    WHERE TH.TEACHER_SERIAL_NUMBER = ? AND SD.MAJOR_ID = ?
");
$sql->bind_param("ss", $teacher_serial, $major_id);
$sql->execute();
$result = $sql->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = [
        "id" => $row["SECTION_ID"],
        "name" => $row["SECTION_NAME_EN"]
    ];
}

echo json_encode(["success" => true, "sections" => $sections]);

$sql->close();
$conn->close();
