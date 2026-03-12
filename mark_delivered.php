<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supplier') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/supplier_helpers.php';

$supplier_id = getResolvedSupplierId($conn);

// Check if order_id is provided
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];

    // Ensure the order belongs to this supplier and is pending
    if ($supplier_id > 0) {
        $order_check = $conn->query("SELECT id, product_id, quantity FROM purchase_orders WHERE id=$order_id AND supplier_id=$supplier_id AND status='pending' LIMIT 1");
    } else {
        $order_check = $conn->query("SELECT id, product_id, quantity FROM purchase_orders WHERE id=$order_id AND status='pending' LIMIT 1");
    }

    if ($order_check && $order_check->num_rows > 0) {
        $order_row = $order_check->fetch_assoc();
        $product_id = (int)$order_row['product_id'];
        $qty = (int)$order_row['quantity'];

        // Update status to delivered
        $conn->query("UPDATE purchase_orders SET status='delivered' WHERE id=$order_id");

        // Auto-increment product stock when delivery is confirmed
        $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $product_id");
    }
}

// Redirect back to supplier dashboard
header("Location: dashboard.php");
exit;
