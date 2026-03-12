<?php
session_start();
include 'config.php';

// Get role parameter from URL
$role_filter = isset($_GET['role']) ? $_GET['role'] : null;
$error_msg = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $db_password, $role);
        $stmt->fetch();

        // Check if user's role matches the requested role (if role was specified)
        if ($role_filter && $role !== $role_filter) {
            $error_msg = "This account is not registered as a " . ucfirst($role_filter) . ".";
        } elseif ($password === $db_password) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = $role;

            // For customers, also set customer_id
            if ($role == 'customer') {
                $customer_stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
                $customer_stmt->bind_param("i", $id);
                $customer_stmt->execute();
                $customer_stmt->bind_result($customer_id);
                if ($customer_stmt->fetch()) {
                    $_SESSION['customer_id'] = $customer_id;
                }
                $customer_stmt->close();
            }

            if ($role == 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($role == 'staff') {
                header("Location: staff/dashboard.php");
            } elseif ($role == 'supplier') {
                header("Location: supplier/dashboard.php");
            } elseif ($role == 'customer') {
                header("Location: customer/dashboard.php");
            }
            exit;
        } else {
            $error_msg = "Invalid password!";
        }
    } else {
        $error_msg = "No user found with that email!";
    }
}

// Get role title for display
$role_title = $role_filter ? ucfirst($role_filter) : 'User';

// Determine back URL: prefer home when role specified or referrer contains home.php
$back_url = 'index.php';
if ($role_filter) {
    $back_url = 'home.php';
} elseif (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'home.php') !== false) {
    $back_url = 'home.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stock Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: url('assets/images/home-bg.jpg') center/cover fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(14px);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.28);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 28px;
            color: #111;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #222;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #111;
            font-weight: 700;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(17, 17, 17, 0.2);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.8);
            color: #111;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: rgba(17, 17, 17, 0.45);
            box-shadow: 0 0 0 3px rgba(17, 17, 17, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.65));
            color: #111;
            border: 1px solid rgba(17, 17, 17, 0.08);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 20px;
            backdrop-filter: blur(6px);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-msg {
            background: rgba(255, 238, 238, 0.8);
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #b91c1c;
            font-size: 14px;
            backdrop-filter: blur(6px);
        }

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.8);
            color: #111;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
            border: 1px solid rgba(17, 17, 17, 0.08);
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            📦 Stock Management System
        </div>
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </header>

    <main>
        <div class="login-container">
            <div class="login-header">
                <h1>Login</h1>
                <?php if ($role_filter): ?>
                    <p>Sign in as <span class="role-badge"><?php echo ucfirst($role_filter); ?></span></p>
                <?php else: ?>
                    <p>Enter your credentials to continue</p>
                <?php endif; ?>
            </div>

            <?php if ($error_msg): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $role_filter ? 'login.php?role=' . htmlspecialchars($role_filter) : 'login.php'; ?>" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" name="login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </main>
</body>
</html>
