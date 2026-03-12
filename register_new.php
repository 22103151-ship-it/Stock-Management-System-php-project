<?php
session_start();
include '../config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Phone number validation - must be exactly 11 digits
    $phone_clean = preg_replace('/\D/', '', $phone);

    // Validation
    if (empty($name) || empty($phone) || empty($password)) {
        $message = "Name, phone number, and password are required.";
        $message_type = "error";
    } elseif (strlen($phone_clean) !== 11) {
        $message = "Phone number must be exactly 11 digits.";
        $message_type = "error";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        // Check if phone already exists
        $check_query = "SELECT id FROM customers WHERE phone = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $phone_clean);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        // Also check email if provided
        $email_exists = false;
        if (!empty($email)) {
            $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $email_check->bind_param("s", $email);
            $email_check->execute();
            if ($email_check->get_result()->num_rows > 0) {
                $email_exists = true;
            }
        }

        if ($check_result->num_rows > 0) {
            $message = "This phone number is already registered. Please login or use a different number.";
            $message_type = "error";
        } elseif ($email_exists) {
            $message = "This email is already registered.";
            $message_type = "error";
        } else {
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_email = !empty($email) ? $email : $phone_clean . '@customer.local';
            
            $user_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("sss", $name, $user_email, $hashed_password);

            if ($user_stmt->execute()) {
                $user_id = $conn->insert_id;

                // Create customer profile (without NID, with membership fields)
                $customer_query = "INSERT INTO customers (user_id, name, email, phone, is_member, membership_fee_paid) VALUES (?, ?, ?, ?, 0, 0.00)";
                $customer_stmt = $conn->prepare($customer_query);
                $cust_email = !empty($email) ? $email : null;
                $customer_stmt->bind_param("isss", $user_id, $name, $cust_email, $phone_clean);

                if ($customer_stmt->execute()) {
                    $customer_id = $conn->insert_id;
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['user_name'] = $name;
                    $_SESSION['customer_id'] = $customer_id;

                    // Send welcome notification
                    $notification_query = "INSERT INTO automated_notifications (customer_id, notification_type, message) VALUES (?, 'welcome', 'Welcome to Stock Management System! Your account has been created successfully. Buy membership (min ৳100) to unlock discounts!')";
                    $notification_stmt = $conn->prepare($notification_query);
                    $notification_stmt->bind_param("i", $customer_id);
                    $notification_stmt->execute();

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $message = "Error creating customer profile. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Error creating account. Please try again.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Stock Management System</title>

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
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --text-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-color) 0%, #e8f4f8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .registration-container {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .registration-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-color), var(--success-color));
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section h1 {
            color: var(--main-color);
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .logo-section p {
            color: #666;
            font-size: 0.9rem;
        }

        .info-box {
            background: #e8f6ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #004085;
        }

        .info-box i {
            margin-right: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .message.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .login-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .required {
            color: var(--error-color);
        }

        .optional {
            color: #999;
            font-size: 0.8rem;
            font-weight: 400;
        }

        .phone-hint {
            font-size: 0.75rem;
            color: #666;
            margin-top: 4px;
        }

        .guest-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color);
        }

        .guest-link a {
            color: #27ae60;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .registration-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="logo-section">
            <h1><i class="fas fa-user-plus"></i> Customer Registration</h1>
            <p>Create your account to start ordering</p>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Membership Benefits:</strong> Pay min ৳100 to unlock 15% off (30+ stocks) or 20% off (100+ stocks)!
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" required placeholder="01XXXXXXXXX" maxlength="11" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                <div class="phone-hint">Must be exactly 11 digits (e.g., 01712345678)</div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="optional">(Optional)</span></label>
                <input type="email" id="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Min 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>

        <div class="guest-link">
            <p>Don't want to register? <a href="../home.php#guest-order">Order as Guest</a></p>
        </div>
    </div>

    <script>
        // Phone number validation - only digits, exactly 11
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
            
            if (this.value.length !== 11) {
                this.setCustomValidity('Phone number must be exactly 11 digits');
            } else if (!this.value.startsWith('01')) {
                this.setCustomValidity('Phone number must start with 01');
            } else {
                this.setCustomValidity('');
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
