<?php
session_start();
include '../config.php';
require_once '../includes/sslcommerz_config.php';
require_once '../includes/sslcommerz_helper.php';

$errors = [];
$success = false;

// VIP Customer Registration Fee
$registration_fee = 500;
$customer_type = 'vip';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    
    if (empty($phone) || !preg_match('/^01[3-9]\d{8}$/', $phone)) {
        $errors[] = 'Valid 11-digit phone number is required (e.g., 01712345678)';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($nid) || strlen($nid) < 10) {
        $errors[] = 'Valid NID number is required (minimum 10 digits)';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if phone/email/nid already exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM customers WHERE phone = ? OR email = ? OR nid = ?");
        $check->bind_param("sss", $phone, $email, $nid);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'Phone, Email or NID already registered';
        }
        $check->close();
    }
    
    if (empty($errors)) {
        // Generate transaction ID
        $tran_id = 'VIP' . time() . rand(1000, 9999);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Store registration data in database (session doesn't persist across SSLCommerz redirect)
        $pending_stmt = $conn->prepare("INSERT INTO pending_registrations (tran_id, name, email, phone, nid, password, customer_type, fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $pending_stmt->bind_param("sssssssd", $tran_id, $name, $email, $phone, $nid, $hashed_password, $customer_type, $registration_fee);
        $pending_stmt->execute();
        $pending_stmt->close();
        
        // Prepare SSLCommerz payment
        $base = sslcommerz_base_url();
        
        $payload = [
            'store_id' => $SSLCOMMERZ_STORE_ID,
            'store_passwd' => $SSLCOMMERZ_STORE_PASS,
            'total_amount' => $registration_fee,
            'currency' => $SSLCOMMERZ_CURRENCY,
            'tran_id' => $tran_id,
            'success_url' => $base . '/customer/registration_success.php',
            'fail_url' => $base . '/customer/registration_fail.php',
            'cancel_url' => $base . '/customer/register_vip.php?cancelled=1',
            'shipping_method' => 'NO',
            'product_name' => 'VIP Customer Registration',
            'product_category' => 'Membership',
            'product_profile' => 'general',
            'cus_name' => $name,
            'cus_email' => $email,
            'cus_phone' => $phone,
            'cus_add1' => 'Bangladesh',
            'cus_city' => 'Dhaka',
            'cus_postcode' => '1200',
            'cus_country' => 'Bangladesh',
            'value_a' => 'vip',
            'value_b' => $tran_id,
            'multi_card_name' => 'bkash,nagad',
        ];
        
        $result = sslcommerz_init_payment($payload, (bool)$SSLCOMMERZ_SANDBOX);
        
        if (!empty($result['ok']) && !empty($result['gateway_url'])) {
            header('Location: ' . $result['gateway_url']);
            exit;
        } else {
            $errors[] = 'Payment gateway error: ' . ($result['error'] ?? 'Please try again');
        }
    }
}

// Check for cancelled payment
if (isset($_GET['cancelled'])) {
    $errors[] = 'Payment was cancelled. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Customer Registration - ৳500</title>
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
        .register-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 2px solid rgba(255, 215, 0, 0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header .badge {
            display: inline-block;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #ffd700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        .benefits {
            background: rgba(255, 215, 0, 0.15);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .benefits h4 {
            color: #ffd700;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .benefits ul {
            list-style: none;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
        }
        .benefits li {
            padding: 5px 0;
        }
        .benefits li i {
            color: #ffd700;
            margin-right: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(255, 215, 0, 0.2);
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255,255,255,0.1);
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .error-box {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid #ff4757;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .error-box p {
            color: #ff6b7a;
            font-size: 13px;
            margin: 5px 0;
        }
        .payment-info {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .payment-info h3 {
            color: #000;
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .payment-info p {
            color: rgba(0,0,0,0.7);
            font-size: 13px;
        }
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }
        .payment-methods span {
            background: rgba(0,0,0,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            color: #000;
            font-size: 12px;
            font-weight: 600;
        }
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            color: #fff;
        }
        .pro-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .pro-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="header">
            <span class="badge"><i class="fas fa-crown"></i> VIP CUSTOMER</span>
            <h1>VIP Customer Registration</h1>
            <p>Register as VIP Customer and get premium discounts!</p>
        </div>
        
        <div class="benefits">
            <h4><i class="fas fa-crown"></i> VIP Customer Benefits:</h4>
            <ul>
                <li><i class="fas fa-check"></i> 10% discount on every product</li>
                <li><i class="fas fa-check"></i> Extra 20% discount on 70+ stocks order</li>
                <li><i class="fas fa-check"></i> Minimum only 10 stocks per product</li>
                <li><i class="fas fa-check"></i> VIP priority customer support</li>
                <li><i class="fas fa-check"></i> Exclusive deals & early access</li>
            </ul>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number (11 digits)</label>
                <input type="tel" name="phone" placeholder="01XXXXXXXXX" pattern="01[3-9][0-9]{8}" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> NID Number</label>
                <input type="text" name="nid" placeholder="Enter your NID number" value="<?php echo htmlspecialchars($_POST['nid'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Minimum 6 characters" minlength="6" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            
            <div class="payment-info">
                <h3>৳<?php echo $registration_fee; ?></h3>
                <p>One-time VIP registration fee</p>
                <div class="payment-methods">
                    <span><i class="fas fa-mobile-alt"></i> bKash</span>
                    <span><i class="fas fa-mobile-alt"></i> Nagad</span>
                </div>
            </div>
            
            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-crown"></i> Pay ৳<?php echo $registration_fee; ?> & Become VIP
            </button>
        </form>
        
        <div class="pro-link">
            <p style="color: rgba(255,255,255,0.6); font-size: 13px;">Looking for a budget option?</p>
            <a href="register_pro.php"><i class="fas fa-star"></i> Register as Pro Customer (৳100)</a>
        </div>
        
        <div class="back-link">
            <a href="../home.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting to Payment...';
        });
    </script>
</body>
</html>
