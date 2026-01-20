<?php
// admin_home.php - Welcome page for Admin showing basic dashboard statistics
session_start();
date_default_timezone_set('Africa/Algiers');

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin info
$admin_name = "Admin";
$admin_position = "Administrator";
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT ADMINISTRATOR_FIRST_NAME_EN, ADMINISTRATOR_LAST_NAME_EN, ADMINISTRATOR_POSITION FROM administrator WHERE USER_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME_EN']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME_EN']);
        $admin_position = htmlspecialchars($row['ADMINISTRATOR_POSITION']);
    }
    $stmt->close();
}

// Get statistics from database
$total_students = 0;
$total_teachers = 0;
$total_classes = 0;
$total_majors = 0;
$total_sections = 0;
$total_sessions = 0;
$total_absences = 0;
$total_observations = 0;

// Total Students
$result = $conn->query("SELECT COUNT(*) as count FROM student");
if ($result) {
    $row = $result->fetch_assoc();
    $total_students = $row['count'];
}

// Total Teachers
$result = $conn->query("SELECT COUNT(*) as count FROM teacher");
if ($result) {
    $row = $result->fetch_assoc();
    $total_teachers = $row['count'];
}

// Total Classes
$result = $conn->query("SELECT COUNT(*) as count FROM class");
if ($result) {
    $row = $result->fetch_assoc();
    $total_classes = $row['count'];
}

// Total Majors
$result = $conn->query("SELECT COUNT(*) as count FROM major");
if ($result) {
    $row = $result->fetch_assoc();
    $total_majors = $row['count'];
}

// Total Sections
$result = $conn->query("SELECT COUNT(*) as count FROM section");
if ($result) {
    $row = $result->fetch_assoc();
    $total_sections = $row['count'];
}

// Total Study Sessions
$result = $conn->query("SELECT COUNT(*) as count FROM study_session");
if ($result) {
    $row = $result->fetch_assoc();
    $total_sessions = $row['count'];
}

// Total Absences
$result = $conn->query("SELECT COUNT(*) as count FROM absence");
if ($result) {
    $row = $result->fetch_assoc();
    $total_absences = $row['count'];
}

// Total Observations
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_makes_an_observation_for_a_student");
if ($result) {
    $row = $result->fetch_assoc();
    $total_observations = $row['count'];
}

// Get recent observations (last 5)
$recent_observations = [];
$result = $conn->query("
    SELECT 
        s.STUDENT_FIRST_NAME_EN,
        s.STUDENT_LAST_NAME_EN,
        t.TEACHER_FIRST_NAME_EN,
        t.TEACHER_LAST_NAME_EN,
        tmoas.OBSERVATION_MOTIF,
        tmoas.OBSERVATION_DATE_AND_TIME,
        ss.STUDY_SESSION_DATE
    FROM teacher_makes_an_observation_for_a_student tmoas
    JOIN student s ON tmoas.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
    JOIN teacher t ON tmoas.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    JOIN study_session ss ON tmoas.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    ORDER BY tmoas.OBSERVATION_DATE_AND_TIME DESC
    LIMIT 5
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_observations[] = $row;
    }
}

