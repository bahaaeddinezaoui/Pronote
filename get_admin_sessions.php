<?php
// get_admin_sessions.php - API endpoint to fetch sessions for admin
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

// Get parameters
$date = $_GET['date'] ?? '';
$time_slot = $_GET['time_slot'] ?? '';
$session_number = $_GET['number'] ?? ''; // Added support for session ID

if (empty($date) && empty($session_number)) {
    echo json_encode(['success' => false, 'message' => 'Date or session number is required']);
    exit;
}

// Build query
$sql = "
    SELECT 
        ss.STUDY_SESSION_ID,
        ss.STUDY_SESSION_DATE,
        ss.STUDY_SESSION_START_TIME,
        ss.STUDY_SESSION_END_TIME,
        ss.CLASS_ID,
        c.CLASS_NAME,
        t.TEACHER_FIRST_NAME,
        t.TEACHER_LAST_NAME
    FROM study_session ss
    LEFT JOIN class c ON ss.CLASS_ID = c.CLASS_ID
    LEFT JOIN teacher t ON ss.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    WHERE 1=1
";

$params = [];
$types = '';

if (!empty($session_number)) {
    $sql .= " AND ss.STUDY_SESSION_ID = ?";
    $params[] = $session_number;
    $types .= 'i';
} else {
    // Standard filter by date
    $sql .= " AND ss.STUDY_SESSION_DATE = ?";
    $params[] = $date;
    $types .= 's';

    // Parse time slot if provided
    if (!empty($time_slot) && strpos($time_slot, '|') !== false) {
        list($start_time, $end_time) = explode('|', $time_slot);
        $sql .= " AND ss.STUDY_SESSION_START_TIME = ? AND ss.STUDY_SESSION_END_TIME = ?";
        $params[] = $start_time;
        $params[] = $end_time;
        $types .= 'ss';
    }
}

$sql .= " ORDER BY ss.STUDY_SESSION_START_TIME, ss.STUDY_SESSION_ID";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];

while ($row = $result->fetch_assoc()) {
    $session_id = $row['STUDY_SESSION_ID'];
    
    // Get sections for this session
    $sections = [];
    $stmtSections = $conn->prepare("
        SELECT DISTINCT s.SECTION_NAME
        FROM studies_in si
        INNER JOIN section s ON si.SECTION_ID = s.SECTION_ID
        WHERE si.STUDY_SESSION_ID = ?
    ");
    $stmtSections->bind_param('i', $session_id);
    $stmtSections->execute();
    $resSections = $stmtSections->get_result();
    while ($sec = $resSections->fetch_assoc()) {
        $sections[] = $sec['SECTION_NAME'];
    }
    $stmtSections->close();
    
    // Get absences for this session
    $absences = [];
    $stmtAbs = $conn->prepare("
        SELECT 
            a.ABSENCE_DATE_AND_TIME,
            a.ABSENCE_MOTIF,
            a.ABSENCE_OBSERVATION,
            st.STUDENT_FIRST_NAME_EN,
            st.STUDENT_LAST_NAME_EN
        FROM absence a
        INNER JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
        INNER JOIN student st ON sga.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
        WHERE a.STUDY_SESSION_ID = ?
        ORDER BY a.ABSENCE_DATE_AND_TIME
    ");
    $stmtAbs->bind_param('i', $session_id);
    $stmtAbs->execute();
    $resAbs = $stmtAbs->get_result();
    while ($abs = $resAbs->fetch_assoc()) {
        $absences[] = [
            'student_name' => $abs['STUDENT_FIRST_NAME_EN'] . ' ' . $abs['STUDENT_LAST_NAME_EN'],
            'absence_time' => date('H:i', strtotime($abs['ABSENCE_DATE_AND_TIME'])),
            'motif' => $abs['ABSENCE_MOTIF'] ?? '',
            'observation' => $abs['ABSENCE_OBSERVATION'] ?? ''
        ];
    }
    $stmtAbs->close();
    
    // Get observations for this session
    $observations = [];
    // Get observations for this session, checking read status for THIS admin
    $observations = [];
    $stmtObs = $conn->prepare("
        SELECT 
            tmo.OBSERVATION_ID,
            tmo.OBSERVATION_DATE_AND_TIME,
            tmo.OBSERVATION_MOTIF,
            tmo.OBSERVATION_NOTE,
            (CASE WHEN aro.OBSERVATION_ID IS NULL THEN 1 ELSE 0 END) as IS_NEW_FOR_ADMIN,
            st.STUDENT_FIRST_NAME_EN,
            st.STUDENT_LAST_NAME_EN,
            t.TEACHER_FIRST_NAME,
            t.TEACHER_LAST_NAME
        FROM teacher_makes_an_observation_for_a_student tmo
        INNER JOIN student st ON tmo.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
        INNER JOIN teacher t ON tmo.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
        LEFT JOIN admin_read_observation aro ON tmo.OBSERVATION_ID = aro.OBSERVATION_ID 
            AND aro.ADMINISTRATOR_ID = (SELECT ADMINISTRATOR_ID FROM administrator WHERE USER_ID = ?)
        WHERE tmo.STUDY_SESSION_ID = ?
        ORDER BY tmo.OBSERVATION_DATE_AND_TIME
    ");
    $stmtObs->bind_param('ii', $_SESSION['user_id'], $session_id);
    $stmtObs->execute();
    $resObs = $stmtObs->get_result();
    while ($obs = $resObs->fetch_assoc()) {
        $observations[] = [
            'observation_id' => $obs['OBSERVATION_ID'],
            'student_name' => $obs['STUDENT_FIRST_NAME_EN'] . ' ' . $obs['STUDENT_LAST_NAME_EN'],
            'teacher_name' => $obs['TEACHER_FIRST_NAME'] . ' ' . $obs['TEACHER_LAST_NAME'],
            'observation_time' => date('H:i', strtotime($obs['OBSERVATION_DATE_AND_TIME'])),
            'motif' => $obs['OBSERVATION_MOTIF'] ?? '',
            'note' => $obs['OBSERVATION_NOTE'] ?? '',
            'is_new_for_admin' => (int)$obs['IS_NEW_FOR_ADMIN']
        ];
    }
    $stmtObs->close();
    
    $sessions[] = [
        'session_id' => $session_id,
        'session_date' => date('d/m/Y', strtotime($row['STUDY_SESSION_DATE'])),
        'start_time' => substr($row['STUDY_SESSION_START_TIME'], 0, 5),
        'end_time' => substr($row['STUDY_SESSION_END_TIME'], 0, 5),
        'class_name' => $row['CLASS_NAME'],
        'teacher_name' => $row['TEACHER_FIRST_NAME'] . ' ' . $row['TEACHER_LAST_NAME'],
        'sections' => $sections,
        'absences' => $absences,
        'observations' => $observations
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'sessions' => $sessions
]);
?>