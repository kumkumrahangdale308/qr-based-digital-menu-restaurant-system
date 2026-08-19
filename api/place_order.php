<?php

require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/table_repository.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$tableId = filter_var($payload['table_id'] ?? ($_SESSION['table_id'] ?? null), FILTER_VALIDATE_INT);
$tableNumber = filter_var($payload['table_number'] ?? ($_SESSION['table_number'] ?? null), FILTER_VALIDATE_INT);
$paymentMethod = trim((string)($payload['payment_method'] ?? 'Counter'));
$items = $payload['items'] ?? [];

if (is_string($items)) {
    $items = json_decode($items, true);
}

$table = null;
if ($tableId) {
    $table = get_table_by_id($conn, $tableId);
} elseif ($tableNumber) {
    $table = get_table_by_number($conn, $tableNumber);
}

if (!$table || $table['status'] === 'OUT_OF_SERVICE') {
    json_response(['success' => false, 'message' => 'This table is not available for ordering.'], 400);
}

if (!in_array($paymentMethod, ['Counter', 'Cash', 'UPI', 'Card'], true)) {
    $paymentMethod = 'Counter';
}

if (!is_array($items) || count($items) === 0) {
    json_response(['success' => false, 'message' => 'Cart is empty.'], 400);
}

$menuIds = [];
$quantities = [];
foreach ($items as $item) {
    $menuId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? ($item['qty'] ?? null), FILTER_VALIDATE_INT);

    if (!$menuId || !$quantity || $quantity < 1 || $quantity > 25) {
        json_response(['success' => false, 'message' => 'Invalid item in cart.'], 400);
    }

    $menuIds[] = $menuId;
    $quantities[$menuId] = ($quantities[$menuId] ?? 0) + $quantity;
}

$placeholders = implode(',', array_fill(0, count($menuIds), '?'));
$types = str_repeat('i', count($menuIds));

$stmt = $conn->prepare("
    SELECT mi.id, mi.item_name, mi.price, mi.category_id, mi.availability, c.category_name
    FROM menu_items mi
    JOIN categories c ON c.id = mi.category_id
    WHERE mi.id IN ($placeholders)
");
$stmt->bind_param($types, ...$menuIds);
$stmt->execute();
$result = $stmt->get_result();

$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[(int)$row['id']] = $row;
}

$orderItems = [];
$totalAmount = 0.00;
foreach ($quantities as $menuId => $quantity) {
    if (!isset($menuItems[$menuId])) {
        json_response(['success' => false, 'message' => 'One or more menu items no longer exist.'], 400);
    }

    $menuItem = $menuItems[$menuId];
    if ($menuItem['availability'] !== 'available') {
        json_response(['success' => false, 'message' => $menuItem['item_name'] . ' is currently unavailable.'], 400);
    }

    $price = (float)$menuItem['price'];
    $category = ((int)$menuItem['category_id'] === 2) ? 'NonVeg' : 'Veg';
    $totalAmount += $price * $quantity;

    $orderItems[] = [
        'menu_item_id' => (int)$menuItem['id'],
        'item_name' => $menuItem['item_name'],
        'quantity' => $quantity,
        'price' => $price,
        'category' => $category
    ];
}

$conn->begin_transaction();

try {
    $orderStmt = $conn->prepare("
        INSERT INTO orders (table_id, table_number, total_amount, payment_method, payment_status, status, start_time, updated_at)
        VALUES (?, ?, ?, ?, 'Pending', 'New', NOW(), NOW())
    ");
    $tableDbId = (int)$table['id'];
    $tableDbNumber = (int)$table['table_number'];
    $orderStmt->bind_param('iids', $tableDbId, $tableDbNumber, $totalAmount, $paymentMethod);
    $orderStmt->execute();
    $orderId = $conn->insert_id;

    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price, category)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($orderItems as $item) {
        $menuItemId = $item['menu_item_id'];
        $itemName = $item['item_name'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $category = $item['category'];
        $itemStmt->bind_param('iisids', $orderId, $menuItemId, $itemName, $quantity, $price, $category);
        $itemStmt->execute();
    }

    mark_table_occupied($conn, $tableDbId);
    $conn->commit();

    $_SESSION['last_order_id'] = $orderId;

    json_response([
        'success' => true,
        'order_id' => $orderId,
        'display_order_id' => 'ORD' . $orderId,
        'table_id' => $tableDbId,
        'table_number' => $tableDbNumber,
        'total_amount' => number_format($totalAmount, 2, '.', ''),
        'payment_method' => $paymentMethod,
        'status' => 'New'
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    json_response(['success' => false, 'message' => 'Could not place order. Please try again.'], 500);
}

?>
