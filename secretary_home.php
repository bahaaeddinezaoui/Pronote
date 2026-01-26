<?php
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
$password_db = "";
$dbname = "test_class_edition";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Fetch Secretary Name
$user_id = $_SESSION['user_id'];
$secretary_name = "Secretary";
$stmt = $conn->prepare("SELECT SECRETARY_FIRST_NAME_EN, SECRETARY_LAST_NAME_EN, SECRETARY_FIRST_NAME_AR, SECRETARY_LAST_NAME_AR FROM secretary WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($LANG === 'ar' && !empty($row['SECRETARY_FIRST_NAME_AR'])) {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_AR'] . ' ' . $row['SECRETARY_LAST_NAME_AR']);
    } else {
        $secretary_name = trim($row['SECRETARY_FIRST_NAME_EN'] . ' ' . $row['SECRETARY_LAST_NAME_EN']);
    }
}
$stmt->close();

// 4. Statistics
$total_students = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM student");
if ($res) {
    $row = $res->fetch_assoc();
    $total_students = $row['count'];
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('home'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .dashboard-container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white; border-radius: var(--radius-xl); padding: 3rem 2rem;
            margin-bottom: 2.5rem; box-shadow: var(--shadow-lg);
        }
        .welcome-header h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; }
        .welcome-header p { font-size: 1.1rem; opacity: 0.9; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .stat-card {
            background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg);
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
            background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md);
            padding: 1.5rem; text-decoration: none; color: inherit; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem;
        }
        .action-card:hover { border-color: var(--primary-color); background: var(--primary-light); }
        .action-card i { font-size: 2rem; color: var(--primary-color); }
        .action-card span { font-weight: 600; font-size: 1.1rem; }

        .recent-section { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; }
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
                        <!-- Add more actions here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
