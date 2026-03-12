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
$stmt = $conn->prepare("SELECT customer_id, status FROM customer_orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] !== 'confirmed') {
    header('Location: customer_orders.php?msg=error');
    exit;
}

// Update order status to shipped
$upd = $conn->prepare("UPDATE customer_orders SET status = 'shipped' WHERE id = ?");
$upd->bind_param('i', $order_id);
$upd->execute();
$upd->close();

// Send notification to customer
$customer_id = (int)$order['customer_id'];
sendOrderStatusUpdateNotification($customer_id, $order_id, 'shipped', $conn);
createNotificationDot('customer_order_shipped', 'staff', 'customer', $order_id, 'yellow', 'Your order has been shipped', $conn);

header('Location: customer_orders.php?msg=shipped');
exit;
