<?php
session_start();
date_default_timezone_set('Africa/Algiers');

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
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Fetch Secretary Name
$user_id = $_SESSION['user_id'];
$secretary_name = "Secretary";
$stmt = $conn->prepare("SELECT SECRETARY_FIRST_NAME_EN, SECRETARY_LAST_NAME_EN FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $secretary_name = trim($row['SECRETARY_FIRST_NAME_EN'] . ' ' . $row['SECRETARY_LAST_NAME_EN']);
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
            STUDENT_ARMY_ID, STUDENT_ORPHAN_STATUS, STUDENT_PARENTS_SITUATION, 
            STUDENT_BIRTH_PLACE_ID, STUDENT_PERSONAL_ADDRESS_ID, STUDENT_RECRUITMENT_SOURCE_ID
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
                $message = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                $msg_type = "error";
        } else {
            // Build type string explicitly: 39 params
            // 1:serial(s) 2:cat(i) 3:sec(i) 4-7:names(ssss) 8:grade(i) 9-12:sex/date/blood/phone(ssss)
            // 13-14:height/weight(dd) 15:foreign(s) 16:average(d) 17-30:14 strings(ssssssssssssss)
            // 31-33:sibling counts(iii) 34:army(i) 35-36:orphan/parents(ss) 37-39:addr/recruit ids(iii)
            $types = "sii" . "ssss" . "i" . "ssss" . "dd" . "s" . "d" . "ssssssssssssss" . "iii" . "i" . "ss" . "iii";
            
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
                $army_id, $orphan, $parents, 
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
                
                // --- F. Insert Parade Uniform ---
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

                    $contact_fname = $_POST['CONTACT_FIRST_NAME'] ?? '';
                    $contact_lname = $_POST['CONTACT_LAST_NAME'] ?? '';
                    $contact_relation = $_POST['CONTACT_RELATION'] ?? '';
                    $contact_phone = $_POST['CONTACT_PHONE_NUMBER'] ?? '';
                    $consulate_num = null; // No consulate number for locals

                } else {
                    // 2. Foreign: No address, Relation is "X's consulate"
                    $contact_address_id = null;
                    $contact_fname = null;
                    $contact_lname = null;
                    $contact_phone = $_POST['CONTACT_PHONE_NUMBER'] ?? '';
                    $consulate_num = $_POST['CONSULATE_NUMBER'] ?? '';
                    
                    // Fetch country name from BP_COUNTRY_ID (inserted earlier as $birth_place_id's country, but easier to get from POST)
                    $bp_country_id = !empty($_POST['BP_COUNTRY_ID']) ? intval($_POST['BP_COUNTRY_ID']) : 0;
                    $country_name = "Unknown";
                    if ($bp_country_id) {
                        $res_c = $conn->query("SELECT COUNTRY_NAME_EN FROM country WHERE COUNTRY_ID = $bp_country_id");
                        if ($res_c && $row_c = $res_c->fetch_assoc()) {
                            $country_name = $row_c['COUNTRY_NAME_EN'];
                        }
                    }
                    $contact_relation = $country_name . "'s consulate";
                }

                $sql_emg = "INSERT INTO student_emergency_contact (STUDENT_SERIAL_NUMBER, CONTACT_FIRST_NAME, CONTACT_LAST_NAME, CONTACT_RELATION, CONTACT_PHONE_NUMBER, CONTACT_ADDRESS_ID, CONSULATE_NUMBER) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_emg = $conn->prepare($sql_emg);
                if ($stmt_emg) {
                    $stmt_emg->bind_param("sssssis", $serial, $contact_fname, $contact_lname, $contact_relation, $contact_phone, $contact_address_id, $consulate_num);
                    $stmt_emg->execute();
                    $stmt_emg->close();
                }


                
                $message = "Student record created successfully!";
                $msg_type = "success";
            } else {
                if ($conn->errno == 1062) {
                        $message = "Error: Serial Number '$serial' already exists.";
                } else {
                        $message = "Error inserting record: " . $stmt->error;
                }
                $msg_type = "error";
            }
            $stmt->close();
        }
    }
}

// 5. Fetch Dropdowns
$categories = [];
$res = $conn->query("SELECT CATEGORY_ID, CATEGORY_NAME FROM category ORDER BY CATEGORY_NAME");
while($r = $res->fetch_assoc()) $categories[] = $r;

