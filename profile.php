<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'customer')) {
    header("Location: register.php");
    exit;
}

include '../config.php';

// Ensure we have the customer's id (some sessions may only have user_id)
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $customer_id = (int)$row['id'];
            $_SESSION['customer_id'] = $customer_id; // cache for later requests
        }
        $stmt->close();
    }
}

if (!$customer_id) {
    header("Location: register.php");
    exit;
}

// Get customer info safely
$customer = null;
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$customer) {
    header("Location: register.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $extra_phone = isset($_POST['extra_phone']) ? trim($_POST['extra_phone']) : '';
    $nid = trim($_POST['nid']);

    // Handle profile picture upload
    $profile_picture = $customer['profile_picture']; // Keep existing if no new upload

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 100 * 1024 * 1024; // 100MB max as per requirements

        if (in_array($_FILES['profile_picture']['type'], $allowed_types) && $_FILES['profile_picture']['size'] <= $max_size) {
            $upload_dir = '../assets/images/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'customer_' . $customer_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if ($customer['profile_picture'] && file_exists('../' . $customer['profile_picture'])) {
                    unlink('../' . $customer['profile_picture']);
                }
                $profile_picture = 'assets/images/profiles/' . $new_filename;
            }
        }
    }

    // Update customer information
    $update_query = "UPDATE customers SET name = ?, email = ?, phone = ?, extra_phone = ?, nid = ?, profile_picture = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssi", $name, $email, $phone, $extra_phone, $nid, $profile_picture, $customer_id);

    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        // Refresh customer data
        $customer_query = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
        $customer = $customer_query->fetch_assoc();
    } else {
        $error_message = "Error updating profile. Please try again.";
    }
}

// Handle support message submission
if (isset($_POST['support_message'])) {
    $support_message = trim($_POST['support_message']);

    if (!empty($support_message)) {
        // For now, just show a success message (in a real system, you'd save to database)
        $support_success = "Support message sent successfully! Our team will respond soon.";
    } else {
        $support_error = "Please enter a message.";
    }
}

