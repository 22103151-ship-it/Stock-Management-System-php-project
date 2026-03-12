<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supplier') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config.php';
include '../includes/notification_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['response'])) {
    $supplier_order_id = (int)$_POST['order_id'];
    $response = $_POST['response'];

    // Validate response
    $valid_responses = ['accepted', 'later', 'cancelled'];
    if (!in_array($response, $valid_responses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid response']);
        exit;
    }

    // Handle supplier response
    if (handleSupplierResponse($supplier_order_id, $response, $conn)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit response']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>