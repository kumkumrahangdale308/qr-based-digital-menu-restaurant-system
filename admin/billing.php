<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/order_repository.php';
require_once __DIR__ . '/../includes/billing_repository.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf_token'] ?? '')) {
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $payment = trim((string)($_POST['payment_method'] ?? 'Cash'));
    if ($orderId && get_or_create_bill($conn, $orderId) && mark_bill_paid($conn, $orderId, $payment)) {
        update_order_status($conn, $orderId, 'Completed', 'billing');
        $message = 'Bill marked paid.';
    } else {
        $message = 'Could not update bill.';
    }
}

$orders = $conn->query("
    SELECT o.id, o.table_number, o.total_amount, o.payment_method, o.payment_status, o.status, o.order_time,
           b.bill_number, b.gst_amount, b.total_amount bill_total, b.payment_status bill_status
    FROM orders o
    LEFT JOIN bills b ON b.order_id = o.id
    ORDER BY o.id DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f1e8;color:#26160f}.top{background:#2a1208;color:#fff;padding:14px 24px;display:flex;justify-content:space-between;flex-wrap:wrap}.brand{font-size:22px;font-weight:900}.nav a{color:#ffe9c7;text-decoration:none;margin-left:12px;font-weight:800}.wrap{padding:24px;max-width:1250px;margin:auto}.msg{background:#fff4cf;border:1px solid #f0d579;padding:10px;border-radius:8px;margin-bottom:12px}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #ead7c3}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}th{background:#fff8ef}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:9px 11px;font-weight:900;text-decoration:none;cursor:pointer}.gold{background:#f7b733;color:#2a1208}select{padding:8px;border:1px solid #d8c8b7;border-radius:8px}@media(max-width:760px){table,tbody,tr,td,th{display:block}th{display:none}}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a><a href="tables.php">Tables & QR</a><a href="billing.php">Billing</a><a href="logout.php">Logout</a></nav></header>
<main class="wrap">
<h1>Billing</h1>
<?php if ($message): ?><div class="msg"><?php echo e($message); ?></div><?php endif; ?>
<table>
<tr><th>Order</th><th>Table</th><th>Status</th><th>Subtotal</th><th>Bill</th><th>Payment</th><th>Action</th></tr>
<?php while ($row = $orders->fetch_assoc()): ?>
<tr>
<td>ORD<?php echo (int)$row['id']; ?></td>
<td><?php echo (int)$row['table_number']; ?></td>
<td><?php echo e($row['status']); ?></td>
<td>Rs. <?php echo e($row['total_amount']); ?></td>
<td><?php echo e($row['bill_number'] ?: 'Not generated'); ?></td>
<td><?php echo e($row['bill_status'] ?: $row['payment_status']); ?></td>
<td>
<a class="btn gold" href="../payment.php?order_id=<?php echo (int)$row['id']; ?>">Print</a>
<?php if (($row['bill_status'] ?: $row['payment_status']) !== 'Paid'): ?>
<form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><select name="payment_method"><option>Cash</option><option>UPI</option><option>Card</option><option>Counter</option></select><button class="btn">Paid</button></form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</main>
</body>
</html>
