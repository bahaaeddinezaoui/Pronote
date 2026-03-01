<?php
session_start();
date_default_timezone_set('Africa/Algiers');

$role = trim((string)($_SESSION['role'] ?? ''));
if (!isset($_SESSION['user_id']) || ($role !== 'Admin' && $role !== 'Superuser')) {
    http_response_code(403);
    exit;
}

$pdfPath = __DIR__ . '/resources/programs/weekly_program.pdf';
if (!is_file($pdfPath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('X-Download-Options: noopen');

@ini_set('zlib.output_compression', 'Off');
while (ob_get_level() > 0) {
    ob_end_clean();
}

$size = filesize($pdfPath);
if ($size === false) {
    http_response_code(500);
    exit;
}

header('Accept-Ranges: bytes');

$start = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=\s*(\d*)-(\d*)/i', $_SERVER['HTTP_RANGE'], $m)) {
    $start = ($m[1] !== '') ? (int)$m[1] : 0;
    $end = ($m[2] !== '') ? (int)$m[2] : $end;

    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }

    if ($end >= $size) {
        $end = $size - 1;
    }

    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

$length = $end - $start + 1;
header('Content-Length: ' . $length);

$fp = fopen($pdfPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit;
}

fseek($fp, $start);

$chunkSize = 8192;
while (!feof($fp) && $length > 0) {
    $read = ($length > $chunkSize) ? $chunkSize : $length;
    $buffer = fread($fp, $read);
    if ($buffer === false) {
        break;
    }
    echo $buffer;
    flush();
    $length -= strlen($buffer);
}

fclose($fp);
exit;
