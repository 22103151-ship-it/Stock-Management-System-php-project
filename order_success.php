<?php
session_start();
include '../config.php';

$order_id = (int)($_GET['order_id'] ?? 0);

if (!$order_id) {
    header('Location: dashboard.php');
    exit;
}

// Validate SSLCommerz response
$tran_id = $_POST['tran_id'] ?? '';
$val_id = $_POST['val_id'] ?? '';
$amount = $_POST['amount'] ?? 0;
$status = $_POST['status'] ?? '';

if ($status == 'VALID' || $status == 'VALIDATED') {
    // Update order status
    $update = $conn->prepare("UPDATE customer_orders SET status = 'confirmed' WHERE id = ?");
    if (!$update) {
        die('Database error: failed to prepare update statement.');
    }
    $update->bind_param("i", $order_id);
    $update->execute();
    
    // Get order details
    $order_stmt = $conn->prepare("SELECT * FROM customer_orders WHERE id = ?");
    if (!$order_stmt) {
        die('Database error: failed to prepare select statement.');
    }
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Stock Management</title>
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
        .success-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00ff88, #00cc66);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: #000;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .order-number {
            background: rgba(0,255,136,0.1);
            padding: 15px 25px;
            border-radius: 10px;
            display: inline-block;
            margin: 20px 0;
        }
        .btn-dashboard {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 10px;
        }
        .btn-dashboard:hover {
            background: linear-gradient(135deg, #00cc66, #00ff88);
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h2 class="mb-3">Payment Successful!</h2>
        <p class="text-muted">Your order has been placed successfully and is being processed.</p>
        
        <?php if (isset($order)): ?>
            <div class="row mt-3 text-start">
                <div class="col-6">
                    <small class="text-muted">Amount Paid</small>
                    <h5>৳<?php echo number_format($order['price'], 2); ?></h5>
                </div>
                <div class="col-6">
                    <small class="text-muted">Status</small>
                    <h5><span class="badge bg-success">Paid</span></h5>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="my_orders.php" class="btn btn-dashboard me-2">
                <i class="fas fa-list me-2"></i>View Orders
            </a>
            <a href="dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>
</body>
</html>
