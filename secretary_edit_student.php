<?php
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'Secretary') {
    header("Location: index.php");
    exit;
}

$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

function upsertAddress($conn, $existingId, $street_en, $street_ar, $country, $wilaya, $daira, $commune) {
    $country = intval($country);
    $wilaya = intval($wilaya);
    $daira = intval($daira);
    $commune = intval($commune);

    if ($country <= 0) {
        return null;
    }

    if (!empty($existingId)) {
        $addressId = intval($existingId);
        $sql = "UPDATE address SET ADDRESS_STREET_EN = ?, ADDRESS_STREET_AR = ?, COMMUNE_ID = ?, DAIRA_ID = ?, WILAYA_ID = ?, COUNTRY_ID = ? WHERE ADDRESS_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssiiiii", $street_en, $street_ar, $commune, $daira, $wilaya, $country, $addressId);
            $stmt->execute();
            $stmt->close();
            return $addressId;
        }
        return $addressId;
    }

    $sql = "INSERT INTO address (ADDRESS_STREET_EN, ADDRESS_STREET_AR, COMMUNE_ID, DAIRA_ID, WILAYA_ID, COUNTRY_ID) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssiiii", $street_en, $street_ar, $commune, $daira, $wilaya, $country);
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            return $newId;
        }
        $stmt->close();
    }

    return null;
}

