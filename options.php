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
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('nav_options'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .options-section {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        .option-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }
        .option-info {
            flex: 1;
        }
        .option-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .option-desc {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: var(--surface-color);
            padding: 24px;
            border-radius: 16px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<!-- Change Password Modal -->
<div id="passwordModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo t('change_password'); ?></h3>
            <button class="close-modal" id="closePasswordModal">&times;</button>
        </div>
        <form id="changePasswordForm">
            <div class="form-group">
                <label class="form-label" for="old_password"><?php echo t('old_password'); ?></label>
                <div style="position: relative;">
                    <input class="form-input" type="password" id="old_password" name="old_password" required style="padding-right: 40px;">
                    <span class="toggle-password" data-target="old_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password"><?php echo t('new_password'); ?></label>
                <div style="position: relative;">
                    <input class="form-input" type="password" id="new_password" name="new_password" required style="padding-right: 40px;">
                    <span class="toggle-password" data-target="new_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_new_password"><?php echo t('confirm_new_password'); ?></label>
                <div style="position: relative;">
                    <input class="form-input" type="password" id="confirm_new_password" name="confirm_new_password" required style="padding-right: 40px;">
                    <span class="toggle-password" data-target="confirm_new_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">👁️</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="changePasswordBtn"><?php echo t('update_password'); ?></button>
            <div id="changePasswordMsg" class="alert mt-4" style="display:none; text-align: center;"></div>
        </form>
    </div>
</div>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div style="padding: 24px; max-width: 800px;">
            <h1 style="margin: 0 0 24px 0; font-size: 28px; font-weight: 800; color: var(--text-primary);">
                <?php echo t('nav_options'); ?>
            </h1>

            <!-- Security Section -->
            <div class="options-section">
                <div class="section-header">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <h2 class="section-title"><?php echo t('security') ?? 'Security'; ?></h2>
                </div>
                
                <div class="option-item">
                    <div class="option-info">
                        <div class="option-label"><?php echo t('change_password'); ?></div>
                        <div class="option-desc"><?php echo t('change_password_desc') ?? 'Update your account password to stay secure.'; ?></div>
                    </div>
                    <button class="btn btn-secondary" id="openPasswordModal" style="width: auto; padding: 8px 16px;">
                        <?php echo t('change_password'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var T = <?php echo json_encode($T ?? []); ?>;
        
        // Modal elements
        var modal = document.getElementById('passwordModal');
        var openBtn = document.getElementById('openPasswordModal');
        var closeBtn = document.getElementById('closePasswordModal');
        
        // Form elements
        var form = document.getElementById('changePasswordForm');
        var msg = document.getElementById('changePasswordMsg');
        var btn = document.getElementById('changePasswordBtn');

        // Modal Logic
        openBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
            // Force reflow
            modal.offsetHeight;
            modal.classList.add('active');
            form.reset();
            msg.style.display = 'none';
        });

        function closeModal() {
            modal.classList.remove('active');
            setTimeout(function() {
                modal.style.display = 'none';
            }, 300);
        }

        closeBtn.addEventListener('click', closeModal);

        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Password Visibility Toggle
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🔒';
                } else {
                    input.type = 'password';
                    this.textContent = '👁️';
                }
            });
        });

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
