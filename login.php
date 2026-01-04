<?php
session_start();

// Prevent cached copy of the login page so back button reloads and respects session
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// If already authenticated, send user to their home
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin_home.php');
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
                <div style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required style="padding-right: 40px;">
                    <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light); user-select: none; font-size: 1.2rem;">
                        👁️
                    </span>
                </div>
            </div>

            <button type="submit" id="login_button" style="display: flex; justify-content: center; align-items: center; width: 100%;">Login</button>
            <div id="login_error" style="color: red; margin-top: 10px; display: none; text-align: center; width: 100%;"></div>
        </fieldset>
    </form>
    <script>
        const loginForm = document.getElementById('login');
        const errorDiv = document.getElementById('login_error');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            errorDiv.style.display = 'none';
            
            const formData = new FormData(loginForm);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                errorDiv.textContent = 'An unexpected error occurred.';
                errorDiv.style.display = 'block';
            });
        });

        // Password toggle logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });

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

