<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Get customer info
$cust_stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$cust_stmt->bind_param("i", $customer_id);
$cust_stmt->execute();
$customer = $cust_stmt->get_result()->fetch_assoc();
$is_member = (bool)$customer['is_member'];

// Get cart items
$cart_stmt = $conn->prepare("
    SELECT cc.*, p.name, p.price, p.stock 
    FROM customer_cart cc 
    JOIN products p ON cc.product_id = p.id 
    WHERE cc.customer_id = ?
");
$cart_stmt->bind_param("i", $customer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$items = [];
$total_stocks = 0;
$subtotal = 0;
$min_per_product = 10;

while ($row = $cart_result->fetch_assoc()) {
    $items[] = $row;
    $total_stocks += $row['quantity'];
    $subtotal += $row['price'] * $row['quantity'];
}

// Calculate discount
$discount_percent = 0;
if ($is_member) {
    if ($total_stocks >= 100) {
        $discount_percent = 20;
    } elseif ($total_stocks >= 30) {
        $discount_percent = 15;
    }
}
$discount_amount = $subtotal * ($discount_percent / 100);
$total = $subtotal - $discount_amount;

// Validate cart
$errors = [];
$payment_method = $_POST['payment_method'] ?? 'sslcommerz';
$min_required = $is_member ? 30 : 100;

if (empty($items)) {
    $errors[] = 'Cart is empty';
}

foreach ($items as $item) {
    if ($item['quantity'] < $min_per_product) {
        $errors[] = $item['name'] . ' requires minimum ' . $min_per_product . ' stocks';
    }
    if ($item['stock'] < $item['quantity']) {
        $errors[] = $item['name'] . ' has insufficient stock';
    }
}

if ($total_stocks < $min_required) {
    $errors[] = 'Minimum ' . $min_required . ' total stocks required';
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $conn->begin_transaction();
    
    try {
        // Create order for each cart item (table structure is single product per order)
        $order_ids = [];
        
        foreach ($items as $item) {
            $item_discount_amount = floatval(($item['price'] * $item['quantity']) * ($discount_percent / 100));
            $status = ($payment_method === 'sslcommerz') ? 'pending_payment' : 'pending';
            $order_stmt = $conn->prepare(
                "INSERT INTO customer_orders (customer_id, product_id, quantity, price, discount_percent, discount_amount, total_stocks, status, order_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $bp_customer_id = intval($customer_id);
            $bp_product_id = intval($item['product_id']);
            $bp_quantity = intval($item['quantity']);
            $bp_price = floatval($item['price']);
            $bp_discount_percent = floatval($discount_percent);
            $bp_discount_amount = floatval($item_discount_amount);
            $bp_total_stocks = intval($item['quantity']);
            $bp_status = $status;
            $order_stmt->bind_param("iiidddis", $bp_customer_id, $bp_product_id, $bp_quantity, $bp_price, $bp_discount_percent, $bp_discount_amount, $bp_total_stocks, $bp_status);
            $order_stmt->execute();
            $order_ids[] = $conn->insert_id;
            $order_stmt->close();
        }
        $conn->commit();
        if ($payment_method === 'sslcommerz') {
            require_once '../includes/sslcommerz_config.php';
            require_once '../includes/sslcommerz_helper.php';
            $tran_id = 'ORD' . time() . rand(1000,9999);
            $base = sslcommerz_base_url();
            $post_data = array();
            $post_data['store_id'] = $SSLCOMMERZ_STORE_ID;
            $post_data['store_passwd'] = $SSLCOMMERZ_STORE_PASS;
            $post_data['total_amount'] = $total;
            $post_data['currency'] = $SSLCOMMERZ_CURRENCY;
            $post_data['tran_id'] = $tran_id;
            $post_data['success_url'] = $base . "/customer/order_success.php?order_id=" . $order_ids[0];
            $post_data['fail_url'] = $base . "/customer/order_fail.php?order_id=" . $order_ids[0];
            $post_data['cancel_url'] = $base . "/customer/order_cancel.php?order_id=" . $order_ids[0];
            $post_data['cus_name'] = $customer['name'];
            $post_data['cus_email'] = $customer['email'] ?? 'customer@example.com';
            $post_data['cus_phone'] = $customer['phone'];
            $post_data['cus_add1'] = $customer['address'] ?? 'N/A';
            $post_data['cus_city'] = "Dhaka";
            $post_data['cus_country'] = "Bangladesh";
            $post_data['ship_name'] = $customer['name'];
            $post_data['ship_add1'] = $customer['address'] ?? 'N/A';
            $post_data['ship_city'] = "Dhaka";
            $post_data['ship_country'] = "Bangladesh";
            $post_data['shipping_method'] = "NO";
            $post_data['product_name'] = "Stock Order";
            $post_data['product_category'] = "Stock";
            $post_data['product_profile'] = "general";
            $sslresponse = sslcommerz_init_payment($post_data, (bool)$SSLCOMMERZ_SANDBOX);
            if (!empty($sslresponse['ok']) && !empty($sslresponse['gateway_url'])) {
                // Redirect to payment gateway
                header("Location: " . $sslresponse['gateway_url']);
                exit;
            } else {
                $errors[] = 'Payment gateway error: ' . ($sslresponse['error'] ?? 'Please try again.');
            }
        } else {
            // Pay later: clear cart and redirect
            $clear_stmt = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ?");
            $clear_stmt->bind_param("i", $customer_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            $_SESSION['checkout_success'] = true;
            $_SESSION['checkout_order_ids'] = $order_ids;
            header('Location: my_orders.php?success=1');
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = 'Checkout failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Stock Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
        }
        .checkout-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        .cart-item {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .summary-box {
            background: rgba(0,255,136,0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .discount-badge {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .btn-pay {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
            width: 100%;
        }
        .btn-pay:hover {
            background: linear-gradient(135deg, #00cc66, #00ff88);
            transform: scale(1.02);
        }
        .alert-danger {
            background: rgba(255,0,0,0.2);
            border: 1px solid rgba(255,0,0,0.5);
            color: #fff;
        }
        .member-badge {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shopping-cart me-2"></i>Checkout</h2>
                <?php if ($is_member): ?>
                    <span class="member-badge"><i class="fas fa-crown me-1"></i>Member</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$is_member): ?>
                <div class="alert alert-warning mb-4" style="background: rgba(255,193,7,0.2); border-color: rgba(255,193,7,0.5);">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Become a member</strong> to unlock discounts! 
                    <a href="membership.php" class="text-warning">Get membership now →</a>
                </div>
            <?php endif; ?>
            
            <h5 class="mb-3">Order Items</h5>
            <?php foreach ($items as $item): ?>
                <div class="cart-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <br>
                            <small class="text-muted">৳<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?> stocks</small>
                        </div>
                        <div class="text-end">
                            <strong>৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="summary-box">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Stocks:</span>
                    <strong><?php echo $total_stocks; ?> stocks</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>৳<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if ($discount_percent > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>
                            <span class="discount-badge me-2"><?php echo $discount_percent; ?>% OFF</span>
                            Member Discount:
                        </span>
                        <span>-৳<?php echo number_format($discount_amount, 2); ?></span>
                    </div>
                <?php elseif ($is_member && $total_stocks < 30): ?>
                    <div class="text-warning small">
                        <i class="fas fa-info-circle me-1"></i>Add <?php echo 30 - $total_stocks; ?> more stocks to unlock 15% discount!
                    </div>
                <?php elseif ($is_member && $total_stocks >= 30 && $total_stocks < 100): ?>
                    <div class="text-info small">
                        <i class="fas fa-info-circle me-1"></i>Add <?php echo 100 - $total_stocks; ?> more stocks to unlock 20% discount!
                    </div>
                <?php endif; ?>
                
                <hr style="border-color: rgba(255,255,255,0.3);">
                
                <div class="d-flex justify-content-between">
                    <h4>Total:</h4>
                    <h4>৳<?php echo number_format($total, 2); ?></h4>
                </div>
            </div>
            
            <?php if (empty($errors) && !empty($items)): ?>
                            <form method="POST" class="mt-4">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method:</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_sslcommerz" value="sslcommerz" checked>
                                        <label class="form-check-label" for="pay_sslcommerz">Pay now with bKash (SSLCommerz)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_later" value="later">
                                        <label class="form-check-label" for="pay_later">Pay later (Cash/Manual)</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-pay">
                                    <i class="fas fa-lock me-2"></i>Proceed to Payment
                                </button>
                            </form>
            <?php else: ?>
                <div class="mt-4 text-center">
                    <a href="products.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="products.php" class="text-muted">← Back to Products</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
