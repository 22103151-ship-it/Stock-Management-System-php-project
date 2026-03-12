<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config.php';
include '../includes/notification_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $customer_id = $_SESSION['customer_id'];

    // Verify the notification belongs to the customer
    $check_query = $conn->prepare("SELECT id FROM automated_notifications WHERE id = ? AND customer_id = ?");
    $check_query->bind_param("ii", $notification_id, $customer_id);
    $check_query->execute();

    if ($check_query->get_result()->num_rows > 0) {
        if (markNotificationAsRead($notification_id, $conn)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>