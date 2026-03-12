<?php
ob_start(); // Start output buffering
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','staff'])) {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php';

// --- Fetch Delivered Purchase Orders (Suppliers) ---
$supplier_delivered = $conn->query("
    SELECT po.id, po.product_id, p.name AS product_name, po.quantity, po.status, po.created_at, p.price
    FROM purchase_orders po
    JOIN products p ON po.product_id = p.id
    WHERE po.status = 'delivered'
    ORDER BY po.created_at DESC
");

// --- Fetch Delivered Customer Orders (Admin grant -> Staff delivered) ---
$customer_delivered = $conn->query("
    SELECT co.id, co.product_id, p.name AS product_name, co.quantity, co.status, co.order_date AS created_at, co.price
    FROM customer_orders co
    JOIN products p ON co.product_id = p.id
    WHERE co.status = 'delivered'
    ORDER BY co.order_date DESC
");

$delivered_rows = [];
if ($supplier_delivered) {
    while ($row = $supplier_delivered->fetch_assoc()) {
        $row['source'] = 'supplier';
        $delivered_rows[] = $row;
    }
}
if ($customer_delivered) {
    while ($row = $customer_delivered->fetch_assoc()) {
        $row['source'] = 'customer';
        $delivered_rows[] = $row;
    }
}

// Sort all delivered rows by newest first
usort($delivered_rows, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
?>

<div style="max-width:1000px; margin:20px auto; padding:20px; background:#f8f8f8; border-radius:8px;">
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;"> Back </a>
    <h2>📦 Delivered Orders (Supplier & Customer)</h2>

    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:white; text-align:left;">
        <tr style="background:#ddd;">
            <th>Serial</th>
            <th>Source</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
            <th>Status</th>
            <th>Delivered At</th>
            <th>Invoice</th>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <th style="width:160px; text-align:center;">Action</th>
            <?php endif; ?>
        </tr>
        <?php if (empty($delivered_rows)): ?>
            <tr><td colspan="10" style="text-align:center; color:#666;">No delivered orders yet.</td></tr>
        <?php else: ?>
            <?php $serial = count($delivered_rows); foreach ($delivered_rows as $row): ?>
            <tr>
                <td><?php echo $serial--; ?></td>
                <td><?php echo $row['source'] === 'supplier' ? 'Supplier' : 'Staff Delivered'; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo number_format($row['price']*$row['quantity'], 2); ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <?php if ($row['source'] === 'supplier'): ?>
                        <a href="generate_supplier_invoice.php?order_id=<?php echo $row['id']; ?>" 
                           target="_blank" 
                           style="padding:5px 10px; background:#007bff; color:white; text-decoration:none; border-radius:3px;">Invoice</a>
                    <?php else: ?>
                        <a href="generate_customer_invoice.php?order_id=<?php echo $row['id']; ?>" 
                           target="_blank" 
                           style="padding:5px 10px; background:#2563eb; color:white; text-decoration:none; border-radius:3px;">Invoice</a>
                    <?php endif; ?>
                </td>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <td style="white-space:nowrap; text-align:center;">
                    <a href="return_delivered.php?type=<?php echo $row['source']; ?>&id=<?php echo $row['id']; ?>" 
                       onclick="return confirm('Return this delivered order? This will adjust stock.');" 
                       style="padding:5px 10px; background:#e74c3c; color:white; text-decoration:none; border-radius:3px;">Return</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<style>
/* Responsive Grid */
@media (max-width: 992px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}
@media (max-width: 600px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
</style>

<!-- <footer style="
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: gray;
    color: white;
    text-align: center;
    padding: 15px 0;
">
    <p>© 2025 Stock Management System. All rights reserved.</p>
</footer> -->
