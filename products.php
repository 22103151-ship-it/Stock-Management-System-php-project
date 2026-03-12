<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to registration
    header("Location: login.php");
    exit;
}

// Check if user has completed customer registration
include '../config.php';
$user_id = $_SESSION['user_id'];
$customer_check = $conn->query("SELECT c.*, c.id as customer_id FROM customers c WHERE c.user_id = $user_id");

if ($customer_check->num_rows == 0) {
    // User exists but hasn't completed customer registration
    header("Location: register_pro.php");
    exit;
}

$customer = $customer_check->fetch_assoc();
$customer_id = $customer['customer_id'];
$customer_type = $customer['customer_type'] ?? 'pro';
$is_member = (bool)($customer['is_member'] ?? 0);
$_SESSION['customer_id'] = $customer_id;
$_SESSION['customer_type'] = $customer_type;

// Pro/VIP discount configuration
$base_discount = ($customer_type === 'vip') ? 10 : 5; // VIP: 10%, Pro: 5%
$bulk_discount = ($customer_type === 'vip') ? 20 : 15; // VIP: 20% for 70+, Pro: 15% for 50+
$bulk_threshold = ($customer_type === 'vip') ? 70 : 50; // VIP: 70 stocks, Pro: 50 stocks
$min_per_product = ($customer_type === 'vip') ? 10 : 20; // VIP: min 10, Pro: min 20

// Get cart count
$cart_count_q = $conn->query("SELECT SUM(quantity) as total FROM customer_cart WHERE customer_id = $customer_id");
$cart_count = $cart_count_q->fetch_assoc()['total'] ?? 0;

// User is fully registered, proceed with products page
include '../includes/header.php';

