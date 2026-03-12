<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supplier') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/notification_functions.php'; // Add notification functions
include '../includes/supplier_helpers.php';

$supplier_id = getResolvedSupplierId($conn);

// Fetch counts for supplier dashboard
$total_orders = 0;
$pending_orders = 0;
$delivered_orders = 0;
$returned_orders = 0;

if ($supplier_id > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE supplier_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $total_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE supplier_id = ? AND status='pending'");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $pending_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE supplier_id = ? AND status='delivered'");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $delivered_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE supplier_id = ? AND status='returned'");
    if ($stmt) {
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $returned_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    }
} else {
    // Fallback: show all if supplier_id cannot be resolved
    $total_orders = $conn->query("SELECT COUNT(*) as total FROM purchase_orders")->fetch_assoc()['total'] ?? 0;
    $pending_orders = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
    $delivered_orders = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='delivered'")->fetch_assoc()['total'] ?? 0;
    $returned_orders = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='returned'")->fetch_assoc()['total'] ?? 0;
}

// Get notification dots for supplier
$supplier_dots = getActiveNotificationDots('supplier', $conn);
$has_notifications = !empty($supplier_dots);
?>

<div class="main-content">
    <div class="dashboard-header">
        <h1 class="dashboard-title">SUPPLIER DASHBOARD</h1>
        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <div class="dashboard-cards">
        
        <a href="my_orders.php" class="card dashboard-card card-total">
            <div class="card-icon-wrapper" style="color: #3498db;">
                <div class="card-icon">🛒</div>
                <div class="card-content">
                    <p class="card-label">TOTAL ORDERS</p>
                    <p class="card-count"><?php echo $total_orders; ?></p>
                </div>
            </div>
            <div class="card-link" style="color: #3498db;">
                View All Orders <span style="font-size: 1.2em; margin-left: 5px;">→</span>
            </div>
        </a>

        <a href="delivered_orders.php?status=delivered" class="card dashboard-card card-delivered">
            <div class="card-icon-wrapper" style="color: #2ecc71;">
                <div class="card-icon">✅</div>
                <div class="card-content">
                    <p class="card-label">DELIVERED ORDERS</p>
                    <p class="card-count"><?php echo $delivered_orders; ?></p>
                </div>
            </div>
            <div class="card-link" style="color: #2ecc71;">
                View Delivered <span style="font-size: 1.2em; margin-left: 5px;">→</span>
            </div>
        </a>
        
        <a href="pending_orders.php?status=pending" class="card dashboard-card card-pending">
            <div class="card-icon-wrapper" style="color: #e67e22;">
                <div class="card-icon">⏳</div>
                <div class="card-content">
                    <p class="card-label">PENDING ORDERS</p>
                    <p class="card-count"><?php echo $pending_orders; ?></p>
                </div>
            </div>
            <div class="card-link" style="color: #e67e22;">
                View Pending <span style="font-size: 1.2em; margin-left: 5px;">→</span>
            </div>
        </a>

        <!-- NEW Returned Products Card -->
        <a href="returned_orders.php?status=returned" class="card dashboard-card card-returned">
            <div class="card-icon-wrapper" style="color: #e74c3c;">
                <div class="card-icon">↩️</div>
                <div class="card-content">
                    <p class="card-label">RETURNED PRODUCTS</p>
                    <p class="card-count"><?php echo $returned_orders; ?></p>
                </div>
            </div>
            <div class="card-link" style="color: #e74c3c;">
                View Returned <span style="font-size: 1.2em; margin-left: 5px;">→</span>
            </div>
        </a>

    </div>

    <!-- Notifications Section -->
    <?php if ($has_notifications): ?>
    <div class="notifications-section">
        <h2><i class="fas fa-bell"></i> Admin Orders & Notifications</h2>
        <div class="notifications-list">
            <?php foreach ($supplier_dots as $notification): ?>
                <div class="notification-item <?php echo $notification['dot_color']; ?>">
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <span class="notification-time"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></span>
                    </div>
                    <?php if ($notification['notification_type'] === 'admin_order_request'): ?>
                        <div class="response-actions">
                            <button class="response-btn accept-btn" onclick="respondToOrder(<?php echo $notification['reference_id']; ?>, 'accepted')">
                                <i class="fas fa-check"></i> Accept & Send
                            </button>
                            <button class="response-btn later-btn" onclick="respondToOrder(<?php echo $notification['reference_id']; ?>, 'later')">
                                <i class="fas fa-clock"></i> Send Later
                            </button>
                            <button class="response-btn cancel-btn" onclick="respondToOrder(<?php echo $notification['reference_id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Cannot Send
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
/* Theme Colors */
:root {
    --color-bg-light: #f5f5f5;
    --color-card-bg: #fff;
    --color-text-dark: #333;
    --color-shadow: rgba(0, 0, 0, 0.05);
    --color-footer-dark: #2c3e50;
}

