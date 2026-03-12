<?php
session_start();
include '../config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email'] ?? ''); // Optional
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($phone) || empty($password)) {
        $message = "Name, phone and password are required.";
        $message_type = "error";
    } elseif (strlen($phone) !== 11) {
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
        $check_stmt->bind_param("s", $phone);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "This phone number is already registered.";
            $message_type = "error";
        } else {
            // Create user account (use phone as email if email is empty)
            $login_email = !empty($email) ? $email : $phone . '@customer.local';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("sss", $name, $login_email, $hashed_password);

            if ($user_stmt->execute()) {
                $user_id = $conn->insert_id;

                // Create customer profile
                $customer_query = "INSERT INTO customers (user_id, name, email, phone) VALUES (?, ?, ?, ?)";
                $customer_stmt = $conn->prepare($customer_query);
                $customer_stmt->bind_param("isss", $user_id, $name, $email, $phone);

                if ($customer_stmt->execute()) {
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['user_name'] = $name;
                    $_SESSION['customer_id'] = $conn->insert_id;

                    // Send welcome notification
                    $notification_query = "INSERT INTO automated_notifications (customer_id, notification_type, message) VALUES (?, 'order_placed', 'Welcome to Stock Management System! Your account has been created successfully.')";
                    $notification_stmt = $conn->prepare($notification_query);
                    $notification_stmt->bind_param("i", $_SESSION['customer_id']);
                    $notification_stmt->execute();

                    header("Location: products.php");
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
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .logo-section p {
            color: #666;
            font-size: 0.9rem;
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
            <p>Join our stock management system</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number (11 digits) <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" required maxlength="11" pattern="\d{11}" placeholder="01XXXXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address (Optional)</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
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

        // Phone number validation - exactly 11 digits
        document.getElementById('phone').addEventListener('input', function() {
            // Remove non-digits
            this.value = this.value.replace(/\D/g, '');
            const phone = this.value;
            if (phone.length !== 11) {
                this.setCustomValidity('Phone number must be exactly 11 digits');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>