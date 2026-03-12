<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow including pages to override asset base path (e.g. '../' for subfolders)
if (!isset($base_path)) {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stock Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* --- Modern Flat Design Color Variables for Cohesion --- */
        :root {
            --header-bg: rgba(0, 0, 0, 0.3); /* Glassy dark overlay */
            --header-text: #ffffff; /* White text for contrast */
            --accent-color: #ff9800; /* Vibrant Orange/Gold */
            --logout-bg: #e74c3c; /* Alizarin Red for warnings/actions */
            --bg-color: #f4f7fc; /* Light background for main content */
            --text-color-dark: #34495e;
            --font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--font-family);
            background: var(--bg-color);
            margin: 0;
            color: var(--text-color-dark);
        }

        /* --- Header Styles (Increased Height & Navigation) --- */
        header {
            /* Increased height by removing fixed padding and using min-height */
            height: 100px; /* Sets the target height (between 80px/90px) */
            background-color: var(--header-bg);
            backdrop-filter: blur(10px);
            color: var(--header-text);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); 
            
            /* Use Flexbox Column for Title/Nav layout */
            display: flex;
            flex-direction: column; 
            justify-content: center;
        }

        .header-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 40px 0; /* Padding control */
            width: 100%;
            box-sizing: border-box;
        }

        /* Logo */
        header .logo {
            display: flex;
            align-items: center;
            flex: 1;
        }

        header .logo img {
            height: 80px; /* Slightly adjusted logo size */
            margin-right: 15px;
        }

        /* Project Name */
        header .project-name {
            flex: 2;
            text-align: center;
        }

        header .project-name h1 {
            margin: 0;
            font-size: 24px; /* Slightly smaller for multi-line header */
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--accent-color);
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        }

        /* Logout Button */
        header .logout {
            flex: 1;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        header .logout a {
            padding: 8px 15px;
            background: var(--logout-bg);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        header .logout a:hover {
            background: #c0392b;
        }
        
        /* User Role Info */
        header .user-info {
            color: rgba(255, 255, 255, 0.8); 
            margin-right: 15px; 
            font-size: 0.9rem; 
            font-weight: 400;
        }

       

        /* Main content styling remains for content area outside the header */
        .main-content {
            padding: 20px;
            max-width: 100%;
            width: 100%;
            margin: 20px auto; 
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 80vh;
            box-sizing: border-box;
            overflow-x: auto;
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1200px) {
            .main-content {
                padding: 15px;
                margin: 15px;
                width: calc(100% - 30px);
            }
        }

        @media screen and (max-width: 768px) {
            .header-top-row {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }
            header {
                height: auto;
                min-height: 80px;
            }
            header .logo img {
                height: 50px;
            }
            header .project-name h1 {
                font-size: 16px;
            }
            .main-content {
                padding: 10px;
                margin: 10px;
                width: calc(100% - 20px);
            }
        }
      
    </style>
</head>

<body>

<header>
    <div class="header-top-row">
        <div class="logo">
                <img src="<?php echo $base_path; ?>logo.png" alt="Logo">
        </div>

        <div class="project-name">
            <h1>STOCK MANAGEMENT SYSTEM 📦</h1>
        </div>

        <div class="logout">
            <?php if (isset($_SESSION['user_role'])): ?>
                <span class="user-info">
                    Logged in as: <strong><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'])); ?></strong>
                </span>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
    
    
</header>

<div class="main-content">
    