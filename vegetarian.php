<?php
require_once __DIR__ . '/includes/app.php';
$table = $_GET['table'] ?? ($_SESSION['table_number'] ?? null);
redirect_to('menu.php?type=veg' . ($table ? '&table=' . rawurlencode((string)$table) : ''));
?>
