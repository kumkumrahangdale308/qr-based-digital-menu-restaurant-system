<?php

require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/order_repository.php';
require_once __DIR__ . '/db.php';

$orderId = filter_var($_GET['order_id'] ?? ($_SESSION['last_order_id'] ?? null), FILTER_VALIDATE_INT);
$tableNumber = filter_var($_GET['table'] ?? ($_SESSION['table_number'] ?? null), FILTER_VALIDATE_INT);

if ($orderId) {
    $order = get_order_with_items($conn, $orderId);
} elseif ($tableNumber) {
    $stmt = $conn->prepare("
        SELECT id
        FROM orders
        WHERE table_number = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $tableNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $order = $row ? get_order_with_items($conn, (int)$row['id']) : null;
} else {
    json_response(['success' => false, 'status' => 'No Order']);
}

if (!$order) {
    json_response(['success' => false, 'status' => 'No Order']);
}

json_response([
    'success' => true,
    'order_id' => (int)$order['id'],
    'display_order_id' => 'ORD' . (int)$order['id'],
    'table_id' => (int)$order['table_id'],
    'table_number' => (int)$order['table_number'],
    'total_amount' => number_format((float)$order['total_amount'], 2, '.', ''),
    'payment_method' => $order['payment_method'],
    'payment_status' => $order['payment_status'],
    'status' => $order['status'],
    'statuses' => order_statuses(),
    'items' => $order['items']
]);

?>
