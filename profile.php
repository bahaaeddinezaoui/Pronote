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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('user_profile'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* Modern Profile Styles */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

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

