<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/notification_functions.php';

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    header('Location: customer_orders_confirmed.php');
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, status FROM customer_orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || $order['status'] !== 'confirmed') {
    header('Location: customer_orders_confirmed.php?msg=cancel_invalid');
    exit;
}

$upd = $conn->prepare("UPDATE customer_orders SET status = 'cancelled' WHERE id = ?");
$upd->bind_param('i', $order_id);
$upd->execute();

$customer_id = (int)$order['customer_id'];
sendOrderStatusUpdateNotification($customer_id, $order_id, 'cancelled', $conn);
createNotificationDot('customer_order_cancelled', 'admin', 'customer', $order_id, 'red', 'Your order was cancelled by admin', $conn);

header('Location: customer_orders_confirmed.php?msg=cancelled');
exit;
