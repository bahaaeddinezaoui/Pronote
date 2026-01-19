<?php
// get_student_records.php - Fetch student information, absences, and observations
session_start();
date_default_timezone_set('Africa/Algiers');

header('Content-Type: application/json');

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : null;
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

if (!$serial_number || !$start_date || !$end_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    $conn->close();
    exit;
}

// Validate dates
if (strtotime($start_date) === false || strtotime($end_date) === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    $conn->close();
    exit;
}

try {
    // 1. Get student basic information
    $studentQuery = "
        SELECT 
            s.STUDENT_SERIAL_NUMBER,
            s.STUDENT_FIRST_NAME_EN,
            s.STUDENT_LAST_NAME_EN,
            s.STUDNET_GRADE,
            s.SECTION_ID,
            s.CATEGORY_ID,
            sec.SECTION_NAME,
            cat.CATEGORY_NAME
        FROM student s
        LEFT JOIN section sec ON s.SECTION_ID = sec.SECTION_ID
        LEFT JOIN category cat ON s.CATEGORY_ID = cat.CATEGORY_ID
        WHERE s.STUDENT_SERIAL_NUMBER = ?
    ";

    $stmt = $conn->prepare($studentQuery);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt->bind_param("s", $serial_number);
    $stmt->execute();
    $studentResult = $stmt->get_result();

    if ($studentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        $conn->close();
        exit;
    }

    $student = $studentResult->fetch_assoc();
    $stmt->close();

    // 2. Get absences for the student within the date range
    $absencesQuery = "
        SELECT 
            a.ABSENCE_ID,
            a.ABSENCE_DATE_AND_TIME,
            a.ABSENCE_MOTIF,
            a.ABSENCE_OBSERVATION,
            ss.STUDY_SESSION_DATE
        FROM absence a
        JOIN study_session ss ON a.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
        JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
        WHERE sga.STUDENT_SERIAL_NUMBER = ?
        AND DATE(a.ABSENCE_DATE_AND_TIME) >= ?
        AND DATE(a.ABSENCE_DATE_AND_TIME) <= ?
        ORDER BY a.ABSENCE_DATE_AND_TIME DESC
    ";

    $stmt = $conn->prepare($absencesQuery);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $absencesResult = $stmt->get_result();

    $absences = [];
    while ($row = $absencesResult->fetch_assoc()) {
        $absences[] = [
            'absence_id' => $row['ABSENCE_ID'],
            'absence_date_and_time' => $row['ABSENCE_DATE_AND_TIME'],
            'absence_motif' => $row['ABSENCE_MOTIF'],
            'absence_observation' => $row['ABSENCE_OBSERVATION'],
            'study_session_date' => $row['STUDY_SESSION_DATE']
        ];
    }
    $stmt->close();

    // 3. Get observations for the student within the date range
    $observationsQuery = "
        SELECT 
            tmao.OBSERVATION_ID,
            tmao.OBSERVATION_DATE_AND_TIME,
            tmao.OBSERVATION_MOTIF,
            tmao.OBSERVATION_NOTE,
            t.TEACHER_FIRST_NAME,
            t.TEACHER_LAST_NAME,
            ss.STUDY_SESSION_DATE
        FROM teacher_makes_an_observation_for_a_student tmao
        JOIN teacher t ON tmao.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
        JOIN study_session ss ON tmao.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
        WHERE tmao.STUDENT_SERIAL_NUMBER = ?
        AND DATE(tmao.OBSERVATION_DATE_AND_TIME) >= ?
        AND DATE(tmao.OBSERVATION_DATE_AND_TIME) <= ?
        ORDER BY tmao.OBSERVATION_DATE_AND_TIME DESC
    ";

    $stmt = $conn->prepare($observationsQuery);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $observationsResult = $stmt->get_result();

    $observations = [];
    while ($row = $observationsResult->fetch_assoc()) {
        $observations[] = [
            'observation_id' => $row['OBSERVATION_ID'],
            'observation_date_and_time' => $row['OBSERVATION_DATE_AND_TIME'],
            'observation_motif' => $row['OBSERVATION_MOTIF'],
            'observation_note' => $row['OBSERVATION_NOTE'],
            'teacher_name' => htmlspecialchars($row['TEACHER_FIRST_NAME'] . ' ' . $row['TEACHER_LAST_NAME']),
            'study_session_date' => $row['STUDY_SESSION_DATE']
        ];
    }
    $stmt->close();

    // Return results
    echo json_encode([
        'success' => true,
        'student' => [
            'serial_number' => htmlspecialchars($student['STUDENT_SERIAL_NUMBER']),
            'first_name' => htmlspecialchars($student['STUDENT_FIRST_NAME_EN']),
            'last_name' => htmlspecialchars($student['STUDENT_LAST_NAME_EN']),
            'grade' => htmlspecialchars($student['STUDNET_GRADE'] ?? ''),
            'section_name' => htmlspecialchars($student['SECTION_NAME'] ?? ''),
            'category_name' => htmlspecialchars($student['CATEGORY_NAME'] ?? '')
        ],
        'absences' => $absences,
        'observations' => $observations
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
