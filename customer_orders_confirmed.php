<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';
require '../includes/header.php';
require '../includes/notification_functions.php';

$orders = [];
$stmt = $conn->prepare("SELECT co.*, c.name AS customer_name, p.name AS product_name FROM customer_orders co JOIN customers c ON co.customer_id = c.id JOIN products p ON co.product_id = p.id WHERE co.status IN ('pending','confirmed','shipped') ORDER BY co.order_date DESC LIMIT 200");
if ($stmt) {
    $stmt->execute();
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $stmt->bind_result($id, $customer_id, $product_id, $quantity, $price, $status, $order_date, $delivery_date, $customer_name, $product_name);
        while ($stmt->fetch()) {
            $orders[] = [
                'id' => $id,
                'customer_id' => $customer_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
                'status' => $status,
                'order_date' => $order_date,
                'delivery_date' => $delivery_date,
                'customer_name' => $customer_name,
                'product_name' => $product_name,
            ];
        }
    }
    $stmt->close();
}
?>

<style>
    header { background: linear-gradient(135deg, rgba(26,42,71,0.85), rgba(255,152,0,0.35)), url('../assets/images/home-bg.jpg'); background-size: cover; background-position: center; }
    body { background: linear-gradient(rgba(255,255,255,0.45), rgba(255,255,255,0.45)), url('../assets/images/customer-orders-bg.jpg'); background-size: cover; background-position: center; background-attachment: fixed; }
    .wrap { max-width: 1100px; margin: 0 auto; background: rgba(255,255,255,0.94); border-radius:16px; padding:22px; box-shadow:0 18px 40px rgba(15, 23, 42, 0.18); border:1px solid rgba(255,255,255,0.6); backdrop-filter: blur(6px); }
    .table-shell { background:#ffffff; border-radius:14px; padding:6px; border:1px solid #eef2f7; box-shadow:0 10px 24px rgba(15,23,42,0.08); }
    .page-header { margin-bottom:16px; padding:16px 18px; border-radius:12px; background: linear-gradient(135deg, rgba(26,42,71,0.85), rgba(255,152,0,0.35)), url('../assets/images/home-bg.jpg'); background-size: cover; background-position: center; color:#fff; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .page-header h1 { margin:0; font-size:22px; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,0.35); }
    .page-header a { color:#fff; text-decoration:none; font-weight:600; background: rgba(0,0,0,0.35); padding:6px 10px; border-radius:8px; }
    table { width:100%; border-collapse: separate; border-spacing:0; overflow:hidden; border-radius:12px; }
    thead tr { background: linear-gradient(135deg, #1f3b74, #27488a); color:#fff; }
    th, td { padding:14px 12px; text-align:left; border-bottom:1px solid #eef2f7; font-size:14px; }
    th { font-weight:700; letter-spacing:0.2px; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody tr:hover { background:#eef2ff; }
    .status { padding:6px 12px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; display:inline-flex; align-items:center; gap:6px; }
    .status-pending { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .status-confirmed { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }
    .status-shipped { background:#ecfdf3; color:#166534; border:1px solid #bbf7d0; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; }
    .btn { border:none; padding:8px 12px; border-radius:10px; cursor:pointer; font-weight:700; font-size:12px; letter-spacing:0.2px; box-shadow:0 8px 16px rgba(15,23,42,0.12); }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#0ea5e9; color:#fff; }
    .btn-muted { background:#e2e8f0; color:#374151; }
</style>

<div class="wrap">
    <div class="page-header">
        <h1><i class="fa-solid fa-clipboard-check"></i> Confirmed Orders Checklist</h1>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'insufficient_stock'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#fef2f2; color:#b91c1c; border-radius:10px;">Stock is insufficient to grant this order.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'granted'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#ecfdf3; color:#166534; border-radius:10px;">Order moved to processing and stock deducted.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#fef2f2; color:#b91c1c; border-radius:10px;">Order cancelled.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'cancel_invalid'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#fff4e5; color:#92400e; border-radius:10px;">Cancel not allowed for this order state.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#ecfdf3; color:#166534; border-radius:10px;">Order approved and moved to confirmed.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'approve_invalid'): ?>
        <div style="margin-bottom:12px; padding:10px; background:#fff4e5; color:#92400e; border-radius:10px;">Approve not allowed for this order state.</div>
    <?php endif; ?>
    <?php if (empty($orders)): ?>
        <p>No confirmed orders right now.</p>
    <?php else: ?>
    <div class="table-shell">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Ordered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td><span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status'] === 'shipped' ? 'granted' : $order['status']); ?></span></td>
                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'] ?? ($order['created_at'] ?? 'now'))); ?></td>
                    <td class="actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <form method="POST" action="approve_customer_order.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button class="btn btn-primary" type="submit">Approve (Confirm)</button>
                            </form>
                            <form method="POST" action="cancel_customer_order.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button class="btn btn-muted" type="submit">Cancel</button>
                            </form>
                        <?php elseif ($order['status'] === 'confirmed'): ?>
                            <form method="POST" action="grant_customer_order.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="action" value="direct">
                                <button class="btn btn-primary" type="submit">Grant Direct</button>
                            </form>
                            <form method="POST" action="grant_customer_order.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="action" value="staff">
                                <button class="btn btn-secondary" type="submit">Grant via Staff</button>
                            </form>
                            <form method="POST" action="cancel_customer_order.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button class="btn btn-muted" type="submit">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="btn btn-muted" style="cursor:default;">Granted</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
