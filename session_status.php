<?php
session_start();

$response = [
    'loggedIn' => false,
    'home' => 'login.php',
];

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $response['loggedIn'] = true;
    $response['home'] = ($_SESSION['role'] === 'Admin') ? 'admin_dashboard.php' : 'fill_form.php';
}

header('Content-Type: application/json');
// Prevent caching so the status reflects the current session each time.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode($response);
?>

