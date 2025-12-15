<?php
session_start();

// Prevent cached copy of the login page so back button reloads and respects session
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// If already authenticated, send user to their home
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: fill_form.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css" />
    <title>Login</title>
</head>
<body>
    <form action="index.php" method="post" id="login">
        <fieldset id="login_fieldset">
            <legend id="login_credentials">Welcome to Pronote!</legend>
            <div id="login_username">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div id="login_password">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" id="login_button">Login</button>
        </fieldset>
    </form>
    <script>
        // If the user navigates back to this page while still logged in, bounce them home.
        window.addEventListener('pageshow', function () {
            fetch('session_status.php', { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    if (data.loggedIn && data.home) {
                        window.location.replace(data.home);
                    }
                })
                .catch(() => { /* ignore */ });
        });
    </script>
</body>
</html>

