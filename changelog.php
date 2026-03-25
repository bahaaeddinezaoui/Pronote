<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';
$role = $_SESSION['role'] ?? '';

// DB Connection
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all changelogs sort by release date desc
$res = $conn->query("SELECT * FROM changelog ORDER BY RELEASE_DATE DESC, CHANGELOG_ID DESC");
$changelogs = [];
while ($row = $res->fetch_assoc()) {
    $changelogs[] = $row;
}

// Function to render items
function renderChangelogLines($text) {
    if (empty(trim($text))) return '';
    $lines = explode("\n", $text);
    $html = '<ul class="cl-list">';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $html .= '<li>' . htmlspecialchars($line) . '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'ar') ? 'سجل التحديثات' : 'System Updates'; ?> - <?php echo t('app_name'); ?></title>
    <link rel="stylesheet" href="styles.css">
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <style>
        .cl-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .cl-timeline {
            position: relative;
            padding: 20px 0;
            margin-top: 20px;
        }

        .cl-timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-color);
            opacity: 0.15;
            transform: none;
        }

        [dir="rtl"] .cl-timeline::before {
            left: auto;
            right: 30px;
        }

        .cl-item {
            position: relative;
            margin-bottom: 40px;
            width: 100%;
            display: flex;
            justify-content: flex-start;
            padding-inline-start: 60px;
        }

        .cl-dot {
            position: absolute;
            left: 30px;
            top: 25px;
            width: 14px;
            height: 14px;
            background: var(--primary-color);
            border: 3px solid var(--bg-primary);
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 5;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
        }

        [dir="rtl"] .cl-dot {
            left: auto;
            right: 30px;
            transform: translateX(50%);
        }

        .cl-card {
            width: 100%;
            background: var(--glass-bg-strong);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            transition: all 0.3s ease;
        }

        .cl-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .cl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cl-version {
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .cl-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .cl-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
            width: 100%;
        }

        .cl-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cl-list li {
            position: relative;
            padding-inline-start: 22px;
            margin-bottom: 10px;
            color: var(--text-secondary);
            line-height: 1.5;
            font-size: 1rem;
        }

        .cl-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        [dir="rtl"] .cl-list li::before {
            left: auto;
            right: 0;
        }

        @media (max-width: 900px) {
            .cl-card {
                padding: 20px;
            }
            .cl-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="cl-container">
                <div class="welcome-section">
                    <div class="header-breadcrumb" style="margin-bottom: 12px; opacity: 0.7; font-size: 0.85rem;">
                        <span><?php echo t('nav_home'); ?></span>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?php echo ($lang === 'ar') ? 'سجل التحديثات' : 'System Updates'; ?></span>
                    </div>
                    <h1><?php echo ($lang === 'ar') ? 'سجل التحديثات' : 'System Updates'; ?></h1>
                    <p><?php echo ($lang === 'ar') ? 'تعرف على آخر التغييرات والميزات الجديدة التي تمت إضافتها' : 'Stay up to date with the latest improvements and new features'; ?></p>
                </div>

                <div class="cl-timeline">
                    <?php if (empty($changelogs)): ?>
                        <div class="card" style="text-align: center; padding: 60px;">
                            <span style="font-size: 4rem; display: block; margin-bottom: 20px;">📜</span>
                            <h3><?php echo ($lang === 'ar') ? 'لا توجد تحديثات حالياً' : 'No updates found'; ?></h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($changelogs as $cl): ?>
                            <div class="cl-item">
                                <div class="cl-dot"></div>
                                <div class="cl-card">
                                    <div class="cl-header">
                                        <div class="cl-version">v<?php echo htmlspecialchars($cl['VERSION']); ?></div>
                                        <div class="cl-date">📅 <?php echo htmlspecialchars($cl['RELEASE_DATE']); ?></div>
                                    </div>
                                    <h2 class="cl-title"><?php echo htmlspecialchars(($lang === 'ar') ? $cl['TITLE_AR'] : $cl['TITLE_EN']); ?></h2>
                                    <div class="cl-body" style="margin-top: 15px;">
                                        <?php echo renderChangelogLines(($lang === 'ar') ? $cl['CONTENT_AR'] : $cl['CONTENT_EN']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