$message = "";
$msg_type = "";

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = "Student updated successfully.";
    $msg_type = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
    $originalSerial = trim($_POST['ORIGINAL_STUDENT_SERIAL_NUMBER'] ?? ($_GET['serial'] ?? ''));
    $serial = trim($_POST['STUDENT_SERIAL_NUMBER'] ?? '');

    if ($serial === '') {
        $message = "Serial Number is required.";
        $msg_type = "error";
        goto after_update;
    }

    if (!preg_match('/^\d+$/', $serial)) {
        $message = "Serial Number must contain digits only.";
        $msg_type = "error";
        goto after_update;
    }

    if ($originalSerial === '') {
        $message = "Serial Number is required.";
        $msg_type = "error";
        goto after_update;
    }

    $stmtExists = $conn->prepare("SELECT STUDENT_SERIAL_NUMBER, STUDENT_BIRTH_PLACE_ID, STUDENT_PERSONAL_ADDRESS_ID FROM student WHERE STUDENT_SERIAL_NUMBER = ?");
    $stmtExists->bind_param("s", $originalSerial);
    $stmtExists->execute();
    $resExists = $stmtExists->get_result();
    $existingStudent = $resExists->fetch_assoc();
    $stmtExists->close();

    if (!$existingStudent) {
        $message = "Student not found.";
        $msg_type = "error";
        goto after_update;
    }

    $conn->begin_transaction();

    try {
        if ($serial !== $originalSerial) {
            $stmtCheck = $conn->prepare("SELECT 1 FROM student WHERE STUDENT_SERIAL_NUMBER = ? LIMIT 1");
            if ($stmtCheck) {
                $stmtCheck->bind_param("s", $serial);
                $stmtCheck->execute();
                $stmtCheck->store_result();
                $exists = ($stmtCheck->num_rows > 0);
                $stmtCheck->close();
                if ($exists) {
                    throw new Exception("This serial number already exists.");
                }
            }

            $stmtDelCombat = $conn->prepare("DELETE FROM student_combat_outfit WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtDelCombat) {
                $stmtDelCombat->bind_param("s", $originalSerial);
                $stmtDelCombat->execute();
                $stmtDelCombat->close();
            }
        }

        $birth_place_id = upsertAddress(
            $conn,
            $existingStudent['STUDENT_BIRTH_PLACE_ID'] ?? null,
            $_POST['BP_STREET_EN'] ?? '',
            $_POST['BP_STREET_AR'] ?? '',
            $_POST['BP_COUNTRY_ID'] ?? 0,
            $_POST['BP_WILAYA_ID'] ?? 0,
            $_POST['BP_DAIRA_ID'] ?? 0,
            $_POST['BP_COMMUNE_ID'] ?? 0
        );

        $personal_address_id = upsertAddress(
            $conn,
            $existingStudent['STUDENT_PERSONAL_ADDRESS_ID'] ?? null,
            $_POST['PERS_STREET_EN'] ?? '',
            $_POST['PERS_STREET_AR'] ?? '',
            $_POST['PERS_COUNTRY_ID'] ?? 0,
            $_POST['PERS_WILAYA_ID'] ?? 0,
            $_POST['PERS_DAIRA_ID'] ?? 0,
            $_POST['PERS_COMMUNE_ID'] ?? 0
        );

        $recruit_source_id = !empty($_POST['RECRUITMENT_SOURCE_ID']) ? intval($_POST['RECRUITMENT_SOURCE_ID']) : null;
        $category_id = intval($_POST['CATEGORY_ID'] ?? 0);
        $section_id = intval($_POST['SECTION_ID'] ?? 0);
        $grade_id = !empty($_POST['STUDENT_GRADE_ID']) ? intval($_POST['STUDENT_GRADE_ID']) : null;
        $army_id = !empty($_POST['STUDENT_ARMY_ID']) ? intval($_POST['STUDENT_ARMY_ID']) : null;

        if ($category_id <= 0 || $section_id <= 0) {
            throw new Exception("Category and Section are required.");
        }

        $photo_path = 'resources\\photos\\students\\' . $serial . '.jpg';

        $sqlStudent = "UPDATE student SET
            STUDENT_SERIAL_NUMBER = ?,
            CATEGORY_ID = ?,
            SECTION_ID = ?,
            STUDENT_FIRST_NAME_EN = ?,
            STUDENT_LAST_NAME_EN = ?,
            STUDENT_FIRST_NAME_AR = ?,
            STUDENT_LAST_NAME_AR = ?,
            STUDENT_GRADE_ID = ?,
            STUDENT_SEX = ?,
            STUDENT_BIRTH_DATE = ?,
            STUDENT_BLOOD_TYPE = ?,
            STUDENT_PERSONAL_PHONE = ?,
            STUDENT_HEIGHT_CM = ?,
            STUDENT_WEIGHT_KG = ?,
            STUDENT_IS_FOREIGN = ?,
            STUDENT_ACADEMIC_AVERAGE = ?,
            STUDENT_SPECIALITY_ID = ?,
            STUDENT_ACADEMIC_LEVEL_ID = ?,
            STUDENT_BACCALAUREATE_SUB_NUMBER = ?,
            STUDENT_EDUCATIONAL_CERTIFICATES = ?,
            STUDENT_SCHOOL_SUB_DATE = ?,
            STUDENT_SCHOOL_SUB_CARD_NUMBER = ?,
            STUDENT_LAPTOP_SERIAL_NUMBER = ?,
            STUDENT_BIRTHDATE_CERTIFICATE_NUMBER = ?,
            STUDENT_ID_CARD_NUMBER = ?,
            STUDENT_POSTAL_ACCOUNT_NUMBER = ?,
            STUDENT_HEALTH_STATUS_ID = ?,
            STUDENT_MILITARY_NECKLACE = ?,
            STUDENT_NUMBER_OF_SIBLINGS = ?,
            STUDENT_NUMBER_OF_SISTERS = ?,
            STUDENT_ORDER_AMONG_SIBLINGS = ?,
            STUDENT_ARMY_ID = ?,
            STUDENT_ORPHAN_STATUS = ?,
            STUDENT_PARENTS_SITUATION = ?,
            STUDENT_BIRTH_PLACE_ID = ?,
            STUDENT_PERSONAL_ADDRESS_ID = ?,
            STUDENT_RECRUITMENT_SOURCE_ID = ?,
            STUDENT_PHOTO = ?
            WHERE STUDENT_SERIAL_NUMBER = ?";

        $stmtStudent = $conn->prepare($sqlStudent);
        if (!$stmtStudent) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $fname_en = $_POST['STUDENT_FIRST_NAME_EN'] ?? '';
        $lname_en = $_POST['STUDENT_LAST_NAME_EN'] ?? '';
        $fname_ar = $_POST['STUDENT_FIRST_NAME_AR'] ?? '';
        $lname_ar = $_POST['STUDENT_LAST_NAME_AR'] ?? '';
        $sex = !empty($_POST['STUDENT_SEX']) ? $_POST['STUDENT_SEX'] : null;
        $birth_date = !empty($_POST['STUDENT_BIRTH_DATE']) ? $_POST['STUDENT_BIRTH_DATE'] : null;
        $blood_type = !empty($_POST['STUDENT_BLOOD_TYPE']) ? $_POST['STUDENT_BLOOD_TYPE'] : null;
        $phone = $_POST['STUDENT_PERSONAL_PHONE'] ?? '';
        $height = (isset($_POST['STUDENT_HEIGHT_CM']) && $_POST['STUDENT_HEIGHT_CM'] !== '') ? floatval($_POST['STUDENT_HEIGHT_CM']) : null;
        $weight = (isset($_POST['STUDENT_WEIGHT_KG']) && $_POST['STUDENT_WEIGHT_KG'] !== '') ? floatval($_POST['STUDENT_WEIGHT_KG']) : null;
        $is_foreign = $_POST['STUDENT_IS_FOREIGN'] ?? 'No';
        $average = (isset($_POST['STUDENT_ACADEMIC_AVERAGE']) && $_POST['STUDENT_ACADEMIC_AVERAGE'] !== '') ? floatval($_POST['STUDENT_ACADEMIC_AVERAGE']) : null;
        $speciality_id = !empty($_POST['STUDENT_SPECIALITY_ID']) ? intval($_POST['STUDENT_SPECIALITY_ID']) : null;
        $academic_level_id = !empty($_POST['STUDENT_ACADEMIC_LEVEL_ID']) ? intval($_POST['STUDENT_ACADEMIC_LEVEL_ID']) : null;
        $bac_num = $_POST['STUDENT_BACCALAUREATE_SUB_NUMBER'] ?? '';
        $edu_certs = $_POST['STUDENT_EDUCATIONAL_CERTIFICATES'] ?? '';
        $military_certificate_ids = $_POST['MILITARY_CERTIFICATE_IDS'] ?? [];
        if (!is_array($military_certificate_ids)) {
            $military_certificate_ids = [];
        }
        $school_sub_date = !empty($_POST['STUDENT_SCHOOL_SUB_DATE']) ? $_POST['STUDENT_SCHOOL_SUB_DATE'] : null;
        $sub_card_num = $_POST['STUDENT_SCHOOL_SUB_CARD_NUMBER'] ?? '';
        $laptop_serial = $_POST['STUDENT_LAPTOP_SERIAL_NUMBER'] ?? '';
        $birth_cert_num = $_POST['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER'] ?? '';
        $id_card_num = $_POST['STUDENT_ID_CARD_NUMBER'] ?? '';
        $postal_num = $_POST['STUDENT_POSTAL_ACCOUNT_NUMBER'] ?? '';
        $hobby_ids = $_POST['HOBBY_IDS'] ?? [];
        if (!is_array($hobby_ids)) {
            $hobby_ids = [];
        }
        $health_status_id = !empty($_POST['STUDENT_HEALTH_STATUS_ID']) ? intval($_POST['STUDENT_HEALTH_STATUS_ID']) : null;
        $mil_necklace = $_POST['STUDENT_MILITARY_NECKLACE'] ?? 'No';
        $siblings_cnt = (isset($_POST['STUDENT_NUMBER_OF_SIBLINGS']) && $_POST['STUDENT_NUMBER_OF_SIBLINGS'] !== '') ? intval($_POST['STUDENT_NUMBER_OF_SIBLINGS']) : null;
        $sisters_cnt = (isset($_POST['STUDENT_NUMBER_OF_SISTERS']) && $_POST['STUDENT_NUMBER_OF_SISTERS'] !== '') ? intval($_POST['STUDENT_NUMBER_OF_SISTERS']) : null;
        $order_siblings = (isset($_POST['STUDENT_ORDER_AMONG_SIBLINGS']) && $_POST['STUDENT_ORDER_AMONG_SIBLINGS'] !== '') ? intval($_POST['STUDENT_ORDER_AMONG_SIBLINGS']) : null;
        $orphan = $_POST['STUDENT_ORPHAN_STATUS'] ?? 'None';
        $parents = $_POST['STUDENT_PARENTS_SITUATION'] ?? 'Married';

        $typesStudent = "s" . "ii" . "ssss" . "i" . "ssss" . "dd" . "s" . "d" . "ii" . str_repeat('s', 8) . "i" . "s" . "iiii" . "ss" . "iii" . "ss";
        $stmtStudent->bind_param(
            $typesStudent,
            $serial,
            $category_id,
            $section_id,
            $fname_en,
            $lname_en,
            $fname_ar,
            $lname_ar,
            $grade_id,
            $sex,
            $birth_date,
            $blood_type,
            $phone,
            $height,
            $weight,
            $is_foreign,
            $average,
            $speciality_id,
            $academic_level_id,
            $bac_num,
            $edu_certs,
            $school_sub_date,
            $sub_card_num,
            $laptop_serial,
            $birth_cert_num,
            $id_card_num,
            $postal_num,
            $health_status_id,
            $mil_necklace,
            $siblings_cnt,
            $sisters_cnt,
            $order_siblings,
            $army_id,
            $orphan,
            $parents,
            $birth_place_id,
            $personal_address_id,
            $recruit_source_id,
            $photo_path,
            $originalSerial
        );
        $stmtStudent->execute();
        $stmtStudent->close();

        if ($serial !== $originalSerial) {
            $mapTables = [
                ['student_parade_uniform', 'STUDENT_SERIAL_NUMBER'],
                ['student_parent_info', 'STUDENT_SERIAL_NUMBER'],
                ['student_emergency_contact', 'STUDENT_SERIAL_NUMBER'],
                ['student_military_certificate', 'student_serial_number'],
                ['student_hobby', 'student_serial_number'],
                ['student_gets_absent', 'STUDENT_SERIAL_NUMBER'],
                ['teacher_makes_an_observation_for_a_student', 'STUDENT_SERIAL_NUMBER']
            ];
            foreach ($mapTables as $t) {
                $table = $t[0];
                $col = $t[1];
                $sql = "UPDATE {$table} SET {$col} = ? WHERE {$col} = ?";
                $st = $conn->prepare($sql);
                if ($st) {
                    $st->bind_param("ss", $serial, $originalSerial);
                    $st->execute();
                    $st->close();
                }
            }
        }

        $stmtDelMc = $conn->prepare("DELETE FROM student_military_certificate WHERE student_serial_number = ?");
        if ($stmtDelMc) {
            $stmtDelMc->bind_param("s", $serial);
            $stmtDelMc->execute();
            $stmtDelMc->close();
        }

        if (!empty($military_certificate_ids)) {
            $stmtInsMc = $conn->prepare("INSERT INTO student_military_certificate (student_serial_number, military_certificate_id) VALUES (?, ?)");
            if ($stmtInsMc) {
                foreach ($military_certificate_ids as $mcid) {
                    if ($mcid === '' || $mcid === null) continue;
                    $mcid = intval($mcid);
                    $stmtInsMc->bind_param("si", $serial, $mcid);
                    $stmtInsMc->execute();
                }
                $stmtInsMc->close();
            }
        }

        $stmtDelHobby = $conn->prepare("DELETE FROM student_hobby WHERE student_serial_number = ?");
        if ($stmtDelHobby) {
            $stmtDelHobby->bind_param("s", $serial);
            $stmtDelHobby->execute();
            $stmtDelHobby->close();
        }

        if (!empty($hobby_ids)) {
            $stmtInsHobby = $conn->prepare("INSERT INTO student_hobby (student_serial_number, hobby_id) VALUES (?, ?)");
            if ($stmtInsHobby) {
                foreach ($hobby_ids as $hid) {
                    if ($hid === '' || $hid === null) continue;
                    $hid = intval($hid);
                    $stmtInsHobby->bind_param("si", $serial, $hid);
                    $stmtInsHobby->execute();
                }
                $stmtInsHobby->close();
            }
        }

        $sqlOutfit = "INSERT INTO student_combat_outfit (STUDENT_SERIAL_NUMBER, FIRST_OUTFIT_NUMBER, FIRST_OUTFIT_SIZE, SECOND_OUTFIT_NUMBER, SECOND_OUTFIT_SIZE, COMBAT_SHOE_SIZE)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                FIRST_OUTFIT_NUMBER = VALUES(FIRST_OUTFIT_NUMBER),
                FIRST_OUTFIT_SIZE = VALUES(FIRST_OUTFIT_SIZE),
                SECOND_OUTFIT_NUMBER = VALUES(SECOND_OUTFIT_NUMBER),
                SECOND_OUTFIT_SIZE = VALUES(SECOND_OUTFIT_SIZE),
                COMBAT_SHOE_SIZE = VALUES(COMBAT_SHOE_SIZE)";
        $stmtOutfit = $conn->prepare($sqlOutfit);
        if ($stmtOutfit) {
            $first_outfit_number = $_POST['FIRST_OUTFIT_NUMBER'] ?? '';
            $first_outfit_size = $_POST['FIRST_OUTFIT_SIZE'] ?? '';
            $second_outfit_number = $_POST['SECOND_OUTFIT_NUMBER'] ?? '';
            $second_outfit_size = $_POST['SECOND_OUTFIT_SIZE'] ?? '';
            $combat_shoe_size = $_POST['COMBAT_SHOE_SIZE'] ?? '';
            $stmtOutfit->bind_param(
                "ssssss",
                $serial,
                $first_outfit_number,
                $first_outfit_size,
                $second_outfit_number,
                $second_outfit_size,
                $combat_shoe_size
            );
            $stmtOutfit->execute();
            $stmtOutfit->close();
        }

        $summer_skirt = ($sex === 'Female') ? ($_POST['SUMMER_SKIRT_SIZE'] ?? '') : null;
        $winter_skirt = ($sex === 'Female') ? ($_POST['WINTER_SKIRT_SIZE'] ?? '') : null;

        $sqlParade = "INSERT INTO student_parade_uniform (
                STUDENT_SERIAL_NUMBER, SUMMER_JACKET_SIZE, WINTER_JACKET_SIZE, SUMMER_TROUSERS_SIZE, WINTER_TROUSERS_SIZE,
                SUMMER_SHIRT_SIZE, WINTER_SHIRT_SIZE, SUMMER_HAT_SIZE, WINTER_HAT_SIZE, SUMMER_SKIRT_SIZE, WINTER_SKIRT_SIZE
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                SUMMER_JACKET_SIZE = VALUES(SUMMER_JACKET_SIZE),
                WINTER_JACKET_SIZE = VALUES(WINTER_JACKET_SIZE),
                SUMMER_TROUSERS_SIZE = VALUES(SUMMER_TROUSERS_SIZE),
                WINTER_TROUSERS_SIZE = VALUES(WINTER_TROUSERS_SIZE),
                SUMMER_SHIRT_SIZE = VALUES(SUMMER_SHIRT_SIZE),
                WINTER_SHIRT_SIZE = VALUES(WINTER_SHIRT_SIZE),
                SUMMER_HAT_SIZE = VALUES(SUMMER_HAT_SIZE),
                WINTER_HAT_SIZE = VALUES(WINTER_HAT_SIZE),
                SUMMER_SKIRT_SIZE = VALUES(SUMMER_SKIRT_SIZE),
                WINTER_SKIRT_SIZE = VALUES(WINTER_SKIRT_SIZE)";
        $stmtParade = $conn->prepare($sqlParade);
        if ($stmtParade) {
            $summer_jacket = $_POST['SUMMER_JACKET_SIZE'] ?? '';
            $winter_jacket = $_POST['WINTER_JACKET_SIZE'] ?? '';
            $summer_trousers = $_POST['SUMMER_TROUSERS_SIZE'] ?? '';
            $winter_trousers = $_POST['WINTER_TROUSERS_SIZE'] ?? '';
            $summer_shirt = $_POST['SUMMER_SHIRT_SIZE'] ?? '';
            $winter_shirt = $_POST['WINTER_SHIRT_SIZE'] ?? '';
            $summer_hat = $_POST['SUMMER_HAT_SIZE'] ?? '';
            $winter_hat = $_POST['WINTER_HAT_SIZE'] ?? '';
            $stmtParade->bind_param(
                "sssssssssss",
                $serial,
                $summer_jacket,
                $winter_jacket,
                $summer_trousers,
                $winter_trousers,
                $summer_shirt,
                $winter_shirt,
                $summer_hat,
                $winter_hat,
                $summer_skirt,
                $winter_skirt
            );
            $stmtParade->execute();
            $stmtParade->close();
        }

        $sqlParent = "INSERT INTO student_parent_info (
                STUDENT_SERIAL_NUMBER,
                FATHER_FIRST_NAME_EN, FATHER_LAST_NAME_EN, FATHER_FIRST_NAME_AR, FATHER_LAST_NAME_AR,
                FATHER_PROFESSION_ID,
                MOTHER_FIRST_NAME_EN, MOTHER_LAST_NAME_EN, MOTHER_FIRST_NAME_AR, MOTHER_LAST_NAME_AR,
                MOTHER_PROFESSION_ID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                FATHER_FIRST_NAME_EN = VALUES(FATHER_FIRST_NAME_EN),
                FATHER_LAST_NAME_EN = VALUES(FATHER_LAST_NAME_EN),
                FATHER_FIRST_NAME_AR = VALUES(FATHER_FIRST_NAME_AR),
                FATHER_LAST_NAME_AR = VALUES(FATHER_LAST_NAME_AR),
                FATHER_PROFESSION_ID = VALUES(FATHER_PROFESSION_ID),
                MOTHER_FIRST_NAME_EN = VALUES(MOTHER_FIRST_NAME_EN),
                MOTHER_LAST_NAME_EN = VALUES(MOTHER_LAST_NAME_EN),
                MOTHER_FIRST_NAME_AR = VALUES(MOTHER_FIRST_NAME_AR),
                MOTHER_LAST_NAME_AR = VALUES(MOTHER_LAST_NAME_AR),
                MOTHER_PROFESSION_ID = VALUES(MOTHER_PROFESSION_ID)";
        $stmtParent = $conn->prepare($sqlParent);
        if ($stmtParent) {
            $father_first_en = $_POST['FATHER_FIRST_NAME_EN'] ?? '';
            $father_last_en = $_POST['FATHER_LAST_NAME_EN'] ?? '';
            $father_first_ar = $_POST['FATHER_FIRST_NAME_AR'] ?? '';
            $father_last_ar = $_POST['FATHER_LAST_NAME_AR'] ?? '';
            $father_profession_id = !empty($_POST['FATHER_PROFESSION_ID']) ? intval($_POST['FATHER_PROFESSION_ID']) : null;
            $mother_first_en = $_POST['MOTHER_FIRST_NAME_EN'] ?? '';
            $mother_last_en = $_POST['MOTHER_LAST_NAME_EN'] ?? '';
            $mother_first_ar = $_POST['MOTHER_FIRST_NAME_AR'] ?? '';
            $mother_last_ar = $_POST['MOTHER_LAST_NAME_AR'] ?? '';
            $mother_profession_id = !empty($_POST['MOTHER_PROFESSION_ID']) ? intval($_POST['MOTHER_PROFESSION_ID']) : null;
            $stmtParent->bind_param(
                "sssssissssi",
                $serial,
                $father_first_en,
                $father_last_en,
                $father_first_ar,
                $father_last_ar,
                $father_profession_id,
                $mother_first_en,
                $mother_last_en,
                $mother_first_ar,
                $mother_last_ar,
                $mother_profession_id
            );
            $stmtParent->execute();
            $stmtParent->close();
        }

        $stmtEmgExisting = $conn->prepare("SELECT EMERGENCY_CONTACT_ID, CONTACT_ADDRESS_ID FROM student_emergency_contact WHERE STUDENT_SERIAL_NUMBER = ? ORDER BY EMERGENCY_CONTACT_ID ASC LIMIT 1");
        $stmtEmgExisting->bind_param("s", $serial);
        $stmtEmgExisting->execute();
        $resEmgExisting = $stmtEmgExisting->get_result();
        $existingEmg = $resEmgExisting->fetch_assoc();
        $stmtEmgExisting->close();

        $consulate_num = null;
        $contact_address_id = null;
        $contact_fname_en = null;
        $contact_lname_en = null;
        $contact_fname_ar = null;
        $contact_lname_ar = null;
        $contact_relation_id = null;
        $contact_phone = $_POST['CONTACT_PHONE_NUMBER'] ?? '';

        if ($is_foreign === 'No') {
            $contact_fname_en = $_POST['CONTACT_FIRST_NAME_EN'] ?? '';
            $contact_lname_en = $_POST['CONTACT_LAST_NAME_EN'] ?? '';
            $contact_fname_ar = $_POST['CONTACT_FIRST_NAME_AR'] ?? '';
            $contact_lname_ar = $_POST['CONTACT_LAST_NAME_AR'] ?? '';
            $contact_relation_id = !empty($_POST['CONTACT_RELATION_ID']) ? intval($_POST['CONTACT_RELATION_ID']) : null;

            $contact_address_id = upsertAddress(
                $conn,
                $existingEmg['CONTACT_ADDRESS_ID'] ?? null,
                $_POST['CONTACT_STREET_EN'] ?? '',
                $_POST['CONTACT_STREET_AR'] ?? '',
                $_POST['CONTACT_COUNTRY_ID'] ?? 0,
                $_POST['CONTACT_WILAYA_ID'] ?? 0,
                $_POST['CONTACT_DAIRA_ID'] ?? 0,
                $_POST['CONTACT_COMMUNE_ID'] ?? 0
            );
        } else {
            $consulate_num = $_POST['CONSULATE_NUMBER'] ?? '';

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
        }

        if (!empty($existingEmg) && !empty($existingEmg['EMERGENCY_CONTACT_ID'])) {
            $emgId = intval($existingEmg['EMERGENCY_CONTACT_ID']);
            $sqlEmgUpdate = "UPDATE student_emergency_contact SET
                CONTACT_FIRST_NAME_EN = ?, CONTACT_LAST_NAME_EN = ?,
                CONTACT_FIRST_NAME_AR = ?, CONTACT_LAST_NAME_AR = ?,
                CONTACT_RELATION_ID = ?,
                CONTACT_PHONE_NUMBER = ?, CONTACT_ADDRESS_ID = ?, CONSULATE_NUMBER = ?
                WHERE EMERGENCY_CONTACT_ID = ?";
            $stmtEmg = $conn->prepare($sqlEmgUpdate);
            if ($stmtEmg) {
                $stmtEmg->bind_param(
                    "ssssisisi",
                    $contact_fname_en,
                    $contact_lname_en,
                    $contact_fname_ar,
                    $contact_lname_ar,
                    $contact_relation_id,
                    $contact_phone,
                    $contact_address_id,
                    $consulate_num,
                    $emgId
                );
                $stmtEmg->execute();
                $stmtEmg->close();
            }
        } else {
            $sqlEmgInsert = "INSERT INTO student_emergency_contact (
                STUDENT_SERIAL_NUMBER,
                CONTACT_FIRST_NAME_EN, CONTACT_LAST_NAME_EN,
                CONTACT_FIRST_NAME_AR, CONTACT_LAST_NAME_AR,
                CONTACT_RELATION_ID,
                CONTACT_PHONE_NUMBER, CONTACT_ADDRESS_ID, CONSULATE_NUMBER
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtEmg = $conn->prepare($sqlEmgInsert);
            if ($stmtEmg) {
                $stmtEmg->bind_param(
                    "sssssisis",
                    $serial,
                    $contact_fname_en,
                    $contact_lname_en,
                    $contact_fname_ar,
                    $contact_lname_ar,
                    $contact_relation_id,
                    $contact_phone,
                    $contact_address_id,
                    $consulate_num
                );
                $stmtEmg->execute();
                $stmtEmg->close();
            }
        }

        $conn->commit();
        header('Location: secretary_edit_student.php?serial=' . urlencode($serial) . '&updated=1');
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        $message = $e->getMessage();
        $msg_type = "error";
    }
}

