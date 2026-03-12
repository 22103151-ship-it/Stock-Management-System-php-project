<?php
session_start();
include '../config.php';

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id) {
    // Update order status to failed
    $update = $conn->prepare("UPDATE customer_orders SET status = 'payment_failed' WHERE id = ?");
    $update->bind_param("i", $order_id);
    $update->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Stock Management</title>
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
        .fail-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
        }
        .fail-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff4444, #cc0000);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: #fff;
        }
        .btn-retry {
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
    <div class="fail-card">
        <div class="fail-icon">
            <i class="fas fa-times"></i>
        </div>
        <h2 class="mb-3">Payment Failed</h2>
        <p class="text-muted">Unfortunately, your payment could not be processed. Please try again.</p>
        
        <div class="mt-4">
            <a href="products.php" class="btn btn-retry me-2">
                <i class="fas fa-redo me-2"></i>Try Again
            </a>
            <a href="dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>
</body>
</html>
