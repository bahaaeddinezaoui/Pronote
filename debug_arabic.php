<?php
// Debug script to check Arabic data retrieval
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
$conn->set_charset("utf8mb4");

$serial_number = $_GET['serial_number'] ?? '120240058131';

$query = "SELECT STUDENT_SERIAL_NUMBER, STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN, 
          STUDENT_FIRST_NAME_AR, STUDENT_LAST_NAME_AR
          FROM student WHERE STUDENT_SERIAL_NUMBER = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $serial_number);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "<pre>";
echo "Serial: " . $row['STUDENT_SERIAL_NUMBER'] . "\n";
echo "First Name EN: " . ($row['STUDENT_FIRST_NAME_EN'] ?? 'NULL') . "\n";
echo "Last Name EN: " . ($row['STUDENT_LAST_NAME_EN'] ?? 'NULL') . "\n";
echo "First Name AR: " . ($row['STUDENT_FIRST_NAME_AR'] ?? 'NULL') . "\n";
echo "Last Name AR: " . ($row['STUDENT_LAST_NAME_AR'] ?? 'NULL') . "\n";
echo "\nRaw array:\n";
print_r($row);
echo "</pre>";

$conn->close();
