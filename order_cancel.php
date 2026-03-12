<?php
session_start();
include '../config.php';

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id) {
    // Update order status to cancelled
    $update = $conn->prepare("UPDATE customer_orders SET status = 'cancelled' WHERE id = ?");
    $update->bind_param("i", $order_id);
    $update->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled - Stock Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cancel-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
        }
        .cancel-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: #000;
        }
        .btn-continue {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="cancel-card">
        <div class="cancel-icon">
            <i class="fas fa-ban"></i>
        </div>
        <h2 class="mb-3">Order Cancelled</h2>
        <p class="text-muted">You have cancelled the payment. Your order has been cancelled.</p>
        
        <div class="mt-4">
            <a href="products.php" class="btn btn-continue me-2">
                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
            </a>
            <a href="dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>
</body>
</html>
