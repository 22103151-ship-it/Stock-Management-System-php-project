<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url('assets/images/home-bg.jpg') center/cover fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .hero-section {
            text-align: center;
            color: white;
            max-width: 600px;
        }

        .hero-section h1 {
            font-size: 48px;
            margin-bottom: 15px;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .hero-section p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .hero-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 900px;
            margin-top: 50px;
        }

        .role-card {
            background: white;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.15);
        }

        .role-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #667eea;
        }

        .role-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }

        .role-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .role-card a {
            display: inline-block;
            padding: 10px 24px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .role-card a:hover {
            background: #764ba2;
        }

        .home-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 26px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            backdrop-filter: blur(8px);
            transition: transform 0.2s, background 0.2s;
        }

        .home-link:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.22);
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 36px;
            }

            .hero-section p {
                font-size: 16px;
            }

            .role-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            📦 Stock Management System
        </div>
    </header>

    <main>
        <div class="hero-section">
            <div class="hero-icon">📦</div>
            <h1>Welcome</h1>
            <p>Manage your stock efficiently with our intuitive system</p>

            <div class="role-cards">
                <div class="role-card">
                    <i class="fas fa-user-tie"></i>
                    <h3>Admin</h3>
                    <p>Full system access and management</p>
                    <a href="login.php?role=admin">Login</a>
                </div>

                <div class="role-card">
                    <i class="fas fa-user-gear"></i>
                    <h3>Staff</h3>
                    <p>Manage products and orders</p>
                    <a href="login.php?role=staff">Login</a>
                </div>

                <div class="role-card">
                    <i class="fas fa-handshake"></i>
                    <h3>Supplier</h3>
                    <p>Track your orders and deliveries</p>
                    <a href="login.php?role=supplier">Login</a>
                </div>

                <div class="role-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Customer</h3>
                    <p>Browse products and place orders</p>
                    <a href="login.php?role=customer">Login</a>
                </div>
            </div>

            <a class="home-link" href="home.php">Go to Home Page</a>
        </div>
    </main>
</body>
</html>
