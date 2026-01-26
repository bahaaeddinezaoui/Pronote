<?php
// admin_home.php - Welcome page for Admin showing basic dashboard statistics
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

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
$admin_position = t('administrator');
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
$motif_col = ($LANG === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";
$result = $conn->query("
    SELECT 
        s.STUDENT_FIRST_NAME_EN,
        s.STUDENT_LAST_NAME_EN,
        t.TEACHER_FIRST_NAME_EN,
        t.TEACHER_LAST_NAME_EN,
        $motif_col AS OBSERVATION_MOTIF,
        tmoas.OBSERVATION_DATE_AND_TIME,
        ss.STUDY_SESSION_DATE
    FROM teacher_makes_an_observation_for_a_student tmoas
    JOIN student s ON tmoas.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
    JOIN teacher t ON tmoas.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    JOIN study_session ss ON tmoas.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    LEFT JOIN observation_motif om ON tmoas.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID
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
$motif_col_abs = ($LANG === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";
$result = $conn->query("
    SELECT 
        s.STUDENT_FIRST_NAME_EN,
        s.STUDENT_LAST_NAME_EN,
        $motif_col_abs AS ABSENCE_MOTIF,
        a.ABSENCE_DATE_AND_TIME,
        ss.STUDY_SESSION_DATE
    FROM student_gets_absent sga
    JOIN student s ON sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
    JOIN absence a ON sga.ABSENCE_ID = a.ABSENCE_ID
    JOIN study_session ss ON a.STUDY_SESSION_ID = ss.STUDY_SESSION_ID
    LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
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
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('home'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* Match admin_dashboard.php look & feel */
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
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
            color: var(--primary-color);
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
            color: var(--primary-color);
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

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="admin-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1><?php echo t('welcome_admin', $admin_position); ?></h1>
        <p><?php echo t('welcome_admin_sub'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size: 24px;">👨‍🎓</div>
            <div class="stat-label"><?php echo t('stat_total_students'); ?></div>
            <div class="stat-number"><?php echo $total_students; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">👨‍🏫</div>
            <div class="stat-label"><?php echo t('stat_total_teachers'); ?></div>
            <div class="stat-number"><?php echo $total_teachers; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📚</div>
            <div class="stat-label"><?php echo t('stat_total_classes'); ?></div>
            <div class="stat-number"><?php echo $total_classes; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📖</div>
            <div class="stat-label"><?php echo t('stat_total_majors'); ?></div>
            <div class="stat-number"><?php echo $total_majors; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">🏛️</div>
            <div class="stat-label"><?php echo t('stat_total_sections'); ?></div>
            <div class="stat-number"><?php echo $total_sections; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">⏰</div>
            <div class="stat-label"><?php echo t('stat_study_sessions'); ?></div>
            <div class="stat-number"><?php echo $total_sessions; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">❌</div>
            <div class="stat-label"><?php echo t('stat_total_absences'); ?></div>
            <div class="stat-number"><?php echo $total_absences; ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size: 24px;">📝</div>
            <div class="stat-label"><?php echo t('stat_total_observations'); ?></div>
            <div class="stat-number"><?php echo $total_observations; ?></div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid-2">
        <!-- Recent Observations -->
        <div class="info-section">
            <h2><?php echo t('recent_observations'); ?></h2>
            <?php if (count($recent_observations) > 0): ?>
                <ul class="info-list">
                    <?php foreach ($recent_observations as $obs): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($obs['STUDENT_FIRST_NAME_EN'] . ' ' . $obs['STUDENT_LAST_NAME_EN']); ?></strong>
                                <div style="font-size: 12px; color: #6b7280;"><?php echo t('by'); ?> <?php echo htmlspecialchars($obs['TEACHER_FIRST_NAME_EN'] . ' ' . $obs['TEACHER_LAST_NAME_EN']); ?></div>
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
                <p class="no-data"><?php echo t('no_recent_observations'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Recent Absences -->
        <div class="info-section">
            <h2><?php echo t('recent_absences'); ?></h2>
            <?php if (count($recent_absences) > 0): ?>
                <ul class="info-list">
                    <?php foreach ($recent_absences as $abs): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($abs['STUDENT_FIRST_NAME_EN'] . ' ' . $abs['STUDENT_LAST_NAME_EN']); ?></strong>
                                <div style="font-size: 12px; color: #6b7280;"><?php echo t('session_label'); ?>: <?php echo date('d/m/Y', strtotime($abs['STUDY_SESSION_DATE'])); ?></div>
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
                <p class="no-data"><?php echo t('no_recent_absences'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>
</div>
</div>

<script>
    // Any page-specific scripts can go here
</script>

</body>
</html>
