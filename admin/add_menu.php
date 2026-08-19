<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }

    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $image = trim((string)($_POST['image'] ?? ''));
    $availability = trim((string)($_POST['availability'] ?? 'available'));

    if (!$categoryId) $errors[] = 'Category is required.';
    if ($itemName === '' || strlen($itemName) > 255) $errors[] = 'Dish name is required.';
    if ($price === false || $price <= 0) $errors[] = 'Price must be greater than zero.';
    if (!in_array($availability, ['available', 'unavailable'], true)) $availability = 'available';
    if ($image !== '' && !preg_match('/^[a-zA-Z0-9_\- .\/]+\.(jpg|jpeg|png|webp)$/i', $image)) $errors[] = 'Image filename is not valid.';

    if (!$errors) {
        $stmt = $conn->prepare("
            INSERT INTO menu_items (category_id, item_name, description, price, image, availability)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('issdss', $categoryId, $itemName, $description, $price, $image, $availability);
        $stmt->execute();
        redirect_to('menu.php');
    }
}

$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Dish</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f4ef;color:#262626}.top{background:#fff;border-bottom:1px solid #e4d9cd;padding:14px 24px;display:flex;justify-content:space-between;align-items:center}.brand{font-size:22px;font-weight:800;color:#8f1d14}.nav a{color:#302c29;text-decoration:none;margin-left:14px;font-weight:700}.wrap{padding:24px;max-width:680px;margin:auto}.panel{background:#fff;border:1px solid #e5ddd4;border-radius:8px;padding:22px}label{font-weight:700;display:block;margin:14px 0 6px}input,textarea,select{width:100%;box-sizing:border-box;padding:11px;border:1px solid #d8d0c7;border-radius:6px;font-size:15px}textarea{min-height:110px}.btn{margin-top:18px;padding:12px 16px;border:0;border-radius:6px;background:#8f1d14;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}.error{background:#fdecec;color:#9f1b1b;border:1px solid #f6caca;padding:10px;border-radius:6px;margin-bottom:12px}
</style>
</head>
<body>
<header class="top"><div class="brand">Restaurant Admin</div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="orders.php">Orders</a><a href="menu.php">Menu</a></nav></header>
<main class="wrap"><section class="panel">
<h1>Add New Dish</h1>
<?php foreach ($errors as $error): ?><div class="error"><?php echo e($error); ?></div><?php endforeach; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
<label>Category</label><select name="category_id" required><option value="">Select Category</option><?php while ($cat = $categories->fetch_assoc()): ?><option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['category_name']); ?></option><?php endwhile; ?></select>
<label>Dish Name</label><input name="item_name" required maxlength="255">
<label>Description</label><textarea name="description"></textarea>
<label>Price</label><input type="number" name="price" step="0.01" min="1" required>
<label>Image File Name</label><input name="image" maxlength="255" placeholder="paneer_butter_masala.jpg">
<label>Availability</label><select name="availability"><option value="available">Available</option><option value="unavailable">Unavailable</option></select>
<button class="btn">Save Dish</button> <a class="btn" href="menu.php">Cancel</a>
</form>
</section></main>
</body>
</html>
