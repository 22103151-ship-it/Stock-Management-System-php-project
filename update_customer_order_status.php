<?php
session_start();
include '../config.php';
include '../includes/notification_functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit("Unauthorized");
}

if(isset($_POST['status']) && is_array($_POST['status'])) {
    foreach($_POST['status'] as $order_id => $status) {
        $order_id = intval($order_id);
        $status = $conn->real_escape_string($status);

        // Update the order status
        $stmt = $conn->prepare("UPDATE customer_orders SET status=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Get customer_id for this order
            $customer_query = $conn->prepare("SELECT customer_id FROM customer_orders WHERE id = ?");
            $customer_query->bind_param("i", $order_id);
            $customer_query->execute();
            $customer_result = $customer_query->get_result();
            $order_data = $customer_result->fetch_assoc();
            $customer_id = $order_data['customer_id'];

            // Send notification based on status
            if ($status === 'delivered') {
                sendOrderNotification($customer_id, $order_id, $conn);
            } else {
                sendOrderStatusUpdateNotification($customer_id, $order_id, $status, $conn);
            }
        }

        $stmt->close();
    }
    echo "success";
} else {
    echo "No data to update";
}
?>