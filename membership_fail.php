<?php
session_start();
$_SESSION['error'] = 'Membership payment failed. Please try again.';
header('Location: membership.php');
exit;
