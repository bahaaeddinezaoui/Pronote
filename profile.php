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
</body>
</html>