$sections = [];
$res = $conn->query("SELECT SECTION_ID, SECTION_NAME, CATEGORY_ID FROM section ORDER BY SECTION_NAME");
while($r = $res->fetch_assoc()) $sections[] = $r;

// NEW: Fetch Grades
$grades = [];
$res = $conn->query("SELECT GRADE_ID, GRADE_NAME FROM grade ORDER BY GRADE_NAME");
while($r = $res->fetch_assoc()) $grades[] = $r;

// Countries (For Address)
$countries = [];
$res = $conn->query("SELECT COUNTRY_ID, COUNTRY_NAME_EN FROM country ORDER BY COUNTRY_NAME_EN");
while($r = $res->fetch_assoc()) $countries[] = $r;

// Recruitment Sources (Fetched from DB)
$recruitmentSources = [];
$res = $conn->query("SELECT RECRUITMENT_SOURCE_ID, RECRUITMENT_TYPE, ECN_SCHOOL_NAME, ECN_SCHOOL_WILAYA_ID FROM recruitment_source ORDER BY RECRUITMENT_TYPE, ECN_SCHOOL_NAME");
if ($res) {
    while($r = $res->fetch_assoc()) $recruitmentSources[] = $r;
}

// Armies (Fetched from DB)
$armies = [];
$res = $conn->query("SELECT ARMY_ID, ARMY_NAME FROM army ORDER BY ARMY_NAME");
if ($res) {
    while($r = $res->fetch_assoc()) $armies[] = $r;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title>Secretary Dashboard</title>
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
    </style>
</head>
<body>

<div class="div1" id="navbar">
    <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
        <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="secretary_home.php" class="navbar_buttons active">Insert Student</a>
            <a href="profile.php" class="navbar_buttons">Profile</a>
        </div>
        <a href="logout.php" class="navbar_buttons logout-btn" style="margin-left:auto;">Logout</a>
    </div>
</div>

<div class="home-container">
    <div style="margin-bottom: 2rem;">
        <h1>Welcome, <?php echo htmlspecialchars($secretary_name); ?></h1>
        <p style="color: var(--text-secondary);">Register a new student by filling out the form below.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="secretary_home.php">
            <input type="hidden" name="action" value="add_student">
            
            <div class="form-grid">
                
                <!-- Identification -->
                <div class="form-section-title">Identification & Classification</div>
                
                <div class="form-group">
                    <label>Serial Number (Required) *</label>
                    <input type="text" name="STUDENT_SERIAL_NUMBER" required placeholder="e.g. 20260001">
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select id="CATEGORY_ID" name="CATEGORY_ID" required>
                        <option value="">Select...</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['CATEGORY_ID']; ?>"><?php echo htmlspecialchars($c['CATEGORY_NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section *</label>
                    <select id="SECTION_ID" name="SECTION_ID" required>
                        <option value="">Select...</option>
                        <?php foreach ($sections as $s): ?>
                            <option value="<?php echo $s['SECTION_ID']; ?>" data-category="<?php echo $s['CATEGORY_ID']; ?>">
                                <?php echo htmlspecialchars($s['SECTION_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Personal Info -->
                <div class="form-section-title">Personal Information</div>

                <div class="form-group"><label>First Name (EN) *</label><input type="text" name="STUDENT_FIRST_NAME_EN" required></div>
                <div class="form-group"><label>Last Name (EN) *</label><input type="text" name="STUDENT_LAST_NAME_EN" required></div>
                <div class="form-group"><label>First Name (AR)</label><input type="text" name="STUDENT_FIRST_NAME_AR" dir="rtl"></div>
                <div class="form-group"><label>Last Name (AR)</label><input type="text" name="STUDENT_LAST_NAME_AR" dir="rtl"></div>
                
                <div class="form-group"><label>Sex</label>
                    <select id="SEX_SELECT" name="STUDENT_SEX" onchange="toggleSkirtFields()">
                        <option value="">Select...</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group"><label>Birth Date</label><input type="date" name="STUDENT_BIRTH_DATE"></div>
                
                <!-- BIRTH PLACE ADDRESS -->
                 <div class="sub-group">
                    <label style="color:var(--primary-color); font-weight:700;">Birth Place Address</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                        <div>
                             <label style="font-size:0.8rem;">Street (EN)</label>
                             <input type="text" name="BP_STREET_EN" placeholder="Street Name">
                        </div>
                        <div>
                             <label style="font-size:0.8rem;">Street (AR)</label>
                             <input type="text" name="BP_STREET_AR" dir="rtl" placeholder="اسم الشارع">
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Country</label>
                            <select class="country-select" data-prefix="BP_" name="BP_COUNTRY_ID">
                                <option value="">Select Country...</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($c['COUNTRY_NAME_EN']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Wilaya</label>
                            <select id="BP_WILAYA_ID" name="BP_WILAYA_ID" class="wilaya-select" data-prefix="BP_" disabled>
                                <option value="">Select Country First</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Daira</label>
                            <select id="BP_DAIRA_ID" name="BP_DAIRA_ID" class="daira-select" data-prefix="BP_" disabled>
                                <option value="">Select Wilaya First</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Commune</label>
                            <select id="BP_COMMUNE_ID" name="BP_COMMUNE_ID" disabled>
                                <option value="">Select Daira First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- PERSONAL ADDRESS -->
                 <div class="sub-group">
                    <label style="color:var(--primary-color); font-weight:700;">Personal Address</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                        <div>
                             <label style="font-size:0.8rem;">Street (EN)</label>
                             <input type="text" name="PERS_STREET_EN" placeholder="Street Name">
                        </div>
                        <div>
                             <label style="font-size:0.8rem;">Street (AR)</label>
                             <input type="text" name="PERS_STREET_AR" dir="rtl" placeholder="اسم الشارع">
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Country</label>
                            <select class="country-select" data-prefix="PERS_" name="PERS_COUNTRY_ID">
                                <option value="">Select Country...</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($c['COUNTRY_NAME_EN']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Wilaya</label>
                            <select id="PERS_WILAYA_ID" name="PERS_WILAYA_ID" class="wilaya-select" data-prefix="PERS_" disabled>
                                <option value="">Select Country First</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Daira</label>
                            <select id="PERS_DAIRA_ID" name="PERS_DAIRA_ID" class="daira-select" data-prefix="PERS_" disabled>
                                <option value="">Select Wilaya First</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;">Commune</label>
                            <select id="PERS_COMMUNE_ID" name="PERS_COMMUNE_ID" disabled>
                                <option value="">Select Daira First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group"><label>Personal Phone</label><input type="text" name="STUDENT_PERSONAL_PHONE"></div>
                <div class="form-group"><label>Blood Type</label>
                    <select name="STUDENT_BLOOD_TYPE">
                        <option value="">Select...</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                    </select>
                </div>

                <!-- Academic Info -->
                <div class="form-section-title">Academic Details</div>

                <div class="form-group"><label>Grade / Rank</label>
                    <select name="STUDENT_GRADE_ID">
                        <option value="">Select Grade...</option>
                        <?php foreach($grades as $g): ?>
                            <option value="<?php echo $g['GRADE_ID']; ?>"><?php echo htmlspecialchars($g['GRADE_NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Academic Average</label><input type="number" step="0.01" name="STUDENT_ACADEMIC_AVERAGE"></div>
                <div class="form-group"><label>Speciality</label><input type="text" name="STUDENT_SPECIALITY"></div>
                <div class="form-group"><label>Academic Level</label><input type="text" name="STUDENT_ACADEMIC_LEVEL"></div>
                <div class="form-group"><label>Bac Number</label><input type="text" name="STUDENT_BACCALAUREATE_SUB_NUMBER"></div>
                
                <!-- Recruitment Source -->
                 <div class="sub-group">
                    <label style="color:var(--primary-color); font-weight:700;">Recruitment Source</label>
                    <div style="margin-top:0.5rem;">
                        <div class="form-group">
                            <label style="font-size:0.8rem;">Select Recruitment Source</label>
                            <select name="RECRUITMENT_SOURCE_ID">
                                <option value="">Select...</option>
                                <?php foreach ($recruitmentSources as $rs): ?>
                                    <?php 
                                        $label = $rs['RECRUITMENT_TYPE'];
                                        if ($rs['ECN_SCHOOL_NAME']) $label .= ' - ' . $rs['ECN_SCHOOL_NAME'];
                                    ?>
                                    <option value="<?php echo $rs['RECRUITMENT_SOURCE_ID']; ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Other Fields (Condensed for brevity but still saving all) -->
                <div class="form-section-title">Other Details</div>
                <div class="form-group"><label>Height (cm)</label><input type="number" step="0.01" name="STUDENT_HEIGHT_CM"></div>
                <div class="form-group"><label>Weight (kg)</label><input type="number" step="0.01" name="STUDENT_WEIGHT_KG"></div>
                <div class="form-group"><label>Is Foreign?</label>
                   <select id="IS_FOREIGN_SELECT" name="STUDENT_IS_FOREIGN" onchange="toggleForeignFields()">
                       <option value="No">No</option>
                       <option value="Yes">Yes</option>
                   </select>
                </div>
                <div class="form-group"><label>School Sub Date</label><input type="date" name="STUDENT_SCHOOL_SUB_DATE"></div>

                <div class="form-group"><label>Parents Situation</label>
                    <select name="STUDENT_PARENTS_SITUATION"><option value="Married">Married</option><option value="Divorced">Divorced</option><option value="Separated">Separated</option><option value="Widowed">Widowed</option></select>
                </div>
                <div class="form-group"><label>Orphan Status</label>
                    <select name="STUDENT_ORPHAN_STATUS"><option value="None">None</option><option value="Father">Father</option><option value="Mother">Mother</option><option value="Both">Both</option></select>
                </div>
                <div class="form-group"><label>Num Siblings</label><input type="number" name="STUDENT_NUMBER_OF_SIBLINGS"></div>
                
                <!-- ... Remaining text fields implied by saving logic ... -->
                <!-- For brevity in this long file update, I'll ensure key structure is correct. -->
                <!-- Documents & Admin Extras -->
                 <div class="form-group"><label>School Card Number</label><input type="text" name="STUDENT_SCHOOL_SUB_CARD_NUMBER"></div>
                 <div class="form-group"><label>Laptop Serial</label><input type="text" name="STUDENT_LAPTOP_SERIAL_NUMBER"></div>
                 <div class="form-group"><label>ID Card Number</label><input type="text" name="STUDENT_ID_CARD_NUMBER"></div>
                 <div class="form-group"><label>Birth Cert Number</label><input type="text" name="STUDENT_BIRTHDATE_CERTIFICATE_NUMBER"></div>
                 <div class="form-group"><label>Postal Account</label><input type="text" name="STUDENT_POSTAL_ACCOUNT_NUMBER"></div>
                 
                 <div class="form-group" style="grid-column: span 2;"><label>Educational Certificates</label><textarea name="STUDENT_EDUCATIONAL_CERTIFICATES" rows="2"></textarea></div>
                 <div class="form-group" style="grid-column: span 2;"><label>Military Certificates</label><textarea name="STUDENT_MILITARY_CERTIFICATES" rows="2"></textarea></div>
                 <div class="form-group" style="grid-column: span 2;"><label>Hobbies</label><textarea name="STUDENT_HOBBIES" rows="2"></textarea></div>
                 
                 <!-- Health & Military Status -->
                 <div class="form-group" style="grid-column: span 2;"><label>Health Status</label><textarea name="STUDENT_HEALTH_STATUS" rows="2"></textarea></div>
                 <div class="form-group"><label>Military Necklace?</label>
                    <select name="STUDENT_MILITARY_NECKLACE"><option value="No">No</option><option value="Yes">Yes</option></select>
                 </div>
                 <div class="form-group"><label>Army</label>
                    <select name="STUDENT_ARMY_ID">
                        <option value="">Select Army...</option>
                        <?php foreach ($armies as $a): ?>
                            <option value="<?php echo $a['ARMY_ID']; ?>"><?php echo htmlspecialchars($a['ARMY_NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                 </div>

                 <!-- Siblings Details -->
                 <div class="form-group"><label>Num Sisters</label><input type="number" name="STUDENT_NUMBER_OF_SISTERS"></div>
                 <div class="form-group"><label>Order among Siblings</label><input type="number" name="STUDENT_ORDER_AMONG_SIBLINGS"></div>

                 <!-- Combat Outfit -->
                 <div class="form-section-title">Combat Outfit</div>
                 <div class="form-group"><label>1st Outfit Number</label><input type="text" name="FIRST_OUTFIT_NUMBER"></div>
                 <div class="form-group"><label>1st Outfit Size</label><input type="text" name="FIRST_OUTFIT_SIZE" placeholder="e.g. M, L, XL"></div>
                 <div class="form-group"><label>2nd Outfit Number</label><input type="text" name="SECOND_OUTFIT_NUMBER"></div>
                 <div class="form-group"><label>2nd Outfit Size</label><input type="text" name="SECOND_OUTFIT_SIZE" placeholder="e.g. M, L, XL"></div>
                 <div class="form-group"><label>Combat Shoe Size</label><input type="text" name="COMBAT_SHOE_SIZE" placeholder="e.g. 42, 43"></div>

                 <!-- Parade Uniform -->
                 <div class="form-section-title">Parade Uniform</div>
                 <div class="form-group"><label>Summer Jacket Size</label><input type="text" name="SUMMER_JACKET_SIZE"></div>
                 <div class="form-group"><label>Winter Jacket Size</label><input type="text" name="WINTER_JACKET_SIZE"></div>
                 <div class="form-group"><label>Summer Trousers Size</label><input type="text" name="SUMMER_TROUSERS_SIZE"></div>
                 <div class="form-group"><label>Winter Trousers Size</label><input type="text" name="WINTER_TROUSERS_SIZE"></div>
                 <div class="form-group"><label>Summer Shirt Size</label><input type="text" name="SUMMER_SHIRT_SIZE"></div>
                 <div class="form-group"><label>Winter Shirt Size</label><input type="text" name="WINTER_SHIRT_SIZE"></div>
                 <div class="form-group"><label>Summer Hat Size</label><input type="text" name="SUMMER_HAT_SIZE"></div>
                 <div class="form-group"><label>Winter Hat Size</label><input type="text" name="WINTER_HAT_SIZE"></div>
                 <!-- Skirt sizes (Female only) -->
                 <div class="form-group skirt-field" style="display:none;"><label>Summer Skirt Size</label><input type="text" name="SUMMER_SKIRT_SIZE"></div>
                 <div class="form-group skirt-field" style="display:none;"><label>Winter Skirt Size</label><input type="text" name="WINTER_SKIRT_SIZE"></div>

                 <!-- Parent Info -->
                 <div class="form-section-title">Parent Information</div>
                 <div class="form-group"><label>Father First Name (EN)</label><input type="text" name="FATHER_FIRST_NAME_EN"></div>
                 <div class="form-group"><label>Father Last Name (EN)</label><input type="text" name="FATHER_LAST_NAME_EN"></div>
                 <div class="form-group"><label>Father First Name (AR)</label><input type="text" name="FATHER_FIRST_NAME_AR" dir="rtl"></div>
                 <div class="form-group"><label>Father Last Name (AR)</label><input type="text" name="FATHER_LAST_NAME_AR" dir="rtl"></div>
                 <div class="form-group"><label>Father Profession (EN)</label><input type="text" name="FATHER_PROFESSION_EN"></div>
                 <div class="form-group"><label>Father Profession (AR)</label><input type="text" name="FATHER_PROFESSION_AR" dir="rtl"></div>
                 <div class="form-group"><label>Mother First Name (EN)</label><input type="text" name="MOTHER_FIRST_NAME_EN"></div>
                 <div class="form-group"><label>Mother Last Name (EN)</label><input type="text" name="MOTHER_LAST_NAME_EN"></div>
                 <div class="form-group"><label>Mother First Name (AR)</label><input type="text" name="MOTHER_FIRST_NAME_AR" dir="rtl"></div>
                 <div class="form-group"><label>Mother Last Name (AR)</label><input type="text" name="MOTHER_LAST_NAME_AR" dir="rtl"></div>
                 <div class="form-group"><label>Mother Profession (EN)</label><input type="text" name="MOTHER_PROFESSION_EN"></div>
                 <div class="form-group"><label>Mother Profession (AR)</label><input type="text" name="MOTHER_PROFESSION_AR" dir="rtl"></div>

                 <!-- Emergency Contact -->
                 <!-- Emergency Contact -->
                 <div class="emergency-section">
                     <div class="form-section-title">Emergency Contact</div>
                     <div class="form-grid" style="gap: 1.5rem;">
                         <div class="form-group"><label>Contact Phone Number</label><input type="text" name="CONTACT_PHONE_NUMBER"></div>
                         
                         <!-- Local Contact Fields (Hidden if Foreign) -->
                         <div id="LOCAL_CONTACT_FIELDS" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                             <div class="form-group"><label>First Name</label><input type="text" name="CONTACT_FIRST_NAME"></div>
                             <div class="form-group"><label>Last Name</label><input type="text" name="CONTACT_LAST_NAME"></div>
                             <div class="form-group"><label>Relation</label><input type="text" name="CONTACT_RELATION" placeholder="e.g. Father, Uncle"></div>
                             
                             <!-- Contact Address -->
                             <div class="sub-group">
                                <label style="color:var(--primary-color); font-weight:700;">Contact Address</label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top:0.5rem;">
                                    <div class="form-group"><label style="font-size:0.8rem;">Street (EN)</label><input type="text" name="CONTACT_STREET_EN"></div>
                                    <div class="form-group"><label style="font-size:0.8rem;">Street (AR)</label><input type="text" name="CONTACT_STREET_AR" dir="rtl"></div>
                                    
                                    <div>
                                        <label style="font-size:0.8rem;">Country</label>
                                        <select class="country-select" data-prefix="CONTACT_" name="CONTACT_COUNTRY_ID">
                                            <option value="">Select Country...</option>
                                            <?php foreach ($countries as $c): ?>
                                                <option value="<?php echo $c['COUNTRY_ID']; ?>"><?php echo htmlspecialchars($c['COUNTRY_NAME_EN']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:0.8rem;">Wilaya</label>
                                        <select id="CONTACT_WILAYA_ID" name="CONTACT_WILAYA_ID" class="wilaya-select" data-prefix="CONTACT_" disabled>
                                            <option value="">Select Country First</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:0.8rem;">Daira</label>
                                        <select id="CONTACT_DAIRA_ID" name="CONTACT_DAIRA_ID" class="daira-select" data-prefix="CONTACT_" disabled>
                                            <option value="">Select Wilaya First</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:0.8rem;">Commune</label>
                                        <select id="CONTACT_COMMUNE_ID" name="CONTACT_COMMUNE_ID" disabled>
                                            <option value="">Select Daira First</option>
                                        </select>
                                    </div>
                                </div>
                             </div>
                         </div>
    
                         <!-- Foreign Contact Fields (Hidden if Not Foreign) -->
                         <div id="FOREIGN_CONTACT_FIELDS" style="grid-column: 1 / -1; display:none; grid-template-columns: 1fr 1fr; gap: 1rem;">
                             <div class="form-group"><label>Consulate Number</label><input type="text" name="CONSULATE_NUMBER"></div>
                             <div class="form-group" style="grid-column: span 2;">
                                <p style="font-size:0.9rem; color:#666;">* Relation will be automatically set to "<b>[Birth Country]'s consulate</b>"</p>
                             </div>
                         </div>
                     </div>
                 </div>

            </div>

            <button type="submit" class="btn-submit">Add Student Record</button>
        </form>
    </div>
</div>

<script>
// Toggle ECN Section
function toggleECN() {
    const type = document.getElementById('RECRUITMENT_TYPE').value;
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




// Cascading Locations (Generic)
function fetchLocations(type, parentParam, parentId, targetSelect, placeholder) {
    targetSelect.innerHTML = '<option value="">Loading...</option>';
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
            targetSelect.innerHTML = '<option value="">Error or None</option>';
        });
}

// Bind Events for Country/Wilaya/Daira/Commune classes
document.querySelectorAll('.country-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const wilayaSel = document.getElementById(prefix + 'WILAYA_ID');
        if(this.value) fetchLocations('wilayas', 'country_id', this.value, wilayaSel, 'Select Wilaya...');
    });
});

document.querySelectorAll('.wilaya-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const dairaSel = document.getElementById(prefix + 'DAIRA_ID');
        if(this.value) fetchLocations('dairas', 'wilaya_id', this.value, dairaSel, 'Select Daira...');
    });
});

document.querySelectorAll('.daira-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const prefix = this.getAttribute('data-prefix');
        const communeSel = document.getElementById(prefix + 'COMMUNE_ID');
        if(this.value) fetchLocations('communes', 'daira_id', this.value, communeSel, 'Select Commune...');
    });
});
</script>

</body>
</html>
