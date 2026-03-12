<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Include config.php and check DB connection
include '../config.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

include '../includes/header.php'; // Keep your header

// ---------------- Add Product ----------------
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $created_at = date('Y-m-d H:i:s');
    $image_name = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['image']['name']));
            $target_path = '../assets/images/' . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Image uploaded successfully
            } else {
                echo "<div class='alert-error'>Failed to upload image.</div>";
                $image_name = '';
            }
        } else {
            echo "<div class='alert-error'>Invalid image file. Only JPG, PNG, GIF, WebP allowed. Max size: 5MB.</div>";
        }
    }

    // Case-insensitive check for duplicate product name
    $check = $conn->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?)");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<div class='alert-error'>Product already exists!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock, supplier_id, image, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiiss", $name, $price, $stock, $supplier_id, $image_name, $created_at);

        if($stmt->execute()){
            echo "<div class='alert-success'>Product added successfully!</div>";
        } else {
            echo "<div class='alert-error'>Product add not possible. ".$stmt->error."</div>";
        }

        $stmt->close();
    }

    $check->close();
}

// ---------------- Edit Product ----------------
if (isset($_POST['edit_product'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $image_name = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['image']['name']));
            $target_path = '../assets/images/' . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Get current image to delete old one
                $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $current_product = $result->fetch_assoc();

                // Delete old image if exists
                if (!empty($current_product['image']) && file_exists('../assets/images/' . $current_product['image'])) {
                    unlink('../assets/images/' . $current_product['image']);
                }
            } else {
                echo "<div class='alert-error'>Failed to upload image.</div>";
                $image_name = '';
            }
        } else {
            echo "<div class='alert-error'>Invalid image file. Only JPG, PNG, GIF, WebP allowed. Max size: 5MB.</div>";
        }
    }

    $check = $conn->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND id <> ?");
    $check->bind_param("si", $name, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<div class='alert-error'>Another product with the same name already exists!</div>";
    } else {
        if (!empty($image_name)) {
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, supplier_id=?, image=? WHERE id=?");
            $stmt->bind_param("sdiisi", $name, $price, $stock, $supplier_id, $image_name, $id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, supplier_id=? WHERE id=?");
            $stmt->bind_param("sdiii", $name, $price, $stock, $supplier_id, $id);
        }

        if($stmt->execute()){
            echo "<div class='alert-success'>Product updated successfully!</div>";
        } else {
            echo "<div class='alert-error'>Product update failed. ".$stmt->error."</div>";
        }

        $stmt->close();
    }

    $check->close();
}

// ---------------- Delete Product ----------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        echo "<div class='alert-success'>Product deleted successfully!</div>";
    } else {
        echo "<div class='alert-error'>Delete failed. ".$stmt->error."</div>";
    }
    $stmt->close();
}

// ---------------- Fetch Products ----------------
$result = $conn->query("SELECT p.*, s.name AS supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.id DESC");
$product_rows = [];
if ($result) {
    $product_rows = $result->fetch_all(MYSQLI_ASSOC);
}

// ---------------- If editing, fetch product details ----------------
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div style="max-width:900px; margin:20px auto; padding:20px; background:#f8f8f8; border-radius:8px;">

    <!-- Back Button -->
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;">Back </a>

    <h2>Manage Products</h2>

    <!-- Add / Edit Product Form -->
    <form method="POST" enctype="multipart/form-data" autocomplete="off" style="margin-bottom: 30px;">
        <h3><?php echo $edit_product ? "Edit Product" : "Add New Product"; ?></h3>

        <input type="hidden" name="id" value="<?php echo $edit_product['id'] ?? ''; ?>">

        <input type="text" name="name" placeholder="Product Name" required 
               value="<?php echo $edit_product['name'] ?? ''; ?>" 
               style="width:100%; padding:10px; margin:8px 0; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;" autocomplete="off">

        <input type="number" step="0.01" name="price" placeholder="Price" required 
               value="<?php echo $edit_product['price'] ?? ''; ?>" 
               style="width:100%; padding:10px; margin:8px 0; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;" autocomplete="off">

        <input type="number" name="stock" placeholder="Stock" required 
               value="<?php echo $edit_product['stock'] ?? ''; ?>" 
               style="width:100%; padding:10px; margin:8px 0; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;" autocomplete="off">

        <!-- Product Image Upload -->
        <label style="display:block; margin:8px 0 4px 0; font-weight:500;">Product Image:</label>
        <input type="file" name="image" accept="image/*" 
               style="width:100%; padding:10px; margin:8px 0; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
        <?php if (!empty($edit_product['image'])): ?>
            <div style="margin:8px 0;">
                <small>Current image: <strong><?php echo htmlspecialchars($edit_product['image']); ?></strong></small><br>
                <img src="../assets/images/<?php echo htmlspecialchars($edit_product['image']); ?>" 
                     alt="Current product image" 
                     style="max-width:100px; max-height:100px; border:1px solid #ddd; margin-top:5px;">
            </div>
        <?php endif; ?>

        <!-- Supplier Dropdown -->
        <select name="supplier_id" style="width:100%; padding:10px; margin:8px 0; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
            <option value="0">Select Supplier</option>
            <?php 
            $suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");
            while($supplier = $suppliers->fetch_assoc()): ?>
                <option value="<?php echo $supplier['id']; ?>" <?php 
                    if(isset($edit_product['supplier_id']) && $edit_product['supplier_id'] == $supplier['id']) echo 'selected'; 
                ?>>
                    <?php echo htmlspecialchars($supplier['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="<?php echo $edit_product ? 'edit_product' : 'add_product'; ?>" 
                class="<?php echo $edit_product ? 'btn-edit' : 'btn-add'; ?>">
            <?php echo $edit_product ? '✏️ Update Product' : '➕ Add Product'; ?>
        </button>
        <?php if ($edit_product): ?>
            <a href="products.php" style="margin-left:10px; color:#555; text-decoration:none; font-weight:600;">Cancel</a>
        <?php endif; ?>
    </form>

    <!-- Products Table -->
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:white; text-align:left;">
        <tr style="background:#ddd;">
            <th>Serial</th>
            <th>Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Supplier</th>
            <th>Created At</th>
            <th style="width:160px; text-align:center;">Action</th>
        </tr>
        <?php if (empty($product_rows)): ?>
            <tr><td colspan="8" style="text-align:center; color:#666;">No products found.</td></tr>
        <?php else: ?>
            <?php $serial = count($product_rows); foreach ($product_rows as $row): ?>
            <tr>
                <td><?php echo $serial--; ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($row['image'])): ?>
                        <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" 
                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                             style="max-width:50px; max-height:50px; border:1px solid #ddd;">
                    <?php else: ?>
                        <span style="color:#999;">No image</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo number_format($row['price'],2); ?></td>
                <td><?php echo $row['stock']; ?></td>
                <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'N/A'); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td style="white-space: nowrap; text-align:center;">
                    <a href="products.php?edit=<?php echo $row['id']; ?>" class="btn-edit" style="display:inline-block; text-decoration:none; margin-right:8px;">✏️ Edit</a>
                    <a href="products.php?delete=<?php echo $row['id']; ?>" class="btn-delete" style="display:inline-block; text-decoration:none;" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

