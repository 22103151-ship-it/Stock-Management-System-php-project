<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/notification_functions.php';

// Fetch all customers with their details
$customers_query = "
    SELECT 
        c.id,
        c.name,
        c.email,
        c.phone,
        c.extra_phone,
        c.nid,
        c.customer_type,
        c.registration_fee,
        c.registration_date,
        c.registration_invoice,
        c.profile_picture,
        c.created_at,
        u.username
    FROM customers c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
";

$customers = $conn->query($customers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List - Staff Panel</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #16213e;
            --accent-color: #0f3460;
            --highlight-color: #00ff88;
            --warning-color: #ffc107;
            --danger-color: #ff4757;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --card-bg: rgba(255, 255, 255, 0.05);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--text-primary);
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--highlight-color), #00cc66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: var(--highlight-color);
            color: #000;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-box h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-box.pro h3 { color: #667eea; }
        .stat-box.vip h3 { color: #ffd700; }
        .stat-box.total h3 { color: var(--highlight-color); }

        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: rgba(0, 255, 136, 0.1);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: var(--highlight-color);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--highlight-color);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pro {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        .badge-vip {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #000;
        }

        .contact-info {
            font-size: 0.9rem;
        }

        .contact-info .email {
            color: var(--highlight-color);
        }

        .contact-info .phone {
            color: var(--text-secondary);
        }

        .invoice-link {
            color: var(--highlight-color);
            text-decoration: none;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--highlight-color);
        }

        .filter-btn {
            padding: 12px 20px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--highlight-color);
            color: #000;
        }

        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Customer List</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php
        // Count stats
        $total_customers = 0;
        $pro_count = 0;
        $vip_count = 0;
        $customers_array = [];
        
        if ($customers && $customers->num_rows > 0) {
            while ($row = $customers->fetch_assoc()) {
                $customers_array[] = $row;
                $total_customers++;
                if ($row['customer_type'] === 'vip') {
                    $vip_count++;
                } else {
                    $pro_count++;
                }
            }
        }
        ?>

        <div class="stats-row">
            <div class="stat-box total">
                <h3><?php echo $total_customers; ?></h3>
                <p>Total Customers</p>
            </div>
            <div class="stat-box pro">
                <h3><?php echo $pro_count; ?></h3>
                <p>Pro Customers</p>
            </div>
            <div class="stat-box vip">
                <h3><?php echo $vip_count; ?></h3>
                <p>VIP Customers</p>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by name, phone, NID, or email...">
            <button class="filter-btn active" onclick="filterCustomers('all')">All</button>
            <button class="filter-btn" onclick="filterCustomers('pro')">Pro</button>
            <button class="filter-btn" onclick="filterCustomers('vip')">VIP</button>
        </div>

        <div class="table-container">
            <table id="customerTable">
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>NID</th>
                        <th>Registration</th>
                        <th>Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers_array)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 15px;"></i>
                                <p>No customers registered yet</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers_array as $customer): ?>
                            <tr data-type="<?php echo htmlspecialchars($customer['customer_type'] ?? 'pro'); ?>">
                                <td>
                                    <?php if (!empty($customer['profile_picture'])): ?>
                                        <img src="../<?php echo htmlspecialchars($customer['profile_picture']); ?>" class="customer-avatar" alt="Avatar">
                                    <?php else: ?>
                                        <div class="default-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                    <?php if (!empty($customer['username'])): ?>
                                        <br><small style="color: var(--text-secondary);">@<?php echo htmlspecialchars($customer['username']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($customer['customer_type'] === 'vip'): ?>
                                        <span class="badge badge-vip"><i class="fas fa-crown"></i> VIP</span>
                                    <?php else: ?>
                                        <span class="badge badge-pro"><i class="fas fa-star"></i> Pro</span>
                                    <?php endif; ?>
                                </td>
                                <td class="contact-info">
                                    <div class="email"><?php echo htmlspecialchars($customer['email']); ?></div>
                                    <div class="phone"><?php echo htmlspecialchars($customer['phone']); ?></div>
                                    <?php if (!empty($customer['extra_phone'])): ?>
                                        <div class="phone"><?php echo htmlspecialchars($customer['extra_phone']); ?> (Alt)</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($customer['nid']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($customer['registration_date'] ?? $customer['created_at'])); ?>
                                    <br><small style="color: var(--highlight-color);">৳<?php echo number_format($customer['registration_fee'] ?? 0, 2); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($customer['registration_invoice'])): ?>
                                        <span class="invoice-link"><?php echo htmlspecialchars($customer['registration_invoice']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#customerTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filter functionality
        function filterCustomers(type) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const rows = document.querySelectorAll('#customerTable tbody tr');
            rows.forEach(row => {
                const rowType = row.getAttribute('data-type');
                if (type === 'all' || rowType === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
