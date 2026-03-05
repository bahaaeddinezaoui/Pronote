<?php
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

$role = trim((string)($_SESSION['role'] ?? ''));
if (!isset($_SESSION['user_id']) || $role !== 'Superuser') {
    header('Location: login.php');
    exit;
}

$uploadDir = __DIR__ . '/resources/programs/';
$pdfFullPath = $uploadDir . 'weekly_program.pdf';
$metaPath = $uploadDir . 'weekly_program_meta.json';

$meta = null;
if (is_file($metaPath)) {
    $raw = @file_get_contents($metaPath);
    $decoded = $raw ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
        $meta = $decoded;
    }
}

$pdfExists = is_file($pdfFullPath);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('superuser_dashboard_title'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
        }
        .card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }
        .meta { color: var(--text-secondary); font-size: 14px; margin-top: 10px; }
        .no-data { color: var(--text-secondary); font-style: italic; padding: 15px; text-align: center; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            width: auto; /* Ensure button doesn't take full width */
        }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary {
            background: var(--surface-color);
            color: var(--primary-color);
            padding: 0.75rem 1.25rem;
            border: 1px solid var(--primary-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            width: auto; /* Ensure button doesn't take full width */
        }
        .btn-secondary:hover { background: var(--background-color); }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="container">
    <h1><?php echo t('superuser_dashboard_title'); ?></h1>

    <div class="card">
        <h2 style="margin-top:0;"><?php echo t('weekly_program_title'); ?></h2>

        <?php if ($meta): ?>
            <div class="meta">
                <?php
                    $uploadedAt = $meta['uploaded_at'] ?? '';
                    $originalName = $meta['original_name'] ?? '';
                ?>
                <?php echo t('weekly_program_last_upload'); ?>
                <?php echo htmlspecialchars($uploadedAt); ?>
                <?php if (!empty($originalName)): ?>
                    (<?php echo htmlspecialchars($originalName); ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($pdfExists): ?>
            <div class="meta"><?php echo t('weekly_program_available'); ?></div>
        <?php else: ?>
            <div class="no-data"><?php echo t('weekly_program_not_uploaded'); ?></div>
        <?php endif; ?>

        <div class="actions">
            <a class="btn-primary" href="superuser_upload_weekly_program.php"><?php echo t('weekly_program_upload_title'); ?></a>
            <a class="btn-secondary" href="admin_weekly_program.php"><?php echo t('weekly_program_title'); ?></a>
        </div>
    </div>
</div>

</div>
</div>
</div>

</body>
</html>
