<?php
include '../config.php';
if(isset($_POST['query'])){
    $search = "%" . $_POST['query'] . "%";
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE name LIKE ? ORDER BY name ASC");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        echo "<div class='suggestion-item' data-id='".$row['id']."' style='padding:5px; cursor:pointer;'>".$row['name']."</div>";
    }
}
?>
