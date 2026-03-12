<?php
session_start();
session_unset();
session_destroy();
// Show a logout confirmation page with a Back to Home link
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Logged Out - Stock Management System</title>
	<style>
		* { box-sizing: border-box; }
		body {
			font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url('assets/images/home-bg.jpg') center/cover fixed;
			color: #fff;
			margin: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			padding: 20px;
		}
		.card {
			background: rgba(255, 255, 255, 0.15);
			backdrop-filter: blur(12px);
			-webkit-backdrop-filter: blur(12px);
			padding: 30px 34px;
			border-radius: 14px;
			text-align: center;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
			border: 1px solid rgba(255, 255, 255, 0.25);
			max-width: 420px;
			width: 100%;
		}
		a.button {
			display: inline-block;
			margin-top: 16px;
			padding: 10px 18px;
			background: #fff;
			color: #333;
			border-radius: 8px;
			text-decoration: none;
			font-weight: 600;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>Signed Out</h1>
		<p>You have been logged out successfully.</p>
		<a class="button" href="home.php">Back to Home</a>
	</div>
</body>
</html>

