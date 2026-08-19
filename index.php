<?php
require_once __DIR__ . '/includes/app.php';
if (isset($_GET['table'])) {
    redirect_to('menu.php?table=' . rawurlencode((string)$_GET['table']));
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restaurant System</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#2a1208;color:#fff}.hero{min-height:100vh;background:linear-gradient(rgba(42,18,8,.68),rgba(42,18,8,.82)),url('Non-veg/chicken_biryani.jpg') center/cover;display:flex;align-items:center;padding:24px}.box{max-width:760px}.brand{font-size:48px;font-weight:900;margin:0 0 12px}.lead{font-size:20px;line-height:1.5;color:#ffe9c7}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}.btn{background:#f7b733;color:#2a1208;text-decoration:none;border-radius:8px;padding:13px 16px;font-weight:900}.btn.dark{background:#fff;color:#2a1208}@media(max-width:640px){.brand{font-size:34px}.lead{font-size:17px}}
</style>
</head>
<body>
<main class="hero">
<section class="box">
<h1 class="brand">Restaurant Operating System</h1>
<p class="lead">Customers order by scanning their table QR. Kitchen, waiter, billing, and admin teams work from the same live table and order flow.</p>
<div class="actions">
<a class="btn" href="admin/login.php">Admin Login</a>
<a class="btn dark" href="kitchen/dashboard.php">Kitchen</a>
<a class="btn dark" href="waiter/dashboard.php">Waiter</a>
</div>
</section>
</main>
</body>
</html>
