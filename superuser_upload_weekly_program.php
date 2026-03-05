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
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
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
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }
        .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; }
        .alert.error { background: var(--bg-error); color: var(--text-error); border: 1px solid #fecaca; }
        .alert.success { background: var(--bg-success); color: var(--text-success); border: 1px solid #bbf7d0; }
        
        .upload-section {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border: 1px dashed var(--border-color);
        }
        .form-row { 
            display: flex; 
            gap: 1rem; 
            align-items: center; 
            flex-wrap: wrap; 
        }
        .file-input-wrapper {
            flex: 1;
            min-width: 250px;
        }
        input[type="file"] { 
            width: 100%;
            padding: 0.5rem; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-md); 
            background: var(--surface-color); 
            font-size: 0.875rem;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            white-space: nowrap;
        }
        .btn-primary:hover { 
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .meta { color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.75rem; }
        .last-upload-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .pdf-box { 
            margin-top: 2rem; 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-lg); 
            overflow: hidden; 
            box-shadow: var(--shadow-sm);
        }
        .pdf-box iframe { width: 100%; height: 700px; border: 0; }
        
        h1 { font-size: 1.875rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; }
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
        <?php if ($meta): ?>
            <div class="last-upload-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>
                    <?php echo t('weekly_program_last_upload'); ?>
                    <strong><?php echo htmlspecialchars($meta['uploaded_at'] ?? ''); ?></strong>
                    <?php if (!empty($meta['original_name'])): ?>
                        (<?php echo htmlspecialchars($meta['original_name']); ?>)
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($pdfExists): ?>
            <div class="pdf-box" style="margin-bottom: 2rem;">
                <iframe src="admin_weekly_program_file.php#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="700px" style="border: none;"></iframe>
            </div>
        <?php endif; ?>

        <div class="upload-section">
            <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">
                <?php echo t('upload_new_program_question'); ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="file-input-wrapper">
                        <input type="file" name="weekly_program_pdf" accept="application/pdf" required>
                    </div>
                    <button type="submit" class="btn-primary">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <?php echo t('weekly_program_upload_btn'); ?>
                        </span>
                    </button>
                </div>
                <div class="meta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <?php echo t('weekly_program_upload_hint'); ?>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
</div>
</div>

</body>
</html>
