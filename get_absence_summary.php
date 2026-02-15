<?php
// get_absence_summary.php - Get absence summary for a specific date and time slot
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
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$date = isset($_GET['date']) ? $_GET['date'] : null;
$time_slot = isset($_GET['time_slot']) ? $_GET['time_slot'] : null;

if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    $conn->close();
    exit;
}

$absences = [];

try {
    // Build the query to get absences for the specified date
    $lang = $_SESSION['lang'] ?? 'en';
    $motif_col = ($lang === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";

    if ($time_slot) {
        // Parse time slot (format: "start|end")
        $timeSlotParts = explode('|', $time_slot);
        if (count($timeSlotParts) === 2) {
            $startTime = $timeSlotParts[0];
            $endTime = $timeSlotParts[1];

            // Get absences within the time slot
            $query = "
                SELECT 
                    s.STUDENT_FIRST_NAME_EN,
                    s.STUDENT_LAST_NAME_EN,
                    a.ABSENCE_DATE_AND_TIME,
                    $motif_col AS ABSENCE_MOTIF,
                    a.ABSENCE_OBSERVATION,
                    ss.STUDY_SESSION_DATE
                FROM absence a
                JOIN study_session ss ON a.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
                JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
                JOIN student s ON sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
                LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
                WHERE ss.STUDY_SESSION_DATE = ?
                AND ss.STUDY_SESSION_START_TIME >= ?
                AND ss.STUDY_SESSION_END_TIME <= ?
                ORDER BY a.ABSENCE_DATE_AND_TIME DESC
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $date, $startTime, $endTime);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid time slot format']);
            $conn->close();
            exit;
        }
    } else {
        // Get all absences for the date
        $query = "
            SELECT 
                s.STUDENT_FIRST_NAME_EN,
                s.STUDENT_LAST_NAME_EN,
                a.ABSENCE_DATE_AND_TIME,
                $motif_col AS ABSENCE_MOTIF,
                a.ABSENCE_OBSERVATION,
                ss.STUDY_SESSION_DATE
            FROM absence a
            JOIN study_session ss ON a.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
            JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
            JOIN student s ON sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
            LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
            WHERE ss.STUDY_SESSION_DATE = ?
            ORDER BY a.ABSENCE_DATE_AND_TIME DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $absences[] = [
            'student_name' => htmlspecialchars($row['STUDENT_FIRST_NAME_EN'] . ' ' . $row['STUDENT_LAST_NAME_EN']),
            'absence_date' => date('d/m/Y', strtotime($row['STUDY_SESSION_DATE'])),
            'absence_time' => date('H:i', strtotime($row['ABSENCE_DATE_AND_TIME'])),
            'motif' => htmlspecialchars($row['ABSENCE_MOTIF'] ?: 'No motif'),
            'observation' => htmlspecialchars($row['ABSENCE_OBSERVATION'] ?: ''),
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'absences' => $absences,
        'count' => count($absences)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
