<?php
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'Secretary') {
    header("Location: index.php");
    exit;
}

// 2. Database Connection
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Fetch Secretary Name
$user_id = $_SESSION['user_id'];
$secretary_name = "Secretary";
$stmt = $conn->prepare("SELECT SECRETARY_FIRST_NAME_EN, SECRETARY_LAST_NAME_EN, SECRETARY_FIRST_NAME_AR, SECRETARY_LAST_NAME_AR FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($LANG === 'ar' && !empty($row['SECRETARY_FIRST_NAME_AR'])) {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_AR'] . ' ' . $row['SECRETARY_LAST_NAME_AR']);
    } else {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_EN'] . ' ' . $row['SECRETARY_LAST_NAME_EN']);
    }
}
$stmt->close();

// 4. Handle Form Submission
$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    
    // --- Helper Function: Insert Address ---
    function insertAddress($conn, $street_en, $street_ar, $country, $wilaya, $daira, $commune) {
        if ($country > 0) {
            $sql = "INSERT INTO address (ADDRESS_STREET_EN, ADDRESS_STREET_AR, COMMUNE_ID, DAIRA_ID, WILAYA_ID, COUNTRY_ID) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssiiii", $street_en, $street_ar, $commune, $daira, $wilaya, $country);
                if ($stmt->execute()) {
                    return $conn->insert_id;
                }
                $stmt->close();
            }
        }
        return null;
    }

    // --- A. Birth Place Address ---
    $birth_place_id = insertAddress($conn, 
        $_POST['BP_STREET_EN']??'', $_POST['BP_STREET_AR']??'', 
        intval($_POST['BP_COUNTRY_ID']??0), intval($_POST['BP_WILAYA_ID']??0), intval($_POST['BP_DAIRA_ID']??0), intval($_POST['BP_COMMUNE_ID']??0)
    );

    // --- B. Personal Address ---
    $personal_address_id = insertAddress($conn, 
        $_POST['PERS_STREET_EN']??'', $_POST['PERS_STREET_AR']??'', 
        intval($_POST['PERS_COUNTRY_ID']??0), intval($_POST['PERS_WILAYA_ID']??0), intval($_POST['PERS_DAIRA_ID']??0), intval($_POST['PERS_COMMUNE_ID']??0)
    );

    // --- C. Recruitment Source (Selected from DB, not inserted) ---
    $recruit_source_id = !empty($_POST['RECRUITMENT_SOURCE_ID']) ? intval($_POST['RECRUITMENT_SOURCE_ID']) : null;


    // --- D. Student Insertion Logic ---
    $serial = $_POST['STUDENT_SERIAL_NUMBER'] ?? '';
    
    // Foreign Keys
    $category_id = intval($_POST['CATEGORY_ID'] ?? 0);
    $section_id = intval($_POST['SECTION_ID'] ?? 0);
    $grade_id = !empty($_POST['STUDENT_GRADE_ID']) ? intval($_POST['STUDENT_GRADE_ID']) : null;
    
    // Basic Info
    $fname_en = $_POST['STUDENT_FIRST_NAME_EN'] ?? '';
    $lname_en = $_POST['STUDENT_LAST_NAME_EN'] ?? '';
    $fname_ar = $_POST['STUDENT_FIRST_NAME_AR'] ?? '';
    $lname_ar = $_POST['STUDENT_LAST_NAME_AR'] ?? '';
    $sex = $_POST['STUDENT_SEX'] ?? null;
    $birth_date = !empty($_POST['STUDENT_BIRTH_DATE']) ? $_POST['STUDENT_BIRTH_DATE'] : null;
    $blood_type = !empty($_POST['STUDENT_BLOOD_TYPE']) ? $_POST['STUDENT_BLOOD_TYPE'] : null;
    $phone = $_POST['STUDENT_PERSONAL_PHONE'] ?? '';
    
    // Physical & Status
    $height = !empty($_POST['STUDENT_HEIGHT_CM']) ? floatval($_POST['STUDENT_HEIGHT_CM']) : null;
    $weight = !empty($_POST['STUDENT_WEIGHT_KG']) ? floatval($_POST['STUDENT_WEIGHT_KG']) : null;
    $is_foreign = $_POST['STUDENT_IS_FOREIGN'] ?? 'No';
    
    // Academic
    $average = !empty($_POST['STUDENT_ACADEMIC_AVERAGE']) ? floatval($_POST['STUDENT_ACADEMIC_AVERAGE']) : null;
    $speciality = $_POST['STUDENT_SPECIALITY'] ?? '';
    $level = $_POST['STUDENT_ACADEMIC_LEVEL'] ?? '';
    $bac_num = $_POST['STUDENT_BACCALAUREATE_SUB_NUMBER'] ?? '';
    $edu_certs = $_POST['STUDENT_EDUCATIONAL_CERTIFICATES'] ?? '';
    $mil_certs = $_POST['STUDENT_MILITARY_CERTIFICATES'] ?? '';
    
    // School Admin
    $school_sub_date = !empty($_POST['STUDENT_SCHOOL_SUB_DATE']) ? $_POST['STUDENT_SCHOOL_SUB_DATE'] : null;
    $sub_card_num = $_POST['STUDENT_SCHOOL_SUB_CARD_NUMBER'] ?? '';
    $laptop_serial = $_POST['STUDENT_LAPTOP_SERIAL_NUMBER'] ?? '';
    
    // Documents
    $birth_cert_num = $_POST['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER'] ?? '';
    $id_card_num = $_POST['STUDENT_ID_CARD_NUMBER'] ?? '';
    $postal_num = $_POST['STUDENT_POSTAL_ACCOUNT_NUMBER'] ?? '';
    
    // Other
    $hobbies = $_POST['STUDENT_HOBBIES'] ?? '';
    $health = $_POST['STUDENT_HEALTH_STATUS'] ?? '';
    $mil_necklace = $_POST['STUDENT_MILITARY_NECKLACE'] ?? 'No';
    $siblings_cnt = !empty($_POST['STUDENT_NUMBER_OF_SIBLINGS']) ? intval($_POST['STUDENT_NUMBER_OF_SIBLINGS']) : null;
    $sisters_cnt = !empty($_POST['STUDENT_NUMBER_OF_SISTERS']) ? intval($_POST['STUDENT_NUMBER_OF_SISTERS']) : null;
    $order_siblings = !empty($_POST['STUDENT_ORDER_AMONG_SIBLINGS']) ? intval($_POST['STUDENT_ORDER_AMONG_SIBLINGS']) : null;
    $army_id = !empty($_POST['STUDENT_ARMY_ID']) ? intval($_POST['STUDENT_ARMY_ID']) : null;
    $orphan = $_POST['STUDENT_ORPHAN_STATUS'] ?? 'None';
    $parents = $_POST['STUDENT_PARENTS_SITUATION'] ?? 'Married';
    
    // Validation
    if (empty($serial) || empty($fname_en) || empty($lname_en) || $category_id <= 0 || $section_id <= 0) {
        $message = "Please fill in all required fields (Serial Number, Names, Category, Section).";
        $msg_type = "error";
    } else {
        $sql = "INSERT INTO student (
            STUDENT_SERIAL_NUMBER, CATEGORY_ID, SECTION_ID,
            STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN, STUDENT_FIRST_NAME_AR, STUDENT_LAST_NAME_AR,
            STUDENT_GRADE_ID, STUDENT_SEX, STUDENT_BIRTH_DATE, STUDENT_BLOOD_TYPE,
            STUDENT_PERSONAL_PHONE, STUDENT_HEIGHT_CM, STUDENT_WEIGHT_KG, STUDENT_IS_FOREIGN,
            STUDENT_ACADEMIC_AVERAGE, STUDENT_SPECIALITY, STUDENT_ACADEMIC_LEVEL, STUDENT_BACCALAUREATE_SUB_NUMBER,
            STUDENT_EDUCATIONAL_CERTIFICATES, STUDENT_MILITARY_CERTIFICATES,
            STUDENT_SCHOOL_SUB_DATE, STUDENT_SCHOOL_SUB_CARD_NUMBER, STUDENT_LAPTOP_SERIAL_NUMBER,
            STUDENT_BIRTHDATE_CERTIFICATE_NUMBER, STUDENT_ID_CARD_NUMBER, STUDENT_POSTAL_ACCOUNT_NUMBER,
            STUDENT_HOBBIES, STUDENT_HEALTH_STATUS, STUDENT_MILITARY_NECKLACE,
            STUDENT_NUMBER_OF_SIBLINGS, STUDENT_NUMBER_OF_SISTERS, STUDENT_ORDER_AMONG_SIBLINGS,
            STUDENT_ARMY_ID, STUDENT_ORPHAN_STATUS, STUDENT_PARENTS_SITUATION, STUDENT_PHOTO,
            STUDENT_BIRTH_PLACE_ID, STUDENT_PERSONAL_ADDRESS_ID, STUDENT_RECRUITMENT_SOURCE_ID
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
                $message = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                $msg_type = "error";
        } else {
            // Handle Photo
            $photo_path = null;
            if (isset($_FILES['STUDENT_PHOTO']) && $_FILES['STUDENT_PHOTO']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/resources/photos/';
                $file_extension = strtolower(pathinfo($_FILES['STUDENT_PHOTO']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $unique_filename = 'student_' . $serial . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_filename;
                    
                    if (move_uploaded_file($_FILES['STUDENT_PHOTO']['tmp_name'], $upload_path)) {
                        $photo_path = 'resources/photos/' . $unique_filename;
                    }
                }
            }

            // Build type string explicitly: 40 params (photo as string path)
            $types = "sii" . "ssss" . "i" . "ssss" . "dd" . "s" . "d" . "ssssssssssssss" . "iii" . "i" . "sss" . "iii";
            
            $stmt->bind_param($types, 
                $serial, $category_id, $section_id,
                $fname_en, $lname_en, $fname_ar, $lname_ar,
                $grade_id, $sex, $birth_date, $blood_type,
                $phone, $height, $weight, $is_foreign,
                $average, $speciality, $level, $bac_num,
                $edu_certs, $mil_certs,
                $school_sub_date, $sub_card_num, $laptop_serial,
                $birth_cert_num, $id_card_num, $postal_num,
                $hobbies, $health, $mil_necklace,
                $siblings_cnt, $sisters_cnt, $order_siblings,
                $army_id, $orphan, $parents, $photo_path,
                $birth_place_id, $personal_address_id, $recruit_source_id
            );

            if ($stmt->execute()) {
                // --- E. Insert Combat Outfit (uses the same serial number) ---
                $first_outfit_num = $_POST['FIRST_OUTFIT_NUMBER'] ?? '';
                $first_outfit_size = $_POST['FIRST_OUTFIT_SIZE'] ?? '';
                $second_outfit_num = $_POST['SECOND_OUTFIT_NUMBER'] ?? '';
                $second_outfit_size = $_POST['SECOND_OUTFIT_SIZE'] ?? '';
                $combat_shoe_size = $_POST['COMBAT_SHOE_SIZE'] ?? '';
                
                $sql_outfit = "INSERT INTO student_combat_outfit (STUDENT_SERIAL_NUMBER, FIRST_OUTFIT_NUMBER, FIRST_OUTFIT_SIZE, SECOND_OUTFIT_NUMBER, SECOND_OUTFIT_SIZE, COMBAT_SHOE_SIZE) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_outfit = $conn->prepare($sql_outfit);
                if ($stmt_outfit) {
                    $stmt_outfit->bind_param("ssssss", $serial, $first_outfit_num, $first_outfit_size, $second_outfit_num, $second_outfit_size, $combat_shoe_size);
                    $stmt_outfit->execute();
                    $stmt_outfit->close();
                }
                
                // --- F. Insert parade_uniform ---
                $summer_jacket = $_POST['SUMMER_JACKET_SIZE'] ?? '';
                $winter_jacket = $_POST['WINTER_JACKET_SIZE'] ?? '';
                $summer_trousers = $_POST['SUMMER_TROUSERS_SIZE'] ?? '';
                $winter_trousers = $_POST['WINTER_TROUSERS_SIZE'] ?? '';
                $summer_shirt = $_POST['SUMMER_SHIRT_SIZE'] ?? '';
                $winter_shirt = $_POST['WINTER_SHIRT_SIZE'] ?? '';
                $summer_hat = $_POST['SUMMER_HAT_SIZE'] ?? '';
                $winter_hat = $_POST['WINTER_HAT_SIZE'] ?? '';
                // Skirt sizes only for Female
                $summer_skirt = ($sex === 'Female') ? ($_POST['SUMMER_SKIRT_SIZE'] ?? '') : null;
                $winter_skirt = ($sex === 'Female') ? ($_POST['WINTER_SKIRT_SIZE'] ?? '') : null;
                
                $sql_parade = "INSERT INTO student_parade_uniform (STUDENT_SERIAL_NUMBER, SUMMER_JACKET_SIZE, WINTER_JACKET_SIZE, SUMMER_TROUSERS_SIZE, WINTER_TROUSERS_SIZE, SUMMER_SHIRT_SIZE, WINTER_SHIRT_SIZE, SUMMER_HAT_SIZE, WINTER_HAT_SIZE, SUMMER_SKIRT_SIZE, WINTER_SKIRT_SIZE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_parade = $conn->prepare($sql_parade);
                if ($stmt_parade) {
                    $stmt_parade->bind_param("sssssssssss", $serial, $summer_jacket, $winter_jacket, $summer_trousers, $winter_trousers, $summer_shirt, $winter_shirt, $summer_hat, $winter_hat, $summer_skirt, $winter_skirt);
                    $stmt_parade->execute();
                    $stmt_parade->close();
                }
                
                // --- G. Insert Parent Info ---
                $father_fname_en = $_POST['FATHER_FIRST_NAME_EN'] ?? '';
                $father_lname_en = $_POST['FATHER_LAST_NAME_EN'] ?? '';
                $father_fname_ar = $_POST['FATHER_FIRST_NAME_AR'] ?? '';
                $father_lname_ar = $_POST['FATHER_LAST_NAME_AR'] ?? '';
                $father_prof_en = $_POST['FATHER_PROFESSION_EN'] ?? '';
                $father_prof_ar = $_POST['FATHER_PROFESSION_AR'] ?? '';
                $mother_fname_en = $_POST['MOTHER_FIRST_NAME_EN'] ?? '';
                $mother_lname_en = $_POST['MOTHER_LAST_NAME_EN'] ?? '';
                $mother_fname_ar = $_POST['MOTHER_FIRST_NAME_AR'] ?? '';
                $mother_lname_ar = $_POST['MOTHER_LAST_NAME_AR'] ?? '';
                $mother_prof_en = $_POST['MOTHER_PROFESSION_EN'] ?? '';
                $mother_prof_ar = $_POST['MOTHER_PROFESSION_AR'] ?? '';
                
                $sql_parent = "INSERT INTO student_parent_info (STUDENT_SERIAL_NUMBER, FATHER_FIRST_NAME_EN, FATHER_LAST_NAME_EN, FATHER_FIRST_NAME_AR, FATHER_LAST_NAME_AR, FATHER_PROFESSION_EN, FATHER_PROFESSION_AR, MOTHER_FIRST_NAME_EN, MOTHER_LAST_NAME_EN, MOTHER_FIRST_NAME_AR, MOTHER_LAST_NAME_AR, MOTHER_PROFESSION_EN, MOTHER_PROFESSION_AR) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_parent = $conn->prepare($sql_parent);
                if ($stmt_parent) {
                    $stmt_parent->bind_param("sssssssssssss", $serial, $father_fname_en, $father_lname_en, $father_fname_ar, $father_lname_ar, $father_prof_en, $father_prof_ar, $mother_fname_en, $mother_lname_en, $mother_fname_ar, $mother_lname_ar, $mother_prof_en, $mother_prof_ar);
                    $stmt_parent->execute();
                    $stmt_parent->close();
                }
                // --- H. Insert Emergency Contact ---
                $is_foreign = $_POST['STUDENT_IS_FOREIGN'] ?? 'No';
                
                if ($is_foreign === 'No') {
                    // 1. Not Foreign: Insert Address first
                    $contact_street_en = $_POST['CONTACT_STREET_EN'] ?? '';
                    $contact_street_ar = $_POST['CONTACT_STREET_AR'] ?? '';
                    $contact_country_id = !empty($_POST['CONTACT_COUNTRY_ID']) ? intval($_POST['CONTACT_COUNTRY_ID']) : null;
                    $contact_wilaya_id = !empty($_POST['CONTACT_WILAYA_ID']) ? intval($_POST['CONTACT_WILAYA_ID']) : null;
                    $contact_daira_id = !empty($_POST['CONTACT_DAIRA_ID']) ? intval($_POST['CONTACT_DAIRA_ID']) : null;
                    $contact_commune_id = !empty($_POST['CONTACT_COMMUNE_ID']) ? intval($_POST['CONTACT_COMMUNE_ID']) : null;

                    $contact_address_id = null;
                    if ($contact_country_id) {
                        $sql_addr = "INSERT INTO address (ADDRESS_STREET_EN, ADDRESS_STREET_AR, COUNTRY_ID, WILAYA_ID, DAIRA_ID, COMMUNE_ID) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_addr = $conn->prepare($sql_addr);
                        if ($stmt_addr) {
                            $stmt_addr->bind_param("ssiiii", $contact_street_en, $contact_street_ar, $contact_country_id, $contact_wilaya_id, $contact_daira_id, $contact_commune_id);
                            if ($stmt_addr->execute()) {
                                $contact_address_id = $conn->insert_id;
                            }
                            $stmt_addr->close();
                        }
                    }

                    $contact_fname_en = $_POST['CONTACT_FIRST_NAME_EN'] ?? '';
                    $contact_lname_en = $_POST['CONTACT_LAST_NAME_EN'] ?? '';
                    $contact_fname_ar = $_POST['CONTACT_FIRST_NAME_AR'] ?? '';
                    $contact_lname_ar = $_POST['CONTACT_LAST_NAME_AR'] ?? '';
                    $contact_relation_en = $_POST['CONTACT_RELATION_EN'] ?? '';
                    $contact_relation_ar = $_POST['CONTACT_RELATION_AR'] ?? '';
                    $contact_phone = $_POST['CONTACT_PHONE_NUMBER'] ?? '';
                    $consulate_num = null; // No consulate number for locals

                } else {
                    // 2. Foreign: No address, Relation is "X's consulate"
                    $contact_address_id = null;
                    $contact_fname_en = null;
                    $contact_lname_en = null;
                    $contact_fname_ar = null;
                    $contact_lname_ar = null;
                    $contact_phone = $_POST['CONTACT_PHONE_NUMBER'] ?? '';
                    $consulate_num = $_POST['CONSULATE_NUMBER'] ?? '';
                    
                    // Fetch country name from BP_COUNTRY_ID
                    $bp_country_id = !empty($_POST['BP_COUNTRY_ID']) ? intval($_POST['BP_COUNTRY_ID']) : 0;
                    $country_name_en = "Unknown";
                    $country_name_ar = "غير معروف";
                    if ($bp_country_id) {
                        $res_c = $conn->query("SELECT COUNTRY_NAME_EN, COUNTRY_NAME_AR FROM country WHERE COUNTRY_ID = $bp_country_id");
                        if ($res_c && $row_c = $res_c->fetch_assoc()) {
                            $country_name_en = $row_c['COUNTRY_NAME_EN'];
                            $country_name_ar = $row_c['COUNTRY_NAME_AR'];
                        }
                    }
                    $contact_relation_en = $country_name_en . "'s consulate";
                    $contact_relation_ar = "قنصلية " . $country_name_ar;
                }

                $sql_emg = "INSERT INTO student_emergency_contact (
                    STUDENT_SERIAL_NUMBER, 
                    CONTACT_FIRST_NAME_EN, CONTACT_LAST_NAME_EN, 
                    CONTACT_FIRST_NAME_AR, CONTACT_LAST_NAME_AR,
                    CONTACT_RELATION_EN, CONTACT_RELATION_AR,
                    CONTACT_PHONE_NUMBER, CONTACT_ADDRESS_ID, CONSULATE_NUMBER
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_emg = $conn->prepare($sql_emg);
                if ($stmt_emg) {
                    $stmt_emg->bind_param("ssssssssis", 
                        $serial, 
                        $contact_fname_en, $contact_lname_en, 
                        $contact_fname_ar, $contact_lname_ar,
                        $contact_relation_en, $contact_relation_ar,
                        $contact_phone, $contact_address_id, $consulate_num
                    );
                    $stmt_emg->execute();
                    $stmt_emg->close();
                }


                
                $message = t('student_created_success');
                $msg_type = "success";
            } else {
                if ($conn->errno == 1062) {
                        $message = t('error_serial_exists', $serial);
                } else {
                        $message = t('error_insert', $stmt->error);
                }
                $msg_type = "error";
            }
            $stmt->close();
        }
    }
}

