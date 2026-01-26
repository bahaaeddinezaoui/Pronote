<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

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
        SELECT t.TEACHER_FIRST_NAME_EN, t.TEACHER_LAST_NAME_EN, g.GRADE_NAME_EN, t.TEACHER_PHOTO
        FROM teacher t
        LEFT JOIN grade g ON t.TEACHER_GRADE_ID = g.GRADE_ID
        WHERE t.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['TEACHER_FIRST_NAME_EN'] ?? '';
        $last = $row['TEACHER_LAST_NAME_EN'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Teacher";
        $grade = htmlspecialchars($row['GRADE_NAME_EN'] ?? 'N/A');
        $photoData = $row['TEACHER_PHOTO'];
    }
    $stmt->close();
} elseif ($role === 'Admin') {
    $stmt = $conn->prepare("
        SELECT a.ADMINISTRATOR_FIRST_NAME_EN, a.ADMINISTRATOR_LAST_NAME_EN, g.GRADE_NAME_EN, a.ADMINISTRATOR_POSITION, a.ADMINISTRATOR_PHOTO
        FROM administrator a
        LEFT JOIN grade g ON a.ADMINISTRATOR_GRADE_ID = g.GRADE_ID
        WHERE a.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['ADMINISTRATOR_FIRST_NAME_EN'] ?? '';
        $last = $row['ADMINISTRATOR_LAST_NAME_EN'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Administrator";
        $grade = htmlspecialchars($row['GRADE_NAME_EN'] ?? 'N/A');
        $position = htmlspecialchars($row['ADMINISTRATOR_POSITION'] ?? '');
        $photoData = $row['ADMINISTRATOR_PHOTO'];
    }
    $stmt->close();
} elseif ($role === 'Secretary') {
    $stmt = $conn->prepare("
        SELECT s.SECRETARY_FIRST_NAME_EN, s.SECRETARY_LAST_NAME_EN, g.GRADE_NAME_EN, s.SECRETARY_POSITION, s.SECRETARY_PHOTO
        FROM secretary s
        LEFT JOIN grade g ON s.SECRETARY_GRADE_ID = g.GRADE_ID
        WHERE s.USER_ID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first = $row['SECRETARY_FIRST_NAME_EN'] ?? '';
        $last = $row['SECRETARY_LAST_NAME_EN'] ?? '';
        $fullName = trim(htmlspecialchars($first . ' ' . $last)) ?: "Secretary";
        $grade = htmlspecialchars($row['GRADE_NAME_EN'] ?? 'N/A');
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('user_profile'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* Modern Profile Styles */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
        }

        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .profile-card {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .profile-banner {
            height: 160px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            position: relative;
        }

        .profile-header {
            padding: 0 2rem;
            margin-top: -60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 10;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: var(--surface-color);
            border-radius: 50%;
            padding: 4px;
            box-shadow: var(--shadow-md);
            margin-bottom: 1rem;
        }

        .profile-avatar-inner {
            width: 100%;
            height: 100%;
            background: var(--bg-tertiary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
        }

        .profile-role-badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .profile-content {
            padding: 0 2rem 2rem 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-card {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary-light);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .info-text label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-text div {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .profile-header { padding: 0 1rem; }
            .profile-content { padding: 0 1rem 2rem 1rem; }
            .info-grid { grid-template-columns: 1fr; }
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
                            <label><?php echo t('user_id'); ?></label>
                            <div>#<?php echo $userId; ?></div>
                        </div>
                    </div>

                    <?php if ($grade !== "N/A"): ?>
                    <div class="info-card">
                        <div class="info-icon">🎓</div>
                        <div class="info-text">
                            <label><?php echo t('grade_level'); ?></label>
                            <div><?php echo $grade; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($position !== ""): ?>
                    <div class="info-card">
                        <div class="info-icon">💼</div>
                        <div class="info-text">
                            <label><?php echo t('position'); ?></label>
                            <div><?php echo $position; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <div class="info-icon">🛡️</div>
                        <div class="info-text">
                            <label><?php echo t('access_level'); ?></label>
                            <div><?php echo $safeRole; ?> <?php echo t('privileges_suffix'); ?></div>
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

