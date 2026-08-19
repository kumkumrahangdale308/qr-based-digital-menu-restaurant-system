<?php

require_once __DIR__ . '/app.php';

function require_admin()
{
    if (empty($_SESSION['admin_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
        redirect_to('login.php');
    }
}

function require_staff_area($area)
{
    if (empty($_SESSION['admin_id'])) {
        redirect_to('../admin/login.php');
    }

    $role = $_SESSION['staff_role'] ?? 'admin';
    if ($role !== 'admin' && $role !== $area) {
        redirect_to('../admin/login.php');
    }
}

function current_admin_name()
{
    return $_SESSION['admin_username'] ?? 'Admin';
}

?>
