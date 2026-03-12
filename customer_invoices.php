<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../config.php';
require '../includes/header.php';

// Fetch all delivered customer orders for invoice viewing
$orders = [];
$stmt = $conn->prepare("
    SELECT co.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, 
           p.name AS product_name 
    FROM customer_orders co 
    JOIN customers c ON co.customer_id = c.id 
    JOIN products p ON co.product_id = p.id 
    ORDER BY co.order_date DESC 
    LIMIT 500
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Calculate totals
$total_revenue = 0;
$total_items = 0;
foreach ($orders as $order) {
    if ($order['status'] === 'delivered') {
        $total_revenue += $order['price'] * $order['quantity'];
        $total_items += $order['quantity'];
    }
}
?>

<style>
    header { background: linear-gradient(135deg, rgba(26,42,71,0.85), rgba(255,152,0,0.35)), url('../assets/images/home-bg.jpg'); background-size: cover; background-position: center; }
    body { background: linear-gradient(rgba(244,247,252,0.95), rgba(244,247,252,0.95)), url('../assets/images/home-bg.jpg'); background-size: cover; background-attachment: fixed; }
    
    .wrap { max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    .page-header { 
        margin-bottom: 25px; 
        padding: 20px 25px; 
        border-radius: 12px; 
        background: linear-gradient(135deg, #1a2a47, #2d4a7c); 
        color: #fff; 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .page-header h1 { margin: 0; font-size: 1.6rem; }
    .page-header a { 
        color: #fff; 
        text-decoration: none; 
        font-weight: 600; 
        background: rgba(255,255,255,0.15); 
        padding: 8px 16px; 
        border-radius: 8px; 
        transition: background 0.2s;
    }
    .page-header a:hover { background: rgba(255,255,255,0.25); }
    
    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-box {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        text-align: center;
    }
    
    .stat-box h3 {
        margin: 0 0 5px 0;
        font-size: 1.8rem;
        color: #1a2a47;
    }
    
    .stat-box p {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    .table-shell { 
        background: #fff; 
        border-radius: 14px; 
        padding: 15px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
    }
    
    .filter-bar {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-bar select, .filter-bar input {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.9rem;
    }
    
    table { width: 100%; border-collapse: collapse; }
    
    thead tr { background: linear-gradient(135deg, #1a2a47, #2d4a7c); color: #fff; }
    
    th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #eef2f7; font-size: 14px; }
    th { font-weight: 600; }
    
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:hover { background: #f0f4ff; }
    
    .status { 
        padding: 5px 12px; 
        border-radius: 20px; 
        font-size: 0.75rem; 
        font-weight: 600; 
        text-transform: uppercase; 
    }
    
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-shipped { background: #fef9c3; color: #854d0e; }
    .status-delivered { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    
    .invoice-btn {
        padding: 6px 12px;
        background: #ff9800;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-block;
    }
    
    .invoice-btn:hover { background: #e68900; }
    
    .no-data {
        text-align: center;
        padding: 50px;
        color: #666;
    }
    
    .customer-info {
        font-size: 0.85rem;
    }
    .customer-info small {
        color: #666;
        display: block;
    }
</style>

<div class="wrap">
    <div class="page-header">
        <h1><i class="fa-solid fa-file-invoice-dollar"></i> Customer Invoices</h1>
        <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <div class="stats-summary">
        <div class="stat-box">
            <h3><?php echo count($orders); ?></h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $total_items; ?></h3>
            <p>Items Sold (Delivered)</p>
        </div>
        <div class="stat-box">
            <h3>৳<?php echo number_format($total_revenue, 2); ?></h3>
            <p>Revenue (Delivered)</p>
        </div>
    </div>
    
    <div class="table-shell">
        <div class="filter-bar">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
            </select>
            <input type="text" id="searchInput" placeholder="Search customer or product..." onkeyup="filterTable()">
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="no-data">
                <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                <h3>No customer orders yet</h3>
                <p>Customer orders will appear here once placed.</p>
            </div>
        <?php else: ?>
        <table id="ordersTable">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr data-status="<?php echo $order['status']; ?>">
                    <td><strong>INV-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                    <td class="customer-info">
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                        <small><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td>৳<?php echo number_format($order['price'], 2); ?></td>
                    <td><strong>৳<?php echo number_format($order['price'] * $order['quantity'], 2); ?></strong></td>
                    <td><span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                    <td>
                        <a href="generate_customer_invoice.php?order_id=<?php echo $order['id']; ?>" class="invoice-btn" target="_blank">
                            <i class="fa-solid fa-print"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function filterTable() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status').toLowerCase();
        const text = row.textContent.toLowerCase();
        
        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchInput || text.includes(searchInput);
        
        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
    });
}
</script>

<?php include '../includes/footer.php'; ?>
