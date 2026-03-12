<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/notification_functions.php';

// Fetch all pending and confirmed customer orders for staff to manage
$stmt = $conn->prepare("SELECT co.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, p.name AS product_name 
                        FROM customer_orders co 
                        JOIN customers c ON co.customer_id = c.id 
                        JOIN products p ON co.product_id = p.id 
                        WHERE co.status IN ('pending', 'confirmed', 'shipped') 
                        ORDER BY co.order_date DESC LIMIT 200");
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - Staff</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f4f7fc;
            --main-color: #1a2a47;
            --accent-color: #ff9800;
            --card-bg: #ffffff;
            --text-color: #34495e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--main-color);
            color: white;
            border-radius: 10px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.25);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .orders-table {
            width: 100%;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: var(--main-color);
            color: white;
            font-weight: 600;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background: #d4edda;
            color: #155724;
        }

        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.85rem;
            margin-right: 5px;
            transition: transform 0.2s, opacity 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .btn-approve {
            background: var(--success-color);
            color: white;
        }

        .btn-ship {
            background: #17a2b8;
            color: white;
        }

        .btn-deliver {
            background: var(--accent-color);
            color: white;
        }

        .no-orders {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-orders i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Customer Orders</h1>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($msg === 'approved'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Order approved successfully!</div>
        <?php elseif ($msg === 'shipped'): ?>
            <div class="alert alert-success"><i class="fas fa-truck"></i> Order marked as shipped!</div>
        <?php elseif ($msg === 'delivered'): ?>
            <div class="alert alert-success"><i class="fas fa-box-open"></i> Order delivered successfully!</div>
        <?php elseif ($msg === 'error'): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Something went wrong. Please try again.</div>
        <?php endif; ?>

        <div class="orders-table">
            <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>৳<?php echo number_format($order['price'], 2); ?></td>
                        <td>৳<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                        <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" action="approve_customer_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="action-btn btn-approve"><i class="fas fa-check"></i> Approve</button>
                                </form>
                            <?php elseif ($order['status'] === 'confirmed'): ?>
                                <form method="POST" action="ship_customer_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="action-btn btn-ship"><i class="fas fa-truck"></i> Ship</button>
                                </form>
                            <?php elseif ($order['status'] === 'shipped'): ?>
                                <form method="POST" action="deliver_customer_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="action-btn btn-deliver"><i class="fas fa-box-open"></i> Deliver</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-orders">
                <i class="fas fa-inbox"></i>
                <h3>No orders to process</h3>
                <p>All customer orders have been handled.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
