<?php
/** Delete a tile and its image files (POST). */
require __DIR__ . '/inc/store.php';
require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /admin/');
    exit;
}
csrf_check();

$id = (string) ($_POST['id'] ?? '');
if ($id !== '') {
    store_delete($id);
}
header('Location: /admin/?ok=1');
exit;
