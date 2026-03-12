<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';

if(!isset($_GET['sell_id'])) {
    die("Sell ID not specified.");
}

$sell_id = intval($_GET['sell_id']);

// Fetch sold product info
$stmt = $conn->prepare("SELECT sp.*, p.stock AS current_stock FROM sell_product sp LEFT JOIN products p ON sp.product_id=p.id WHERE sp.id=?");
$stmt->bind_param("i", $sell_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0) {
    die("Invalid Sell ID.");
}
$order = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $order['id']; ?></title>
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
    <h2>Proforma Invoice</h2>
    <p><strong>Invoice ID:</strong> <?php echo $order['id']; ?></p>
    <p><strong>Date:</strong> <?php echo $order['created_at']; ?></p>

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
            <td><?php echo number_format($order['price']*$order['quantity'],2); ?></td>
        </tr>
    </table>

    <div class="total">
        Grand Total: <?php echo number_format($order['price']*$order['quantity'],2); ?>
    </div>

    <button class="print-btn" onclick="window.print();">🖨️ Print Invoice</button>
</div>

</body>
</html>