// 5. Fetch Dropdowns
$categories = [];
$res = $conn->query("SELECT CATEGORY_ID, CATEGORY_NAME_EN, CATEGORY_NAME_AR FROM category ORDER BY CATEGORY_NAME_EN");
while($r = $res->fetch_assoc()) $categories[] = $r;

$sections = [];
$res = $conn->query("SELECT SECTION_ID, SECTION_NAME_EN, SECTION_NAME_AR, CATEGORY_ID FROM section ORDER BY SECTION_NAME_EN");
while($r = $res->fetch_assoc()) $sections[] = $r;

// NEW: Fetch Grades
$grades = [];
$res = $conn->query("SELECT GRADE_ID, GRADE_NAME_EN, GRADE_NAME_AR FROM grade ORDER BY GRADE_NAME_EN");
while($r = $res->fetch_assoc()) $grades[] = $r;

// Countries (For Address)
$countries = [];
$res = $conn->query("SELECT COUNTRY_ID, COUNTRY_NAME_EN, COUNTRY_NAME_AR FROM country ORDER BY COUNTRY_NAME_EN");
while($r = $res->fetch_assoc()) $countries[] = $r;

// Recruitment Sources (Fetched from DB)
$recruitment_sources = [];
$res = $conn->query("SELECT RECRUITMENT_SOURCE_ID, RECRUITMENT_TYPE_EN, RECRUITMENT_TYPE_AR, ECN_SCHOOL_NAME_EN, ECN_SCHOOL_NAME_AR, ECN_SCHOOL_WILAYA_ID FROM recruitment_source ORDER BY RECRUITMENT_TYPE_EN, ECN_SCHOOL_NAME_EN");
if ($res) {
    while($r = $res->fetch_assoc()) $recruitment_sources[] = $r;
}

