<?php
session_start();
header('Content-Type: application/json');
include 'config.php';
require_once 'includes/sslcommerz_helper.php';
require_once 'includes/sslcommerz_config.php';

// Handle JSON request from home.php modal
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    // JSON request - get session_id and items from POST
    $session_id = $input['session_id'] ?? '';
    $items = $input['items'] ?? [];
    
    if (empty($session_id) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Verify guest session
    $guest_stmt = $conn->prepare("SELECT id, name, phone, otp_verified FROM guest_customers WHERE session_id = ? AND otp_verified = 1");
    $guest_stmt->bind_param("s", $session_id);
    $guest_stmt->execute();
    $guest_result = $guest_stmt->get_result();
    
    if ($guest_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Session expired or not verified']);
        exit;
    }
    
    $guest = $guest_result->fetch_assoc();
    $guest_id = $guest['id'];
    $guest_name = $guest['name'];
    $guest_phone = $guest['phone'];
    
    // Build cart from items
    $cart = [];
    $total_stocks = 0;
    $subtotal = 0;
    
    foreach ($items as $item) {
        $product_id = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        
        // Validate minimum per product for guest (10)
        if ($quantity < 10) {
            echo json_encode(['success' => false, 'message' => 'Minimum 10 stocks per product required']);
            exit;
        }
        
        // Get product details
        $prod_stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
        $prod_stmt->bind_param("i", $product_id);
        $prod_stmt->execute();
        $product = $prod_stmt->get_result()->fetch_assoc();
        
        if (!$product || $product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock for ' . ($product['name'] ?? 'product')]);
            exit;
        }
        
        $cart[] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
        
        $total_stocks += $quantity;
        $subtotal += $product['price'] * $quantity;
    }
    
    // Validate minimum total (100 for guests)
    if ($total_stocks < 100) {
        echo json_encode(['success' => false, 'message' => 'Minimum 100 stocks required']);
        exit;
    }
    
} else {
    // Session-based request (legacy)
    if (!isset($_SESSION['guest_verified']) || !$_SESSION['guest_verified']) {
        echo json_encode(['success' => false, 'message' => 'Not verified']);
        exit;
    }

    $cart = $_SESSION['guest_cart'] ?? [];
    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $total_stocks = 0;
    $subtotal = 0;
    foreach ($cart as $item) {
        $total_stocks += $item['quantity'];
        $subtotal += $item['price'] * $item['quantity'];
    }

    if ($total_stocks < 100) {
        echo json_encode(['success' => false, 'message' => 'Minimum 100 stocks required']);
        exit;
    }

    $guest_id = $_SESSION['guest_id'];
    $guest_name = $_SESSION['guest_name'];
    $guest_phone = $_SESSION['guest_phone'];
}

// Guest discount: 1000 taka for every 100 stocks
$discount = floor($total_stocks / 100) * 1000;
$total = max(0, $subtotal - $discount);

// Create guest order
$conn->begin_transaction();

try {
    // Insert guest order
    $order_stmt = $conn->prepare("INSERT INTO guest_orders (guest_id, total_stocks, subtotal, discount_amount, total_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $order_stmt->bind_param("iiddd", $guest_id, $total_stocks, $subtotal, $discount, $total);
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO guest_order_items (guest_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $item_stmt->execute();
    }
    
    // Update guest stats
    $update_guest = $conn->prepare("UPDATE guest_customers SET total_orders = total_orders + 1, total_stocks_ordered = total_stocks_ordered + ? WHERE id = ?");
    $update_guest->bind_param("ii", $total_stocks, $guest_id);
    $update_guest->execute();
    
    $conn->commit();
    
    // Prepare SSLCommerz payment
    $tran_id = 'GUEST' . $guest_id . 'O' . $order_id . 'T' . time();
    
    // Update order with transaction ID
    $upd_tran = $conn->prepare("UPDATE guest_orders SET tran_id = ? WHERE id = ?");
    $upd_tran->bind_param("si", $tran_id, $order_id);
    $upd_tran->execute();
    
    $base = sslcommerz_base_url();
    $successUrl = $base . '/guest_payment_success.php';
    $failUrl = $base . '/guest_payment_fail.php';
    $cancelUrl = $base . '/guest_payment_cancel.php';
    
    $payload = [
        'store_id' => (string)$SSLCOMMERZ_STORE_ID,
        'store_passwd' => (string)$SSLCOMMERZ_STORE_PASS,
        'total_amount' => $total,
        'currency' => (string)$SSLCOMMERZ_CURRENCY,
        'tran_id' => $tran_id,
        'success_url' => $successUrl,
        'fail_url' => $failUrl,
        'cancel_url' => $cancelUrl,
        'shipping_method' => 'NO',
        'product_name' => 'Bulk Order - ' . $total_stocks . ' items',
        'product_category' => 'General',
        'product_profile' => 'general',
        'cus_name' => $guest_name,
        'cus_email' => $guest_phone . '@guest.local',
        'cus_add1' => 'Dhaka',
        'cus_city' => 'Dhaka',
        'cus_postcode' => '1200',
        'cus_country' => 'Bangladesh',
        'cus_phone' => $guest_phone,
        'value_a' => (string)$order_id,
        'value_b' => (string)$guest_id,
        'value_c' => 'guest',
        'multi_card_name' => 'bkash',
    ];
    
    $init = sslcommerz_init_payment($payload, (bool)$SSLCOMMERZ_SANDBOX);
    
    if (!empty($init['ok']) && !empty($init['gateway_url'])) {
        // Clear cart after successful order creation
        unset($_SESSION['guest_cart']);
        
        // Return JSON response with redirect URL
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order_id,
            'redirect_url' => $init['gateway_url']
        ]);
        exit;
    }
    
    $error = isset($init['error']) ? $init['error'] : 'Payment gateway error';
    throw new Exception($error);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Order failed: ' . $e->getMessage()]);
    exit;
}