after_update:

$categories = [];
$res = $conn->query("SELECT CATEGORY_ID, CATEGORY_NAME_EN, CATEGORY_NAME_AR FROM category ORDER BY CATEGORY_NAME_EN");
while($r = $res->fetch_assoc()) $categories[] = $r;

$sections = [];
$res = $conn->query("SELECT SECTION_ID, SECTION_NAME_EN, SECTION_NAME_AR, CATEGORY_ID FROM section ORDER BY SECTION_NAME_EN");
while($r = $res->fetch_assoc()) $sections[] = $r;

$grades = [];
$res = $conn->query("SELECT GRADE_ID, GRADE_NAME_EN, GRADE_NAME_AR FROM grade ORDER BY GRADE_NAME_EN");
while($r = $res->fetch_assoc()) $grades[] = $r;

$specialities = [];
$res = $conn->query("SELECT student_speciality_id, speciality_name_en, speciality_name_ar FROM student_speciality ORDER BY speciality_name_en");
if ($res) {
    while($r = $res->fetch_assoc()) $specialities[] = $r;
}

$academic_levels = [];
$res = $conn->query("SELECT ACADEMIC_LEVEL_ID, ACADEMIC_LEVEL_EN, ACADEMIC_LEVEL_AR FROM academic_level ORDER BY ACADEMIC_LEVEL_EN");
if ($res) {
    while($r = $res->fetch_assoc()) $academic_levels[] = $r;
}

$professions = [];
$res = $conn->query("SELECT profession_id, profession_name_en, profession_name_ar FROM profession ORDER BY profession_name_en");
if ($res) {
    while($r = $res->fetch_assoc()) $professions[] = $r;
}

$military_certificates = [];
$res = $conn->query("SELECT military_certificate_id, military_certificate_en, military_certificate_ar FROM military_certificate ORDER BY military_certificate_en");
if ($res) {
    while($r = $res->fetch_assoc()) $military_certificates[] = $r;
}

$hobbies = [];
$res = $conn->query("SELECT hobby_id, hobby_name_en, hobby_name_ar FROM hobby ORDER BY hobby_name_en");
if ($res) {
    while($r = $res->fetch_assoc()) $hobbies[] = $r;
}

$health_statuses = [];
$res = $conn->query("SELECT health_status_id, health_status_en, health_status_ar FROM health_status ORDER BY health_status_en");
if ($res) {
    while($r = $res->fetch_assoc()) $health_statuses[] = $r;
}

$relations = [];
$res = $conn->query("SELECT relation_id, relation_name_en, relation_name_ar FROM relation ORDER BY relation_name_en");
if ($res) {
    while($r = $res->fetch_assoc()) $relations[] = $r;
}

$countries = [];
$res = $conn->query("SELECT COUNTRY_ID, COUNTRY_NAME_EN, COUNTRY_NAME_AR FROM country ORDER BY COUNTRY_NAME_EN");
while($r = $res->fetch_assoc()) $countries[] = $r;

$recruitment_sources = [];
$res = $conn->query("SELECT RECRUITMENT_SOURCE_ID, RECRUITMENT_TYPE_EN, RECRUITMENT_TYPE_AR, ECN_SCHOOL_NAME_EN, ECN_SCHOOL_NAME_AR, ECN_SCHOOL_WILAYA_ID FROM recruitment_source ORDER BY RECRUITMENT_TYPE_EN, ECN_SCHOOL_NAME_EN");
if ($res) {
    while($r = $res->fetch_assoc()) $recruitment_sources[] = $r;
}

$armies = [];
$res = $conn->query("SELECT ARMY_ID, ARMY_NAME_EN, ARMY_NAME_AR FROM army ORDER BY ARMY_NAME_EN");
if ($res) {
    while($r = $res->fetch_assoc()) $armies[] = $r;
}

$loadedSerial = trim($_GET['serial'] ?? '');
$student = null;

$bp = [];
$pers = [];
$contactAddr = [];
$emg = null;
$parent = null;
$outfit = null;
$parade = null;

$selected_military_certificate_ids = [];
$selected_hobby_ids = [];

$bpWilayas = [];
$bpDairas = [];
$bpCommunes = [];
$persWilayas = [];
$persDairas = [];
$persCommunes = [];
$contactWilayas = [];
$contactDairas = [];
$contactCommunes = [];

