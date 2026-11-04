<?php
declare(strict_types=1);
require __DIR__ . '/app/core.php';

if (!$GLOBALS['config']) {
    header('Location: ' . base_path() . 'install/');
    exit;
}

$secure = str_starts_with(base_url(), 'https');
session_name('diario_sid');
session_set_cookie_params(['lifetime' => 0, 'path' => base_path(), 'httponly' => true, 'secure' => $secure, 'samesite' => 'Lax']);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$route = (string) ($_GET['r'] ?? 'painel');
if (!preg_match('#^[a-z_]+(/[a-z_]+)?$#', $route)) not_found();
if (!str_contains($route, '/')) $route .= '/index';
$file = ROOT . '/app/pages/' . $route . '.php';

$public = ['auth/login', 'auth/google'];
if (!in_array($route, $public, true) && !user()) redirect('auth/login');
if (!is_file($file)) not_found();

if (isset($_GET['ano_id']) && user()) {
    $_SESSION['ano_id'] = (int) $_GET['ano_id'];
    unset($_GET['ano_id']);
    redirect(substr($route, -6) === '/index' ? substr($route, 0, -6) : $route, array_diff_key($_GET, ['r' => 1]));
}
if (is_post() && $route !== 'auth/google') csrf_check();

$title = '';
$tpl = 'layout';
ob_start();
require $file;
$content = ob_get_clean();
layout($content, $tpl);
