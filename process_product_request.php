<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/notification_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity_needed = (int)$_POST['quantity_needed'];
    $urgency = $_POST['urgency'];
    $reason = trim($_POST['reason']);
    $staff_id = $_SESSION['user_id'];

    // Validate urgency level
    $valid_urgencies = ['low', 'medium', 'high', 'critical'];
    if (!in_array($urgency, $valid_urgencies)) {
        $urgency = 'medium';
    }

    // Insert product request
    $request_id = handleStaffProductNeed($product_id, $staff_id, $quantity_needed, $urgency, $conn);

    if ($request_id) {
        // Redirect back to dashboard with success message
        header("Location: dashboard.php?success=Product request submitted successfully");
        exit;
    } else {
        // Redirect back with error message
        header("Location: dashboard.php?error=Failed to submit product request");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>