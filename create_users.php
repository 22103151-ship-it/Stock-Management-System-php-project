<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // plain text for now
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        // If creating a customer, also create customer record
        if ($role === 'customer') {
            $customer_stmt = $conn->prepare("INSERT INTO customers (user_id, name, email, phone, nid) VALUES (?, ?, ?, '', '')");
            $customer_stmt->bind_param("iss", $user_id, $name, $email);
            if ($customer_stmt->execute()) {
                echo "<div class='alert-success'>✅ Customer user and profile created successfully!</div>";
            } else {
                echo "<div class='alert-warning'>⚠️ User created but customer profile creation failed: " . $customer_stmt->error . "</div>";
            }
        } else {
            echo "<div class='alert-success'>✅ User created successfully!</div>";
        }
    } else {
        echo "<div class='alert-error'>❌ Error: " . $stmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create User</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 style="text-align: center; color: #333; margin-top: 0;">Create New User</h2>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter email address" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="supplier">Supplier</option>
                </select>
            </div>
            
            <button type="submit" class="btn-add" style="width: 100%; padding: 12px; font-size: 16px;">
                ➕ Create User
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666;">
            <a href="index.php" style="color: #007BFF; text-decoration: none; font-weight: 600;">Back to Login</a>
        </p>
    </div>
</body>
</html>
