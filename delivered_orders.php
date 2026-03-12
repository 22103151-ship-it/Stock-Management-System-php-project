<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','staff','supplier'])) {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php';

$supplier_id = 0;
if (file_exists('../includes/supplier_helpers.php')) {
    include '../includes/supplier_helpers.php';
    $supplier_id = getResolvedSupplierId($conn);
}

// ------------------ Update stock for delivered orders ------------------
$delivered_orders = null;
if ($supplier_id > 0) {
    $stmt = $conn->prepare("
        SELECT po.id, po.product_id, po.quantity, po.stock_updated
        FROM purchase_orders po
        WHERE po.status = 'delivered' AND po.stock_updated = 0 AND po.supplier_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $delivered_orders = $stmt->get_result();
        $stmt->close();
    }
} else {
    $delivered_orders = $conn->query("
        SELECT po.id, po.product_id, po.quantity, po.stock_updated
        FROM purchase_orders po
        WHERE po.status = 'delivered' AND po.stock_updated = 0
    ");
}

if ($delivered_orders === false) {
    error_log("DB query error (update stock): " . $conn->error);
} elseif ($delivered_orders && $delivered_orders->num_rows > 0) {
    while($order = $delivered_orders->fetch_assoc()){
        $product_id = $order['product_id'];
        $quantity_delivered = $order['quantity'];

        // Update product stock
        $conn->query("UPDATE products SET stock = stock + $quantity_delivered WHERE id = $product_id");

        // Mark order as stock updated
        $conn->query("UPDATE purchase_orders SET stock_updated = 1 WHERE id = ".$order['id']);
    }
}

// ------------------ Fetch delivered orders ------------------
$result = null;
if ($supplier_id > 0) {
    $stmt = $conn->prepare("
        SELECT po.id, p.name AS product_name, po.quantity, po.status, po.created_at
        FROM purchase_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.status = 'delivered' AND po.supplier_id = ?
        ORDER BY po.id DESC
    ");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    $result = $conn->query("
        SELECT po.id, p.name AS product_name, po.quantity, po.status, po.created_at
        FROM purchase_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.status = 'delivered'
        ORDER BY po.id DESC
    ");
}

if ($result === false) {
    error_log("DB query error (fetch delivered orders): " . $conn->error);
}
?>

<div class="main-container">
    <!-- Back Button -->
    <a href="dashboard.php" class="back-btn">Back</a>

    <h2 class="page-title">📦 Delivered Orders</h2>

    <div class="table-container">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No delivered orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .main-container {
        max-width: 1000px;
        margin: 40px auto;
        background: #fff;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .page-title {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .back-btn {
        display: inline-block;
        margin-bottom: 20px;
        padding: 8px 15px;
        background: #555;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .back-btn:hover {
        background: #333;
    }

    .table-container {
        overflow-x: auto;
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 auto;
        font-size: 15px;
        border-radius: 5px;
        overflow: hidden;
    }

    .styled-table thead tr {
        background-color: #007BFF;
        color: #ffffff;
        text-align: left;
    }

    .styled-table th, .styled-table td {
        padding: 12px 15px;
        border: 1px solid #ddd;
    }

    .styled-table tbody tr:nth-child(even) {
        background-color: #f3f3f3;
    }

    .styled-table tbody tr:hover {
        background-color: #e9f5ff;
    }
</style>


<!-- <footer style="
    background-color: gray;
    color: white;
    text-align: center;
    padding: 15px 0;
">
    <p>© 2025 Stock Management System. All rights reserved.</p>
</footer> -->
