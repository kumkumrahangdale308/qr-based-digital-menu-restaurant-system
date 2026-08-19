<?php
require_once __DIR__ . '/../includes/app.php';

if (!empty($_SESSION['admin_id'])) {
    redirect_to('dashboard.php');
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f6f3ef;color:#252525;min-height:100vh;display:flex;align-items:center;justify-content:center}
.login{width:min(420px,92vw);background:#fff;border:1px solid #e7ded2;border-radius:8px;padding:30px;box-shadow:0 12px 30px rgba(37,37,37,.08)}
h1{margin:0 0 6px;font-size:28px;color:#8f1d14}
p{margin:0 0 22px;color:#6b625b}
label{font-weight:700;font-size:14px;display:block;margin:14px 0 6px}
input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #d8d0c7;border-radius:6px;font-size:15px}
button{width:100%;margin-top:20px;padding:12px;border:0;border-radius:6px;background:#8f1d14;color:#fff;font-weight:700;font-size:15px;cursor:pointer}
.error{background:#fdecec;color:#9f1b1b;border:1px solid #f6caca;padding:10px;border-radius:6px;margin-bottom:14px}
</style>
</head>
<body>
<main class="login">
    <h1>Restaurant Admin</h1>
    <p>Sign in to manage orders and menu.</p>
    <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
    <form method="post" action="login_process.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <label for="username">Username</label>
        <input id="username" name="username" required maxlength="100">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required maxlength="255">
        <button type="submit">Login</button>
    </form>
</main>
</body>
</html>
