<?php
session_start();
header('Content-Type: application/json');
include '../config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

// Get customer type for discount calculation
$cust_stmt = $conn->prepare("SELECT customer_type FROM customers WHERE id = ?");
$cust_stmt->bind_param("i", $customer_id);
$cust_stmt->execute();
$customer = $cust_stmt->get_result()->fetch_assoc();
$customer_type = $customer['customer_type'] ?? 'pro';

// Calculate discounts based on customer type
$base_discount = ($customer_type === 'vip') ? 10 : 5;
$bulk_discount = ($customer_type === 'vip') ? 20 : 15;
$bulk_threshold = ($customer_type === 'vip') ? 70 : 50;
$min_per_product = ($customer_type === 'vip') ? 10 : 20;

switch ($action) {
    case 'add':
        addToCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product);
        break;
    case 'update':
        updateCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product);
        break;
    case 'remove':
        removeFromCart($conn, $customer_id);
        break;
    case 'get':
        getCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product);
        break;
    case 'clear':
        clearCart($conn, $customer_id);
        break;
    case 'validate':
        validateCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addToCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    if ($quantity < $min_per_product) {
        echo json_encode(['success' => false, 'message' => "Minimum $min_per_product stocks per product required"]);
        return;
    }
    
    // Check stock
    $stock_check = $conn->prepare("SELECT stock, name, price FROM products WHERE id = ?");
    $stock_check->bind_param("i", $product_id);
    $stock_check->execute();
    $product = $stock_check->get_result()->fetch_assoc();
    
    if (!$product || $product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        return;
    }
    
    // Add or update cart
    $check = $conn->prepare("SELECT id FROM customer_cart WHERE customer_id = ? AND product_id = ?");
    $check->bind_param("ii", $customer_id, $product_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $upd = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE customer_id = ? AND product_id = ?");
        $upd->bind_param("iii", $quantity, $customer_id, $product_id);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO customer_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $customer_id, $product_id, $quantity);
        $ins->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Added to cart']);
}

function updateCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    if ($quantity < $min_per_product) {
        echo json_encode(['success' => false, 'message' => "Minimum $min_per_product stocks per product"]);
        return;
    }
    
    $upd = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE customer_id = ? AND product_id = ?");
    $upd->bind_param("iii", $quantity, $customer_id, $product_id);
    $upd->execute();
    
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
}

function removeFromCart($conn, $customer_id) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    $del = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ? AND product_id = ?");
    $del->bind_param("ii", $customer_id, $product_id);
    $del->execute();
    
    echo json_encode(['success' => true, 'message' => 'Removed from cart']);
}

function clearCart($conn, $customer_id) {
    $del = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ?");
    $del->bind_param("i", $customer_id);
    $del->execute();
    
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
}

function getCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product) {
    $stmt = $conn->prepare("
        SELECT cc.*, p.name, p.price, p.stock 
        FROM customer_cart cc 
        JOIN products p ON cc.product_id = p.id 
        WHERE cc.customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total_stocks = 0;
    $subtotal = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Apply base discount to price
        $row['discounted_price'] = $row['price'] * (1 - $base_discount / 100);
        $items[] = $row;
        $total_stocks += $row['quantity'];
        $subtotal += $row['discounted_price'] * $row['quantity'];
    }
    
    // Calculate bulk discount if applicable
    $discount_percent = 0;
    $discount_amount = 0;
    
    if ($total_stocks >= $bulk_threshold) {
        $discount_percent = $bulk_discount;
        $discount_amount = $subtotal * ($discount_percent / 100);
    }
    
    $total = $subtotal - $discount_amount;
    
    // Minimum order validation - Pro/VIP can checkout with any quantity >= min per product
    $can_checkout = $total_stocks >= $min_per_product;
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_stocks' => $total_stocks,
        'subtotal' => $subtotal,
        'base_discount' => $base_discount,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'total' => $total,
        'min_per_product' => $min_per_product,
        'bulk_threshold' => $bulk_threshold,
        'can_checkout' => $can_checkout
    ]);
}

function validateCart($conn, $customer_id, $base_discount, $bulk_discount, $bulk_threshold, $min_per_product) {
    $stmt = $conn->prepare("
        SELECT cc.*, p.name, p.price, p.stock 
        FROM customer_cart cc 
        JOIN products p ON cc.product_id = p.id 
        WHERE cc.customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $errors = [];
    $total_stocks = 0;
    $subtotal = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Apply base discount
        $discounted_price = $row['price'] * (1 - $base_discount / 100);
        $total_stocks += $row['quantity'];
        $subtotal += $discounted_price * $row['quantity'];
        
        if ($row['quantity'] < $min_per_product) {
            $errors[] = $row['name'] . ' requires minimum ' . $min_per_product . ' stocks';
        }
        
        if ($row['stock'] < $row['quantity']) {
            $errors[] = $row['name'] . ' has insufficient stock';
        }
    }
    
    // Calculate bulk discount
    $discount_percent = 0;
    $discount_amount = 0;
    
    if ($total_stocks >= $bulk_threshold) {
        $discount_percent = $bulk_discount;
        $discount_amount = $subtotal * ($discount_percent / 100);
    }
    
    $total = $subtotal - $discount_amount;
    
    echo json_encode([
        'success' => true,
        'valid' => empty($errors),
        'errors' => $errors,
        'total_stocks' => $total_stocks,
        'subtotal' => $subtotal,
        'base_discount' => $base_discount,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'total' => $total,
        'min_per_product' => $min_per_product,
        'bulk_threshold' => $bulk_threshold
    ]);
}
