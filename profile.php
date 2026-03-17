<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
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
        SELECT t.TEACHER_FIRST_NAME_EN, t.TEACHER_LAST_NAME_EN, t.TEACHER_FIRST_NAME_AR, t.TEACHER_LAST_NAME_AR, g.GRADE_NAME_EN, g.GRADE_NAME_AR, t.TEACHER_PHOTO
        FROM teacher t
        LEFT JOIN grade g ON t.TEACHER_GRADE_ID = g.GRADE_ID
        WHERE t.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = ($LANG === 'ar' && !empty($row['TEACHER_FIRST_NAME_AR'])) ? $row['TEACHER_FIRST_NAME_AR'] : ($row['TEACHER_FIRST_NAME_EN'] ?? '');
        $last = ($LANG === 'ar' && !empty($row['TEACHER_LAST_NAME_AR'])) ? $row['TEACHER_LAST_NAME_AR'] : ($row['TEACHER_LAST_NAME_EN'] ?? '');
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Teacher";
        $gradeName = ($LANG === 'ar' && !empty($row['GRADE_NAME_AR'])) ? $row['GRADE_NAME_AR'] : ($row['GRADE_NAME_EN'] ?? 'N/A');
        $grade = htmlspecialchars($gradeName);
        $photoData = $row['TEACHER_PHOTO'];
    }
    $stmt->close();
} elseif ($role === 'Admin') {
    $stmt = $conn->prepare("
        SELECT a.ADMINISTRATOR_FIRST_NAME_EN, a.ADMINISTRATOR_LAST_NAME_EN, a.ADMINISTRATOR_FIRST_NAME_AR, a.ADMINISTRATOR_LAST_NAME_AR, g.GRADE_NAME_EN, g.GRADE_NAME_AR, a.ADMINISTRATOR_POSITION, a.ADMINISTRATOR_PHOTO
        FROM administrator a
        LEFT JOIN grade g ON a.ADMINISTRATOR_GRADE_ID = g.GRADE_ID
        WHERE a.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = ($LANG === 'ar' && !empty($row['ADMINISTRATOR_FIRST_NAME_AR'])) ? $row['ADMINISTRATOR_FIRST_NAME_AR'] : ($row['ADMINISTRATOR_FIRST_NAME_EN'] ?? '');
        $last = ($LANG === 'ar' && !empty($row['ADMINISTRATOR_LAST_NAME_AR'])) ? $row['ADMINISTRATOR_LAST_NAME_AR'] : ($row['ADMINISTRATOR_LAST_NAME_EN'] ?? '');
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Administrator";
        $gradeName = ($LANG === 'ar' && !empty($row['GRADE_NAME_AR'])) ? $row['GRADE_NAME_AR'] : ($row['GRADE_NAME_EN'] ?? 'N/A');
        $grade = htmlspecialchars($gradeName);
        $position = htmlspecialchars($row['ADMINISTRATOR_POSITION'] ?? '');
        $photoData = $row['ADMINISTRATOR_PHOTO'];
    }
    $stmt->close();
} elseif ($role === 'Secretary') {
    $stmt = $conn->prepare("
        SELECT s.SECRETARY_FIRST_NAME_EN, s.SECRETARY_LAST_NAME_EN, s.SECRETARY_FIRST_NAME_AR, s.SECRETARY_LAST_NAME_AR, g.GRADE_NAME_EN, g.GRADE_NAME_AR, s.SECRETARY_POSITION, s.SECRETARY_PHOTO
        FROM secretary s
        LEFT JOIN grade g ON s.SECRETARY_GRADE_ID = g.GRADE_ID
        WHERE s.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = ($LANG === 'ar' && !empty($row['SECRETARY_FIRST_NAME_AR'])) ? $row['SECRETARY_FIRST_NAME_AR'] : ($row['SECRETARY_FIRST_NAME_EN'] ?? '');
        $last = ($LANG === 'ar' && !empty($row['SECRETARY_LAST_NAME_AR'])) ? $row['SECRETARY_LAST_NAME_AR'] : ($row['SECRETARY_LAST_NAME_EN'] ?? '');
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Secretary";
        $gradeName = ($LANG === 'ar' && !empty($row['GRADE_NAME_AR'])) ? $row['GRADE_NAME_AR'] : ($row['GRADE_NAME_EN'] ?? 'N/A');
        $grade = htmlspecialchars($gradeName);
        $position = htmlspecialchars($row['SECRETARY_POSITION'] ?? '');
        $photoData = $row['SECRETARY_PHOTO'];
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

$homeUrl = ($role === 'Admin') ? 'admin_dashboard.php' : (($role === 'Teacher') ? 'teacher_home.php' : (($role === 'Secretary') ? 'secretary_home.php' : 'fill_form.php'));

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('user_profile'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .profile-card {
            background: var(--glass-bg-strong);
            backdrop-filter: blur(var(--glass-blur)) saturate(160%);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(160%);
            border-radius: var(--radius-xl);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            border: 1px solid var(--glass-border);
            position: relative;
        }

        .profile-banner {
            height: 180px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #8b5cf6 100%);
            position: relative;
        }

        .profile-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url('assets/noise.png');
            opacity: 0.05;
            pointer-events: none;
        }

        .profile-header {
            padding: 0 40px;
            margin-top: -75px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 10;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            background: var(--surface-color);
            border-radius: 50%;
            padding: 6px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            border: 4px solid var(--glass-bg-strong);
        }

        .profile-avatar-inner {
            width: 100%;
            height: 100%;
            background: var(--bg-tertiary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            color: var(--primary-color);
            overflow: hidden;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.025em;
        }

        .profile-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            margin-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .profile-content {
            padding: 0 40px 50px 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            transition: var(--transition);
        }

        .info-item:hover {
            background: var(--glass-bg-strong);
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .info-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            font-size: 20px;
            opacity: 0.8;
        }

        @media (max-width: 640px) {
            .profile-header { padding: 0 20px; margin-top: -60px; }
            .profile-avatar { width: 120px; height: 120px; }
            .profile-content { padding: 0 20px 40px 20px; }
            .profile-name { font-size: 24px; }
        }
    </style>
</head>
<body>
    
    <div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-banner"></div>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="profile-avatar-inner">
                        <?php if (!empty($photoData)): ?>
                            <?php if (filter_var($photoData, FILTER_VALIDATE_URL) || file_exists(__DIR__ . '/' . $photoData)): ?>
                                <img src="<?php echo htmlspecialchars($photoData); ?>" alt="Profile Photo" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($photoData); ?>" alt="Profile Photo" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php endif; ?>
                        <?php else: ?>
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        <?php endif; ?>
                    </div>
                </div>
                <h1 class="profile-name"><?php echo $fullName; ?></h1>
                <div class="profile-role-badge"><?php echo $safeRole; ?></div>
            </div>

            <div class="profile-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><?php echo t('user_id'); ?></div>
                        <div class="info-value">
                            <span class="info-icon">🆔</span>
                            #<?php echo $userId; ?>
                        </div>
                    </div>

                    <?php if ($grade !== "N/A"): ?>
                    <div class="info-item">
                        <div class="info-label"><?php echo t('grade_level'); ?></div>
                        <div class="info-value">
                            <span class="info-icon">🎓</span>
                            <?php echo $grade; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($position !== ""): ?>
                    <div class="info-item">
                        <div class="info-label"><?php echo t('position'); ?></div>
                        <div class="info-value">
                            <span class="info-icon">💼</span>
                            <?php echo $position; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-label"><?php echo t('access_level'); ?></div>
                        <div class="info-value">
                            <span class="info-icon">🛡️</span>
                            <?php echo $safeRole; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

</div>
</div>
</div>

<script>
var T = <?php echo json_encode($T); ?>;
let newNotifications = [];


</script>

</body>
</html>

