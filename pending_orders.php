<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php';

// Resolve customer_id from session or fallback to lookup via user_id
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $customer_id = (int)$row['id'];
        $_SESSION['customer_id'] = $customer_id;
    } else {
        header("Location: register.php");
        exit;
    }
}

$pending_orders = [];
$query_error = '';
if ($customer_id && isset($conn)) {
    $stmt = $conn->prepare(
        "SELECT co.id, co.quantity, co.price, co.status, co.order_date, co.created_at,
                p.name AS product_name, p.image AS product_image
         FROM customer_orders co
         JOIN products p ON co.product_id = p.id
         WHERE co.customer_id = ? AND co.status = 'pending'
         ORDER BY co.order_date DESC"
    );

    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $pending_orders[] = $row;
            }
        } else {
            $query_error = 'Unable to load pending orders right now.';
        }
        $stmt->close();
    } else {
        $query_error = 'Unable to prepare pending orders query.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders - Customer</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bg-color: #f4f7fc;
            --main-color: #2c3e50;
            --accent-color: #3498db;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --text-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--main-color), var(--accent-color));
            color: white;
            border-radius: 8px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .orders-container {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 20px;
            border-left: 4px solid var(--warning-color);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id { font-size: 1.1rem; font-weight: 600; color: var(--text-color); }
        .order-date { color: #666; font-size: 0.9rem; }

        .order-details { margin-bottom: 15px; }

        .order-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .order-detail-row strong { color: var(--text-color); }

        .order-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-color);
            text-align: right;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }

        .status-info {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-danger { background: var(--danger-color); color: white; }
        .btn-danger:hover { background: #c0392b; }

        .no-orders {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .order-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .order-detail-row { flex-direction: column; gap: 5px; }
        }

        .back-navigation { margin-bottom: 20px; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .back-btn:hover { background: #2980b9; color: white; }
        .back-btn i { font-size: 0.9rem; }

        .serial-chip {
            background: #eef3fb;
            color: var(--main-color);
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .product-thumb {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f2f5;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Make Order</h1>
</div>

<div class="back-navigation">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="products.php" class="back-btn" style="margin-left:10px; background: var(--main-color);">
        <i class="fas fa-box"></i> Browse Products
    </a>
</div>

<?php if (empty($pending_orders)): ?>
    <div class="no-orders">
        <i class="fa-solid fa-clock" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
        <p>Your cart is empty right now.</p>
        <p>Please add items to your cart to proceed with your order.</p>
        <a href="products.php" class="action-btn" style="background: var(--accent-color); color: white; margin-top: 20px;">
            <i class="fa-solid fa-plus"></i> Browse Products
        </a>
    </div>
<?php else: ?>
    <div class="orders-container">
        <?php $serial = count($pending_orders); foreach ($pending_orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <div class="order-date"><?php echo date('M d, Y H:i', strtotime($order['created_at'] ?? $order['order_date'])); ?></div>
                </div>

                <div class="status-info">
                    <span class="serial-chip">Serial: <?php echo $serial--; ?></span>
                    <span style="margin-left:10px;"><i class="fa-solid fa-cart-plus"></i> This item is in your cart. Confirm to submit the order.</span>
                </div>

                <div class="order-details">
                    <div class="order-detail-row" style="align-items:center; gap:10px;">
                        <strong>Image:</strong>
                        <span>
                            <?php if (!empty($order['product_image'])): ?>
                                <img class="product-thumb" src="../assets/images/<?php echo htmlspecialchars($order['product_image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                            <?php else: ?>
                                <div class="product-thumb" style="display:flex; align-items:center; justify-content:center; color:#999; font-size:20px;">
                                    <i class="fa-solid fa-box"></i>
                                </div>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="order-detail-row">
                        <strong>Product:</strong>
                        <span><?php echo htmlspecialchars($order['product_name']); ?></span>
                    </div>
                    <div class="order-detail-row">
                        <strong>Quantity:</strong>
                        <span><?php echo $order['quantity']; ?> units</span>
                    </div>
                    <div class="order-detail-row">
                        <strong>Unit Price:</strong>
                        <span>৳<?php echo number_format($order['price'], 2); ?></span>
                    </div>
                    <div class="order-total">
                        Total: ৳<?php echo number_format($order['price'] * $order['quantity'], 2); ?>
                    </div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <form method="GET" action="pay.php" style="margin:0;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="action-btn" style="background: var(--success-color); color: white;">
                            <i class="fa-solid fa-check"></i> Confirm & Pay
                        </button>
                    </form>
                    <form method="POST" action="cancel_order.php" style="margin:0;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="action-btn btn-danger">
                            <i class="fa-solid fa-times"></i> Cancel Order
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($query_error)): ?>
    <div class="no-orders" style="color:#e74c3c;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
        <p><?php echo htmlspecialchars($query_error); ?></p>
    </div>
<?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>