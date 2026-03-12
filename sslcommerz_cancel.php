<?php

session_start();
require '../config.php';
require '../includes/sslcommerz_helper.php';

ensure_customer_payments_table($conn);

$tran_id = isset($_POST['tran_id']) ? (string)$_POST['tran_id'] : '';
if ($tran_id !== '') {
    $upd = $conn->prepare("UPDATE customer_payments SET status='cancelled' WHERE tran_id = ? LIMIT 1");
    if ($upd) {
        $upd->bind_param('s', $tran_id);
        $upd->execute();
        $upd->close();
    }
}

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট ক্যানসেল (SSLCommerz)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family:'Poppins', sans-serif; background:#f5f7fb; margin:0; padding:20px; }
        .wrap { max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 28px rgba(0,0,0,0.1); text-align:center; }
        h1 { margin:0 0 10px 0; color:#f59e0b; }
        .icon { font-size:48px; color:#f59e0b; margin-bottom:10px; }
        a { text-decoration:none; color:#2563eb; font-weight:600; }
        .meta { margin-top:10px; color:#555; font-size:14px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="icon"><i class="fa-solid fa-ban"></i></div>
    <h1>পেমেন্ট ক্যানসেল</h1>
    <p>আপনি পেমেন্ট ক্যানসেল করেছেন।</p>
    <?php if ($tran_id): ?><div class="meta">Tran ID: <?php echo htmlspecialchars($tran_id); ?></div><?php endif; ?>
    <a href="pending_orders.php">« Back to Make Order</a>
</div>
</body>
</html>
