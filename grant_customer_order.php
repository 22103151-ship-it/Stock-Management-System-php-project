<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/notification_functions.php';

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($order_id <= 0 || !in_array($action, ['direct','staff'])) {
    header('Location: customer_orders_confirmed.php');
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, status FROM customer_orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order || $order['status'] !== 'confirmed') {
    header('Location: customer_orders_confirmed.php');
    exit;
}

// Move to shipped (delivery processing)
// Fetch product and stock to deduct now (prepared)
$detail = $conn->prepare("SELECT p.id as product_id, p.stock, co.quantity FROM customer_orders co JOIN products p ON co.product_id = p.id WHERE co.id = ? LIMIT 1");
$d = null;
if ($detail) {
    $detail->bind_param('i', $order_id);
    $detail->execute();
    if (method_exists($detail, 'get_result')) {
        $res = $detail->get_result();
        $d = $res ? $res->fetch_assoc() : null;
    } else {
        $detail->bind_result($product_id, $stock, $quantity);
        if ($detail->fetch()) {
            $d = ['product_id' => $product_id, 'stock' => $stock, 'quantity' => $quantity];
        }
    }
    $detail->close();
}
if (!$d || $d['stock'] < $d['quantity']) {
    header('Location: customer_orders_confirmed.php?msg=insufficient_stock');
    exit;
}

// Deduct stock
$new_stock = (int)$d['stock'] - (int)$d['quantity'];
$stockUpd = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
if ($stockUpd) {
    $stockUpd->bind_param('ii', $new_stock, $d['product_id']);
    $stockUpd->execute();
    $stockUpd->close();
}

// Move to shipped (delivery processing)
$upd = $conn->prepare("UPDATE customer_orders SET status = 'shipped' WHERE id = ?");
if ($upd) {
    $upd->bind_param('i', $order_id);
    $upd->execute();
    $upd->close();
}

$customer_id = (int)$order['customer_id'];
// Notify customer: delivery processing
sendOrderStatusUpdateNotification($customer_id, $order_id, 'shipped', $conn);

$redirect = 'grant_status.php?order_id=' . $order_id . '&action=' . $action;

if ($action === 'staff') {
    createNotificationDot('admin_grant_staff', 'admin', 'staff', $order_id, 'green', 'Admin granted order to staff for delivery', $conn);
} else {
    createNotificationDot('admin_grant_direct', 'admin', 'admin', $order_id, 'blue', 'Admin handling delivery directly', $conn);
}

header('Location: ' . $redirect);
exit;
