<?php
/** Persist a new tile order (POST, called via fetch from admin.js). */
require __DIR__ . '/inc/store.php';
require_login();
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}
csrf_check();

$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}
store_reorder(array_map('strval', $ids));
echo json_encode(['ok' => true]);
