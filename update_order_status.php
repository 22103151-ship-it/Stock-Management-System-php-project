<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit("Unauthorized");
}

if(isset($_POST['status']) && is_array($_POST['status'])) {
    foreach($_POST['status'] as $order_id => $status) {
        $order_id = intval($order_id);
        $status = $conn->real_escape_string($status);

        $stmt = $conn->prepare("UPDATE purchase_orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();
    }
    echo "success";
} else {
    echo "No data to update";
}
?>
