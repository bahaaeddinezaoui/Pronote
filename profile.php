<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? null;

// Fallback: refresh role from DB if it is missing in the session
if (!$role) {
    $roleStmt = $conn->prepare("SELECT ROLE FROM user_account WHERE USER_ID = ?");
    $roleStmt->bind_param("i", $userId);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    if ($roleResult->num_rows > 0) {
        $role = $roleResult->fetch_assoc()['ROLE'];
        $_SESSION['role'] = $role;
    }
    $roleStmt->close();
}

$fullName = "Unknown user";
$grade = "N/A";
$position = "";
$safeRole = htmlspecialchars($role ?? 'User');

if ($role === 'Teacher') {
    $stmt = $conn->prepare("
        SELECT TEACHER_FIRST_NAME, TEACHER_LAST_NAME, TEACHER_GRADE
        FROM teacher
        WHERE USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['TEACHER_FIRST_NAME'] ?? '';
        $last = $row['TEACHER_LAST_NAME'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Teacher";
        $grade = htmlspecialchars($row['TEACHER_GRADE'] ?? 'N/A');
    }
    $stmt->close();
} elseif ($role === 'Admin') {
    $stmt = $conn->prepare("
        SELECT ADMINISTRATOR_FIRST_NAME, ADMINISTRATOR_LAST_NAME, ADMINISTRATOR_GRADE, ADMINISTRATOR_POSITION
        FROM administrator
        WHERE USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['ADMINISTRATOR_FIRST_NAME'] ?? '';
        $last = $row['ADMINISTRATOR_LAST_NAME'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Administrator";
        $grade = htmlspecialchars($row['ADMINISTRATOR_GRADE'] ?? 'N/A');
        $position = htmlspecialchars($row['ADMINISTRATOR_POSITION'] ?? '');
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT USERNAME FROM user_account WHERE USER_ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fullName = htmlspecialchars($row['USERNAME']);
    }
    $stmt->close();
}

$homeUrl = ($role === 'Admin') ? 'admin_dashboard.php' : (($role === 'Teacher') ? 'teacher_home.php' : 'fill_form.php');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>User Profile</title>
    <style>
        /* Admin navbar styling */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f9fafb;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .navbar-admin {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-admin a {
            text-decoration: none;
            color: #6b7280;
            padding: 0.5rem 1rem;
            margin-left: 1rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .navbar-admin a:hover {
            background: #f3f4f6;
            color: #4f46e5;
        }

        .navbar-admin a.active {
            color: #6f42c1;
            font-weight: 600;
        }

        .notification-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            min-width: 24px;
            height: 24px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .notification-bell {
            cursor: pointer;
            font-size: 20px;
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .notifications-panel {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 400px;
            max-height: 500px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .notifications-panel.active {
            display: block;
        }

        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .notification-item:hover {
            background-color: #fde68a;
        }

        .notification-item.new {
            background-color: #dbeafe;
            border-left-color: #3b82f6;
            font-weight: 500;
        }

        .notification-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .notification-item-student {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .notification-item-time {
            font-size: 12px;
            color: #6b7280;
        }

        .notification-item-details {
            font-size: 13px;
            color: #374151;
            margin-top: 4px;
        }

        .notification-empty {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
        }

        .profile-wrapper {
            max-width: 720px;
            margin: 2rem auto;
            width: 100%;
        }
        .profile-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 1.75rem;
            box-shadow: var(--shadow-md);
        }
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .profile-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-primary);
        }
        .profile-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        .profile-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .profile-label {
            font-weight: 700;
            color: var(--text-secondary);
        }
        .profile-value {
            font-weight: 700;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
<div class="parent">
    <?php if ($role === 'Admin'): ?>
        <!-- Admin Navbar -->
        <div class="navbar-admin">
            <div style="font-family: sans-serif; display:flex; align-items:center; justify-content:space-between; width:100%;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <a href="admin_home.php" class="navbar_buttons">Home</a>
                    <a href="admin_dashboard.php" class="navbar_buttons">Search</a>
                    <a href="profile.php" class="navbar_buttons active">Profile</a>
                </div>
                <div style="display:flex; align-items:center; gap:20px; margin-left:auto;">
                    <div class="notification-bell" id="notificationBell" onclick="toggleNotificationsPanel()">
                        🔔
                        <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
                        <div class="notifications-panel" id="notificationsPanel">
                            <div style="padding:12px; border-bottom:1px solid #e5e7eb; font-weight:600; background:#f9fafb;">
                                New Observations
                            </div>
                            <div id="notificationsContent"></div>
                        </div>
                    </div>
                    <a href="logout.php" class="navbar_buttons logout-btn">Logout</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Teacher/Other Navbar -->
        <div class="div1" id="navbar">
            <div style="font-family: sans-serif; display:flex; align-items:center; gap:12px; width:100%;">
                <a href="<?php echo $homeUrl; ?>" class="navbar_buttons">Home</a>
                
                <?php if ($role === 'Teacher'): ?>
                    <a href="fill_form.php?tab=absences" class="navbar_buttons">Absences</a>
                    <a href="fill_form.php?tab=observations" class="navbar_buttons">Observations</a>
                <?php endif; ?>

                <a href="profile.php" class="navbar_buttons active">Profile</a>
                <a href="logout.php" class="navbar_buttons logout-btn" style="margin-left:auto;">Logout</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="div2" id="left_side">
        <fieldset id="form_fill">
            <legend id="form_legend">Profile</legend>
            <div class="profile-wrapper">
                <div class="profile-card">
                    <div class="profile-header">
                        <div>
                            <div style="text-transform: uppercase; font-size: 0.75rem; color: var(--text-light);">Signed in as</div>
                            <h2><?php echo $safeRole; ?></h2>
                        </div>
                    </div>
                    <div class="profile-meta">
                        <div class="profile-item">
                            <span class="profile-label">Full name</span>
                            <span class="profile-value"><?php echo $fullName; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Role</span>
                            <span class="profile-value"><?php echo $safeRole; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Grade</span>
                            <span class="profile-value"><?php echo $grade ?: 'N/A'; ?></span>
                        </div>
                        <?php if ($position !== ""): ?>
                        <div class="profile-item">
                            <span class="profile-label">Position</span>
                            <span class="profile-value"><?php echo $position; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
</div>

<script>
let newNotifications = [];

// Only initialize admin notifications if user is admin
<?php if ($role === 'Admin'): ?>
function toggleNotificationsPanel() {
    const panel = document.getElementById('notificationsPanel');
    if (panel) {
        panel.classList.toggle('active');
    }
}

function fetchNotifications() {
    fetch('get_new_notifications.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                newNotifications = data.notifications;
                updateNotificationDisplay();
            }
        })
        .catch(err => console.error('Error fetching notifications:', err));
}

function updateNotificationDisplay() {
    const countBadge = document.getElementById('notificationCount');
    const content = document.getElementById('notificationsContent');
    
    if (newNotifications.length > 0) {
        countBadge.textContent = newNotifications.length;
        countBadge.style.display = 'flex';
        
        let html = '';
        newNotifications.forEach((notif) => {
            html += `<div class="notification-item new">
                <div class="notification-item-header">
                    <span class="notification-item-student">${notif.student_name}</span>
                    <span class="notification-item-time">${notif.observation_time}</span>
                </div>
                <div class="notification-item-details">
                    <div><strong>Teacher:</strong> ${notif.teacher_name}</div>
                    <div><strong>Session:</strong> ${notif.session_date} (${notif.session_time})</div>
                    <div><strong>Motif:</strong> ${notif.motif}</div>
                </div>
            </div>`;
        });
        content.innerHTML = html;
    } else {
        countBadge.style.display = 'none';
        content.innerHTML = '<div class="notification-empty">No new observations</div>';
    }
}

// Close notifications panel when clicking outside
document.addEventListener('click', function(event) {
    const notifBell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationsPanel');
    
    if (notifBell && panel && !notifBell.contains(event.target)) {
        panel.classList.remove('active');
    }
});

// Fetch notifications on page load
fetchNotifications();

// Refresh notifications every 30 seconds
setInterval(fetchNotifications, 30000);
<?php endif; ?>
</script>

</body>
</html>

