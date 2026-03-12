<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/notification_functions.php';

$customer_id = $_SESSION['customer_id'] ?? 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$gateway = isset($_GET['gateway']) ? $_GET['gateway'] : '';

$stmt = $conn->prepare("SELECT id, status FROM customer_orders WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || $order['status'] !== 'pending') {
    header('Location: my_orders.php');
    exit;
}

$upd = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ? AND customer_id = ?");
$upd->bind_param('ii', $order_id, $customer_id);
$upd->execute();

sendOrderStatusUpdateNotification($customer_id, $order_id, 'confirmed', $conn);
createNotificationDot('customer_order_confirmed', 'customer', 'admin', $order_id, 'blue', 'Customer confirmed an order', $conn);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট সফল</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family:'Poppins', sans-serif; background:#f5f7fb; margin:0; padding:20px; }
        .wrap { max-width:480px; margin:0 auto; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 28px rgba(0,0,0,0.1); text-align:center; }
        h1 { margin:0 0 10px 0; color:#16a34a; }
        .icon { font-size:48px; color:#16a34a; }
        a { text-decoration:none; color:#2563eb; font-weight:600; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="icon"><i class="fa-solid fa-circle-check"></i></div>
    <h1>পেমেন্ট সফল</h1>
    <p>গেটওয়ে: <?php echo htmlspecialchars($gateway ?: ''); ?></p>
    <p>আপনার পেমেন্ট সফল, অর্ডার নিশ্চিত হয়েছে। ডেলিভারি প্রসেসিং শুরু করতে অ্যাডমিন অনুমোদন দেবেন।</p>
    <a href="my_orders.php">আমার অর্ডার দেখুন</a>
</div>
</body>
</html>
