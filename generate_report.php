<?php
// generate_report.php - Generate PDF report for student records
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parameters
$serial_number = isset($_GET['serial_number']) ? $_GET['serial_number'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$serial_number || !$start_date || !$end_date) {
    die("Missing required parameters.");
}

$lang = $_SESSION['lang'] ?? 'en';

// Helper function to escape LaTeX special characters
function latexEscape($text) {
    if ($text === null || $text === '') return 'N/A';
    $text = (string) $text;
    $specialChars = ['\\', '&', '%', '$', '#', '_', '{', '}', '~', '^'];
    $replacements = ['\\textbackslash{}', '\\&', '\\%', '\\$', '\\#', '\\_', '\\{', '\\}', '\\textasciitilde{}', '\\textasciicircum{}'];
    $text = str_replace($specialChars, $replacements, $text);
    return $text;
}

try {
    // 1. Get student full information
    $studentQuery = "
        SELECT 
            s.STUDENT_SERIAL_NUMBER,
            s.STUDENT_FIRST_NAME_EN, s.STUDENT_LAST_NAME_EN,
            s.STUDENT_FIRST_NAME_AR, s.STUDENT_LAST_NAME_AR,
            s.STUDENT_SEX, s.STUDENT_BIRTH_DATE, s.STUDENT_BLOOD_TYPE,
            s.STUDENT_PERSONAL_PHONE,
            s.STUDENT_HEIGHT_CM, s.STUDENT_WEIGHT_KG,
            s.STUDENT_IS_FOREIGN,
            s.STUDENT_ACADEMIC_AVERAGE,
            s.STUDENT_BACCALAUREATE_SUB_NUMBER,
            s.STUDENT_LAPTOP_SERIAL_NUMBER,
            s.STUDENT_BIRTHDATE_CERTIFICATE_NUMBER, s.STUDENT_ID_CARD_NUMBER,
            s.STUDENT_POSTAL_ACCOUNT_NUMBER,
            s.STUDENT_MILITARY_NECKLACE,
            s.STUDENT_NUMBER_OF_SIBLINGS, s.STUDENT_NUMBER_OF_SISTERS,
            s.STUDENT_ORDER_AMONG_SIBLINGS,
            s.STUDENT_ORPHAN_STATUS, s.STUDENT_PARENTS_SITUATION,
            
            g.GRADE_NAME_EN, g.GRADE_NAME_AR,
            sec.SECTION_NAME_EN, sec.SECTION_NAME_AR,
            cat.CATEGORY_NAME_EN, cat.CATEGORY_NAME_AR,
            a.ARMY_NAME_EN, a.ARMY_NAME_AR,

            sp.speciality_name_en AS SPECIALITY_NAME_EN,
            sp.speciality_name_ar AS SPECIALITY_NAME_AR,

            al.ACADEMIC_LEVEL_EN AS ACADEMIC_LEVEL_EN,
            al.ACADEMIC_LEVEL_AR AS ACADEMIC_LEVEL_AR,

            spi.FATHER_FIRST_NAME_EN, spi.FATHER_LAST_NAME_EN,
            spi.FATHER_FIRST_NAME_AR, spi.FATHER_LAST_NAME_AR,
            spi.MOTHER_FIRST_NAME_EN, spi.MOTHER_LAST_NAME_EN,
            spi.MOTHER_FIRST_NAME_AR, spi.MOTHER_LAST_NAME_AR,

            pf.profession_name_en AS FATHER_PROFESSION_EN,
            pf.profession_name_ar AS FATHER_PROFESSION_AR,
            pm.profession_name_en AS MOTHER_PROFESSION_EN,
            pm.profession_name_ar AS MOTHER_PROFESSION_AR,

            hs.health_status_en AS HEALTH_STATUS_EN,
            hs.health_status_ar AS HEALTH_STATUS_AR,

            addr_bp.ADDRESS_STREET_EN AS BP_STREET_EN, addr_bp.ADDRESS_STREET_AR AS BP_STREET_AR,
            c_bp.COUNTRY_NAME_EN AS BP_COUNTRY_EN, c_bp.COUNTRY_NAME_AR AS BP_COUNTRY_AR,
            w_bp.WILAYA_NAME_EN AS BP_WILAYA_EN, w_bp.WILAYA_NAME_AR AS BP_WILAYA_AR,

            addr_p.ADDRESS_STREET_EN AS PERS_STREET_EN, addr_p.ADDRESS_STREET_AR AS PERS_STREET_AR,
            c_p.COUNTRY_NAME_EN AS PERS_COUNTRY_EN, c_p.COUNTRY_NAME_AR AS PERS_COUNTRY_AR,
            w_p.WILAYA_NAME_EN AS PERS_WILAYA_EN, w_p.WILAYA_NAME_AR AS PERS_WILAYA_AR,

            sec_emg.CONTACT_FIRST_NAME_EN, sec_emg.CONTACT_LAST_NAME_EN,
            sec_emg.CONTACT_FIRST_NAME_AR, sec_emg.CONTACT_LAST_NAME_AR,
            rel.relation_name_en AS CONTACT_RELATION_EN,
            rel.relation_name_ar AS CONTACT_RELATION_AR,
            sec_emg.CONTACT_PHONE_NUMBER AS EMG_PHONE,
            sec_emg.CONSULATE_NUMBER

        FROM student s
        LEFT JOIN section sec ON s.SECTION_ID = sec.SECTION_ID
        LEFT JOIN category cat ON s.CATEGORY_ID = cat.CATEGORY_ID
        LEFT JOIN grade g ON s.STUDENT_GRADE_ID = g.GRADE_ID
        LEFT JOIN army a ON s.STUDENT_ARMY_ID = a.ARMY_ID
        LEFT JOIN student_speciality sp ON s.STUDENT_SPECIALITY_ID = sp.student_speciality_id
        LEFT JOIN academic_level al ON s.STUDENT_ACADEMIC_LEVEL_ID = al.ACADEMIC_LEVEL_ID
        LEFT JOIN student_parent_info spi ON s.STUDENT_SERIAL_NUMBER = spi.STUDENT_SERIAL_NUMBER
        LEFT JOIN profession pf ON spi.FATHER_PROFESSION_ID = pf.profession_id
        LEFT JOIN profession pm ON spi.MOTHER_PROFESSION_ID = pm.profession_id
        LEFT JOIN health_status hs ON s.STUDENT_HEALTH_STATUS_ID = hs.health_status_id
        LEFT JOIN address addr_bp ON s.STUDENT_BIRTH_PLACE_ID = addr_bp.ADDRESS_ID
        LEFT JOIN country c_bp ON addr_bp.COUNTRY_ID = c_bp.COUNTRY_ID
        LEFT JOIN wilaya w_bp ON addr_bp.WILAYA_ID = w_bp.WILAYA_ID
        LEFT JOIN address addr_p ON s.STUDENT_PERSONAL_ADDRESS_ID = addr_p.ADDRESS_ID
        LEFT JOIN country c_p ON addr_p.COUNTRY_ID = c_p.COUNTRY_ID
        LEFT JOIN wilaya w_p ON addr_p.WILAYA_ID = w_p.WILAYA_ID
        LEFT JOIN student_emergency_contact sec_emg ON s.STUDENT_SERIAL_NUMBER = sec_emg.STUDENT_SERIAL_NUMBER
        LEFT JOIN relation rel ON sec_emg.CONTACT_RELATION_ID = rel.relation_id
        WHERE s.STUDENT_SERIAL_NUMBER = ?
    ";

    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("s", $serial_number);
    $stmt->execute();
    $studentResult = $stmt->get_result();

    if ($studentResult->num_rows === 0) {
        die("Student not found.");
    }

    $student = $studentResult->fetch_assoc();
    $stmt->close();

    // 2. Get absences
    $motif_col_abs = ($lang === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";
    $absencesQuery = "
        SELECT a.ABSENCE_DATE_AND_TIME, $motif_col_abs AS ABSENCE_MOTIF, a.ABSENCE_OBSERVATION
        FROM absence a
        JOIN student_gets_absent sga ON a.ABSENCE_ID = sga.ABSENCE_ID
        LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
        WHERE sga.STUDENT_SERIAL_NUMBER = ?
        AND DATE(a.ABSENCE_DATE_AND_TIME) >= ?
        AND DATE(a.ABSENCE_DATE_AND_TIME) <= ?
        ORDER BY a.ABSENCE_DATE_AND_TIME DESC
    ";
    $stmt = $conn->prepare($absencesQuery);
    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $absencesResult = $stmt->get_result();
    $absences = [];
    while ($row = $absencesResult->fetch_assoc()) {
        $absences[] = $row;
    }
    $stmt->close();

    // 3. Get observations
    $motif_col_obs = ($lang === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";
    $observationsQuery = "
        SELECT tmao.OBSERVATION_DATE_AND_TIME, $motif_col_obs AS OBSERVATION_MOTIF, tmao.OBSERVATION_NOTE,
               t.TEACHER_FIRST_NAME_EN, t.TEACHER_LAST_NAME_EN, t.TEACHER_FIRST_NAME_AR, t.TEACHER_LAST_NAME_AR
        FROM teacher_makes_an_observation_for_a_student tmao
        JOIN teacher t ON tmao.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
        LEFT JOIN observation_motif om ON tmao.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID
        WHERE tmao.STUDENT_SERIAL_NUMBER = ?
        AND DATE(tmao.OBSERVATION_DATE_AND_TIME) >= ?
        AND DATE(tmao.OBSERVATION_DATE_AND_TIME) <= ?
        ORDER BY tmao.OBSERVATION_DATE_AND_TIME DESC
    ";
    $stmt = $conn->prepare($observationsQuery);
    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $observationsResult = $stmt->get_result();
    $observations = [];
    while ($row = $observationsResult->fetch_assoc()) {
        $observations[] = $row;
    }
    $stmt->close();

    // 4. Get punishments
    $pun_label_col = ($lang === 'ar') ? "pt.PUNISHMENT_LABEL_AR" : "pt.PUNISHMENT_LABEL_EN";
    $punishmentsQuery = "
        SELECT sps.PUNISHMENT_SUGGESTED_AT, $pun_label_col AS PUNISHMENT_LABEL, sps.PUNISHMENT_NOTE
        FROM secretary_punishes_student sps
        JOIN punishment_type pt ON sps.PUNISHMENT_TYPE_ID = pt.PUNISHMENT_TYPE_ID
        WHERE sps.STUDENT_SERIAL_NUMBER = ?
        AND DATE(sps.PUNISHMENT_SUGGESTED_AT) >= ?
        AND DATE(sps.PUNISHMENT_SUGGESTED_AT) <= ?
        ORDER BY sps.PUNISHMENT_SUGGESTED_AT DESC
    ";
    $stmt = $conn->prepare($punishmentsQuery);
    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $punishmentsResult = $stmt->get_result();
    $punishments = [];
    while ($row = $punishmentsResult->fetch_assoc()) {
        $punishments[] = $row;
    }
    $stmt->close();

    // 5. Get rewards
    $rew_label_col = ($lang === 'ar') ? "rt.REWARD_LABEL_AR" : "rt.REWARD_LABEL_EN";
    $rewardsQuery = "
        SELECT srs.REWARD_SUGGESTED_AT, $rew_label_col AS REWARD_LABEL, srs.REWARD_NOTE
        FROM secretary_rewards_student srs
        JOIN reward_type rt ON srs.REWARD_TYPE_ID = rt.REWARD_TYPE_ID
        WHERE srs.STUDENT_SERIAL_NUMBER = ?
        AND DATE(srs.REWARD_SUGGESTED_AT) >= ?
        AND DATE(srs.REWARD_SUGGESTED_AT) <= ?
        ORDER BY srs.REWARD_SUGGESTED_AT DESC
    ";
    $stmt = $conn->prepare($rewardsQuery);
    $stmt->bind_param("sss", $serial_number, $start_date, $end_date);
    $stmt->execute();
    $rewardsResult = $stmt->get_result();
    $rewards = [];
    while ($row = $rewardsResult->fetch_assoc()) {
        $rewards[] = $row;
    }
    $stmt->close();

    $conn->close();

    // Prepare localized values
    $studentName = ($lang === 'ar' && !empty($student['STUDENT_FIRST_NAME_AR'])) 
        ? $student['STUDENT_FIRST_NAME_AR'] . ' ' . $student['STUDENT_LAST_NAME_AR']
        : $student['STUDENT_FIRST_NAME_EN'] . ' ' . $student['STUDENT_LAST_NAME_EN'];
    
    $grade = ($lang === 'ar') ? ($student['GRADE_NAME_AR'] ?? 'N/A') : ($student['GRADE_NAME_EN'] ?? 'N/A');
    $section = ($lang === 'ar') ? ($student['SECTION_NAME_AR'] ?? 'N/A') : ($student['SECTION_NAME_EN'] ?? 'N/A');
    $category = ($lang === 'ar') ? ($student['CATEGORY_NAME_AR'] ?? 'N/A') : ($student['CATEGORY_NAME_EN'] ?? 'N/A');
    $speciality = ($lang === 'ar') ? ($student['SPECIALITY_NAME_AR'] ?? 'N/A') : ($student['SPECIALITY_NAME_EN'] ?? 'N/A');
    $academic_level = ($lang === 'ar') ? ($student['ACADEMIC_LEVEL_AR'] ?? 'N/A') : ($student['ACADEMIC_LEVEL_EN'] ?? 'N/A');
    $health_status = ($lang === 'ar') ? ($student['HEALTH_STATUS_AR'] ?? 'N/A') : ($student['HEALTH_STATUS_EN'] ?? 'N/A');
    $father_prof = ($lang === 'ar') ? ($student['FATHER_PROFESSION_AR'] ?? 'N/A') : ($student['FATHER_PROFESSION_EN'] ?? 'N/A');
    $mother_prof = ($lang === 'ar') ? ($student['MOTHER_PROFESSION_AR'] ?? 'N/A') : ($student['MOTHER_PROFESSION_EN'] ?? 'N/A');

    $father_name = ($lang === 'ar' && !empty($student['FATHER_FIRST_NAME_AR']))
        ? trim($student['FATHER_FIRST_NAME_AR'] . ' ' . $student['FATHER_LAST_NAME_AR'])
        : trim(($student['FATHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['FATHER_LAST_NAME_EN'] ?? ''));
    
    $mother_name = ($lang === 'ar' && !empty($student['MOTHER_FIRST_NAME_AR']))
        ? trim($student['MOTHER_FIRST_NAME_AR'] . ' ' . $student['MOTHER_LAST_NAME_AR'])
        : trim(($student['MOTHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['MOTHER_LAST_NAME_EN'] ?? ''));

    $birth_place = trim(($student['BP_STREET_' . strtoupper($lang)] ?? '') . ' ' . ($student['BP_WILAYA_' . strtoupper($lang)] ?? '') . ' ' . ($student['BP_COUNTRY_' . strtoupper($lang)] ?? ''));
    $personal_address = trim(($student['PERS_STREET_' . strtoupper($lang)] ?? '') . ' ' . ($student['PERS_WILAYA_' . strtoupper($lang)] ?? '') . ' ' . ($student['PERS_COUNTRY_' . strtoupper($lang)] ?? ''));

    $emg_name = ($lang === 'ar' && !empty($student['CONTACT_FIRST_NAME_AR']))
        ? trim($student['CONTACT_FIRST_NAME_AR'] . ' ' . $student['CONTACT_LAST_NAME_AR'])
        : trim(($student['CONTACT_FIRST_NAME_EN'] ?? '') . ' ' . ($student['CONTACT_LAST_NAME_EN'] ?? ''));
    $emg_relation = ($lang === 'ar') ? ($student['CONTACT_RELATION_AR'] ?? 'N/A') : ($student['CONTACT_RELATION_EN'] ?? 'N/A');

    // Orphan status translation
    $orphan_map = ['None' => 'none', 'Father' => 'father', 'Mother' => 'mother', 'Both' => 'both'];
    $orphan_key = $orphan_map[$student['STUDENT_ORPHAN_STATUS'] ?? 'None'] ?? 'none';
    $orphan_status = t('orphan_' . $orphan_key);

    // Parents situation translation
    $parents_situation = t(strtolower($student['STUDENT_PARENTS_SITUATION'] ?? 'none'));

    // Sex translation
    $sex = t(strtolower($student['STUDENT_SEX'] ?? 'male'));

    // Is foreign translation
    $is_foreign = t(strtolower($student['STUDENT_IS_FOREIGN'] ?? 'no'));

    // Generate LaTeX content
    $title = ($lang === 'ar') ? "تقرير تفصيلي للطالب: " . $studentName : "Detailed Student Record: " . $studentName;
    
    $tex = "";
    if ($lang === 'ar') {
        $tex .= "\\documentclass[a4paper,12pt]{article}\n";
        $tex .= "\\usepackage{fontspec}\n";
        $tex .= "\\usepackage{polyglossia}\n";
        $tex .= "\\setmainlanguage{arabic}\n";
        $tex .= "\\setotherlanguage{english}\n";
        $tex .= "\\newfontfamily\\arabicfont[Script=Arabic]{Simplified Arabic}\n";
    } else {
        $tex .= "\\documentclass[a4paper,12pt]{article}\n";
        $tex .= "\\usepackage{fontspec}\n";
        $tex .= "\\usepackage{polyglossia}\n";
        $tex .= "\\setmainlanguage{english}\n";
        $tex .= "\\setotherlanguage{arabic}\n";
        $tex .= "\\newfontfamily\\arabicfont[Script=Arabic]{Simplified Arabic}\n";
    }
    $tex .= "\\usepackage{geometry}\n";
    $tex .= "\\geometry{margin=2cm}\n";
    $tex .= "\\usepackage{graphicx}\n";
    $tex .= "\\usepackage{booktabs}\n";
    $tex .= "\\usepackage{longtable}\n";
    $tex .= "\\usepackage{xcolor}\n";
    $tex .= "\\title{" . latexEscape($title) . "}\n";
    $tex .= "\\author{EduTrack System}\n";
    $tex .= "\\date{\\today}\n";
    $tex .= "\\begin{document}\n";
    $tex .= "\\maketitle\n\n";

    // Section 1: Personal Information
    $section1_title = ($lang === 'ar') ? "1. المعلومات الشخصية" : "1. Personal Information";
    $tex .= "\\section*{" . $section1_title . "}\n";
    $tex .= "\\begin{tabular}{lp{10cm}}\n";
    
    $labels = [
        ($lang === 'ar') ? 'الرقم التسلسلي' : 'Serial Number',
        ($lang === 'ar') ? 'الاسم الكامل' : 'Full Name',
        ($lang === 'ar') ? 'الجنس' : 'Sex',
        ($lang === 'ar') ? 'تاريخ الميلاد' : 'Birth Date',
        ($lang === 'ar') ? 'فصيلة الدم' : 'Blood Type',
        ($lang === 'ar') ? 'الهاتف الشخصي' : 'Personal Phone',
        ($lang === 'ar') ? 'الطول (سم)' : 'Height (cm)',
        ($lang === 'ar') ? 'الوزن (كغ)' : 'Weight (kg)',
        ($lang === 'ar') ? 'أجنبي؟' : 'Foreign Student',
        ($lang === 'ar') ? 'الحالة الصحية' : 'Health Status',
    ];
    
    $values = [
        latexEscape($student['STUDENT_SERIAL_NUMBER']),
        latexEscape($studentName),
        latexEscape($sex),
        latexEscape($student['STUDENT_BIRTH_DATE']),
        latexEscape($student['STUDENT_BLOOD_TYPE']),
        latexEscape($student['STUDENT_PERSONAL_PHONE']),
        latexEscape($student['STUDENT_HEIGHT_CM']),
        latexEscape($student['STUDENT_WEIGHT_KG']),
        latexEscape($is_foreign),
        latexEscape($health_status),
    ];
    
    for ($i = 0; $i < count($labels); $i++) {
        $tex .= "\\textbf{" . $labels[$i] . ":} & " . ($values[$i] ?: 'N/A') . " \\\\\n";
    }
    $tex .= "\\end{tabular}\n\n";

    // Section 2: Academic Details
    $section2_title = ($lang === 'ar') ? "2. التفاصيل الأكاديمية" : "2. Academic Details";
    $tex .= "\\section*{" . $section2_title . "}\n";
    $tex .= "\\begin{tabular}{lp{10cm}}\n";
    
    $labels2 = [
        ($lang === 'ar') ? 'الرتبة' : 'Grade',
        ($lang === 'ar') ? 'القسم' : 'Section',
        ($lang === 'ar') ? 'الفئة' : 'Category',
        ($lang === 'ar') ? 'التخصص' : 'Speciality',
        ($lang === 'ar') ? 'المستوى الأكاديمي' : 'Academic Level',
        ($lang === 'ar') ? 'المعدل الأكاديمي' : 'Academic Average',
        ($lang === 'ar') ? 'رقم البكالوريا' : 'Baccalaureate Number',
        ($lang === 'ar') ? 'الرقم التسلسلي للحاسوب' : 'Laptop Serial',
    ];
    
    $values2 = [
        latexEscape($grade),
        latexEscape($section),
        latexEscape($category),
        latexEscape($speciality),
        latexEscape($academic_level),
        latexEscape($student['STUDENT_ACADEMIC_AVERAGE']),
        latexEscape($student['STUDENT_BACCALAUREATE_SUB_NUMBER']),
        latexEscape($student['STUDENT_LAPTOP_SERIAL_NUMBER']),
    ];
    
    for ($i = 0; $i < count($labels2); $i++) {
        $tex .= "\\textbf{" . $labels2[$i] . ":} & " . ($values2[$i] ?: 'N/A') . " \\\\\n";
    }
    $tex .= "\\end{tabular}\n\n";

    // Section 3: Family Information
    $section3_title = ($lang === 'ar') ? "3. معلومات العائلة" : "3. Family Information";
    $tex .= "\\section*{" . $section3_title . "}\n";
    $tex .= "\\begin{tabular}{lp{10cm}}\n";
    
    $labels3 = [
        ($lang === 'ar') ? 'اسم الأب' : "Father's Name",
        ($lang === 'ar') ? 'مهنة الأب' : "Father's Profession",
        ($lang === 'ar') ? 'اسم الأم' : "Mother's Name",
        ($lang === 'ar') ? 'مهنة الأم' : "Mother's Profession",
        ($lang === 'ar') ? 'حالة الوالدين' : "Parents' Situation",
        ($lang === 'ar') ? 'حالة اليتم' : 'Orphan Status',
        ($lang === 'ar') ? 'عدد الإخوة' : 'Siblings Count',
    ];
    
    $values3 = [
        latexEscape($father_name) ?: 'N/A',
        latexEscape($father_prof),
        latexEscape($mother_name) ?: 'N/A',
        latexEscape($mother_prof),
        latexEscape($parents_situation),
        latexEscape($orphan_status),
        latexEscape($student['STUDENT_NUMBER_OF_SIBLINGS']),
    ];
    
    for ($i = 0; $i < count($labels3); $i++) {
        $tex .= "\\textbf{" . $labels3[$i] . ":} & " . ($values3[$i] ?: 'N/A') . " \\\\\n";
    }
    $tex .= "\\end{tabular}\n\n";

    // Section 4: Addresses
    $section4_title = ($lang === 'ar') ? "4. العناوين" : "4. Addresses";
    $tex .= "\\section*{" . $section4_title . "}\n";
    $tex .= "\\begin{tabular}{lp{10cm}}\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'عنوان مكان الميلاد' : 'Birth Place') . ":} & " . latexEscape($birth_place) . " \\\\\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'العنوان الشخصي' : 'Personal Address') . ":} & " . latexEscape($personal_address) . " \\\\\n";
    $tex .= "\\end{tabular}\n\n";

    // Section 5: Emergency Contact
    $section5_title = ($lang === 'ar') ? "5. جهة الاتصال في الطوارئ" : "5. Emergency Contact";
    $tex .= "\\section*{" . $section5_title . "}\n";
    $tex .= "\\begin{tabular}{lp{10cm}}\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'الاسم' : 'Name') . ":} & " . latexEscape($emg_name) . " \\\\\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'صلة القرابة' : 'Relation') . ":} & " . latexEscape($emg_relation) . " \\\\\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'الهاتف' : 'Phone') . ":} & " . latexEscape($student['EMG_PHONE']) . " \\\\\n";
    $tex .= "\\textbf{" . (($lang === 'ar') ? 'رقم القنصلية' : 'Consulate Number') . ":} & " . latexEscape($student['CONSULATE_NUMBER']) . " \\\\\n";
    $tex .= "\\end{tabular}\n\n";

    // Section 6: Absences
    $section6_title = ($lang === 'ar') ? "6. الغيابات ($start_date إلى $end_date)" : "6. Absences ($start_date to $end_date)";
    $tex .= "\\section*{" . $section6_title . "}\n";
    
    if (empty($absences)) {
        $no_abs = ($lang === 'ar') ? 'لا توجد غيابات مسجلة في هذه الفترة.' : 'No absences recorded in this period.';
        $tex .= $no_abs . "\n";
    } else {
        $tex .= "\\begin{longtable}{lp{4cm}p{6cm}}\n";
        $tex .= "\\hline \\textbf{" . (($lang === 'ar') ? 'التاريخ' : 'Date') . "} & \\textbf{" . (($lang === 'ar') ? 'السبب' : 'Motif') . "} & \\textbf{" . (($lang === 'ar') ? 'ملاحظة' : 'Note') . "} \\\\ \\hline\n";
        $tex .= "\\endhead\n";
        foreach ($absences as $a) {
            $tex .= latexEscape($a['ABSENCE_DATE_AND_TIME']) . " & " . latexEscape($a['ABSENCE_MOTIF']) . " & " . latexEscape($a['ABSENCE_OBSERVATION']) . " \\\\\n";
        }
        $tex .= "\\hline\n";
        $tex .= "\\end{longtable}\n\n";
    }

    // Section 7: Observations
    $section7_title = ($lang === 'ar') ? "7. الملاحظات" : "7. Observations";
    $tex .= "\\section*{" . $section7_title . "}\n";
    
    if (empty($observations)) {
        $no_obs = ($lang === 'ar') ? 'لا توجد ملاحظات مسجلة.' : 'No observations recorded.';
        $tex .= $no_obs . "\n";
    } else {
        $tex .= "\\begin{longtable}{lp{3cm}p{3cm}p{4cm}}\n";
        $tex .= "\\hline \\textbf{" . (($lang === 'ar') ? 'التاريخ' : 'Date') . "} & \\textbf{" . (($lang === 'ar') ? 'السبب' : 'Motif') . "} & \\textbf{" . (($lang === 'ar') ? 'المعلم' : 'Teacher') . "} & \\textbf{" . (($lang === 'ar') ? 'ملاحظة' : 'Note') . "} \\\\ \\hline\n";
        $tex .= "\\endhead\n";
        foreach ($observations as $o) {
            $teacherName = ($lang === 'ar' && !empty($o['TEACHER_FIRST_NAME_AR']))
                ? $o['TEACHER_FIRST_NAME_AR'] . ' ' . $o['TEACHER_LAST_NAME_AR']
                : $o['TEACHER_FIRST_NAME_EN'] . ' ' . $o['TEACHER_LAST_NAME_EN'];
            $tex .= latexEscape($o['OBSERVATION_DATE_AND_TIME']) . " & " . latexEscape($o['OBSERVATION_MOTIF']) . " & " . latexEscape($teacherName) . " & " . latexEscape($o['OBSERVATION_NOTE']) . " \\\\\n";
        }
        $tex .= "\\hline\n";
        $tex .= "\\end{longtable}\n\n";
    }

    // Section 8: Punishments
    $section8_title = ($lang === 'ar') ? "8. العقوبات" : "8. Punishments";
    $tex .= "\\section*{" . $section8_title . "}\n";
    
    if (empty($punishments)) {
        $no_pun = ($lang === 'ar') ? 'لا توجد عقوبات مسجلة في هذه الفترة.' : 'No punishments recorded in this period.';
        $tex .= $no_pun . "\n";
    } else {
        $tex .= "\\begin{longtable}{lp{4cm}p{6cm}}\n";
        $tex .= "\\hline \\textbf{" . (($lang === 'ar') ? 'التاريخ' : 'Date') . "} & \\textbf{" . (($lang === 'ar') ? 'النوع' : 'Type') . "} & \\textbf{" . (($lang === 'ar') ? 'ملاحظة' : 'Note') . "} \\\\ \\hline\n";
        $tex .= "\\endhead\n";
        foreach ($punishments as $p) {
            $tex .= latexEscape($p['PUNISHMENT_SUGGESTED_AT']) . " & " . latexEscape($p['PUNISHMENT_LABEL']) . " & " . latexEscape($p['PUNISHMENT_NOTE']) . " \\\\\n";
        }
        $tex .= "\\hline\n";
        $tex .= "\\end{longtable}\n\n";
    }

    // Section 9: Rewards
    $section9_title = ($lang === 'ar') ? "9. المكافآت" : "9. Rewards";
    $tex .= "\\section*{" . $section9_title . "}\n";
    
    if (empty($rewards)) {
        $no_rew = ($lang === 'ar') ? 'لا توجد مكافآت مسجلة في هذه الفترة.' : 'No rewards recorded in this period.';
        $tex .= $no_rew . "\n";
    } else {
        $tex .= "\\begin{longtable}{lp{4cm}p{6cm}}\n";
        $tex .= "\\hline \\textbf{" . (($lang === 'ar') ? 'التاريخ' : 'Date') . "} & \\textbf{" . (($lang === 'ar') ? 'النوع' : 'Type') . "} & \\textbf{" . (($lang === 'ar') ? 'ملاحظة' : 'Note') . "} \\\\ \\hline\n";
        $tex .= "\\endhead\n";
        foreach ($rewards as $r) {
            $tex .= latexEscape($r['REWARD_SUGGESTED_AT']) . " & " . latexEscape($r['REWARD_LABEL']) . " & " . latexEscape($r['REWARD_NOTE']) . " \\\\\n";
        }
        $tex .= "\\hline\n";
        $tex .= "\\end{longtable}\n\n";
    }

    $tex .= "\\end{document}\n";

    // Create tmp_reports directory if it doesn't exist
    $tmpDir = __DIR__ . '/tmp_reports';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0755, true);
    }

    // Generate unique filename
    $timestamp = date('Ymd_His');
    $baseName = "student_{$serial_number}_{$timestamp}";
    $texPath = $tmpDir . '/' . $baseName . '.tex';
    $pdfPath = $tmpDir . '/' . $baseName . '.pdf';

    // Write .tex file
    file_put_contents($texPath, $tex);

    // Compile LaTeX to PDF using xelatex (supports Arabic)
    $output = [];
    $returnCode = 0;
    
    // Try xelatex first (better for Unicode/Arabic)
    $command = "cd \"$tmpDir\" && xelatex -interaction=nonstopmode \"$baseName.tex\" 2>&1";
    exec($command, $output, $returnCode);

    // Check if PDF was created
    if (!file_exists($pdfPath)) {
        // Try pdflatex as fallback
        $command = "cd \"$tmpDir\" && pdflatex -interaction=nonstopmode \"$baseName.tex\" 2>&1";
        exec($command, $output, $returnCode);
    }

    if (file_exists($pdfPath)) {
        // Send PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="student_report_' . $serial_number . '.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        
        // Clean up auxiliary files
        $auxFile = $tmpDir . '/' . $baseName . '.aux';
        $logFile = $tmpDir . '/' . $baseName . '.log';
        if (file_exists($auxFile)) unlink($auxFile);
        if (file_exists($logFile)) unlink($logFile);
        
        exit;
    } else {
        // PDF generation failed - show error with LaTeX output for debugging
        echo "<h3>PDF Generation Failed</h3>";
        echo "<p>LaTeX output:</p><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        echo "<p>Generated TEX file is available at: " . htmlspecialchars($texPath) . "</p>";
        echo "<p>Please ensure XeLaTeX or PDFLaTeX is installed on the server.</p>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
