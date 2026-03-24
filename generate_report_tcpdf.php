<?php
// generate_report.php - Generate PDF report for student records using TCPDF
// Ensure UTF-8 encoding throughout
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');

session_start();
date_default_timezone_set('Africa/Algiers');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/lang/i18n.php';

function load_translations($l) {
    $T = [];
    require __DIR__ . '/lang/' . $l . '.php';
    return $T;
}

$T_EN = load_translations('en');
$T_AR = load_translations('ar');

function tr_lang($key, $l) {
    global $T_EN, $T_AR;
    $table = ($l === 'ar') ? $T_AR : $T_EN;
    return $table[$key] ?? $key;
}

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Set TCPDF font directory (must be before including TCPDF)
define('K_PATH_FONTS', __DIR__ . '/vendor/tcpdf/fonts/');

$FONT_EN = file_exists(K_PATH_FONTS . 'cmunrm.php') ? 'cmunrm' : 'helvetica';
$FONT_AR = 'trado'; // Traditional Arabic font

// Include TCPDF
require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Set charset to utf8mb4 for Arabic text support
$conn->set_charset("utf8mb4");

// Get parameters
$serial_number = isset($_GET['serial_number']) ? $_GET['serial_number'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$serial_number || !$start_date || !$end_date) {
    die("Missing required parameters.");
}

