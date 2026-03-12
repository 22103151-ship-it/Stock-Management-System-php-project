<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট বাতিল</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family:'Poppins', sans-serif; background:#fff1f2; margin:0; padding:20px; }
        .wrap { max-width:480px; margin:0 auto; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 28px rgba(0,0,0,0.1); text-align:center; }
        h1 { margin:0 0 10px 0; color:#b91c1c; }
        .icon { font-size:48px; color:#b91c1c; }
        a { text-decoration:none; color:#2563eb; font-weight:600; display:inline-block; margin-top:10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <h1>পেমেন্ট বাতিল হয়েছে</h1>
    <p>আপনি পেমেন্ট সম্পন্ন করেননি। আবার চেষ্টা করতে পারেন।</p>
    <a href="pending_orders.php">« Back to Make Order</a>
</div>
</body>
</html>
