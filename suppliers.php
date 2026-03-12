<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php'; // Header and sidebar

// Fetch suppliers
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");
$supplier_rows = [];
if ($suppliers) {
    $supplier_rows = $suppliers->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="main-container">
    <!-- Back Button -->
    <a href="dashboard.php" class="back-btn">Back</a>

    <h2 class="page-title">Suppliers (View Only)</h2>

    <div class="table-container">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Serial</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($supplier_rows)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#666;">No suppliers found.</td></tr>
                <?php else: ?>
                    <?php $serial = count($supplier_rows); foreach ($supplier_rows as $s): ?>
                    <tr>
                        <td><?php echo $serial--; ?></td>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                        <td><?php echo htmlspecialchars($s['address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .main-container {
        max-width: 1000px;
        margin: 40px auto;
        background: #fff;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .page-title {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .back-btn {
        display: inline-block;
        margin-bottom: 20px;
        padding: 8px 15px;
        background: #555;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .back-btn:hover {
        background: #333;
    }

    .table-container {
        overflow-x: auto;
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 auto;
        font-size: 15px;
        border-radius: 5px;
        overflow: hidden;
    }

    .styled-table thead tr {
        background-color: #007BFF;
        color: #ffffff;
        text-align: left;
    }

    .styled-table th, .styled-table td {
        padding: 12px 15px;
        border: 1px solid #ddd;
    }

    .styled-table tbody tr:nth-child(even) {
        background-color: #f3f3f3;
    }

    .styled-table tbody tr:hover {
        background-color: #e9f5ff;
    }
</style>