$lang = $_SESSION['lang'] ?? 'en';
$isRTL = ($lang === 'ar');

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
            sec_emg.CONSULATE_NUMBER,
            s.STUDENT_PHOTO


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

    // Create PDF
    class MYPDF extends TCPDF {
    protected $isRTL;
    protected $lang;
    protected $reportSerial;
    
    public function __construct($isRTL = false, $lang = 'en', $reportSerial = '') {
        parent::__construct();
        $this->isRTL = $isRTL;
        $this->lang = $lang;
        $this->reportSerial = $reportSerial;
        if ($this->isRTL) {
            $this->setRTL(true);
        }
    }
    
    public function Header() {
        global $FONT_EN, $FONT_AR;
        
        // Top decorative line
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(1.5);
        $this->Line(10, 10, $this->getPageWidth() - 10, 10);
        
        // Institution header box
        $this->SetFillColor(0, 51, 102);
        $this->Rect(10, 12, $this->getPageWidth() - 20, 18, 'F');
        
        // Institution name (Bilingual)
        $this->SetTextColor(255, 255, 255);
        $this->SetY(13);
        $this->SetFont($FONT_AR, 'B', 15);
        $this->Cell(0, 8, tr_lang('app_name', 'ar') . ' - ' . "\xE2\x80\xAA" . 'eNote' . "\xE2\x80\xAC", 0, 1, 'C');
        $this->SetFont($FONT_AR, '', 9);
        $this->Cell(0, 5, tr_lang('system_name', 'ar') . ' / ' . "\xE2\x80\xAA" . 'Educational Management System' . "\xE2\x80\xAC", 0, 1, 'C');
        
        // Reset text color
        $this->SetTextColor(0, 0, 0);
        
        // Thin separator line
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.3);
        $this->Line(10, 38, $this->getPageWidth() - 10, 38);
        
        $this->SetY(42);
    }
    
    public function Footer() {
        global $FONT_EN, $FONT_AR;
        
        // Footer line
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->getPageHeight() - 18, $this->getPageWidth() - 10, $this->getPageHeight() - 18);
        
        $this->SetY(-15);
        $this->SetFont($this->isRTL ? $FONT_AR : $FONT_EN, '', 8);
        $this->SetTextColor(80, 80, 80);
        
        $pageText = $this->isRTL ? tr_lang('page', 'ar') : 'Page';
        $this->Cell(60, 8, $pageText . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'L');
        
        // Center text
        $this->Cell(0, 8, date('Y') . ' © eNote', 0, 0, 'C');
        
        // Right text - generated date
        $this->Cell(60, 8, date('d/m/Y H:i'), 0, 0, 'R');
        
        $this->SetTextColor(0, 0, 0);
    }
}

    $pdf = new MYPDF($isRTL, $lang, $serial_number);
    $pdf->SetCreator('eNote Educational Management System');
    $pdf->SetAuthor('eNote');
    
    // Ensure proper Unicode handling
    $pdf->setFontSubsetting(true); // Embed all font glyphs for Unicode support
    
    $reportTitle = ($lang === 'ar') ? tr_lang('student_report', 'ar') . ': ' . $studentName : 'Student Report: ' . $studentName;
    $pdf->SetTitle($reportTitle);
    
    // Set margins for official document look
    $pdf->SetMargins(15, 45, 15);
    $pdf->SetAutoPageBreak(true, 20);
    
    $pdf->AddPage();
    $pdf->SetFont($FONT_EN, '', 11);

    // Helper function to add section title with official styling
    function addSection($pdf, $title) {
        global $FONT_EN;
        // Section header with dark navy background
        $pdf->SetFont($FONT_EN, 'B', 12);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(0, 51, 102);
        $pdf->Cell(0, 8, '  ' . $title, 1, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($FONT_EN, '', 10);
        $pdf->Ln(2);
    }

    function normalizePdfValue($value) {
        if ($value === null) {
            return 'N/A';
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strcasecmp($trimmed, 'null') === 0) {
                return 'N/A';
            }
            return $value;
        }
        return $value;
    }

    function addBiSection($pdf, $titleEn, $titleAr) {
        global $FONT_EN, $FONT_AR;
        
        // Official dark navy section header
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(0, 51, 102);

        $margins = $pdf->getMargins();
        $x = $margins['left'];
        $w = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
        $half = $w / 2;
        $y = $pdf->GetY();

        $pdf->SetFont($FONT_EN, 'B', 11);
        $pdf->MultiCell($half, 7, '  ' . $titleEn, 0, 'L', true, 0, $x, $y, true);
        $titleArSafe = htmlspecialchars($titleAr, ENT_QUOTES, 'UTF-8');
        $pdf->SetFont($FONT_AR, 'B', 11);
        $pdf->writeHTMLCell(
            $half,
            7,
            $x + $half,
            $y,
            '<div dir="rtl" style="text-align:right; background-color:#003366; padding:0 5px;">' . $titleArSafe . '</div>',
            0,
            1,
            true,
            true,
            'R',
            true
        );

        // Bottom border for section header
        $pdf->SetDrawColor(0, 51, 102);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($x, $y + 7, $x + $w, $y + 7);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($FONT_EN, '', 10);
        $pdf->Ln(3);
    }

    function writeBilingualHeaderCell($pdf, $w, $h, $en, $ar, $ln = 0, $fill = true) {
        global $FONT_EN, $FONT_AR;
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $enSafe = htmlspecialchars($en, ENT_QUOTES, 'UTF-8');
        $arSafe = htmlspecialchars($ar, ENT_QUOTES, 'UTF-8');
        $html = '<div style="text-align:center; font-family:' . $FONT_EN . '; font-weight:bold; font-size:9pt;">'
            . $enSafe
            . '<br/><span dir="rtl" style="text-align:center; font-family:' . $FONT_AR . '; font-weight:bold; font-size:9pt;">'
            . $arSafe
            . '</span></div>';

        // Official table header styling - dark navy
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(0, 51, 102);
        $pdf->writeHTMLCell($w, $h, $x, $y, $html, 1, 0, $fill, true, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + $w, $y);
        if ($ln === 1) {
            $pdf->Ln($h);
        }
    }

    function writeBilingualNoData($pdf, $en, $ar) {
        global $FONT_EN, $FONT_AR;
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $w = $pdf->getPageWidth() - $pdf->getMargins()['right'] - $x;

        // Styled "no data" box with light background
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect($x, $y, $w, 14, 'DF');

        // English line (LTR)
        $pdf->setRTL(false);
        $pdf->SetFont($FONT_EN, 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell($w, 5, $en, 0, 'C', false, 1, $x, $y + 2, true);

        // Arabic line (RTL) - force RTL to avoid bidi inversion
        $pdf->setRTL(true);
        $pdf->SetFont($FONT_AR, 'I', 9);
        $pdf->MultiCell($w, 5, $ar, 0, 'C', false, 1, $x, $pdf->GetY(), true);
        $pdf->setRTL(false);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
    }

    function addCenteredRowLabels($pdf, $labelEn, $labelAr, $value) {
        global $FONT_EN, $FONT_AR;
        $value = normalizePdfValue($value);

        $margins = $pdf->getMargins();
        $pageW = $pdf->getPageWidth();
        $w = $pageW - $margins['left'] - $margins['right'];

        $labelW = 50;
        $lineH = 7;

        $x = $margins['left'];
        $y = $pdf->GetY();

        $valueW = $w - (2 * $labelW);
        if ($valueW < 20) {
            $valueW = 20;
        }

        // Light gray background for row
        static $rowAlt = false;
        if ($rowAlt) {
            $pdf->SetFillColor(245, 245, 250);
            $pdf->Rect($x, $y, $w, $lineH, 'F');
        }
        $rowAlt = !$rowAlt;

        // EN label (left) - bold for official look
        $pdf->SetFont($FONT_EN, 'B', 9);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->MultiCell($labelW, $lineH, $labelEn . ':', 0, 'L', false, 0, $x, $y, true);

        // Value centered
        $pdf->SetTextColor(0, 0, 0);
        $hasArabicVal = is_string($value) && preg_match('/[\x{0600}-\x{06FF}]/u', $value);
        $pdf->SetFont($hasArabicVal ? $FONT_AR : $FONT_EN, '', 9);
        $pdf->MultiCell($valueW, $lineH, (string)$value, 0, 'C', false, 0, $x + $labelW, $y, true);

        // AR label (right) - bold for official look
        $pdf->SetFont($FONT_AR, 'B', 9);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->MultiCell($labelW, $lineH, $labelAr . ':', 0, 'R', false, 1, $x + $labelW + $valueW, $y, true);
        $pdf->SetTextColor(0, 0, 0);
    }

    function addBiRowLabels($pdf, $labelEn, $labelAr, $valueEn, $valueAr) {
        global $FONT_EN, $FONT_AR;
        $valueEn = normalizePdfValue($valueEn);
        $valueAr = normalizePdfValue($valueAr);

        // If there is no distinct Arabic equivalent (same value), center the value.
        // Example: serial number, numeric measures, IDs.
        $same = ((string)$valueEn === (string)$valueAr);
        if ($same) {
            addCenteredRowLabels($pdf, $labelEn, $labelAr, $valueEn);
            return;
        }

        $margins = $pdf->getMargins();
        $pageW = $pdf->getPageWidth();
        $w = $pageW - $margins['left'] - $margins['right'];
        $half = $w / 2;

        $labelW = 42;
        $lineH = 7;

        $xLeft = $margins['left'];
        $xRight = $margins['left'] + $half;
        $y = $pdf->GetY();

        // Light gray background for alternating rows
        static $rowAltBi = false;
        if ($rowAltBi) {
            $pdf->SetFillColor(245, 245, 250);
            $pdf->Rect($xLeft, $y, $w, $lineH, 'F');
        }
        $rowAltBi = !$rowAltBi;

        // Left (EN) - bold label for official look
        $pdf->SetFont($FONT_EN, 'B', 9);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->MultiCell($labelW, $lineH, $labelEn . ':', 0, 'L', false, 0, $xLeft, $y, true);
        $pdf->SetTextColor(0, 0, 0);
        $hasArabicEn = is_string($valueEn) && preg_match('/[\x{0600}-\x{06FF}]/u', $valueEn);
        $pdf->SetFont($hasArabicEn ? $FONT_AR : $FONT_EN, '', 9);
        $pdf->MultiCell($half - $labelW, $lineH, (string)$valueEn, 0, $hasArabicEn ? 'R' : 'L', false, 0, $xLeft + $labelW, $y, true);

        // Right (AR) - keep the Arabic label on the far right
        $hasArabic = is_string($valueAr) && preg_match('/[\x{0600}-\x{06FF}]/u', $valueAr);
        $pdf->SetFont($FONT_AR, '', 9);
        $alignArVal = $hasArabic ? 'R' : 'R';
        $pdf->MultiCell($half - $labelW, $lineH, (string)$valueAr, 0, $alignArVal, false, 0, $xRight, $y, true);

        // Arabic label with proper RTL using writeHTMLCell - bold for official look
        $pdf->SetFont($FONT_AR, 'B', 9);
        $pdf->SetTextColor(0, 51, 102);
        $htmlLabel = '<span dir="rtl" style="font-family: trado;">' . htmlspecialchars($labelAr, ENT_QUOTES, 'UTF-8') . ':</span>';
        $pdf->writeHTMLCell($labelW, $lineH, $xRight + ($half - $labelW), $y, $htmlLabel, 0, 1, false, true, 'R');
        $pdf->SetTextColor(0, 0, 0);
    }

    function addBiRowKey($pdf, $labelKey, $valueEn, $valueAr) {
        addBiRowLabels(
            $pdf,
            tr_lang($labelKey, 'en'),
            tr_lang($labelKey, 'ar'),
            $valueEn,
            $valueAr
        );
    }

    // Helper function to add key-value row
    function addRow($pdf, $label, $value, $isRTL = false) {
        global $FONT_AR, $FONT_EN;
        // Normalize missing values
        if ($value === null) {
            $value = 'N/A';
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strcasecmp($trimmed, 'null') === 0) {
                $value = 'N/A';
            }
        }

        $labelW = 60;
        $lineH = 8;

        // Detect if value contains Arabic characters
        $hasArabic = is_string($value) && preg_match('/[\x{0600}-\x{06FF}]/u', $value);

        // Keep document LTR to avoid cursor / overlap issues.
        // For Arabic values we simply right-align the value cell.
        $pdf->setRTL(false);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $margins = $pdf->getMargins();

        // Label
        $pdf->SetFont($FONT_EN, '', 10);
        $pdf->MultiCell($labelW, $lineH, $label . ':', 0, 'L', false, 0, $x, $y, true);

        // Value
        $pdf->SetFont(($hasArabic || $isRTL) ? $FONT_AR : $FONT_EN, '', 10);
        $xVal = $x + $labelW;
        $availableW = $pdf->getPageWidth() - $margins['right'] - $xVal;
        $align = ($hasArabic || $isRTL) ? 'R' : 'L';
        $pdf->MultiCell($availableW, $lineH, (string)$value, 0, $align, false, 1, $xVal, $y, true);
    }

    // Title with official document styling
    $pdf->SetY(45);
    
    // Document title box with space for photo
    $pdf->SetFillColor(240, 245, 250);
    $pdf->SetDrawColor(0, 51, 102);
    $pdf->SetLineWidth(0.8);
    $titleY = $pdf->GetY();
    
    $photoBoxW = 32;
    $photoBoxH = 42;
    $spacing = 5;
    $contentW = $pdf->getPageWidth() - 30;
    $titleBoxW = $contentW - $photoBoxW - $spacing;
    
    $pdf->Rect(15, $titleY, $titleBoxW, 25, 'DF');
    
    // Bilingual Title
    $pdf->SetTextColor(0, 51, 102);
    $pdf->SetFont($FONT_AR, 'B', 15);
    $studentNameAR = ($student['STUDENT_FIRST_NAME_AR'] ?? '') . ' ' . ($student['STUDENT_LAST_NAME_AR'] ?? '');
    $studentNameEN = trim(($student['STUDENT_FIRST_NAME_EN'] ?? '') . ' ' . ($student['STUDENT_LAST_NAME_EN'] ?? ''));
    $titleAR = tr_lang('student_report', 'ar') . ': ' . $studentNameAR;
    $titleEN = "\xE2\x80\xAA" . tr_lang('student_report', 'en') . ': ' . $studentNameEN . "\xE2\x80\xAC";
    $pdf->Cell($titleBoxW, 10, $titleAR . ' / ' . $titleEN, 0, 1, 'C');
    
    // Bilingual Date
    $pdf->SetFont($FONT_AR, '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $dateLabelAR = tr_lang('report_date', 'ar');
    $dateLabelEN = "\xE2\x80\xAA" . 'Report Date' . "\xE2\x80\xAC";
    $pdf->Cell($titleBoxW, 8, $dateLabelAR . ' / ' . $dateLabelEN . ': ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // Student Photo on the right
    $photoPath = $student['STUDENT_PHOTO'] ?? '';
    if (!empty($photoPath)) {
        $photoPath = str_replace('\\', '/', $photoPath);
        $fullPath = __DIR__ . '/' . $photoPath;
        if (file_exists($fullPath)) {
            $photoX = 15 + $titleBoxW + $spacing;
            // Photo frame
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect($photoX, $titleY, $photoBoxW, $photoBoxH, 'D');
            $pdf->Image($fullPath, $photoX + 1, $titleY + 1, $photoBoxW - 2, $photoBoxH - 2, '', '', '', true, 300, '', false, false, 0, false, false, false);
        }
    }
    
    // Period info - ensure it starts below both boxes
    $pdf->SetY($titleY + max(25, !empty($photoPath) ? $photoBoxH : 0) + 2);
    // Bilingual Period
    $periodLabelAR = 'الفترة (من ' . $start_date . ' إلى ' . $end_date . ')';
    $periodLabelEN = "\xE2\x80\xAA" . 'Period: ' . $start_date . ' to ' . $end_date . "\xE2\x80\xAC";
    $pdf->SetFont($FONT_AR, 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, $periodLabelAR . ' / ' . $periodLabelEN, 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(8);

    // Section 1: Personal Information
    addBiSection($pdf, '1. ' . tr_lang('personal_information', 'en'), tr_lang('personal_information', 'ar'));
    addBiRowKey($pdf, 'student_serial_number', $student['STUDENT_SERIAL_NUMBER'], $student['STUDENT_SERIAL_NUMBER']);
    addBiRowLabels(
        $pdf,
        tr_lang('full_name', 'en'),
        tr_lang('full_name', 'ar'),
        trim(($student['STUDENT_FIRST_NAME_EN'] ?? '') . ' ' . ($student['STUDENT_LAST_NAME_EN'] ?? '')),
        trim(($student['STUDENT_FIRST_NAME_AR'] ?? '') . ' ' . ($student['STUDENT_LAST_NAME_AR'] ?? ''))
    );
    addBiRowKey($pdf, 'sex', tr_lang(strtolower($student['STUDENT_SEX'] ?? 'male'), 'en'), tr_lang(strtolower($student['STUDENT_SEX'] ?? 'male'), 'ar'));
    addBiRowKey($pdf, 'birth_date', $student['STUDENT_BIRTH_DATE'], $student['STUDENT_BIRTH_DATE']);
    addBiRowKey($pdf, 'blood_type', $student['STUDENT_BLOOD_TYPE'], $student['STUDENT_BLOOD_TYPE']);
    addBiRowKey($pdf, 'personal_phone', $student['STUDENT_PERSONAL_PHONE'], $student['STUDENT_PERSONAL_PHONE']);
    addBiRowKey($pdf, 'height_cm', $student['STUDENT_HEIGHT_CM'], $student['STUDENT_HEIGHT_CM']);
    addBiRowKey($pdf, 'weight_kg', $student['STUDENT_WEIGHT_KG'], $student['STUDENT_WEIGHT_KG']);
    addBiRowKey($pdf, 'is_foreign', tr_lang(strtolower($student['STUDENT_IS_FOREIGN'] ?? 'no'), 'en'), tr_lang(strtolower($student['STUDENT_IS_FOREIGN'] ?? 'no'), 'ar'));
    addBiRowKey($pdf, 'health_status', $student['HEALTH_STATUS_EN'] ?? $health_status, $student['HEALTH_STATUS_AR'] ?? $health_status);
    addBiRowKey($pdf, 'birth_cert_number', $student['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER'], $student['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER']);
    addBiRowKey($pdf, 'id_card_number', $student['STUDENT_ID_CARD_NUMBER'], $student['STUDENT_ID_CARD_NUMBER']);
    addBiRowKey($pdf, 'postal_account', $student['STUDENT_POSTAL_ACCOUNT_NUMBER'], $student['STUDENT_POSTAL_ACCOUNT_NUMBER']);
    addBiRowKey($pdf, 'military_necklace', $student['STUDENT_MILITARY_NECKLACE'], $student['STUDENT_MILITARY_NECKLACE']);
    $pdf->Ln(5);

    // Section 2: Academic Details
    addBiSection($pdf, '2. ' . tr_lang('academic_details', 'en'), tr_lang('academic_details', 'ar'));
    addBiRowLabels($pdf, tr_lang('grade_rank', 'en'), tr_lang('grade_rank', 'ar'), $student['GRADE_NAME_EN'], $student['GRADE_NAME_AR']);
    addBiRowLabels($pdf, tr_lang('section', 'en'), tr_lang('section', 'ar'), $student['SECTION_NAME_EN'], $student['SECTION_NAME_AR']);
    addBiRowLabels($pdf, tr_lang('category', 'en'), tr_lang('category', 'ar'), $student['CATEGORY_NAME_EN'], $student['CATEGORY_NAME_AR']);
    addBiRowLabels($pdf, tr_lang('label_speciality', 'en'), tr_lang('label_speciality', 'ar'), $student['SPECIALITY_NAME_EN'], $student['SPECIALITY_NAME_AR']);
    addBiRowLabels($pdf, tr_lang('label_academic_level', 'en'), tr_lang('label_academic_level', 'ar'), $student['ACADEMIC_LEVEL_EN'], $student['ACADEMIC_LEVEL_AR']);
    addBiRowLabels($pdf, tr_lang('label_academic_average', 'en'), tr_lang('label_academic_average', 'ar'), $student['STUDENT_ACADEMIC_AVERAGE'], $student['STUDENT_ACADEMIC_AVERAGE']);
    addBiRowLabels($pdf, tr_lang('label_bac_number', 'en'), tr_lang('label_bac_number', 'ar'), $student['STUDENT_BACCALAUREATE_SUB_NUMBER'], $student['STUDENT_BACCALAUREATE_SUB_NUMBER']);
    addBiRowLabels($pdf, tr_lang('laptop_serial', 'en'), tr_lang('laptop_serial', 'ar'), $student['STUDENT_LAPTOP_SERIAL_NUMBER'], $student['STUDENT_LAPTOP_SERIAL_NUMBER']);
    addBiRowLabels($pdf, tr_lang('army', 'en'), tr_lang('army', 'ar'), $student['ARMY_NAME_EN'], $student['ARMY_NAME_AR']);
    $pdf->Ln(5);

    // Section 3: Family Information
    addBiSection($pdf, '3. ' . tr_lang('family_information', 'en'), tr_lang('family_information', 'ar'));
    addBiRowLabels(
        $pdf,
        tr_lang('father_name', 'en'),
        tr_lang('father_name', 'ar'),
        trim(($student['FATHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['FATHER_LAST_NAME_EN'] ?? '')),
        trim(($student['FATHER_FIRST_NAME_AR'] ?? '') . ' ' . ($student['FATHER_LAST_NAME_AR'] ?? ''))
    );
    addBiRowLabels(
        $pdf,
        tr_lang('father_profession', 'en'),
        tr_lang('father_profession', 'ar'),
        $student['FATHER_PROFESSION_EN'],
        $student['FATHER_PROFESSION_AR']
    );
    addBiRowLabels(
        $pdf,
        tr_lang('mother_name', 'en'),
        tr_lang('mother_name', 'ar'),
        trim(($student['MOTHER_FIRST_NAME_EN'] ?? '') . ' ' . ($student['MOTHER_LAST_NAME_EN'] ?? '')),
        trim(($student['MOTHER_FIRST_NAME_AR'] ?? '') . ' ' . ($student['MOTHER_LAST_NAME_AR'] ?? ''))
    );
    addBiRowLabels(
        $pdf,
        tr_lang('mother_profession', 'en'),
        tr_lang('mother_profession', 'ar'),
        $student['MOTHER_PROFESSION_EN'],
        $student['MOTHER_PROFESSION_AR']
    );
    addBiRowLabels($pdf, tr_lang('parents_situation', 'en'), tr_lang('parents_situation', 'ar'), tr_lang(strtolower($student['STUDENT_PARENTS_SITUATION'] ?? 'none'), 'en'), tr_lang(strtolower($student['STUDENT_PARENTS_SITUATION'] ?? 'none'), 'ar'));
    $orphan_map = ['None' => 'none', 'Father' => 'father', 'Mother' => 'mother', 'Both' => 'both'];
    $orphan_key2 = $orphan_map[$student['STUDENT_ORPHAN_STATUS'] ?? 'None'] ?? 'none';
    addBiRowLabels($pdf, tr_lang('orphan_status', 'en'), tr_lang('orphan_status', 'ar'), tr_lang('orphan_' . $orphan_key2, 'en'), tr_lang('orphan_' . $orphan_key2, 'ar'));
    addBiRowLabels($pdf, tr_lang('label_siblings_count', 'en'), tr_lang('label_siblings_count', 'ar'), $student['STUDENT_NUMBER_OF_SIBLINGS'], $student['STUDENT_NUMBER_OF_SIBLINGS']);
    addBiRowLabels($pdf, tr_lang('label_sisters_count', 'en'), tr_lang('label_sisters_count', 'ar'), $student['STUDENT_NUMBER_OF_SISTERS'], $student['STUDENT_NUMBER_OF_SISTERS']);
    addBiRowLabels($pdf, tr_lang('label_order_among_siblings', 'en'), tr_lang('label_order_among_siblings', 'ar'), $student['STUDENT_ORDER_AMONG_SIBLINGS'], $student['STUDENT_ORDER_AMONG_SIBLINGS']);
    $pdf->Ln(5);

    // Section 4: Addresses
    addBiSection($pdf, '4. ' . tr_lang('addresses', 'en'), tr_lang('addresses', 'ar'));
    addBiRowLabels($pdf, tr_lang('birth_place_country', 'en'), tr_lang('birth_place_country', 'ar'), $student['BP_COUNTRY_EN'], $student['BP_COUNTRY_AR']);
    addBiRowLabels($pdf, tr_lang('personal_address_country', 'en'), tr_lang('personal_address_country', 'ar'), $student['PERS_COUNTRY_EN'], $student['PERS_COUNTRY_AR']);
    $pdf->Ln(5);

    // Section 5: Emergency Contact
    addBiSection($pdf, '5. ' . tr_lang('emergency_contact', 'en'), tr_lang('emergency_contact', 'ar'));
    addBiRowLabels(
        $pdf,
        tr_lang('emergency_name', 'en'),
        tr_lang('emergency_name', 'ar'),
        trim(($student['CONTACT_FIRST_NAME_EN'] ?? '') . ' ' . ($student['CONTACT_LAST_NAME_EN'] ?? '')),
        trim(($student['CONTACT_FIRST_NAME_AR'] ?? '') . ' ' . ($student['CONTACT_LAST_NAME_AR'] ?? ''))
    );
    addBiRowLabels($pdf, tr_lang('emergency_relation', 'en'), tr_lang('emergency_relation', 'ar'), $student['CONTACT_RELATION_EN'], $student['CONTACT_RELATION_AR']);
    addBiRowLabels($pdf, tr_lang('label_phone', 'en'), tr_lang('label_phone', 'ar'), $student['EMG_PHONE'], $student['EMG_PHONE']);
    addBiRowLabels($pdf, tr_lang('label_consulate_number', 'en'), tr_lang('label_consulate_number', 'ar'), $student['CONSULATE_NUMBER'], $student['CONSULATE_NUMBER']);
    $pdf->Ln(5);

    // Section 6: Absences
    addBiSection($pdf, '6. ' . tr_lang('absences', 'en'), tr_lang('absences', 'ar'));
    // Period info under section header
    $pdf->SetFont($FONT_EN, 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $periodText = "($start_date → $end_date)";
    $pdf->Cell(0, 5, $periodText, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    if (empty($absences)) {
        writeBilingualNoData($pdf, tr_lang('no_absences_period', 'en'), tr_lang('no_absences_period', 'ar'));
    } else {
        // Table header with official styling
        $pdf->SetFont($FONT_EN, 'B', 9);
        writeBilingualHeaderCell($pdf, 45, 10, tr_lang('date', 'en'), tr_lang('date', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 50, 10, tr_lang('motif', 'en'), tr_lang('motif', 'ar'), 0, true);
        $remainingW = $pdf->getPageWidth() - $pdf->getMargins()['right'] - $pdf->GetX();
        writeBilingualHeaderCell($pdf, $remainingW, 10, tr_lang('note', 'en'), tr_lang('note', 'ar'), 1, true);
        $pdf->SetFont($FONT_EN, '', 9);
        
        $rowAlt = false;
        foreach ($absences as $a) {
            // Alternating row colors
            if ($rowAlt) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            $rowAlt = !$rowAlt;
            
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->Cell(45, 7, substr($a['ABSENCE_DATE_AND_TIME'], 0, 19), 1, 0, 'L', true);
            $pdf->Cell(50, 7, $a['ABSENCE_MOTIF'] ?: 'N/A', 1, 0, 'L', true);
            $pdf->Cell(0, 7, $a['ABSENCE_OBSERVATION'] ?: 'N/A', 1, 1, 'L', true);
        }
    }
    $pdf->Ln(5);

    // Section 7: Observations
    addBiSection($pdf, '7. ' . tr_lang('observations', 'en'), tr_lang('observations', 'ar'));
    if (empty($observations)) {
        writeBilingualNoData($pdf, tr_lang('no_observations', 'en'), tr_lang('no_observations', 'ar'));
    } else {
        $pdf->SetFont($FONT_EN, 'B', 9);
        writeBilingualHeaderCell($pdf, 35, 10, tr_lang('date', 'en'), tr_lang('date', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 35, 10, tr_lang('motif', 'en'), tr_lang('motif', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 35, 10, tr_lang('teacher_label', 'en'), tr_lang('teacher_label', 'ar'), 0, true);
        $remainingW = $pdf->getPageWidth() - $pdf->getMargins()['right'] - $pdf->GetX();
        writeBilingualHeaderCell($pdf, $remainingW, 10, tr_lang('note', 'en'), tr_lang('note', 'ar'), 1, true);
        $pdf->SetFont($FONT_EN, '', 9);
        
        $rowAlt = false;
        foreach ($observations as $o) {
            $teacherName = ($lang === 'ar' && !empty($o['TEACHER_FIRST_NAME_AR']))
                ? $o['TEACHER_FIRST_NAME_AR'] . ' ' . $o['TEACHER_LAST_NAME_AR']
                : $o['TEACHER_FIRST_NAME_EN'] . ' ' . $o['TEACHER_LAST_NAME_EN'];
            
            // Alternating row colors
            if ($rowAlt) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            $rowAlt = !$rowAlt;
            
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->Cell(35, 7, substr($o['OBSERVATION_DATE_AND_TIME'], 0, 19), 1, 0, 'L', true);
            $pdf->Cell(35, 7, $o['OBSERVATION_MOTIF'] ?: 'N/A', 1, 0, 'L', true);
            $pdf->Cell(35, 7, $teacherName, 1, 0, 'L', true);
            $pdf->Cell(0, 7, $o['OBSERVATION_NOTE'] ?: 'N/A', 1, 1, 'L', true);
        }
    }
    $pdf->Ln(5);

    // Section 8: Punishments
    addBiSection($pdf, '8. ' . tr_lang('punishments', 'en'), tr_lang('punishments', 'ar'));
    if (empty($punishments)) {
        writeBilingualNoData($pdf, tr_lang('no_punishments_period', 'en'), tr_lang('no_punishments_period', 'ar'));
    } else {
        $pdf->SetFont($FONT_EN, 'B', 9);
        writeBilingualHeaderCell($pdf, 45, 10, tr_lang('date', 'en'), tr_lang('date', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 50, 10, tr_lang('type', 'en'), tr_lang('type', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 0, 10, tr_lang('note', 'en'), tr_lang('note', 'ar'), 1, true);
        $pdf->SetFont($FONT_EN, '', 9);
        
        $rowAlt = false;
        foreach ($punishments as $p) {
            // Alternating row colors
            if ($rowAlt) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            $rowAlt = !$rowAlt;
            
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->Cell(45, 7, substr($p['PUNISHMENT_SUGGESTED_AT'], 0, 19), 1, 0, 'L', true);
            $pdf->Cell(50, 7, $p['PUNISHMENT_LABEL'] ?: 'N/A', 1, 0, 'L', true);
            $pdf->Cell(0, 7, $p['PUNISHMENT_NOTE'] ?: 'N/A', 1, 1, 'L', true);
        }
    }
    $pdf->Ln(5);

    // Section 9: Rewards
    addBiSection($pdf, '9. ' . tr_lang('rewards', 'en'), tr_lang('rewards', 'ar'));
    if (empty($rewards)) {
        writeBilingualNoData($pdf, tr_lang('no_rewards_period', 'en'), tr_lang('no_rewards_period', 'ar'));
    } else {
        $pdf->SetFont($FONT_EN, 'B', 9);
        writeBilingualHeaderCell($pdf, 45, 10, tr_lang('date', 'en'), tr_lang('date', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 50, 10, tr_lang('type', 'en'), tr_lang('type', 'ar'), 0, true);
        writeBilingualHeaderCell($pdf, 0, 10, tr_lang('note', 'en'), tr_lang('note', 'ar'), 1, true);
        $pdf->SetFont($FONT_EN, '', 9);
        
        $rowAlt = false;
        foreach ($rewards as $r) {
            // Alternating row colors
            if ($rowAlt) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            $rowAlt = !$rowAlt;
            
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->Cell(45, 7, substr($r['REWARD_SUGGESTED_AT'], 0, 19), 1, 0, 'L', true);
            $pdf->Cell(50, 7, $r['REWARD_LABEL'] ?: 'N/A', 1, 0, 'L', true);
            $pdf->Cell(0, 7, $r['REWARD_NOTE'] ?: 'N/A', 1, 1, 'L', true);
        }
    }
    
    
    // Output PDF
    $pdf->Output('student_report_' . $serial_number . '.pdf', 'D');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
