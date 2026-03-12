<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';
require '../includes/notification_functions.php';

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    header('Location: customer_orders.php?msg=error');
    exit;
}

// Get order details
$stmt = $conn->prepare("SELECT customer_id, product_id, quantity, status FROM customer_orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] !== 'pending') {
    header('Location: customer_orders.php?msg=error');
    exit;
}

// Check stock availability
$product_id = (int)$order['product_id'];
$quantity = (int)$order['quantity'];

$stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? LIMIT 1");
$stock_stmt->bind_param('i', $product_id);
$stock_stmt->execute();
$product = $stock_stmt->get_result()->fetch_assoc();
$stock_stmt->close();

if (!$product || $product['stock'] < $quantity) {
    header('Location: customer_orders.php?msg=error&reason=insufficient_stock');
    exit;
}

// Update order status to confirmed
$upd = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ?");
$upd->bind_param('i', $order_id);
$upd->execute();
$upd->close();

// Deduct stock
$stock_upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
$stock_upd->bind_param('ii', $quantity, $product_id);
$stock_upd->execute();
$stock_upd->close();

// Send notification to customer
$customer_id = (int)$order['customer_id'];
sendOrderStatusUpdateNotification($customer_id, $order_id, 'confirmed', $conn);
createNotificationDot('customer_order_confirmed', 'staff', 'customer', $order_id, 'blue', 'Your order has been approved by staff', $conn);

header('Location: customer_orders.php?msg=approved');
exit;
