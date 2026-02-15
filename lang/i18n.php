<?php
/**
 * Internationalization (i18n) for eNote – English & Arabic
 * Include after session_start(). Sets $LANG, $T, and t().
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed = ['en', 'ar'];
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, $allowed)) {
    $lang = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed)) {
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 365 * 24 * 3600, '/');
}

$LANG = $lang;
$T = [];
require __DIR__ . DIRECTORY_SEPARATOR . $lang . '.php';

if (!function_exists('t')) {
    function t($key, ...$args) {
        global $T;
        $s = $T[$key] ?? $key;
        return $args ? sprintf($s, ...$args) : $s;
    }
}

/** Current page URL with lang param (for switcher) */
function lang_url($l) {
    $uri = $_SERVER['PHP_SELF'] ?? '/';
    $qs = $_GET;
    $qs['lang'] = $l;
    return $uri . '?' . http_build_query($qs);
}
