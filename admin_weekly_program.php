<?php
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

$role = trim((string)($_SESSION['role'] ?? ''));
if (!isset($_SESSION['user_id']) || ($role !== 'Admin' && $role !== 'Superuser')) {
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('weekly_program_title'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
        }
        .card {
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }
        .meta { color: #6b7280; font-size: 14px; margin-top: 10px; }
        .pdf-box { margin-top: 20px; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .pdf-box iframe { width: 100%; height: 750px; border: 0; }
        .no-data { color: #888; font-style: italic; padding: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="container">
    <h1><?php echo t('weekly_program_title'); ?></h1>

    <div class="card">
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
            <div class="pdf-box">
                <iframe src="admin_weekly_program_file.php#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="750px" style="border: none;"></iframe>
            </div>
        <?php else: ?>
            <div class="no-data"><?php echo t('weekly_program_not_uploaded'); ?></div>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
</div>

</body>
</html>
