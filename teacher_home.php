<?php
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role === 'Teacher') {
    $servername = "localhost";
    $username_db = "root";
    $password_db = "08212001";
    $dbname = "edutrack";

    $conn_guard = new mysqli($servername, $username_db, $password_db, $dbname);
    if (!$conn_guard->connect_error) {
        $user_id_guard = (int)$_SESSION['user_id'];
        $needs_onboarding_guard = !empty($_SESSION['needs_onboarding']);
        $has_logged_in_before = !empty($_SESSION['last_login_at']);

        if (!$needs_onboarding_guard && !$has_logged_in_before) {
            $stmt_teacher_guard = $conn_guard->prepare("SELECT TEACHER_SERIAL_NUMBER FROM teacher WHERE USER_ID = ?");
            if ($stmt_teacher_guard) {
                $stmt_teacher_guard->bind_param('i', $user_id_guard);
                $stmt_teacher_guard->execute();
                $res_teacher_guard = $stmt_teacher_guard->get_result();
                $row_teacher_guard = $res_teacher_guard ? $res_teacher_guard->fetch_assoc() : null;
                $stmt_teacher_guard->close();

                if (!$row_teacher_guard || empty($row_teacher_guard['TEACHER_SERIAL_NUMBER'])) {
                    $needs_onboarding_guard = true;
                } else {
                    $teacher_serial_guard = $row_teacher_guard['TEACHER_SERIAL_NUMBER'];
                    $stmt_has_guard = $conn_guard->prepare("SELECT 1 FROM TEACHES WHERE TEACHER_SERIAL_NUMBER = ? LIMIT 1");
                    if ($stmt_has_guard) {
                        $stmt_has_guard->bind_param('s', $teacher_serial_guard);
                        $stmt_has_guard->execute();
                        $res_has_guard = $stmt_has_guard->get_result();
                        $needs_onboarding_guard = (!$res_has_guard || $res_has_guard->num_rows === 0);
                        $stmt_has_guard->close();
                    }
                }
            }
        }

        if ($needs_onboarding_guard) {
            $_SESSION['needs_onboarding'] = true;
            $conn_guard->close();
            header('Location: teacher_onboarding.php');
            exit;
        }
        $conn_guard->close();
    }
}

// Check role
if ($role !== 'Teacher') {
    // If not teacher, maybe redirect to their respective home? 
    // For now, just allow if they have a session, but typically we'd restrict.
}

$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Teacher Info
$user_id = $_SESSION['user_id'];
$teacher_first = "";
$teacher_last = "";
$teacher_serial = "";

$stmt = $conn->prepare("
    SELECT TEACHER_FIRST_NAME_EN, TEACHER_LAST_NAME_EN, TEACHER_FIRST_NAME_AR, TEACHER_LAST_NAME_AR, TEACHER_SERIAL_NUMBER 
    FROM teacher 
    WHERE USER_ID = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $teacher_first = ($LANG === 'ar' && !empty($row['TEACHER_FIRST_NAME_AR'])) ? $row['TEACHER_FIRST_NAME_AR'] : $row['TEACHER_FIRST_NAME_EN'];
    $teacher_last = ($LANG === 'ar' && !empty($row['TEACHER_LAST_NAME_AR'])) ? $row['TEACHER_LAST_NAME_AR'] : $row['TEACHER_LAST_NAME_EN'];
    $teacher_serial = $row['TEACHER_SERIAL_NUMBER'];
}
$stmt->close();

$current_date = date('Y-m-d');
$display_date = date('l, F j, Y');

// Fetch simple stats or info
// 1. Count of observations made by this teacher
$obs_count = 0;
if ($teacher_serial) {
    $stmt_obs = $conn->prepare("SELECT COUNT(*) as cnt FROM teacher_makes_an_observation_for_a_student WHERE TEACHER_SERIAL_NUMBER = ?");
    $stmt_obs->bind_param("s", $teacher_serial);
    $stmt_obs->execute();
    $res_obs = $stmt_obs->get_result();
    if ($r = $res_obs->fetch_assoc()) {
        $obs_count = $r['cnt'];
    }
    $stmt_obs->close();
}

// 2. Get assigned classes/majors
$assigned_classes = [];
if ($teacher_serial) {
    $stmt_classes = $conn->prepare("
        SELECT DISTINCT M.MAJOR_NAME_EN, M.MAJOR_NAME_AR
        FROM TEACHES TH
        JOIN MAJOR M ON TH.MAJOR_ID = M.MAJOR_ID
        WHERE TH.TEACHER_SERIAL_NUMBER = ?
    ");
    $stmt_classes->bind_param("s", $teacher_serial);
    $stmt_classes->execute();
    $result_classes = $stmt_classes->get_result();
    while ($r = $result_classes->fetch_assoc()) {
        $majorName = ($LANG === 'ar' && !empty($r['MAJOR_NAME_AR'])) ? $r['MAJOR_NAME_AR'] : $r['MAJOR_NAME_EN'];
        $assigned_classes[] = $majorName;
    }
    $stmt_classes->close();
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
        .home-container {
            max-width: 1000px;
            margin: 2rem auto;
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        .hero-section {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        .hero-content {
            position: relative;
            z-index: 1;
        }
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        .hero-section p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .hero-date {
            margin-top: 1.5rem;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }
        
        .info-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        .info-card h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .action-chip {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="home-container">
            <!-- Hero Welcome -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1><?php echo t('welcome_back', htmlspecialchars($teacher_last)); ?></h1>
                    <p><?php echo t('ready_to_manage'); ?></p>
                    <div class="hero-date">
                        📅 <?php echo $display_date; ?>
                    </div>
                </div>
            </div>

            <!-- Stats / Info -->
            <div class="info-card">
                <div>
                    <h3><?php echo t('total_observations'); ?></h3>
                    <div class="stat-value"><?php echo $obs_count; ?></div>
                    <div class="stat-label"><?php echo t('recorded_observations'); ?></div>
                </div>
                <div style="margin-top: 2rem;">
                     <a href="fill_form.php?tab=observations" style="text-decoration:none; color:var(--primary-color); font-weight:600;"><?php echo t('record_new'); ?></a>
                </div>
            </div>

            <div class="info-card">
                <div>
                    <h3><?php echo t('your_majors'); ?></h3>
                    <div style="margin-top:0.5rem;">
                        <?php if (count($assigned_classes) > 0): ?>
                            <div class="quick-actions">
                                <?php foreach($assigned_classes as $major): ?>
                                    <span class="action-chip"><?php echo htmlspecialchars($major); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:var(--text-secondary); font-style:italic;"><?php echo t('no_majors_assigned'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="margin-top: 2rem;">
                    <a href="fill_form.php?tab=absences" style="text-decoration:none; color:var(--primary-color); font-weight:600;"><?php echo t('start_attendance'); ?></a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
