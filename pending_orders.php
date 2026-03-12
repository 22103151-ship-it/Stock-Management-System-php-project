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
$result = null;

if ($supplier_id > 0) {
    $stmt = $conn->prepare("
        SELECT po.id, p.name AS product_name, po.quantity, po.status, po.created_at
        FROM purchase_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.status = 'pending' AND po.supplier_id = ?
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
        WHERE po.status = 'pending'
        ORDER BY po.id DESC
    ");
}
?>

<div style="max-width:900px; 
               margin:20px auto; 
               padding:20px; 
               background:#f8f8f8; 
               border-radius:8px;">
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;"> Back </a>
    <h2>📦 Pending Orders</h2> <!-- ✅ Corrected heading -->

    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:white; text-align:left;">
        <tr style="background:#ddd;">
            <th>ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Created At</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
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
            <tr><td colspan="5" style="text-align:center; color:#666;">No pending orders.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- <footer style="
    /* position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: pending;
    color: white;
    text-align: center;
    padding: 15px 0;
">
    <p>© 2025 Stock Management System. All rights reserved.</p>
</footer> */ -->