// Get support chat history
$support_history = [];
$support_query = $conn->query("
    SELECT * FROM support_messages
    WHERE customer_id = $customer_id
    ORDER BY created_at DESC
    LIMIT 20
");

if ($support_query instanceof mysqli_result) {
    while ($row = $support_query->fetch_assoc()) {
        $support_history[] = $row;
    }
    $support_history = array_reverse($support_history);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Customer Portal</title>

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
            --secondary-bg: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--main-color);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .header-actions {
            margin-top: 12px;
        }

        .btn-back {
            padding: 10px 18px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.22);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .card h2 {
            color: var(--main-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-color);
            margin: 0 auto 20px;
            display: block;
        }

        .default-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
            border: 4px solid var(--accent-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--main-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 12px;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--secondary-bg);
        }

        .file-input-label:hover {
            border-color: var(--accent-color);
            background: rgba(52, 152, 219, 0.05);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .support-chat {
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            overflow-y: auto;
            background: var(--secondary-bg);
            margin-bottom: 15px;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 8px;
            max-width: 80%;
        }

        .message.customer {
            background: var(--accent-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message.admin {
            background: #ecf0f1;
            color: var(--text-color);
            border-bottom-left-radius: 4px;
        }

        .message .timestamp {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 5px;
        }

        .chat-input-group {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            resize: vertical;
            min-height: 50px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--secondary-bg);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user"></i> My Profile</h1>
            <p>Manage your account information and get support</p>
            <div class="header-actions">
                <a class="btn-back" href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Profile Information -->
            <div class="card">
                <h2><i class="fas fa-user-edit"></i> Profile Information</h2>

                <?php 
                $customer_type = isset($customer['customer_type']) ? $customer['customer_type'] : 'pro';
                if ($customer_type === 'vip'): 
                ?>
                <div style="text-align: center; margin-bottom: 15px;">
                    <span style="display: inline-block; background: linear-gradient(135deg, #ffd700, #ff8c00); color: #000; padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-crown"></i> VIP Customer
                    </span>
                    <div style="margin-top: 8px; color: #666; font-size: 0.9em;">10% off all products • 20% off on 70+ stocks</div>
                </div>
                <?php else: ?>
                <div style="text-align: center; margin-bottom: 15px;">
                    <span style="display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-star"></i> Pro Customer
                    </span>
                    <div style="margin-top: 8px; color: #666; font-size: 0.9em;">5% off all products • 15% off on 50+ stocks</div>
                    <a href="register_vip.php" style="color: #764ba2; text-decoration: underline; font-size: 0.85em;">Upgrade to VIP →</a>
                </div>
                <?php endif; ?>

                <div class="profile-section">
                    <?php if ($customer['profile_picture']): ?>
                        <img src="../<?php echo htmlspecialchars($customer['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <div class="default-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>" pattern="[0-9]{11}" title="Phone must be 11 digits" required>
                    </div>

                    <div class="form-group">
                        <label for="extra_phone">Extra Phone Number (Optional)</label>
                        <input type="tel" id="extra_phone" name="extra_phone" class="form-control" value="<?php echo htmlspecialchars($customer['extra_phone'] ?? ''); ?>" pattern="[0-9]{11}" title="Phone must be 11 digits">
                    </div>

                    <div class="form-group">
                        <label for="nid">National ID (NID)</label>
                        <input type="text" id="nid" name="nid" class="form-control" value="<?php echo htmlspecialchars($customer['nid']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="profile_picture" name="profile_picture" class="file-input" accept="image/*">
                            <label for="profile_picture" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Click to upload or drag and drop image (Max 5MB)
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Support Chat -->
            <div class="card">
                <h2><i class="fas fa-headset"></i> Support Chat</h2>

                <?php if (isset($support_success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $support_success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($support_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $support_error; ?>
                    </div>
                <?php endif; ?>

                <div class="support-chat">
                    <div class="chat-messages" id="supportMessages">
                        <?php if (empty($support_history)): ?>
                            <div class="message admin">
                                <strong>Support Team:</strong><br>
                                Hello! How can we help you today? Feel free to ask any questions about our products or services.
                                <div class="timestamp"><?php echo date('M d, H:i'); ?></div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($support_history as $msg): ?>
                                <div class="message <?php echo $msg['message_type'] === 'customer_to_admin' ? 'customer' : 'admin'; ?>">
                                    <strong><?php echo $msg['message_type'] === 'customer_to_admin' ? 'You:' : 'Support Team:'; ?></strong><br>
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                    <div class="timestamp"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <div class="chat-input-group">
                            <textarea name="support_message" class="chat-input" placeholder="Type your message to support..." required></textarea>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Account Statistics -->
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Account Statistics</h2>

            <div class="stats-grid">
                <?php
                // Get order statistics
                $order_stats_query = $conn->query("
                    SELECT
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
                    FROM customer_orders
                    WHERE customer_id = $customer_id
                ");
                $order_stats = $order_stats_query ? $order_stats_query->fetch_assoc() : ['total_orders' => 0, 'delivered_orders' => 0, 'pending_orders' => 0];

                // Get total spent (using price * quantity since total_amount doesn't exist)
                $total_spent_query = $conn->query("
                    SELECT SUM(price * quantity) as total_spent
                    FROM customer_orders
                    WHERE customer_id = $customer_id AND status = 'delivered'
                ");
                $total_spent = $total_spent_query ? ($total_spent_query->fetch_assoc()['total_spent'] ?? 0) : 0;
                ?>

                <div class="stat-card">
                    <span class="stat-number"><?php echo $order_stats['total_orders']; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>

                <div class="stat-card">
                    <span class="stat-number"><?php echo $order_stats['delivered_orders']; ?></span>
                    <span class="stat-label">Delivered Orders</span>
                </div>

                <div class="stat-card">
                    <span class="stat-number"><?php echo $order_stats['pending_orders']; ?></span>
                    <span class="stat-label">Add to Cart</span>
                </div>

                <div class="stat-card">
                    <span class="stat-number">$<?php echo number_format($total_spent, 2); ?></span>
                    <span class="stat-label">Total Spent</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom of support chat
        function scrollToBottom() {
            const supportMessages = document.getElementById('supportMessages');
            supportMessages.scrollTop = supportMessages.scrollHeight;
        }

        // Scroll to bottom on page load
        scrollToBottom();

        // File input preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const label = document.querySelector('.file-input-label');
                label.innerHTML = '<i class="fas fa-check-circle"></i> ' + file.name + ' selected';
                label.style.borderColor = 'var(--success-color)';
                label.style.background = 'rgba(39, 174, 96, 0.05)';
            }
        });

        // Auto-resize textarea
        document.querySelector('.chat-input').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>