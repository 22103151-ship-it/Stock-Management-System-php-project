<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';
require '../includes/notification_functions.php';

$customer_id = $_SESSION['customer_id'] ?? null;
// Fallback lookup if session missed the customer_id but has user_id
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

$message = '';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order
$stmt = $conn->prepare("SELECT co.*, p.name AS product_name, p.price AS unit_price FROM customer_orders co JOIN products p ON co.product_id = p.id WHERE co.id = ? AND co.customer_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ii', $order_id, $customer_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $order = null;
    $message = 'Unable to load order for payment.';
}

if (!$order || $order['status'] !== 'pending' || !$customer_id) {
    header('Location: pending_orders.php');
    exit;
}

// Pricing
$subtotal = (float)$order['unit_price'] * (int)$order['quantity'];
$delivery_charge = 60; // flat delivery
$tax_rate = 0.05;      // 5% tax
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $delivery_charge + $tax_amount;

// Handle payment submission (simulated)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gateway = $_POST['gateway'] ?? '';
    $pay_action = $_POST['pay_action'] ?? '';

    if ($pay_action === 'cancel') {
        header('Location: pay_cancel.php?order_id=' . $order_id);
        exit;
    }

    if (!in_array($gateway, ['sslcommerz','stripe','paypal','bkash','nagad'])) {
        $message = 'একটি পেমেন্ট গেটওয়ে নির্বাচন করুন।';
    } else {
        // TODO: আসল গেটওয়ে রিডাইরেক্ট ইন্টিগ্রেট করুন
        header('Location: pay_success.php?order_id=' . $order_id . '&gateway=' . urlencode($gateway));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background:#f5f7fb; margin:0; padding:20px; }
        .wrap { max-width: 520px; margin: 0 auto; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 28px rgba(0,0,0,0.1); }
        h1 { margin:0 0 10px 0; font-size:22px; color:#333; }
        .row { display:flex; justify-content:space-between; margin:6px 0; color:#444; }
        .total { font-weight:700; font-size:18px; color:#2563eb; margin-top:10px; }
        .gateways { display:grid; gap:10px; margin:14px 0; }
        label { display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #e5e7eb; border-radius:10px; cursor:pointer; }
        input[type="radio"] { accent-color:#2563eb; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
        button { border:none; padding:10px 16px; border-radius:10px; font-weight:600; cursor:pointer; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { background:#e5e7eb; color:#374151; }
        .message { margin:10px 0; color:#b91c1c; font-weight:600; }
        .back { text-decoration:none; color:#2563eb; font-weight:600; display:inline-block; margin-top:10px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>পেমেন্ট নিশ্চিত করুন</h1>
    <div style="margin-bottom:12px; color:#555;">প্রোডাক্ট: <strong><?php echo htmlspecialchars($order['product_name']); ?></strong></div>
    <div class="row"><span>পরিমাণ</span><span><?php echo (int)$order['quantity']; ?></span></div>
    <div class="row"><span>একক মূল্য</span><span>৳<?php echo number_format($order['unit_price'], 2); ?></span></div>
    <div class="row"><span>সাবটোটাল</span><span>৳<?php echo number_format($subtotal, 2); ?></span></div>
    <div class="row"><span>ডেলিভারি চার্জ</span><span>৳<?php echo number_format($delivery_charge, 2); ?></span></div>
    <div class="row"><span>ট্যাক্স (5%)</span><span>৳<?php echo number_format($tax_amount, 2); ?></span></div>
    <div class="row total"><span>মোট</span><span>৳<?php echo number_format($total, 2); ?></span></div>

    <form method="POST">
        <div class="gateways">
            <label><input type="radio" name="gateway" value="sslcommerz">SSLCommerz</label>
            <label><input type="radio" name="gateway" value="stripe">Stripe</label>
            <label><input type="radio" name="gateway" value="paypal">PayPal</label>
            <label><input type="radio" name="gateway" value="bkash">bKash</label>
            <label><input type="radio" name="gateway" value="nagad">Nagad</label>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="actions">
            <button class="btn-primary" type="submit" name="pay_action" value="pay">Pay Now</button>
            <button class="btn-secondary" type="submit" name="pay_action" value="cancel">Payment Cancel</button>
        </div>
    </form>

    <a class="back" href="pending_orders.php">« Back to Make Order</a>
    <br>
    <a class="back" href="products.php">Browse Products</a>
</div>
</body>
</html>
