<?php
session_start();
include '../config.php'; // DB connection

// ✅ Get order_id from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if($order_id == 0){
    die("Order ID not found.");
}

// Get payment info from POST
$tran_id   = isset($_POST['tran_id']) ? $_POST['tran_id'] : uniqid("txn_");
$card_type = isset($_POST['card_type']) ? $_POST['card_type'] : 'Unknown';
$tran_date = isset($_POST['tran_date']) ? $_POST['tran_date'] : date("Y-m-d H:i:s");

// Insert into admin_payments table
$stmt = $conn->prepare("INSERT INTO admin_payments (transaction_id, payment_type, transaction_date) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $tran_id, $card_type, $tran_date);

if ($stmt->execute()) {
    echo "✅ Payment recorded successfully.<br>";

    // Update the order as Paid
    $update = $conn->prepare("UPDATE purchase_orders SET payment_status='Paid' WHERE id=?");
    $update->bind_param("i", $order_id);
    $update->execute();
    $update->close();

    echo "✔ Invoice updated as Paid.<br>";

} else {
    echo "❌ Database Error: " . $stmt->error;
}

$stmt->close();

echo "Transaction ID: $tran_id<br>";
echo "Payment Type: $card_type<br>";
echo "Transaction Date: $tran_date<br>";
?>
