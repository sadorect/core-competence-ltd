<?php
/** Update a tile's caption / alt / category (POST). */
require __DIR__ . '/inc/store.php';
require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /admin/');
    exit;
}
csrf_check();

$id = (string) ($_POST['id'] ?? '');
if ($id !== '') {
    store_update(
        $id,
        (string) ($_POST['category'] ?? ''),
        (string) ($_POST['caption'] ?? ''),
        (string) ($_POST['alt'] ?? '')
    );
}
header('Location: /admin/?ok=1');
exit;
