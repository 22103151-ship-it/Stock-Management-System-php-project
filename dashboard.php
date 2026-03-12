<?php
session_start();

include '../config.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Check if customer_id is set
if (!isset($_SESSION['customer_id'])) {
    // Try to get customer_id from user_id
    $user_id = $_SESSION['user_id'];
    $customer_stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
    $customer_stmt->bind_param("i", $user_id);
    $customer_stmt->execute();
    $customer_stmt->bind_result($customer_id);
    if ($customer_stmt->fetch()) {
        $_SESSION['customer_id'] = $customer_id;
    } else {
        // Customer record not found, redirect to login
        header("Location: ../login.php");
        exit;
    }
    $customer_stmt->close();
}

include '../includes/notification_functions.php';

// Get customer info
$customer_id = $_SESSION['customer_id'];
$customer_query = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
$customer = $customer_query->fetch_assoc();

if (!$customer) {
    // Customer not found, redirect to login
    header("Location: ../login.php");
    exit;
}

// Get dashboard statistics
$product_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock > 0")->fetch_assoc()['total'] ?? 0;

// Get customer order statistics
$order_stats = $conn->query("
    SELECT
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
    FROM customer_orders
    WHERE customer_id = $customer_id
")->fetch_assoc();

// Get unread notifications count
$unread_notifications = getUnreadNotificationCount($customer_id, $conn);

// Get recent notifications
$recent_notifications = getCustomerNotifications($customer_id, 5, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>

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

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: linear-gradient(135deg, rgb(199, 90, 0), rgb(138, 55, 0));
            color: rgb(24, 24, 24);
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 800;
            color: rgb(20, 20, 20);
        }

        .dashboard-header p {
            margin: 10px 0 0;
            opacity: 0.95;
            font-size: 1.1rem;
            color: rgb(30, 30, 30);
        }

        .header-chat-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chat-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: rgb(20, 20, 20);
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .chat-action-btn:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
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
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .card-details .card-count {
            font-size: 2rem;
            font-weight: 700;
            margin: 5px 0;
            color: var(--text-color);
        }

        .stat-card .card-footer {
            margin-top: auto;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .notifications-section {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .notifications-section h2 {
            margin: 0 0 20px 0;
            color: var(--text-color);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: background 0.2s;
        }

        .notification-item.unread {
            background: rgba(52, 152, 219, 0.05);
            border-color: var(--accent-color);
        }

        .notification-item.read {
            background: var(--secondary-bg);
        }

        .notification-content p {
            margin: 0 0 5px 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }

        .mark-read-btn {
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.2s;
        }

        .mark-read-btn:hover {
            background: #27ae60;
        }

        .no-notifications {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }

        .no-notifications i {
            font-size: 3rem;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .logout-btn {
            background: var(--danger-color) !important;
        }

        .logout-btn:hover {
            background: #c0392b !important;
        }

        /* Quick Actions Styles */
        .quick-actions {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px var(--shadow-color);
            margin-top: 30px;
        }

        .quick-actions h2 {
            margin: 0 0 20px 0;
            color: var(--text-color);
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            justify-items: center;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9, var(--accent-color));
        }

        .action-btn i {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
        }

        .action-btn span {
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            line-height: 1.2;
            display: block;
        }

        .action-btn.logout-btn {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        .action-btn.logout-btn:hover {
            background: linear-gradient(135deg, #c0392b, var(--danger-color));
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        @media (max-width: 600px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .action-btn {
                width: 100px;
                height: 100px;
            }
            .action-btn i {
                font-size: 1.5rem;
            }
            .action-btn span {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

    <script>
        function markAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const notificationItem = document.querySelector(`[onclick="markAsRead(${notificationId})"]`).closest('.notification-item');
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                    notificationItem.querySelector('.mark-read-btn').remove();

                    // Update badge count
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 1) {
                            badge.textContent = currentCount - 1;
                        } else {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

    <div class="dashboard-header">
        <h1>Welcome to Customer Portal</h1>
        <p>Hello <?php echo htmlspecialchars($customer['name']); ?>, manage your orders and browse our products</p>
        <?php 
        $customer_type = isset($customer['customer_type']) ? $customer['customer_type'] : 'pro';
        if ($customer_type === 'vip'): 
        ?>
        <div style="display: inline-block; background: linear-gradient(135deg, #ffd700, #ff8c00); color: #000; padding: 8px 20px; border-radius: 25px; font-weight: 600; margin-top: 10px;">
            <i class="fas fa-crown"></i> VIP Customer • 10% Off All Products • 20% Off on 70+ Stocks
        </div>
        <?php else: ?>
        <div style="display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 8px 20px; border-radius: 25px; font-weight: 600; margin-top: 10px;">
            <i class="fas fa-star"></i> Pro Customer • 5% Off All Products • 15% Off on 50+ Stocks
        </div>
        <?php endif; ?>
        <div class="header-chat-actions">
            <a class="chat-action-btn" href="support.php?type=staff"><i class="fa-solid fa-headset"></i> Chat with Staff</a>
        </div>
    </div>

    <div class="stats-grid">
        <a href="products.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: var(--accent-color);"><i class="fa-solid fa-box"></i></div>
                <div class="card-details">
                    <h3>Available Products</h3>
                    <p class="card-count"><?php echo $product_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                Browse Products <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="my_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: var(--warning-color);"><i class="fa-solid fa-shopping-cart"></i></div>
                <div class="card-details">
                    <h3>My Orders</h3>
                    <p class="card-count"><?php echo $order_stats['total_orders']; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Order History <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="pending_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: var(--danger-color);"><i class="fa-solid fa-clock"></i></div>
                <div class="card-details">
                    <h3>Make Order</h3>
                    <p class="card-count"><?php echo $order_stats['pending_orders']; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Order Cart <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="ai_assistant.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: var(--success-color);"><i class="fa-solid fa-robot"></i></div>
                <div class="card-details">
                    <h3>AI Assistant</h3>
                    <p class="card-count">Chat Now</p>
                </div>
            </div>
            <div class="card-footer">
                Get Instant Help <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

    </div>

    <div class="content-grid">
        <!-- Recent Notifications -->
        <div class="notifications-section">
            <h2><i class="fas fa-bell"></i> Recent Notifications <?php if ($unread_notifications > 0): ?><span class="notification-badge"><?php echo $unread_notifications; ?></span><?php endif; ?></h2>
            <div class="notifications-list">
                <?php if (empty($recent_notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-inbox"></i>
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-content">
                                <p><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                <span class="notification-time"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></span>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="products.php" class="action-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Browse Products</span>
                </a>
                <a href="place_order.php" class="action-btn">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Place New Order</span>
                </a>
                <a href="ai_assistant.php" class="action-btn">
                    <i class="fa-solid fa-robot"></i>
                    <span>AI Assistant</span>
                </a>
                <a href="profile.php" class="action-btn">
                    <i class="fa-solid fa-user-edit"></i>
                    <span>My Profile</span>
                </a>
                <a href="support.php" class="action-btn">
                    <i class="fa-solid fa-headset"></i>
                    <span>Customer Support</span>
                </a>
                <a href="../logout.php" class="action-btn logout-btn">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>