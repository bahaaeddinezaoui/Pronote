<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('nav_options'); ?> - <?php echo t('app_name'); ?></title>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div style="padding: 24px; max-width: 560px;">
            <h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 700;">
                <?php echo t('nav_options'); ?>
            </h1>

            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;">
                <div style="font-weight: 700; margin-bottom: 12px;">
                    <?php echo t('change_password'); ?>
                </div>

                <form id="changePasswordForm">
                    <div class="form-group">
                        <label class="form-label" for="old_password"><?php echo t('old_password'); ?></label>
                        <input class="form-input" type="password" id="old_password" name="old_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password"><?php echo t('new_password'); ?></label>
                        <input class="form-input" type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password"><?php echo t('confirm_new_password'); ?></label>
                        <input class="form-input" type="password" id="confirm_new_password" name="confirm_new_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary" id="changePasswordBtn"><?php echo t('update_password'); ?></button>
                    <div id="changePasswordMsg" class="alert mt-4" style="display:none; text-align: center;"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var T = <?php echo json_encode($T ?? []); ?>;
        var form = document.getElementById('changePasswordForm');
        var msg = document.getElementById('changePasswordMsg');
        var btn = document.getElementById('changePasswordBtn');

        function showMessage(text, type) {
            msg.textContent = text;
            msg.style.display = 'block';
            msg.className = 'alert mt-4 ' + (type === 'success' ? 'alert-success' : 'alert-error');
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            msg.style.display = 'none';

            var oldPassword = document.getElementById('old_password').value;
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_new_password').value;

            if (!oldPassword || !newPassword || !confirmPassword) {
                showMessage(T.fields_required || 'All fields are required.', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showMessage(T.password_mismatch || 'Passwords do not match.', 'error');
                return;
            }

            btn.disabled = true;

            fetch('change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    oldPassword: oldPassword,
                    newPassword: newPassword,
                    confirmPassword: confirmPassword
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.success) {
                    showMessage(data.message || (T.password_updated_logout || 'Password updated. You will be logged out.'), 'success');
                    setTimeout(function() {
                        window.location.href = 'logout.php';
                    }, 1500);
                } else {
                    showMessage((data && data.message) ? data.message : (T.password_change_failed || 'Password change failed.'), 'error');
                }
            })
            .catch(function() {
                showMessage(T.error_unexpected || 'An unexpected error occurred.', 'error');
            })
            .finally(function() {
                btn.disabled = false;
            });
        });
    })();
</script>

</body>
</html>
