<?php

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../api/db.php';
require_staff_area('kitchen');

header('Content-Type: application/json; charset=utf-8');

$sql = "
    SELECT o.id, o.table_number, o.total_amount, o.status, o.order_time, o.start_time,
           GROUP_CONCAT(CONCAT(oi.item_name, ' x ', oi.quantity, ' [', IFNULL(oi.category, ''), ']') ORDER BY oi.id SEPARATOR '||') items,
           SUM(CASE WHEN oi.category = 'Veg' THEN oi.quantity ELSE 0 END) veg_qty,
           SUM(CASE WHEN oi.category = 'NonVeg' THEN oi.quantity ELSE 0 END) nonveg_qty
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status IN ('New','Accepted','Preparing','Ready')
    GROUP BY o.id
    ORDER BY FIELD(o.status, 'New','Accepted','Preparing','Ready'), o.id ASC
";

$result = $conn->query($sql);
$orders = [];

while ($row = $result->fetch_assoc()) {
    $items = [];
    if (!empty($row['items'])) {
        foreach (explode('||', $row['items']) as $item) {
            $items[] = $item;
        }
    }

    $orders[] = [
        'id' => (int)$row['id'],
        'table_number' => (int)$row['table_number'],
        'total_amount' => (float)$row['total_amount'],
        'status' => $row['status'],
        'order_time' => $row['order_time'],
        'start_time' => $row['start_time'],
        'items' => $items,
        'veg_qty' => (int)$row['veg_qty'],
        'nonveg_qty' => (int)$row['nonveg_qty']
    ];
}

echo json_encode($orders);

?>
