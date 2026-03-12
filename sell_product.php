<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';

$msg = "";

// ---------------- Handle Sell Product Form ----------------
if (isset($_POST['sell_product'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);

    // Fetch product stock and price
    $stmt = $conn->prepare("SELECT name, stock, price FROM products WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($product_name, $stock, $price);
    $stmt->fetch();
    $stmt->close();

    if ($quantity > $stock) {
        $_SESSION['msg'] = "<p style='color:red;'>Stock not sufficient! Available: $stock</p>";
    } else {
        // Deduct stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
        $stmt->close();

        // Insert into sell_product table
        $stmt = $conn->prepare("INSERT INTO sell_product (product_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isid", $product_id, $product_name, $quantity, $price);
        $stmt->execute();
        $stmt->close();

        $_SESSION['msg'] = "<p style='color:green;'>Product sold successfully!</p>";
    }

    // Redirect to prevent resubmission on refresh
    header("Location: sell_product.php");
    exit;
}

// ---------------- Fetch Products ----------------
$products_result = $conn->query("SELECT id, name, stock, price FROM products ORDER BY name ASC");

// ---------------- Fetch Sold Products History ----------------
$sold_products = $conn->query("
    SELECT sp.id, sp.product_name, sp.quantity, sp.price, sp.created_at, p.stock AS current_stock
    FROM sell_product sp
    LEFT JOIN products p ON sp.product_id = p.id
    ORDER BY sp.id DESC
");

// ✅ include header only AFTER all redirects are done
include '../includes/header.php';
?>

<div style="max-width:900px; margin:20px auto; padding:20px; background:#f8f8f8; border-radius:8px;">
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;">Back</a>
    <h2>Sell Product</h2>

    <?php 
    if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
    }
    ?>

    <!-- Sell Product Form -->
    <form method="POST" style="margin-bottom:20px;">
        <label>Product:</label>
        <select name="product_id" id="product_select" required style="width:100%; padding:8px; margin:5px 0;">
            <option value="">-- Select Product --</option>
            <?php while($p = $products_result->fetch_assoc()): ?>
                <option value="<?php echo $p['id']; ?>" data-stock="<?php echo $p['stock']; ?>">
                    <?php echo htmlspecialchars($p['name']); ?> (Stock: <?php echo $p['stock']; ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Quantity:</label>
        <input type="number" name="quantity" id="quantity" min="1" required style="width:100%; padding:8px; margin:5px 0;">

        <button type="submit" name="sell_product" class="btn-add">Sell</button>
    </form>

    <!-- Sold Products Table -->
    <h3>Sold Products History</h3>
    <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;">
        <thead style="background:#ddd;">
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
                <th>Current Stock</th>
                <th>Sold At</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>
            <?php while($s = $sold_products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $s['id']; ?></td>
                    <td><?php echo htmlspecialchars($s['product_name']); ?></td>
                    <td><?php echo $s['quantity']; ?></td>
                    <td><?php echo number_format($s['price'],2); ?></td>
                    <td><?php echo number_format($s['price']*$s['quantity'],2); ?></td>
                    <td><?php echo $s['current_stock'] !== null ? $s['current_stock'] : 'N/A'; ?></td>
                    <td><?php echo $s['created_at']; ?></td>
                    <td>
                        <a href="generate_invoice.php?sell_id=<?php echo $s['id']; ?>" target="_blank" class="btn-add" style="padding:5px 10px; text-decoration:none; border-radius:3px; display:inline-block;">Invoice</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    const productSelect = document.getElementById('product_select');
    const quantityInput = document.getElementById('quantity');

    quantityInput.addEventListener('input', () => {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (!selectedOption) return;
        const stock = parseInt(selectedOption.getAttribute('data-stock'));
        const quantity = parseInt(quantityInput.value);
        if (quantity > stock) {
            alert('Stock not sufficient! Available: ' + stock);
            quantityInput.value = stock;
        }
    });
</script>

<!-- <footer style="background-color: gray; color:white; text-align:center; padding:15px 0;">
    <p>Stock Management System</p>
</footer> -->
