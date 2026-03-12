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
    header('Location: customer_orders_confirmed.php?msg=approve_invalid');
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, status FROM customer_orders WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $order = null;
}

if (!$order || $order['status'] !== 'pending') {
    header('Location: customer_orders_confirmed.php?msg=approve_invalid');
    exit;
}

$upd = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ?");
if ($upd) {
    $upd->bind_param('i', $order_id);
    $upd->execute();
    $upd->close();
}

$customer_id = (int)$order['customer_id'];
sendOrderStatusUpdateNotification($customer_id, $order_id, 'confirmed', $conn);
createNotificationDot('customer_order_confirmed', 'admin', 'customer', $order_id, 'blue', 'Admin approved your order', $conn);

header('Location: customer_orders_confirmed.php?msg=approved');
exit;
