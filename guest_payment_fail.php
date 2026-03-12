<?php
session_start();
$_SESSION['error'] = 'Payment failed. Please try again.';
header('Location: home.php');
exit;
