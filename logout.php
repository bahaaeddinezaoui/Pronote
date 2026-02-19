<?php
// logout.php

// Start the session
session_start();

// Check if user is in onboarding process
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Teacher') {
    if (!empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) {
        // User is in onboarding, redirect back to onboarding page
        header("Location: teacher_onboarding.php");
        exit;
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php"); // or wherever your login form is
exit;
?>
