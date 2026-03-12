<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
require_once '../includes/sslcommerz_helper.php';
require_once '../includes/sslcommerz_config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer info
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_membership'])) {
    $amount = (float)($_POST['amount'] ?? 100);
    
    if ($amount < 100) {
        $message = 'Minimum membership fee is ৳100';
        $message_type = 'error';
    } else {
        // Create membership payment record
        $tran_id = 'MEM' . $customer_id . 'T' . time();
        
        $ins = $conn->prepare("INSERT INTO membership_payments (customer_id, amount, tran_id, status) VALUES (?, ?, ?, 'pending')");
        $ins->bind_param("ids", $customer_id, $amount, $tran_id);
        $ins->execute();
        $payment_id = $conn->insert_id;
        
        $base = sslcommerz_base_url();
        $successUrl = $base . '/customer/membership_success.php';
        $failUrl = $base . '/customer/membership_fail.php';
        $cancelUrl = $base . '/customer/membership_cancel.php';
        
        $payload = [
            'store_id' => (string)$SSLCOMMERZ_STORE_ID,
            'store_passwd' => (string)$SSLCOMMERZ_STORE_PASS,
            'total_amount' => $amount,
            'currency' => (string)$SSLCOMMERZ_CURRENCY,
            'tran_id' => $tran_id,
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'cancel_url' => $cancelUrl,
            'shipping_method' => 'NO',
            'product_name' => 'Membership Fee',
            'product_category' => 'Membership',
            'product_profile' => 'general',
            'cus_name' => $customer['name'],
            'cus_email' => $customer['email'] ?? ($customer['phone'] . '@customer.local'),
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_postcode' => '1200',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $customer['phone'],
            'value_a' => (string)$customer_id,
            'value_b' => (string)$payment_id,
            'value_c' => 'membership',
            'multi_card_name' => 'bkash',
        ];
        
        $init = sslcommerz_init_payment($payload, (bool)$SSLCOMMERZ_SANDBOX);
        
        if (!empty($init['ok']) && !empty($init['gateway_url'])) {
            header('Location: ' . $init['gateway_url']);
            exit;
        }
        
        $message = 'Payment gateway error: ' . ($init['error'] ?? 'Unknown error');
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership - Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f4f7fc; min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #ff9800, #f57c00); color: #fff; padding: 25px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; }
        .back-btn { color: #fff; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; }
        
        .status-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-bottom: 25px; text-align: center; }
        .status-icon { font-size: 4rem; margin-bottom: 15px; }
        .status-icon.member { color: #27ae60; }
        .status-icon.non-member { color: #e74c3c; }
        .status-text { font-size: 1.3rem; font-weight: 600; margin-bottom: 10px; }
        .status-desc { color: #666; }
        
        .benefits-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .benefits-card h2 { margin-bottom: 20px; color: #333; }
        .benefit-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; }
        .benefit-item:last-child { border-bottom: none; }
        .benefit-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .benefit-icon.green { background: #e8f5e9; color: #27ae60; }
        .benefit-icon.blue { background: #e3f2fd; color: #2196f3; }
        .benefit-icon.orange { background: #fff3e0; color: #ff9800; }
        .benefit-text h4 { margin-bottom: 3px; }
        .benefit-text p { color: #666; font-size: 0.9rem; }
        
        .purchase-card { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .purchase-card h2 { margin-bottom: 20px; }
        .amount-options { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .amount-option { background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .amount-option:hover, .amount-option.selected { background: rgba(255,255,255,0.3); border-color: #fff; }
        .amount-option input { display: none; }
        .amount-value { font-size: 1.3rem; font-weight: 700; }
        .amount-label { font-size: 0.8rem; opacity: 0.8; }
        .custom-amount { display: flex; gap: 10px; margin-bottom: 20px; }
        .custom-amount input { flex: 1; padding: 12px 15px; border: none; border-radius: 8px; font-size: 1rem; }
        .btn-purchase { background: #fff; color: #667eea; border: none; padding: 15px 40px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-purchase:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .message.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .message.success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-crown"></i> Membership</h1>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="status-card">
            <?php if ($customer['is_member']): ?>
                <div class="status-icon member"><i class="fas fa-crown"></i></div>
                <div class="status-text">You are a Premium Member!</div>
                <div class="status-desc">Membership fee paid: ৳<?php echo number_format($customer['membership_fee_paid'], 2); ?></div>
            <?php else: ?>
                <div class="status-icon non-member"><i class="fas fa-user"></i></div>
                <div class="status-text">You are not a member yet</div>
                <div class="status-desc">Buy membership to unlock exclusive discounts!</div>
            <?php endif; ?>
        </div>
        
        <div class="benefits-card">
            <h2><i class="fas fa-gift"></i> Membership Benefits</h2>
            <div class="benefit-item">
                <div class="benefit-icon green"><i class="fas fa-percent"></i></div>
                <div class="benefit-text">
                    <h4>15% Discount</h4>
                    <p>On orders of 30+ stocks (min 10 per product)</p>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon blue"><i class="fas fa-tags"></i></div>
                <div class="benefit-text">
                    <h4>20% Discount</h4>
                    <p>On orders of 100+ stocks (min 10 per product)</p>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon orange"><i class="fas fa-box"></i></div>
                <div class="benefit-text">
                    <h4>Lower Minimum Order</h4>
                    <p>Order as few as 30 stocks (vs 100 for guests)</p>
                </div>
            </div>
        </div>
        
        <?php if (!$customer['is_member']): ?>
        <div class="purchase-card">
            <h2><i class="fas fa-shopping-cart"></i> Buy Membership</h2>
            <form method="POST">
                <div class="amount-options">
                    <label class="amount-option selected">
                        <input type="radio" name="amount" value="100" checked>
                        <div class="amount-value">৳100</div>
                        <div class="amount-label">Basic</div>
                    </label>
                    <label class="amount-option">
                        <input type="radio" name="amount" value="500">
                        <div class="amount-value">৳500</div>
                        <div class="amount-label">Standard</div>
                    </label>
                    <label class="amount-option">
                        <input type="radio" name="amount" value="1000">
                        <div class="amount-value">৳1000</div>
                        <div class="amount-label">Premium</div>
                    </label>
                </div>
                <button type="submit" name="buy_membership" class="btn-purchase">
                    <i class="fas fa-credit-card"></i> Pay with bKash
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.querySelectorAll('.amount-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.amount-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    </script>
</body>
</html>
