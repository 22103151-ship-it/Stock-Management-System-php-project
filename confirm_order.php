<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';
require '../includes/notification_functions.php';

$customer_id = $_SESSION['customer_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    header('Location: pending_orders.php');
    exit;
}

// Verify order ownership and status
$stmt = $conn->prepare("SELECT id, product_id, quantity, price, status FROM customer_orders WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || $order['status'] !== 'pending') {
    header('Location: pending_orders.php');
    exit;
}

// Move to confirmed
$update = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ? AND customer_id = ?");
$update->bind_param('ii', $order_id, $customer_id);
$update->execute();

// Notify customer and admin
sendOrderStatusUpdateNotification($customer_id, $order_id, 'confirmed', $conn);
createNotificationDot('customer_order_confirmed', 'customer', 'admin', $order_id, 'blue', 'Customer confirmed an order', $conn);

header('Location: my_orders.php');
exit;
