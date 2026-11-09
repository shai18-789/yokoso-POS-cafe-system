<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}

// 1. KONEKTA SA DATABASE
require_once '../../config/database.php';

// 2. KUHAA ANG "TODAY'S SALES"
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
$todaySales = $stmt->fetch()['total'] ?? 0;

// 3. KUHAA ANG "TOTAL ORDERS" KARONG BULANA
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
$totalOrders = $stmt->fetch()['count'] ?? 0;

// 4. KUHAA ANG "LOW STOCK ITEMS"
$stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE stock_quantity <= low_stock_threshold");
$lowStockCount = $stmt->fetch()['count'] ?? 0;

// 5. KUHAA ANG SALES SA MIAGING 7 KA ADLAW (PARA SA CHART)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days")); // e.g., Mon, Tue
    $chart_labels[] = $label;

    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = ?");
    $stmt->execute([$date]);
    $daily_total = $stmt->fetch()['total'] ?? 0;
    $chart_data[] = (float)$daily_total;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Admin Reports</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="app-container">
    <div class="sidebar">
        <div class="brand">
            <img src="../../assets/img/logo.png" class="sidebar-logo" onerror="this.src='https://via.placeholder.com/45'">
            <h3>Yokoso Cafe <span class="version">Admin</span></h3>
        </div>
        <nav>
            <a href="index.php" class="nav-link active"><i class="fa fa-home"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fa fa-users"></i> Manage Users</a>
            <a href="menu.php" class="nav-link"><i class="fa fa-coffee"></i> Menu</a>
            <a href="category.php" class="nav-link"><i class="fa fa-tags"></i> Categories</a>
            <a href="inventory.php" class="nav-link"><i class="fa fa-boxes"></i> Inventory</a>
            <a href="sales_report.php" class="nav-link"><i class="fa fa-chart-line"></i> Sales Report</a>
            <a href="system_logs.php" class="nav-link"><i class="fa fa-history"></i> System Logs</a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="time-box">
                <div class="time-display" id="clock">00:00</div>
                <div class="date-display"><?php echo date('M d, Y'); ?></div>
            </div>
            <a href="../../logout.php" class="logout-btn"><i class="fa fa-sign-out"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="subtitle">Real-time overview of your cafe performance</p>
            </div>       
        </div>

        <div class="kpi-grid">
            <div class="kpi-card green" style="background: #e8f5e9; border-left: 5px solid #2e7d32;">
                <div class="kpi-content">
                    <h4 style="color: #2e7d32;">TODAY'S SALES</h4>
                    <span class="kpi-value" id="todaySales" style="color: #2e7d32;">₱<?php echo number_format($todaySales, 2); ?></span>
                    <small style="color: #666;">Updated just now</small>
                </div>
                <div class="kpi-icon" style="color: #2e7d32;"><i class="fa fa-money-bill-wave"></i></div>
            </div>
            
            <div class="kpi-card blue" style="background: #e3f2fd; border-left: 5px solid #1565c0;">
                <div class="kpi-content">
                    <h4 style="color: #1565c0;">TOTAL ORDERS</h4>
                    <span class="kpi-value" id="totalOrders" style="color: #1565c0;"><?php echo $totalOrders; ?></span>
                    <small style="color: #666;">For the current month</small>
                </div>
                <div class="kpi-icon" style="color: #1565c0;"><i class="fa fa-shopping-cart"></i></div>
            </div>
            
            <div class="kpi-card red" style="background: #ffebee; border-left: 5px solid #c62828;">
                <div class="kpi-content">
                    <h4 style="color: #c62828;">LOW STOCK ITEMS</h4>
                    <span class="kpi-value" id="lowStockCount" style="color: #c62828;"><?php echo $lowStockCount; ?></span>
                    <small style="color: #666;">Needs attention!</small>
                </div>
                <div class="kpi-icon" style="color: #c62828;"><i class="fa fa-exclamation-triangle"></i></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <div class="recent-activity" style="margin-bottom: 0;">
                <div class="section-header" style="margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #333;">Sales Trend (Last 7 Days)</h3>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="recent-activity" style="margin-bottom: 0;">
                <div class="section-header" style="margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #333;">System Audit Trail</h3>
                    <a href="system_logs.php" style="color: #4E372C; font-weight: 600;">View All Logs</a>
                </div>
                <div style="height: 300px; overflow-y: auto; padding-right: 5px;">
                    <table id="logsTable" style="margin-top: 0; width: 100%; text-align: left; border-collapse: collapse;">
                        <thead style="background: #4b3832; color: white;">
                            <tr>
                                <th style="padding: 10px;">Date & Time</th>
                                <th style="padding: 10px;">User</th>
                                <th style="padding: 10px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardLogList">
                            <tr><td colspan="3" style="text-align: center; padding: 20px; color: #888;">Loading logs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        </div>
</div>

<script>
// Himuon natong global function aron mo-dagan bisag e-load via Sidebar (SPA Router)
window.loadDashboardContent = function() {
    // 1. I-LOAD ANG CHART (Safe Reload)
    const canvas = document.getElementById('salesChart');
    if (canvas) {
        // I-guba ang daan nga chart aron dili mo-error og "Canvas is already in use"
        if (window.dashboardChart) {
            window.dashboardChart.destroy();
        }
        const ctx = canvas.getContext('2d');
        window.dashboardChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>, 
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?php echo json_encode($chart_data); ?>, 
                    borderColor: '#2e7d32', 
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // 2. I-LOAD ANG LOGS
    fetch('../../api/admin/get_logs.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('dashboardLogList');
        if (!tbody) return;

        if (data.error || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #888;">No recent logs found.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        
        // Kuhaon lang ang unang 7 ka logs
        const recentLogs = data.slice(0, 7); 

        recentLogs.forEach(log => {
            let actionColor = '#f0f0f0';
            if(log.action.includes('Login')) actionColor = '#e3f2fd; color: #0d47a1;';
            else if(log.action.includes('Delete') || log.action.includes('Out') || log.action.includes('Void')) actionColor = '#ffebee; color: #c62828;';
            else if(log.action.includes('Update') || log.action.includes('Restock') || log.action.includes('Save')) actionColor = '#e8f5e9; color: #2e7d32;';

            tbody.innerHTML += `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px; font-size: 12px; color: #666; white-space: nowrap;">${log.created_at}</td>
                    <td style="padding: 10px; font-weight: bold; font-size: 13px; color: #333;">${log.fullname}</td>
                    <td style="padding: 10px;">
                        <span style="background: ${actionColor}; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                            ${log.action}
                        </span>
                    </td>
                </tr>
            `;
        });
    })
    .catch(err => {
        console.error("Dashboard Logs Error:", err);
    });
};

// Tawgon ang function dayon inig load!
setTimeout(() => {
    if (typeof window.loadDashboardContent === 'function') {
        window.loadDashboardContent();
    }
}, 150); // Naay gamayng delay aron maka-ginhawa ang browser
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>

</body>
</html>