<?php

function get_table_by_number(mysqli $conn, $tableNumber)
{
    $stmt = $conn->prepare("
        SELECT id, table_number, capacity, qr_code, status, created_at, updated_at
        FROM restaurant_tables
        WHERE table_number = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $tableNumber);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_table_by_id(mysqli $conn, $tableId)
{
    $stmt = $conn->prepare("
        SELECT id, table_number, capacity, qr_code, status, created_at, updated_at
        FROM restaurant_tables
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $tableId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function set_customer_table(mysqli $conn, $tableNumber)
{
    $table = get_table_by_number($conn, $tableNumber);
    if (!$table || $table['status'] === 'OUT_OF_SERVICE') {
        return null;
    }

    $_SESSION['table_id'] = (int)$table['id'];
    $_SESSION['table_number'] = (int)$table['table_number'];
    return $table;
}

function current_customer_table(mysqli $conn)
{
    if (empty($_SESSION['table_id'])) {
        return null;
    }

    return get_table_by_id($conn, (int)$_SESSION['table_id']);
}

function mark_table_occupied(mysqli $conn, $tableId)
{
    $stmt = $conn->prepare("
        UPDATE restaurant_tables
        SET status = 'OCCUPIED'
        WHERE id = ? AND status IN ('AVAILABLE','RESERVED','OCCUPIED')
    ");
    $stmt->bind_param('i', $tableId);
    return $stmt->execute();
}

function table_qr_url($table)
{
    return app_base_url() . '/menu.php?table=' . rawurlencode((string)$table['table_number']);
}

?>
