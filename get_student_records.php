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
    // 1. Get student basic information (Comprehensive)
    $studentQuery = "
        SELECT 
            s.STUDENT_SERIAL_NUMBER,
            s.STUDENT_FIRST_NAME_EN, s.STUDENT_LAST_NAME_EN,
            s.STUDENT_FIRST_NAME_AR, s.STUDENT_LAST_NAME_AR,
            s.STUDENT_SEX, s.STUDENT_BIRTH_DATE, s.STUDENT_BLOOD_TYPE,
            s.STUDENT_PERSONAL_PHONE,
            s.STUDENT_HEIGHT_CM, s.STUDENT_WEIGHT_KG,
            s.STUDENT_IS_FOREIGN,
            s.STUDENT_ACADEMIC_AVERAGE, s.STUDENT_SPECIALITY,
            s.STUDENT_ACADEMIC_LEVEL, s.STUDENT_BACCALAUREATE_SUB_NUMBER,
            s.STUDENT_EDUCATIONAL_CERTIFICATES, s.STUDENT_MILITARY_CERTIFICATES,
            s.STUDENT_SCHOOL_SUB_DATE, s.STUDENT_SCHOOL_SUB_CARD_NUMBER,
            s.STUDENT_LAPTOP_SERIAL_NUMBER,
            s.STUDENT_BIRTHDATE_CERTIFICATE_NUMBER, s.STUDENT_ID_CARD_NUMBER,
            s.STUDENT_POSTAL_ACCOUNT_NUMBER,
            s.STUDENT_HOBBIES, s.STUDENT_HEALTH_STATUS,
            s.STUDENT_MILITARY_NECKLACE,
            s.STUDENT_NUMBER_OF_SIBLINGS, s.STUDENT_NUMBER_OF_SISTERS,
            s.STUDENT_ORDER_AMONG_SIBLINGS,
            s.STUDENT_ORPHAN_STATUS, s.STUDENT_PARENTS_SITUATION,
            
            g.GRADE_NAME,
            sec.SECTION_NAME,
            cat.CATEGORY_NAME,
            a.ARMY_NAME,

            -- Parent Info
            spi.FATHER_FIRST_NAME_EN, spi.FATHER_LAST_NAME_EN, spi.FATHER_PROFESSION_EN,
            spi.FATHER_FIRST_NAME_AR, spi.FATHER_LAST_NAME_AR, spi.FATHER_PROFESSION_AR,
            spi.MOTHER_FIRST_NAME_EN, spi.MOTHER_LAST_NAME_EN, spi.MOTHER_PROFESSION_EN,
            spi.MOTHER_FIRST_NAME_AR, spi.MOTHER_LAST_NAME_AR, spi.MOTHER_PROFESSION_AR,

            -- Combat Outfit
            sco.FIRST_OUTFIT_NUMBER, sco.FIRST_OUTFIT_SIZE,
            sco.SECOND_OUTFIT_NUMBER, sco.SECOND_OUTFIT_SIZE,
            sco.COMBAT_SHOE_SIZE,

            -- Parade Uniform
            spu.SUMMER_JACKET_SIZE, spu.WINTER_JACKET_SIZE,
            spu.SUMMER_TROUSERS_SIZE, spu.WINTER_TROUSERS_SIZE,
            spu.SUMMER_SHIRT_SIZE, spu.WINTER_SHIRT_SIZE,
            spu.SUMMER_HAT_SIZE, spu.WINTER_HAT_SIZE,
            spu.SUMMER_SKIRT_SIZE, spu.WINTER_SKIRT_SIZE,

            -- Birth Place Address
            addr_bp.ADDRESS_STREET_EN AS BP_STREET,
            c_bp.COUNTRY_NAME_EN AS BP_COUNTRY,
            w_bp.WILAYA_NAME_EN AS BP_WILAYA,

            -- Personal Address
            addr_p.ADDRESS_STREET_EN AS PERS_STREET,
            c_p.COUNTRY_NAME_EN AS PERS_COUNTRY,
            w_p.WILAYA_NAME_EN AS PERS_WILAYA

        FROM student s
        LEFT JOIN section sec ON s.SECTION_ID = sec.SECTION_ID
        LEFT JOIN category cat ON s.CATEGORY_ID = cat.CATEGORY_ID
        LEFT JOIN grade g ON s.STUDENT_GRADE_ID = g.GRADE_ID
        LEFT JOIN army a ON s.STUDENT_ARMY_ID = a.ARMY_ID
        
        LEFT JOIN student_parent_info spi ON s.STUDENT_SERIAL_NUMBER = spi.STUDENT_SERIAL_NUMBER
        LEFT JOIN student_combat_outfit sco ON s.STUDENT_SERIAL_NUMBER = sco.STUDENT_SERIAL_NUMBER
        LEFT JOIN student_parade_uniform spu ON s.STUDENT_SERIAL_NUMBER = spu.STUDENT_SERIAL_NUMBER

        -- Address Joins
        LEFT JOIN address addr_bp ON s.STUDENT_BIRTH_PLACE_ID = addr_bp.ADDRESS_ID
        LEFT JOIN country c_bp ON addr_bp.COUNTRY_ID = c_bp.COUNTRY_ID
        LEFT JOIN wilaya w_bp ON addr_bp.WILAYA_ID = w_bp.WILAYA_ID

        LEFT JOIN address addr_p ON s.STUDENT_PERSONAL_ADDRESS_ID = addr_p.ADDRESS_ID
        LEFT JOIN country c_p ON addr_p.COUNTRY_ID = c_p.COUNTRY_ID
        LEFT JOIN wilaya w_p ON addr_p.WILAYA_ID = w_p.WILAYA_ID

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
            'first_name_ar' => htmlspecialchars($student['STUDENT_FIRST_NAME_AR'] ?? ''),
            'last_name_ar' => htmlspecialchars($student['STUDENT_LAST_NAME_AR'] ?? ''),
            'sex' => htmlspecialchars($student['STUDENT_SEX'] ?? ''),
            'birth_date' => htmlspecialchars($student['STUDENT_BIRTH_DATE'] ?? ''),
            'blood_type' => htmlspecialchars($student['STUDENT_BLOOD_TYPE'] ?? ''),
            'personal_phone' => htmlspecialchars($student['STUDENT_PERSONAL_PHONE'] ?? ''),
            'height_cm' => htmlspecialchars($student['STUDENT_HEIGHT_CM'] ?? ''),
            'weight_kg' => htmlspecialchars($student['STUDENT_WEIGHT_KG'] ?? ''),
            'is_foreign' => htmlspecialchars($student['STUDENT_IS_FOREIGN'] ?? ''),
            'academic_average' => htmlspecialchars($student['STUDENT_ACADEMIC_AVERAGE'] ?? ''),
            'speciality' => htmlspecialchars($student['STUDENT_SPECIALITY'] ?? ''),
            'academic_level' => htmlspecialchars($student['STUDENT_ACADEMIC_LEVEL'] ?? ''),
            'bac_number' => htmlspecialchars($student['STUDENT_BACCALAUREATE_SUB_NUMBER'] ?? ''),
            'edu_certificates' => htmlspecialchars($student['STUDENT_EDUCATIONAL_CERTIFICATES'] ?? ''),
            'mil_certificates' => htmlspecialchars($student['STUDENT_MILITARY_CERTIFICATES'] ?? ''),
            'school_sub_date' => htmlspecialchars($student['STUDENT_SCHOOL_SUB_DATE'] ?? ''),
            'school_sub_card' => htmlspecialchars($student['STUDENT_SCHOOL_SUB_CARD_NUMBER'] ?? ''),
            'laptop_serial' => htmlspecialchars($student['STUDENT_LAPTOP_SERIAL_NUMBER'] ?? ''),
            'birth_cert_num' => htmlspecialchars($student['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER'] ?? ''),
            'id_card_num' => htmlspecialchars($student['STUDENT_ID_CARD_NUMBER'] ?? ''),
            'postal_account' => htmlspecialchars($student['STUDENT_POSTAL_ACCOUNT_NUMBER'] ?? ''),
            'hobbies' => htmlspecialchars($student['STUDENT_HOBBIES'] ?? ''),
            'health_status' => htmlspecialchars($student['STUDENT_HEALTH_STATUS'] ?? ''),
            'mil_necklace' => htmlspecialchars($student['STUDENT_MILITARY_NECKLACE'] ?? ''),
            
            'grade' => htmlspecialchars($student['GRADE_NAME'] ?? ''),
            'section_name' => htmlspecialchars($student['SECTION_NAME'] ?? ''),
            'category_name' => htmlspecialchars($student['CATEGORY_NAME'] ?? ''),
            'army_name' => htmlspecialchars($student['ARMY_NAME'] ?? ''),

            // Parents
            'father_name_en' => htmlspecialchars(($student['FATHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['FATHER_LAST_NAME_EN'] ?? '')),
            'father_name_ar' => htmlspecialchars(($student['FATHER_FIRST_NAME_AR'] ?? '') . ' ' . ($student['FATHER_LAST_NAME_AR'] ?? '')),
            'mother_name_en' => htmlspecialchars(($student['MOTHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['MOTHER_LAST_NAME_EN'] ?? '')),
            'mother_name_ar' => htmlspecialchars(($student['MOTHER_FIRST_NAME_AR'] ?? '') . ' ' . ($student['MOTHER_LAST_NAME_AR'] ?? '')),
            'father_profession' => htmlspecialchars($student['FATHER_PROFESSION_EN'] ?? ''),
            'father_profession_ar' => htmlspecialchars($student['FATHER_PROFESSION_AR'] ?? ''),
            'mother_profession' => htmlspecialchars($student['MOTHER_PROFESSION_EN'] ?? ''),
            'mother_profession_ar' => htmlspecialchars($student['MOTHER_PROFESSION_AR'] ?? ''),
            
            'orphans_status' => htmlspecialchars($student['STUDENT_ORPHAN_STATUS'] ?? ''),
            'parents_situation' => htmlspecialchars($student['STUDENT_PARENTS_SITUATION'] ?? ''),
            'siblings_count' => htmlspecialchars($student['STUDENT_NUMBER_OF_SIBLINGS'] ?? ''),
            'sisters_count' => htmlspecialchars($student['STUDENT_NUMBER_OF_SISTERS'] ?? ''),
            'order_among_siblings' => htmlspecialchars($student['STUDENT_ORDER_AMONG_SIBLINGS'] ?? ''),

            // Addresses
            'birth_place' => htmlspecialchars(($student['BP_STREET'] ?? '') . ', ' . ($student['BP_WILAYA'] ?? '') . ', ' . ($student['BP_COUNTRY'] ?? '')),
            'personal_address' => htmlspecialchars(($student['PERS_STREET'] ?? '') . ', ' . ($student['PERS_WILAYA'] ?? '') . ', ' . ($student['PERS_COUNTRY'] ?? '')),

            // Uniforms (sending raw object might be cleaner but flat mapping is safer for sanitized HTML output simplicity)
            'uniforms' => [
                'combat' => [
                    'outfit1' => htmlspecialchars(($student['FIRST_OUTFIT_NUMBER'] ?? '') . ' / ' . ($student['FIRST_OUTFIT_SIZE'] ?? '')),
                    'outfit2' => htmlspecialchars(($student['SECOND_OUTFIT_NUMBER'] ?? '') . ' / ' . ($student['SECOND_OUTFIT_SIZE'] ?? '')),
                    'shoe' => htmlspecialchars($student['COMBAT_SHOE_SIZE'] ?? '')
                ],
                'parade' => [
                    'summer_jacket' => htmlspecialchars($student['SUMMER_JACKET_SIZE'] ?? ''),
                    'winter_jacket' => htmlspecialchars($student['WINTER_JACKET_SIZE'] ?? ''),
                    'summer_trousers' => htmlspecialchars($student['SUMMER_TROUSERS_SIZE'] ?? ''),
                    'winter_trousers' => htmlspecialchars($student['WINTER_TROUSERS_SIZE'] ?? ''),
                    'summer_shirt' => htmlspecialchars($student['SUMMER_SHIRT_SIZE'] ?? ''),
                    'winter_shirt' => htmlspecialchars($student['WINTER_SHIRT_SIZE'] ?? ''),
                    'summer_hat' => htmlspecialchars($student['SUMMER_HAT_SIZE'] ?? ''),
                    'winter_hat' => htmlspecialchars($student['WINTER_HAT_SIZE'] ?? ''),
                    'summer_skirt' => htmlspecialchars($student['SUMMER_SKIRT_SIZE'] ?? ''),
                    'winter_skirt' => htmlspecialchars($student['WINTER_SKIRT_SIZE'] ?? '')
                ]
            ]
        ],
        'absences' => $absences,
        'observations' => $observations
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
