<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

// Prevent cached copy of the login page so back button reloads and respects session
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// If already authenticated, send user to their home
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin_home.php');
    } elseif ($_SESSION['role'] === 'Secretary') {
        header('Location: secretary_home.php');
    } elseif ($_SESSION['role'] === 'Teacher') {
        header('Location: teacher_home.php');
    } else {
        header('Location: fill_form.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('login'); ?> - <?php echo t('app_name'); ?></title>
</head>
<body>
    <div class="login-lang-fix"><?php include __DIR__ . '/lang/switcher.php'; ?></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title"><?php echo t('login_welcome'); ?></h1>
            </div>

            <form action="index.php" method="post" id="login">
                <div class="form-group">
                    <label class="form-label" for="username"><?php echo t('username'); ?></label>
                    <input class="form-input" type="text" id="username" name="username" placeholder="<?php echo t('username_placeholder'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password"><?php echo t('password'); ?></label>
                    <div style="position: relative;">
                        <input class="form-input" type="password" id="password" name="password" placeholder="<?php echo t('password_placeholder'); ?>" required style="padding-right: 40px;">
                        <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">
                            👁️
                        </span>
                    </div>
                </div>

                <button type="submit" id="login_button" class="btn btn-primary"><?php echo t('login_button'); ?></button>
                <div id="login_error" class="alert alert-error mt-4" style="display: none; text-align: center;"></div>
            </form>
        </div>
    </div>

    <script>
        var T = <?php echo json_encode($T); ?>;
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
                errorDiv.textContent = (T && T.error_unexpected) ? T.error_unexpected : 'An unexpected error occurred.';
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

