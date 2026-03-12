<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supplier') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';

$supplier_id = 0;
if (file_exists('../includes/supplier_helpers.php')) {
    include '../includes/supplier_helpers.php';
    $supplier_id = getResolvedSupplierId($conn);
}

if (!isset($_GET['order_id'])) {
    die("Order ID not specified.");
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$stmt = null;
if ($supplier_id > 0) {
    $stmt = $conn->prepare("
        SELECT po.id, po.quantity, po.created_at, p.name AS product_name, p.price
        FROM purchase_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.id = ? AND po.supplier_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $supplier_id);
} else {
    $stmt = $conn->prepare("
        SELECT po.id, po.quantity, po.created_at, p.name AS product_name, p.price
        FROM purchase_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.id = ?
    ");
    $stmt->bind_param("i", $order_id);
}
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found.");
}

$total_price = $order['quantity'] * $order['price'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Proforma Invoice - Order #<?php echo $order['id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-weight: bold; }
        .button-row {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .print-button, .payment-button {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .print-button {
            background: #28a745; /* Green */
        }
        .payment-button {
            background: #007bff; /* Blue */
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <h2>Proforma Invoice</h2>

        <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
        <p><strong>Date:</strong> <?php echo $order['created_at']; ?></p>

        <table>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price (per unit)</th>
                <th>Total</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td><?php echo number_format($order['price'], 2); ?></td>
                <td><?php echo number_format($total_price, 2); ?></td>
            </tr>
        </table>

        <p class="total">Grand Total: <?php echo number_format($total_price, 2); ?></p>

        <!-- Buttons Row -->
        <div class="button-row">
            <a href="javascript:window.print()" class="print-button">🖨️ Print Invoice</a>
          
        </div>
    </div>
</body>
</html>
