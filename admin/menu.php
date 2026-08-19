<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$search = trim((string)($_GET['search'] ?? ''));
$category = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT);

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(mi.item_name LIKE ? OR mi.description LIKE ?)";
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($category) {
    $where[] = "mi.category_id = ?";
    $types .= 'i';
    $params[] = $category;
}

$sql = "
    SELECT mi.*, c.category_name
    FROM menu_items mi
    JOIN categories c ON c.id = mi.category_id
";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY c.category_name, mi.item_name";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result();
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Management</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f4ef;color:#262626}.top{background:#fff;border-bottom:1px solid #e4d9cd;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}.brand{font-size:22px;font-weight:800;color:#8f1d14}.nav a{color:#302c29;text-decoration:none;margin-left:14px;font-weight:700}.wrap{padding:24px;max-width:1250px;margin:auto}.toolbar{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}.filters{display:flex;gap:10px;flex-wrap:wrap}.filters input,.filters select{padding:10px;border:1px solid #d8d0c7;border-radius:6px}.btn{padding:10px 14px;border:0;border-radius:6px;background:#8f1d14;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5ddd4}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}th{background:#fbfaf8;color:#6b625b;font-size:13px;text-transform:uppercase}.muted{color:#777}.available{color:#167a3c;font-weight:700}.unavailable{color:#a12a2a;font-weight:700}.danger{background:#a12a2a}@media(max-width:760px){.wrap{padding:12px}table,thead,tbody,tr,td,th{display:block}th{display:none}td{border-bottom:0}}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a><a href="tables.php">Tables & QR</a><a href="billing.php">Billing</a><a href="../waiter/dashboard.php">Waiter</a><a href="logout.php">Logout</a></nav></header>
<main class="wrap">
<h1>Menu Management</h1>
<div class="toolbar">
<form class="filters" method="get">
<input name="search" placeholder="Search menu" value="<?php echo e($search); ?>">
<select name="category"><option value="">All Categories</option><?php while ($cat = $categories->fetch_assoc()): ?><option value="<?php echo (int)$cat['id']; ?>" <?php echo $category === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['category_name']); ?></option><?php endwhile; ?></select>
<button class="btn">Filter</button><a class="btn" href="menu.php">Reset</a>
</form>
<a class="btn" href="add_menu.php">Add New Dish</a>
</div>
<table>
<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Image</th><th>Status</th><th>Actions</th></tr>
<?php while ($row = $items->fetch_assoc()): ?>
<tr>
<td><?php echo (int)$row['id']; ?></td>
<td><strong><?php echo e($row['item_name']); ?></strong><div class="muted"><?php echo e($row['description']); ?></div></td>
<td><?php echo e($row['category_name']); ?></td>
<td>Rs. <?php echo e($row['price']); ?></td>
<td><?php echo e($row['image_path'] ?: $row['image']); ?></td>
<td class="<?php echo e($row['availability']); ?>"><?php echo e(ucfirst($row['availability'])); ?></td>
<td>
<a class="btn" href="edit_menu.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
<form method="post" action="delete_menu.php" style="display:inline" onsubmit="return confirm('Delete this dish?');">
<input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
<input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
<button class="btn danger" type="submit">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</main>
</body>
</html>