// Fetch products (including out-of-stock, sorted by stock DESC so available items appear first)
$products = [];
if (isset($conn)) {
    $result = $conn->query("SELECT * FROM products ORDER BY stock DESC, name ASC");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Customer</title>

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
            --shadow-color: rgba(0, 0, 0, 0.1);
            --text-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--main-color), var(--accent-color));
            color: white;
            border-radius: 8px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, var(--accent-color), var(--main-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-image:hover img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .product-stock {
            color: var(--success-color);
            font-weight: 500;
            margin-bottom: 15px;
        }

        .product-stock.low-stock {
            color: var(--warning-color);
        }

        .product-stock.out-of-stock {
            color: var(--danger-color);
        }

        .order-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .order-btn:hover {
            background: #2980b9;
        }

        .order-btn.disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        /* Out of stock styling */
        .product-card.out-of-stock {
            opacity: 0.7;
            position: relative;
        }

        .product-card.out-of-stock .product-image {
            filter: grayscale(50%);
        }

        .product-card.out-of-stock:hover {
            transform: none;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
            text-transform: uppercase;
        }

        .stock-badge.out {
            background: #e74c3c;
            color: white;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            box-shadow: 0 2px 8px rgba(0, 255, 136, 0.4);
        }

        .out-of-stock-msg {
            padding: 12px 20px;
            background: #f8f9fa;
            color: #6c757d;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
            margin-top: 10px;
        }

        .out-of-stock-msg i {
            margin-right: 5px;
            color: #e67e22;
        }

        .no-products {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            font-size: 1.2rem;
        }

        /* Cart floating button */
        .cart-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(0,255,136,0.4);
            z-index: 1000;
            text-decoration: none;
            transition: transform 0.3s;
        }
        .cart-float:hover {
            transform: scale(1.1);
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: #fff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Member banner */
        .member-banner {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .non-member-banner {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Add to cart form */
        .cart-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .qty-input {
            width: 80px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
        }
        .add-cart-btn {
            flex: 1;
            padding: 10px 15px;
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .add-cart-btn:hover {
            transform: scale(1.02);
        }
        .min-note {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        .back-navigation {
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .back-btn:hover {
            background: #2980b9;
            color: white;
        }

        .back-btn i {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="page-header">
        <h1>Browse Our Products</h1>
    </div>

    <?php if ($customer_type === 'vip'): ?>
        <div class="member-banner" style="background: linear-gradient(135deg, #ffd700, #ff8c00);">
            <div style="color: #000;">
                <i class="fas fa-crown me-2"></i>
                <strong>VIP Customer Benefits!</strong> 
                10% off on all products • Extra 20% off on 70+ stocks • Min 10 stocks per product
            </div>
        </div>
    <?php else: ?>
        <div class="member-banner" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div>
                <i class="fas fa-star me-2"></i>
                <strong>Pro Customer Benefits!</strong> 
                5% off on all products • Extra 15% off on 50+ stocks • Min 20 stocks per product
            </div>
            <a href="register_vip.php" style="color: #ffd700; text-decoration: underline; font-weight: bold;">
                Upgrade to VIP →
            </a>
        </div>
    <?php endif; ?>

    <div class="back-navigation">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($products)): ?>
        <div class="no-products">
            <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
            <p>No products available at the moment.</p>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <?php $out_of_stock = $product['stock'] <= 0; ?>
                <?php 
                    // Calculate discounted price
                    $original_price = $product['price'];
                    $discounted_price = $original_price * (1 - $base_discount / 100);
                ?>
                <div class="product-card <?php echo $out_of_stock ? 'out-of-stock' : ''; ?>">
                    <div class="product-image">
                        <?php if ($out_of_stock): ?>
                            <span class="stock-badge out">Out of Stock</span>
                        <?php else: ?>
                            <span class="discount-badge"><?php echo $base_discount; ?>% OFF</span>
                        <?php endif; ?>
                        <?php if (!empty($product['image'])): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-box"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">
                            <span class="original-price" style="text-decoration: line-through; color: #999; font-size: 0.85em;">৳<?php echo number_format($original_price, 2); ?></span>
                            <span style="color: #28a745; font-weight: bold;">৳<?php echo number_format($discounted_price, 2); ?></span>
                        </div>
                        <div class="product-stock <?php echo $product['stock'] < 10 ? 'low-stock' : ''; ?>">
                            <?php if ($out_of_stock): ?>
                                <span style="color: #e74c3c;">Not Available</span>
                            <?php else: ?>
                                <?php echo $product['stock']; ?> in stock
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$out_of_stock): ?>
                            <form class="cart-form" onsubmit="addToCart(event, <?php echo $product['id']; ?>)">
                                <input type="number" class="qty-input" id="qty-<?php echo $product['id']; ?>" 
                                       value="<?php echo $min_per_product; ?>" min="<?php echo $min_per_product; ?>" max="<?php echo $product['stock']; ?>" step="1">
                                <button type="submit" class="add-cart-btn">
                                    <i class="fas fa-cart-plus"></i> Add
                                </button>
                            </form>
                            <div class="min-note">Min <?php echo $min_per_product; ?> stocks • Extra <?php echo $bulk_discount; ?>% on <?php echo $bulk_threshold; ?>+ stocks</div>
                        <?php else: ?>
                            <div class="out-of-stock-msg">
                                <i class="fas fa-clock"></i> Coming Soon
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Floating Cart Button -->
    <a href="checkout.php" class="cart-float" title="View Cart">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge" id="cart-count"><?php echo $cart_count; ?></span>
    </a>

</div>

<?php include '../includes/footer.php'; ?>

<script>
function addToCart(e, productId) {
    e.preventDefault();
    const qty = document.getElementById('qty-' + productId).value;
    
    fetch('cart_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId + '&quantity=' + qty
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount();
            showToast('Added to cart!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(err => {
        showToast('Error adding to cart', 'error');
    });
}

function updateCartCount() {
    fetch('cart_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-count').textContent = data.total_stocks;
        }
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'toast-message ' + type;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 100px;
        right: 30px;
        padding: 15px 25px;
        border-radius: 8px;
        color: #fff;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

</body>
</html>