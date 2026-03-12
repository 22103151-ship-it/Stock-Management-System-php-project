<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php';

// Get customer_id (fallback lookup if session missing)
$customer_id = $_SESSION['customer_id'] ?? null;
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

// Get customer orders
$orders = [];
if (isset($conn) && $customer_id) {
    $stmt = $conn->prepare(
        "SELECT co.*, p.name as product_name, p.price as unit_price, p.image as product_image
         FROM customer_orders co
         JOIN products p ON co.product_id = p.id
         WHERE co.customer_id = ?
         ORDER BY co.order_date DESC"
    );
    if ($stmt) {
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
}
?>

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

        .orders-table {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text-color);
        }

        .orders-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-returned {
            background: #f8d7da;
            color: #721c24;
        }

        .status-shipped {
            background: #e0f2fe;
            color: #0f3d67;
        }

        .order-total {
            font-weight: 600;
            color: var(--accent-color);
        }

        .no-orders {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            font-size: 1.2rem;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        @media (max-width: 768px) {
            .orders-table {
                overflow-x: auto;
            }

            .orders-table table {
                min-width: 600px;
            }
        }

        .back-navigation {
            margin-bottom: 20px;
        }

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

        .back-btn:hover {
            background: #2980b9;
            color: white;
        }

        .back-btn i {
            font-size: 0.9rem;
        }
    </style>

    <div class="page-header">
        <h1>My Order History</h1>
    </div>

    <div class="back-navigation">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'received'): ?>
        <div class="message success-message" style="margin-bottom:16px;">Order marked as received successfully.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'invalid'): ?>
        <div class="message error-message" style="margin-bottom:16px;">Unable to mark as received.</div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fa-solid fa-shopping-cart" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="action-btn btn-success" style="margin-top: 20px;">
                <i class="fa-solid fa-plus"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $serial = count($orders); foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $serial--; ?></td>
                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td>
                                <?php if (!empty($order['product_image'])): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($order['product_image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; background:#f0f2f5;">
                                <?php else: ?>
                                    <div style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:6px; background:#f8fafc; color:#94a3b8;">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>৳<?php echo number_format($order['unit_price'], 2); ?></td>
                            <td class="order-total">৳<?php echo number_format($order['unit_price'] * $order['quantity'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <?php if ($order['status'] === 'confirmed'): ?>
                                    <div style="margin-top:6px;" class="status-badge status-pending">Awaiting admin grant</div>
                                <?php elseif ($order['status'] === 'shipped'): ?>
                                    <div style="margin-top:6px;" class="status-badge status-pending">Delivery processing</div>
                                <?php elseif ($order['status'] === 'delivered'): ?>
                                    <div style="margin-top:6px;" class="status-badge status-delivered">Received by you</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'] ?? ($order['created_at'] ?? 'now'))); ?></td>
                            <td>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="action-btn btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        <i class="fa-solid fa-times"></i> Cancel
                                    </button>
                                <?php elseif ($order['status'] === 'shipped'): ?>
                                    <form method="POST" action="confirm_received.php" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button class="action-btn btn-success" type="submit">
                                            <i class="fa-solid fa-check"></i> Received Product
                                        </button>
                                    </form>
                                <?php elseif ($order['status'] === 'delivered'): ?>
                                    <a class="action-btn btn-success" href="generate_invoice.php?order_id=<?php echo $order['id']; ?>" target="_blank">
                                        <i class="fa-solid fa-file-invoice"></i> Invoice
                                    </a>
                                    <button class="action-btn btn-success" onclick="returnOrder(<?php echo $order['id']; ?>)">
                                        <i class="fa-solid fa-undo"></i> Return
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // In a real system, you'd make an AJAX call to cancel the order
                alert('Order cancellation feature would be implemented here.');
            }
        }

        function returnOrder(orderId) {
            if (confirm('Are you sure you want to return this order?')) {
                // In a real system, you'd make an AJAX call to return the order
                alert('Order return feature would be implemented here.');
            }
        }
    </script>

</div>

<?php include '../includes/footer.php'; ?>