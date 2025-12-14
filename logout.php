<?php
// logout.php

// Start the session
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.html"); // or wherever your login form is
exit;
?>
