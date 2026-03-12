<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0 || !in_array($type, ['supplier','customer'])) {
    header('Location: delivered_orders.php');
    exit;
}

if ($type === 'supplier') {
    $stmt = $conn->prepare("SELECT product_id, quantity, status FROM purchase_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if ($order && $order['status'] === 'delivered') {
        // Mark returned and remove previously added stock
        $conn->query("UPDATE purchase_orders SET status='returned' WHERE id=$id");
        $pid = (int)$order['product_id'];
        $qty = (int)$order['quantity'];
        $conn->query("UPDATE products SET stock = GREATEST(0, stock - $qty) WHERE id = $pid");
    }
} else {
    $stmt = $conn->prepare("SELECT product_id, quantity, status FROM customer_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if ($order && $order['status'] === 'delivered') {
        // Mark returned and restock the item
        $conn->query("UPDATE customer_orders SET status='returned' WHERE id=$id");
        $pid = (int)$order['product_id'];
        $qty = (int)$order['quantity'];
        $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $pid");
    }
}

header('Location: delivered_orders.php');
exit;
