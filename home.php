<?php
session_start();
include 'config.php';
include 'includes/notification_functions.php';

// Get notification dot counts for different user types
$admin_dots = getNotificationDotCounts('admin', $conn);
$staff_dots = getNotificationDotCounts('staff', $conn);
$supplier_dots = getNotificationDotCounts('supplier', $conn);
$customer_dots = getNotificationDotCounts('customer', $conn);

// Check if any user type has notifications
$has_notifications = array_sum($admin_dots) > 0 || array_sum($staff_dots) > 0 || array_sum($supplier_dots) > 0 || array_sum($customer_dots) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management System - Home</title>
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


        /* Header with 3-Dot Menu */
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

        /* Revenue Display */
        .revenue-section {
            display: flex;
            gap: 20px;
            align-items: center;
            flex: 1;
            margin: 0 30px;
            flex-wrap: wrap;
        }

        .revenue-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .revenue-card label {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .revenue-card .amount {
            font-size: 20px;
            font-weight: 700;
            color: #4ade80;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-left: auto;
        }

        .menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .menu-btn:hover {
            transform: scale(1.1);
        }

        /* Notification Dots */
        .menu-btn {
            position: relative;
        }

        .notification-dots {
            position: absolute;
            top: -5px;
            right: -5px;
            display: flex;
            gap: 2px;
            flex-wrap: wrap;
            max-width: 40px;
        }

        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: 1px solid white;
            animation: pulse 2s infinite;
        }

        .notification-dot.blue { background-color: #2196f3; }
        .notification-dot.green { background-color: #00c853; }
        .notification-dot.yellow { background-color: #ffd600; }
        .notification-dot.red { background-color: #ff1744; }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Slide-in Menu */
        .dropdown-menu {
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            width: 260px;
            background: #ffffff;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
            pointer-events: none;
        }

        .dropdown-menu.active {
            transform: translateX(0);
            pointer-events: auto;
        }

        .dropdown-menu button {
            width: 100%;
            padding: 12px 14px;
            border: none;
            background: #f5f7fb;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            border-radius: 10px;
            transition: background 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-menu button:hover {
            background: #e9edf5;
            transform: translateX(-2px);
        }

        .dropdown-menu button.divider {
            border-top: 1px solid #e0e5ec;
            padding: 10px 14px 0 14px;
            font-size: 12px;
            color: #999;
            cursor: default;
            background: transparent;
            transform: none;
        }

        .dropdown-menu button.divider:hover {
            background: transparent;
        }

        .dropdown-menu button i {
            width: 20px;
            text-align: center;
            color: #000;
        }

        /* About Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .modal-content li {
            color: #666;
            margin-bottom: 8px;
        }

        .partner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .partner-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
            color: #555;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .partner-card i {
            font-size: 20px;
            width: 22px;
            text-align: center;
            color: #444;
        }

        .partner-card span {
            font-weight: 600;
            color: #444;
        }

        .dropdown-menu button.divider {
            border-top: 1px solid #eee;
            padding: 6px 16px;
            font-size: 12px;
            color: #999;
            cursor: default;
        }

        .dropdown-menu button.divider:hover {
            background: none;
        }

        /* Main Content */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .hero-section {
            text-align: center;
            color: #ffffff;
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

        /* User Role Cards (visible on home, styled cards) */
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 900px;
            margin-top: 50px;
        }

        .role-card {
            background:
                radial-gradient(circle at 18% 22%, rgba(255, 255, 255, 0.28), transparent 42%),
                radial-gradient(circle at 80% 0%, rgba(255, 255, 255, 0.24), transparent 44%),
                linear-gradient(135deg, rgba(0, 214, 255, 0.55), rgba(255, 98, 160, 0.48));
            backdrop-filter: blur(18px);
            border-radius: 14px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 20px 42px rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            color: rgb(20, 24, 32);
        }

        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.22);
        }

        .role-card i {
            font-size: 48px;
            margin-bottom: 15px;
            color: rgb(20, 24, 32);
        }

        .role-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: rgb(20, 24, 32);
        }

        .role-card p {
            color: rgb(20, 24, 32);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .role-card a {
            display: inline-block;
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.78);
            color: rgb(20, 24, 32);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.36);
            backdrop-filter: blur(6px);
        }

        .role-card a:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
        }

        /* Responsive */
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

            .revenue-section {
                margin: 0 15px;
                gap: 10px;
            }

            .revenue-card {
                padding: 10px 16px;
            }

            .revenue-card .amount {
                font-size: 16px;
            }

            .modal-content {
                padding: 30px;
            }
        }

        /* Overlay for menu close */
        .menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            z-index: 999;
        }

        .menu-overlay.active {
            display: block;
        }

        /* Product search bar (now in header) */
        .product-search {
            width: min(460px, 50vw);
            margin: 0;
            background: transparent;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            backdrop-filter: none;
            border: none;
            color: #111;
        }

        .product-search form {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .search-input-wrap {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 10px 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .search-input-wrap i {
            color: #777;
            font-size: 16px;
        }

        #productSearchInput {
            border: none;
            outline: none;
            font-size: 15px;
            background: transparent;
            color: #555;
        }

        #productSearchInput::placeholder {
            color: #888;
        }

        .search-input-wrap button {
            border: none;
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
            box-shadow: 0 8px 18px rgba(0, 114, 255, 0.3);
        }

        .search-input-wrap button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(0, 114, 255, 0.35);
        }

        .search-status {
            font-size: 13px;
            color: #1f2937;
            min-height: 18px;
            padding-left: 2px;
            font-weight: 600;
        }

        .search-status.success { color: #0f766e; }
        .search-status.empty { color: #b91c1c; }
        .search-status.muted { color: #4b5563; font-weight: 500; }

        /* Toast notification */
        .toast-container {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1600;
        }

        .toast {
            background: rgba(17, 24, 39, 0.92);
            color: #fff;
            padding: 12px 14px;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(8px);
            min-width: 220px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.25s ease;
        }

        .toast.success { background: rgba(34, 197, 94, 0.92); }
        .toast.error { background: rgba(239, 68, 68, 0.92); }
        .toast.info { background: rgba(59, 130, 246, 0.92); }

        .toast i { font-size: 16px; }

        .search-results {
            margin: 12px auto 0 auto;
            display: grid;
            gap: 8px;
            width: min(960px, 95%);
        }

        .result-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items: center;
            padding: 12px 12px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            color: #555;
        }

        .result-row .name {
            font-weight: 700;
            color: #444;
        }

        .result-row .stock {
            font-size: 13px;
            color: #666;
        }

        .result-row .price {
            font-weight: 600;
            color: #444;
        }

        .result-row.in-stock .stock { color: #2e8b57; }
        .result-row.out-of-stock .stock { color: #c0392b; }

        @media (max-width: 640px) {
            .result-row {
                grid-template-columns: 1fr;
                align-items: flex-start;
            }

            .search-input-wrap {
                grid-template-columns: auto 1fr;
                grid-template-rows: auto auto;
            }

            .search-input-wrap button {
                grid-column: 1 / -1;
                justify-self: flex-end;
            }
        }

        /* Guest Order Button */
        .guest-order-btn {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0, 255, 136, 0.4);
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .guest-order-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 255, 136, 0.5);
        }

        /* Guest Order Modal */
        .guest-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .guest-modal.active { display: flex; }
        .guest-modal-content {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            color: #fff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .guest-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .guest-modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 28px;
            cursor: pointer;
        }
        .guest-step { display: none; }
        .guest-step.active { display: block; }
        .guest-input {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .guest-input::placeholder { color: rgba(255,255,255,0.5); }
        .guest-btn {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
            border: none;
            padding: 12px 30px;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
        }
        .guest-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }
        .otp-display {
            background: rgba(0,255,136,0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-display h3 { color: #00ff88; font-size: 32px; letter-spacing: 5px; }
        .guest-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: 300px;
            overflow-y: auto;
            margin: 15px 0;
        }
        .guest-product-card {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: border 0.3s;
        }
        .guest-product-card.selected {
            border-color: #00ff88;
        }
        .guest-product-name { font-weight: 600; margin-bottom: 5px; }
        .guest-product-price { color: #00ff88; margin-bottom: 5px; }
        .guest-product-stock { font-size: 12px; color: rgba(255,255,255,0.6); }
        .guest-qty-input {
            width: 80px;
            padding: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: #fff;
            margin-top: 10px;
        }
        .guest-cart-summary {
            background: rgba(0,255,136,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .guest-discount { color: #00ff88; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            📦 Stock Management System
        </div>
        <div class="revenue-section">
            <div class="revenue-card">
                <label>Monthly Revenue</label>
                <div class="amount">৳ 2,50,000</div>
            </div>
            <div class="revenue-card">
                <label>Annual Growth</label>
                <div class="amount">45%</div>
            </div>
            <div class="revenue-card">
                <label>Active Partners</label>
                <div class="amount">12+</div>
            </div>
        </div>
        <div class="header-right">
            <section class="product-search">
                <form id="productSearchForm">
                    <div class="search-input-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearchInput" name="query" placeholder="Search products in stock..." aria-label="Search products">
                        <button type="submit">Search</button>
                    </div>
                    <div class="search-status" id="productSearchStatus"></div>
                </form>
            </section>
            <button class="menu-btn" id="menuBtn">
                <i class="fas fa-bars"></i>
                <?php if ($has_notifications): ?>
                <div class="notification-dots">
                    <?php
                    // Role-specific dots
                    if (array_sum($admin_dots) > 0) echo '<div class="notification-dot red" title="Admin notifications"></div>';
                    if (array_sum($customer_dots) > 0) echo '<div class="notification-dot blue" title="Customer notifications"></div>';
                    if (array_sum($staff_dots) > 0) echo '<div class="notification-dot green" title="Staff notifications"></div>';
                    if (array_sum($supplier_dots) > 0) echo '<div class="notification-dot yellow" title="Supplier notifications"></div>';
                    ?>
                </div>
                <?php endif; ?>
            </button>
        </div>
    </header>

    <!-- Dropdown Menu -->
    <div class="menu-overlay" id="menuOverlay"></div>
    <div class="dropdown-menu" id="dropdownMenu">
        <button onclick="selectRole('admin')">
            <i class="fas fa-user-tie"></i> Admin
        </button>
        <button onclick="selectRole('staff')">
            <i class="fas fa-user-nurse"></i> Staff
        </button>
        <button onclick="selectRole('supplier')">
            <i class="fas fa-handshake"></i> Supplier
        </button>
        <button onclick="selectRole('customer')">
            <i class="fas fa-shopping-cart"></i> Customer
        </button>
        <button class="divider">Partnership</button>
        <button onclick="openAbout()">
            <i class="fas fa-info-circle"></i> About Us
        </button>
    </div>

    <!-- Search Results (below header) -->
    <div class="search-results" id="productSearchResults"></div>

    <!-- Guest Order Modal -->
    <div class="guest-modal" id="guestOrderModal">
        <div class="guest-modal-content">
            <div class="guest-modal-header">
                <h2><i class="fas fa-shopping-bag me-2"></i>Guest Order</h2>
                <button class="guest-modal-close" onclick="closeGuestOrderModal()">&times;</button>
            </div>

            <!-- Step 1: Phone Verification -->
            <div class="guest-step active" id="guestStep1">
                <h4 style="margin-bottom: 15px;">Enter your details</h4>
                <input type="text" class="guest-input" id="guestName" placeholder="Your Name" required>
                <input type="tel" class="guest-input" id="guestPhone" placeholder="Phone Number (11 digits)" maxlength="11" required>
                <button class="guest-btn" onclick="sendGuestOTP()">
                    <i class="fas fa-paper-plane me-2"></i>Send OTP
                </button>
            </div>

            <!-- Step 2: OTP Verification -->
            <div class="guest-step" id="guestStep2">
                <h4 style="margin-bottom: 15px;">Verify OTP</h4>
                <div class="otp-display">
                    <p>Demo OTP (for testing):</p>
                    <h3 id="displayOTP">----</h3>
                    <small style="color: rgba(255,255,255,0.5);">In production, OTP will be sent via SMS</small>
                </div>
                <input type="text" class="guest-input" id="guestOTP" placeholder="Enter 4-digit OTP" maxlength="4">
                <button class="guest-btn" onclick="verifyGuestOTP()">
                    <i class="fas fa-check me-2"></i>Verify OTP
                </button>
            </div>

            <!-- Step 3: Product Selection -->
            <div class="guest-step" id="guestStep3">
                <h4 style="margin-bottom: 15px;">Select Products</h4>
                <div style="background: rgba(255,193,7,0.1); padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-info-circle me-2" style="color: #ffc107;"></i>
                    <strong>Guest Requirements:</strong> Minimum 100 stocks total, minimum 10 per product. 
                    Get ৳1000 discount for every 100 stocks ordered!
                </div>
                <div class="guest-product-grid" id="guestProductGrid">
                    <!-- Products loaded dynamically -->
                </div>
                <div class="guest-cart-summary" id="guestCartSummary" style="display: none;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Total Stocks:</span>
                        <strong id="guestTotalStocks">0</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Subtotal:</span>
                        <span id="guestSubtotal">৳0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;" id="guestDiscountRow" style="display: none;">
                        <span class="guest-discount">Discount:</span>
                        <span class="guest-discount" id="guestDiscount">-৳0</span>
                    </div>
                    <hr style="border-color: rgba(255,255,255,0.2); margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between;">
                        <h4>Total:</h4>
                        <h4 id="guestTotal">৳0</h4>
                    </div>
                </div>
                <button class="guest-btn" id="guestCheckoutBtn" onclick="guestCheckout()" disabled>
                    <i class="fas fa-credit-card me-2"></i>Pay with bKash/SSLCommerz
                </button>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div class="modal" id="aboutModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>About Our Business</h2>
                <button class="modal-close" onclick="closeAbout()">&times;</button>
            </div>
            <p>Welcome to <strong>Stock Management System</strong> — your trusted partner in inventory and supply chain excellence.</p>
            
            <h3 style="color:#667eea; margin-top:20px;">Our Mission</h3>
            <p>We empower businesses with intelligent stock management solutions that streamline operations, reduce costs, and maximize profitability.</p>

            <h3 style="color:#667eea; margin-top:20px;">Why Partner With Us?</h3>
            <ul>
                <li><strong>Proven Track Record:</strong> ৳2,50,000+ monthly revenue with 45% annual growth</li>
                <li><strong>Reliable Infrastructure:</strong> 99.9% system uptime and 24/7 support</li>
                <li><strong>Scalable Solutions:</strong> Works for startups to enterprise-level operations</li>
                <li><strong>Real-time Analytics:</strong> Data-driven insights for smarter decisions</li>
                <li><strong>Expert Team:</strong> 12+ active partners and growing community</li>
            </ul>

            <h3 style="color:#667eea; margin-top:20px;">Partnership Opportunities</h3>
            <p>We're actively seeking strategic partnerships to expand our reach and impact. If you're interested in collaborating with us, here's what we offer:</p>
            <ul>
                <li>Revenue sharing models</li>
                <li>Technical support and integration assistance</li>
                <li>Marketing and co-branding opportunities</li>
                <li>Custom solutions for unique business needs</li>
            </ul>

            <h3 style="color:#667eea; margin-top:20px;">Our Partner Companies</h3>
            <div class="partner-grid">
                <div class="partner-card"><i class="fab fa-amazon"></i><span>Amazon</span></div>
                <div class="partner-card"><i class="fab fa-apple"></i><span>Apple</span></div>
                <div class="partner-card"><i class="fab fa-google"></i><span>Google</span></div>
                <div class="partner-card"><i class="fab fa-microsoft"></i><span>Microsoft</span></div>
                <div class="partner-card"><i class="fab fa-shopify"></i><span>Shopify</span></div>
                <div class="partner-card"><i class="fab fa-uber"></i><span>Uber</span></div>
                <div class="partner-card"><i class="fab fa-airbnb"></i><span>Airbnb</span></div>
                <div class="partner-card"><i class="fab fa-figma"></i><span>Figma</span></div>
                <div class="partner-card"><i class="fab fa-paypal"></i><span>PayPal</span></div>
                <div class="partner-card"><i class="fab fa-meta"></i><span>Meta</span></div>
                <div class="partner-card"><i class="fab fa-slack"></i><span>Slack</span></div>
                <div class="partner-card"><i class="fab fa-spotify"></i><span>Spotify</span></div>
            </div>

            <h3 style="color:#667eea; margin-top:20px;">Get in Touch</h3>
            <p><strong>Email:</strong> partnerships@stockms.com</p>
            <p><strong>Phone:</strong> +880 1234 567890</p>
            <p><strong>Location:</strong> Dhaka, Bangladesh</p>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <div class="hero-section">
            <div class="hero-icon">📦</div>
            <h1>Welcome</h1>
            <p>Manage your stock efficiently with our intuitive system</p>

            <!-- Guest Order Button -->
            <button class="guest-order-btn" onclick="openGuestOrderModal()">
                <i class="fas fa-shopping-bag"></i> Order as Guest
            </button>

            <div class="role-cards">
                <div class="role-card">
                    <i class="fas fa-user-tie"></i>
                    <h3>Admin</h3>
                    <p>Full system access and management</p>
                    <a href="login.php?role=admin">Login</a>
                </div>

                <div class="role-card">
                    <i class="fas fa-user-nurse"></i>
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

                <div class="role-card customer-card" style="background: linear-gradient(135deg, rgba(102,126,234,0.15), rgba(118,75,162,0.15)); border: 2px solid rgba(102,126,234,0.3);">
                    <i class="fas fa-star" style="color: #667eea;"></i>
                    <h3 style="color: #667eea;">Pro Customer</h3>
                    <p>5% discount on all products • ৳100 registration</p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="customer/register_pro.php" style="background: linear-gradient(135deg, #667eea, #764ba2);">Register</a>
                        <a href="customer/login.php" style="background: rgba(102,126,234,0.3); color: #667eea;">Login</a>
                    </div>
                </div>

                <div class="role-card vip-card" style="background: linear-gradient(135deg, rgba(255,215,0,0.15), rgba(255,140,0,0.15)); border: 2px solid rgba(255,215,0,0.3);">
                    <i class="fas fa-crown" style="color: #ffd700;"></i>
                    <h3 style="color: #ffd700;">VIP Customer</h3>
                    <p>10% discount on all products • ৳500 registration</p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="customer/register_vip.php" style="background: linear-gradient(135deg, #ffd700, #ff8c00); color: #000;">Register</a>
                        <a href="customer/login.php" style="background: rgba(255,215,0,0.3); color: #ffd700;">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        const menuOverlay = document.getElementById('menuOverlay');

        // Toggle menu
        if (menuBtn && dropdownMenu && menuOverlay) {
            menuBtn.addEventListener('click', function() {
                dropdownMenu.classList.toggle('active');
                menuOverlay.classList.toggle('active');
            });

            // Close menu on overlay click
            menuOverlay.addEventListener('click', function() {
                dropdownMenu.classList.remove('active');
                menuOverlay.classList.remove('active');
            });
        }

        // Close menu when selecting a role
        function selectRole(role) {
            if (dropdownMenu) dropdownMenu.classList.remove('active');
            if (menuOverlay) menuOverlay.classList.remove('active');
            window.location.href = 'login.php?role=' + role;
        }

        // Floating notice drag and close (guarded: notice may be removed)
        const floatingNotice = document.getElementById('floatingNotice');

        function dismissNotice() {
            if (floatingNotice) floatingNotice.style.display = 'none';
        }

        if (floatingNotice) {
            const dragHandle = floatingNotice.querySelector('.drag-handle');
            let isDragging = false;
            let offsetX = 0;
            let offsetY = 0;

            // Position notice at bottom-right initially
            function placeNoticeBottomRight() {
                const noticeRect = floatingNotice.getBoundingClientRect();
                floatingNotice.style.left = `${window.innerWidth - noticeRect.width - 30}px`;
                floatingNotice.style.top = `${window.innerHeight - noticeRect.height - 30}px`;
            }

            placeNoticeBottomRight();

            if (dragHandle) {
                dragHandle.addEventListener('pointerdown', (e) => {
                    isDragging = true;
                    offsetX = e.clientX - floatingNotice.getBoundingClientRect().left;
                    offsetY = e.clientY - floatingNotice.getBoundingClientRect().top;
                    floatingNotice.setPointerCapture(e.pointerId);
                });

                dragHandle.addEventListener('pointermove', (e) => {
                    if (!isDragging) return;
                    const newLeft = e.clientX - offsetX;
                    const newTop = e.clientY - offsetY;
                    const maxLeft = window.innerWidth - floatingNotice.offsetWidth;
                    const maxTop = window.innerHeight - floatingNotice.offsetHeight;
                    floatingNotice.style.left = `${Math.min(Math.max(0, newLeft), maxLeft)}px`;
                    floatingNotice.style.top = `${Math.min(Math.max(0, newTop), maxTop)}px`;
                });

                dragHandle.addEventListener('pointerup', (e) => {
                    isDragging = false;
                    floatingNotice.releasePointerCapture(e.pointerId);
                });

                dragHandle.addEventListener('pointerleave', (e) => {
                    if (!isDragging) return;
                    isDragging = false;
                    floatingNotice.releasePointerCapture(e.pointerId);
                });
            }

            // Reposition on resize to keep within viewport
            window.addEventListener('resize', () => {
                const rect = floatingNotice.getBoundingClientRect();
                const maxLeft = window.innerWidth - rect.width;
                const maxTop = window.innerHeight - rect.height;
                floatingNotice.style.left = `${Math.min(parseInt(rect.left, 10), maxLeft)}px`;
                floatingNotice.style.top = `${Math.min(parseInt(rect.top, 10), maxTop)}px`;
            });
        }

        // About modal functions
        function openAbout() {
            document.getElementById('aboutModal').classList.add('active');
            if (dropdownMenu) dropdownMenu.classList.remove('active');
            if (menuOverlay) menuOverlay.classList.remove('active');
        }

        function closeAbout() {
            document.getElementById('aboutModal').classList.remove('active');
        }

        // Close about modal on outside click
        document.getElementById('aboutModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeAbout();
            }
        });

        // Close menu on outside click
        document.addEventListener('click', function(event) {
            if (!event.target.closest('header')) {
                if (dropdownMenu) dropdownMenu.classList.remove('active');
                if (menuOverlay) menuOverlay.classList.remove('active');
            }
        });

        // Product search logic
        const productSearchForm = document.getElementById('productSearchForm');
        const productSearchInput = document.getElementById('productSearchInput');
        const productSearchStatus = document.getElementById('productSearchStatus');
        const productSearchResults = document.getElementById('productSearchResults');

        if (!productSearchForm || !productSearchInput || !productSearchStatus || !productSearchResults) {
            console.warn('Search UI elements missing; search disabled.');
        }

        function setSearchStatus(text, cls = 'muted') {
            productSearchStatus.textContent = text;
            productSearchStatus.className = `search-status ${cls}`.trim();
        }

        function notify(text, type = 'info') {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const iconClass = type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info';
            toast.innerHTML = `<i class="fa-solid ${iconClass}"></i><span>${text}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(() => toast.remove(), 200);
            }, 2200);
        }

        function renderResults(items) {
            if (!Array.isArray(items) || items.length === 0) {
                setSearchStatus('No products found.', 'empty');
                notify('No products found.', 'error');
                productSearchResults.innerHTML = '';
                return;
            }
            setSearchStatus(`${items.length} result${items.length > 1 ? 's' : ''} found`, 'success');
            notify(`${items.length} product${items.length > 1 ? 's' : ''} found.`, 'success');
            productSearchResults.innerHTML = items.map(item => {
                const stockText = item.stock > 0 ? `${item.stock} in stock` : 'Out of stock';
                const stockClass = item.stock > 0 ? 'in-stock' : 'out-of-stock';
                const priceText = item.price !== undefined ? `৳ ${Number(item.price).toLocaleString()}` : '';
                return `
                    <div class="result-row ${stockClass}">
                        <div class="name">${item.name}</div>
                        <div class="stock">${stockText}</div>
                        <div class="price">${priceText}</div>
                    </div>
                `;
            }).join('');
        }

        if (productSearchForm) productSearchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!productSearchInput || !productSearchStatus || !productSearchResults) return;
            const query = productSearchInput.value.trim();
            if (!query) {
                setSearchStatus('Enter a product name to search.', 'empty');
                notify('Please type a product name.', 'info');
                productSearchResults.innerHTML = '';
                return;
            }
            setSearchStatus('Searching...', 'muted');
            productSearchResults.innerHTML = '';
            try {
                const response = await fetch('home_product_search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(query)}`
                });
                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();
                renderResults(data);
            } catch (error) {
                setSearchStatus('Search failed. Please try again.', 'empty');
                notify('Search failed. Please try again.', 'error');
                productSearchResults.innerHTML = '';
            }
        });

        // ============= GUEST ORDER FUNCTIONALITY =============
        let guestSessionId = null;
        let guestProducts = [];
        let guestCart = {};

        function openGuestOrderModal() {
            document.getElementById('guestOrderModal').classList.add('active');
            resetGuestOrder();
        }

        function closeGuestOrderModal() {
            document.getElementById('guestOrderModal').classList.remove('active');
        }

        function resetGuestOrder() {
            guestSessionId = null;
            guestCart = {};
            document.getElementById('guestStep1').classList.add('active');
            document.getElementById('guestStep2').classList.remove('active');
            document.getElementById('guestStep3').classList.remove('active');
            document.getElementById('guestName').value = '';
            document.getElementById('guestPhone').value = '';
            document.getElementById('guestOTP').value = '';
        }

        function showGuestStep(stepNum) {
            document.querySelectorAll('.guest-step').forEach(s => s.classList.remove('active'));
            document.getElementById('guestStep' + stepNum).classList.add('active');
        }

        async function sendGuestOTP() {
            const name = document.getElementById('guestName').value.trim();
            const phone = document.getElementById('guestPhone').value.trim();

            if (!name) {
                notify('Please enter your name', 'error');
                return;
            }
            if (!/^\d{11}$/.test(phone)) {
                notify('Phone must be exactly 11 digits', 'error');
                return;
            }

            try {
                const res = await fetch('guest_order_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=sendOTP&name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}`
                });
                const data = await res.json();
                
                if (data.success) {
                    guestSessionId = data.session_id;
                    document.getElementById('displayOTP').textContent = data.otp; // Demo only
                    showGuestStep(2);
                    notify('OTP sent successfully!', 'success');
                } else {
                    notify(data.message || 'Failed to send OTP', 'error');
                }
            } catch (err) {
                notify('Error sending OTP', 'error');
            }
        }

        async function verifyGuestOTP() {
            const otp = document.getElementById('guestOTP').value.trim();

            if (!/^\d{4}$/.test(otp)) {
                notify('OTP must be 4 digits', 'error');
                return;
            }

            try {
                const res = await fetch('guest_order_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verifyOTP&session_id=${guestSessionId}&otp=${otp}`
                });
                const data = await res.json();
                
                if (data.success) {
                    notify('OTP verified!', 'success');
                    loadGuestProducts();
                    showGuestStep(3);
                } else {
                    notify(data.message || 'Invalid OTP', 'error');
                }
            } catch (err) {
                notify('Error verifying OTP', 'error');
            }
        }

        async function loadGuestProducts() {
            try {
                const res = await fetch('guest_order_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=getProducts'
                });
                const data = await res.json();
                
                if (data.success) {
                    guestProducts = data.products;
                    renderGuestProducts();
                }
            } catch (err) {
                notify('Error loading products', 'error');
            }
        }

        function renderGuestProducts() {
            const grid = document.getElementById('guestProductGrid');
            grid.innerHTML = guestProducts.map(p => `
                <div class="guest-product-card" id="guestProduct${p.id}">
                    <div class="guest-product-name">${p.name}</div>
                    <div class="guest-product-price">৳${Number(p.price).toLocaleString()}</div>
                    <div class="guest-product-stock">${p.stock} in stock</div>
                    <input type="number" class="guest-qty-input" id="guestQty${p.id}" 
                           min="0" max="${p.stock}" value="0" 
                           data-id="${p.id}" data-price="${p.price}" data-stock="${p.stock}"
                           onchange="updateGuestCart(${p.id})" onkeyup="updateGuestCart(${p.id})">
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 5px;">Min 10 stocks</div>
                </div>
            `).join('');
        }

        function updateGuestCart(productId) {
            const input = document.getElementById('guestQty' + productId);
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price);
            const card = document.getElementById('guestProduct' + productId);

            if (qty > 0 && qty < 10) {
                input.style.borderColor = '#ff4444';
            } else {
                input.style.borderColor = 'rgba(255,255,255,0.2)';
            }

            if (qty >= 10) {
                guestCart[productId] = { qty, price };
                card.classList.add('selected');
            } else if (qty === 0) {
                delete guestCart[productId];
                card.classList.remove('selected');
            }

            calculateGuestTotal();
        }

        function calculateGuestTotal() {
            let totalStocks = 0;
            let subtotal = 0;

            for (const id in guestCart) {
                totalStocks += guestCart[id].qty;
                subtotal += guestCart[id].qty * guestCart[id].price;
            }

            // Guest discount: ৳1000 for every 100 stocks
            const discountMultiplier = Math.floor(totalStocks / 100);
            const discount = discountMultiplier * 1000;
            const total = subtotal - discount;

            document.getElementById('guestTotalStocks').textContent = totalStocks;
            document.getElementById('guestSubtotal').textContent = '৳' + subtotal.toLocaleString();
            document.getElementById('guestDiscount').textContent = '-৳' + discount.toLocaleString();
            document.getElementById('guestTotal').textContent = '৳' + total.toLocaleString();

            const discountRow = document.getElementById('guestDiscountRow');
            if (discount > 0) {
                discountRow.style.display = 'flex';
            } else {
                discountRow.style.display = 'none';
            }

            const summaryDiv = document.getElementById('guestCartSummary');
            const checkoutBtn = document.getElementById('guestCheckoutBtn');
            
            if (Object.keys(guestCart).length > 0) {
                summaryDiv.style.display = 'block';
            } else {
                summaryDiv.style.display = 'none';
            }

            // Validate: minimum 100 stocks total, minimum 10 per product
            let valid = totalStocks >= 100;
            for (const id in guestCart) {
                if (guestCart[id].qty < 10) {
                    valid = false;
                    break;
                }
            }

            checkoutBtn.disabled = !valid;
            
            if (totalStocks < 100 && Object.keys(guestCart).length > 0) {
                checkoutBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Need ' + (100 - totalStocks) + ' more stocks';
            } else {
                checkoutBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay with bKash/SSLCommerz';
            }
        }

        async function guestCheckout() {
            const checkoutBtn = document.getElementById('guestCheckoutBtn');
            const originalText = checkoutBtn.innerHTML;
            
            // Show loading state
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connecting to Payment Gateway...';
            
            // Build cart items array
            const items = Object.entries(guestCart).map(([id, data]) => ({
                product_id: id,
                quantity: data.qty
            }));

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 second timeout
                
                const res = await fetch('guest_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        session_id: guestSessionId,
                        items: items
                    }),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                const data = await res.json();
                
                if (data.success && data.redirect_url) {
                    checkoutBtn.innerHTML = '<i class="fas fa-check me-2"></i>Redirecting to SSLCommerz...';
                    window.location.href = data.redirect_url;
                } else {
                    notify(data.message || 'Checkout failed', 'error');
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = originalText;
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    notify('Payment gateway is not responding. Please try again later.', 'error');
                } else {
                    notify('Error processing checkout: ' + (err.message || 'Network error'), 'error');
                }
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = originalText;
            }
        }

        // Close modal on outside click
        document.getElementById('guestOrderModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeGuestOrderModal();
            }
        });
    </script>
</body>
</html>
