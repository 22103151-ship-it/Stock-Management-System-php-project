<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/notification_functions.php';

// Accept POST or GET
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : (isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0);

// Customer id fallback lookup
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $lookup = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
    if ($lookup) {
        $lookup->bind_param('i', $user_id);
        $lookup->execute();
        $res = $lookup->get_result();
        if ($row = $res->fetch_assoc()) {
            $customer_id = (int)$row['id'];
            $_SESSION['customer_id'] = $customer_id;
        }
        $lookup->close();
    }
}

if ($order_id <= 0 || !$customer_id) {
    header('Location: my_orders.php?msg=invalid');
    exit;
}

$stmt = $conn->prepare("SELECT status FROM customer_orders WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    header('Location: my_orders.php?msg=invalid');
    exit;
}

// Mark as delivered when customer confirms receipt
if ($order['status'] === 'shipped') {
    $upd = $conn->prepare("UPDATE customer_orders SET status = 'delivered', delivery_date = NOW() WHERE id = ? AND customer_id = ?");
    if ($upd) {
        $upd->bind_param('ii', $order_id, $customer_id);
        $upd->execute();
        $upd->close();
    }
    sendOrderStatusUpdateNotification($customer_id, $order_id, 'delivered', $conn);
    header('Location: my_orders.php?msg=received');
    exit;
}

header('Location: my_orders.php?msg=invalid');
exit;