// Armies (Fetched from DB)
$armies = [];
$res = $conn->query("SELECT ARMY_ID, ARMY_NAME_EN, ARMY_NAME_AR FROM army ORDER BY ARMY_NAME_EN");
if ($res) {
    while($r = $res->fetch_assoc()) $armies[] = $r;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('insert_student'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .home-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .form-card {
            background: var(--bg-primary); border: 1px solid var(--border-color);
            border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md);
        }
        .form-section-title {
            grid-column: 1 / -1; font-size: 1.1rem; font-weight: 700;
            color: var(--primary-color); margin-top: 1rem; margin-bottom: 0.5rem;
            border-bottom: 2px solid var(--bg-secondary); padding-bottom: 0.5rem;
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: var(--text-primary); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.6rem; border: 1px solid var(--border-color);
            border-radius: var(--radius-md); font-size: 0.95rem; background: var(--bg-secondary);
        }
        .btn-submit {
            grid-column: 1 / -1; margin-top: 2rem; background: var(--primary-color);
            color: white; padding: 1rem; border: none; border-radius: var(--radius-md);
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
        }
        .btn-submit:hover { background: var(--primary-hover); }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .sub-group { padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; grid-column: 1 / -1; }
        .emergency-section {
            background: #fffafa; border: 1px solid #fecaca; border-radius: 12px;
            padding: 1.5rem; grid-column: 1 / -1; margin-top: 2rem;
            position: relative;
        }
        .emergency-section::before {
            content: '🚨'; position: absolute; top: -15px; left: 20px;
            font-size: 1.5rem; background: white; padding: 0 5px;
        }
        .emergency-section .form-section-title {
             color: #dc2626; border-color: #fee2e2; margin-top: 0;
        }

        /* Wizard */
        .wizard-steps {
            display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem;
            padding: 0.75rem; background: var(--bg-secondary); border-radius: var(--radius-lg);
        }
        .wizard-step-dot {
            width: 2rem; height: 2rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 700; color: #6b7280; background: #e5e7eb;
            cursor: pointer; transition: all 0.2s;
        }
        .wizard-step-dot:hover { background: #d1d5db; color: #374151; }
        .wizard-step-dot.active { background: var(--primary-color); color: white; }
        .wizard-step-dot.done { background: #10b981; color: white; }
        .wizard-panels { position: relative; min-height: 320px; }
        .wizard-panel { display: none; }
        .wizard-panel.active { display: block; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .wizard-actions {
            display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 1.5rem;
            border-top: 1px solid var(--border-color); gap: 1rem; flex-wrap: wrap;
        }
        .wizard-actions .btn-prev, .wizard-actions .btn-next {
            padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;
            border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary);
        }
        .wizard-actions .btn-prev:hover, .wizard-actions .btn-next:hover { background: #e5e7eb; }
        .wizard-actions .btn-next { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .wizard-actions .btn-next:hover { background: var(--primary-hover); }
        .wizard-actions .btn-submit { margin-left: auto; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="home-container">
    <div style="margin-bottom: 2rem;">
        <h1><?php echo t('welcome_secretary', htmlspecialchars($secretary_name)); ?></h1>
        <p style="color: var(--text-secondary);"><?php echo t('register_student_sub'); ?></p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="secretary_home.php" id="wizardForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_student">
            <div class="wizard-steps">
                <div class="wizard-step-dot active" data-step="1" title="<?php echo t('step_personal_details'); ?>">1</div>
                <div class="wizard-step-dot" data-step="2" title="<?php echo t('step_academic_info'); ?>">2</div>
                <div class="wizard-step-dot" data-step="3" title="<?php echo t('step_family_info'); ?>">3</div>
                <div class="wizard-step-dot" data-step="4" title="<?php echo t('step_addresses'); ?>">4</div>
                <div class="wizard-step-dot" data-step="5" title="<?php echo t('step_uniforms_labels'); ?>">5</div>
                <div class="wizard-step-dot" data-step="6" title="<?php echo t('step_other_details'); ?>">6</div>
                <div class="wizard-step-dot" data-step="7" title="<?php echo t('step_emergency_contact'); ?>">7</div>
            </div>
            <div class="wizard-panels">
                <div class="wizard-panel active" data-step="1">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('step_personal_details'); ?></div>
                        
                        <div class="form-group">
                            <label><?php echo t('serial_number'); ?></label>
                            <input type="text" name="STUDENT_SERIAL_NUMBER" required placeholder="<?php echo t('serial_placeholder'); ?>">
                        </div>

                        <!-- STUDENT PHOTO UPLOAD -->
                        <div class="form-group">
                            <label><?php echo t('label_student_photo'); ?></label>
                            <input type="file" name="STUDENT_PHOTO" accept="image/*">
                        </div>

                        <div class="form-group"><label><?php echo t('label_first_name_en'); ?></label><input type="text" name="STUDENT_FIRST_NAME_EN" required></div>
                        <div class="form-group"><label><?php echo t('label_last_name_en'); ?></label><input type="text" name="STUDENT_LAST_NAME_EN" required></div>
                        <div class="form-group"><label><?php echo t('label_first_name_ar'); ?></label><input type="text" name="STUDENT_FIRST_NAME_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_last_name_ar'); ?></label><input type="text" name="STUDENT_LAST_NAME_AR" dir="rtl"></div>
                        
                        <div class="form-group"><label><?php echo t('label_sex'); ?></label>
                            <select id="SEX_SELECT" name="STUDENT_SEX" onchange="toggleSkirtFields()">
                                <option value=""><?php echo t('select'); ?></option>
                                <option value="Male"><?php echo t('male'); ?></option>
                                <option value="Female"><?php echo t('female'); ?></option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('label_birth_date'); ?></label><input type="date" name="STUDENT_BIRTH_DATE"></div>
                        <div class="form-group"><label><?php echo t('label_phone'); ?></label><input type="text" name="STUDENT_PERSONAL_PHONE"></div>
                        <div class="form-group"><label><?php echo t('label_blood_type'); ?></label>
                            <select name="STUDENT_BLOOD_TYPE">
                                <option value=""><?php echo t('select'); ?></option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('label_height'); ?></label><input type="number" step="0.01" name="STUDENT_HEIGHT_CM"></div>
                        <div class="form-group"><label><?php echo t('label_weight'); ?></label><input type="number" step="0.01" name="STUDENT_WEIGHT_KG"></div>
                        <div class="form-group"><label><?php echo t('label_is_foreign'); ?></label>
                           <select id="IS_FOREIGN_SELECT" name="STUDENT_IS_FOREIGN" onchange="toggleForeignFields()">
                               <option value="No"><?php echo t('no'); ?></option>
                               <option value="Yes"><?php echo t('yes'); ?></option>
                           </select>
                        </div>
                    </div>
                </div>
                <div class="wizard-panel" data-step="2">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('step_academic_info'); ?></div>
                        <div class="form-group">
                            <label><?php echo t('category'); ?></label>
                            <select id="CATEGORY_ID" name="CATEGORY_ID" required>
                                <option value=""><?php echo t('select'); ?></option>
                                <?php foreach ($categories as $c): ?>
                                    <?php $catName = ($LANG === 'ar' && !empty($c['CATEGORY_NAME_AR'])) ? $c['CATEGORY_NAME_AR'] : $c['CATEGORY_NAME_EN']; ?>
                                    <option value="<?php echo $c['CATEGORY_ID']; ?>"><?php echo htmlspecialchars($catName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="SECTION_GROUP">
                            <label><?php echo t('section'); ?></label>
                            <select id="SECTION_ID" name="SECTION_ID" required disabled>
                                <option value=""><?php echo t('select'); ?></option>
                                <?php foreach ($sections as $s): ?>
                                    <?php $secName = ($LANG === 'ar' && !empty($s['SECTION_NAME_AR'])) ? $s['SECTION_NAME_AR'] : $s['SECTION_NAME_EN']; ?>
                                    <option value="<?php echo $s['SECTION_ID']; ?>" data-category="<?php echo $s['CATEGORY_ID']; ?>">
                                        <?php echo htmlspecialchars($secName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('speciality'); ?></label><input type="text" name="STUDENT_SPECIALITY"></div>
                        <div class="form-group"><label><?php echo t('academic_level'); ?></label><input type="text" name="STUDENT_ACADEMIC_LEVEL"></div>
                        <div class="form-group"><label><?php echo t('academic_average'); ?></label><input type="number" step="0.01" name="STUDENT_ACADEMIC_AVERAGE"></div>
                        <div class="form-group"><label><?php echo t('bac_number'); ?></label><input type="text" name="STUDENT_BACCALAUREATE_SUB_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('grade_rank'); ?></label>
                            <select name="STUDENT_GRADE_ID">
                                <option value=""><?php echo t('select_grade'); ?></option>
                                <?php foreach ($grades as $g): ?>
                                    <?php 
                                        $gradeName = ($LANG === 'ar' && !empty($g['GRADE_NAME_AR'])) ? $g['GRADE_NAME_AR'] : $g['GRADE_NAME_EN'];
                                    ?>
                                    <option value="<?php echo $g['GRADE_ID']; ?>"><?php echo htmlspecialchars($gradeName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('recruitment_source'); ?></label>
                            <select id="RECRUITMENT_SOURCE_ID" name="STUDENT_RECRUITMENT_SOURCE_ID">
                                <option value=""><?php echo t('select_recruitment'); ?></option>
                                <?php foreach ($recruitment_sources as $rs): ?>
                                    <?php 
                                        $label = ($LANG === 'ar' ? ($rs['RECRUITMENT_TYPE_AR'] ?? $rs['RECRUITMENT_TYPE_EN']) : $rs['RECRUITMENT_TYPE_EN']);
                                        $school = ($LANG === 'ar' ? ($rs['ECN_SCHOOL_NAME_AR'] ?? $rs['ECN_SCHOOL_NAME_EN']) : $rs['ECN_SCHOOL_NAME_EN']);
                                        if ($school) $label .= ' - ' . $school;
                                    ?>
                                    <option value="<?php echo $rs['RECRUITMENT_SOURCE_ID']; ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('army'); ?></label>
                            <select name="STUDENT_ARMY_ID">
                                <option value=""><?php echo t('select_army'); ?></option>
                                <?php foreach ($armies as $a): ?>
                                    <?php $armyName = ($LANG === 'ar' && !empty($a['ARMY_NAME_AR'])) ? $a['ARMY_NAME_AR'] : $a['ARMY_NAME_EN']; ?>
                                    <option value="<?php echo $a['ARMY_ID']; ?>"><?php echo htmlspecialchars($armyName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="wizard-panel" data-step="3">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('family_information'); ?></div>
                        <div class="form-group"><label><?php echo t('label_father_first_en'); ?></label><input type="text" name="FATHER_FIRST_NAME_EN"></div>
                        <div class="form-group"><label><?php echo t('label_father_last_en'); ?></label><input type="text" name="FATHER_LAST_NAME_EN"></div>
                        <div class="form-group"><label><?php echo t('label_father_first_ar'); ?></label><input type="text" name="FATHER_FIRST_NAME_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_father_last_ar'); ?></label><input type="text" name="FATHER_LAST_NAME_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_father_prof_en'); ?></label><input type="text" name="FATHER_PROFESSION_EN"></div>
                        <div class="form-group"><label><?php echo t('label_father_prof_ar'); ?></label><input type="text" name="FATHER_PROFESSION_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_mother_first_en'); ?></label><input type="text" name="MOTHER_FIRST_NAME_EN"></div>
                        <div class="form-group"><label><?php echo t('label_mother_last_en'); ?></label><input type="text" name="MOTHER_LAST_NAME_EN"></div>
                        <div class="form-group"><label><?php echo t('label_mother_first_ar'); ?></label><input type="text" name="MOTHER_FIRST_NAME_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_mother_last_ar'); ?></label><input type="text" name="MOTHER_LAST_NAME_AR" dir="rtl"></div>
                        <div class="form-group"><label><?php echo t('label_mother_prof_en'); ?></label><input type="text" name="MOTHER_PROFESSION_EN"></div>
                        <div class="form-group"><label><?php echo t('label_mother_prof_ar'); ?></label><input type="text" name="MOTHER_PROFESSION_AR" dir="rtl"></div>
                        
                        <div class="form-group"><label><?php echo t('orphan_status'); ?></label>
                            <select name="STUDENT_ORPHAN_STATUS">
                                <option value="None"><?php echo t('orphan_none'); ?></option>
                                <option value="Father"><?php echo t('orphan_father'); ?></option>
                                <option value="Mother"><?php echo t('orphan_mother'); ?></option>
                                <option value="Both"><?php echo t('orphan_both'); ?></option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('parents_situation'); ?></label>
                            <select name="STUDENT_PARENTS_SITUATION">
                                <option value="Married"><?php echo t('married'); ?></option>
                                <option value="Divorced"><?php echo t('divorced'); ?></option>
                                <option value="Separated"><?php echo t('separated'); ?></option>
                                <option value="Widowed"><?php echo t('widowed'); ?></option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php echo t('num_siblings'); ?></label><input type="number" name="STUDENT_NUMBER_OF_SIBLINGS"></div>
                        <div class="form-group"><label><?php echo t('label_sisters_count'); ?></label><input type="number" name="STUDENT_NUMBER_OF_SISTERS"></div>
                        <div class="form-group"><label><?php echo t('label_order_among_siblings'); ?></label><input type="number" name="STUDENT_ORDER_AMONG_SIBLINGS"></div>
                    </div>
                </div>

                <div class="wizard-panel" data-step="4">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('label_personal_address'); ?> & <?php echo t('label_birth_place_address'); ?></div>
                        
                        <!-- BIRTH PLACE ADDRESS -->
                        <div class="sub-group" style="grid-column: 1 / -1;">
                            <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_birth_place_address'); ?></label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="BP_STREET_EN" placeholder="<?php echo t('placeholder_street'); ?>"></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="BP_STREET_AR" dir="rtl" placeholder="<?php echo t('placeholder_street'); ?>"></div>
                                <div class="form-group">
                                    <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                    <select class="country-select" data-prefix="BP_" name="BP_COUNTRY_ID">
                                        <option value=""><?php echo t('option_select_country'); ?></option>
                                        <?php foreach ($countries as $c): ?>
                                            <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                            <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($countryName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label><select id="BP_WILAYA_ID" name="BP_WILAYA_ID" class="wilaya-select" data-prefix="BP_" disabled><option value=""><?php echo t('option_select_wilaya_first'); ?></option></select></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label><select id="BP_DAIRA_ID" name="BP_DAIRA_ID" class="daira-select" data-prefix="BP_" disabled><option value=""><?php echo t('option_select_daira_first'); ?></option></select></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label><select id="BP_COMMUNE_ID" name="BP_COMMUNE_ID" disabled><option value=""><?php echo t('option_select_commune_first'); ?></option></select></div>
                            </div>
                        </div>

                        <!-- PERSONAL ADDRESS -->
                        <div class="sub-group" style="grid-column: 1 / -1;">
                            <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_personal_address'); ?></label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="PERS_STREET_EN" placeholder="<?php echo t('placeholder_street'); ?>"></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="PERS_STREET_AR" dir="rtl" placeholder="<?php echo t('placeholder_street'); ?>"></div>
                                <div class="form-group">
                                    <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                    <select class="country-select" data-prefix="PERS_" name="PERS_COUNTRY_ID">
                                        <option value=""><?php echo t('option_select_country'); ?></option>
                                        <?php foreach ($countries as $c): ?>
                                            <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                            <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($countryName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label><select id="PERS_WILAYA_ID" name="PERS_WILAYA_ID" class="wilaya-select" data-prefix="PERS_" disabled><option value=""><?php echo t('option_select_wilaya_first'); ?></option></select></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label><select id="PERS_DAIRA_ID" name="PERS_DAIRA_ID" class="daira-select" data-prefix="PERS_" disabled><option value=""><?php echo t('option_select_daira_first'); ?></option></select></div>
                                <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label><select id="PERS_COMMUNE_ID" name="PERS_COMMUNE_ID" disabled><option value=""><?php echo t('option_select_commune_first'); ?></option></select></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wizard-panel" data-step="5">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('combat_outfit'); ?></div>
                        <div class="form-group"><label><?php echo t('outfit1_number'); ?></label><input type="text" name="FIRST_OUTFIT_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('outfit1_size'); ?></label><input type="text" name="FIRST_OUTFIT_SIZE" placeholder="<?php echo t('outfit_size_placeholder'); ?>"></div>
                        <div class="form-group"><label><?php echo t('outfit2_number'); ?></label><input type="text" name="SECOND_OUTFIT_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('outfit2_size'); ?></label><input type="text" name="SECOND_OUTFIT_SIZE" placeholder="<?php echo t('outfit_size_placeholder'); ?>"></div>
                        <div class="form-group"><label><?php echo t('shoe_size'); ?></label><input type="text" name="COMBAT_SHOE_SIZE" placeholder="<?php echo t('shoe_placeholder'); ?>"></div>

                        <div class="form-section-title"><?php echo t('parade_uniform'); ?></div>
                        <div class="form-group"><label><?php echo t('summer_jacket_size'); ?></label><input type="text" name="SUMMER_JACKET_SIZE"></div>
                        <div class="form-group"><label><?php echo t('winter_jacket_size'); ?></label><input type="text" name="WINTER_JACKET_SIZE"></div>
                        <div class="form-group"><label><?php echo t('summer_trousers_size'); ?></label><input type="text" name="SUMMER_TROUSERS_SIZE"></div>
                        <div class="form-group"><label><?php echo t('winter_trousers_size'); ?></label><input type="text" name="WINTER_TROUSERS_SIZE"></div>
                        <div class="form-group"><label><?php echo t('summer_shirt_size'); ?></label><input type="text" name="SUMMER_SHIRT_SIZE"></div>
                        <div class="form-group"><label><?php echo t('winter_shirt_size'); ?></label><input type="text" name="WINTER_SHIRT_SIZE"></div>
                        <div class="form-group"><label><?php echo t('summer_hat_size'); ?></label><input type="text" name="SUMMER_HAT_SIZE"></div>
                        <div class="form-group"><label><?php echo t('winter_hat_size'); ?></label><input type="text" name="WINTER_HAT_SIZE"></div>
                        <div class="form-group skirt-field" style="display:none;"><label><?php echo t('summer_skirt_size'); ?></label><input type="text" name="SUMMER_SKIRT_SIZE"></div>
                        <div class="form-group skirt-field" style="display:none;"><label><?php echo t('winter_skirt_size'); ?></label><input type="text" name="WINTER_SKIRT_SIZE"></div>
                    </div>
                </div>

                <div class="wizard-panel" data-step="6">
                    <div class="form-grid">
                        <div class="form-section-title"><?php echo t('step_other_details'); ?></div>
                        <div class="form-group"><label><?php echo t('id_card_num'); ?></label><input type="text" name="STUDENT_ID_CARD_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('birth_cert_num'); ?></label><input type="text" name="STUDENT_BIRTHDATE_CERTIFICATE_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('school_card_number'); ?></label><input type="text" name="STUDENT_SCHOOL_SUB_CARD_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('school_sub_date'); ?></label><input type="date" name="STUDENT_SCHOOL_SUB_DATE"></div>
                        <div class="form-group"><label><?php echo t('laptop_serial'); ?></label><input type="text" name="STUDENT_LAPTOP_SERIAL_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('postal_account'); ?></label><input type="text" name="STUDENT_POSTAL_ACCOUNT_NUMBER"></div>
                        <div class="form-group"><label><?php echo t('label_mil_necklace'); ?></label>
                            <select name="STUDENT_MILITARY_NECKLACE">
                                <option value="No"><?php echo t('no'); ?></option>
                                <option value="Yes"><?php echo t('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 2;"><label><?php echo t('educational_certificates'); ?></label><textarea name="STUDENT_EDUCATIONAL_CERTIFICATES" rows="2"></textarea></div>
                        <div class="form-group" style="grid-column: span 2;"><label><?php echo t('military_certificates'); ?></label><textarea name="STUDENT_MILITARY_CERTIFICATES" rows="2"></textarea></div>
                        <div class="form-group" style="grid-column: span 2;"><label><?php echo t('hobbies'); ?></label><textarea name="STUDENT_HOBBIES" rows="2"></textarea></div>
                        <div class="form-group" style="grid-column: span 2;"><label><?php echo t('health_status'); ?></label><textarea name="STUDENT_HEALTH_STATUS" rows="2"></textarea></div>
                    </div>
                </div>

                <div class="wizard-panel" data-step="7">
                    <div class="form-grid">
                        <div class="emergency-section">
                             <div class="form-section-title"><?php echo t('step_emergency_contact'); ?></div>
                             <div class="form-grid" style="gap: 1.5rem;">
                                 <div class="form-group"><label><?php echo t('label_contact_phone'); ?></label><input type="text" name="CONTACT_PHONE_NUMBER"></div>
                                 
                                 <div id="LOCAL_CONTACT_FIELDS" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                     <div class="form-group"><label><?php echo t('label_first_name_en'); ?></label><input type="text" name="CONTACT_FIRST_NAME_EN"></div>
                                     <div class="form-group"><label><?php echo t('label_last_name_en'); ?></label><input type="text" name="CONTACT_LAST_NAME_EN"></div>
                                     <div class="form-group"><label><?php echo t('label_relation_en'); ?></label><input type="text" name="CONTACT_RELATION_EN" placeholder="<?php echo t('contact_relation_placeholder'); ?>"></div>
                                     <div class="form-group"><label><?php echo t('label_first_name_ar'); ?></label><input type="text" name="CONTACT_FIRST_NAME_AR" dir="rtl"></div>
                                     <div class="form-group"><label><?php echo t('label_last_name_ar'); ?></label><input type="text" name="CONTACT_LAST_NAME_AR" dir="rtl"></div>
                                     <div class="form-group"><label><?php echo t('label_relation_ar'); ?></label><input type="text" name="CONTACT_RELATION_AR" dir="rtl" placeholder="<?php echo t('contact_relation_ar_placeholder'); ?>"></div>
                                     
                                     <div class="sub-group" style="grid-column: 1 / -1;">
                                        <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_contact_address'); ?></label>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                            <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="CONTACT_STREET_EN"></div>
                                            <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="CONTACT_STREET_AR" dir="rtl"></div>
                                            <div class="form-group">
                                                <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                                <select class="country-select" data-prefix="CONTACT_" name="CONTACT_COUNTRY_ID">
                                                    <option value=""><?php echo t('option_select_country'); ?></option>
                                                    <?php foreach ($countries as $c): ?>
                                                        <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                        <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($countryName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label><select id="CONTACT_WILAYA_ID" name="CONTACT_WILAYA_ID" class="wilaya-select" data-prefix="CONTACT_" disabled><option value=""><?php echo t('option_select_country_first'); ?></option></select></div>
                                            <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label><select id="CONTACT_DAIRA_ID" name="CONTACT_DAIRA_ID" class="daira-select" data-prefix="CONTACT_" disabled><option value=""><?php echo t('option_select_wilaya_first'); ?></option></select></div>
                                            <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label><select id="CONTACT_COMMUNE_ID" name="CONTACT_COMMUNE_ID" disabled><option value=""><?php echo t('option_select_daira_first'); ?></option></select></div>
                                        </div>
                                     </div>
                                 </div>
            
                                 <div id="FOREIGN_CONTACT_FIELDS" style="grid-column: 1 / -1; display:none; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                     <div class="form-group"><label><?php echo t('label_consulate_number'); ?></label><input type="text" name="CONSULATE_NUMBER"></div>
                                     <div class="form-group" style="grid-column: span 2;">
                                        <p style="font-size:0.9rem; color:#666;"><?php echo t('relation_consulate_note'); ?></p>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn-prev" id="wizardPrev"><?php echo t('previous'); ?></button>
                <button type="button" class="btn-next" id="wizardNext"><?php echo t('next'); ?></button>
                <button type="submit" class="btn-submit" id="wizardSubmit" style="display:none;"><?php echo t('add_student_record'); ?></button>
            </div>
        </form>
    </div>
</div>

</div>
</div>
</div>

<script>
var T = <?php echo json_encode($T); ?>;
// Toggle ECN Section
function toggleECN() {
    const type = document.getElementById('RECRUITMENT_SOURCE_ID').value;
    const section = document.getElementById('ECN_SECTION');
    if (type === 'ECN') {
        section.style.display = 'grid';
    } else {
        section.style.display = 'none';
    }
}

// Toggle Skirt Fields (Female only)
function toggleSkirtFields() {
    const sex = document.getElementById('SEX_SELECT').value;
    const skirtFields = document.querySelectorAll('.skirt-field');
    skirtFields.forEach(field => {
        field.style.display = (sex === 'Female') ? 'block' : 'none';
    });
}

// Toggle Foreign Fields
function toggleForeignFields() {
    const isForeign = document.getElementById('IS_FOREIGN_SELECT').value;
    const localFields = document.getElementById('LOCAL_CONTACT_FIELDS');
    const foreignFields = document.getElementById('FOREIGN_CONTACT_FIELDS');
    
    if (isForeign === 'Yes') {
        localFields.style.display = 'none';
        foreignFields.style.display = 'grid'; // grid or block
        foreignFields.style.gridTemplateColumns = '1fr 1fr';
        foreignFields.style.gap = '1rem';
    } else {
        localFields.style.display = 'grid';
        foreignFields.style.display = 'none';
    }
}

// --- Wizard ---
const TOTAL_STEPS = 7;
let currentStep = 1;

function showStep(step) {
    currentStep = step;
    document.querySelectorAll('.wizard-panel').forEach(p => { p.classList.remove('active'); });
    document.querySelectorAll('.wizard-step-dot').forEach(d => { d.classList.remove('active','done'); });
    const panel = document.querySelector('.wizard-panel[data-step="' + step + '"]');
    if (panel) panel.classList.add('active');
    const dot = document.querySelector('.wizard-step-dot[data-step="' + step + '"]');
    if (dot) dot.classList.add('active');
    document.querySelectorAll('.wizard-step-dot').forEach(d => {
        const n = parseInt(d.getAttribute('data-step'), 10);
        if (n < step) d.classList.add('done');
    });
    document.getElementById('wizardPrev').style.display = (step === 1) ? 'none' : 'inline-block';
    document.getElementById('wizardNext').style.display = (step === TOTAL_STEPS) ? 'none' : 'inline-block';
    document.getElementById('wizardSubmit').style.display = (step === TOTAL_STEPS) ? 'inline-block' : 'none';
    if (step === 5) toggleSkirtFields();
    if (step === 7) toggleForeignFields();
}

document.getElementById('wizardPrev').onclick = function() { if (currentStep > 1) showStep(currentStep - 1); };
document.getElementById('wizardNext').onclick = function() { if (currentStep < TOTAL_STEPS) showStep(currentStep + 1); };
document.querySelectorAll('.wizard-step-dot').forEach(d => {
    d.addEventListener('click', function() { showStep(parseInt(this.getAttribute('data-step'), 10)); });
});
showStep(1);

// Cascading Locations (Generic)
function fetchLocations(type, parentParam, parentId, targetSelect, placeholder) {
    targetSelect.innerHTML = '<option value="">' + (T.loading || 'Loading...') + '</option>';
    targetSelect.disabled = true;
    
    fetch(`get_locations.php?type=${type}&${parentParam}=${parentId}`)
        .then(res => res.json())
        .then(data => {
            targetSelect.innerHTML = `<option value="">${placeholder}</option>`;
            data.forEach(item => {
                let idKey, nameKey;
                if(type === 'wilayas') { idKey = 'WILAYA_ID'; nameKey = 'WILAYA_NAME_EN'; }
                else if(type === 'dairas') { idKey = 'DAIRA_ID'; nameKey = 'DAIRA_NAME_EN'; }
                else if(type === 'communes') { idKey = 'COMMUNE_ID'; nameKey = 'COMMUNE_NAME_EN'; }
                
                const opt = document.createElement('option');
                opt.value = item[idKey];
                opt.textContent = item[nameKey];
                targetSelect.appendChild(opt);
            });
            targetSelect.disabled = false;
        })
        .catch(err => {
            console.error(err);
            targetSelect.innerHTML = '<option value="">' + (T.error_loading || 'Error or None') + '</option>';
        });
}

// Bind Events for country/wilaya/daira/commune classes
document.querySelectorAll('.country-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const wilayaSel = document.getElementById(prefix + 'WILAYA_ID');
        if(this.value) fetchLocations('wilayas', 'country_id', this.value, wilayaSel, T.select_wilaya_opt || 'Select Wilaya...');
    });
});

document.querySelectorAll('.wilaya-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const dairaSel = document.getElementById(prefix + 'DAIRA_ID');
        if(this.value) fetchLocations('dairas', 'wilaya_id', this.value, dairaSel, T.select_daira_opt || 'Select Daira...');
    });
});

document.querySelectorAll('.daira-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const communeSel = document.getElementById(prefix + 'COMMUNE_ID');
        if(this.value) fetchLocations('communes', 'daira_id', this.value, communeSel, T.select_commune_opt || 'Select Commune...');
    });
});
// Category-Section Filtering
function updateSections() {
    const categoryId = document.getElementById('CATEGORY_ID').value;
    const sectionGroup = document.getElementById('SECTION_GROUP');
    const sectionSelect = document.getElementById('SECTION_ID');
    const options = sectionSelect.querySelectorAll('option[data-category]');

    if (categoryId) {
        sectionSelect.disabled = false;
        
        // Filter options
        options.forEach(opt => {
            if (opt.getAttribute('data-category') === categoryId) {
                opt.hidden = false;
                opt.disabled = false;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        });
        
        // If current value is not in the new category, reset it
        const currentOption = sectionSelect.options[sectionSelect.selectedIndex];
        if (currentOption && currentOption.getAttribute('data-category') !== categoryId && currentOption.value !== "") {
            sectionSelect.value = "";
        }
    } else {
        sectionSelect.disabled = true;
        sectionSelect.value = "";
    }
}

document.getElementById('CATEGORY_ID').addEventListener('change', updateSections);
// Initialize on load
updateSections();
</script>

</body>
</html>
