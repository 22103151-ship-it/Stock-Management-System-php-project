<?php
session_start();
include 'config.php';

$order_id = (int)($_GET['order'] ?? 0);
if (!$order_id) {
    header('Location: home.php');
    exit;
}

// Get order details
$stmt = $conn->prepare("
    SELECT go.*, gc.name as customer_name, gc.phone as customer_phone 
    FROM guest_orders go 
    JOIN guest_customers gc ON go.guest_id = gc.id 
    WHERE go.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: home.php');
    exit;
}

// Get order items
$items_stmt = $conn->prepare("
    SELECT goi.*, p.name as product_name 
    FROM guest_order_items goi 
    JOIN products p ON goi.product_id = p.id 
    WHERE goi.guest_order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$invoice_no = 'INV-GUEST-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
$order_date = date('d M Y, h:i A', strtotime($order['order_date']));
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পেমেন্ট সফল হয়েছে - Invoice #<?php echo $invoice_no; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        
        /* Success Banner */
        .success-banner {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            border-radius: 20px 20px 0 0;
            padding: 40px;
            text-align: center;
            color: #000;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: #00cc66;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0,255,136,0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(0,255,136,0); }
        }
        .success-banner h1 { font-size: 2rem; margin-bottom: 5px; }
        .success-banner h2 { font-size: 1.5rem; font-weight: 500; opacity: 0.9; }
        .success-banner p { margin-top: 10px; opacity: 0.8; }
        
        /* Invoice Card */
        .invoice-card {
            background: #fff;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .invoice-header {
            background: #f8f9fa;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #e0e0e0;
        }
        .invoice-title h3 { color: #333; font-size: 1.3rem; }
        .invoice-title p { color: #666; font-size: 0.9rem; }
        .invoice-number {
            text-align: right;
        }
        .invoice-number h4 { color: #00cc66; font-size: 1.1rem; }
        .invoice-number p { color: #666; font-size: 0.85rem; }
        
        .invoice-body { padding: 30px; }
        
        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .info-block h5 { color: #999; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 5px; }
        .info-block p { color: #333; font-weight: 600; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-paid { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #1a1a2e;
            color: #fff;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        .items-table th:first-child { border-radius: 8px 0 0 8px; }
        .items-table th:last-child { border-radius: 0 8px 8px 0; text-align: right; }
        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .items-table td:last-child { text-align: right; font-weight: 600; }
        .items-table tbody tr:hover { background: #f8f9fa; }
        
        /* Summary */
        .summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row.discount { color: #e65100; }
        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00cc66;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #00cc66;
        }
        
        /* Footer */
        .invoice-footer {
            background: #1a1a2e;
            color: #fff;
            padding: 25px 30px;
            text-align: center;
        }
        .invoice-footer p { opacity: 0.7; font-size: 0.9rem; margin-bottom: 15px; }
        .action-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary { background: linear-gradient(135deg, #00ff88, #00cc66); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,255,136,0.4); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }
        
        /* Print Styles */
        @media print {
            body { background: #fff; padding: 0; }
            .success-banner { background: #00cc66 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .invoice-footer { display: none; }
            .no-print { display: none; }
        }
        
        @media (max-width: 600px) {
            .customer-info { grid-template-columns: 1fr; }
            .invoice-header { flex-direction: column; gap: 15px; text-align: center; }
            .invoice-number { text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success Banner -->
        <div class="success-banner">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>পেমেন্ট সফল হয়েছে!</h1>
            <h2>Payment Successful</h2>
            <p>আপনার অর্ডার সফলভাবে সম্পন্ন হয়েছে</p>
        </div>
        
        <!-- Invoice Card -->
        <div class="invoice-card">
            <div class="invoice-header">
                <div class="invoice-title">
                    <h3>📦 Stock Management System</h3>
                    <p>Guest Order Invoice</p>
                </div>
                <div class="invoice-number">
                    <h4><?php echo $invoice_no; ?></h4>
                    <p><?php echo $order_date; ?></p>
                </div>
            </div>
            
            <div class="invoice-body">
                <!-- Customer Info -->
                <div class="customer-info">
                    <div class="info-block">
                        <h5>Customer Name / গ্রাহকের নাম</h5>
                        <p><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    </div>
                    <div class="info-block">
                        <h5>Phone Number / ফোন নম্বর</h5>
                        <p><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    </div>
                    <div class="info-block">
                        <h5>Payment Method / পেমেন্ট মাধ্যম</h5>
                        <p><i class="fas fa-mobile-alt"></i> bKash / SSLCommerz</p>
                    </div>
                    <div class="info-block">
                        <h5>Status / অবস্থা</h5>
                        <span class="status-badge <?php echo $order['status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>">
                            <i class="fas fa-<?php echo $order['status'] === 'paid' ? 'check-circle' : 'clock'; ?>"></i>
                            <?php echo $order['status'] === 'paid' ? 'Paid / পরিশোধিত' : ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product / পণ্য</th>
                            <th>Qty / পরিমাণ</th>
                            <th>Unit Price / একক মূল্য</th>
                            <th>Total / মোট</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sl = 1; foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?> pcs</td>
                            <td>৳<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>৳<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Summary -->
                <div class="summary">
                    <div class="summary-row">
                        <span>Total Stocks / মোট স্টক</span>
                        <span><strong><?php echo $order['total_stocks']; ?></strong> pcs</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal / উপ-মোট</span>
                        <span>৳<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row discount">
                        <span><i class="fas fa-tag"></i> Discount / ছাড় (৳1000 × <?php echo floor($order['total_stocks'] / 100); ?>)</span>
                        <span>-৳<?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total Paid / মোট পরিশোধ</span>
                        <span>৳<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="invoice-footer">
                <p>ধন্যবাদ আমাদের সাথে কেনাকাটা করার জন্য! Thank you for shopping with us!</p>
                <div class="action-buttons">
                    <a href="home.php" class="btn btn-primary no-print">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary no-print">
                        <i class="fas fa-print"></i> Print Invoice
                    </button>
                    <button onclick="downloadInvoice()" class="btn btn-secondary no-print">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function downloadInvoice() {
            window.print();
        }
    </script>
</body>
</html>
