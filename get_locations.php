<?php
// get_locations.php
session_start();
require_once __DIR__ . '/lang/i18n.php';

header('Content-Type: application/json');

$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

$type = $_GET['type'] ?? '';
$data = [];

switch ($type) {
    case 'countries':
        $res = $conn->query("SELECT COUNTRY_ID, COUNTRY_NAME_EN, COUNTRY_NAME_AR FROM country ORDER BY COUNTRY_NAME_EN");
        while ($row = $res->fetch_assoc()) {
            $name = ($LANG === 'ar' && !empty($row['COUNTRY_NAME_AR'])) ? $row['COUNTRY_NAME_AR'] : $row['COUNTRY_NAME_EN'];
            $data[] = ['COUNTRY_ID' => $row['COUNTRY_ID'], 'COUNTRY_NAME_EN' => $name];
        }
        break;

    case 'wilayas':
        $country_id = intval($_GET['country_id'] ?? 0);
        if ($country_id > 0) {
            $stmt = $conn->prepare("SELECT WILAYA_ID, WILAYA_NAME_EN, WILAYA_NAME_AR FROM wilaya WHERE COUNTRY_ID = ? ORDER BY WILAYA_NAME_EN");
            $stmt->bind_param("i", $country_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $name = ($LANG === 'ar' && !empty($row['WILAYA_NAME_AR'])) ? $row['WILAYA_NAME_AR'] : $row['WILAYA_NAME_EN'];
                $data[] = ['WILAYA_ID' => $row['WILAYA_ID'], 'WILAYA_NAME_EN' => $name];
            }
            $stmt->close();
        }
        break;

    case 'dairas':
        $wilaya_id = intval($_GET['wilaya_id'] ?? 0);
        if ($wilaya_id > 0) {
            $stmt = $conn->prepare("SELECT DAIRA_ID, DAIRA_NAME_EN, DAIRA_NAME_AR FROM daira WHERE WILAYA_ID = ? ORDER BY DAIRA_NAME_EN");
            $stmt->bind_param("i", $wilaya_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $name = ($LANG === 'ar' && !empty($row['DAIRA_NAME_AR'])) ? $row['DAIRA_NAME_AR'] : $row['DAIRA_NAME_EN'];
                $data[] = ['DAIRA_ID' => $row['DAIRA_ID'], 'DAIRA_NAME_EN' => $name];
            }
            $stmt->close();
        }
        break;
        
    case 'communes':
        $daira_id = intval($_GET['daira_id'] ?? 0);
        if ($daira_id > 0) {
            $stmt = $conn->prepare("SELECT COMMUNE_ID, COMMUNE_NAME_EN, COMMUNE_NAME_AR FROM commune WHERE DAIRA_ID = ? ORDER BY COMMUNE_NAME_EN");
            $stmt->bind_param("i", $daira_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $name = ($LANG === 'ar' && !empty($row['COMMUNE_NAME_AR'])) ? $row['COMMUNE_NAME_AR'] : $row['COMMUNE_NAME_EN'];
                $data[] = ['COMMUNE_ID' => $row['COMMUNE_ID'], 'COMMUNE_NAME_EN' => $name];
            }
            $stmt->close();
        }
        break;
}

echo json_encode($data);
$conn->close();
?>
