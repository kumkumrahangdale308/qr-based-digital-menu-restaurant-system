<?php

require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../api/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? '')) {
    redirect_to('login.php?error=Invalid request');
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    redirect_to('login.php?error=Username and password are required');
}

$stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin || !password_verify($password, $admin['password'])) {
    redirect_to('login.php?error=Invalid username or password');
}

session_regenerate_id(true);
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['staff_name'] = $admin['username'];
$_SESSION['staff_role'] = 'admin';
csrf_token();

redirect_to('dashboard.php');

?>
