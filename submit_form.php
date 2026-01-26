<?php
// --- SESSION & CONFIG ---
session_start();
date_default_timezone_set('Africa/Algiers');

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

// --- RETRIEVE TEACHER INFO ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    die(json_encode(["success" => false, "message" => "Teacher not logged in."]));
}

// Look up the teacher serial number from the `teacher` table using the logged-in user id
$teacher_serial = null;
$stmtTeacher = $conn->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
if (!$stmtTeacher) {
    die(json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]));
}
$stmtTeacher->bind_param("i", $_SESSION['user_id']);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
if ($resTeacher && $resTeacher->num_rows > 0) {
    $teacher_serial = $resTeacher->fetch_assoc()['TEACHER_SERIAL_NUMBER'];
} else {
    die(json_encode(["success" => false, "message" => "Teacher record not found for this account."]));
}
$stmtTeacher->close();

// --- RETRIEVE FORM DATA ---
$class_id = $_POST['class_id'] ?? null;

$sections = $_POST['sections'] ?? [];
if (!is_array($sections)) $sections = [$sections];

$session_date = $_POST['session_date'] ?? date('Y-m-d');

$motif_ids = $_POST['motif_id'] ?? [];
if (!is_array($motif_ids)) $motif_ids = [$motif_ids];

$observations = $_POST['observation'] ?? [];
if (!is_array($observations)) $observations = [$observations];

$first_names = $_POST['first_name'] ?? [];
if (!is_array($first_names)) $first_names = [$first_names];

$last_names = $_POST['last_name'] ?? [];
if (!is_array($last_names)) $last_names = [$last_names];

// --- HANDLE TIME SLOT FIELD ---
$time_slot = $_POST['time_slot'] ?? '';
$start_time = null;
$end_time = null;

if (!empty($time_slot) && strpos($time_slot, ' - ') !== false) {
    list($start_time, $end_time) = array_map('trim', explode(' - ', $time_slot));
}

// --- VALIDATE REQUIRED FIELDS ---
if (empty($class_id)) {
    die(json_encode(["success" => false, "message" => "Class selection is required."]));
}
if (empty($sections)) {
    die(json_encode(["success" => false, "message" => "Please select at least one section."]));
}
if (!$start_time || !$end_time) {
    die(json_encode(["success" => false, "message" => "Start and end times are required."]));
}

// --- HELPER FUNCTION ---
function nextId($conn, $table, $column)
{
    $result = $conn->query("SELECT MAX($column) AS max_id FROM $table");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}

// --- BEGIN TRANSACTION (manual rollback) ---
$conn->autocommit(false);

try {
    // 1️⃣ Check for existing session
    $stmtCheck = $conn->prepare("SELECT STUDY_SESSION_ID FROM study_session WHERE TEACHER_SERIAL_NUMBER = ? AND STUDY_SESSION_DATE = ? AND STUDY_SESSION_START_TIME = ? AND STUDY_SESSION_END_TIME = ?");
    $stmtCheck->bind_param("ssss", $teacher_serial, $session_date, $start_time, $end_time);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        // Session already exists
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "A session already exists for this time slot. You can only record observations."]);
        exit;
    }
    $stmtCheck->close();

    // 2️⃣ Insert into study_session
    $session_id = nextId($conn, 'study_session', 'STUDY_SESSION_ID');
    $stmt = $conn->prepare("INSERT INTO study_session 
        (STUDY_SESSION_ID, CLASS_ID, TEACHER_SERIAL_NUMBER, STUDY_SESSION_DATE, STUDY_SESSION_START_TIME, STUDY_SESSION_END_TIME)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $session_id, $class_id, $teacher_serial, $session_date, $start_time, $end_time);
    $stmt->execute();

    // Store the current study session ID in the PHP session
    $_SESSION['current_study_session_id'] = $session_id;

    // 3️⃣ Link sections to session (studies_in) - ✅ FIX: Create new statement for each iteration
    foreach ($sections as $section_id) {
        $stmtSection = $conn->prepare("INSERT INTO studies_in (SECTION_ID, STUDY_SESSION_ID) VALUES (?, ?)");
        if (!$stmtSection) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmtSection->bind_param("ii", $section_id, $session_id);
        if (!$stmtSection->execute()) {
            throw new Exception("Execute failed: " . $stmtSection->error);
        }
        $stmtSection->close();
    }

    // 4️⃣ Insert absences
    $stmtAbs = $conn->prepare("INSERT INTO absence 
        (ABSENCE_ID, STUDY_SESSION_ID, ABSENCE_DATE_AND_TIME, ABSENCE_MOTIF_ID, ABSENCE_OBSERVATION)
        VALUES (?, ?, ?, ?, ?)");
    $stmtStudentAbs = $conn->prepare("INSERT INTO student_gets_absent 
        (STUDENT_SERIAL_NUMBER, ABSENCE_ID) VALUES (?, ?)");

    $absence_date = date('Y-m-d H:i:s');

    for ($i = 0; $i < count($first_names); $i++) {
        $f = trim($first_names[$i]);
        $l = trim($last_names[$i]);
        if ($f === '' || $l === '') continue;

        // Find student serial number
        $stmtFind = $conn->prepare("SELECT STUDENT_SERIAL_NUMBER FROM student WHERE STUDENT_FIRST_NAME_EN = ? AND STUDENT_LAST_NAME_EN = ?");
        $stmtFind->bind_param("ss", $f, $l);
        $stmtFind->execute();
        $result = $stmtFind->get_result();
        if ($result->num_rows === 0) continue;
        $student = $result->fetch_assoc();
        $student_serial = $student['STUDENT_SERIAL_NUMBER'];
        $stmtFind->close();

        // New absence ID
        $absence_id = nextId($conn, 'absence', 'ABSENCE_ID');
        $motif_id = (int)($motif_ids[$i] ?? 0);
        $obs = $observations[$i] ?? '';

        // Insert absence
        $stmtAbs->bind_param("iisis", $absence_id, $session_id, $absence_date, $motif_id, $obs);
        if (!$stmtAbs->execute()) {
            throw new Exception("Failed to insert absence: " . $stmtAbs->error);
        }

        // Link student <-> absence
        $stmtStudentAbs->bind_param("si", $student_serial, $absence_id);
        if (!$stmtStudentAbs->execute()) {
            throw new Exception("Failed to link student to absence: " . $stmtStudentAbs->error);
        }
    }

    $stmtAbs->close();
    $stmtStudentAbs->close();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Form submitted successfully!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->autocommit(true);
$conn->close();
?>