if ($loadedSerial !== '') {
    $sql = "SELECT
            s.*,
            spi.FATHER_FIRST_NAME_EN, spi.FATHER_LAST_NAME_EN, spi.FATHER_FIRST_NAME_AR, spi.FATHER_LAST_NAME_AR, spi.FATHER_PROFESSION_ID,
            spi.MOTHER_FIRST_NAME_EN, spi.MOTHER_LAST_NAME_EN, spi.MOTHER_FIRST_NAME_AR, spi.MOTHER_LAST_NAME_AR, spi.MOTHER_PROFESSION_ID,
            sco.FIRST_OUTFIT_NUMBER, sco.FIRST_OUTFIT_SIZE, sco.SECOND_OUTFIT_NUMBER, sco.SECOND_OUTFIT_SIZE, sco.COMBAT_SHOE_SIZE,
            spu.SUMMER_JACKET_SIZE, spu.WINTER_JACKET_SIZE, spu.SUMMER_TROUSERS_SIZE, spu.WINTER_TROUSERS_SIZE, spu.SUMMER_SHIRT_SIZE, spu.WINTER_SHIRT_SIZE,
            spu.SUMMER_HAT_SIZE, spu.WINTER_HAT_SIZE, spu.SUMMER_SKIRT_SIZE, spu.WINTER_SKIRT_SIZE,
            addr_bp.ADDRESS_ID AS BP_ADDRESS_ID, addr_bp.ADDRESS_STREET_EN AS BP_STREET_EN, addr_bp.ADDRESS_STREET_AR AS BP_STREET_AR,
            addr_bp.COUNTRY_ID AS BP_COUNTRY_ID, addr_bp.WILAYA_ID AS BP_WILAYA_ID, addr_bp.DAIRA_ID AS BP_DAIRA_ID, addr_bp.COMMUNE_ID AS BP_COMMUNE_ID,
            addr_p.ADDRESS_ID AS PERS_ADDRESS_ID, addr_p.ADDRESS_STREET_EN AS PERS_STREET_EN, addr_p.ADDRESS_STREET_AR AS PERS_STREET_AR,
            addr_p.COUNTRY_ID AS PERS_COUNTRY_ID, addr_p.WILAYA_ID AS PERS_WILAYA_ID, addr_p.DAIRA_ID AS PERS_DAIRA_ID, addr_p.COMMUNE_ID AS PERS_COMMUNE_ID,
            sec_emg.EMERGENCY_CONTACT_ID, sec_emg.CONTACT_FIRST_NAME_EN, sec_emg.CONTACT_LAST_NAME_EN, sec_emg.CONTACT_FIRST_NAME_AR, sec_emg.CONTACT_LAST_NAME_AR,
            sec_emg.CONTACT_RELATION_ID, sec_emg.CONTACT_PHONE_NUMBER, sec_emg.CONTACT_ADDRESS_ID, sec_emg.CONSULATE_NUMBER,
            addr_emg.ADDRESS_STREET_EN AS CONTACT_STREET_EN, addr_emg.ADDRESS_STREET_AR AS CONTACT_STREET_AR,
            addr_emg.COUNTRY_ID AS CONTACT_COUNTRY_ID, addr_emg.WILAYA_ID AS CONTACT_WILAYA_ID, addr_emg.DAIRA_ID AS CONTACT_DAIRA_ID, addr_emg.COMMUNE_ID AS CONTACT_COMMUNE_ID
        FROM student s
        LEFT JOIN student_parent_info spi ON s.STUDENT_SERIAL_NUMBER = spi.STUDENT_SERIAL_NUMBER
        LEFT JOIN student_combat_outfit sco ON s.STUDENT_SERIAL_NUMBER = sco.STUDENT_SERIAL_NUMBER
        LEFT JOIN student_parade_uniform spu ON s.STUDENT_SERIAL_NUMBER = spu.STUDENT_SERIAL_NUMBER
        LEFT JOIN address addr_bp ON s.STUDENT_BIRTH_PLACE_ID = addr_bp.ADDRESS_ID
        LEFT JOIN address addr_p ON s.STUDENT_PERSONAL_ADDRESS_ID = addr_p.ADDRESS_ID
        LEFT JOIN student_emergency_contact sec_emg ON s.STUDENT_SERIAL_NUMBER = sec_emg.STUDENT_SERIAL_NUMBER
        LEFT JOIN address addr_emg ON sec_emg.CONTACT_ADDRESS_ID = addr_emg.ADDRESS_ID
        WHERE s.STUDENT_SERIAL_NUMBER = ?
        ORDER BY sec_emg.EMERGENCY_CONTACT_ID ASC
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $loadedSerial);
    $stmt->execute();
    $resStudent = $stmt->get_result();
    $student = $resStudent->fetch_assoc();
    $stmt->close();

    if ($student) {
        $stmtMc = $conn->prepare("SELECT military_certificate_id FROM student_military_certificate WHERE student_serial_number = ?");
        if ($stmtMc) {
            $stmtMc->bind_param("s", $loadedSerial);
            $stmtMc->execute();
            $resMc = $stmtMc->get_result();
            while ($rowMc = $resMc->fetch_assoc()) {
                $selected_military_certificate_ids[] = (int)$rowMc['military_certificate_id'];
            }
            $stmtMc->close();
        }

        $stmtHobby = $conn->prepare("SELECT hobby_id FROM student_hobby WHERE student_serial_number = ?");
        if ($stmtHobby) {
            $stmtHobby->bind_param("s", $loadedSerial);
            $stmtHobby->execute();
            $resHobby = $stmtHobby->get_result();
            while ($rowHobby = $resHobby->fetch_assoc()) {
                $selected_hobby_ids[] = (int)$rowHobby['hobby_id'];
            }
            $stmtHobby->close();
        }

        $bp = [
            'BP_STREET_EN' => $student['BP_STREET_EN'] ?? '',
            'BP_STREET_AR' => $student['BP_STREET_AR'] ?? '',
            'BP_COUNTRY_ID' => $student['BP_COUNTRY_ID'] ?? '',
            'BP_WILAYA_ID' => $student['BP_WILAYA_ID'] ?? '',
            'BP_DAIRA_ID' => $student['BP_DAIRA_ID'] ?? '',
            'BP_COMMUNE_ID' => $student['BP_COMMUNE_ID'] ?? ''
        ];

        $pers = [
            'PERS_STREET_EN' => $student['PERS_STREET_EN'] ?? '',
            'PERS_STREET_AR' => $student['PERS_STREET_AR'] ?? '',
            'PERS_COUNTRY_ID' => $student['PERS_COUNTRY_ID'] ?? '',
            'PERS_WILAYA_ID' => $student['PERS_WILAYA_ID'] ?? '',
            'PERS_DAIRA_ID' => $student['PERS_DAIRA_ID'] ?? '',
            'PERS_COMMUNE_ID' => $student['PERS_COMMUNE_ID'] ?? ''
        ];

        $contactAddr = [
            'CONTACT_STREET_EN' => $student['CONTACT_STREET_EN'] ?? '',
            'CONTACT_STREET_AR' => $student['CONTACT_STREET_AR'] ?? '',
            'CONTACT_COUNTRY_ID' => $student['CONTACT_COUNTRY_ID'] ?? '',
            'CONTACT_WILAYA_ID' => $student['CONTACT_WILAYA_ID'] ?? '',
            'CONTACT_DAIRA_ID' => $student['CONTACT_DAIRA_ID'] ?? '',
            'CONTACT_COMMUNE_ID' => $student['CONTACT_COMMUNE_ID'] ?? ''
        ];

        $emg = $student;
        $parent = $student;
        $outfit = $student;
        $parade = $student;

        $bpCountryId = intval($student['BP_COUNTRY_ID'] ?? 0);
        $bpWilayaId = intval($student['BP_WILAYA_ID'] ?? 0);
        $bpDairaId = intval($student['BP_DAIRA_ID'] ?? 0);

        if ($bpCountryId > 0) {
            $stmtW = $conn->prepare("SELECT WILAYA_ID, WILAYA_NAME_EN, WILAYA_NAME_AR FROM wilaya WHERE COUNTRY_ID = ? ORDER BY WILAYA_NAME_EN");
            $stmtW->bind_param("i", $bpCountryId);
            $stmtW->execute();
            $rw = $stmtW->get_result();
            while ($r = $rw->fetch_assoc()) $bpWilayas[] = $r;
            $stmtW->close();
        }
        if ($bpWilayaId > 0) {
            $stmtD = $conn->prepare("SELECT DAIRA_ID, DAIRA_NAME_EN, DAIRA_NAME_AR FROM daira WHERE WILAYA_ID = ? ORDER BY DAIRA_NAME_EN");
            $stmtD->bind_param("i", $bpWilayaId);
            $stmtD->execute();
            $rd = $stmtD->get_result();
            while ($r = $rd->fetch_assoc()) $bpDairas[] = $r;
            $stmtD->close();
        }
        if ($bpDairaId > 0) {
            $stmtC = $conn->prepare("SELECT COMMUNE_ID, COMMUNE_NAME_EN, COMMUNE_NAME_AR FROM commune WHERE DAIRA_ID = ? ORDER BY COMMUNE_NAME_EN");
            $stmtC->bind_param("i", $bpDairaId);
            $stmtC->execute();
            $rc = $stmtC->get_result();
            while ($r = $rc->fetch_assoc()) $bpCommunes[] = $r;
            $stmtC->close();
        }

        $pCountryId = intval($student['PERS_COUNTRY_ID'] ?? 0);
        $pWilayaId = intval($student['PERS_WILAYA_ID'] ?? 0);
        $pDairaId = intval($student['PERS_DAIRA_ID'] ?? 0);

        if ($pCountryId > 0) {
            $stmtW = $conn->prepare("SELECT WILAYA_ID, WILAYA_NAME_EN, WILAYA_NAME_AR FROM wilaya WHERE COUNTRY_ID = ? ORDER BY WILAYA_NAME_EN");
            $stmtW->bind_param("i", $pCountryId);
            $stmtW->execute();
            $rw = $stmtW->get_result();
            while ($r = $rw->fetch_assoc()) $persWilayas[] = $r;
            $stmtW->close();
        }
        if ($pWilayaId > 0) {
            $stmtD = $conn->prepare("SELECT DAIRA_ID, DAIRA_NAME_EN, DAIRA_NAME_AR FROM daira WHERE WILAYA_ID = ? ORDER BY DAIRA_NAME_EN");
            $stmtD->bind_param("i", $pWilayaId);
            $stmtD->execute();
            $rd = $stmtD->get_result();
            while ($r = $rd->fetch_assoc()) $persDairas[] = $r;
            $stmtD->close();
        }
        if ($pDairaId > 0) {
            $stmtC = $conn->prepare("SELECT COMMUNE_ID, COMMUNE_NAME_EN, COMMUNE_NAME_AR FROM commune WHERE DAIRA_ID = ? ORDER BY COMMUNE_NAME_EN");
            $stmtC->bind_param("i", $pDairaId);
            $stmtC->execute();
            $rc = $stmtC->get_result();
            while ($r = $rc->fetch_assoc()) $persCommunes[] = $r;
            $stmtC->close();
        }

        $cCountryId = intval($student['CONTACT_COUNTRY_ID'] ?? 0);
        $cWilayaId = intval($student['CONTACT_WILAYA_ID'] ?? 0);
        $cDairaId = intval($student['CONTACT_DAIRA_ID'] ?? 0);

        if ($cCountryId > 0) {
            $stmtW = $conn->prepare("SELECT WILAYA_ID, WILAYA_NAME_EN, WILAYA_NAME_AR FROM wilaya WHERE COUNTRY_ID = ? ORDER BY WILAYA_NAME_EN");
            $stmtW->bind_param("i", $cCountryId);
            $stmtW->execute();
            $rw = $stmtW->get_result();
            while ($r = $rw->fetch_assoc()) $contactWilayas[] = $r;
            $stmtW->close();
        }
        if ($cWilayaId > 0) {
            $stmtD = $conn->prepare("SELECT DAIRA_ID, DAIRA_NAME_EN, DAIRA_NAME_AR FROM daira WHERE WILAYA_ID = ? ORDER BY DAIRA_NAME_EN");
            $stmtD->bind_param("i", $cWilayaId);
            $stmtD->execute();
            $rd = $stmtD->get_result();
            while ($r = $rd->fetch_assoc()) $contactDairas[] = $r;
            $stmtD->close();
        }
        if ($cDairaId > 0) {
            $stmtC = $conn->prepare("SELECT COMMUNE_ID, COMMUNE_NAME_EN, COMMUNE_NAME_AR FROM commune WHERE DAIRA_ID = ? ORDER BY COMMUNE_NAME_EN");
            $stmtC->bind_param("i", $cDairaId);
            $stmtC->execute();
            $rc = $stmtC->get_result();
            while ($r = $rc->fetch_assoc()) $contactCommunes[] = $r;
            $stmtC->close();
        }
    }
}