/* General Body Styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif; 
    background: var(--color-bg-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: var(--color-text-dark);
}

/* Main Container */
.main-content {
    flex: 1;
    max-width: 1200px;
    width: 95%;
    margin: 40px auto; 
    padding: 20px;
    background: var(--color-card-bg); 
    border-radius: 8px;
    box-shadow: 0 0 15px var(--color-shadow);
    height: auto;
}

/* Dashboard Title */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin: 0 0 30px 0;
    padding: 0 20px;
}

.dashboard-title {
    font-weight: 800;
    font-size: 1.8rem;
    letter-spacing: 1px;
    color: var(--color-text-dark);
    margin: 0;
    text-align: left; 
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
    font-weight: 700;
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

/* Grid Layout */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* now 4 cards including returned products */
    gap: 25px;
    padding: 0 20px;
}

/* Individual Cards */
.card {
    background-color: var(--color-card-bg);
    border-radius: 6px;
    padding: 20px 20px;
    transition: box-shadow 0.3s;
    text-decoration: none;
    color: var(--color-text-dark);
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 1px 3px var(--color-shadow);
    display: flex;
    flex-direction: column;
    justify-content: space-between; 
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Top Section of Card (Icon and Text) */
.card-icon-wrapper {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.card-icon {
    font-size: 30px;
    margin-right: 15px; 
}

/* Metric Count */
.card-count {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.1;
    color: var(--color-text-dark); 
}

/* Descriptive Label */
.card-label {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0;
    color: #777; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Link/Action area */
.card-link {
    font-size: 0.9rem;
    font-weight: 500;
    padding-top: 15px;
    border-top: 1px solid rgba(0, 0, 0, 0.08); 
    transition: color 0.3s;
    text-align: right;
}

.card:hover .card-link {
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .dashboard-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .dashboard-cards { grid-template-columns: 1fr; }
    .main-content { margin: 20px auto; padding: 10px; }
}

/* Notifications Section */
.notifications-section {
    background: var(--color-card-bg);
    border-radius: 8px;
    padding: 25px;
    margin-top: 30px;
    box-shadow: 0 2px 10px var(--color-shadow);
}

.notifications-section h2 {
    color: var(--color-text-dark);
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
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.3s ease;
}

.notification-item.blue { border-left: 4px solid #3498db; }

.notification-content p {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
}

.notification-time {
    font-size: 0.8rem;
    color: #666;
}

.response-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.response-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.accept-btn {
    background: #27ae60;
    color: white;
}

.accept-btn:hover {
    background: #229954;
}

.later-btn {
    background: #f39c12;
    color: white;
}

.later-btn:hover {
    background: #e67e22;
}

.cancel-btn {
    background: #e74c3c;
    color: white;
}

.cancel-btn:hover {
    background: #c0392b;
}

/* Footer */
footer {
    background-color: var(--color-footer-dark);
    color: #fff;
    text-align: center;
    padding: 15px 0;
    font-size: 0.8rem;
    font-weight: 400;
}
</style>

<footer>
    <p>© <?php echo date("Y"); ?> Stock Management System. All rights reserved.</p>
</footer>

<script>
function respondToOrder(orderId, response) {
    let confirmMessage = '';
    switch(response) {
        case 'accepted':
            confirmMessage = 'Are you sure you want to accept this order and send the products?';
            break;
        case 'later':
            confirmMessage = 'Are you sure you want to send the products later?';
            break;
        case 'cancelled':
            confirmMessage = 'Are you sure you cannot fulfill this order?';
            break;
    }

    if (confirm(confirmMessage)) {
        fetch('respond_to_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId + '&response=' + response
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Response submitted successfully!');
                location.reload();
            } else {
                alert('Error submitting response: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting response');
        });
    }
}
</script>
