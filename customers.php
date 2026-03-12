<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';

// Fetch customer summary
$customers_query = "
    SELECT 
        c.id,
        c.name,
        c.phone,
        c.nid,
        c.customer_type,
        c.registration_fee,
        c.registration_date,
        c.registration_invoice
    FROM customers c
    ORDER BY c.created_at DESC
";

$customers = $conn->query($customers_query);

// Get totals
$total_pro = $conn->query("SELECT COUNT(*) as cnt FROM customers WHERE customer_type = 'pro'")->fetch_assoc()['cnt'] ?? 0;
$total_vip = $conn->query("SELECT COUNT(*) as cnt FROM customers WHERE customer_type = 'vip'")->fetch_assoc()['cnt'] ?? 0;
$total_revenue = $conn->query("SELECT SUM(registration_fee) as total FROM customers")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Overview - Admin Panel</title>
    
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
            max-width: 1200px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--text-secondary);
        }

        .stat-card.pro i, .stat-card.pro h3 { color: #667eea; }
        .stat-card.vip i, .stat-card.vip h3 { color: #ffd700; }
        .stat-card.revenue i, .stat-card.revenue h3 { color: var(--highlight-color); }
        .stat-card.total i, .stat-card.total h3 { color: #3498db; }

        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h2 {
            font-size: 1.2rem;
        }

        .search-input {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-primary);
            width: 250px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--highlight-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: rgba(0, 255, 136, 0.1);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            color: var(--highlight-color);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.03);
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

        .invoice-number {
            font-family: monospace;
            font-size: 0.9rem;
            color: var(--highlight-color);
        }

        .export-btn {
            padding: 10px 20px;
            background: var(--highlight-color);
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: #00cc66;
        }

        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-user-tie"></i> Customer Overview</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_pro + $total_vip; ?></h3>
                <p>Total Customers</p>
            </div>
            <div class="stat-card pro">
                <i class="fas fa-star"></i>
                <h3><?php echo $total_pro; ?></h3>
                <p>Pro Customers</p>
            </div>
            <div class="stat-card vip">
                <i class="fas fa-crown"></i>
                <h3><?php echo $total_vip; ?></h3>
                <p>VIP Customers</p>
            </div>
            <div class="stat-card revenue">
                <i class="fas fa-money-bill-wave"></i>
                <h3>৳<?php echo number_format($total_revenue, 0); ?></h3>
                <p>Registration Revenue</p>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Customer List</h2>
                <div>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search customers...">
                </div>
            </div>

            <table id="customerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>NID</th>
                        <th>Phone</th>
                        <th>Invoice</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    if ($customers && $customers->num_rows > 0):
                        while ($customer = $customers->fetch_assoc()): 
                            $count++;
                    ?>
                        <tr>
                            <td><?php echo $count; ?></td>
                            <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                            <td>
                                <?php if ($customer['customer_type'] === 'vip'): ?>
                                    <span class="badge badge-vip"><i class="fas fa-crown"></i> VIP</span>
                                <?php else: ?>
                                    <span class="badge badge-pro"><i class="fas fa-star"></i> Pro</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['nid']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td>
                                <?php if (!empty($customer['registration_invoice'])): ?>
                                    <span class="invoice-number"><?php echo htmlspecialchars($customer['registration_invoice']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($customer['registration_date'] ?? $customer['created_at'] ?? 'now')); ?></td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 15px; display: block;"></i>
                                <p>No customers registered yet</p>
                            </td>
                        </tr>
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
    </script>
</body>
</html>
