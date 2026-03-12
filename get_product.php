<?php
include '../config.php';
if(isset($_POST['id'])){
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT stock, price FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    echo json_encode($product);
}
?>
