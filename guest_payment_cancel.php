<?php
session_start();
$_SESSION['error'] = 'Payment cancelled.';
header('Location: home.php');
exit;
