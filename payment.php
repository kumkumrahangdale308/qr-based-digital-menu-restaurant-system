<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/order_repository.php';
require_once __DIR__ . '/includes/billing_repository.php';
require_once __DIR__ . '/api/db.php';

$orderId = filter_var($_GET['order_id'] ?? ($_SESSION['last_order_id'] ?? null), FILTER_VALIDATE_INT);
$order = $orderId ? get_order_with_items($conn, $orderId) : null;
$bill = $order ? get_or_create_bill($conn, $orderId) : null;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bill</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff8ef;color:#24140d}.bill{max-width:760px;margin:24px auto;background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:24px}.top{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid #2a1208;padding-bottom:14px}.brand{font-size:28px;font-weight:900;color:#a32017}.row{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:10px 0}.total{font-size:22px;font-weight:900}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:12px 15px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-block;margin-top:16px}@media print{.btn{display:none}.bill{border:0;margin:0}}
</style>
</head>
<body>
<main class="bill">
<?php if (!$order || !$bill): ?>
<h1>No bill found</h1>
<p>Please place an order first.</p>
<?php else: ?>
<section class="top">
<div><div class="brand">Restaurant Bill</div><div><?php echo e($bill['bill_number']); ?></div></div>
<div><strong>Table <?php echo (int)$order['table_number']; ?></strong><br>Order ORD<?php echo (int)$order['id']; ?></div>
</section>
<?php foreach ($order['items'] as $item): ?>
<div class="row"><span><?php echo e($item['item_name']); ?> x <?php echo (int)$item['quantity']; ?></span><strong>Rs. <?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></strong></div>
<?php endforeach; ?>
<div class="row"><span>Subtotal</span><strong>Rs. <?php echo number_format((float)$bill['subtotal'], 2); ?></strong></div>
<div class="row"><span>GST 5%</span><strong>Rs. <?php echo number_format((float)$bill['gst_amount'], 2); ?></strong></div>
<div class="row total"><span>Total</span><strong>Rs. <?php echo number_format((float)$bill['total_amount'], 2); ?></strong></div>
<p>Payment: <?php echo e($bill['payment_method']); ?> · <?php echo e($bill['payment_status']); ?></p>
<button class="btn" onclick="window.print()">Print Bill</button>
<a class="btn" href="orderstatus.php">Track Order</a>
<?php endif; ?>
</main>
</body>
</html>
