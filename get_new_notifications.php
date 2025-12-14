<?php
// get_new_notifications.php - Fetch unread observations for admin
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch obs where IS_NEW_FOR_ADMIN = 1
$sql = "
    SELECT 
        tmo.OBSERVATION_ID,
        tmo.OBSERVATION_DATE_AND_TIME,
        tmo.OBSERVATION_MOTIF,
        tmo.STUDY_SESSION_ID,
        st.STUDENT_FIRST_NAME,
        st.STUDENT_LAST_NAME,
        t.TEACHER_FIRST_NAME,
        t.TEACHER_LAST_NAME,
        ss.STUDY_SESSION_DATE,
        ss.STUDY_SESSION_START_TIME,
        ss.STUDY_SESSION_END_TIME
    FROM teacher_makes_an_observation_for_a_student tmo
    INNER JOIN student st ON tmo.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
    INNER JOIN teacher t ON tmo.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    INNER JOIN study_session ss ON tmo.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    WHERE tmo.IS_NEW_FOR_ADMIN = 1
    ORDER BY tmo.OBSERVATION_DATE_AND_TIME DESC
";

$result = $conn->query($sql);

$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'observation_id' => $row['OBSERVATION_ID'],
            'student_name' => $row['STUDENT_FIRST_NAME'] . ' ' . $row['STUDENT_LAST_NAME'],
            'teacher_name' => $row['TEACHER_FIRST_NAME'] . ' ' . $row['TEACHER_LAST_NAME'],
            'observation_time' => date('d/m/Y H:i', strtotime($row['OBSERVATION_DATE_AND_TIME'])),
            'motif' => $row['OBSERVATION_MOTIF'],
            'session_id' => $row['STUDY_SESSION_ID'],
            'session_date' => date('d/m/Y', strtotime($row['STUDY_SESSION_DATE'])),
            'session_time' => substr($row['STUDY_SESSION_START_TIME'], 0, 5) // HH:MM
        ];
    }
}

$conn->close();

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