// Get recent absences (last 5)
$recent_absences = [];
$result = $conn->query("
    SELECT 
        s.STUDENT_FIRST_NAME_EN,
        s.STUDENT_LAST_NAME_EN,
        a.ABSENCE_MOTIF,
        a.ABSENCE_DATE_AND_TIME,
        ss.STUDY_SESSION_DATE
    FROM student_gets_absent sga
    JOIN student s ON sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
    JOIN absence a ON sga.ABSENCE_ID = a.ABSENCE_ID
    JOIN study_session ss ON a.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    ORDER BY a.ABSENCE_DATE_AND_TIME DESC
    LIMIT 5
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_absences[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Admin Home - Welcome</title>
    <style>
        /* Match admin_dashboard.php look & feel */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f9fafb;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
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

        .welcome-section {
            background: linear-gradient(135deg, #6f42c1 0%, #8c63d9 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
        }

        .welcome-section h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .welcome-section p {
            margin: 5px 0;
            font-size: 15px;
            opacity: 0.95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #6f42c1;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .info-section {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }

        .info-section h2 {
            margin: 0 0 15px 0;
            color: #6f42c1;
            font-size: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            font-size: 14px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list strong {
            color: #1f1f1f;
            font-weight: 600;
        }

        .info-list .highlight {
            color: #6f42c1;
            font-weight: 500;
        }

        .no-data {
            color: #888;
            font-style: italic;
            padding: 15px;
            text-align: center;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>

<div class="navbar-admin">
    <div style="font-family: sans-serif; display:flex; align-items:center; width:100%;">
        <div style="font-weight: 700; font-size: 1.25rem; color: #111; margin-right: 2rem;">📚 Pronote</div>
        <div style="display:flex; align-items:center;">
            <a href="admin_home.php" class="navbar_buttons active">Home</a>
            <a href="admin_dashboard.php" class="navbar_buttons">Search</a>
            <a href="admin_search_student.php" class="navbar_buttons">Student Records</a>
            <a href="profile.php" class="navbar_buttons">Profile</a>
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

<div class="admin-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1>Welcome, <?php echo $admin_position; ?>! 👋</h1>
        <p>Here's an overview of the educational management system</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size: 24px;">👨‍🎓</div>
            <div class="stat-label">Total Students</div>
            <div class="stat-number"><?php echo $total_students; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">👨‍🏫</div>
            <div class="stat-label">Total Teachers</div>
            <div class="stat-number"><?php echo $total_teachers; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📚</div>
            <div class="stat-label">Total Classes</div>
            <div class="stat-number"><?php echo $total_classes; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📖</div>
            <div class="stat-label">Total Majors</div>
            <div class="stat-number"><?php echo $total_majors; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">🏛️</div>
            <div class="stat-label">Total Sections</div>
            <div class="stat-number"><?php echo $total_sections; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">⏰</div>
            <div class="stat-label">Study Sessions</div>
            <div class="stat-number"><?php echo $total_sessions; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">❌</div>
            <div class="stat-label">Total Absences</div>
            <div class="stat-number"><?php echo $total_absences; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📝</div>
            <div class="stat-label">Total Observations</div>
            <div class="stat-number"><?php echo $total_observations; ?></div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid-2">
        <!-- Recent Observations -->
        <div class="info-section">
            <h2>Recent Observations</h2>
            <?php if (count($recent_observations) > 0): ?>
                <ul class="info-list">
                    <?php foreach ($recent_observations as $obs): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($obs['STUDENT_FIRST_NAME_EN'] . ' ' . $obs['STUDENT_LAST_NAME_EN']); ?></strong>
                                <div style="font-size: 12px; color: #6b7280;">By <?php echo htmlspecialchars($obs['TEACHER_FIRST_NAME_EN'] . ' ' . $obs['TEACHER_LAST_NAME_EN']); ?></div>
                            </div>
                            <div>
                                <span class="highlight"><?php echo htmlspecialchars($obs['OBSERVATION_MOTIF']); ?></span>
                            </div>
                            <div style="text-align: right; color: #9ca3af;">
                                <?php echo date('d/m/Y H:i', strtotime($obs['OBSERVATION_DATE_AND_TIME'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">No recent observations</p>
            <?php endif; ?>
        </div>

        <!-- Recent Absences -->
        <div class="info-section">
            <h2>Recent Absences</h2>
            <?php if (count($recent_absences) > 0): ?>
                <ul class="info-list">
                    <?php foreach ($recent_absences as $abs): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($abs['STUDENT_FIRST_NAME_EN'] . ' ' . $abs['STUDENT_LAST_NAME_EN']); ?></strong>
                                <div style="font-size: 12px; color: #6b7280;">Session: <?php echo date('d/m/Y', strtotime($abs['STUDY_SESSION_DATE'])); ?></div>
                            </div>
                            <div>
                                <span class="highlight"><?php echo htmlspecialchars($abs['ABSENCE_MOTIF']); ?></span>
                            </div>
                            <div style="text-align: right; color: #9ca3af;">
                                <?php echo date('d/m/Y H:i', strtotime($abs['ABSENCE_DATE_AND_TIME'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">No recent absences</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let newNotifications = [];

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
</script>

</body>
</html>
