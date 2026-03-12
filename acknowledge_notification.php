<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];

    // Deactivate the notification dot
    $query = $conn->prepare("UPDATE notification_dots SET is_active = 0 WHERE id = ? AND to_user_type = 'staff'");
    $query->bind_param("i", $notification_id);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to acknowledge notification']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>