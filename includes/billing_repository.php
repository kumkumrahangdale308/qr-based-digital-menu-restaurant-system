<?php

function calculate_bill_totals($subtotal)
{
    $subtotal = round((float)$subtotal, 2);
    $gst = round($subtotal * 0.05, 2);
    return [
        'subtotal' => $subtotal,
        'gst_amount' => $gst,
        'total_amount' => round($subtotal + $gst, 2)
    ];
}

function get_or_create_bill(mysqli $conn, $orderId)
{
    $existing = $conn->prepare("SELECT * FROM bills WHERE order_id = ? LIMIT 1");
    $existing->bind_param('i', $orderId);
    $existing->execute();
    $bill = $existing->get_result()->fetch_assoc();
    if ($bill) {
        return $bill;
    }

    $order = get_order_with_items($conn, $orderId);
    if (!$order) {
        return null;
    }

    $totals = calculate_bill_totals($order['total_amount']);
    $billNumber = 'BILL' . date('Ymd') . '-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
    $paymentMethod = $order['payment_method'] ?: 'Counter';

    $stmt = $conn->prepare("
        INSERT INTO bills (order_id, bill_number, subtotal, gst_amount, total_amount, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->bind_param(
        'isddds',
        $orderId,
        $billNumber,
        $totals['subtotal'],
        $totals['gst_amount'],
        $totals['total_amount'],
        $paymentMethod
    );
    $stmt->execute();

    $billId = $conn->insert_id;
    $fetch = $conn->prepare("SELECT * FROM bills WHERE id = ? LIMIT 1");
    $fetch->bind_param('i', $billId);
    $fetch->execute();
    return $fetch->get_result()->fetch_assoc();
}

function mark_bill_paid(mysqli $conn, $orderId, $paymentMethod)
{
    $bill = get_or_create_bill($conn, $orderId);
    if (!$bill) {
        return false;
    }

    $stmt = $conn->prepare("
        UPDATE bills
        SET payment_method = ?, payment_status = 'Paid', updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->bind_param('si', $paymentMethod, $orderId);
    if (!$stmt->execute()) {
        return false;
    }

    $order = $conn->prepare("
        UPDATE orders
        SET payment_method = ?, payment_status = 'Paid', updated_at = NOW()
        WHERE id = ?
    ");
    $order->bind_param('si', $paymentMethod, $orderId);
    return $order->execute();
}

?>
