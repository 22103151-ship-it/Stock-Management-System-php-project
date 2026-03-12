<footer>
 <p>  Stock Management System</p>
 <?php
	 // Hide learning journal on customer-facing dashboards
	 if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
 ?>
	 <p><a href="<?php echo isset($base_path) ? $base_path : '../'; ?>learning_journal.php">Learning Journal</a></p>
 <?php } ?>
</footer>