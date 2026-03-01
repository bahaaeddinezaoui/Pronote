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
$pdfFileName = 'weekly_program.pdf';
$pdfFullPath = $uploadDir . $pdfFileName;
$metaPath = $uploadDir . 'weekly_program_meta.json';

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    if (!isset($_FILES['weekly_program_pdf']) || $_FILES['weekly_program_pdf']['error'] !== UPLOAD_ERR_OK) {
        $message = t('weekly_program_upload_failed');
        $msg_type = 'error';
    } else {
        $tmp = $_FILES['weekly_program_pdf']['tmp_name'];
        $originalName = $_FILES['weekly_program_pdf']['name'] ?? '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $message = t('weekly_program_invalid_file');
            $msg_type = 'error';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            $allowedMimes = ['application/pdf', 'application/x-pdf'];

            $maxBytes = 10 * 1024 * 1024;
            $size = (int)($_FILES['weekly_program_pdf']['size'] ?? 0);

            if (!in_array($mime, $allowedMimes, true)) {
                $message = t('weekly_program_invalid_file');
                $msg_type = 'error';
            } elseif ($size <= 0 || $size > $maxBytes) {
                $message = t('weekly_program_invalid_size');
                $msg_type = 'error';
            } else {
                if (move_uploaded_file($tmp, $pdfFullPath)) {
                    $meta = [
                        'uploaded_at' => date('c'),
                        'uploaded_by_user_id' => (int)($_SESSION['user_id'] ?? 0),
                        'original_name' => $originalName,
                    ];
                    @file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                    $message = t('weekly_program_uploaded_success');
                    $msg_type = 'success';
                } else {
                    $message = t('weekly_program_upload_failed');
                    $msg_type = 'error';
                }
            }
        }
    }
}

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
    <title><?php echo t('weekly_program_upload_title'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        .container {
            max-width: 1000px;
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
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .form-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        input[type="file"] { padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 700;
        }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .meta { color: #6b7280; font-size: 14px; margin-top: 10px; }
        .pdf-box { margin-top: 20px; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .pdf-box iframe { width: 100%; height: 600px; border: 0; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="container">
    <h1><?php echo t('weekly_program_upload_title'); ?></h1>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <input type="file" name="weekly_program_pdf" accept="application/pdf" required>
                <button type="submit" class="btn-primary"><?php echo t('weekly_program_upload_btn'); ?></button>
            </div>
            <div class="meta"><?php echo t('weekly_program_upload_hint'); ?></div>
        </form>

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
                <iframe src="admin_weekly_program_file.php#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="600px" style="border: none;"></iframe>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
</div>

</body>
</html>
