<?php

session_start();
require '../config.php';
require '../includes/notification_functions.php';
require '../includes/sslcommerz_helper.php';
require '../includes/sslcommerz_config.php';

ensure_customer_payments_table($conn);

// SSLCommerz typically sends POST back
$tran_id = isset($_POST['tran_id']) ? (string)$_POST['tran_id'] : '';
$val_id = isset($_POST['val_id']) ? (string)$_POST['val_id'] : '';
$status = isset($_POST['status']) ? (string)$_POST['status'] : '';
$order_id = isset($_POST['value_a']) ? (int)$_POST['value_a'] : 0;
$customer_id = isset($_POST['value_b']) ? (int)$_POST['value_b'] : 0;

// Fallback lookup from our mapping table
if ($order_id <= 0 && $tran_id !== '') {
    $lookup = $conn->prepare("SELECT order_id, customer_id FROM customer_payments WHERE tran_id = ? LIMIT 1");
    if ($lookup) {
        $lookup->bind_param('s', $tran_id);
        $lookup->execute();
        $row = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if ($row) {
            $order_id = (int)$row['order_id'];
            $customer_id = (int)$row['customer_id'];
        }
    }
}

$error = '';

if ($tran_id === '' || $val_id === '' || $order_id <= 0) {
    $error = 'Invalid payment response.';
} else {
    // Validate with SSLCommerz validator
    $validation = sslcommerz_validate_transaction($val_id, (bool)$SSLCOMMERZ_SANDBOX, (string)$SSLCOMMERZ_STORE_ID, (string)$SSLCOMMERZ_STORE_PASS);
    if (!$validation['ok']) {
        $error = 'Payment validation failed: ' . ($validation['error'] ?? 'Unknown error');
    } else {
        $v = $validation['data'];
        $vStatus = strtoupper((string)($v['status'] ?? ''));
        if (!in_array($vStatus, ['VALID', 'VALIDATED'])) {
            $error = 'Payment not valid. Status: ' . ($v['status'] ?? '');
        } else {
            // Update payment mapping
            $updPay = $conn->prepare("UPDATE customer_payments SET status='success', val_id=?, bank_tran_id=?, card_type=? WHERE tran_id = ? LIMIT 1");
            if ($updPay) {
                $bank_tran_id = (string)($v['bank_tran_id'] ?? '');
                $card_type = (string)($v['card_type'] ?? ($v['card_issuer'] ?? ''));
                $updPay->bind_param('ssss', $val_id, $bank_tran_id, $card_type, $tran_id);
                $updPay->execute();
                $updPay->close();
            }

            // Confirm order (same behavior as your simulated pay_success.php)
            if ($customer_id <= 0 && isset($_SESSION['customer_id'])) {
                $customer_id = (int)$_SESSION['customer_id'];
            }

            $stmt = $conn->prepare("SELECT id, status FROM customer_orders WHERE id = ?" . ($customer_id > 0 ? " AND customer_id = ?" : "") . " LIMIT 1");
            if ($stmt) {
                if ($customer_id > 0) {
                    $stmt->bind_param('ii', $order_id, $customer_id);
                } else {
                    $stmt->bind_param('i', $order_id);
                }
                $stmt->execute();
                $order = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($order && $order['status'] === 'pending') {
                    $upd = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ?" . ($customer_id > 0 ? " AND customer_id = ?" : ""));
                    if ($upd) {
                        if ($customer_id > 0) {
                            $upd->bind_param('ii', $order_id, $customer_id);
                        } else {
                            $upd->bind_param('i', $order_id);
                        }
                        $upd->execute();
                        $upd->close();

                        if ($customer_id > 0) {
                            sendOrderStatusUpdateNotification($customer_id, $order_id, 'confirmed', $conn);
                            createNotificationDot('customer_order_confirmed', 'customer', 'admin', $order_id, 'blue', 'Customer paid (bKash/SSLCommerz) and confirmed an order', $conn);
                        }
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট সফল (SSLCommerz)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family:'Poppins', sans-serif; background:#f5f7fb; margin:0; padding:20px; }
        .wrap { max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 28px rgba(0,0,0,0.1); text-align:center; }
        h1 { margin:0 0 10px 0; }
        .ok { color:#16a34a; }
        .bad { color:#b91c1c; }
        .icon { font-size:48px; margin-bottom:10px; }
        a { text-decoration:none; color:#2563eb; font-weight:600; }
        .meta { margin-top:10px; color:#555; font-size:14px; }
    </style>
</head>
<body>
<div class="wrap">
    <?php if ($error): ?>
        <div class="icon bad"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h1 class="bad">পেমেন্ট ভ্যালিড হয়নি</h1>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="pending_orders.php">« Back to Make Order</a>
    <?php else: ?>
        <div class="icon ok"><i class="fa-solid fa-circle-check"></i></div>
        <h1 class="ok">পেমেন্ট সফল</h1>
        <p>আপনার পেমেন্ট সফল হয়েছে (bKash via SSLCommerz)। অর্ডার Confirmed হয়েছে।</p>
        <div class="meta">
            <div>Tran ID: <?php echo htmlspecialchars($tran_id); ?></div>
            <div>Order ID: <?php echo (int)$order_id; ?></div>
        </div>
        <a href="my_orders.php">আমার অর্ডার দেখুন</a>
    <?php endif; ?>
</div>
</body>
</html>
