<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$stats = [];
$stats['total_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$stats['pending_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status IN ('New','Accepted','Preparing')")->fetch_assoc()['c'];
$stats['ready_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status = 'Ready'")->fetch_assoc()['c'];
$stats['completed_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status = 'Completed'")->fetch_assoc()['c'];
$stats['menu_items'] = (int)$conn->query("SELECT COUNT(*) c FROM menu_items")->fetch_assoc()['c'];
$stats['tables'] = (int)$conn->query("SELECT COUNT(*) c FROM restaurant_tables")->fetch_assoc()['c'];
$stats['requests'] = (int)$conn->query("SELECT COUNT(*) c FROM waiter_requests WHERE status IN ('Open','Accepted')")->fetch_assoc()['c'];
$stats['bills'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE payment_status IN ('Pending','Requested')")->fetch_assoc()['c'];

$recent = $conn->query("
    SELECT id, table_number, total_amount, status, order_time
    FROM orders
    ORDER BY id DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f1e8;color:#26160f}.top{background:#2a1208;color:#fff;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.brand{font-size:22px;font-weight:900}.nav a{color:#ffe9c7;text-decoration:none;margin-left:12px;font-weight:800}.wrap{padding:24px;max-width:1220px;margin:auto}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.card{background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:18px;box-shadow:0 8px 20px rgba(86,45,17,.08)}.num{font-size:32px;font-weight:900;color:#a32017}.quick{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}.btn{background:#a32017;color:#fff;border-radius:8px;padding:11px 13px;text-decoration:none;font-weight:900}.gold{background:#f7b733;color:#2a1208}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #ead7c3}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}th{background:#fff8ef}.badge{display:inline-block;padding:5px 9px;border-radius:999px;color:#fff;font-size:12px;font-weight:900}.new{background:#2563eb}.accepted{background:#7c3aed}.preparing{background:#f59e0b}.ready{background:#16a34a}.served{background:#0f766e}.completed{background:#64748b}@media(max-width:900px){.cards{grid-template-columns:1fr 1fr}}@media(max-width:560px){.cards{grid-template-columns:1fr}.wrap{padding:14px}.nav a{display:inline-block;margin:8px 8px 0 0}}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a><a href="tables.php">Tables & QR</a><a href="billing.php">Billing</a><a href="../waiter/dashboard.php">Waiter</a><a href="../kitchen/dashboard.php">Kitchen</a><a href="logout.php">Logout</a></nav></header>
<main class="wrap">
<h1>Dashboard</h1>
<section class="cards">
<div class="card"><div class="num"><?php echo $stats['total_orders']; ?></div><div>Total Orders</div></div>
<div class="card"><div class="num"><?php echo $stats['pending_orders']; ?></div><div>Pending Orders</div></div>
<div class="card"><div class="num"><?php echo $stats['ready_orders']; ?></div><div>Ready Orders</div></div>
<div class="card"><div class="num"><?php echo $stats['completed_orders']; ?></div><div>Completed Orders</div></div>
<div class="card"><div class="num"><?php echo $stats['menu_items']; ?></div><div>Menu Items</div></div>
<div class="card"><div class="num"><?php echo $stats['tables']; ?></div><div>Tables</div></div>
<div class="card"><div class="num"><?php echo $stats['requests']; ?></div><div>Waiter Requests</div></div>
<div class="card"><div class="num"><?php echo $stats['bills']; ?></div><div>Pending Bills</div></div>
</section>
<div class="quick"><a class="btn gold" href="tables.php">Manage QR Tables</a><a class="btn" href="billing.php">Billing</a><a class="btn" href="orders.php">Orders</a><a class="btn" href="menu.php">Menu</a></div>
<h2>Recent Orders</h2>
<table>
<tr><th>Order</th><th>Table</th><th>Total</th><th>Status</th><th>Time</th></tr>
<?php while ($row = $recent->fetch_assoc()): ?>
<tr><td>ORD<?php echo (int)$row['id']; ?></td><td><?php echo (int)$row['table_number']; ?></td><td>Rs. <?php echo e($row['total_amount']); ?></td><td><span class="badge <?php echo e(status_badge_class($row['status'])); ?>"><?php echo e($row['status']); ?></span></td><td><?php echo e($row['order_time']); ?></td></tr>
<?php endwhile; ?>
</table>
</main>
</body>
</html>
