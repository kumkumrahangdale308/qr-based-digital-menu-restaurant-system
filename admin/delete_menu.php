<?php

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../api/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? '')) {
    redirect_to('menu.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

redirect_to('menu.php');

?>
