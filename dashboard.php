<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

// NOTE: Ensure your paths for config.php and header.php are correct relative to this file.
include '../config.php';
include '../includes/notification_functions.php'; // Add notification functions

// Check if $conn is successfully established before querying
if (isset($conn)) {
    // Fetch counts from database
    $user_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;
    $supplier_count = $conn->query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'] ?? 0;
    $product_count = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'] ?? 0;
    $order_count = $conn->query("SELECT COUNT(*) as total FROM purchase_orders")->fetch_assoc()['total'] ?? 0;
    $delivered_count = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='delivered'")->fetch_assoc()['total'] ?? 0;
    $pending_count = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
    $returned_count = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='returned'")->fetch_assoc()['total'] ?? 0;
    $processing_customer_orders = $conn->query("SELECT COUNT(*) as total FROM customer_orders WHERE status='shipped'")->fetch_assoc()['total'] ?? 0;
    $pending_customer_orders = $conn->query("SELECT COUNT(*) as total FROM customer_orders WHERE status='pending'")->fetch_assoc()['total'] ?? 0;

    // Get notification dots for staff
    $staff_dots = getActiveNotificationDots('staff', $conn);
    $has_notifications = !empty($staff_dots);
} else {
    // Fallback if database connection fails
    $user_count = $supplier_count = $product_count = $order_count = 0;
    $delivered_count = $pending_count = $returned_count = 0;
    $processing_customer_orders = 0;
    $has_notifications = false;
    $staff_dots = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* --- Modern Flat Design (Navy/Gold Theme) --- */

        /* CSS Variables */
        :root {
            --bg-color: #f4f7fc; /* Light background */
            --main-color: #1a2a47; /* Deep Navy Blue (Primary) */
            --accent-color: #ff9800; /* Vibrant Orange/Gold (Accent) */
            --card-bg: #ffffff;
            --text-color: #34495e; /* Darker text */
            --text-light: #7f8c8d; /* Muted secondary text */
            --border-color: #e6e9ed;
            --shadow-color: rgba(0, 0, 0, 0.08);
            --font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .dashboard-header {
            margin-bottom: 35px;
            border-left: 5px solid var(--accent-color);
            padding-left: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header-title h1 {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--main-color);
            margin: 0;
        }

        .header-title p {
            font-size: 1rem;
            color: var(--text-light);
            margin-top: 5px;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #e74c3c;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.16);
        }

        .logout-btn i {
            font-size: 0.95rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px; /* Slightly sharper corners */
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 25px;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: var(--text-color);
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card .card-icon {
            font-size: 1.8rem;
            height: 55px;
            width: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }
        
        .stat-card .card-details {
            display: flex;
            flex-direction: column;
        }

        .stat-card .card-details h3 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .card-details .card-count {
            margin: 3px 0 0;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--main-color);
        }

        .card-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color); /* Dashed separator for a modern touch */
            text-align: right;
        }
        
        .card-footer a {
            text-decoration: none;
            color: var(--accent-color);
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }
        
        .card-footer a i {
            margin-left: 5px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover .card-footer a i {
            transform: translateX(3px);
        }


        /* Icon Colors - High contrast colors */
        .icon-users { background-color: #3498db; } /* Blue */
        .icon-suppliers { background-color: #9b59b6; } /* Amethyst */
        .icon-products { background-color: #f39c12; } /* Orange */
        .icon-orders { background-color: #2ecc71; } /* Emerald */
        .icon-delivered { background-color: #1abc9c; } /* Turquoise */
        .icon-pending { background-color: #e67e22; } /* Carrot */
        .icon-sell { background-color: #e74c3c; } /* Alizarin */
        .icon-returned { background-color: #95a5a6; } /* Concrete */

        /* Responsive */
        @media (max-width: 600px) {
            .main-content {
                padding: 15px;
            }
            .dashboard-header h1 {
                font-size: 2rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Footer */
        footer {
            background-color: var(--main-color);
            color: #fff;
            text-align: center;
            padding: 15px 0;
            margin-top: 30px;
            font-size: 0.85rem;
            font-weight: 400;
        }
        /* Notifications Section */
        .notifications-section {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .notifications-section h2 {
            color: var(--main-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-item {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: all 0.3s ease;
        }

        .notification-item.blue { border-left: 4px solid #3498db; }
        .notification-item.green { border-left: 4px solid #27ae60; }
        .notification-item.yellow { border-left: 4px solid #f39c12; }
        .notification-item.red { border-left: 4px solid #e74c3c; }

        .notification-content p {
            margin: 0 0 5px 0;
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }

        .action-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .action-btn:hover {
            background: #e67e22;
        }

        /* Product Request Section */
        .product-request-section {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .product-request-section h2 {
            color: var(--main-color);
            margin-bottom: 15px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-suggestion {
            background: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: var(--text-color);
            line-height: 1.5;
        }

        .request-actions {
            text-align: center;
        }

        .request-btn {
            background: linear-gradient(135deg, var(--accent-color), #e67e22);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }

        .request-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }

        .request-form {
            margin-top: 20px;
            padding: 20px;
            background: var(--secondary-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .request-form h3 {
            color: var(--main-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .submit-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: #27ae60;
        }

        .cancel-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .cancel-btn:hover {
            background: #c0392b;
        }    </style>
</head>
<body>

<div class="main-content">
    <div class="dashboard-header">
        <div class="header-title">
            <h1>STAFF DASHBOARD</h1>
            <p>Tools, orders, and requests</p>
        </div>
        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <div class="stats-grid">
        <a href="items.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-products"><i class="fa-solid fa-box-archive"></i></div>
                <div class="card-details">
                    <h3>Total Products</h3>
                    <p class="card-count"><?php echo $product_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Products <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="suppliers.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-suppliers"><i class="fa-solid fa-truck-field"></i></div>
                <div class="card-details">
                    <h3>Total Suppliers</h3>
                    <p class="card-count"><?php echo $supplier_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                Manage Suppliers <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="sell_product.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-sell"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="card-details">
                    <h3>Sell a Product</h3>
                    <p class="card-count">&nbsp;</p>
                </div>
            </div>
            <div class="card-footer">
                Go to Sell Page <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="processing_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-delivered"><i class="fa-solid fa-truck"></i></div>
                <div class="card-details">
                    <h3>Shipped Orders</h3>
                    <p class="card-count"><?php echo $processing_customer_orders; ?></p>
                </div>
            </div>
            <div class="card-footer">
                Handle Deliveries <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="customer_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: #e74c3c;"><i class="fa-solid fa-shopping-cart"></i></div>
                <div class="card-details">
                    <h3>Pending Customer Orders</h3>
                    <p class="card-count"><?php echo $pending_customer_orders; ?></p>
                </div>
            </div>
            <div class="card-footer">
                Approve & Process Orders <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="customers.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="fa-solid fa-users"></i></div>
                <div class="card-details">
                    <h3>Customer List</h3>
                    <p class="card-count">Pro & VIP</p>
                </div>
            </div>
            <div class="card-footer">
                View All Customers <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
    </div>

    <!-- Notifications Section -->
    <?php if ($has_notifications): ?>
    <div class="notifications-section">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <div class="notifications-list">
            <?php foreach ($staff_dots as $notification): ?>
                <div class="notification-item <?php echo $notification['dot_color']; ?>">
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <span class="notification-time"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></span>
                    </div>
                    <?php if ($notification['notification_type'] === 'admin_approval'): ?>
                        <button class="action-btn" onclick="handleAdminApproval(<?php echo $notification['reference_id']; ?>)">
                            <i class="fas fa-check"></i> Acknowledge
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Product Request Section -->
    <div class="product-request-section">
        <h2><i class="fas fa-exclamation-triangle"></i> Product Requests</h2>
        <p class="ai-suggestion">🤖 <strong>AI Assistant:</strong> If you notice any products are running low or out of stock, use the button below to request replenishment from suppliers.</p>

        <div class="request-actions">
            <button class="request-btn" onclick="showProductRequestForm()">
                <i class="fas fa-plus-circle"></i> Request Product Replenishment
            </button>
        </div>

        <!-- Product Request Form (Hidden by default) -->
        <div id="productRequestForm" class="request-form" style="display: none;">
            <h3>Select Product to Request</h3>
            <form method="POST" action="process_product_request.php">
                <div class="form-group">
                    <label for="product_id">Product:</label>
                    <select name="product_id" id="product_id" required>
                        <option value="">Select a product...</option>
                        <?php
                        $products = $conn->query("SELECT id, name, stock FROM products ORDER BY name");
                        while ($product = $products->fetch_assoc()) {
                            $stock_status = $product['stock'] <= 5 ? ' (Low Stock)' : '';
                            echo "<option value='{$product['id']}'>{$product['name']}{$stock_status}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity_needed">Quantity Needed:</label>
                    <input type="number" name="quantity_needed" id="quantity_needed" min="1" required>
                </div>

                <div class="form-group">
                    <label for="urgency">Urgency Level:</label>
                    <select name="urgency" id="urgency" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <textarea name="reason" id="reason" placeholder="Please explain why this product is needed..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                    <button type="button" class="cancel-btn" onclick="hideProductRequestForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer>
    <p>© <?php echo date("Y"); ?> Stock Management System. All rights reserved.</p>
</footer>

<script>
function showProductRequestForm() {
    document.getElementById('productRequestForm').style.display = 'block';
    document.querySelector('.request-btn').style.display = 'none';
}

function hideProductRequestForm() {
    document.getElementById('productRequestForm').style.display = 'none';
    document.querySelector('.request-btn').style.display = 'inline-block';
}

function handleAdminApproval(notificationId) {
    if (confirm('Mark this notification as acknowledged?')) {
        fetch('acknowledge_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error acknowledging notification');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    // You could add AJAX call here to refresh notifications without full page reload
}, 30000);
</script>

</body>
</html>