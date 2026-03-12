<?php
// Test script to verify AirPod image setup
include '../config.php';

if (!$conn) {
    die("Database connection failed");
}

echo "<h1>AirPod Image Setup Test</h1>";

// Check if image column exists
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'image'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Image column exists in products table</p>";
} else {
    echo "<p style='color: red;'>❌ Image column missing from products table</p>";
}

// Check AirPod product
$result = $conn->query("SELECT * FROM products WHERE name = 'Air pod'");
if ($result && $row = $result->fetch_assoc()) {
    echo "<p>📱 AirPod Product Found:</p>";
    echo "<ul>";
    echo "<li>ID: " . $row['id'] . "</li>";
    echo "<li>Name: " . $row['name'] . "</li>";
    echo "<li>Price: ৳" . $row['price'] . "</li>";
    echo "<li>Stock: " . $row['stock'] . "</li>";
    echo "<li>Image: " . ($row['image'] ?? 'Not set') . "</li>";
    echo "</ul>";

    if (!empty($row['image'])) {
        $image_path = "../assets/images/" . $row['image'];
        if (file_exists($image_path)) {
            echo "<p style='color: green;'>✅ Image file exists: $image_path</p>";
            echo "<img src='$image_path' alt='AirPod' style='max-width: 200px; border: 1px solid #ddd;'>";
        } else {
            echo "<p style='color: orange;'>⚠️ Image file not found: $image_path</p>";
            echo "<p>Upload your airpod.jpg image to the assets/images/ directory</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No image set for AirPod product</p>";
    }
} else {
    echo "<p style='color: red;'>❌ AirPod product not found in database</p>";
}

echo "<hr>";
echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Make sure the database has the image column</li>";
echo "<li>Upload airpod.jpg to assets/images/ directory</li>";
echo "<li>The AirPod product should display the image in customer products page</li>";
echo "</ol>";
?>