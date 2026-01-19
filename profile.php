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

$photoData = null;

if ($role === 'Teacher') {
    $stmt = $conn->prepare("
        SELECT TEACHER_FIRST_NAME, TEACHER_LAST_NAME, TEACHER_GRADE, TEACHER_PHOTO
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
        $photoData = $row['TEACHER_PHOTO'];
    }
    $stmt->close();
} elseif ($role === 'Admin') {
    $stmt = $conn->prepare("
        SELECT ADMINISTRATOR_FIRST_NAME_EN, ADMINISTRATOR_LAST_NAME_EN, ADMINISTRATOR_GRADE, ADMINISTRATOR_POSITION, ADMINISTRATOR_PHOTO
        FROM administrator
        WHERE USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['ADMINISTRATOR_FIRST_NAME_EN'] ?? '';
        $last = $row['ADMINISTRATOR_LAST_NAME_EN'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Administrator";
        $grade = htmlspecialchars($row['ADMINISTRATOR_GRADE'] ?? 'N/A');
        $position = htmlspecialchars($row['ADMINISTRATOR_POSITION'] ?? '');
        $photoData = $row['ADMINISTRATOR_PHOTO'];
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
        /* Modern Profile Styles */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .navbar-admin, .div1 {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-admin a, .navbar_buttons {
            text-decoration: none;
            color: #6b7280;
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .navbar-admin a:hover, .navbar_buttons:hover {
            background: #f3f4f6;
            color: #4f46e5;
        }

        .navbar-admin a.active, .navbar_buttons.active {
            color: #6f42c1;
            font-weight: 600;
            background: #f3f4f6;
        }

        .notification-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: -8px;
            margin-top: -8px;
            position: relative;
        }

        .notification-bell {
            cursor: pointer;
            font-size: 20px;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .notification-bell:hover {
            background: #f3f4f6;
        }

        .notifications-panel {
            display: none;
            position: absolute;
            top: 60px;
            right: 2rem;
            width: 380px;
            max-height: 480px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .notifications-panel.active { display: block; }

        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            background: #fff9db; /* Light yellow for unread */
            border-left: 4px solid #f59e0b;
        }

        .notification-item:hover { background: #fff3bf; }
        
        .notification-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .notification-item-student { font-weight: 600; color: #1f2937; }
        .notification-item-time { font-size: 12px; color: #6b7280; }
        .notification-item-details { font-size: 13px; color: #4b5563; }
        .notification-empty { padding: 24px; text-align: center; color: #9ca3af; }

        /* Profile Specific Styles */
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .profile-banner {
            height: 160px;
            background: linear-gradient(135deg, #6f42c1 0%, #8b5cf6 100%);
            position: relative;
        }

        .profile-header {
            padding: 0 40px;
            margin-top: -60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 10;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 16px;
        }

        .profile-avatar-inner {
            width: 100%;
            height: 100%;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #6f42c1;
            font-weight: 700;
        }

        .profile-name {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 4px 0;
        }

        .profile-role-badge {
            display: inline-block;
            background: #ede9fe;
            color: #6f42c1;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
        }

        .profile-content {
            padding: 0 40px 40px 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .info-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.2s;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-color: #ddd6fe;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #ede9fe;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #6f42c1;
        }

        .info-text label {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-text div {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .profile-header { padding: 0 20px; }
            .profile-content { padding: 0 20px 30px 20px; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    
    <?php if ($role === 'Admin'): ?>
        <!-- Admin Navbar -->
        <div class="navbar-admin">
            <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
                <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
                <div style="display:flex; align-items:center;">
                    <a href="admin_home.php">Home</a>
                    <a href="admin_dashboard.php">Search</a>
                    <a href="admin_search_student.php">Student Records</a>
                    <a href="profile.php" class="active">Profile</a>
                </div>
                <div style="display:flex; align-items:center; gap:16px; margin-left:auto;">
                    <div class="notification-bell" id="notificationBell" onclick="toggleNotificationsPanel()">
                        🔔
                        <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
                        <div class="notifications-panel" id="notificationsPanel">
                            <div style="padding:16px; border-bottom:1px solid #e5e7eb; font-weight:600; background:#f9fafb;">
                                New Observations
                            </div>
                            <div id="notificationsContent"></div>
                        </div>
                    </div>
                    <a href="logout.php" style="color: #dc2626;">Logout</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Teacher/Other Navbar (Consistent with fill_form.php) -->
        <div class="div1" id="navbar">
            <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
                <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <a href="<?php echo $homeUrl; ?>" class="navbar_buttons">Home</a>
                    
                    <?php if ($role === 'Teacher'): ?>
                        <a href="fill_form.php?tab=absences" class="navbar_buttons">Absences</a>
                        <a href="fill_form.php?tab=observations" class="navbar_buttons">Observations</a>
                    <?php endif; ?>

                    <a href="profile.php" class="navbar_buttons active">Profile</a>
                </div>
                <a href="logout.php" class="navbar_buttons logout-btn" style="margin-left:auto;">Logout</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-banner"></div>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="profile-avatar-inner">
                        <?php if (!empty($photoData)): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($photoData); ?>" alt="Profile Photo" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($fullName, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <h1 class="profile-name"><?php echo $fullName; ?></h1>
                <div class="profile-role-badge"><?php echo $safeRole; ?></div>
            </div>

            <div class="profile-content">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon">🆔</div>
                        <div class="info-text">
                            <label>User ID</label>
                            <div>#<?php echo $userId; ?></div>
                        </div>
                    </div>

                    <?php if ($grade !== "N/A"): ?>
                    <div class="info-card">
                        <div class="info-icon">🎓</div>
                        <div class="info-text">
                            <label>Grade / Level</label>
                            <div><?php echo $grade; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($position !== ""): ?>
                    <div class="info-card">
                        <div class="info-icon">💼</div>
                        <div class="info-text">
                            <label>Position</label>
                            <div><?php echo $position; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <div class="info-icon">🛡️</div>
                        <div class="info-text">
                            <label>Access Level</label>
                            <div><?php echo $safeRole; ?> Privileges</div>
                        </div>
                    </div>
                </div>
            </div>
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

