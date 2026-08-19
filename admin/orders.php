<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/order_repository.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $status = trim((string)($_POST['status'] ?? ''));

    if ($orderId && csrf_valid($_POST['csrf_token'] ?? '') && update_order_status($conn, $orderId, $status)) {
        $message = 'Order status updated.';
    } else {
        $message = 'Status update failed.';
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(CAST(o.id AS CHAR) LIKE ? OR CAST(o.table_number AS CHAR) LIKE ? OR oi.item_name LIKE ?)";
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== '' && in_array($statusFilter, order_statuses(), true)) {
    $where[] = "o.status = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

$sql = "
    SELECT o.id, o.table_number, o.total_amount, o.status, o.order_time,
           GROUP_CONCAT(CONCAT(oi.item_name, ' x ', oi.quantity) ORDER BY oi.id SEPARATOR ', ') items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " GROUP BY o.id ORDER BY o.id DESC LIMIT 200";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Management</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f4ef;color:#262626}.top{background:#fff;border-bottom:1px solid #e4d9cd;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}.brand{font-size:22px;font-weight:800;color:#8f1d14}.nav a{color:#302c29;text-decoration:none;margin-left:14px;font-weight:700}.wrap{padding:24px;max-width:1250px;margin:auto}.panel{background:#fff;border:1px solid #e5ddd4;border-radius:8px;padding:16px;margin-bottom:16px}.filters{display:flex;gap:10px;flex-wrap:wrap}.filters input,.filters select{padding:10px;border:1px solid #d8d0c7;border-radius:6px}.btn{padding:10px 14px;border:0;border-radius:6px;background:#8f1d14;color:#fff;font-weight:700;cursor:pointer;text-decoration:none}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5ddd4}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}th{background:#fbfaf8;color:#6b625b;font-size:13px;text-transform:uppercase}.badge{display:inline-block;padding:5px 9px;border-radius:999px;color:#fff;font-size:12px;font-weight:700}.new{background:#2f80ed}.accepted{background:#7b61ff}.preparing{background:#f2994a}.ready{background:#219653}.served{background:#0f766e}.completed{background:#6b7280}.msg{background:#fff7df;border:1px solid #ead7a3;padding:10px;border-radius:6px;margin-bottom:12px}.status-form{display:flex;gap:6px}.status-form select{padding:8px}@media(max-width:760px){.wrap{padding:12px}table,thead,tbody,tr,td,th{display:block}th{display:none}td{border-bottom:0}.status-form{flex-wrap:wrap}}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a><a href="tables.php">Tables & QR</a><a href="billing.php">Billing</a><a href="../waiter/dashboard.php">Waiter</a><a href="logout.php">Logout</a></nav></header>
<main class="wrap">
<h1>Order Management</h1>
<?php if ($message): ?><div class="msg"><?php echo e($message); ?></div><?php endif; ?>
<section class="panel">
<form class="filters" method="get">
<input name="search" placeholder="Search order, table, item" value="<?php echo e($search); ?>">
<select name="status"><option value="">All Statuses</option><?php foreach (order_statuses() as $s): ?><option value="<?php echo e($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option><?php endforeach; ?></select>
<button class="btn">Filter</button><a class="btn" href="orders.php">Reset</a>
</form>
</section>
<table>
<tr><th>Order</th><th>Table</th><th>Items</th><th>Total</th><th>Status</th><th>Time</th><th>Action</th></tr>
<?php while ($row = $orders->fetch_assoc()): ?>
<tr>
<td>ORD<?php echo (int)$row['id']; ?></td>
<td><?php echo (int)$row['table_number']; ?></td>
<td><?php echo e($row['items'] ?: 'No items'); ?></td>
<td>Rs. <?php echo e($row['total_amount']); ?></td>
<td><span class="badge <?php echo e(status_badge_class($row['status'])); ?>"><?php echo e($row['status']); ?></span></td>
<td><?php echo e($row['order_time']); ?></td>
<td>
<form class="status-form" method="post">
<input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
<input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">
<select name="status"><?php foreach (order_statuses() as $s): ?><option value="<?php echo e($s); ?>" <?php echo $row['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option><?php endforeach; ?></select>
<button class="btn">Save</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</main>
</body>
</html>
