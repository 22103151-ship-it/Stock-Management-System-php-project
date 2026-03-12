<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php'; // Keep header only

// ---------------- Add Supplier ----------------
if (isset($_POST['add_supplier'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO suppliers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $address);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:green;'>Supplier added successfully!</p>";
}

// ---------------- Edit Supplier ----------------
if (isset($_POST['edit_supplier'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("UPDATE suppliers SET name=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:green;'>Supplier updated successfully!</p>";
}

// ---------------- Delete Supplier ----------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:red;'>Supplier deleted successfully!</p>";
}

// ---------------- Fetch Suppliers ----------------
$result = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
$supplier_rows = [];
if ($result) {
    $supplier_rows = $result->fetch_all(MYSQLI_ASSOC);
}

// ---------------- If editing, fetch supplier details ----------------
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_supplier = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div style="max-width:900px; margin:20px auto; padding:20px; background:#f8f8f8; border-radius:8px;">

    <!-- Back Button -->
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;">Back </a>

    <h2>Manage Suppliers</h2>

    <!-- Add / Edit Supplier Form -->
    <form method="POST" style="margin-bottom: 30px;">
        <h3><?php echo $edit_supplier ? "Edit Supplier" : "Add New Supplier"; ?></h3>

        <input type="hidden" name="id" value="<?php echo $edit_supplier['id'] ?? ''; ?>">

        <input type="text" name="name" placeholder="Supplier Name" required value="<?php echo $edit_supplier['name'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">
        <input type="email" name="email" placeholder="Email" required value="<?php echo $edit_supplier['email'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">
        <input type="text" name="phone" placeholder="Phone" value="<?php echo $edit_supplier['phone'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">
        <input type="text" name="address" placeholder="Address" value="<?php echo $edit_supplier['address'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">

        <button type="submit" name="<?php echo $edit_supplier ? 'edit_supplier' : 'add_supplier'; ?>" class="<?php echo $edit_supplier ? 'btn-edit' : 'btn-add'; ?>">
            <?php echo $edit_supplier ? '✏️ Update Supplier' : '➕ Add Supplier'; ?>
        </button>
        <?php if ($edit_supplier): ?>
            <a href="suppliers.php" style="margin-left:10px; color:#555;">Cancel</a>
        <?php endif; ?>
    </form>

    <!-- Suppliers Table -->
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:white; text-align:left;">
        <tr style="background:#ddd;">
            <th>Serial</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th style="width:160px; text-align:center;">Action</th>
        </tr>
        <?php if (empty($supplier_rows)): ?>
            <tr><td colspan="6" style="text-align:center; color:#666;">No suppliers found.</td></tr>
        <?php else: ?>
            <?php $serial = count($supplier_rows); foreach ($supplier_rows as $row): ?>
            <tr>
                <td><?php echo $serial--; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['address']); ?></td>
                <td style="white-space:nowrap; text-align:center;">
                    <a href="suppliers.php?edit=<?php echo $row['id']; ?>" class="btn-edit" style="text-decoration:none; margin-right:8px;">✏️ Edit</a>
                    <a href="suppliers.php?delete=<?php echo $row['id']; ?>" class="btn-delete" style="text-decoration:none;" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

       
    </div>
    <style>
        /* Responsive Grid */
@media (max-width: 992px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}

@media (max-width: 600px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
    </style>
    <!-- <footer style="background-color: gray;"
            
            height: 10px;>

    <p>  Stock Management System</p>
</footer> -->


