<?php
session_start();
include '../config.php';

$tran_id = $_POST['tran_id'] ?? '';
$status = $_POST['status'] ?? '';
$customer_id = (int)($_POST['value_a'] ?? 0);
$payment_id = (int)($_POST['value_b'] ?? 0);

if ($status === 'VALID' || $status === 'VALIDATED') {
    // Get payment amount
    $pay_stmt = $conn->prepare("SELECT amount FROM membership_payments WHERE id = ? AND tran_id = ?");
    $pay_stmt->bind_param("is", $payment_id, $tran_id);
    $pay_stmt->execute();
    $payment = $pay_stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        $amount = $payment['amount'];
        
        // Update payment status
        $upd_pay = $conn->prepare("UPDATE membership_payments SET status = 'completed' WHERE id = ?");
        $upd_pay->bind_param("i", $payment_id);
        $upd_pay->execute();
        
        // Update customer membership
        $upd_cust = $conn->prepare("UPDATE customers SET is_member = 1, membership_fee_paid = membership_fee_paid + ?, membership_date = NOW() WHERE id = ?");
        $upd_cust->bind_param("di", $amount, $customer_id);
        $upd_cust->execute();
        
        $_SESSION['success'] = 'Membership activated successfully! You now have access to member discounts.';
        header('Location: membership.php');
        exit;
    }
}

$_SESSION['error'] = 'Payment validation failed';
header('Location: membership.php');
exit;
