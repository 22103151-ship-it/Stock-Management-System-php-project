<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_name = '';

    if ($name === '' || $price <= 0) {
        $message = '<div class="alert-error">Please provide a product name and a valid price.</div>';
    } else {
        // Duplicate name check (case-insensitive)
        $check = $conn->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?)");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = '<div class="alert-error">A product with this name already exists.</div>';
        } else {
            // Handle image upload (optional)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                    $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['image']['name']));
                    $image_name = time() . '_' . $safe_name;
                    $target_path = '../assets/images/' . $image_name;

                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $message = '<div class="alert-error">Image upload failed.</div>';
                        $image_name = '';
                    }
                } else {
                    $message = '<div class="alert-error">Invalid image file. Allowed: JPG, PNG, GIF, WebP. Max 5MB.</div>';
                }
            }

            if ($message === '') {
                $stmt = $conn->prepare("INSERT INTO products (name, price, stock, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("sdis", $name, $price, $stock, $image_name);

                if ($stmt->execute()) {
                    $message = '<div class="alert-success">Product added successfully.</div>';
                } else {
                    $message = '<div class="alert-error">Failed to add product. ' . htmlspecialchars($stmt->error) . '</div>';
                }
                $stmt->close();
            }
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #f4f7fc;
            --card: #ffffff;
            --text: #2c3e50;
            --muted: #7f8c8d;
            --accent: #3498db;
            --danger: #e74c3c;
            --shadow: rgba(0, 0, 0, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 0;
            color: var(--text);
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }
        .page-header h1 { margin: 0; font-size: 1.8rem; }
        .page-header p { margin: 4px 0 0; color: var(--muted); }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #2c3e50;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 4px 10px var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .back-btn:hover { background: #34495e; transform: translateY(-2px); box-shadow: 0 6px 14px var(--shadow); }
        .card {
            background: var(--card);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 6px 18px var(--shadow);
        }
        form { display: grid; gap: 18px; }
        label { font-weight: 600; color: var(--text); }
        input[type="text"], input[type="number"], input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e8ed;
            border-radius: 6px;
            font-size: 1rem;
        }
        input[type="file"] { padding: 10px; }
        .actions { display: flex; gap: 12px; }
        button[type="submit"] {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        button[type="submit"]:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
        }
        .alert-success, .alert-error {
            padding: 12px 14px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .alert-success { background: #eafaf1; color: #27ae60; border: 1px solid #b8e6cc; }
        .alert-error { background: #fdecea; color: #c0392b; border: 1px solid #f5c6c1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-box"></i> Add Product</h1>
                <p>Create a new product with image and price</p>
            </div>
            <a class="back-btn" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($message) { echo $message; } ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div>
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter product name" required>
                </div>
                <div>
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" placeholder="Enter price" required>
                </div>
                <div>
                    <label for="stock">Stock (optional)</label>
                    <input type="number" id="stock" name="stock" min="0" value="0">
                </div>
                <div>
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="actions">
                    <button type="submit" name="add_product"><i class="fa-solid fa-plus"></i> Add Product</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