$conn->close();

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function selectedAttr($current, $value) {
    return ((string)$current !== '' && (string)$current === (string)$value) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('app_name'); ?> - <?php echo t('edit_student'); ?></title>
    <style>
        .home-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; animation: fadeIn 0.6s ease-out; }
        .home-container h1 { animation: slideInLeft 0.5s ease-out 0.1s both; }
        .home-container p { animation: slideInLeft 0.5s ease-out 0.2s both; }
        .form-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md); animation: slideUp 0.5s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .form-section-title { grid-column: 1 / -1; font-size: 1.1rem; font-weight: 700; color: var(--primary-color); margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 2px solid var(--bg-secondary); padding-bottom: 0.5rem; position: relative; }
        .form-section-title::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: var(--primary-color); transition: width 0.5s ease; }
        .accordion-section:hover .form-section-title::after { width: 100%; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: var(--text-primary); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.95rem; background: var(--bg-secondary); transition: all 0.3s ease; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.15); transform: translateY(-1px); }
        .form-group { transition: transform 0.2s ease; }
        .form-group:focus-within { transform: translateY(-2px); }
        .btn-submit { grid-column: 1 / -1; margin-top: 2rem; background: var(--primary-color); color: white; padding: 1rem; border: none; border-radius: var(--radius-md); font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4); }
        .btn-submit:active { transform: translateY(0); box-shadow: 0 2px 6px rgba(111, 66, 193, 0.3); }
        .btn-submit::after { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: rgba(255,255,255,0.2); border-radius: 50%; transform: translate(-50%, -50%); transition: width 0.6s ease, height 0.6s ease; }
        .btn-submit:active::after { width: 300px; height: 300px; }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; animation: slideInLeft 0.4s ease-out; }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .alert.error { background: var(--bg-error); color: var(--text-error); border: 1px solid #fecaca; animation: shake 0.5s ease-out; }
        .alert.success { background: var(--bg-success); color: var(--text-success); border: 1px solid #bbf7d0; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }
        .sub-group { padding: 1rem; background: var(--background-color); border: 1px solid var(--border-color); border-radius: 8px; grid-column: 1 / -1; transition: all 0.3s ease; }
        .sub-group:hover { border-color: var(--primary-color); box-shadow: 0 2px 8px rgba(111, 66, 193, 0.1); }
        .wizard-steps { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: var(--radius-lg); }
        .wizard-step-dot { width: 2rem; height: 2rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); background: var(--border-color); cursor: pointer; transition: all 0.3s ease; }
        .wizard-step-dot:hover { background: var(--border-color); color: var(--text-primary); transform: scale(1.1); }
        .wizard-step-dot.active { background: var(--primary-color); color: white; animation: pulse 1.5s infinite; }
        .wizard-step-dot.done { background: #10b981; color: white; animation: bounce 0.5s ease; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(111, 66, 193, 0.4); } 50% { box-shadow: 0 0 0 8px rgba(111, 66, 193, 0); } }
        @keyframes bounce { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
        .wizard-panels { position: relative; min-height: 0; }
        .wizard-panel { display: block; }
        .wizard-panel.active { display: block; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .wizard-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); gap: 1rem; flex-wrap: wrap; }
        .wizard-actions .btn-prev, .wizard-actions .btn-next { padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); transition: all 0.3s ease; }
        .wizard-actions .btn-prev:hover, .wizard-actions .btn-next:hover { background: var(--border-color); transform: translateY(-2px); }
        .wizard-actions .btn-next { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .wizard-actions .btn-next:hover { background: var(--primary-hover); box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3); }
        .wizard-actions .btn-submit { margin-left: auto; }
        .search-bar { display: grid; grid-template-columns: 1fr 220px auto auto; gap: 1rem; align-items: end; }
        @media (max-width: 900px) { .search-bar { grid-template-columns: 1fr; } }
        .btn-search { padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; border: 1px solid var(--border-color); background: var(--primary-color); color: white; transition: all 0.3s ease; }
        .btn-search:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3); }
        .btn-search:active { transform: translateY(0); }

        #suggestionsContainer {
            display: none;
            margin-bottom: 20px;
            background: var(--bg-muted);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            animation: scaleIn 0.3s ease-out;
        }
        #suggestionsContainer.show { display: block; }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        #suggestionsList {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .student-card {
            position: relative;
            width: 100%;
            height: 220px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .student-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 30px rgba(111, 66, 193, 0.35);
        }

        .student-card-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }

        .student-card-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .student-card-bg.placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .student-card-bg.placeholder::after {
            content: '👤';
            font-size: 60px;
            opacity: 0.5;
        }

        .student-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 15px 15px;
            background: linear-gradient(to top,
                rgba(0, 0, 0, 0.9) 0%,
                rgba(0, 0, 0, 0.7) 40%,
                rgba(0, 0, 0, 0.3) 70%,
                transparent 100%);
        }

        .student-card-name {
            font-weight: 700;
            color: #ffffff;
            font-size: 15px;
            margin-bottom: 6px;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .student-card-id {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
            background: rgba(111, 66, 193, 0.8);
            padding: 4px 10px;
            border-radius: 12px;
            font-family: monospace;
            display: inline-block;
            backdrop-filter: blur(4px);
        }

        .accordion-section { border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: var(--bg-primary); margin-bottom: 1rem; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); transform-origin: top; }
        .accordion-section:hover { box-shadow: 0 6px 20px rgba(111, 66, 193, 0.15); transform: translateY(-2px); border-color: rgba(111, 66, 193, 0.3); }
        .accordion-section summary { cursor: pointer; list-style: none; padding: 1rem 1.25rem; font-weight: 800; color: var(--primary-color); background: var(--bg-secondary); user-select: none; transition: all 0.3s ease; position: relative; }
        .accordion-section summary:hover { background: linear-gradient(90deg, var(--bg-secondary) 0%, rgba(111, 66, 193, 0.05) 100%); padding-left: 1.5rem; }
        .accordion-section summary::-webkit-details-marker { display: none; }
        .accordion-section summary::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--primary-color); transform: scaleY(0); transition: transform 0.3s ease; }
        .accordion-section:hover summary::before, .accordion-section[open] summary::before { transform: scaleY(1); }
        .accordion-section summary::after { content: '▸'; float: right; color: var(--text-secondary); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: inline-block; font-size: 0.9rem; }
        .accordion-section[open] summary::after { content: '▾'; transform: rotate(180deg); color: var(--primary-color); }
        .accordion-section[open] { animation: accordionExpand 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes accordionExpand { from { opacity: 0.7; transform: scaleY(0.95); } to { opacity: 1; transform: scaleY(1); } }
        .accordion-section summary { border-bottom: 1px solid var(--border-color); }
        .accordion-section[open] summary { border-bottom-color: var(--primary-color); border-bottom-width: 2px; }
        .accordion-content { padding: 1.25rem; animation: contentSlideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes contentSlideIn { from { opacity: 0; transform: translateY(-15px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .accordion-section .form-group { animation: fieldFadeIn 0.4s ease-out backwards; }
        .accordion-section .form-group:nth-child(1) { animation-delay: 0.05s; }
        .accordion-section .form-group:nth-child(2) { animation-delay: 0.1s; }
        .accordion-section .form-group:nth-child(3) { animation-delay: 0.15s; }
        .accordion-section .form-group:nth-child(4) { animation-delay: 0.2s; }
        .accordion-section .form-group:nth-child(5) { animation-delay: 0.25s; }
        .accordion-section .form-group:nth-child(n+6) { animation-delay: 0.3s; }
        @keyframes fieldFadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
        .accordion-section .sub-group { animation: subGroupFade 0.5s ease-out 0.2s backwards; }
        @keyframes subGroupFade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .accordion-section .wizard-panel { padding: 0; }
        .skirt-field { display:none; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

        <div class="home-container">
            <div style="margin-bottom: 2rem;">
                <h1><?php echo t('welcome_secretary', h($secretary_name)); ?></h1>
                <p style="color: var(--text-secondary);"><?php echo t('edit_student_info'); ?></p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo h($message); ?></div>
            <?php endif; ?>

            <div class="form-card" style="margin-bottom: 1.5rem;">
                <form method="GET" action="secretary_edit_student.php">
                    <div class="search-bar">
                        <div class="form-group">
                            <label for="searchInput"><?php echo t('search_student_name_label'); ?></label>
                            <input type="text" id="searchInput" placeholder="<?php echo t('student_search_placeholder'); ?>" autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="serialInput"><?php echo t('serial_number'); ?></label>
                            <input type="text" id="serialInput" name="serial" value="<?php echo h($loadedSerial); ?>" placeholder="<?php echo t('serial_placeholder'); ?>" autocomplete="off">
                        </div>

                        <button type="submit" class="btn-search"><?php echo t('search_btn'); ?></button>
                        <button type="button" class="btn-search" style="background: var(--border-color); color: var(--text-primary);" onclick="clearSearch()"><?php echo t('clear'); ?></button>
                    </div>
                </form>
            </div>

            <div id="suggestionsContainer">
                <ul id="suggestionsList"></ul>
            </div>

            <?php if ($loadedSerial !== '' && !$student): ?>
                <div class="alert error"><?php echo t('student_not_found'); ?></div>
            <?php endif; ?>

            <?php if ($student): ?>
            <div class="form-card">
                <form method="POST" action="secretary_edit_student.php?serial=<?php echo urlencode($loadedSerial); ?>" id="wizardForm">
                    <input type="hidden" name="action" value="update_student">

                    <div class="wizard-panels">
                        <details class="accordion-section" open>
                            <summary><?php echo t('personal_details'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="1">
                            <div class="form-grid">

                                <div class="form-group">
                                    <label><?php echo t('serial_number'); ?></label>
                                    <input type="hidden" name="ORIGINAL_STUDENT_SERIAL_NUMBER" value="<?php echo h($student['STUDENT_SERIAL_NUMBER']); ?>">
                                    <input type="text" id="STUDENT_SERIAL_NUMBER" name="STUDENT_SERIAL_NUMBER" value="<?php echo h($student['STUDENT_SERIAL_NUMBER']); ?>">
                                </div>

                                <div class="form-group"><label><?php echo t('label_first_name_en'); ?></label><input type="text" name="STUDENT_FIRST_NAME_EN" required value="<?php echo h($student['STUDENT_FIRST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_last_name_en'); ?></label><input type="text" name="STUDENT_LAST_NAME_EN" required value="<?php echo h($student['STUDENT_LAST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_first_name_ar'); ?></label><input type="text" name="STUDENT_FIRST_NAME_AR" dir="rtl" value="<?php echo h($student['STUDENT_FIRST_NAME_AR']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_last_name_ar'); ?></label><input type="text" name="STUDENT_LAST_NAME_AR" dir="rtl" value="<?php echo h($student['STUDENT_LAST_NAME_AR']); ?>"></div>

                                <div class="form-group"><label><?php echo t('label_sex'); ?></label>
                                    <select id="SEX_SELECT" name="STUDENT_SEX" onchange="toggleSkirtFields()">
                                        <option value="" <?php echo ($student['STUDENT_SEX'] ? '' : 'selected'); ?>><?php echo t('select'); ?></option>
                                        <option value="Male" <?php echo selectedAttr($student['STUDENT_SEX'], 'Male'); ?>><?php echo t('male'); ?></option>
                                        <option value="Female" <?php echo selectedAttr($student['STUDENT_SEX'], 'Female'); ?>><?php echo t('female'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group"><label><?php echo t('label_birth_date'); ?></label><input type="date" name="STUDENT_BIRTH_DATE" value="<?php echo h($student['STUDENT_BIRTH_DATE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_phone'); ?></label><input type="text" name="STUDENT_PERSONAL_PHONE" value="<?php echo h($student['STUDENT_PERSONAL_PHONE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_blood_type'); ?></label>
                                    <select name="STUDENT_BLOOD_TYPE">
                                        <option value="" <?php echo ($student['STUDENT_BLOOD_TYPE'] ? '' : 'selected'); ?>><?php echo t('select'); ?></option>
                                        <option value="A+" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'A+'); ?>>A+</option>
                                        <option value="A-" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'A-'); ?>>A-</option>
                                        <option value="B+" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'B+'); ?>>B+</option>
                                        <option value="B-" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'B-'); ?>>B-</option>
                                        <option value="AB+" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'AB+'); ?>>AB+</option>
                                        <option value="AB-" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'AB-'); ?>>AB-</option>
                                        <option value="O+" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'O+'); ?>>O+</option>
                                        <option value="O-" <?php echo selectedAttr($student['STUDENT_BLOOD_TYPE'], 'O-'); ?>>O-</option>
                                    </select>
                                </div>
                                <div class="form-group"><label><?php echo t('label_height'); ?></label><input type="number" step="0.01" name="STUDENT_HEIGHT_CM" value="<?php echo h($student['STUDENT_HEIGHT_CM']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_weight'); ?></label><input type="number" step="0.01" name="STUDENT_WEIGHT_KG" value="<?php echo h($student['STUDENT_WEIGHT_KG']); ?>" min="50" max="150"></div>
                                <div class="form-group"><label><?php echo t('label_is_foreign'); ?></label>
                                    <select id="IS_FOREIGN_SELECT" name="STUDENT_IS_FOREIGN" onchange="toggleForeignFields()">
                                        <option value="No" <?php echo selectedAttr($student['STUDENT_IS_FOREIGN'], 'No'); ?>><?php echo t('no'); ?></option>
                                        <option value="Yes" <?php echo selectedAttr($student['STUDENT_IS_FOREIGN'], 'Yes'); ?>><?php echo t('yes'); ?></option>
                                    </select>
                                </div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('academic_info'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="2">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><?php echo t('category'); ?></label>
                                    <select id="CATEGORY_ID" name="CATEGORY_ID" required>
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($categories as $c): ?>
                                            <?php $catName = ($LANG === 'ar' && !empty($c['CATEGORY_NAME_AR'])) ? $c['CATEGORY_NAME_AR'] : $c['CATEGORY_NAME_EN']; ?>
                                            <option value="<?php echo $c['CATEGORY_ID']; ?>" <?php echo selectedAttr($student['CATEGORY_ID'], $c['CATEGORY_ID']); ?>><?php echo h($catName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="SECTION_GROUP">
                                    <label><?php echo t('section'); ?></label>
                                    <select id="SECTION_ID" name="SECTION_ID" required>
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($sections as $s): ?>
                                            <?php $secName = ($LANG === 'ar' && !empty($s['SECTION_NAME_AR'])) ? $s['SECTION_NAME_AR'] : $s['SECTION_NAME_EN']; ?>
                                            <option value="<?php echo $s['SECTION_ID']; ?>" data-category="<?php echo $s['CATEGORY_ID']; ?>" <?php echo selectedAttr($student['SECTION_ID'], $s['SECTION_ID']); ?>><?php echo h($secName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('speciality'); ?></label>
                                    <select name="STUDENT_SPECIALITY_ID">
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($specialities as $sp): ?>
                                            <?php $spName = ($LANG === 'ar' && !empty($sp['speciality_name_ar'])) ? $sp['speciality_name_ar'] : $sp['speciality_name_en']; ?>
                                            <option value="<?php echo $sp['student_speciality_id']; ?>" <?php echo selectedAttr($student['STUDENT_SPECIALITY_ID'] ?? '', $sp['student_speciality_id']); ?>><?php echo h($spName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label><?php echo t('academic_level'); ?></label>
                                    <select name="STUDENT_ACADEMIC_LEVEL_ID">
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($academic_levels as $al): ?>
                                            <?php $alName = ($LANG === 'ar' && !empty($al['ACADEMIC_LEVEL_AR'])) ? $al['ACADEMIC_LEVEL_AR'] : $al['ACADEMIC_LEVEL_EN']; ?>
                                            <option value="<?php echo $al['ACADEMIC_LEVEL_ID']; ?>" <?php echo selectedAttr($student['STUDENT_ACADEMIC_LEVEL_ID'] ?? '', $al['ACADEMIC_LEVEL_ID']); ?>><?php echo h($alName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label><?php echo t('academic_average'); ?></label><input type="number" step="0.01" name="STUDENT_ACADEMIC_AVERAGE" value="<?php echo h($student['STUDENT_ACADEMIC_AVERAGE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('bac_number'); ?></label><input type="text" name="STUDENT_BACCALAUREATE_SUB_NUMBER" value="<?php echo h($student['STUDENT_BACCALAUREATE_SUB_NUMBER']); ?>"></div>

                                <div class="form-group"><label><?php echo t('grade_rank'); ?></label>
                                    <select name="STUDENT_GRADE_ID">
                                        <option value=""><?php echo t('select_grade'); ?></option>
                                        <?php foreach ($grades as $g): ?>
                                            <?php $gradeName = ($LANG === 'ar' && !empty($g['GRADE_NAME_AR'])) ? $g['GRADE_NAME_AR'] : $g['GRADE_NAME_EN']; ?>
                                            <option value="<?php echo $g['GRADE_ID']; ?>" <?php echo selectedAttr($student['STUDENT_GRADE_ID'], $g['GRADE_ID']); ?>><?php echo h($gradeName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('recruitment_source'); ?></label>
                                    <select id="RECRUITMENT_SOURCE_ID" name="RECRUITMENT_SOURCE_ID">
                                        <option value=""><?php echo t('select_recruitment'); ?></option>
                                        <?php foreach ($recruitment_sources as $rs): ?>
                                            <?php
                                                $label = ($LANG === 'ar' ? ($rs['RECRUITMENT_TYPE_AR'] ?? $rs['RECRUITMENT_TYPE_EN']) : $rs['RECRUITMENT_TYPE_EN']);
                                                $school = ($LANG === 'ar' ? ($rs['ECN_SCHOOL_NAME_AR'] ?? $rs['ECN_SCHOOL_NAME_EN']) : $rs['ECN_SCHOOL_NAME_EN']);
                                                if ($school) $label .= ' - ' . $school;
                                            ?>
                                            <option value="<?php echo $rs['RECRUITMENT_SOURCE_ID']; ?>" <?php echo selectedAttr($student['STUDENT_RECRUITMENT_SOURCE_ID'], $rs['RECRUITMENT_SOURCE_ID']); ?>><?php echo h($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('army'); ?></label>
                                    <select name="STUDENT_ARMY_ID">
                                        <option value=""><?php echo t('select_army'); ?></option>
                                        <?php foreach ($armies as $a): ?>
                                            <?php $armyName = ($LANG === 'ar' && !empty($a['ARMY_NAME_AR'])) ? $a['ARMY_NAME_AR'] : $a['ARMY_NAME_EN']; ?>
                                            <option value="<?php echo $a['ARMY_ID']; ?>" <?php echo selectedAttr($student['STUDENT_ARMY_ID'], $a['ARMY_ID']); ?>><?php echo h($armyName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('family_information'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="3">
                            <div class="form-grid">
                                <div class="form-group"><label><?php echo t('label_father_first_en'); ?></label><input type="text" name="FATHER_FIRST_NAME_EN" value="<?php echo h($student['FATHER_FIRST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_father_last_en'); ?></label><input type="text" name="FATHER_LAST_NAME_EN" value="<?php echo h($student['FATHER_LAST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_father_first_ar'); ?></label><input type="text" name="FATHER_FIRST_NAME_AR" dir="rtl" value="<?php echo h($student['FATHER_FIRST_NAME_AR']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_father_last_ar'); ?></label><input type="text" name="FATHER_LAST_NAME_AR" dir="rtl" value="<?php echo h($student['FATHER_LAST_NAME_AR']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_father_prof_en'); ?></label>
                                    <select name="FATHER_PROFESSION_ID">
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($professions as $p): ?>
                                            <?php $pName = ($LANG === 'ar' && !empty($p['profession_name_ar'])) ? $p['profession_name_ar'] : $p['profession_name_en']; ?>
                                            <option value="<?php echo $p['profession_id']; ?>" <?php echo selectedAttr($student['FATHER_PROFESSION_ID'] ?? '', $p['profession_id']); ?>><?php echo h($pName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label><?php echo t('label_mother_first_en'); ?></label><input type="text" name="MOTHER_FIRST_NAME_EN" value="<?php echo h($student['MOTHER_FIRST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_mother_last_en'); ?></label><input type="text" name="MOTHER_LAST_NAME_EN" value="<?php echo h($student['MOTHER_LAST_NAME_EN']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_mother_first_ar'); ?></label><input type="text" name="MOTHER_FIRST_NAME_AR" dir="rtl" value="<?php echo h($student['MOTHER_FIRST_NAME_AR']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_mother_last_ar'); ?></label><input type="text" name="MOTHER_LAST_NAME_AR" dir="rtl" value="<?php echo h($student['MOTHER_LAST_NAME_AR']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_mother_prof_en'); ?></label>
                                    <select name="MOTHER_PROFESSION_ID">
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($professions as $p): ?>
                                            <?php $pName = ($LANG === 'ar' && !empty($p['profession_name_ar'])) ? $p['profession_name_ar'] : $p['profession_name_en']; ?>
                                            <option value="<?php echo $p['profession_id']; ?>" <?php echo selectedAttr($student['MOTHER_PROFESSION_ID'] ?? '', $p['profession_id']); ?>><?php echo h($pName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('orphan_status'); ?></label>
                                    <select name="STUDENT_ORPHAN_STATUS">
                                        <option value="None" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'None'); ?>><?php echo t('orphan_none'); ?></option>
                                        <option value="Father" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Father'); ?>><?php echo t('orphan_father'); ?></option>
                                        <option value="Mother" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Mother'); ?>><?php echo t('orphan_mother'); ?></option>
                                        <option value="Both" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Both'); ?>><?php echo t('orphan_both'); ?></option>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('parents_situation'); ?></label>
                                    <select name="STUDENT_PARENTS_SITUATION">
                                        <option value="Married" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Married'); ?>><?php echo t('married'); ?></option>
                                        <option value="Divorced" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Divorced'); ?>><?php echo t('divorced'); ?></option>
                                        <option value="Separated" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Separated'); ?>><?php echo t('separated'); ?></option>
                                        <option value="Widowed" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Widowed'); ?>><?php echo t('widowed'); ?></option>
                                    </select>
                                </div>

                                <div class="form-group"><label><?php echo t('num_siblings'); ?></label><input type="number" name="STUDENT_NUMBER_OF_SIBLINGS" value="<?php echo h($student['STUDENT_NUMBER_OF_SIBLINGS']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_sisters_count'); ?></label><input type="number" name="STUDENT_NUMBER_OF_SISTERS" value="<?php echo h($student['STUDENT_NUMBER_OF_SISTERS']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_order_among_siblings'); ?></label><input type="number" name="STUDENT_ORDER_AMONG_SIBLINGS" value="<?php echo h($student['STUDENT_ORDER_AMONG_SIBLINGS']); ?>"></div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('label_personal_address'); ?> <?php echo t('and'); ?> <?php echo t('label_birth_place_address'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="4">
                            <div class="form-grid">

                                <div class="sub-group" style="grid-column: 1 / -1;">
                                    <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_birth_place_address'); ?></label>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="BP_STREET_EN" value="<?php echo h($bp['BP_STREET_EN'] ?? ''); ?>"></div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="BP_STREET_AR" dir="rtl" value="<?php echo h($bp['BP_STREET_AR'] ?? ''); ?>"></div>
                                        <div class="form-group">
                                            <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                            <select class="country-select" data-prefix="BP_" name="BP_COUNTRY_ID">
                                                <option value=""><?php echo t('option_select_country'); ?></option>
                                                <?php foreach ($countries as $c): ?>
                                                    <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                    <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($bp['BP_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label>
                                            <select id="BP_WILAYA_ID" name="BP_WILAYA_ID" class="wilaya-select" data-prefix="BP_" <?php echo (!empty($bpWilayas) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_wilaya_first'); ?></option>
                                                <?php foreach ($bpWilayas as $w): ?>
                                                    <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                    <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($bp['BP_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label>
                                            <select id="BP_DAIRA_ID" name="BP_DAIRA_ID" class="daira-select" data-prefix="BP_" <?php echo (!empty($bpDairas) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_daira_first'); ?></option>
                                                <?php foreach ($bpDairas as $d): ?>
                                                    <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                    <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($bp['BP_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label>
                                            <select id="BP_COMMUNE_ID" name="BP_COMMUNE_ID" <?php echo (!empty($bpCommunes) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_commune_first'); ?></option>
                                                <?php foreach ($bpCommunes as $cc): ?>
                                                    <?php $cName = ($LANG === 'ar' && !empty($cc['COMMUNE_NAME_AR'])) ? $cc['COMMUNE_NAME_AR'] : $cc['COMMUNE_NAME_EN']; ?>
                                                    <option value="<?php echo $cc['COMMUNE_ID']; ?>" <?php echo selectedAttr($bp['BP_COMMUNE_ID'] ?? '', $cc['COMMUNE_ID']); ?>><?php echo h($cName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="sub-group" style="grid-column: 1 / -1;">
                                    <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_personal_address'); ?></label>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="PERS_STREET_EN" value="<?php echo h($pers['PERS_STREET_EN'] ?? ''); ?>"></div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="PERS_STREET_AR" dir="rtl" value="<?php echo h($pers['PERS_STREET_AR'] ?? ''); ?>"></div>
                                        <div class="form-group">
                                            <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                            <select class="country-select" data-prefix="PERS_" name="PERS_COUNTRY_ID">
                                                <option value=""><?php echo t('option_select_country'); ?></option>
                                                <?php foreach ($countries as $c): ?>
                                                    <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                    <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($pers['PERS_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label>
                                            <select id="PERS_WILAYA_ID" name="PERS_WILAYA_ID" class="wilaya-select" data-prefix="PERS_" <?php echo (!empty($persWilayas) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_wilaya_first'); ?></option>
                                                <?php foreach ($persWilayas as $w): ?>
                                                    <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                    <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($pers['PERS_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label>
                                            <select id="PERS_DAIRA_ID" name="PERS_DAIRA_ID" class="daira-select" data-prefix="PERS_" <?php echo (!empty($persDairas) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_daira_first'); ?></option>
                                                <?php foreach ($persDairas as $d): ?>
                                                    <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                    <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($pers['PERS_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label>
                                            <select id="PERS_COMMUNE_ID" name="PERS_COMMUNE_ID" <?php echo (!empty($persCommunes) ? '' : 'disabled'); ?>>
                                                <option value=""><?php echo t('option_select_commune_first'); ?></option>
                                                <?php foreach ($persCommunes as $cc): ?>
                                                    <?php $cName = ($LANG === 'ar' && !empty($cc['COMMUNE_NAME_AR'])) ? $cc['COMMUNE_NAME_AR'] : $cc['COMMUNE_NAME_EN']; ?>
                                                    <option value="<?php echo $cc['COMMUNE_ID']; ?>" <?php echo selectedAttr($pers['PERS_COMMUNE_ID'] ?? '', $cc['COMMUNE_ID']); ?>><?php echo h($cName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('uniforms'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="5">
                            <div class="form-grid">
                                <div class="form-section-title"><?php echo t('combat_outfit'); ?></div>
                                <div class="form-group"><label><?php echo t('outfit1_number'); ?></label><input type="text" name="FIRST_OUTFIT_NUMBER" value="<?php echo h($student['FIRST_OUTFIT_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('outfit1_size'); ?></label><input type="text" name="FIRST_OUTFIT_SIZE" value="<?php echo h($student['FIRST_OUTFIT_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('outfit2_number'); ?></label><input type="text" name="SECOND_OUTFIT_NUMBER" value="<?php echo h($student['SECOND_OUTFIT_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('outfit2_size'); ?></label><input type="text" name="SECOND_OUTFIT_SIZE" value="<?php echo h($student['SECOND_OUTFIT_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('shoe_size'); ?></label><input type="text" name="COMBAT_SHOE_SIZE" value="<?php echo h($student['COMBAT_SHOE_SIZE']); ?>"></div>

                                <div class="form-section-title"><?php echo t('parade_uniform'); ?></div>
                                <div class="form-group"><label><?php echo t('summer_jacket_size'); ?></label><input type="text" name="SUMMER_JACKET_SIZE" value="<?php echo h($student['SUMMER_JACKET_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('winter_jacket_size'); ?></label><input type="text" name="WINTER_JACKET_SIZE" value="<?php echo h($student['WINTER_JACKET_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('summer_trousers_size'); ?></label><input type="text" name="SUMMER_TROUSERS_SIZE" value="<?php echo h($student['SUMMER_TROUSERS_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('winter_trousers_size'); ?></label><input type="text" name="WINTER_TROUSERS_SIZE" value="<?php echo h($student['WINTER_TROUSERS_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('summer_shirt_size'); ?></label><input type="text" name="SUMMER_SHIRT_SIZE" value="<?php echo h($student['SUMMER_SHIRT_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('winter_shirt_size'); ?></label><input type="text" name="WINTER_SHIRT_SIZE" value="<?php echo h($student['WINTER_SHIRT_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('summer_hat_size'); ?></label><input type="text" name="SUMMER_HAT_SIZE" value="<?php echo h($student['SUMMER_HAT_SIZE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('winter_hat_size'); ?></label><input type="text" name="WINTER_HAT_SIZE" value="<?php echo h($student['WINTER_HAT_SIZE']); ?>"></div>
                                <div class="form-group skirt-field"><label><?php echo t('summer_skirt_size'); ?></label><input type="text" name="SUMMER_SKIRT_SIZE" value="<?php echo h($student['SUMMER_SKIRT_SIZE']); ?>"></div>
                                <div class="form-group skirt-field"><label><?php echo t('winter_skirt_size'); ?></label><input type="text" name="WINTER_SKIRT_SIZE" value="<?php echo h($student['WINTER_SKIRT_SIZE']); ?>"></div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('step_other_details'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel">
                            <div class="form-grid">
                                <div class="form-group"><label><?php echo t('id_card_num'); ?></label><input type="text" name="STUDENT_ID_CARD_NUMBER" value="<?php echo h($student['STUDENT_ID_CARD_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('birth_cert_num'); ?></label><input type="text" name="STUDENT_BIRTHDATE_CERTIFICATE_NUMBER" value="<?php echo h($student['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('school_card_number'); ?></label><input type="text" name="STUDENT_SCHOOL_SUB_CARD_NUMBER" value="<?php echo h($student['STUDENT_SCHOOL_SUB_CARD_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('school_sub_date'); ?></label><input type="date" name="STUDENT_SCHOOL_SUB_DATE" value="<?php echo h($student['STUDENT_SCHOOL_SUB_DATE']); ?>"></div>
                                <div class="form-group"><label><?php echo t('laptop_serial'); ?></label><input type="text" name="STUDENT_LAPTOP_SERIAL_NUMBER" value="<?php echo h($student['STUDENT_LAPTOP_SERIAL_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('postal_account'); ?></label><input type="text" name="STUDENT_POSTAL_ACCOUNT_NUMBER" value="<?php echo h($student['STUDENT_POSTAL_ACCOUNT_NUMBER']); ?>"></div>
                                <div class="form-group"><label><?php echo t('label_mil_necklace'); ?></label>
                                    <select name="STUDENT_MILITARY_NECKLACE">
                                        <option value="No" <?php echo selectedAttr($student['STUDENT_MILITARY_NECKLACE'], 'No'); ?>><?php echo t('no'); ?></option>
                                        <option value="Yes" <?php echo selectedAttr($student['STUDENT_MILITARY_NECKLACE'], 'Yes'); ?>><?php echo t('yes'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group" style="grid-column: span 2;"><label><?php echo t('educational_certificates'); ?></label><textarea name="STUDENT_EDUCATIONAL_CERTIFICATES" rows="2"><?php echo h($student['STUDENT_EDUCATIONAL_CERTIFICATES']); ?></textarea></div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label><?php echo t('military_certificates'); ?></label>
                                    <select name="MILITARY_CERTIFICATE_IDS[]" multiple size="6">
                                        <?php foreach ($military_certificates as $mc): ?>
                                            <?php $mcName = ($LANG === 'ar' && !empty($mc['military_certificate_ar'])) ? $mc['military_certificate_ar'] : $mc['military_certificate_en']; ?>
                                            <option value="<?php echo $mc['military_certificate_id']; ?>" <?php echo in_array((int)$mc['military_certificate_id'], $selected_military_certificate_ids, true) ? 'selected' : ''; ?>><?php echo h($mcName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label><?php echo t('hobbies'); ?></label>
                                    <select name="HOBBY_IDS[]" multiple size="6">
                                        <?php foreach ($hobbies as $hb): ?>
                                            <?php $hbName = ($LANG === 'ar' && !empty($hb['hobby_name_ar'])) ? $hb['hobby_name_ar'] : $hb['hobby_name_en']; ?>
                                            <option value="<?php echo $hb['hobby_id']; ?>" <?php echo in_array((int)$hb['hobby_id'], $selected_hobby_ids, true) ? 'selected' : ''; ?>><?php echo h($hbName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label><?php echo t('health_status'); ?></label>
                                    <select name="STUDENT_HEALTH_STATUS_ID">
                                        <option value=""><?php echo t('select'); ?></option>
                                        <?php foreach ($health_statuses as $hs): ?>
                                            <?php $hsName = ($LANG === 'ar' && !empty($hs['health_status_ar'])) ? $hs['health_status_ar'] : $hs['health_status_en']; ?>
                                            <option value="<?php echo $hs['health_status_id']; ?>" <?php echo selectedAttr($student['STUDENT_HEALTH_STATUS_ID'] ?? '', $hs['health_status_id']); ?>><?php echo h($hsName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>
                            </div>
                            </div>
                        </details>

                        <details class="accordion-section">
                            <summary><?php echo t('step_emergency_contact'); ?></summary>
                            <div class="accordion-content">
                            <div class="wizard-panel" data-step="7">
                            <div class="form-grid">
                                <div class="sub-group" style="grid-column: 1 / -1; border-color:var(--border-error); background:var(--bg-error);">
                                    <div class="form-grid" style="gap: 1.5rem;">
                                        <div class="form-group"><label><?php echo t('label_contact_phone'); ?></label><input type="text" name="CONTACT_PHONE_NUMBER" value="<?php echo h($student['CONTACT_PHONE_NUMBER']); ?>"></div>

                                        <div id="LOCAL_CONTACT_FIELDS" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                            <div class="form-group"><label><?php echo t('label_first_name_en'); ?></label><input type="text" name="CONTACT_FIRST_NAME_EN" value="<?php echo h($student['CONTACT_FIRST_NAME_EN']); ?>"></div>
                                            <div class="form-group"><label><?php echo t('label_last_name_en'); ?></label><input type="text" name="CONTACT_LAST_NAME_EN" value="<?php echo h($student['CONTACT_LAST_NAME_EN']); ?>"></div>
                                            <div class="form-group"><label><?php echo t('label_relation_en'); ?></label>
                                                <select name="CONTACT_RELATION_ID">
                                                    <option value=""><?php echo t('select'); ?></option>
                                                    <?php foreach ($relations as $rel): ?>
                                                        <?php $relName = ($LANG === 'ar' && !empty($rel['relation_name_ar'])) ? $rel['relation_name_ar'] : $rel['relation_name_en']; ?>
                                                        <option value="<?php echo $rel['relation_id']; ?>" <?php echo selectedAttr($student['CONTACT_RELATION_ID'] ?? '', $rel['relation_id']); ?>><?php echo h($relName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group"><label><?php echo t('label_first_name_ar'); ?></label><input type="text" name="CONTACT_FIRST_NAME_AR" dir="rtl" value="<?php echo h($student['CONTACT_FIRST_NAME_AR']); ?>"></div>
                                            <div class="form-group"><label><?php echo t('label_last_name_ar'); ?></label><input type="text" name="CONTACT_LAST_NAME_AR" dir="rtl" value="<?php echo h($student['CONTACT_LAST_NAME_AR']); ?>"></div>

                                            <div class="sub-group" style="grid-column: 1 / -1;">
                                                <label style="color:var(--primary-color); font-weight:700;"><?php echo t('label_contact_address'); ?></label>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                                    <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_en'); ?></label><input type="text" name="CONTACT_STREET_EN" value="<?php echo h($contactAddr['CONTACT_STREET_EN'] ?? ''); ?>"></div>
                                                    <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_street_ar'); ?></label><input type="text" name="CONTACT_STREET_AR" dir="rtl" value="<?php echo h($contactAddr['CONTACT_STREET_AR'] ?? ''); ?>"></div>
                                                    <div class="form-group">
                                                        <label style="font-size:0.8rem;"><?php echo t('label_country'); ?></label>
                                                        <select class="country-select" data-prefix="CONTACT_" name="CONTACT_COUNTRY_ID">
                                                            <option value=""><?php echo t('option_select_country'); ?></option>
                                                            <?php foreach ($countries as $c): ?>
                                                                <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                                <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_wilaya'); ?></label>
                                                        <select id="CONTACT_WILAYA_ID" name="CONTACT_WILAYA_ID" class="wilaya-select" data-prefix="CONTACT_" <?php echo (!empty($contactWilayas) ? '' : 'disabled'); ?>>
                                                            <option value=""><?php echo t('option_select_country_first'); ?></option>
                                                            <?php foreach ($contactWilayas as $w): ?>
                                                                <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                                <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_daira'); ?></label>
                                                        <select id="CONTACT_DAIRA_ID" name="CONTACT_DAIRA_ID" class="daira-select" data-prefix="CONTACT_" <?php echo (!empty($contactDairas) ? '' : 'disabled'); ?>>
                                                            <option value=""><?php echo t('option_select_wilaya_first'); ?></option>
                                                            <?php foreach ($contactDairas as $d): ?>
                                                                <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                                <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group"><label style="font-size:0.8rem;"><?php echo t('label_commune'); ?></label>
                                                        <select id="CONTACT_COMMUNE_ID" name="CONTACT_COMMUNE_ID" <?php echo (!empty($contactCommunes) ? '' : 'disabled'); ?>>
                                                            <option value=""><?php echo t('option_select_daira_first'); ?></option>
                                                            <?php foreach ($contactCommunes as $cc): ?>
                                                                <?php $cName = ($LANG === 'ar' && !empty($cc['COMMUNE_NAME_AR'])) ? $cc['COMMUNE_NAME_AR'] : $cc['COMMUNE_NAME_EN']; ?>
                                                                <option value="<?php echo $cc['COMMUNE_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_COMMUNE_ID'] ?? '', $cc['COMMUNE_ID']); ?>><?php echo h($cName); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div id="FOREIGN_CONTACT_FIELDS" style="grid-column: 1 / -1; display:none; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div class="form-group"><label><?php echo t('label_consulate_number'); ?></label><input type="text" name="CONSULATE_NUMBER" value="<?php echo h($student['CONSULATE_NUMBER']); ?>"></div>
                                            <div class="form-group" style="grid-column: span 2;"><p style="font-size:0.9rem; color:var(--text-secondary);"><?php echo t('relation_consulate_note'); ?></p></div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            </div>
                        </details>

                    </div>

                    <div class="wizard-actions">
                        <button type="submit" class="btn-submit"><?php echo t('save_changes'); ?></button>
                    </div>

                </form>
            </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<script>
var T = <?php echo json_encode($T); ?>;

const t = (key) => T[key] || key;

let allStudents = [];
let allStudentsLoaded = false;
let allStudentsLoadingPromise = null;
let lastAutocompleteQuery = '';

const isImageFilename = (value) => typeof value === 'string' && /\.(jpe?g|png|gif|webp)$/i.test(value);
const resolvePhotoUrl = (value) => {
    if (!value) return null;
    value = String(value).replace(/\\/g, '/').replace(/^\/+/, '');
    if (value.startsWith('data:') || value.startsWith('http')) return value;
    if (value.includes('/')) return value;
    if (isImageFilename(value)) return `resources/photos/students/${value}`;
    return `data:image/jpeg;base64,${value}`;
};

function fetchAllStudents() {
    if (allStudentsLoaded) {
        return Promise.resolve(allStudents);
    }
    if (allStudentsLoadingPromise) {
        return allStudentsLoadingPromise;
    }

    allStudentsLoadingPromise = fetch('get_all_students.php')
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                allStudents = Array.isArray(data.students) ? data.students : [];
                allStudentsLoaded = true;
            } else {
                allStudents = [];
            }
            return allStudents;
        })
        .catch(() => {
            allStudents = [];
            return allStudents;
        })
        .finally(() => {
            allStudentsLoadingPromise = null;
        });

    return allStudentsLoadingPromise;
}

function openStudent(serialNumber) {
    if (!serialNumber) return;
    window.location.href = `secretary_edit_student.php?serial=${encodeURIComponent(serialNumber)}`;
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const serialInput = document.getElementById('serialInput');
    const suggestionsContainer = document.getElementById('suggestionsContainer');
    const suggestionsList = document.getElementById('suggestionsList');

    if (searchInput) searchInput.value = '';
    if (serialInput) serialInput.value = '';
    if (suggestionsList) suggestionsList.innerHTML = '';
    if (suggestionsContainer) suggestionsContainer.style.display = 'none';

    window.location.href = 'secretary_edit_student.php';
}

function renderSuggestions(rawQuery) {
    lastAutocompleteQuery = String(rawQuery || '');
    const query = String(rawQuery || '').toLowerCase().trim();
    const suggestionsContainer = document.getElementById('suggestionsContainer');
    const suggestionsList = document.getElementById('suggestionsList');
    if (!suggestionsContainer || !suggestionsList) return;

    if (query.length < 2) {
        suggestionsContainer.style.display = 'none';
        return;
    }

    if (!allStudentsLoaded && !allStudentsLoadingPromise) {
        fetchAllStudents().then(() => {
            if (lastAutocompleteQuery) {
                renderSuggestions(lastAutocompleteQuery);
            }
        });
    }

    if (!allStudentsLoaded) {
        return;
    }

    const matches = allStudents.filter(s =>
        String(s.first_name || '').toLowerCase().includes(query) ||
        String(s.last_name || '').toLowerCase().includes(query) ||
        (`${s.first_name || ''} ${s.last_name || ''}`).toLowerCase().includes(query) ||
        String(s.serial_number || '').toLowerCase().includes(query)
    );

    if (matches.length > 0) {
        suggestionsList.innerHTML = matches.slice(0, 12).map(student => {
            let bgStyle = '';
            let bgClass = 'placeholder';

            const photoUrl = student.photo ? resolvePhotoUrl(student.photo) : null;
            const photoImg = photoUrl
                ? `<img class="student-card-img" src="${photoUrl}" onerror="this.onerror=null;this.src='assets/placeholder-student.png';" />`
                : '';

            if (student.photo) {
                bgStyle = `style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"`;
            }

            const safeSerial = String(student.serial_number || '').replace(/'/g, "\\'");
            const safeFirst = String(student.first_name || '').replace(/'/g, "\\'");
            const safeLast = String(student.last_name || '').replace(/'/g, "\\'");

            return `
                <li class="student-card" onclick="openStudent('${safeSerial}')">
                    <div class="student-card-bg ${bgClass}" ${bgStyle}></div>
                    ${photoImg}
                    <div class="student-card-overlay">
                        <div class="student-card-name">${safeFirst} ${safeLast}</div>
                        <div class="student-card-id">${safeSerial}</div>
                    </div>
                </li>
            `;
        }).join('');
        suggestionsContainer.style.display = 'block';
    } else {
        suggestionsContainer.style.display = 'none';
    }
}

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        renderSuggestions(this.value);
    });
}

const serialInput = document.getElementById('serialInput');
if (serialInput) {
    serialInput.addEventListener('input', function() {
        renderSuggestions(this.value);
    });
}

document.addEventListener('click', function(e) {
    const container = document.getElementById('suggestionsContainer');
    if (!container) return;
    if (searchInput && (e.target === searchInput)) return;
    if (serialInput && (e.target === serialInput)) return;
    if (!container.contains(e.target)) {
        container.style.display = 'none';
    }
});

function toggleSkirtFields() {
    const sexEl = document.getElementById('SEX_SELECT');
    if (!sexEl) return;
    const sex = sexEl.value;
    const skirtFields = document.querySelectorAll('.skirt-field');
    skirtFields.forEach(field => {
        field.style.display = (sex === 'Female') ? 'block' : 'none';
    });
}

function toggleForeignFields() {
    const isForeignEl = document.getElementById('IS_FOREIGN_SELECT');
    if (!isForeignEl) return;
    const isForeign = isForeignEl.value;
    const localFields = document.getElementById('LOCAL_CONTACT_FIELDS');
    const foreignFields = document.getElementById('FOREIGN_CONTACT_FIELDS');

    if (!localFields || !foreignFields) return;

    if (isForeign === 'Yes') {
        localFields.style.display = 'none';
        foreignFields.style.display = 'grid';
        foreignFields.style.gridTemplateColumns = '1fr 1fr';
        foreignFields.style.gap = '1rem';
    } else {
        localFields.style.display = 'grid';
        foreignFields.style.display = 'none';
    }
}

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
        .catch(() => {
            targetSelect.innerHTML = '<option value="">' + (T.error_loading || 'Error or None') + '</option>';
        });
}

document.querySelectorAll('.country-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const wilayaSel = document.getElementById(prefix + 'WILAYA_ID');
        const dairaSel = document.getElementById(prefix + 'DAIRA_ID');
        const communeSel = document.getElementById(prefix + 'COMMUNE_ID');
        if (dairaSel) { dairaSel.innerHTML = '<option value="">' + (T.select_daira_opt || 'Select Daira...') + '</option>'; dairaSel.disabled = true; }
        if (communeSel) { communeSel.innerHTML = '<option value="">' + (T.select_commune_opt || 'Select Commune...') + '</option>'; communeSel.disabled = true; }
        if(this.value && wilayaSel) fetchLocations('wilayas', 'country_id', this.value, wilayaSel, T.select_wilaya_opt || 'Select Wilaya...');
    });
});

document.querySelectorAll('.wilaya-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const dairaSel = document.getElementById(prefix + 'DAIRA_ID');
        const communeSel = document.getElementById(prefix + 'COMMUNE_ID');
        if (communeSel) { communeSel.innerHTML = '<option value="">' + (T.select_commune_opt || 'Select Commune...') + '</option>'; communeSel.disabled = true; }
        if(this.value && dairaSel) fetchLocations('dairas', 'wilaya_id', this.value, dairaSel, T.select_daira_opt || 'Select Daira...');
    });
});

document.querySelectorAll('.daira-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const communeSel = document.getElementById(prefix + 'COMMUNE_ID');
        if(this.value && communeSel) fetchLocations('communes', 'daira_id', this.value, communeSel, T.select_commune_opt || 'Select Commune...');
    });
});

function updateSections() {
    const categoryId = document.getElementById('CATEGORY_ID');
    const sectionSelect = document.getElementById('SECTION_ID');
    if (!categoryId || !sectionSelect) return;

    const catVal = categoryId.value;
    const options = sectionSelect.querySelectorAll('option[data-category]');

    if (catVal) {
        options.forEach(opt => {
            if (opt.getAttribute('data-category') === catVal) {
                opt.hidden = false;
                opt.disabled = false;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        });

        const currentOption = sectionSelect.options[sectionSelect.selectedIndex];
        if (currentOption && currentOption.getAttribute('data-category') !== catVal && currentOption.value !== "") {
            sectionSelect.value = "";
        }
    } else {
        options.forEach(opt => { opt.hidden = false; opt.disabled = false; });
    }
}

const catSel = document.getElementById('CATEGORY_ID');
if (catSel) {
    catSel.addEventListener('change', updateSections);
    updateSections();
}

toggleSkirtFields();
toggleForeignFields();

fetchAllStudents();

const getField = (name) => document.querySelector(`#wizardForm [name="${CSS.escape(name)}"]`);
const ensureErrorEl = (input) => {
    if (!input) return null;
    const key = input.name || input.id || Math.random().toString(36).slice(2);
    const id = `err_${key}`;
    let el = document.getElementById(id);
    if (!el) {
        el = document.createElement('div');
        el.id = id;
        el.style.marginTop = '0.35rem';
        el.style.color = 'var(--text-error)';
        el.style.fontSize = '0.85rem';
        el.style.display = 'none';
        input.insertAdjacentElement('afterend', el);
    }
    return el;
};
const setFieldError = (input, message) => {
    if (!input) return;
    input.setCustomValidity(message || '');
    const el = ensureErrorEl(input);
    if (el) {
        el.textContent = message || '';
        el.style.display = message ? 'block' : 'none';
    }
};

const isEmpty = (v) => (v === null || v === undefined || String(v).trim() === '');
const normalizePhone = (v) => String(v || '').trim();
const isE164 = (v) => /^\+[1-9]\d{1,14}$/.test(normalizePhone(v));
const containsLatin = (v) => /[A-Za-z]/.test(String(v || ''));
const containsArabic = (v) => /[\u0600-\u06FF]/.test(String(v || ''));
const digitsOnly = (v) => /^\d+$/.test(String(v || '').trim());
const toIntOrNull = (v) => {
    if (isEmpty(v)) return null;
    const n = parseInt(String(v), 10);
    return Number.isFinite(n) ? n : null;
};
const toDateOrNull = (v) => {
    if (isEmpty(v)) return null;
    const d = new Date(String(v));
    return Number.isNaN(d.getTime()) ? null : d;
};

const validateAll = () => {
    const form = document.getElementById('wizardForm');
    if (!form) return;

    const serial = getField('STUDENT_SERIAL_NUMBER');
    if (serial) {
        if (isEmpty(serial.value)) {
            setFieldError(serial, 'Serial Number is required.');
        } else if (!digitsOnly(serial.value)) {
            setFieldError(serial, 'Serial Number must contain digits only.');
        } else {
            setFieldError(serial, '');
        }
    }

    const phone = getField('STUDENT_PERSONAL_PHONE');
    if (phone && !isEmpty(phone.value) && !isE164(phone.value)) {
        setFieldError(phone, t('invalid_phone') || 'Phone number must be an international format like +213xxxxxxxxx');
    } else {
        setFieldError(phone, '');
    }

    const contactPhone = getField('CONTACT_PHONE_NUMBER');
    if (contactPhone && !isEmpty(contactPhone.value) && !isE164(contactPhone.value)) {
        setFieldError(contactPhone, t('invalid_phone') || 'Phone number must be an international format like +213xxxxxxxxx');
    } else {
        setFieldError(contactPhone, '');
    }

    const birthDate = getField('STUDENT_BIRTH_DATE');
    const bd = birthDate ? toDateOrNull(birthDate.value) : null;
    if (birthDate && bd) {
        if (bd.getFullYear() < 1990) {
            setFieldError(birthDate, 'Birth year must be 1990 or later');
        } else {
            setFieldError(birthDate, '');
        }
    } else {
        setFieldError(birthDate, '');
    }

    const schoolSubDate = getField('STUDENT_SCHOOL_SUB_DATE');
    const sd = schoolSubDate ? toDateOrNull(schoolSubDate.value) : null;
    if (schoolSubDate && bd && sd) {
        if (sd.getTime() < bd.getTime()) {
            setFieldError(schoolSubDate, 'School subscription date cannot be before the birth date');
        } else {
            setFieldError(schoolSubDate, '');
        }
    } else {
        setFieldError(schoolSubDate, '');
    }

    const height = getField('STUDENT_HEIGHT_CM');
    if (height && !isEmpty(height.value)) {
        const hv = Number(height.value);
        if (!Number.isFinite(hv)) {
            setFieldError(height, 'Height must be a number');
        } else if (hv < 140 || hv > 250) {
            setFieldError(height, 'Height must be between 140 and 250 cm');
        } else {
            setFieldError(height, '');
        }
    } else {
        setFieldError(height, '');
    }

    const weight = getField('STUDENT_WEIGHT_KG');
    if (weight && weight.validity && weight.validity.badInput) {
        setFieldError(weight, 'Weight must be a number');
    } else if (weight && !isEmpty(weight.value)) {
        const wv = weight.valueAsNumber;
        if (Number.isNaN(wv)) {
            setFieldError(weight, 'Weight must be a number');
        } else if (wv < 50 || wv > 150) {
            setFieldError(weight, 'Weight must be between 50 and 150 kg');
        } else {
            setFieldError(weight, '');
        }
    } else {
        setFieldError(weight, '');
    }

    const bac = getField('STUDENT_BACCALAUREATE_SUB_NUMBER');
    if (bac && !isEmpty(bac.value) && !digitsOnly(bac.value)) {
        setFieldError(bac, 'Bac number must be digits only');
    } else {
        setFieldError(bac, '');
    }

    const numericFields = [
        'STUDENT_ID_CARD_NUMBER',
        'STUDENT_BIRTHDATE_CERTIFICATE_NUMBER',
        'STUDENT_SCHOOL_SUB_CARD_NUMBER',
        'STUDENT_POSTAL_ACCOUNT_NUMBER',
        'CONSULATE_NUMBER'
    ];
    numericFields.forEach((n) => {
        const f = getField(n);
        if (f && !isEmpty(f.value) && !digitsOnly(f.value)) {
            setFieldError(f, 'This field must be a number');
        } else {
            setFieldError(f, '');
        }
    });

    const siblings = getField('STUDENT_NUMBER_OF_SIBLINGS');
    const sisters = getField('STUDENT_NUMBER_OF_SISTERS');
    const order = getField('STUDENT_ORDER_AMONG_SIBLINGS');
    const sibVal = siblings ? toIntOrNull(siblings.value) : null;
    const sisVal = sisters ? toIntOrNull(sisters.value) : null;
    const ordVal = order ? toIntOrNull(order.value) : null;

    if (siblings && sibVal !== null) {
        if (sibVal < 0 || sibVal > 30) setFieldError(siblings, 'Number of siblings must be between 0 and 30');
        else setFieldError(siblings, '');
    } else {
        setFieldError(siblings, '');
    }

    if (sisters && sisVal !== null) {
        if (sibVal !== null && sisVal > sibVal) setFieldError(sisters, 'Number of sisters cannot exceed number of siblings');
        else if (sisVal < 0 || sisVal > 30) setFieldError(sisters, 'Number of sisters must be between 0 and 30');
        else setFieldError(sisters, '');
    } else {
        setFieldError(sisters, '');
    }

    if (order && ordVal !== null) {
        if (ordVal < 1) {
            setFieldError(order, 'Order among siblings must be at least 1');
        } else if (sibVal !== null && ordVal > (sibVal + 1)) {
            setFieldError(order, 'Order among siblings must be at most number of siblings + 1');
        } else {
            setFieldError(order, '');
        }
    } else {
        setFieldError(order, '');
    }

    const enforceNoLatin = (name) => {
        const f = getField(name);
        if (!f) return;
        if (!isEmpty(f.value) && containsLatin(f.value)) setFieldError(f, 'This field must not contain English letters');
        else setFieldError(f, '');
    };
    const enforceNoArabic = (name) => {
        const f = getField(name);
        if (!f) return;
        if (!isEmpty(f.value) && containsArabic(f.value)) setFieldError(f, 'This field must not contain Arabic letters');
        else setFieldError(f, '');
    };

    [
        'STUDENT_FIRST_NAME_AR','STUDENT_LAST_NAME_AR',
        'BP_STREET_AR','PERS_STREET_AR',
        'FATHER_FIRST_NAME_AR','FATHER_LAST_NAME_AR',
        'MOTHER_FIRST_NAME_AR','MOTHER_LAST_NAME_AR',
        'CONTACT_FIRST_NAME_AR','CONTACT_LAST_NAME_AR',
        'CONTACT_STREET_AR'
    ].forEach(enforceNoLatin);

    [
        'STUDENT_FIRST_NAME_EN','STUDENT_LAST_NAME_EN',
        'BP_STREET_EN','PERS_STREET_EN',
        'FATHER_FIRST_NAME_EN','FATHER_LAST_NAME_EN',
        'MOTHER_FIRST_NAME_EN','MOTHER_LAST_NAME_EN',
        'CONTACT_FIRST_NAME_EN','CONTACT_LAST_NAME_EN',
        'CONTACT_STREET_EN'
    ].forEach(enforceNoArabic);
};

const bindValidation = () => {
    const form = document.getElementById('wizardForm');
    if (!form) return;
    const fields = Array.from(form.querySelectorAll('input, select, textarea'));
    fields.forEach((f) => {
        f.addEventListener('input', validateAll);
        f.addEventListener('change', validateAll);
        f.addEventListener('blur', validateAll);
    });

    form.addEventListener('submit', function(e) {
        validateAll();
        if (!form.checkValidity()) {
            e.preventDefault();
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                const details = firstInvalid.closest('details.accordion-section');
                if (details) details.open = true;
                firstInvalid.focus();
                firstInvalid.reportValidity();
            }
        }
    });

    validateAll();
};

bindValidation();
</script>

</body>
</html>
