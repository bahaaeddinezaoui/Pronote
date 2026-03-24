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

$secretary_id = null;
$stmt = $conn->prepare("SELECT SECRETARY_ID FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $secretary_id = (int)$row['SECRETARY_ID'];
}
$stmt->close();

function upsertAddress($conn, $existingId, $street_en, $street_ar, $country, $wilaya, $daira, $commune) {
    $country = intval($country);
    $wilaya = intval($wilaya);
    $daira = intval($daira);
    $commune = intval($commune);

    if ($wilaya <= 0) $wilaya = null;
    if ($daira <= 0) $daira = null;
    if ($commune <= 0) $commune = null;

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
$updatedSerial = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
    $serial = trim($_POST['STUDENT_SERIAL_NUMBER'] ?? '');
    $originalSerial = trim($_POST['ORIGINAL_STUDENT_SERIAL_NUMBER'] ?? '');

    if ($originalSerial === '') {
        $originalSerial = $serial;
    }

    if ($serial === '') {
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
            $stmtDup = $conn->prepare("SELECT 1 FROM student WHERE STUDENT_SERIAL_NUMBER = ? LIMIT 1");
            if (!$stmtDup) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmtDup->bind_param("s", $serial);
            $stmtDup->execute();
            $stmtDup->store_result();
            $exists = ($stmtDup->num_rows > 0);
            $stmtDup->close();
            if ($exists) {
                throw new Exception("Serial Number already exists.");
            }

            $stmtDelMcPre = $conn->prepare("DELETE FROM student_military_certificate WHERE student_serial_number = ?");
            if ($stmtDelMcPre) {
                $stmtDelMcPre->bind_param("s", $originalSerial);
                $stmtDelMcPre->execute();
                $stmtDelMcPre->close();
            }

            $stmtDelHobbyPre = $conn->prepare("DELETE FROM student_hobby WHERE student_serial_number = ?");
            if ($stmtDelHobbyPre) {
                $stmtDelHobbyPre->bind_param("s", $originalSerial);
                $stmtDelHobbyPre->execute();
                $stmtDelHobbyPre->close();
            }

            $stmtDelCombat = $conn->prepare("DELETE FROM student_combat_outfit WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtDelCombat) {
                $stmtDelCombat->bind_param("s", $originalSerial);
                $stmtDelCombat->execute();
                $stmtDelCombat->close();
            }

            $stmtDelParade = $conn->prepare("DELETE FROM student_parade_uniform WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtDelParade) {
                $stmtDelParade->bind_param("s", $originalSerial);
                $stmtDelParade->execute();
                $stmtDelParade->close();
            }

            $stmtDelParent = $conn->prepare("DELETE FROM student_parent_info WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtDelParent) {
                $stmtDelParent->bind_param("s", $originalSerial);
                $stmtDelParent->execute();
                $stmtDelParent->close();
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

        $typesStudent = "ii" . "ssss" . "i" . "ssss" . "dd" . "s" . "d" . "ii" . str_repeat('s', 8) . "i" . "s" . "iiii" . "ss" . "iii" . "ss";
        $stmtStudent->bind_param(
            $typesStudent,
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
            $stmtUpdateSerial = $conn->prepare("UPDATE student SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if (!$stmtUpdateSerial) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmtUpdateSerial->bind_param("ss", $serial, $originalSerial);
            $stmtUpdateSerial->execute();
            $stmtUpdateSerial->close();

            $stmtCascadeEmg = $conn->prepare("UPDATE student_emergency_contact SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtCascadeEmg) {
                $stmtCascadeEmg->bind_param("ss", $serial, $originalSerial);
                $stmtCascadeEmg->execute();
                $stmtCascadeEmg->close();
            }

            $stmtCascadeAbsent = $conn->prepare("UPDATE student_gets_absent SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtCascadeAbsent) {
                $stmtCascadeAbsent->bind_param("ss", $serial, $originalSerial);
                $stmtCascadeAbsent->execute();
                $stmtCascadeAbsent->close();
            }

            $stmtCascadeObs = $conn->prepare("UPDATE teacher_makes_an_observation_for_a_student SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtCascadeObs) {
                $stmtCascadeObs->bind_param("ss", $serial, $originalSerial);
                $stmtCascadeObs->execute();
                $stmtCascadeObs->close();
            }

            $stmtCascadeRewards = $conn->prepare("UPDATE secretary_rewards_student SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtCascadeRewards) {
                $stmtCascadeRewards->bind_param("ss", $serial, $originalSerial);
                $stmtCascadeRewards->execute();
                $stmtCascadeRewards->close();
            }

            $stmtCascadePunishments = $conn->prepare("UPDATE secretary_punishes_student SET STUDENT_SERIAL_NUMBER = ? WHERE STUDENT_SERIAL_NUMBER = ?");
            if ($stmtCascadePunishments) {
                $stmtCascadePunishments->bind_param("ss", $serial, $originalSerial);
                $stmtCascadePunishments->execute();
                $stmtCascadePunishments->close();
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
        $message = "Student updated successfully.";
        $msg_type = "success";

        if ($serial !== $originalSerial) {
            $updatedSerial = $serial;
        }

    } catch (Throwable $e) {
        $conn->rollback();
        $message = $e->getMessage();
        $msg_type = "error";
    }
}

after_update:

if ($updatedSerial !== null) {
    $loadedSerial = $updatedSerial;
}

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

$reward_types = [];
$res = $conn->query("SELECT REWARD_TYPE_ID, REWARD_LABEL_EN, REWARD_LABEL_AR, REWARD_DURATION FROM reward_type ORDER BY REWARD_LABEL_EN");
if ($res) {
    while ($r = $res->fetch_assoc()) $reward_types[] = $r;
}

$punishment_types = [];
$res = $conn->query("SELECT PUNISHMENT_TYPE_ID, PUNISHMENT_LABEL_EN, PUNISHMENT_LABEL_AR, PUNISHMENT_DURATION FROM punishment_type ORDER BY PUNISHMENT_LABEL_EN");
if ($res) {
    while ($r = $res->fetch_assoc()) $punishment_types[] = $r;
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

$loadedSerial = isset($loadedSerial) && $loadedSerial !== '' ? $loadedSerial : trim($_GET['serial'] ?? '');
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
        .form-group label { display: none; }
        .form-group { position: relative; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 0.8rem 1rem; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-lg); 
            font-size: 0.95rem; 
            background: var(--bg-secondary); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            border-color: var(--primary-color); 
            box-shadow: 0 4px 20px rgba(111, 66, 193, 0.1);
            background: var(--bg-primary);
            transform: translateY(-2px);
        }
        .form-group::after {
            content: attr(data-label);
            position: absolute;
            top: -0.5rem;
            left: 0.8rem;
            padding: 0 0.4rem;
            background: var(--bg-primary);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 4px;
            opacity: 0;
            transform: translateY(5px);
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 1;
        }
        .form-group:focus-within::after,
        .form-group.has-value::after {
            opacity: 1;
            transform: translateY(0);
            color: var(--primary-color);
        }
        .form-group.has-value::after { color: var(--text-secondary); }
        .form-group:focus-within::after { color: var(--primary-color); }

        .tabs-list { width: 240px; }
        .tab-button { 
            border: none;
            background: transparent;
            padding: 0.8rem 1rem;
            color: var(--text-secondary);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        .tab-button:hover { background: var(--bg-secondary); transform: none; color: var(--text-primary); }
        .tab-button.active { 
            background: var(--bg-secondary); 
            color: var(--primary-color); 
            box-shadow: none;
        }
        .tab-button.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-color);
            border-radius: 0 4px 4px 0;
        }
        .tab-button::after { display: none; }
        
        .tab-panel { border: none; box-shadow: var(--shadow-sm); padding: 2.5rem; }
        .form-section-title { border: none; font-size: 1.5rem; margin-bottom: 2rem; color: var(--text-primary); }

        /* Bubble Buttons for selection */
        .bubble-group { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem; }
        .bubble-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }
        .bubble-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        .bubble-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        }
        .bubble-label { display: block; margin-bottom: 0.75rem; font-weight: 700; color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }

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

        .tabs-container { display: flex; gap: 2rem; align-items: flex-start; }
        .tabs-list { display: flex; flex-direction: column; gap: 0.5rem; width: 280px; flex-shrink: 0; position: sticky; top: 2rem; }
        .tab-button { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.3s ease; text-align: left; font-weight: 700; color: var(--text-secondary); width: 100%; font-family: inherit; font-size: 0.95rem; }
        .tab-button:hover { background: rgba(111, 66, 193, 0.05); border-color: var(--primary-color); color: var(--primary-color); transform: translateX(5px); }
        .tab-button.active { background: var(--primary-color); color: white; border-color: var(--primary-color); box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3); }
        .tab-button::after { content: '▸'; opacity: 0.5; transition: transform 0.3s ease; }
        .tab-button.active::after { transform: rotate(90deg); opacity: 1; }
        .tab-content-container { flex-grow: 1; min-width: 0; }
        .tab-panel { display: none; animation: fadeIn 0.4s ease; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md); }
        .tab-panel.active { display: block; }

        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; padding: 1rem; z-index: 2000; }
        .modal-backdrop.active { display: flex; }
        .modal-card { width: 100%; max-width: 640px; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); padding: 1.25rem; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .modal-title { font-weight: 800; font-size: 1.1rem; }
        .modal-close { border: none; background: transparent; cursor: pointer; font-size: 1.5rem; line-height: 1; color: var(--text-secondary); }
        .modal-actions { display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem; }
        .btn-secondary { background: var(--border-color); color: var(--text-primary); }
        
        @media (max-width: 992px) {
            .tabs-container { flex-direction: column; }
            .tabs-list { width: 100%; position: static; flex-direction: row; overflow-x: auto; padding-bottom: 0.5rem; }
            .tab-button { white-space: nowrap; width: auto; }
        }

        .form-card.main-form-card { background: transparent; border: none; padding: 0; box-shadow: none; }
        .form-section-title { grid-column: 1 / -1; font-size: 1.1rem; font-weight: 700; color: var(--primary-color); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 2px solid var(--bg-secondary); padding-bottom: 0.5rem; }
        .skirt-field { display:none; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

        <div class="home-container">
            <div style="margin-bottom: 2rem;">
                <h1><?php echo t('welcome_secretary_edit'); ?></h1>
                <p style="color: var(--text-secondary);"><?php echo t('edit_student_subtitle'); ?></p>
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

            <?php if ($loadedSerial !== '' && !$student && $message !== 'Student not found.'): ?>
                <div class="alert error"><?php echo t('student_not_found'); ?></div>
            <?php endif; ?>

            <?php if ($student): ?>
            <div class="form-card main-form-card">
                <form method="POST" action="secretary_edit_student.php?serial=<?php echo urlencode($loadedSerial); ?>" id="wizardForm">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="ORIGINAL_STUDENT_SERIAL_NUMBER" value="<?php echo h($student['STUDENT_SERIAL_NUMBER']); ?>">

                    <div class="tabs-container">
                        <div class="tabs-list">
                            <button type="button" class="tab-button active" onclick="switchTab(event, 'tab-personal')"><?php echo t('personal_details'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-academic')"><?php echo t('academic_info'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-family')"><?php echo t('family_information'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-address')"><?php echo t('label_personal_address'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-uniforms')"><?php echo t('uniforms'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-other')"><?php echo t('step_other_details'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-rewards')"><?php echo t('rewards'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-punishments')"><?php echo t('punishments'); ?></button>
                            <button type="button" class="tab-button" onclick="switchTab(event, 'tab-emergency')"><?php echo t('step_emergency_contact'); ?></button>
                        </div>

                        <div class="tab-content-container">
                            <!-- Tab 1: Personal Details -->
                            <div id="tab-personal" class="tab-panel active">
                                <h2 class="form-section-title"><?php echo t('personal_details'); ?></h2>
                                <div class="form-grid">
                                    <div class="form-group" data-label="<?php echo t('serial_number'); ?>">
                                        <input type="text" name="STUDENT_SERIAL_NUMBER" placeholder="<?php echo t('serial_number'); ?>" value="<?php echo h($student['STUDENT_SERIAL_NUMBER']); ?>">
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('label_first_name_en'); ?>"><input type="text" name="STUDENT_FIRST_NAME_EN" placeholder="<?php echo t('label_first_name_en'); ?>" required value="<?php echo h($student['STUDENT_FIRST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_last_name_en'); ?>"><input type="text" name="STUDENT_LAST_NAME_EN" placeholder="<?php echo t('label_last_name_en'); ?>" required value="<?php echo h($student['STUDENT_LAST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_first_name_ar'); ?>"><input type="text" name="STUDENT_FIRST_NAME_AR" placeholder="<?php echo t('label_first_name_ar'); ?>" dir="rtl" value="<?php echo h($student['STUDENT_FIRST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_last_name_ar'); ?>"><input type="text" name="STUDENT_LAST_NAME_AR" placeholder="<?php echo t('label_last_name_ar'); ?>" dir="rtl" value="<?php echo h($student['STUDENT_LAST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_sex'); ?>">
                                        <select id="SEX_SELECT" name="STUDENT_SEX" onchange="toggleSkirtFields()">
                                            <option value="" disabled <?php echo ($student['STUDENT_SEX'] ? '' : 'selected'); ?>><?php echo t('label_sex'); ?></option>
                                            <option value="Male" <?php echo selectedAttr($student['STUDENT_SEX'], 'Male'); ?>><?php echo t('male'); ?></option>
                                            <option value="Female" <?php echo selectedAttr($student['STUDENT_SEX'], 'Female'); ?>><?php echo t('female'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('label_birth_date'); ?>"><input type="date" name="STUDENT_BIRTH_DATE" placeholder="<?php echo t('label_birth_date'); ?>" value="<?php echo h($student['STUDENT_BIRTH_DATE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_phone'); ?>"><input type="text" name="STUDENT_PERSONAL_PHONE" placeholder="<?php echo t('label_phone'); ?>" value="<?php echo h($student['STUDENT_PERSONAL_PHONE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_blood_type'); ?>">
                                        <select name="STUDENT_BLOOD_TYPE">
                                            <option value="" disabled <?php echo ($student['STUDENT_BLOOD_TYPE'] ? '' : 'selected'); ?>><?php echo t('label_blood_type'); ?></option>
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
                                    <div class="form-group" data-label="<?php echo t('label_height'); ?>"><input type="number" step="0.01" name="STUDENT_HEIGHT_CM" placeholder="<?php echo t('label_height'); ?>" value="<?php echo h($student['STUDENT_HEIGHT_CM']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_weight'); ?>"><input type="number" step="0.01" name="STUDENT_WEIGHT_KG" placeholder="<?php echo t('label_weight'); ?>" value="<?php echo h($student['STUDENT_WEIGHT_KG']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_is_foreign'); ?>">
                                        <select id="IS_FOREIGN_SELECT" name="STUDENT_IS_FOREIGN" onchange="toggleForeignFields()">
                                            <option value="No" <?php echo selectedAttr($student['STUDENT_IS_FOREIGN'], 'No'); ?>><?php echo t('no'); ?></option>
                                            <option value="Yes" <?php echo selectedAttr($student['STUDENT_IS_FOREIGN'], 'Yes'); ?>><?php echo t('yes'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Academic Info -->
                            <div id="tab-academic" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('academic_info'); ?></h2>
                                <div class="form-grid">
                                    <div class="form-group" data-label="<?php echo t('category'); ?>">
                                        <select id="CATEGORY_ID" name="CATEGORY_ID" required>
                                            <option value="" disabled><?php echo t('category'); ?></option>
                                            <?php foreach ($categories as $c): ?>
                                                <?php $catName = ($LANG === 'ar' && !empty($c['CATEGORY_NAME_AR'])) ? $c['CATEGORY_NAME_AR'] : $c['CATEGORY_NAME_EN']; ?>
                                                <option value="<?php echo $c['CATEGORY_ID']; ?>" <?php echo selectedAttr($student['CATEGORY_ID'], $c['CATEGORY_ID']); ?>><?php echo h($catName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" id="SECTION_GROUP" data-label="<?php echo t('section'); ?>">
                                        <select id="SECTION_ID" name="SECTION_ID" required>
                                            <option value="" disabled><?php echo t('section'); ?></option>
                                            <?php foreach ($sections as $s): ?>
                                                <?php $secName = ($LANG === 'ar' && !empty($s['SECTION_NAME_AR'])) ? $s['SECTION_NAME_AR'] : $s['SECTION_NAME_EN']; ?>
                                                <option value="<?php echo $s['SECTION_ID']; ?>" data-category="<?php echo $s['CATEGORY_ID']; ?>" <?php echo selectedAttr($student['SECTION_ID'], $s['SECTION_ID']); ?>><?php echo h($secName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('speciality'); ?>">
                                        <select name="STUDENT_SPECIALITY_ID">
                                            <option value="" disabled><?php echo t('speciality'); ?></option>
                                            <?php foreach ($specialities as $sp): ?>
                                                <?php $spName = ($LANG === 'ar' && !empty($sp['speciality_name_ar'])) ? $sp['speciality_name_ar'] : $sp['speciality_name_en']; ?>
                                                <option value="<?php echo $sp['student_speciality_id']; ?>" <?php echo selectedAttr($student['STUDENT_SPECIALITY_ID'] ?? '', $sp['student_speciality_id']); ?>><?php echo h($spName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('academic_level'); ?>">
                                        <select name="STUDENT_ACADEMIC_LEVEL_ID">
                                            <option value="" disabled><?php echo t('academic_level'); ?></option>
                                            <?php foreach ($academic_levels as $al): ?>
                                                <?php $alName = ($LANG === 'ar' && !empty($al['ACADEMIC_LEVEL_AR'])) ? $al['ACADEMIC_LEVEL_AR'] : $al['ACADEMIC_LEVEL_EN']; ?>
                                                <option value="<?php echo $al['ACADEMIC_LEVEL_ID']; ?>" <?php echo selectedAttr($student['STUDENT_ACADEMIC_LEVEL_ID'] ?? '', $al['ACADEMIC_LEVEL_ID']); ?>><?php echo h($alName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('academic_average'); ?>"><input type="number" step="0.01" name="STUDENT_ACADEMIC_AVERAGE" placeholder="<?php echo t('academic_average'); ?>" value="<?php echo h($student['STUDENT_ACADEMIC_AVERAGE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('bac_number'); ?>"><input type="text" name="STUDENT_BACCALAUREATE_SUB_NUMBER" placeholder="<?php echo t('bac_number'); ?>" value="<?php echo h($student['STUDENT_BACCALAUREATE_SUB_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('grade_rank'); ?>">
                                        <select name="STUDENT_GRADE_ID">
                                            <option value="" disabled><?php echo t('grade_rank'); ?></option>
                                            <?php foreach ($grades as $g): ?>
                                                <?php $gradeName = ($LANG === 'ar' && !empty($g['GRADE_NAME_AR'])) ? $g['GRADE_NAME_AR'] : $g['GRADE_NAME_EN']; ?>
                                                <option value="<?php echo $g['GRADE_ID']; ?>" <?php echo selectedAttr($student['STUDENT_GRADE_ID'], $g['GRADE_ID']); ?>><?php echo h($gradeName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('recruitment_source'); ?>">
                                        <select id="RECRUITMENT_SOURCE_ID" name="RECRUITMENT_SOURCE_ID">
                                            <option value="" disabled><?php echo t('recruitment_source'); ?></option>
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
                                    <div class="form-group" data-label="<?php echo t('army'); ?>">
                                        <select name="STUDENT_ARMY_ID">
                                            <option value="" disabled><?php echo t('army'); ?></option>
                                            <?php foreach ($armies as $a): ?>
                                                <?php $armyName = ($LANG === 'ar' && !empty($a['ARMY_NAME_AR'])) ? $a['ARMY_NAME_AR'] : $a['ARMY_NAME_EN']; ?>
                                                <option value="<?php echo $a['ARMY_ID']; ?>" <?php echo selectedAttr($student['STUDENT_ARMY_ID'], $a['ARMY_ID']); ?>><?php echo h($armyName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: Family Info -->
                            <div id="tab-family" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('family_information'); ?></h2>
                                <div class="form-grid">
                                    <div class="form-group" data-label="<?php echo t('label_father_first_en'); ?>"><input type="text" name="FATHER_FIRST_NAME_EN" placeholder="<?php echo t('label_father_first_en'); ?>" value="<?php echo h($student['FATHER_FIRST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_father_last_en'); ?>"><input type="text" name="FATHER_LAST_NAME_EN" placeholder="<?php echo t('label_father_last_en'); ?>" value="<?php echo h($student['FATHER_LAST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_father_first_ar'); ?>"><input type="text" name="FATHER_FIRST_NAME_AR" placeholder="<?php echo t('label_father_first_ar'); ?>" dir="rtl" value="<?php echo h($student['FATHER_FIRST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_father_last_ar'); ?>"><input type="text" name="FATHER_LAST_NAME_AR" placeholder="<?php echo t('label_father_last_ar'); ?>" dir="rtl" value="<?php echo h($student['FATHER_LAST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_father_prof_en'); ?>">
                                        <select name="FATHER_PROFESSION_ID">
                                            <option value="" disabled><?php echo t('label_father_prof_en'); ?></option>
                                            <?php foreach ($professions as $p): ?>
                                                <?php $pName = ($LANG === 'ar' && !empty($p['profession_name_ar'])) ? $p['profession_name_ar'] : $p['profession_name_en']; ?>
                                                <option value="<?php echo $p['profession_id']; ?>" <?php echo selectedAttr($student['FATHER_PROFESSION_ID'] ?? '', $p['profession_id']); ?>><?php echo h($pName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('label_mother_first_en'); ?>"><input type="text" name="MOTHER_FIRST_NAME_EN" placeholder="<?php echo t('label_mother_first_en'); ?>" value="<?php echo h($student['MOTHER_FIRST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_mother_last_en'); ?>"><input type="text" name="MOTHER_LAST_NAME_EN" placeholder="<?php echo t('label_mother_last_en'); ?>" value="<?php echo h($student['MOTHER_LAST_NAME_EN']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_mother_first_ar'); ?>"><input type="text" name="MOTHER_FIRST_NAME_AR" placeholder="<?php echo t('label_mother_first_ar'); ?>" dir="rtl" value="<?php echo h($student['MOTHER_FIRST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_mother_last_ar'); ?>"><input type="text" name="MOTHER_LAST_NAME_AR" placeholder="<?php echo t('label_mother_last_ar'); ?>" dir="rtl" value="<?php echo h($student['MOTHER_LAST_NAME_AR']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_mother_prof_en'); ?>">
                                        <select name="MOTHER_PROFESSION_ID">
                                            <option value="" disabled><?php echo t('label_mother_prof_en'); ?></option>
                                            <?php foreach ($professions as $p): ?>
                                                <?php $pName = ($LANG === 'ar' && !empty($p['profession_name_ar'])) ? $p['profession_name_ar'] : $p['profession_name_en']; ?>
                                                <option value="<?php echo $p['profession_id']; ?>" <?php echo selectedAttr($student['MOTHER_PROFESSION_ID'] ?? '', $p['profession_id']); ?>><?php echo h($pName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('orphan_status'); ?>">
                                        <select name="STUDENT_ORPHAN_STATUS">
                                            <option value="None" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'None'); ?>><?php echo t('orphan_none'); ?></option>
                                            <option value="Father" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Father'); ?>><?php echo t('orphan_father'); ?></option>
                                            <option value="Mother" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Mother'); ?>><?php echo t('orphan_mother'); ?></option>
                                            <option value="Both" <?php echo selectedAttr($student['STUDENT_ORPHAN_STATUS'], 'Both'); ?>><?php echo t('orphan_both'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('parents_situation'); ?>">
                                        <select name="STUDENT_PARENTS_SITUATION">
                                            <option value="Married" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Married'); ?>><?php echo t('married'); ?></option>
                                            <option value="Divorced" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Divorced'); ?>><?php echo t('divorced'); ?></option>
                                            <option value="Separated" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Separated'); ?>><?php echo t('separated'); ?></option>
                                            <option value="Widowed" <?php echo selectedAttr($student['STUDENT_PARENTS_SITUATION'], 'Widowed'); ?>><?php echo t('widowed'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group" data-label="<?php echo t('num_siblings'); ?>"><input type="number" name="STUDENT_NUMBER_OF_SIBLINGS" placeholder="<?php echo t('num_siblings'); ?>" value="<?php echo h($student['STUDENT_NUMBER_OF_SIBLINGS']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_sisters_count'); ?>"><input type="number" name="STUDENT_NUMBER_OF_SISTERS" placeholder="<?php echo t('label_sisters_count'); ?>" value="<?php echo h($student['STUDENT_NUMBER_OF_SISTERS']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_order_among_siblings'); ?>"><input type="number" name="STUDENT_ORDER_AMONG_SIBLINGS" placeholder="<?php echo t('label_order_among_siblings'); ?>" value="<?php echo h($student['STUDENT_ORDER_AMONG_SIBLINGS']); ?>"></div>
                                </div>
                            </div>

                            <!-- Tab 4: Address Info -->
                            <div id="tab-address" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('label_personal_address'); ?> <?php echo t('and'); ?> <?php echo t('label_birth_place_address'); ?></h2>
                                <div class="form-grid">
                                    <div class="sub-group" style="grid-column: 1 / -1;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_street_en'); ?>"><input type="text" name="BP_STREET_EN" placeholder="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_street_en'); ?>" value="<?php echo h($bp['BP_STREET_EN'] ?? ''); ?>"></div>
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_street_ar'); ?>"><input type="text" name="BP_STREET_AR" placeholder="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_street_ar'); ?>" dir="rtl" value="<?php echo h($bp['BP_STREET_AR'] ?? ''); ?>"></div>
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_country'); ?>">
                                                <select class="country-select" data-prefix="BP_" name="BP_COUNTRY_ID">
                                                    <option value="" disabled><?php echo t('label_birth_place_address'); ?> - <?php echo t('label_country'); ?></option>
                                                    <?php foreach ($countries as $c): ?>
                                                        <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                        <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($bp['BP_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_wilaya'); ?>">
                                                <select id="BP_WILAYA_ID" name="BP_WILAYA_ID" class="wilaya-select" data-prefix="BP_" <?php echo (!empty($bpWilayas) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_birth_place_address'); ?> - <?php echo t('label_wilaya'); ?></option>
                                                    <?php foreach ($bpWilayas as $w): ?>
                                                        <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                        <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($bp['BP_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_daira'); ?>">
                                                <select id="BP_DAIRA_ID" name="BP_DAIRA_ID" class="daira-select" data-prefix="BP_" <?php echo (!empty($bpDairas) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_birth_place_address'); ?> - <?php echo t('label_daira'); ?></option>
                                                    <?php foreach ($bpDairas as $d): ?>
                                                        <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                        <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($bp['BP_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_birth_place_address'); ?> - <?php echo t('label_commune'); ?>">
                                                <select id="BP_COMMUNE_ID" name="BP_COMMUNE_ID" <?php echo (!empty($bpCommunes) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_birth_place_address'); ?> - <?php echo t('label_commune'); ?></option>
                                                    <?php foreach ($bpCommunes as $cc): ?>
                                                        <?php $cName = ($LANG === 'ar' && !empty($cc['COMMUNE_NAME_AR'])) ? $cc['COMMUNE_NAME_AR'] : $cc['COMMUNE_NAME_EN']; ?>
                                                        <option value="<?php echo $cc['COMMUNE_ID']; ?>" <?php echo selectedAttr($bp['BP_COMMUNE_ID'] ?? '', $cc['COMMUNE_ID']); ?>><?php echo h($cName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sub-group" style="grid-column: 1 / -1;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_street_en'); ?>"><input type="text" name="PERS_STREET_EN" placeholder="<?php echo t('label_personal_address'); ?> - <?php echo t('label_street_en'); ?>" value="<?php echo h($pers['PERS_STREET_EN'] ?? ''); ?>"></div>
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_street_ar'); ?>"><input type="text" name="PERS_STREET_AR" placeholder="<?php echo t('label_personal_address'); ?> - <?php echo t('label_street_ar'); ?>" dir="rtl" value="<?php echo h($pers['PERS_STREET_AR'] ?? ''); ?>"></div>
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_country'); ?>">
                                                <select class="country-select" data-prefix="PERS_" name="PERS_COUNTRY_ID">
                                                    <option value="" disabled><?php echo t('label_personal_address'); ?> - <?php echo t('label_country'); ?></option>
                                                    <?php foreach ($countries as $c): ?>
                                                        <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                        <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($pers['PERS_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_wilaya'); ?>">
                                                <select id="PERS_WILAYA_ID" name="PERS_WILAYA_ID" class="wilaya-select" data-prefix="PERS_" <?php echo (!empty($persWilayas) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_personal_address'); ?> - <?php echo t('label_wilaya'); ?></option>
                                                    <?php foreach ($persWilayas as $w): ?>
                                                        <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                        <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($pers['PERS_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_daira'); ?>">
                                                <select id="PERS_DAIRA_ID" name="PERS_DAIRA_ID" class="daira-select" data-prefix="PERS_" <?php echo (!empty($persDairas) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_personal_address'); ?> - <?php echo t('label_daira'); ?></option>
                                                    <?php foreach ($persDairas as $d): ?>
                                                        <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                        <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($pers['PERS_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" data-label="<?php echo t('label_personal_address'); ?> - <?php echo t('label_commune'); ?>">
                                                <select id="PERS_COMMUNE_ID" name="PERS_COMMUNE_ID" <?php echo (!empty($persCommunes) ? '' : 'disabled'); ?>>
                                                    <option value="" disabled><?php echo t('label_personal_address'); ?> - <?php echo t('label_commune'); ?></option>
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

                            <!-- Tab 5: Uniforms -->
                            <div id="tab-uniforms" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('uniforms'); ?></h2>
                                <div class="form-grid">
                                    <div class="form-group" data-label="<?php echo t('outfit1_number'); ?>"><input type="text" name="FIRST_OUTFIT_NUMBER" placeholder="<?php echo t('outfit1_number'); ?>" value="<?php echo h($student['FIRST_OUTFIT_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('outfit1_size'); ?>"><input type="text" name="FIRST_OUTFIT_SIZE" placeholder="<?php echo t('outfit1_size'); ?>" value="<?php echo h($student['FIRST_OUTFIT_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('outfit2_number'); ?>"><input type="text" name="SECOND_OUTFIT_NUMBER" placeholder="<?php echo t('outfit2_number'); ?>" value="<?php echo h($student['SECOND_OUTFIT_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('outfit2_size'); ?>"><input type="text" name="SECOND_OUTFIT_SIZE" placeholder="<?php echo t('outfit2_size'); ?>" value="<?php echo h($student['SECOND_OUTFIT_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('shoe_size'); ?>"><input type="text" name="COMBAT_SHOE_SIZE" placeholder="<?php echo t('shoe_size'); ?>" value="<?php echo h($student['COMBAT_SHOE_SIZE']); ?>"></div>

                                    <div class="form-group" data-label="<?php echo t('summer_jacket_size'); ?>"><input type="text" name="SUMMER_JACKET_SIZE" placeholder="<?php echo t('summer_jacket_size'); ?>" value="<?php echo h($student['SUMMER_JACKET_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('winter_jacket_size'); ?>"><input type="text" name="WINTER_JACKET_SIZE" placeholder="<?php echo t('winter_jacket_size'); ?>" value="<?php echo h($student['WINTER_JACKET_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('summer_trousers_size'); ?>"><input type="text" name="SUMMER_TROUSERS_SIZE" placeholder="<?php echo t('summer_trousers_size'); ?>" value="<?php echo h($student['SUMMER_TROUSERS_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('winter_trousers_size'); ?>"><input type="text" name="WINTER_TROUSERS_SIZE" placeholder="<?php echo t('winter_trousers_size'); ?>" value="<?php echo h($student['WINTER_TROUSERS_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('summer_shirt_size'); ?>"><input type="text" name="SUMMER_SHIRT_SIZE" placeholder="<?php echo t('summer_shirt_size'); ?>" value="<?php echo h($student['SUMMER_SHIRT_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('winter_shirt_size'); ?>"><input type="text" name="WINTER_SHIRT_SIZE" placeholder="<?php echo t('winter_shirt_size'); ?>" value="<?php echo h($student['WINTER_SHIRT_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('summer_hat_size'); ?>"><input type="text" name="SUMMER_HAT_SIZE" placeholder="<?php echo t('summer_hat_size'); ?>" value="<?php echo h($student['SUMMER_HAT_SIZE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('winter_hat_size'); ?>"><input type="text" name="WINTER_HAT_SIZE" placeholder="<?php echo t('winter_hat_size'); ?>" value="<?php echo h($student['WINTER_HAT_SIZE']); ?>"></div>
                                    <div class="form-group skirt-field" data-label="<?php echo t('summer_skirt_size'); ?>"><input type="text" name="SUMMER_SKIRT_SIZE" placeholder="<?php echo t('summer_skirt_size'); ?>" value="<?php echo h($student['SUMMER_SKIRT_SIZE']); ?>"></div>
                                    <div class="form-group skirt-field" data-label="<?php echo t('winter_skirt_size'); ?>"><input type="text" name="WINTER_SKIRT_SIZE" placeholder="<?php echo t('winter_skirt_size'); ?>" value="<?php echo h($student['WINTER_SKIRT_SIZE']); ?>"></div>
                                </div>
                            </div>

                            <!-- Tab 6: Other Details -->
                            <div id="tab-other" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('step_other_details'); ?></h2>
                                <div class="form-grid">
                                    <div class="form-group" data-label="<?php echo t('id_card_num'); ?>"><input type="text" name="STUDENT_ID_CARD_NUMBER" placeholder="<?php echo t('id_card_num'); ?>" value="<?php echo h($student['STUDENT_ID_CARD_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('birth_cert_num'); ?>"><input type="text" name="STUDENT_BIRTHDATE_CERTIFICATE_NUMBER" placeholder="<?php echo t('birth_cert_num'); ?>" value="<?php echo h($student['STUDENT_BIRTHDATE_CERTIFICATE_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('school_card_number'); ?>"><input type="text" name="STUDENT_SCHOOL_SUB_CARD_NUMBER" placeholder="<?php echo t('school_card_number'); ?>" value="<?php echo h($student['STUDENT_SCHOOL_SUB_CARD_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('school_sub_date'); ?>"><input type="date" name="STUDENT_SCHOOL_SUB_DATE" placeholder="<?php echo t('school_sub_date'); ?>" value="<?php echo h($student['STUDENT_SCHOOL_SUB_DATE']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('laptop_serial'); ?>"><input type="text" name="STUDENT_LAPTOP_SERIAL_NUMBER" placeholder="<?php echo t('laptop_serial'); ?>" value="<?php echo h($student['STUDENT_LAPTOP_SERIAL_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('postal_account'); ?>"><input type="text" name="STUDENT_POSTAL_ACCOUNT_NUMBER" placeholder="<?php echo t('postal_account'); ?>" value="<?php echo h($student['STUDENT_POSTAL_ACCOUNT_NUMBER']); ?>"></div>
                                    <div class="form-group" data-label="<?php echo t('label_mil_necklace'); ?>">
                                        <select name="STUDENT_MILITARY_NECKLACE">
                                            <option value="No" <?php echo selectedAttr($student['STUDENT_MILITARY_NECKLACE'], 'No'); ?>><?php echo t('no'); ?></option>
                                            <option value="Yes" <?php echo selectedAttr($student['STUDENT_MILITARY_NECKLACE'], 'Yes'); ?>><?php echo t('yes'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="grid-column: span 2;" data-label="<?php echo t('educational_certificates'); ?>"><textarea name="STUDENT_EDUCATIONAL_CERTIFICATES" placeholder="<?php echo t('educational_certificates'); ?>" rows="2"><?php echo h($student['STUDENT_EDUCATIONAL_CERTIFICATES']); ?></textarea></div>
                                    <div class="form-group" style="grid-column: span 2;">
                                        <label class="bubble-label"><?php echo t('military_certificates'); ?></label>
                                        <div class="bubble-group" id="military-certificates-bubbles">
                                            <?php foreach ($military_certificates as $mc): ?>
                                                <?php 
                                                    $mcName = ($LANG === 'ar' && !empty($mc['military_certificate_ar'])) ? $mc['military_certificate_ar'] : $mc['military_certificate_en']; 
                                                    $isSelected = in_array((int)$mc['military_certificate_id'], $selected_military_certificate_ids, true);
                                                ?>
                                                <div class="bubble-btn <?php echo $isSelected ? 'active' : ''; ?>" 
                                                     data-id="<?php echo $mc['military_certificate_id']; ?>" 
                                                     onclick="toggleBubble(this, 'MILITARY_CERTIFICATE_IDS[]')">
                                                    <?php echo h($mcName); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div id="military-certificates-inputs">
                                            <?php foreach ($selected_military_certificate_ids as $id): ?>
                                                <input type="hidden" name="MILITARY_CERTIFICATE_IDS[]" value="<?php echo $id; ?>" data-bubble-id="<?php echo $id; ?>">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="form-group" style="grid-column: span 2;" data-label="<?php echo t('hobbies'); ?>">
                                        <label class="bubble-label"><?php echo t('hobbies'); ?></label>
                                        <div class="bubble-group" id="hobbies-bubbles">
                                            <?php foreach ($hobbies as $hb): ?>
                                                <?php 
                                                    $hbName = ($LANG === 'ar' && !empty($hb['hobby_name_ar'])) ? $hb['hobby_name_ar'] : $hb['hobby_name_en']; 
                                                    $isSelected = in_array((int)$hb['hobby_id'], $selected_hobby_ids, true);
                                                ?>
                                                <div class="bubble-btn <?php echo $isSelected ? 'active' : ''; ?>" 
                                                     data-id="<?php echo $hb['hobby_id']; ?>" 
                                                     onclick="toggleBubble(this, 'HOBBY_IDS[]')">
                                                    <?php echo h($hbName); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div id="hobbies-inputs">
                                            <?php foreach ($selected_hobby_ids as $id): ?>
                                                <input type="hidden" name="HOBBY_IDS[]" value="<?php echo $id; ?>" data-bubble-id="<?php echo $id; ?>">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="form-group" style="grid-column: span 2;" data-label="<?php echo t('health_status'); ?>">
                                        <select name="STUDENT_HEALTH_STATUS_ID">
                                            <option value="" disabled><?php echo t('health_status'); ?></option>
                                            <?php foreach ($health_statuses as $hs): ?>
                                                <?php $hsName = ($LANG === 'ar' && !empty($hs['health_status_ar'])) ? $hs['health_status_ar'] : $hs['health_status_en']; ?>
                                                <option value="<?php echo $hs['health_status_id']; ?>" <?php echo selectedAttr($student['STUDENT_HEALTH_STATUS_ID'] ?? '', $hs['health_status_id']); ?>><?php echo h($hsName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 7: Emergency Contact -->
                            <div id="tab-emergency" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('step_emergency_contact'); ?></h2>
                                <div class="form-grid">
                                    <div class="sub-group" style="grid-column: 1 / -1; border-color:var(--border-error); background:var(--bg-error);">
                                        <div class="form-grid" style="gap: 1.5rem;">
                                            <div class="form-group" data-label="<?php echo t('label_contact_phone'); ?>"><input type="text" name="CONTACT_PHONE_NUMBER" placeholder="<?php echo t('label_contact_phone'); ?>" value="<?php echo h($student['CONTACT_PHONE_NUMBER']); ?>"></div>

                                            <div id="LOCAL_CONTACT_FIELDS" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                                <div class="form-group" data-label="<?php echo t('label_first_name_en'); ?>"><input type="text" name="CONTACT_FIRST_NAME_EN" placeholder="<?php echo t('label_first_name_en'); ?>" value="<?php echo h($student['CONTACT_FIRST_NAME_EN']); ?>"></div>
                                                <div class="form-group" data-label="<?php echo t('label_last_name_en'); ?>"><input type="text" name="CONTACT_LAST_NAME_EN" placeholder="<?php echo t('label_last_name_en'); ?>" value="<?php echo h($student['CONTACT_LAST_NAME_EN']); ?>"></div>
                                                <div class="form-group" data-label="<?php echo t('label_relation_en'); ?>">
                                                    <select name="CONTACT_RELATION_ID">
                                                        <option value="" disabled><?php echo t('label_relation_en'); ?></option>
                                                        <?php foreach ($relations as $rel): ?>
                                                            <?php $relName = ($LANG === 'ar' && !empty($rel['relation_name_ar'])) ? $rel['relation_name_ar'] : $rel['relation_name_en']; ?>
                                                            <option value="<?php echo $rel['relation_id']; ?>" <?php echo selectedAttr($student['CONTACT_RELATION_ID'] ?? '', $rel['relation_id']); ?>><?php echo h($relName); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group" data-label="<?php echo t('label_first_name_ar'); ?>"><input type="text" name="CONTACT_FIRST_NAME_AR" placeholder="<?php echo t('label_first_name_ar'); ?>" dir="rtl" value="<?php echo h($student['CONTACT_FIRST_NAME_AR']); ?>"></div>
                                                <div class="form-group" data-label="<?php echo t('label_last_name_ar'); ?>"><input type="text" name="CONTACT_LAST_NAME_AR" placeholder="<?php echo t('label_last_name_ar'); ?>" dir="rtl" value="<?php echo h($student['CONTACT_LAST_NAME_AR']); ?>"></div>

                                                <div class="sub-group" style="grid-column: 1 / -1;">
                                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_street_en'); ?>"><input type="text" name="CONTACT_STREET_EN" placeholder="<?php echo t('label_contact_address'); ?> - <?php echo t('label_street_en'); ?>" value="<?php echo h($contactAddr['CONTACT_STREET_EN'] ?? ''); ?>"></div>
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_street_ar'); ?>"><input type="text" name="CONTACT_STREET_AR" placeholder="<?php echo t('label_contact_address'); ?> - <?php echo t('label_street_ar'); ?>" dir="rtl" value="<?php echo h($contactAddr['CONTACT_STREET_AR'] ?? ''); ?>"></div>
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_country'); ?>">
                                                            <select class="country-select" data-prefix="CONTACT_" name="CONTACT_COUNTRY_ID">
                                                                <option value="" disabled><?php echo t('label_contact_address'); ?> - <?php echo t('label_country'); ?></option>
                                                                <?php foreach ($countries as $c): ?>
                                                                    <?php $countryName = ($LANG === 'ar' && !empty($c['COUNTRY_NAME_AR'])) ? $c['COUNTRY_NAME_AR'] : $c['COUNTRY_NAME_EN']; ?>
                                                                    <option value="<?php echo $c['COUNTRY_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_COUNTRY_ID'] ?? '', $c['COUNTRY_ID']); ?>><?php echo h($countryName); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_wilaya'); ?>">
                                                            <select id="CONTACT_WILAYA_ID" name="CONTACT_WILAYA_ID" class="wilaya-select" data-prefix="CONTACT_" <?php echo (!empty($contactWilayas) ? '' : 'disabled'); ?>>
                                                                <option value="" disabled><?php echo t('label_contact_address'); ?> - <?php echo t('label_wilaya'); ?></option>
                                                                <?php foreach ($contactWilayas as $w): ?>
                                                                    <?php $wName = ($LANG === 'ar' && !empty($w['WILAYA_NAME_AR'])) ? $w['WILAYA_NAME_AR'] : $w['WILAYA_NAME_EN']; ?>
                                                                    <option value="<?php echo $w['WILAYA_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_WILAYA_ID'] ?? '', $w['WILAYA_ID']); ?>><?php echo h($wName); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_daira'); ?>">
                                                            <select id="CONTACT_DAIRA_ID" name="CONTACT_DAIRA_ID" class="daira-select" data-prefix="CONTACT_" <?php echo (!empty($contactDairas) ? '' : 'disabled'); ?>>
                                                                <option value="" disabled><?php echo t('label_contact_address'); ?> - <?php echo t('label_daira'); ?></option>
                                                                <?php foreach ($contactDairas as $d): ?>
                                                                    <?php $dName = ($LANG === 'ar' && !empty($d['DAIRA_NAME_AR'])) ? $d['DAIRA_NAME_AR'] : $d['DAIRA_NAME_EN']; ?>
                                                                    <option value="<?php echo $d['DAIRA_ID']; ?>" <?php echo selectedAttr($contactAddr['CONTACT_DAIRA_ID'] ?? '', $d['DAIRA_ID']); ?>><?php echo h($dName); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group" data-label="<?php echo t('label_contact_address'); ?> - <?php echo t('label_commune'); ?>">
                                                            <select id="CONTACT_COMMUNE_ID" name="CONTACT_COMMUNE_ID" <?php echo (!empty($contactCommunes) ? '' : 'disabled'); ?>>
                                                                <option value="" disabled><?php echo t('label_contact_address'); ?> - <?php echo t('label_commune'); ?></option>
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
                                                <div class="form-group" data-label="<?php echo t('label_consulate_number'); ?>"><input type="text" name="CONSULATE_NUMBER" placeholder="<?php echo t('label_consulate_number'); ?>" value="<?php echo h($student['CONSULATE_NUMBER']); ?>"></div>
                                                <div class="form-group" style="grid-column: span 2;"><p style="font-size:0.9rem; color:var(--text-secondary);"><?php echo t('relation_consulate_note'); ?></p></div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="tab-rewards" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('rewards'); ?></h2>
                                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-bottom:1rem;">
                                    <button type="button" class="btn-search" id="BTN_OPEN_REWARD_MODAL"><?php echo t('add_reward'); ?></button>
                                </div>
                                <div id="REWARDS_STATUS" style="margin-top:0.5rem;"></div>
                                <div id="REWARDS_HISTORY" style="margin-top:1rem;"></div>
                            </div>

                            <div id="tab-punishments" class="tab-panel">
                                <h2 class="form-section-title"><?php echo t('punishments'); ?></h2>
                                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-bottom:1rem;">
                                    <button type="button" class="btn-search" id="BTN_OPEN_PUNISHMENT_MODAL"><?php echo t('add_punishment'); ?></button>
                                </div>
                                <div id="PUNISHMENTS_STATUS" style="margin-top:0.5rem;"></div>
                                <div id="PUNISHMENTS_HISTORY" style="margin-top:1rem;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="submit" class="btn-submit"><?php echo t('save_changes'); ?></button>
                    </div>

                </form>
            </div>

            <div class="modal-backdrop" id="REWARD_MODAL">
                <div class="modal-card">
                    <div class="modal-header">
                        <div class="modal-title"><?php echo t('add_reward'); ?></div>
                        <button type="button" class="modal-close" data-close-modal="REWARD_MODAL">&times;</button>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <div class="form-group" data-label="<?php echo t('reward_type'); ?>">
                            <select id="REWARD_TYPE_ID">
                                <option value="" disabled selected><?php echo t('reward_type'); ?></option>
                                <?php foreach ($reward_types as $rt): ?>
                                    <?php $label = ($LANG === 'ar' && !empty($rt['REWARD_LABEL_AR'])) ? $rt['REWARD_LABEL_AR'] : $rt['REWARD_LABEL_EN']; ?>
                                    <option value="<?php echo (int)$rt['REWARD_TYPE_ID']; ?>" data-duration="<?php echo (int)($rt['REWARD_DURATION'] ?? 0); ?>"><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" data-label="<?php echo t('start_date'); ?>"><input type="date" id="REWARD_START_DATE"></div>
                        <div class="form-group" data-label="<?php echo t('end_date'); ?>"><input type="date" id="REWARD_END_DATE" readonly></div>
                        <div class="form-group" style="grid-column: 1 / -1;" data-label="<?php echo t('note'); ?>"><textarea id="REWARD_NOTE" rows="3" placeholder="<?php echo t('optional_note'); ?>"></textarea></div>
                    </div>
                    <div id="REWARD_MODAL_STATUS" style="margin-top:0.75rem;"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-search btn-secondary" data-close-modal="REWARD_MODAL"><?php echo t('cancel'); ?></button>
                        <button type="button" class="btn-search" id="BTN_ADD_REWARD"><?php echo t('save'); ?></button>
                    </div>
                </div>
            </div>

            <div class="modal-backdrop" id="PUNISHMENT_MODAL">
                <div class="modal-card">
                    <div class="modal-header">
                        <div class="modal-title"><?php echo t('add_punishment'); ?></div>
                        <button type="button" class="modal-close" data-close-modal="PUNISHMENT_MODAL">&times;</button>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <div class="form-group" data-label="<?php echo t('punishment_type'); ?>">
                            <select id="PUNISHMENT_TYPE_ID">
                                <option value="" disabled selected><?php echo t('punishment_type'); ?></option>
                                <?php foreach ($punishment_types as $pt): ?>
                                    <?php $label = ($LANG === 'ar' && !empty($pt['PUNISHMENT_LABEL_AR'])) ? $pt['PUNISHMENT_LABEL_AR'] : $pt['PUNISHMENT_LABEL_EN']; ?>
                                    <option value="<?php echo (int)$pt['PUNISHMENT_TYPE_ID']; ?>" data-duration="<?php echo (int)($pt['PUNISHMENT_DURATION'] ?? 0); ?>"><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" data-label="<?php echo t('start_date'); ?>"><input type="date" id="PUNISHMENT_START_DATE"></div>
                        <div class="form-group" data-label="<?php echo t('end_date'); ?>"><input type="date" id="PUNISHMENT_END_DATE" readonly></div>
                        <div class="form-group" style="grid-column: 1 / -1;" data-label="<?php echo t('note'); ?>"><textarea id="PUNISHMENT_NOTE" rows="3" placeholder="<?php echo t('optional_note'); ?>"></textarea></div>
                    </div>
                    <div id="PUNISHMENT_MODAL_STATUS" style="margin-top:0.75rem;"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-search btn-secondary" data-close-modal="PUNISHMENT_MODAL"><?php echo t('cancel'); ?></button>
                        <button type="button" class="btn-search" id="BTN_ADD_PUNISHMENT"><?php echo t('save'); ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<script>
function toggleBubble(btn, inputName) {
    btn.classList.toggle('active');
    const id = btn.getAttribute('data-id');
    const containerId = inputName.includes('MILITARY') ? 'military-certificates-inputs' : 'hobbies-inputs';
    const container = document.getElementById(containerId);
    
    if (btn.classList.contains('active')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = inputName;
        input.value = id;
        input.setAttribute('data-bubble-id', id);
        container.appendChild(input);
    } else {
        const input = container.querySelector(`input[data-bubble-id="${id}"]`);
        if (input) input.remove();
    }
}

function switchTab(evt, tabId) {
    const tabPanels = document.querySelectorAll('.tab-panel');
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabPanels.forEach(panel => panel.classList.remove('active'));
    tabButtons.forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');

    if (window.innerWidth <= 992) {
        evt.currentTarget.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    if (tabId === 'tab-rewards') {
        loadRewardsHistory();
    }
    if (tabId === 'tab-punishments') {
        loadPunishmentsHistory();
    }
}

var T = <?php echo json_encode($T); ?>;

const t = (key) => T[key] || key;

const CURRENT_STUDENT_SERIAL = <?php echo json_encode($student ? ($student['STUDENT_SERIAL_NUMBER'] ?? '') : ''); ?>;

function formatHistoryTable(rows, columns) {
    if (!Array.isArray(rows) || rows.length < 1) {
        return `<div style="color:var(--text-secondary); font-size:0.95rem;">${t('no_records_found')}</div>`;
    }

    const header = `<tr>${columns.map(c => `<th style="text-align:left; padding:0.5rem; border-bottom:1px solid var(--border-color);">${c.label}</th>`).join('')}</tr>`;
    const body = rows.map(r => {
        return `<tr>${columns.map(c => `<td style="padding:0.5rem; border-bottom:1px solid var(--border-color); vertical-align:top;">${c.render(r)}</td>`).join('')}</tr>`;
    }).join('');

    return `
        <div style="overflow:auto; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <table style="width:100%; border-collapse:collapse; font-size:0.95rem;">
                <thead style="background:var(--bg-secondary);">${header}</thead>
                <tbody>${body}</tbody>
            </table>
        </div>
    `;
}

function showInlineStatus(elId, message, type) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = message || '';
    el.style.color = type === 'error' ? '#b91c1c' : type === 'success' ? '#15803d' : 'var(--text-secondary)';
    el.style.fontSize = '0.95rem';
}

function addDays(dateStr, days) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return '';
    const delta = parseInt(days || 0, 10);
    if (Number.isNaN(delta) || delta <= 0) return dateStr;
    d.setDate(d.getDate() + delta);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

async function fetchHistory(endpoint, action, serial) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('serial_number', serial);
    const res = await fetch(endpoint, { method: 'POST', body: fd });
    return res.json();
}

async function addRecord(endpoint, action, payload) {
    const fd = new FormData();
    Object.keys(payload).forEach(k => fd.append(k, payload[k] == null ? '' : payload[k]));
    fd.append('action', action);
    const res = await fetch(endpoint, { method: 'POST', body: fd });
    return res.json();
}

async function loadRewardsHistory() {
    if (!CURRENT_STUDENT_SERIAL) return;
    try {
        const rewardsData = await fetchHistory('secretary_rewards_student.php', 'get_history', CURRENT_STUDENT_SERIAL);
        if (rewardsData && rewardsData.success) {
            const rows = rewardsData.history || [];
            document.getElementById('REWARDS_HISTORY').innerHTML = formatHistoryTable(rows, [
                { label: t('date'), render: (r) => (r.REWARD_SUGGESTED_AT || '') },
                { label: t('reward_type'), render: (r) => (<?php echo json_encode($LANG); ?> === 'ar' && r.REWARD_LABEL_AR ? r.REWARD_LABEL_AR : (r.REWARD_LABEL_EN || '')) },
                { label: t('start_date'), render: (r) => (r.REWARD_START_DATE || '') },
                { label: t('end_date'), render: (r) => (r.REWARD_END_DATE || '') },
                { label: t('note'), render: (r) => (r.REWARD_NOTE || '') }
            ]);
        }
    } catch (e) {
        showInlineStatus('REWARDS_STATUS', t('msg_error_loading'), 'error');
    }
}

async function loadPunishmentsHistory() {
    if (!CURRENT_STUDENT_SERIAL) return;
    try {
        const punData = await fetchHistory('secretary_punishes_student.php', 'get_history', CURRENT_STUDENT_SERIAL);
        if (punData && punData.success) {
            const rows = punData.history || [];
            document.getElementById('PUNISHMENTS_HISTORY').innerHTML = formatHistoryTable(rows, [
                { label: t('date'), render: (r) => (r.PUNISHMENT_SUGGESTED_AT || '') },
                { label: t('punishment_type'), render: (r) => (<?php echo json_encode($LANG); ?> === 'ar' && r.PUNISHMENT_LABEL_AR ? r.PUNISHMENT_LABEL_AR : (r.PUNISHMENT_LABEL_EN || '')) },
                { label: t('start_date'), render: (r) => (r.PUNISHMENT_START_DATE || '') },
                { label: t('end_date'), render: (r) => (r.PUNISHMENT_END_DATE || '') },
                { label: t('note'), render: (r) => (r.PUNISHMENT_NOTE || '') }
            ]);
        }
    } catch (e) {
        showInlineStatus('PUNISHMENTS_STATUS', t('msg_error_loading'), 'error');
    }
}

function wireDisciplineForm() {
    const rewardType = document.getElementById('REWARD_TYPE_ID');
    const rewardStart = document.getElementById('REWARD_START_DATE');
    const rewardEnd = document.getElementById('REWARD_END_DATE');
    const rewardBtn = document.getElementById('BTN_ADD_REWARD');

    const openRewardModalBtn = document.getElementById('BTN_OPEN_REWARD_MODAL');
    const rewardModal = document.getElementById('REWARD_MODAL');

    if (rewardType && rewardStart && rewardEnd) {
        const syncRewardEnd = () => {
            const opt = rewardType.options[rewardType.selectedIndex];
            const dur = opt ? opt.getAttribute('data-duration') : 0;
            rewardEnd.value = addDays(rewardStart.value, dur);
        };
        rewardType.addEventListener('change', syncRewardEnd);
        rewardStart.addEventListener('change', syncRewardEnd);
    }

    if (openRewardModalBtn && rewardModal) {
        openRewardModalBtn.addEventListener('click', () => {
            showInlineStatus('REWARD_MODAL_STATUS', '', '');
            rewardModal.classList.add('active');
        });
    }

    if (rewardBtn) {
        rewardBtn.addEventListener('click', async () => {
            if (!CURRENT_STUDENT_SERIAL) return;
            showInlineStatus('REWARDS_STATUS', t('saving'), '');
            const typeId = rewardType ? rewardType.value : '';
            const startDate = rewardStart ? rewardStart.value : '';
            const endDate = rewardEnd ? rewardEnd.value : '';
            const note = (document.getElementById('REWARD_NOTE') || {}).value || '';
            if (!typeId || !startDate || !endDate) {
                showInlineStatus('REWARDS_STATUS', t('error_missing_fields'), 'error');
                return;
            }
            try {
                const data = await addRecord('secretary_rewards_student.php', 'add_reward', {
                    serial_number: CURRENT_STUDENT_SERIAL,
                    reward_type_id: typeId,
                    start_date: startDate,
                    end_date: endDate,
                    note: note
                });
                if (data && data.success) {
                    showInlineStatus('REWARDS_STATUS', data.message || t('saved'), 'success');
                    showInlineStatus('REWARD_MODAL_STATUS', data.message || t('saved'), 'success');
                    if (rewardModal) rewardModal.classList.remove('active');
                    loadRewardsHistory();
                } else {
                    showInlineStatus('REWARD_MODAL_STATUS', (data && data.message) ? data.message : t('error_saving_reward'), 'error');
                }
            } catch (e) {
                showInlineStatus('REWARD_MODAL_STATUS', t('error_saving_reward'), 'error');
            }
        });
    }

    const punType = document.getElementById('PUNISHMENT_TYPE_ID');
    const punStart = document.getElementById('PUNISHMENT_START_DATE');
    const punEnd = document.getElementById('PUNISHMENT_END_DATE');
    const punBtn = document.getElementById('BTN_ADD_PUNISHMENT');

    const openPunModalBtn = document.getElementById('BTN_OPEN_PUNISHMENT_MODAL');
    const punModal = document.getElementById('PUNISHMENT_MODAL');

    if (punType && punStart && punEnd) {
        const syncPunEnd = () => {
            const opt = punType.options[punType.selectedIndex];
            const dur = opt ? opt.getAttribute('data-duration') : 0;
            punEnd.value = addDays(punStart.value, dur);
        };
        punType.addEventListener('change', syncPunEnd);
        punStart.addEventListener('change', syncPunEnd);
    }

    if (openPunModalBtn && punModal) {
        openPunModalBtn.addEventListener('click', () => {
            showInlineStatus('PUNISHMENT_MODAL_STATUS', '', '');
            punModal.classList.add('active');
        });
    }

    if (punBtn) {
        punBtn.addEventListener('click', async () => {
            if (!CURRENT_STUDENT_SERIAL) return;
            showInlineStatus('PUNISHMENTS_STATUS', t('saving'), '');
            const typeId = punType ? punType.value : '';
            const startDate = punStart ? punStart.value : '';
            const endDate = punEnd ? punEnd.value : '';
            const note = (document.getElementById('PUNISHMENT_NOTE') || {}).value || '';
            if (!typeId || !startDate || !endDate) {
                showInlineStatus('PUNISHMENTS_STATUS', t('error_missing_fields'), 'error');
                return;
            }
            try {
                const data = await addRecord('secretary_punishes_student.php', 'add_punishment', {
                    serial_number: CURRENT_STUDENT_SERIAL,
                    punishment_type_id: typeId,
                    start_date: startDate,
                    end_date: endDate,
                    note: note
                });
                if (data && data.success) {
                    showInlineStatus('PUNISHMENTS_STATUS', data.message || t('saved'), 'success');
                    showInlineStatus('PUNISHMENT_MODAL_STATUS', data.message || t('saved'), 'success');
                    if (punModal) punModal.classList.remove('active');
                    loadPunishmentsHistory();
                } else {
                    showInlineStatus('PUNISHMENT_MODAL_STATUS', (data && data.message) ? data.message : t('error_saving_punishment'), 'error');
                }
            } catch (e) {
                showInlineStatus('PUNISHMENT_MODAL_STATUS', t('error_saving_punishment'), 'error');
            }
        });
    }
}

wireDisciplineForm();

document.addEventListener('click', function(e) {
    const closeTarget = e.target && e.target.getAttribute ? e.target.getAttribute('data-close-modal') : null;
    if (closeTarget) {
        const modal = document.getElementById(closeTarget);
        if (modal) modal.classList.remove('active');
    }

    const isBackdrop = e.target && e.target.classList && e.target.classList.contains('modal-backdrop');
    if (isBackdrop) {
        e.target.classList.remove('active');
    }
});

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
    const sexSelect = document.getElementById('SEX_SELECT');
    if (!sexSelect) return;
    const sex = sexSelect.value;
    const skirtFields = document.querySelectorAll('.skirt-field');
    skirtFields.forEach(field => {
        field.style.display = (sex === 'Female') ? 'block' : 'none';
    });
}

function toggleForeignFields() {
    const isForeignSelect = document.getElementById('IS_FOREIGN_SELECT');
    if (!isForeignSelect) return;
    const isForeign = isForeignSelect.value;
    const localFields = document.getElementById('LOCAL_CONTACT_FIELDS');
    const foreignFields = document.getElementById('FOREIGN_CONTACT_FIELDS');

    if (isForeign === 'Yes') {
        if (localFields) localFields.style.display = 'none';
        if (foreignFields) {
            foreignFields.style.display = 'grid';
            foreignFields.style.gridTemplateColumns = '1fr 1fr';
            foreignFields.style.gap = '1rem';
        }
    } else {
        if (localFields) localFields.style.display = 'grid';
        if (foreignFields) foreignFields.style.display = 'none';
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

function initModernForm() {
    document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(el => {
        const checkValue = () => {
            if (el.value && el.value !== "" && el.value !== "undefined") {
                el.closest('.form-group').classList.add('has-value');
            } else {
                el.closest('.form-group').classList.remove('has-value');
            }
        };
        el.addEventListener('input', checkValue);
        el.addEventListener('change', checkValue);
        checkValue();
    });
}

toggleSkirtFields();
if (document.getElementById('IS_FOREIGN_SELECT')) {
    toggleForeignFields();
}
fetchAllStudents();
initModernForm();
</script>

</body>
</html>
