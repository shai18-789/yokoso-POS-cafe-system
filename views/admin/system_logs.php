<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs | Cafe Admin</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <div class="sidebar">
        <div class="brand">
            <img src="../../assets/img/logo.png" class="sidebar-logo" onerror="this.src='https://via.placeholder.com/45'">
            <h3>Yokoso Cafe <span class="version">Admin</span></h3>
        </div>
        
        <nav>
            <a href="index.php" class="nav-link"><i class="fa fa-home"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fa fa-users"></i> Manage Users</a>
            <a href="menu.php" class="nav-link"><i class="fa fa-coffee"></i> Menu</a>
            <a href="category.php" class="nav-link"><i class="fa fa-tags"></i> Categories</a>
            <a href="inventory.php" class="nav-link"><i class="fa fa-boxes"></i> Inventory</a>
            <a href="sales_report.php" class="nav-link"><i class="fa fa-chart-line"></i> Sales Report</a>
            <a href="system_logs.php" class="nav-link active"><i class="fa fa-history"></i> System Logs</a>
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
                <h1>System Logs</h1>
                <p class="subtitle">View all system activities and user actions here.</p>
            </div>
<button id="refreshBtn" class="btn-primary" onclick="fetchLogs()" style="transition: 0.3s;"><i class="fa fa-sync-alt"></i> Refresh Logs</button>        </div>

        <div class="table-controls" style="margin-bottom: 20px;">
            <div style="position: relative; width: 400px;">
                <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #aaa;"></i>
                <input type="text" id="searchLogsInput" oninput="renderLogs()" placeholder="Search by date, name, action, or details..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none;">
            </div>
        </div>
            <table id="logsTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #888;">
                            <i class="fa fa-spinner fa-spin"></i> Loading logs...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../../assets/js/admin.js"></script>

<script>
    // Magbuhat tag array aron sudlan sa data
    let allLogsData = [];

    // I-tawag diritso ang function para mo-load ang logs
    fetchLogs();

    function fetchLogs() {
        const btn = document.getElementById('refreshBtn');
        
        // 1. Inig click, usbon ang hitsura sa button aron kabalo ang user nga nag-load
        if (btn) {
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
            btn.style.opacity = '0.7';
            btn.disabled = true; // I-disable kadiyot aron dili ma-spam og click
        }

        fetch('../../api/admin/get_logs.php')
            .then(response => response.json())
            .then(data => {
                allLogsData = data;
                renderLogs(); 
                
                // 2. Inig human og load, i-ilis ang text to "Updated!"
                if (btn) {
                    // Butangan natog gamayng delay (500ms) para makita gyud nga nag-tuyok
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fa fa-check"></i> Updated!';
                        btn.style.background = '#2ecc71'; // Himoon natong Green kadiyot!
                        btn.style.opacity = '1';
                        
                        // 3. I-balik sa normal inig human og 1.5 seconds
                        setTimeout(() => {
                            btn.innerHTML = '<i class="fa fa-sync-alt"></i> Refresh Logs';
                            btn.style.background = ''; // Balik sa original nga color
                            btn.disabled = false;
                        }, 1500);
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('logsTableBody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Error loading logs.</td></tr>';
                
                // Kung mag-error, ibalik gihapon sa normal ang button
                if (btn) {
                    btn.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error!';
                    btn.style.background = '#e74c3c'; // Pula
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fa fa-sync-alt"></i> Refresh Logs';
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2000);
                }
            });
    }

    // KINI ANG BAG-ONG FUNCTION PARA MO-FILTER UG DRAW SA TABLE
    function renderLogs() {
        const tbody = document.getElementById('logsTableBody');
        const searchTerm = document.getElementById('searchLogsInput').value.toLowerCase();
        
        tbody.innerHTML = ''; 

        if (allLogsData.length === 0 || allLogsData.error) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No system logs found.</td></tr>';
            return;
        }

        // I-filter ang data base sa gi-type sa Search Bar
        // I-filter ang data base sa gi-type sa Search Bar
        const filteredLogs = allLogsData.filter(log => {
            const matchName = (log.fullname || '').toLowerCase().includes(searchTerm);
            const matchAction = (log.action || '').toLowerCase().includes(searchTerm);
            const matchDetails = (log.details || '').toLowerCase().includes(searchTerm);
            const matchDate = (log.created_at || '').toLowerCase().includes(searchTerm); // 📍 Gidugang nato ang Date!
            
            // 📍 Gi-apil ang matchDate diri sa return
            return matchName || matchAction || matchDetails || matchDate; 
        });

        if (filteredLogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: #888; font-weight: bold;">Walay nakit-an nga log record.</td></tr>';
            return;
        }

        // I-display ang na-filter nga data
        filteredLogs.forEach(log => {
            let actionClass = 'action-other';
            if(log.action.includes('Login')) actionClass = 'action-login';
            else if(log.action.includes('Void') || log.action.includes('Delete')) actionClass = 'action-void';
            else if(log.action.includes('Update') || log.action.includes('Edit')) actionClass = 'action-update';

            tbody.innerHTML += `
                <tr>
                    <td style="white-space: nowrap;">${log.created_at}</td>
                    <td style="font-weight: bold; color: #333;">${log.fullname}</td>
                    <td class="${actionClass}">${log.action}</td>
                    <td>${log.details}</td>
                </tr>
            `;
        });
    }
</script>

</body>
</html>