<?php
session_start();
include '../config.php';

if (!isset($_GET['order_id'])) {
    die("Order ID not specified.");
}

$order_id = intval($_GET['order_id']);

// Fetch order info including payment status
$stmt = $conn->prepare("
    SELECT po.quantity, p.price, p.name AS product_name, po.created_at, po.payment_status
    FROM purchase_orders po
    JOIN products p ON po.product_id = p.id
    WHERE po.id=?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Invalid Order ID.");
}

$order = $result->fetch_assoc();

// Calculate total amount
$total_amount = $order['price'] * $order['quantity'];

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $order_id; ?></title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .invoice-box { max-width: 600px; margin:auto; padding:30px; border:1px solid #eee; border-radius:10px; }
        table { width:100%; border-collapse: collapse; margin-top:20px; }
        table th, table td { border:1px solid #ddd; padding:8px; }
        table th { background:#f2f2f2; }
        .total { text-align:right; font-weight:bold; margin-top:20px; }
        .btn { padding:10px 20px; color:white; border:none; border-radius:5px; text-decoration:none; cursor:pointer; display:inline-block; }
        .print-btn { background:#007bff; }
        .pay-btn { background:#28a745; float:right; }
        .paid-btn { background:#6c757d; float:right; cursor:not-allowed; }
    </style>
</head>
<body>

<div class="invoice-box">
    <h2>Invoice</h2>
    <p><strong>Order ID:</strong> <?php echo $order_id; ?></p>
    <p><strong>Date:</strong> <?php echo $order['created_at']; ?></p>
    <p><strong>Payment Status:</strong> <?php echo $order['payment_status']; ?></p>

    <table>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
            <td><?php echo $order['quantity']; ?></td>
            <td><?php echo number_format($order['price'],2); ?></td>
            <td><?php echo number_format($total_amount,2); ?></td>
        </tr>
    </table>

    <div class="total">Grand Total: <?php echo number_format($total_amount,2); ?></div>

    <button class="btn print-btn" onclick="window.print();">🖨️ Print Invoice</button>

    <?php if ($order['payment_status'] == 'Pending'): ?>
        <a href="checkout.php?order_id=<?php echo $order_id; ?>&total_price=<?php echo $total_amount; ?>" 
           class="btn pay-btn">💳 Proceed to Pay</a>
    <?php else: ?>
        <button class="btn paid-btn" disabled>✔ Payment Completed</button>
    <?php endif; ?>
</div>

</body>
</html>
