<?php
session_start();
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send_otp':
    case 'sendOTP':
        sendOTP($conn);
        break;
    case 'verify_otp':
    case 'verifyOTP':
        verifyOTP($conn);
        break;
    case 'get_products':
    case 'getProducts':
        getProducts($conn);
        break;
    case 'add_to_cart':
        addToCart($conn);
        break;
    case 'get_cart':
        getCart($conn);
        break;
    case 'remove_from_cart':
        removeFromCart($conn);
        break;
    case 'calculate_total':
        calculateTotal($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function sendOTP($conn) {
    $name = trim($_POST['name'] ?? '');
    $phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    
    if (empty($name) || strlen($phone) !== 11) {
        echo json_encode(['success' => false, 'message' => 'Valid name and 11-digit phone required']);
        return;
    }
    
    // Generate 4-digit OTP
    $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Generate unique session ID
    $session_id = bin2hex(random_bytes(16));
    
    // Check if guest exists
    $check = $conn->prepare("SELECT id FROM guest_customers WHERE phone = ?");
    $check->bind_param("s", $phone);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing guest
        $guest = $result->fetch_assoc();
        $update = $conn->prepare("UPDATE guest_customers SET name = ?, otp_code = ?, otp_expires = ?, otp_verified = 0, session_id = ? WHERE id = ?");
        $update->bind_param("ssssi", $name, $otp, $expires, $session_id, $guest['id']);
        $update->execute();
        $guest_id = $guest['id'];
    } else {
        // Create new guest
        $insert = $conn->prepare("INSERT INTO guest_customers (name, phone, otp_code, otp_expires, session_id) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("sssss", $name, $phone, $otp, $expires, $session_id);
        $insert->execute();
        $guest_id = $conn->insert_id;
    }
    
    // In production, send OTP via SMS API
    // For demo, we'll show it in the response (remove in production!)
    
    $_SESSION['guest_id'] = $guest_id;
    $_SESSION['guest_phone'] = $phone;
    $_SESSION['guest_session_id'] = $session_id;
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP sent to your phone',
        'session_id' => $session_id,
        'otp' => $otp // DEMO ONLY - REMOVE IN PRODUCTION!
    ]);
}

function verifyOTP($conn) {
    $session_id = trim($_POST['session_id'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    // Also support phone-based verification
    $phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    
    if (empty($session_id) && strlen($phone) !== 11) {
        echo json_encode(['success' => false, 'message' => 'Session ID or valid phone required']);
        return;
    }
    
    if (strlen($otp) !== 4 && strlen($otp) !== 6) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
        return;
    }
    
    // Try session_id first, then phone
    if (!empty($session_id)) {
        $stmt = $conn->prepare("SELECT id, name, phone, otp_code, otp_expires FROM guest_customers WHERE session_id = ? AND otp_code = ?");
        $stmt->bind_param("ss", $session_id, $otp);
    } else {
        $stmt = $conn->prepare("SELECT id, name, phone, otp_code, otp_expires FROM guest_customers WHERE phone = ? AND otp_code = ?");
        $stmt->bind_param("ss", $phone, $otp);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
        return;
    }
    
    $guest = $result->fetch_assoc();
    
    // Check if OTP expired
    if (strtotime($guest['otp_expires']) < time()) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        return;
    }
    
    // Mark as verified
    $update = $conn->prepare("UPDATE guest_customers SET otp_verified = 1 WHERE id = ?");
    $update->bind_param("i", $guest['id']);
    $update->execute();
    
    $_SESSION['guest_id'] = $guest['id'];
    $_SESSION['guest_verified'] = true;
    $_SESSION['guest_name'] = $guest['name'];
    $_SESSION['guest_phone'] = $guest['phone'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Phone verified successfully',
        'guest_name' => $guest['name']
    ]);
}

function getProducts($conn) {
    $result = $conn->query("SELECT id, name, price, stock FROM products WHERE stock > 0 ORDER BY name");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode(['success' => true, 'products' => $products]);
}

function addToCart($conn) {
    if (!isset($_SESSION['guest_verified']) || !$_SESSION['guest_verified']) {
        echo json_encode(['success' => false, 'message' => 'Please verify your phone first']);
        return;
    }
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    // Guest minimum per product is 50
    if ($quantity < 50) {
        echo json_encode(['success' => false, 'message' => 'Guest orders require minimum 50 stocks per product']);
        return;
    }
    
    // Check stock availability
    $stock_check = $conn->prepare("SELECT stock, price, name FROM products WHERE id = ?");
    $stock_check->bind_param("i", $product_id);
    $stock_check->execute();
    $product = $stock_check->get_result()->fetch_assoc();
    
    if (!$product || $product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        return;
    }
    
    // Store in session cart
    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }
    
    $_SESSION['guest_cart'][$product_id] = [
        'product_id' => $product_id,
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity
    ];
    
    echo json_encode(['success' => true, 'message' => 'Added to cart', 'cart' => $_SESSION['guest_cart']]);
}

function getCart($conn) {
    $cart = $_SESSION['guest_cart'] ?? [];
    $total_stocks = 0;
    $subtotal = 0;
    
    foreach ($cart as $item) {
        $total_stocks += $item['quantity'];
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Guest discount: 1000 taka for every 100 stocks
    $discount = floor($total_stocks / 100) * 1000;
    $total = max(0, $subtotal - $discount);
    
    echo json_encode([
        'success' => true,
        'cart' => array_values($cart),
        'total_stocks' => $total_stocks,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total,
        'min_required' => 100,
        'can_checkout' => $total_stocks >= 100
    ]);
}

function removeFromCart($conn) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if (isset($_SESSION['guest_cart'][$product_id])) {
        unset($_SESSION['guest_cart'][$product_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Removed from cart']);
}

function calculateTotal($conn) {
    $cart = $_SESSION['guest_cart'] ?? [];
    $total_stocks = 0;
    $subtotal = 0;
    
    foreach ($cart as $item) {
        $total_stocks += $item['quantity'];
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Validate minimum requirements
    $valid = true;
    $errors = [];
    
    if ($total_stocks < 100) {
        $valid = false;
        $errors[] = 'Minimum 100 stocks required for guest orders';
    }
    
    foreach ($cart as $item) {
        if ($item['quantity'] < 50) {
            $valid = false;
            $errors[] = $item['name'] . ' requires minimum 50 stocks';
        }
    }
    
    // Guest discount: 1000 taka for every 100 stocks
    $discount = floor($total_stocks / 100) * 1000;
    $total = max(0, $subtotal - $discount);
    
    echo json_encode([
        'success' => true,
        'valid' => $valid,
        'errors' => $errors,
        'total_stocks' => $total_stocks,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'discount_info' => '৳1000 off per 100 stocks',
        'total' => $total
    ]);
}
