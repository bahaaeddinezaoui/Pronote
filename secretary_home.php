teacher_onboarding.php<?php
// secretary_home.php - Welcome page for Secretary
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'Secretary') {
    header("Location: index.php");
    exit;
}

// 2. Database Connection
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Fetch Secretary Name + editable category
$user_id = $_SESSION['user_id'];
$secretary_name = "Secretary";
$editable_category_id = null;
$stmt = $conn->prepare("SELECT SECRETARY_FIRST_NAME_EN, SECRETARY_LAST_NAME_EN, SECRETARY_FIRST_NAME_AR, SECRETARY_LAST_NAME_AR, SECRETARY_EDITABLE_CATEGORY_ID FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $editable_category_id = isset($row['SECRETARY_EDITABLE_CATEGORY_ID']) ? (int)$row['SECRETARY_EDITABLE_CATEGORY_ID'] : null;
    if ($LANG === 'ar' && !empty($row['SECRETARY_FIRST_NAME_AR'])) {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_AR'] . ' ' . $row['SECRETARY_LAST_NAME_AR']);
    } else {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_EN'] . ' ' . $row['SECRETARY_LAST_NAME_EN']);
    }
}
$stmt->close();

// 4. Statistics
$total_students = 0;
if (!empty($editable_category_id) && $editable_category_id > 0) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) as count FROM student WHERE CATEGORY_ID = ?");
    if ($stmtCount) {
        $stmtCount->bind_param("i", $editable_category_id);
        $stmtCount->execute();
        $res = $stmtCount->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $total_students = (int)$row['count'];
        }
        $stmtCount->close();
    }
} else {
    // Fallback: if no category is assigned to this secretary, show 0
    $total_students = 0;
}

// Recent registrations
$recent_students = [];
$res = $conn->query("SELECT STUDENT_SERIAL_NUMBER, STUDENT_FIRST_NAME_EN, STUDENT_LAST_NAME_EN, STUDENT_FIRST_NAME_AR, STUDENT_LAST_NAME_AR FROM student ORDER BY STUDENT_SERIAL_NUMBER DESC LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) $recent_students[] = $r;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('home'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .dashboard-container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        /* welcome-header is globally styled in styles.css */

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .stat-card {
            background: var(--surface-color); border: 1px solid var(--border-color); border-radius: var(--radius-lg);
            padding: 2rem; display: flex; align-items: center; gap: 1.5rem; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .stat-icon { font-size: 3rem; background: var(--bg-tertiary); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); }
        .stat-info h3 { font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2.5rem; font-weight: 800; color: var(--text-primary); }

        .quick-actions { margin-bottom: 3rem; }
        .section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem; }
        .action-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .action-card {
            background: var(--surface-color); border: 1px solid var(--border-color); border-radius: var(--radius-md);
            padding: 1.5rem; text-decoration: none; color: inherit; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem;
        }
        .action-card:hover { border-color: var(--primary-color); background: var(--primary-light); }
        .action-card i { font-size: 2rem; color: var(--primary-color); }
        .action-card span { font-weight: 600; font-size: 1.1rem; }

        .recent-section { background: var(--surface-color); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th { text-align: left; padding: 1rem; border-bottom: 2px solid var(--bg-secondary); color: var(--text-secondary); font-weight: 600; }
        .data-table td { padding: 1rem; border-bottom: 1px solid var(--bg-secondary); }
        [dir="rtl"] .data-table th { text-align: right; }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="dashboard-container">
                <div class="welcome-header">
                    <h1><?php echo t('welcome_secretary', htmlspecialchars($secretary_name)); ?></h1>
                    <p><?php echo t('ready_to_start'); ?></p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👨‍🎓</div>
                        <div class="stat-info">
                            <h3><?php echo t('total_students'); ?></h3>
                            <div class="stat-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <!-- Add more stats here if needed -->
                </div>

                <div class="quick-actions">
                    <h2 class="section-title">⚡ <?php echo t('quick_actions'); ?></h2>
                    <div class="action-cards">
                        <a href="insert_student.php" class="action-card">
                            <i>➕</i>
                            <span><?php echo t('insert_student'); ?></span>
                        </a>
                        <a href="secretary_edit_student.php" class="action-card">
                            <i>✏️</i>
                            <span>Edit Student</span>
                        </a>
                        <a href="secretary_punishes_student.php" class="action-card">
                            <i>⚠️</i>
                            <span><?php echo t('secretary_punishes_student_title') ?: 'Assign Punishments'; ?></span>
                        </a>
                        <a href="secretary_rewards_student.php" class="action-card">
                            <i>🌟</i>
                            <span><?php echo t('secretary_rewards_student_title') ?: 'Assign Rewards'; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
