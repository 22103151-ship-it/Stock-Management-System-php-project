<?php
session_start();
$_SESSION['error'] = 'Membership payment cancelled.';
header('Location: membership.php');
exit;
