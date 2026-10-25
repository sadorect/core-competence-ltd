<?php
/** Handle a gallery image upload (POST). */
require __DIR__ . '/inc/store.php';
require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /admin/');
    exit;
}
csrf_check();

[$ok, $res] = store_upload(
    $_FILES['image'] ?? [],
    (string) ($_POST['category'] ?? ''),
    (string) ($_POST['caption'] ?? ''),
    (string) ($_POST['alt'] ?? '')
);

$q = $ok ? 'ok=1' : 'err=' . rawurlencode(is_string($res) ? $res : 'Upload failed.');
header('Location: /admin/?' . $q);
exit;
