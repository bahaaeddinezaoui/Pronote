<?php
// Debug script to check all Arabic columns
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
$conn->set_charset("utf8mb4");

$serial_number = $_GET['serial_number'] ?? '120240058131';

$studentQuery = "
    SELECT 
        s.STUDENT_SERIAL_NUMBER,
        s.STUDENT_FIRST_NAME_EN, s.STUDENT_LAST_NAME_EN,
        s.STUDENT_FIRST_NAME_AR, s.STUDENT_LAST_NAME_AR,
        
        g.GRADE_NAME_EN, g.GRADE_NAME_AR,
        sec.SECTION_NAME_EN, sec.SECTION_NAME_AR,
        cat.CATEGORY_NAME_EN, cat.CATEGORY_NAME_AR,
        a.ARMY_NAME_EN, a.ARMY_NAME_AR,

        sp.speciality_name_en AS SPECIALITY_NAME_EN,
        sp.speciality_name_ar AS SPECIALITY_NAME_AR,

        al.ACADEMIC_LEVEL_EN, al.ACADEMIC_LEVEL_AR,

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
        rel.relation_name_ar AS CONTACT_RELATION_AR

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
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "<pre>";
echo "=== STUDENT INFO ===\n";
echo "First Name AR: " . ($row['STUDENT_FIRST_NAME_AR'] ?? 'NULL') . "\n";
echo "Last Name AR: " . ($row['STUDENT_LAST_NAME_AR'] ?? 'NULL') . "\n";
echo "\n=== ACADEMIC INFO ===\n";
echo "Grade AR: " . ($row['GRADE_NAME_AR'] ?? 'NULL') . "\n";
echo "Section AR: " . ($row['SECTION_NAME_AR'] ?? 'NULL') . "\n";
echo "Category AR: " . ($row['CATEGORY_NAME_AR'] ?? 'NULL') . "\n";
echo "Army AR: " . ($row['ARMY_NAME_AR'] ?? 'NULL') . "\n";
echo "Speciality AR: " . ($row['SPECIALITY_NAME_AR'] ?? 'NULL') . "\n";
echo "Academic Level AR: " . ($row['ACADEMIC_LEVEL_AR'] ?? 'NULL') . "\n";
echo "\n=== PARENT INFO ===\n";
echo "Father Name AR: " . trim(($row['FATHER_FIRST_NAME_AR'] ?? '') . ' ' . ($row['FATHER_LAST_NAME_AR'] ?? '')) . "\n";
echo "Mother Name AR: " . trim(($row['MOTHER_FIRST_NAME_AR'] ?? '') . ' ' . ($row['MOTHER_LAST_NAME_AR'] ?? '')) . "\n";
echo "Father Profession AR: " . ($row['FATHER_PROFESSION_AR'] ?? 'NULL') . "\n";
echo "Mother Profession AR: " . ($row['MOTHER_PROFESSION_AR'] ?? 'NULL') . "\n";
echo "\n=== ADDRESS INFO ===\n";
echo "BP Street AR: " . ($row['BP_STREET_AR'] ?? 'NULL') . "\n";
echo "BP Wilaya AR: " . ($row['BP_WILAYA_AR'] ?? 'NULL') . "\n";
echo "BP Country AR: " . ($row['BP_COUNTRY_AR'] ?? 'NULL') . "\n";
echo "PERS Street AR: " . ($row['PERS_STREET_AR'] ?? 'NULL') . "\n";
echo "PERS Wilaya AR: " . ($row['PERS_WILAYA_AR'] ?? 'NULL') . "\n";
echo "PERS Country AR: " . ($row['PERS_COUNTRY_AR'] ?? 'NULL') . "\n";
echo "\n=== EMERGENCY CONTACT ===\n";
echo "Contact Name AR: " . trim(($row['CONTACT_FIRST_NAME_AR'] ?? '') . ' ' . ($row['CONTACT_LAST_NAME_AR'] ?? '')) . "\n";
echo "Contact Relation AR: " . ($row['CONTACT_RELATION_AR'] ?? 'NULL') . "\n";
echo "\n=== HEALTH ===\n";
echo "Health Status AR: " . ($row['HEALTH_STATUS_AR'] ?? 'NULL') . "\n";
echo "</pre>";

$conn->close();
