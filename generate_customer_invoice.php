<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';

if (!isset($_GET['order_id'])) {
    die("Order ID not specified.");
}

$order_id = intval($_GET['order_id']);

$stmt = $conn->prepare("SELECT co.id, co.quantity, co.price, co.order_date, co.status, p.name AS product_name, c.name AS customer_name FROM customer_orders co JOIN products p ON co.product_id = p.id JOIN customers c ON co.customer_id = c.id WHERE co.id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid Order ID.");
}

$order = $result->fetch_assoc();
$total_amount = $order['price'] * $order['quantity'];
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $order_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .invoice-box { max-width: 600px; margin:auto; padding:30px; border:1px solid #eee; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,.15); }
        h2 { text-align:center; }
        table { width:100%; line-height:inherit; text-align:left; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border:1px solid #ddd; padding:8px; }
        table th { background:#f2f2f2; }
        .total { text-align:right; margin-top:20px; font-weight:bold; }
        .print-btn { margin-top: 20px; padding:10px 20px; background:#28a745; color:white; border:none; border-radius:5px; cursor:pointer; }
    </style>
</head>
<body>

<div class="invoice-box">
    <h2>Customer Invoice</h2>
    <p><strong>Invoice ID:</strong> <?php echo $order['id']; ?></p>
    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
    <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
    <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>

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

    <div class="total">
        Grand Total: <?php echo number_format($total_amount,2); ?>
    </div>

    <button class="print-btn" onclick="window.print();">🖨️ Print Invoice</button>
</div>

</body>
</html>
