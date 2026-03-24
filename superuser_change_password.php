<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

$role = trim((string)($_SESSION['role'] ?? ''));
if (!isset($_SESSION['user_id']) || $role !== 'Superuser') {
    header('Location: login.php');
    exit;
}

$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $targetUserId = $_POST['target_user_id'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($targetUserId) || empty($newPassword) || empty($confirmPassword)) {
        $message = t('fields_required');
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = t('password_mismatch');
        $messageType = 'error';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_account SET PASSWORD = ? WHERE USER_ID = ?");
        $stmt->bind_param("si", $hashedPassword, $targetUserId);
        if ($stmt->execute()) {
            $message = t('saved');
            $messageType = 'success';
        } else {
            $message = t('error_unexpected');
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Fetch all users for the selection dropdown
$users = [];
$result = $conn->query("SELECT USER_ID, USERNAME, ROLE FROM user_account ORDER BY USERNAME ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('change_password'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .container {
            max-width: 800px;
            margin: 0;
            padding: 24px;
        }
        .card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); }
        .form-select, .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--background-color);
            color: var(--text-primary);
            font-size: 16px;
        }
        .search-container {
            position: relative;
        }
        .search-input {
            margin-bottom: 5px;
            padding-right: 35px;
        }
        .search-icon {
            position: absolute;
            right: 12px;
            top: 12px;
            color: var(--text-secondary);
            pointer-events: none;
        }
        .user-dropdown {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-color);
            position: absolute;
            width: 100%;
            z-index: 1000;
            display: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
            top: 100%;
            left: 0;
            margin-top: 4px;
        }
        .user-option {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
            background: var(--surface-color);
        }
        .user-option:last-child { border-bottom: none; }
        .user-option:hover { 
            background: var(--primary-light); 
        }
        .user-option.selected { 
            background: var(--primary-color); 
            color: white; 
        }
        .user-option.selected .role-tag {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .user-option .username-text {
            font-weight: 500;
            color: inherit;
        }
        .user-option .role-tag {
            font-size: 11px;
            background: var(--bg-tertiary);
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .no-results {
            padding: 10px 15px;
            color: var(--text-secondary);
            font-style: italic;
            text-align: center;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .alert {
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1 style="margin: 0 0 24px 0; font-size: 28px; font-weight: 800; color: var(--text-primary);"><?php echo t('change_password'); ?></h1>
            <div class="card">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo t('username'); ?></label>
                        <div class="search-container">
                            <input type="text" id="userSearch" class="form-input search-input" placeholder="<?php echo t('student_search_placeholder'); ?>" autocomplete="off">
                            <span class="search-icon">🔍</span>
                            <input type="hidden" name="target_user_id" id="targetUserId" required>
                            <div id="userDropdown" class="user-dropdown">
                                <?php foreach ($users as $u): ?>
                                    <div class="user-option" data-id="<?php echo $u['USER_ID']; ?>" data-username="<?php echo htmlspecialchars($u['USERNAME']); ?>">
                                        <span class="username-text"><?php echo htmlspecialchars($u['USERNAME']); ?></span>
                                        <span class="role-tag"><?php echo htmlspecialchars($u['ROLE']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div id="noResults" class="no-results" style="display: none;"><?php echo t('no_records_found'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo t('new_password'); ?></label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo t('confirm_new_password'); ?></label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>

                    <button type="submit" class="btn-primary"><?php echo t('update_password'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const dropdown = document.getElementById('userDropdown');
    const options = dropdown.querySelectorAll('.user-option');
    const hiddenInput = document.getElementById('targetUserId');
    const noResults = document.getElementById('noResults');

    // Show dropdown on focus
    searchInput.addEventListener('focus', function() {
        dropdown.style.display = 'block';
        filterUsers();
    });

    // Filter users as typing
    searchInput.addEventListener('input', filterUsers);

    function filterUsers() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        options.forEach(opt => {
            const username = opt.getAttribute('data-username').toLowerCase();
            if (username.includes(query)) {
                opt.style.display = 'block';
                visibleCount++;
            } else {
                opt.style.display = 'none';
            }
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        dropdown.style.display = 'block';
    }

    // Handle option selection
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');

            searchInput.value = username;
            hiddenInput.value = userId;
            
            options.forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            
            dropdown.style.display = 'none';
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
