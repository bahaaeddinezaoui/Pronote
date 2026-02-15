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
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch obs where NOT EXISTS in admin_read_observation for this admin
$lang = $_SESSION['lang'] ?? 'en';
$motif_col = ($lang === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";

$sql = "
    SELECT 
        tmo.OBSERVATION_ID,
        tmo.OBSERVATION_DATE_AND_TIME,
        $motif_col AS OBSERVATION_MOTIF,
        tmo.STUDY_SESSION_ID,
        st.STUDENT_FIRST_NAME_EN,
        st.STUDENT_LAST_NAME_EN,
        t.TEACHER_FIRST_NAME_EN,
        t.TEACHER_LAST_NAME_EN,
        ss.STUDY_SESSION_DATE,
        ss.STUDY_SESSION_START_TIME,
        ss.STUDY_SESSION_END_TIME
    FROM teacher_makes_an_observation_for_a_student tmo
    INNER JOIN student st ON tmo.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
    INNER JOIN teacher t ON tmo.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    INNER JOIN study_session ss ON tmo.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    LEFT JOIN observation_motif om ON tmo.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID
    WHERE NOT EXISTS (
        SELECT 1 
        FROM admin_read_observation aro 
        WHERE aro.OBSERVATION_ID = tmo.OBSERVATION_ID 
        AND aro.ADMINISTRATOR_ID = (SELECT ADMINISTRATOR_ID FROM administrator WHERE USER_ID = ?)
    )
    ORDER BY tmo.OBSERVATION_DATE_AND_TIME DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'observation_id' => $row['OBSERVATION_ID'],
            'student_name' => $row['STUDENT_FIRST_NAME_EN'] . ' ' . $row['STUDENT_LAST_NAME_EN'],
            'teacher_name' => $row['TEACHER_FIRST_NAME_EN'] . ' ' . $row['TEACHER_LAST_NAME_EN'],
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
