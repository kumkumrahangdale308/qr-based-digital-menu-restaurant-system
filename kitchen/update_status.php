<?php

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/order_repository.php';
require_once __DIR__ . '/../api/db.php';
require_staff_area('kitchen');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('invalid');
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$status = trim((string)($_POST['status'] ?? ''));

if (!in_array($status, kitchen_statuses(), true)) {
    http_response_code(400);
    exit('invalid');
}

if (!$orderId || !csrf_valid($_POST['csrf_token'] ?? '') || !update_order_status($conn, $orderId, $status, 'kitchen')) {
    http_response_code(400);
    exit('failed');
}

echo 'success';

?>
