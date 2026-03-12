<?php
session_start();
include '../config.php';
require_once '../includes/sslcommerz_config.php';
require_once '../includes/sslcommerz_helper.php';

$error = '';
$success = false;
$customer_data = null;

// Handle SSLCommerz POST response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tran_id = $_POST['tran_id'] ?? '';
    $val_id = $_POST['val_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $customer_type = $_POST['value_a'] ?? 'pro';
    
    // Check if we have pending registration in database
    $pending_stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE tran_id = ? LIMIT 1");
    $pending_stmt->bind_param("s", $tran_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    $reg = $pending_result->fetch_assoc();
    $pending_stmt->close();
    
    if (!$reg) {
        $error = 'Invalid or expired registration session.';
    } elseif ($status !== 'VALID' && $status !== 'VALIDATED') {
        $error = 'Payment was not successful. Status: ' . $status;
    } else {
        // Validate with SSLCommerz
        $validation = sslcommerz_validate_transaction($val_id, (bool)$SSLCOMMERZ_SANDBOX, $SSLCOMMERZ_STORE_ID, $SSLCOMMERZ_STORE_PASS);
        
        if (!$validation['ok']) {
            $error = 'Payment validation failed: ' . ($validation['error'] ?? 'Unknown error');
        } else {
            $v = $validation['data'];
            $vStatus = strtoupper($v['status'] ?? '');
            
            if (!in_array($vStatus, ['VALID', 'VALIDATED'])) {
                $error = 'Payment not valid. Status: ' . ($v['status'] ?? 'Unknown');
            } else {
                // Payment is valid - create user and customer
                // $reg already fetched from database above
                $conn->begin_transaction();
                
                try {
                    // Create user account - users table has: name, email, password, role
                    $user_stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
                    $user_stmt->bind_param("sss", $reg['name'], $reg['email'], $reg['password']);
                    $user_stmt->execute();
                    $user_id = $conn->insert_id;
                    
                    // Generate invoice number
                    $invoice_prefix = $reg['customer_type'] === 'vip' ? 'VIP' : 'PRO';
                    $invoice_number = $invoice_prefix . '-' . date('Ymd') . '-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                    
                    // Create customer profile
                    $cust_stmt = $conn->prepare("INSERT INTO customers (user_id, name, email, phone, nid, customer_type, registration_fee, registration_date, registration_invoice, is_member) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1)");
                    $cust_stmt->bind_param("isssssds", $user_id, $reg['name'], $reg['email'], $reg['phone'], $reg['nid'], $reg['customer_type'], $reg['fee'], $invoice_number);
                    $cust_stmt->execute();
                    $customer_id = $conn->insert_id;
                    
                    // Delete pending registration
                    $del_stmt = $conn->prepare("DELETE FROM pending_registrations WHERE tran_id = ?");
                    $del_stmt->bind_param("s", $tran_id);
                    $del_stmt->execute();
                    $del_stmt->close();
                    
                    $conn->commit();
                    
                    // Set session for auto-login
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $reg['email']; // Use email as username
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['customer_id'] = $customer_id;
                    $_SESSION['customer_type'] = $reg['customer_type'];
                    
                    // Prepare success data
                    $customer_data = [
                        'id' => $customer_id,
                        'name' => $reg['name'],
                        'email' => $reg['email'],
                        'phone' => $reg['phone'],
                        'nid' => $reg['nid'],
                        'type' => $reg['customer_type'],
                        'fee' => $reg['fee'],
                        'invoice' => $invoice_number,
                        'tran_id' => $tran_id,
                        'val_id' => $val_id
                    ];
                    
                    $success = true;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    }
} else {
    $error = 'Invalid access method.';
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Registration Successful!' : 'Registration Error'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .result-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00ff88, #00cc66);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            animation: pulse 2s infinite;
        }
        .success-icon i {
            font-size: 50px;
            color: #fff;
        }
        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff4757, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .error-icon i {
            font-size: 50px;
            color: #fff;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        h1 {
            color: #fff;
            margin-bottom: 15px;
        }
        h1.success { color: #00ff88; }
        h1.error { color: #ff4757; }
        .message {
            color: rgba(255,255,255,0.8);
            margin-bottom: 25px;
        }
        .invoice-box {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
        }
        .invoice-header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px dashed rgba(255,255,255,0.2);
            margin-bottom: 15px;
        }
        .invoice-header h3 {
            color: #ffd700;
            margin-bottom: 5px;
        }
        .invoice-header .invoice-no {
            color: #00ff88;
            font-size: 18px;
            font-weight: 700;
        }
        .invoice-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .invoice-row:last-child {
            border-bottom: none;
        }
        .invoice-label {
            color: rgba(255,255,255,0.6);
        }
        .invoice-value {
            color: #fff;
            font-weight: 500;
        }
        .customer-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-pro {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        .badge-vip {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
        }
        .total-row {
            background: rgba(0,255,136,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        .total-row .invoice-value {
            color: #00ff88;
            font-size: 24px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s;
        }
        .btn-dashboard {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
        }
        .btn-print {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        @media print {
            body { background: #fff; }
            .result-card { box-shadow: none; background: #fff; }
            .btn { display: none; }
            * { color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="result-card">
        <?php if ($success && $customer_data): ?>
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success">পেমেন্ট সফল হয়েছে!</h1>
            <p class="message">অভিনন্দন! আপনি সফলভাবে <?php echo $customer_data['type'] === 'vip' ? 'VIP' : 'Pro'; ?> Customer হিসেবে রেজিস্টার করেছেন।</p>
            
            <div class="invoice-box">
                <div class="invoice-header">
                    <h3><i class="fas fa-file-invoice"></i> Registration Invoice</h3>
                    <div class="invoice-no"><?php echo htmlspecialchars($customer_data['invoice']); ?></div>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Customer Type</span>
                    <span class="invoice-value">
                        <span class="customer-type-badge badge-<?php echo $customer_data['type']; ?>">
                            <?php echo $customer_data['type'] === 'vip' ? '<i class="fas fa-crown"></i> VIP' : '<i class="fas fa-star"></i> PRO'; ?>
                        </span>
                    </span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Customer ID</span>
                    <span class="invoice-value">#<?php echo str_pad($customer_data['id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Name</span>
                    <span class="invoice-value"><?php echo htmlspecialchars($customer_data['name']); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Phone</span>
                    <span class="invoice-value"><?php echo htmlspecialchars($customer_data['phone']); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Email</span>
                    <span class="invoice-value"><?php echo htmlspecialchars($customer_data['email']); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">NID</span>
                    <span class="invoice-value"><?php echo htmlspecialchars($customer_data['nid']); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Transaction ID</span>
                    <span class="invoice-value" style="font-size: 12px;"><?php echo htmlspecialchars($customer_data['tran_id']); ?></span>
                </div>
                
                <div class="invoice-row">
                    <span class="invoice-label">Date</span>
                    <span class="invoice-value"><?php echo date('d M Y, h:i A'); ?></span>
                </div>
                
                <div class="total-row">
                    <div class="invoice-row" style="border: none; margin: 0; padding: 0;">
                        <span class="invoice-label" style="color: #fff;">Registration Fee Paid</span>
                        <span class="invoice-value">৳<?php echo number_format($customer_data['fee'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <a href="dashboard.php" class="btn btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            
        <?php else: ?>
            <div class="error-icon">
                <i class="fas fa-times"></i>
            </div>
            <h1 class="error">Registration Failed</h1>
            <p class="message"><?php echo htmlspecialchars($error); ?></p>
            <a href="register_pro.php" class="btn btn-dashboard">
                <i class="fas fa-redo"></i> Try Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
