<?php

require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/table_repository.php';
require_once __DIR__ . '/../includes/waiter_repository.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$requestType = trim((string)($payload['request_type'] ?? ''));
$table = current_customer_table($conn);

if (!$table) {
    json_response(['success' => false, 'message' => 'Table session expired. Please scan the table QR again.'], 400);
}

if (!create_waiter_request($conn, (int)$table['id'], $requestType)) {
    json_response(['success' => false, 'message' => 'Could not send waiter request.'], 400);
}

if ($requestType === 'Need Bill' && !empty($_SESSION['last_order_id'])) {
    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'Requested', updated_at = NOW()
        WHERE id = ?
    ");
    $orderId = (int)$_SESSION['last_order_id'];
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
}

json_response(['success' => true, 'message' => $requestType . ' request sent to waiter.']);

?>
