<?php

function get_order_with_items(mysqli $conn, $orderId)
{
    $stmt = $conn->prepare("
        SELECT o.id, o.table_id, o.table_number, o.total_amount, o.payment_method,
               o.payment_status, o.status, o.order_time, o.start_time,
               rt.status table_status
        FROM orders o
        LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
        WHERE o.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        return null;
    }

    $itemStmt = $conn->prepare("
        SELECT item_name, quantity, price, category
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");
    $itemStmt->bind_param('i', $orderId);
    $itemStmt->execute();
    $order['items'] = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return $order;
}

function update_order_status(mysqli $conn, $orderId, $nextStatus, $actor = 'system')
{
    $allowed = [
        'New' => ['Accepted'],
        'Accepted' => ['Preparing'],
        'Preparing' => ['Ready'],
        'Ready' => ['Served'],
        'Served' => ['Completed'],
        'Completed' => []
    ];

    if (!in_array($nextStatus, order_statuses(), true)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return false;
    }

    $currentStatus = $row['status'];
    if ($currentStatus !== $nextStatus && !in_array($nextStatus, $allowed[$currentStatus] ?? [], true)) {
        return false;
    }

    $update = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $update->bind_param('si', $nextStatus, $orderId);
    if (!$update->execute()) {
        return false;
    }

    if (in_array($nextStatus, ['Served', 'Completed'], true)) {
        $action = $actor . ': status changed to ' . $nextStatus;
        $log = $conn->prepare("INSERT INTO waiter_actions (order_id, action) VALUES (?, ?)");
        if ($log) {
            $log->bind_param('is', $orderId, $action);
            $log->execute();
        }
    }

    if ($nextStatus === 'Completed') {
        $table = $conn->prepare("SELECT table_id FROM orders WHERE id = ? LIMIT 1");
        $table->bind_param('i', $orderId);
        $table->execute();
        $tableRow = $table->get_result()->fetch_assoc();
        if (!empty($tableRow['table_id'])) {
            $clear = $conn->prepare("
                UPDATE restaurant_tables
                SET status = 'AVAILABLE'
                WHERE id = ? AND status = 'OCCUPIED'
            ");
            $clear->bind_param('i', $tableRow['table_id']);
            $clear->execute();
        }
    }

    return true;
}

function order_status_rank($status)
{
    $statuses = order_statuses();
    $index = array_search($status, $statuses, true);
    return $index === false ? -1 : $index;
}

?>
