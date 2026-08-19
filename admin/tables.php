<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/table_repository.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $tableNumber = filter_var($_POST['table_number'] ?? null, FILTER_VALIDATE_INT);
    $capacity = filter_var($_POST['capacity'] ?? 4, FILTER_VALIDATE_INT);
    $status = trim((string)($_POST['status'] ?? 'AVAILABLE'));

    if ($action === 'save' && $tableNumber && in_array($status, table_statuses(), true)) {
        $qr = 'menu.php?table=' . $tableNumber;
        if ($id) {
            $stmt = $conn->prepare("UPDATE restaurant_tables SET table_number = ?, capacity = ?, qr_code = ?, status = ? WHERE id = ?");
            $stmt->bind_param('iissi', $tableNumber, $capacity, $qr, $status, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, capacity, qr_code, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiss', $tableNumber, $capacity, $qr, $status);
        }
        $message = $stmt->execute() ? 'Table saved.' : 'Could not save table.';
    } elseif ($action === 'delete' && $id) {
        $active = $conn->prepare("SELECT COUNT(*) c FROM orders WHERE table_id = ? AND status <> 'Completed'");
        $active->bind_param('i', $id);
        $active->execute();
        $count = (int)$active->get_result()->fetch_assoc()['c'];
        if ($count > 0) {
            $message = 'Cannot delete a table with active orders.';
        } else {
            $stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id = ?");
            $stmt->bind_param('i', $id);
            $message = $stmt->execute() ? 'Table deleted.' : 'Could not delete table.';
        }
    }
}

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$edit = null;
if ($editId) {
    $stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

$tables = $conn->query("SELECT * FROM restaurant_tables ORDER BY table_number ASC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Table Management</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f1e8;color:#26160f}.top{background:#2a1208;color:#fff;padding:14px 24px;display:flex;justify-content:space-between;flex-wrap:wrap}.brand{font-size:22px;font-weight:900}.nav a{color:#ffe9c7;text-decoration:none;margin-left:12px;font-weight:800}.wrap{padding:24px;max-width:1250px;margin:auto}.panel{background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:16px;margin-bottom:16px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}.card{background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:14px}.qr{width:160px;height:160px}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:10px 12px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-block}.gold{background:#f7b733;color:#2a1208}.danger{background:#7f1d1d}.msg{background:#fff4cf;border:1px solid #f0d579;padding:10px;border-radius:8px;margin-bottom:12px}input,select{padding:10px;border:1px solid #d8c8b7;border-radius:8px;margin:4px}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a><a href="tables.php">Tables & QR</a><a href="billing.php">Billing</a><a href="logout.php">Logout</a></nav></header>
<main class="wrap">
<h1>Table & QR Management</h1>
<?php if ($message): ?><div class="msg"><?php echo e($message); ?></div><?php endif; ?>
<section class="panel">
<h2><?php echo $edit ? 'Edit Table' : 'Add Table'; ?></h2>
<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
<input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?>
<input name="table_number" type="number" min="1" required placeholder="Table number" value="<?php echo e($edit['table_number'] ?? ''); ?>">
<input name="capacity" type="number" min="1" required placeholder="Capacity" value="<?php echo e($edit['capacity'] ?? '4'); ?>">
<select name="status"><?php foreach (table_statuses() as $s): ?><option value="<?php echo e($s); ?>" <?php echo ($edit['status'] ?? '') === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option><?php endforeach; ?></select>
<button class="btn">Save Table</button>
</form>
</section>
<section class="grid">
<?php while ($row = $tables->fetch_assoc()): $url = table_qr_url($row); ?>
<article class="card">
<h2>Table <?php echo (int)$row['table_number']; ?></h2>
<p>Capacity <?php echo (int)$row['capacity']; ?> · <?php echo e($row['status']); ?></p>
<img class="qr" src="<?php echo e(resolve_table_image_url($url)); ?>" alt="QR">
<p><small><?php echo e($url); ?></small></p>
<a class="btn gold" href="<?php echo e(resolve_table_image_url($url)); ?>" download="table-<?php echo (int)$row['table_number']; ?>.png">Download QR</a>
<button class="btn" onclick="printQr('<?php echo e($url); ?>','Table <?php echo (int)$row['table_number']; ?>')">Print QR</button>
<a class="btn" href="tables.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
<form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"><button class="btn danger" onclick="return confirm('Delete this table?')">Delete</button></form>
</article>
<?php endwhile; ?>
</section>
</main>
<script>
function printQr(url,label){const qr='https://api.qrserver.com/v1/create-qr-code/?size=360x360&data='+encodeURIComponent(url);const w=window.open('','qr');w.document.write('<title>'+label+'</title><body style="font-family:Arial;text-align:center"><h1>'+label+'</h1><img src="'+qr+'"><p>'+url+'</p><script>window.print()<\\/script></body>');w.document.close();}
</script>
</body>
</html>
