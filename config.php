<?php
// Database configuration
$host = "localhost";
$user = "root";                 // XAMPP default user
$pass = "";                     // XAMPP default password is empty
$db   = "stock_management_system";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to avoid encoding issues
$conn->set_charset("utf8");
?>
