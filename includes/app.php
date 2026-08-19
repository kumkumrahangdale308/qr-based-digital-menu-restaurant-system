<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to($path)
{
    header('Location: ' . $path);
    exit;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valid($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function json_response($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function app_base_url()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $scriptDir = preg_replace('#/(admin|api|kitchen|waiter)$#', '', $scriptDir);
    return $scheme . '://' . $host . ($scriptDir ? $scriptDir : '');
}

function normalize_name($value)
{
    $value = strtolower(trim((string)$value));
    return preg_replace('/\s+/', ' ', $value);
}

function menu_image_path($item)
{
    $image = trim((string)($item['image_path'] ?: $item['image']));

    if ($image === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }

    $categoryId = (int)($item['category_id'] ?? 0);
    $base = $categoryId === 2 ? 'Non-veg/' : 'vegitarian/';
    $candidate = $base . $image;

    if (file_exists(__DIR__ . '/../' . $candidate)) {
        return $candidate;
    }

    $folders = ['vegitarian', 'Non-veg', 'salad', 'soup'];
    $lowerImage = strtolower($image);

    foreach ($folders as $folder) {
        $dir = __DIR__ . '/../' . $folder;
        if (!is_dir($dir)) {
            continue;
        }

        foreach (scandir($dir) as $file) {
            if (strtolower($file) === $lowerImage) {
                return $folder . '/' . $file;
            }
        }
    }

    return $candidate;
}

function order_statuses()
{
    return ['New', 'Accepted', 'Preparing', 'Ready', 'Served', 'Completed'];
}

function kitchen_statuses()
{
    return ['New', 'Accepted', 'Preparing', 'Ready'];
}

function status_badge_class($status)
{
    return strtolower(preg_replace('/[^a-z]/i', '', (string)$status));
}

function table_statuses()
{
    return ['AVAILABLE', 'OCCUPIED', 'RESERVED', 'OUT_OF_SERVICE'];
}

function waiter_request_types()
{
    return ['Need Water', 'Need Spoon', 'Need Tissue', 'Need Assistance', 'Need Bill'];
}

function require_role($role)
{
    if (empty($_SESSION['staff_role']) || $_SESSION['staff_role'] !== $role) {
        $login = $role === 'admin' ? '../admin/login.php' : '../admin/login.php';
        redirect_to($login);
    }
}

function current_staff_name()
{
    return $_SESSION['admin_username'] ?? $_SESSION['staff_name'] ?? 'Staff';
}

function resolve_table_image_url($url)
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($url);
}

?>
