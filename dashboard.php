<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// NOTE: Ensure your paths for config.php and header.php are correct relative to this file.
include '../config.php';
include '../includes/header.php'; // Assuming your header includes the opening <html>, <head>, and <body> tags

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
} else {
    // Fallback if database connection fails
    $user_count = $supplier_count = $product_count = $order_count = 0;
    $delivered_count = $pending_count = $returned_count = 0;
}
?>

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
            background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url('../assets/images/home-bg.jpg') center/cover fixed;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url('../assets/images/home-bg.jpg') center/cover fixed;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px;
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
            color: #fff;
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .back-btn,
        .primary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--main-color);
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .primary-btn {
            background: var(--accent-color);
        }

        .back-btn:hover,
        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.16);
        }

        .back-btn:hover { background: #22365c; }
        .primary-btn:hover { background: #e68a00; }

        .back-btn i,
        .primary-btn i {
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
        .icon-analytics { background-color: #9c88ff; } /* Purple */

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
</style>
    <div class="dashboard-header">
        <div class="header-title">
            <h1>ADMIN DASHBOARD</h1>
            <p>Control panel and insights</p>
        </div>
        <div class="header-actions">
            <a href="customer_invoices.php" class="primary-btn"><i class="fa-solid fa-file-invoice-dollar"></i> Customer Invoices</a>
            <a href="add_product.php" class="primary-btn"><i class="fa-solid fa-plus"></i> Add Product</a>
        </div>
    </div>

    <div class="stats-grid">
        <a href="manage_users.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-users"><i class="fa-solid fa-users"></i></div>
                <div class="card-details">
                    <h3>Total Users</h3>
                    <p class="card-count"><?php echo $user_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                Manage Users <i class="fa-solid fa-arrow-right"></i>
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
        
        <a href="products.php" class="stat-card">
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

        <a href="sell_product.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-sell"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="card-details">
                    <h3>Sell a Product</h3>
                    <p class="card-count">&nbsp;</p> </div>
            </div>
            <div class="card-footer">
                Go to Sell Page <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="purchase_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-orders"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="card-details">
                    <h3>Total Orders</h3>
                    <p class="card-count"><?php echo $order_count; ?></p>
                </div>
            </div>
             <div class="card-footer">
                View All Orders <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="create_purchase_order.php" class="stat-card" style="border:2px solid #ff9800;">
            <div class="card-content">
                <div class="card-icon icon-orders" style="background:#ff9800;"><i class="fa-solid fa-truck-ramp-box"></i></div>
                <div class="card-details">
                    <h3>Order from Supplier</h3>
                    <p class="card-count">Create PO</p>
                </div>
            </div>
             <div class="card-footer" style="color:#ff9800;">
                Create Purchase Order <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="delivered_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-delivered"><i class="fa-solid fa-circle-check"></i></div>
                <div class="card-details">
                    <h3>Delivered Orders</h3>
                    <p class="card-count"><?php echo $delivered_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Delivered <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="pending_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-pending"><i class="fa-solid fa-clock"></i></div>
                <div class="card-details">
                    <h3>Pending Orders</h3>
                    <p class="card-count"><?php echo $pending_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Pending <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="returned_orders.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-returned"><i class="fa-solid fa-rotate-left"></i></div>
                <div class="card-details">
                    <h3>Returned Orders</h3>
                    <p class="card-count"><?php echo $returned_count; ?></p>
                </div>
            </div>
            <div class="card-footer">
                View Returned <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="analytics.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon icon-analytics"><i class="fa-solid fa-chart-line"></i></div>
                <div class="card-details">
                    <h3>Analytics</h3>
                    <p class="card-count">Dashboard</p>
                </div>
            </div>
            <div class="card-footer">
                View Analytics <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="customers.php" class="stat-card">
            <div class="card-content">
                <div class="card-icon" style="background: linear-gradient(135deg, #ffd700, #ff8c00);"><i class="fa-solid fa-user-tie"></i></div>
                <div class="card-details">
                    <h3>Customer Overview</h3>
                    <p class="card-count">Pro & VIP</p>
                </div>
            </div>
            <div class="card-footer">
                View Customers <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
    </div>
</div>

<footer>
    <p>© <?php echo date("Y"); ?> Stock Management System. All rights reserved.</p>
</footer>

</body>
</html>