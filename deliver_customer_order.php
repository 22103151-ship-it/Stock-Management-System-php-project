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
    header('Location: processing_orders.php');
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, status FROM customer_orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order || $order['status'] !== 'shipped') {
    header('Location: processing_orders.php');
    exit;
}

$upd = $conn->prepare("UPDATE customer_orders SET status = 'delivered' WHERE id = ?");
$upd->bind_param('i', $order_id);
$upd->execute();

$customer_id = (int)$order['customer_id'];
sendOrderNotification($customer_id, $order_id, $conn);

// Notify admin that staff completed delivery
createNotificationDot('staff_delivery_done', 'staff', 'admin', $order_id, 'green', 'Staff marked order delivered', $conn);

// Deactivate staff grant dot if exists
@deactivateNotificationDots('admin_grant_staff', $order_id, $conn);

header('Location: delivery_status.php?order_id=' . $order_id . '&notified=1');
exit;
