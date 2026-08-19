<?php
require_once __DIR__ . '/includes/app.php';
$query = $_SERVER['QUERY_STRING'] ?? '';
redirect_to('menu.php' . ($query ? '?' . $query : ''));
?>
