<?php
/** Sign out (POST with CSRF). */
require __DIR__ . '/inc/auth.php';
admin_session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /admin/login.php');
exit;
