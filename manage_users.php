<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php'; // Keep header, but no sidebar

// ---------------- Add User ----------------
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // plain text for now
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:green;'>User added successfully!</p>";
}

// ---------------- Edit User ----------------
if (isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $password, $role, $id);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:green;'>User updated successfully!</p>";
}

// ---------------- Delete User ----------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color:red;'>User deleted successfully!</p>";
}

// ---------------- Fetch Users ----------------
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
$user_rows = [];
if ($result) {
    $user_rows = $result->fetch_all(MYSQLI_ASSOC);
}

// ---------------- If editing, fetch user details ----------------
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div style="max-width:900px; margin:20px auto; padding:20px; background:#f8f8f8; border-radius:8px;">
    <!-- Back Button -->
    <a href="dashboard.php" style="display:inline-block; margin-bottom:20px; padding:8px 15px; background:#555; color:white; border-radius:5px; text-decoration:none;"> Back </a>

    <h2>Manage Users</h2>

    <!-- Add / Edit User Form -->
    <form method="POST" style="margin-bottom: 30px;">
        <h3><?php echo $edit_user ? "Edit User" : "Add New User"; ?></h3>

        <input type="hidden" name="id" value="<?php echo $edit_user['id'] ?? ''; ?>">

        <input type="text" name="name" placeholder="Full Name" required value="<?php echo $edit_user['name'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">
        <input type="email" name="email" placeholder="Email" required value="<?php echo $edit_user['email'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">
        <input type="text" name="password" placeholder="Password" required value="<?php echo $edit_user['password'] ?? ''; ?>" style="width:100%; padding:8px; margin:5px 0;">

        <select name="role" required style="width:100%; padding:8px; margin:5px 0;">
            <option value="admin" <?php if(isset($edit_user['role']) && $edit_user['role']=='admin') echo 'selected'; ?>>Admin</option>
            <option value="staff" <?php if(isset($edit_user['role']) && $edit_user['role']=='staff') echo 'selected'; ?>>Staff</option>
            <option value="supplier" <?php if(isset($edit_user['role']) && $edit_user['role']=='supplier') echo 'selected'; ?>>Supplier</option>
        </select>

        <button type="submit" name="<?php echo $edit_user ? 'edit_user' : 'add_user'; ?>" class="<?php echo $edit_user ? 'btn-edit' : 'btn-add'; ?>">
            <?php echo $edit_user ? '✏️ Update User' : '➕ Add User'; ?>
        </button>
        <?php if ($edit_user): ?>
            <a href="manage_users.php" style="margin-left:10px; color:#555;">Cancel</a>
        <?php endif; ?>
    </form>

    <!-- Users Table -->
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:white; text-align:left;">
        <tr style="background:#ddd;">
            <th>Serial</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th style="width:160px; text-align:center;">Action</th>
        </tr>
        <?php if (empty($user_rows)): ?>
            <tr><td colspan="5" style="text-align:center; color:#666;">No users found.</td></tr>
        <?php else: ?>
            <?php $serial = count($user_rows); foreach ($user_rows as $row): ?>
            <tr>
                <td><?php echo $serial--; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo $row['role']; ?></td>
                <td style="white-space:nowrap; text-align:center;">
                    <a href="manage_users.php?edit=<?php echo $row['id']; ?>" class="btn-edit" style="text-decoration:none; margin-right:8px;">✏️ Edit</a>
                    <a href="manage_users.php?delete=<?php echo $row['id']; ?>" class="btn-delete" style="text-decoration:none;" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
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

       
    </div>
    
    <!-- <footer style="background-color: gray;"
            
            height: 10px;>

    <p>  Stock Management System</p>
</footer> -->

