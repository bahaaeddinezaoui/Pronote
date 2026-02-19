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
$password_db = "08212001";
$dbname = "edutrack";

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
        c.CLASS_NAME_EN,
        c.CLASS_NAME_AR,
        t.TEACHER_FIRST_NAME_EN,
        t.TEACHER_LAST_NAME_EN,
        t.TEACHER_FIRST_NAME_AR,
        t.TEACHER_LAST_NAME_AR
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

$lang = $_SESSION['lang'] ?? 'en';

while ($row = $result->fetch_assoc()) {
    $session_id = $row['STUDY_SESSION_ID'];
    
    // Get sections for this session
    $sections = [];
    $stmtSections = $conn->prepare("
        SELECT DISTINCT s.SECTION_NAME_EN, s.SECTION_NAME_AR
        FROM studies_in si
        INNER JOIN section s ON si.SECTION_ID = s.SECTION_ID
        WHERE si.STUDY_SESSION_ID = ?
    ");
    $stmtSections->bind_param('i', $session_id);
    $stmtSections->execute();
    $resSections = $stmtSections->get_result();
    while ($sec = $resSections->fetch_assoc()) {
        $sectionName = ($lang === 'ar' && !empty($sec['SECTION_NAME_AR'])) ? $sec['SECTION_NAME_AR'] : $sec['SECTION_NAME_EN'];
        $sections[] = $sectionName;
    }
    $stmtSections->close();
    
    // Get absences for this session
    $absences = [];
    $motif_col_abs = ($lang === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";

    $stmtAbs = $conn->prepare("
        SELECT 
            a.ABSENCE_DATE_AND_TIME,
            $motif_col_abs AS ABSENCE_MOTIF,
            a.ABSENCE_OBSERVATION,
            st.STUDENT_FIRST_NAME_EN,
            st.STUDENT_LAST_NAME_EN,
            st.STUDENT_FIRST_NAME_AR,
            st.STUDENT_LAST_NAME_AR
        FROM absence a
        INNER JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
        INNER JOIN student st ON sga.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
        LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
        WHERE a.STUDY_SESSION_ID = ?
        ORDER BY a.ABSENCE_DATE_AND_TIME
    ");
    $stmtAbs->bind_param('i', $session_id);
    $stmtAbs->execute();
    $resAbs = $stmtAbs->get_result();
    while ($abs = $resAbs->fetch_assoc()) {
        $firstName = ($lang === 'ar' && !empty($abs['STUDENT_FIRST_NAME_AR'])) ? $abs['STUDENT_FIRST_NAME_AR'] : $abs['STUDENT_FIRST_NAME_EN'];
        $lastName = ($lang === 'ar' && !empty($abs['STUDENT_LAST_NAME_AR'])) ? $abs['STUDENT_LAST_NAME_AR'] : $abs['STUDENT_LAST_NAME_EN'];
        $absences[] = [
            'student_name' => $firstName . ' ' . $lastName,
            'absence_time' => date('H:i', strtotime($abs['ABSENCE_DATE_AND_TIME'])),
            'motif' => $abs['ABSENCE_MOTIF'] ?? '',
            'observation' => $abs['ABSENCE_OBSERVATION'] ?? ''
        ];
    }
    $stmtAbs->close();
    
    // Get observations for this session, checking read status for THIS admin
    $observations = [];
    $motif_col_obs = ($lang === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";

    $stmtObs = $conn->prepare("
        SELECT 
            tmo.OBSERVATION_ID,
            tmo.OBSERVATION_DATE_AND_TIME,
            $motif_col_obs AS OBSERVATION_MOTIF,
            tmo.OBSERVATION_NOTE,
            (CASE WHEN aro.OBSERVATION_ID IS NULL THEN 1 ELSE 0 END) as IS_NEW_FOR_ADMIN,
            st.STUDENT_FIRST_NAME_EN,
            st.STUDENT_LAST_NAME_EN,
            st.STUDENT_FIRST_NAME_AR,
            st.STUDENT_LAST_NAME_AR,
            t.TEACHER_FIRST_NAME_EN,
            t.TEACHER_LAST_NAME_EN,
            t.TEACHER_FIRST_NAME_AR,
            t.TEACHER_LAST_NAME_AR
        FROM teacher_makes_an_observation_for_a_student tmo
        INNER JOIN student st ON tmo.STUDENT_SERIAL_NUMBER = st.STUDENT_SERIAL_NUMBER
        INNER JOIN teacher t ON tmo.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
        LEFT JOIN observation_motif om ON tmo.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID
        LEFT JOIN admin_read_observation aro ON tmo.OBSERVATION_ID = aro.OBSERVATION_ID 
            AND aro.ADMINISTRATOR_ID = (SELECT ADMINISTRATOR_ID FROM administrator WHERE USER_ID = ?)
        WHERE tmo.STUDY_SESSION_ID = ?
        ORDER BY tmo.OBSERVATION_DATE_AND_TIME
    ");
    $stmtObs->bind_param('ii', $_SESSION['user_id'], $session_id);
    $stmtObs->execute();
    $resObs = $stmtObs->get_result();
    while ($obs = $resObs->fetch_assoc()) {
        $studentFirstName = ($lang === 'ar' && !empty($obs['STUDENT_FIRST_NAME_AR'])) ? $obs['STUDENT_FIRST_NAME_AR'] : $obs['STUDENT_FIRST_NAME_EN'];
        $studentLastName = ($lang === 'ar' && !empty($obs['STUDENT_LAST_NAME_AR'])) ? $obs['STUDENT_LAST_NAME_AR'] : $obs['STUDENT_LAST_NAME_EN'];
        $teacherFirstName = ($lang === 'ar' && !empty($obs['TEACHER_FIRST_NAME_AR'])) ? $obs['TEACHER_FIRST_NAME_AR'] : $obs['TEACHER_FIRST_NAME_EN'];
        $teacherLastName = ($lang === 'ar' && !empty($obs['TEACHER_LAST_NAME_AR'])) ? $obs['TEACHER_LAST_NAME_AR'] : $obs['TEACHER_LAST_NAME_EN'];
        $observations[] = [
            'observation_id' => $obs['OBSERVATION_ID'],
            'student_name' => $studentFirstName . ' ' . $studentLastName,
            'teacher_name' => $teacherFirstName . ' ' . $teacherLastName,
            'observation_time' => date('H:i', strtotime($obs['OBSERVATION_DATE_AND_TIME'])),
            'motif' => $obs['OBSERVATION_MOTIF'] ?? '',
            'note' => $obs['OBSERVATION_NOTE'] ?? '',
            'is_new_for_admin' => (int)$obs['IS_NEW_FOR_ADMIN']
        ];
    }
    $stmtObs->close();
    
    $className = ($lang === 'ar' && !empty($row['CLASS_NAME_AR'])) ? $row['CLASS_NAME_AR'] : $row['CLASS_NAME_EN'];
    $teacherFirstName = ($lang === 'ar' && !empty($row['TEACHER_FIRST_NAME_AR'])) ? $row['TEACHER_FIRST_NAME_AR'] : $row['TEACHER_FIRST_NAME_EN'];
    $teacherLastName = ($lang === 'ar' && !empty($row['TEACHER_LAST_NAME_AR'])) ? $row['TEACHER_LAST_NAME_AR'] : $row['TEACHER_LAST_NAME_EN'];
    
    $sessions[] = [
        'session_id' => $session_id,
        'session_date' => date('d/m/Y', strtotime($row['STUDY_SESSION_DATE'])),
        'start_time' => substr($row['STUDY_SESSION_START_TIME'], 0, 5),
        'end_time' => substr($row['STUDY_SESSION_END_TIME'], 0, 5),
        'class_name' => $className,
        'teacher_name' => $teacherFirstName . ' ' . $teacherLastName,
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