<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    header('Location: processing_orders.php');
    exit;
}

$stmt = $conn->prepare("SELECT co.id, co.status, co.quantity, co.order_date, c.name AS customer_name, p.name AS product_name FROM customer_orders co JOIN customers c ON co.customer_id = c.id JOIN products p ON co.product_id = p.id WHERE co.id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: processing_orders.php');
    exit;
}

$is_delivered = $order['status'] === 'delivered';
$notified = isset($_GET['notified']) && $_GET['notified'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family:'Poppins', sans-serif; background:#f6f8fb; margin:0; padding:20px; }
        .wrap { max-width:720px; margin:0 auto; background:#fff; border-radius:16px; padding:24px; box-shadow:0 14px 36px rgba(0,0,0,0.08); }
        h1 { margin:0 0 10px 0; font-size:22px; color:#111827; }
        .badge { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; font-weight:800; font-size:14px; letter-spacing:0.2px; }
        .badge-success { background:#e0fbea; color:#0f5132; border:1px solid #a3e3b8; }
        .badge-warn { background:#fff4e5; color:#92400e; }
        .banner { margin:14px 0 6px 0; padding:14px; border-radius:12px; background:#eef2ff; color:#312e81; font-weight:700; display:flex; gap:10px; align-items:center; }
        .banner i { color:#2563eb; }
        .card { margin-top:16px; padding:16px; border:1px solid #e5e7eb; border-radius:12px; }
        .row { display:flex; justify-content:space-between; margin-bottom:8px; color:#374151; }
        .row span:first-child { font-weight:600; }
        .actions { margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; }
        .btn { text-decoration:none; padding:10px 14px; border-radius:10px; font-weight:600; color:#fff; }
        .btn-primary { background:#2563eb; }
        .btn-secondary { background:#6b7280; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1><i class="fa-solid fa-badge-check"></i> Admin Approval Notified</h1>
        <?php if ($is_delivered || $notified): ?>
            <div class="badge badge-success"><i class="fa-solid fa-circle-check"></i> OK — Order sent, admin notified instantly.</div>
            <div class="banner"><i class="fa-solid fa-bell"></i>Admin has been notified. No further action needed.</div>
        <?php else: ?>
            <div class="badge badge-warn"><i class="fa-solid fa-circle-exclamation"></i> Order is not marked delivered yet.</div>
        <?php endif; ?>

        <div class="card">
            <div class="row"><span>Order ID</span><span>#<?php echo $order['id']; ?></span></div>
            <div class="row"><span>Customer</span><span><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
            <div class="row"><span>Product</span><span><?php echo htmlspecialchars($order['product_name']); ?></span></div>
            <div class="row"><span>Quantity</span><span><?php echo $order['quantity']; ?></span></div>
            <div class="row"><span>Order Date</span><span><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></span></div>
            <div class="row"><span>Current Status</span><span style="text-transform:capitalize;">&nbsp;<?php echo $order['status']; ?></span></div>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="processing_orders.php">Back to Processing</a>
            <a class="btn btn-secondary" href="dashboard.php">Staff Dashboard</a>
        </div>
    </div>
</body>
</html>
