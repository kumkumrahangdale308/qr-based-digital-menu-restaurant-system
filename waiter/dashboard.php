<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/order_repository.php';
require_once __DIR__ . '/../includes/waiter_repository.php';
require_once __DIR__ . '/../includes/billing_repository.php';
require_once __DIR__ . '/../api/db.php';
require_staff_area('waiter');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'serve_order') {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        $message = ($orderId && update_order_status($conn, $orderId, 'Served', 'waiter')) ? 'Order marked served.' : 'Could not serve order.';
    } elseif ($action === 'complete_request') {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['status'] === 'Accepted' ? 'Accepted' : 'Completed';
        $message = ($requestId && update_waiter_request($conn, $requestId, $status)) ? 'Request updated.' : 'Could not update request.';
    } elseif ($action === 'mark_paid') {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        $payment = trim((string)($_POST['payment_method'] ?? 'Cash'));
        if ($orderId && mark_bill_paid($conn, $orderId, $payment) && update_order_status($conn, $orderId, 'Completed', 'billing')) {
            $message = 'Bill paid and order completed.';
        } else {
            $message = 'Could not complete bill.';
        }
    }
}

$ready = $conn->query("
    SELECT o.id, o.table_number, o.total_amount, o.status, o.order_time,
           GROUP_CONCAT(CONCAT(oi.item_name, ' x ', oi.quantity) ORDER BY oi.id SEPARATOR ', ') items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status = 'Ready'
    GROUP BY o.id
    ORDER BY o.id ASC
");

$requests = $conn->query("
    SELECT wr.id, wr.request_type, wr.status, wr.created_at, rt.table_number
    FROM waiter_requests wr
    JOIN restaurant_tables rt ON rt.id = wr.table_id
    WHERE wr.status IN ('Open','Accepted')
    ORDER BY FIELD(wr.status, 'Open','Accepted'), wr.id ASC
");

$bills = $conn->query("
    SELECT o.id, o.table_number, o.total_amount, o.payment_method, o.payment_status, o.status
    FROM orders o
    WHERE o.status = 'Served' OR o.payment_status = 'Requested'
    ORDER BY o.id ASC
");

$tables = $conn->query("
    SELECT table_number, capacity, status
    FROM restaurant_tables
    ORDER BY table_number ASC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Waiter Dashboard</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f6f1e9;color:#25150e}.top{background:#2a1208;color:#fff;padding:16px 22px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.brand{font-size:26px;font-weight:900}.nav a{color:#ffe9c7;text-decoration:none;font-weight:800;margin-left:12px}.wrap{padding:20px;max-width:1320px;margin:auto}.grid{display:grid;grid-template-columns:1.2fr 1fr;gap:18px}.panel{background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:16px;box-shadow:0 8px 20px rgba(86,45,17,.08);margin-bottom:18px}.card{border:1px solid #ead7c3;border-radius:8px;padding:14px;margin:10px 0}.table{font-size:22px;font-weight:900;color:#a32017}.muted{color:#6d5e52}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:10px 12px;font-weight:900;cursor:pointer}.btn.gold{background:#f7b733;color:#2a1208}.msg{background:#fff4cf;border:1px solid #f0d579;padding:10px;border-radius:8px;margin-bottom:14px}.table-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px}.tile{border-radius:8px;padding:12px;text-align:center;font-weight:900;background:#f5eadc}.OCCUPIED{background:#ffe1dc}.AVAILABLE{background:#e9f8ed}.RESERVED{background:#fff4cf}.OUT_OF_SERVICE{background:#e5e7eb}@media(max-width:900px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="top"><div class="brand">Waiter Dashboard</div><nav class="nav"><a href="../admin/dashboard.php">Admin</a><a href="../kitchen/dashboard.php">Kitchen</a></nav></header>
<main class="wrap">
<?php if ($message): ?><div class="msg"><?php echo e($message); ?></div><?php endif; ?>
<section class="grid">
<div>
<section class="panel">
<h2>Ready Orders</h2>
<?php while ($row = $ready->fetch_assoc()): ?>
<article class="card"><div class="table">Table <?php echo (int)$row['table_number']; ?> · ORD<?php echo (int)$row['id']; ?></div><p><?php echo e($row['items'] ?: 'No items'); ?></p><p class="muted">Rs. <?php echo e($row['total_amount']); ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="action" value="serve_order"><input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><button class="btn">Served</button></form></article>
<?php endwhile; ?>
</section>
<section class="panel">
<h2>Pending Bills</h2>
<?php while ($row = $bills->fetch_assoc()): ?>
<article class="card"><div class="table">Table <?php echo (int)$row['table_number']; ?> · ORD<?php echo (int)$row['id']; ?></div><p>Status: <?php echo e($row['status']); ?> · Payment: <?php echo e($row['payment_status']); ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><select name="payment_method"><option>Cash</option><option>UPI</option><option>Card</option><option>Counter</option></select> <button class="btn gold">Mark Paid</button> <a class="btn" href="../payment.php?order_id=<?php echo (int)$row['id']; ?>">Bill</a></form></article>
<?php endwhile; ?>
</section>
</div>
<div>
<section class="panel">
<h2>Customer Requests</h2>
<?php while ($row = $requests->fetch_assoc()): ?>
<article class="card"><div class="table">Table <?php echo (int)$row['table_number']; ?></div><p><?php echo e($row['request_type']); ?> · <?php echo e($row['status']); ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="action" value="complete_request"><input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>"><button class="btn gold" name="status" value="Accepted">Accept</button> <button class="btn" name="status" value="Completed">Complete</button></form></article>
<?php endwhile; ?>
</section>
<section class="panel">
<h2>Tables</h2>
<div class="table-grid">
<?php while ($row = $tables->fetch_assoc()): ?>
<div class="tile <?php echo e($row['status']); ?>">Table <?php echo (int)$row['table_number']; ?><br><span class="muted"><?php echo e($row['status']); ?></span></div>
<?php endwhile; ?>
</div>
</section>
</div>
</section>
</main>
</body>
</html>
