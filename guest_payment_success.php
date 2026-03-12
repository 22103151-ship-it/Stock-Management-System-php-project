<?php
session_start();
include 'config.php';

// Check if this is a POST request from SSLCommerz
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Direct access - check if there's an order in session or GET
    $order_id = (int)($_GET['order'] ?? 0);
    if ($order_id) {
        header('Location: guest_order_success.php?order=' . $order_id);
        exit;
    }
    // Show friendly error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Access - Stock Management System</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .error-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 50px; text-align: center; color: #fff; max-width: 450px; }
            .error-icon { font-size: 60px; color: #ffc107; margin-bottom: 20px; }
            h1 { margin-bottom: 15px; }
            p { color: rgba(255,255,255,0.7); margin-bottom: 25px; }
            .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00ff88, #00cc66); color: #000; text-decoration: none; border-radius: 10px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            <h1>Invalid Access</h1>
            <p>This page can only be accessed after completing a payment through SSLCommerz.</p>
            <a href="home.php" class="btn">← Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$tran_id = $_POST['tran_id'] ?? '';
$val_id = $_POST['val_id'] ?? '';
$status = $_POST['status'] ?? '';
$order_id = (int)($_POST['value_a'] ?? 0);
$guest_id = (int)($_POST['value_b'] ?? 0);

if ($status === 'VALID' || $status === 'VALIDATED') {
    // Update order status
    $stmt = $conn->prepare("UPDATE guest_orders SET status = 'paid', payment_method = 'bKash/SSLCommerz' WHERE id = ? AND tran_id = ?");
    $stmt->bind_param("is", $order_id, $tran_id);
    $stmt->execute();
    
    // Deduct stock
    $items = $conn->prepare("SELECT product_id, quantity FROM guest_order_items WHERE guest_order_id = ?");
    $items->bind_param("i", $order_id);
    $items->execute();
    $result = $items->get_result();
    
    while ($item = $result->fetch_assoc()) {
        $stock_upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stock_upd->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
        $stock_upd->execute();
    }
    
    $_SESSION['success'] = 'Payment successful! Your order #' . $order_id . ' has been placed.';
    
    // Redirect to success page (same directory)
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    header('Location: ' . $base_path . '/guest_order_success.php?order=' . $order_id);
    exit;
}

$_SESSION['error'] = 'Payment validation failed';
$base_path = dirname($_SERVER['SCRIPT_NAME']);
header('Location: ' . $base_path . '/home.php');
exit;
