<?php
session_start();
include '../config.php';
require_once '../includes/sslcommerz_config.php';
require_once '../includes/sslcommerz_helper.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$total_price = isset($_GET['total_price']) ? floatval($_GET['total_price']) : 0;

if($order_id == 0 || $total_price <= 0){
    die("Invalid order ID or total price.");
}

// Optional: verify order exists in DB
$stmt = $conn->prepare("SELECT id FROM purchase_orders WHERE id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0){
    die("Invalid order ID.");
}
$stmt->close();

// SSLCOMMERZ Payment
$base = sslcommerz_base_url();

$post_data = array();
$post_data['store_id'] = $SSLCOMMERZ_STORE_ID;
$post_data['store_passwd'] = $SSLCOMMERZ_STORE_PASS;
$post_data['total_amount'] = $total_price;
$post_data['currency'] = $SSLCOMMERZ_CURRENCY;
$post_data['tran_id'] = "PO_" . $order_id . "_" . time();

// Success/Fail/Cancel URLs
$post_data['success_url'] = $base . "/admin/success.php?order_id=" . $order_id;
$post_data['fail_url'] = $base . "/admin/purchase_orders.php?error=payment_failed";
$post_data['cancel_url'] = $base . "/admin/purchase_orders.php?error=payment_cancelled";

// Customer Info
$post_data['cus_name'] = "Admin Purchase Order";
$post_data['cus_email'] = "admin@stock.local";
$post_data['cus_add1'] = "Dhaka";
$post_data['cus_city'] = "Dhaka";
$post_data['cus_country'] = "Bangladesh";
$post_data['cus_phone'] = "01711111111";
$post_data['shipping_method'] = "NO";
$post_data['product_name'] = "Purchase Order #" . $order_id;
$post_data['product_category'] = "Stock Purchase";
$post_data['product_profile'] = "general";

// Use helper function for API call
$sslcz = sslcommerz_init_payment($post_data, (bool)$SSLCOMMERZ_SANDBOX);

if(!empty($sslcz['ok']) && !empty($sslcz['gateway_url'])){
    header("Location: " . $sslcz['gateway_url']);
    exit;
} else {
    $error = $sslcz['error'] ?? 'Could not connect to SSLCOMMERZ.';
    die("Payment Error: " . htmlspecialchars($error));
}
?>
