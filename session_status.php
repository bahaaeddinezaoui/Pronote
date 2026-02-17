<?php
session_start();

$response = [
    'loggedIn' => false,
    'home' => 'login.php',
];

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $response['loggedIn'] = true;
    if ($_SESSION['role'] === 'Admin') {
        $response['home'] = 'admin_home.php';
    } elseif ($_SESSION['role'] === 'Secretary') {
        $response['home'] = 'secretary_home.php';
    } elseif ($_SESSION['role'] === 'Teacher') {
        if (!empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) {
            $response['home'] = 'teacher_onboarding.php';
        } else {
            $response['home'] = 'teacher_home.php';
        }
    } else {
        $response['home'] = 'fill_form.php';
    }
}

header('Content-Type: application/json');
// Prevent caching so the status reflects the current session each time.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode($response);
?>

