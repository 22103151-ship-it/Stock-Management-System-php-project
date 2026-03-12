<?php
session_start();

// Clear pending registration
unset($_SESSION['pending_registration']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Failed</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            max-width: 450px;
        }
        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff4757, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .icon i {
            font-size: 50px;
            color: #fff;
        }
        h1 {
            color: #ff4757;
            margin-bottom: 15px;
        }
        p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin: 5px;
        }
        .btn-vip {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <i class="fas fa-times"></i>
        </div>
        <h1>Payment Failed</h1>
        <p>Your payment was not successful. Please try again.</p>
        <a href="register_pro.php" class="btn"><i class="fas fa-star"></i> Pro Registration</a>
        <a href="register_vip.php" class="btn btn-vip"><i class="fas fa-crown"></i> VIP Registration</a>
    </div>
</body>
</html>
