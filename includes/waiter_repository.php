<?php

function create_waiter_request(mysqli $conn, $tableId, $requestType)
{
    if (!in_array($requestType, waiter_request_types(), true)) {
        return false;
    }

    $stmt = $conn->prepare("
        INSERT INTO waiter_requests (table_id, request_type, status)
        VALUES (?, ?, 'Open')
    ");
    $stmt->bind_param('is', $tableId, $requestType);
    return $stmt->execute();
}

function update_waiter_request(mysqli $conn, $requestId, $status)
{
    if (!in_array($status, ['Accepted', 'Completed', 'Cancelled'], true)) {
        return false;
    }

    $stmt = $conn->prepare("
        UPDATE waiter_requests
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('si', $status, $requestId);
    return $stmt->execute();
}

?>
