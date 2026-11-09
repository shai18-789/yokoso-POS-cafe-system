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
    <title>Sales Report | Admin</title>
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
            <a href="sales_report.php" class="nav-link active"><i class="fa fa-chart-line"></i> Sales Report</a>
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

    <div class="main-content" style="padding: 30px;">
        
        <style>
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .kpi-card {
                background: #ffffff;
                padding: 20px;
                border-radius: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
                border: 1px solid #edf2f7;
            }
            .kpi-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            }
            .kpi-card.revenue { border-left: 5px solid #2ecc71; } 
            .kpi-card.transactions { border-left: 5px solid #3498db; }

            .kpi-content h4 {
                margin: 0; color: #718096; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
            }
            .kpi-value {
                display: block; font-size: 28px; font-weight: 800; margin-top: 5px; color: #2d3748;
            }
            .kpi-icon {
                font-size: 32px; opacity: 0.2;
            }

            .filter-section {
                background: white; padding: 20px; border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;
                display: flex; align-items: center; gap: 15px; flex-wrap: wrap;
            }
            .filter-group { display: flex; align-items: center; gap: 8px; }
            .filter-input {
                padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; outline: none;
            }
            .btn-action {
                padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s;
            }
            .btn-filter { background: #4b3832; color: white; }
            .btn-reset { background: #e2e8f0; color: #4a5568; }
            .btn-action:hover { opacity: 0.9; }

            .report-table {
                width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            .report-table th {
                background: #4b3832; color: white; padding: 15px; text-align: left; font-size: 14px;
            }
            .report-table td {
                padding: 15px; border-bottom: 1px solid #edf2f7; font-size: 14px;
            }
        </style>

        <h1 style="margin: 0 0 5px 0; color: #333;">Sales Report</h1>
        <p style="color: #777; margin-bottom: 25px;">Track your daily and monthly revenue</p>

        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <div class="kpi-content">
                    <h4>Total Sales Revenue</h4>
                    <span class="kpi-value" id="totalSales" style="color: #2ecc71;">₱0.00</span>
                </div>
                <div class="kpi-icon" style="color: #2ecc71;"><i class="fa fa-money-bill-wave"></i></div>
            </div>

            <div class="kpi-card transactions">
                <div class="kpi-content">
                    <h4>Total Transactions</h4>
                    <span class="kpi-value" id="totalTransactions" style="color: #3498db;">0</span>
                </div>
                <div class="kpi-icon" style="color: #3498db;"><i class="fa fa-receipt"></i></div>
            </div>
        </div>

        
        <div class="filter-section">
            <div class="filter-group">
                <label style="font-weight: 600; color: #1565c0;"><i class="fa fa-clock"></i> Quick Range:</label>
                <select id="quickFilter" onchange="applyQuickFilter()" class="filter-input" style="cursor: pointer; background: #f8f9fa; font-weight: bold;">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="custom">Custom Date</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight: 600;">From:</label>
                <input type="date" id="startDate" class="filter-input">
            </div>
            <div class="filter-group">
                <label style="font-weight: 600;">To:</label>
                <input type="date" id="endDate" class="filter-input">
            </div>
            <button class="btn-action btn-filter" onclick="loadSales()"><i class="fa fa-search"></i> Filter</button>
            <button class="btn-action btn-reset" onclick="resetFilter()">Reset</button>
            
            <div style="position: relative; margin-left: auto; min-width: 300px;">
                <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #aaa;"></i>
                <input type="text" id="searchReceipt" oninput="renderSalesTable()" placeholder="Search Receipt No..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ddd; border-radius: 6px; outline: none;">
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Date & Time</th>
                    <th>Total Amount</th>
                    <th>Cash Tendered</th>
                    <th>Change</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody id="salesTableBody">
                <tr><td colspan="6" style="text-align:center; padding: 30px; color: #888;">Loading data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="receiptModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center;">
    <div class="receipt-card" style="background: white; width: 320px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); font-family: 'Courier New', Courier, monospace; color: #333; position: relative;">
        <button onclick="closeReceipt()" style="position: absolute; right: 15px; top: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #888;">&times;</button>
        
        <div style="text-align: center; border-bottom: 1px dashed #333; padding-bottom: 15px; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 22px; font-weight: bold;">YOKOSO CAFE</h2>
            <p style="margin: 10px 0 0 0; font-size: 12px;">Receipt #: <span id="recNo"></span></p>
            <p style="margin: 0; font-size: 12px;">Date: <span id="recDate"></span></p>
            <p style="margin: 5px 0 0 0; font-size: 13px; font-weight: bold;" id="recStatus"></p>
        </div>
        
        <div id="recItems" style="min-height: 100px; margin-bottom: 15px;"></div>
        
        <div style="border-top: 1px dashed #333; padding-top: 15px;">
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px;">
                <span>TOTAL:</span> <span id="recTotal">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px;">
                <span>Tendered:</span> <span id="recPayment">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px; color: #d35400; font-weight: bold;">
                <span>CHANGE:</span> <span id="recChange">₱0.00</span>
            </div>
        </div>
    </div>
</div>

<script>
// GLOBAL VARIABLES
window.allSalesData = window.allSalesData || []; 

// INITIALIZATION
(function initSalesPage() {
    const today = new Date().toISOString().split('T')[0];
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');

    if(startInput && endInput) {
        startInput.value = today;
        endInput.value = today;
    }
    
    if (typeof window.loadSales === 'function') {
        window.loadSales();
    }
})();

// SPA-PROOF FUNCTIONS (Gibutangan og window. aron makit-an sa HTML buttons)
window.loadSales = function() {
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    const tbody = document.getElementById('salesTableBody');

    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;">Fetching data...</td></tr>';

    let url = '../../api/admin/get_sales.php'; 
    if(startDate && endDate) {
        url += `?start=${startDate}&end=${endDate}`;
    }

    fetch(url)
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        window.allSalesData = Array.isArray(data) ? data : []; 
        window.renderSalesTable(); 
    })
    .catch(error => {
        console.error('Error fetching sales:', error);
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding: 20px;">Error loading sales data.</td></tr>`;
    });
}

window.renderSalesTable = function() {
    const tbody = document.getElementById('salesTableBody');
    if (!tbody) return;

    const searchInput = document.getElementById('searchReceipt');
    const searchStr = searchInput ? searchInput.value.toLowerCase() : '';
    
    tbody.innerHTML = '';
    let runningTotalSales = 0;
    let runningTotalTrans = 0;

    const filteredData = window.allSalesData.filter(sale => {
        const receipt = sale.receipt_no ? sale.receipt_no.toLowerCase() : '';
        return receipt.includes(searchStr);
    });

    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 30px; color: #888;">Walay records nga nakit-an.</td></tr>';
    } else {
        filteredData.forEach(sale => {
            const amount = parseFloat(sale.total_amount) || 0;
            const isVoided = sale.status === 'Voided';
            
            if (!isVoided) {
                runningTotalSales += amount;
                runningTotalTrans++;
            }

            const tr = document.createElement('tr');
            
            const badge = isVoided 
                ? '<br><span style="background: #ffebee; color: #c62828; padding: 2px 6px; border-radius: 4px; font-size: 10px;">VOIDED</span>' 
                : '';
            const priceStyle = isVoided 
                ? 'text-decoration: line-through; color: #e53e3e; opacity: 0.7;' 
                : 'color: #2ecc71; font-weight:bold;';
            const rowOpacity = isVoided ? 'opacity: 0.6;' : '';

            tr.style = rowOpacity;
            // 📍 Gidugang ang window.viewReceipt sa onclick!
            tr.innerHTML = `
                <td style="font-weight:bold; color: #4a5568;">#${sale.receipt_no} ${badge}</td>
                <td>${sale.sale_date}</td>
                <td style="${priceStyle}">₱${amount.toFixed(2)}</td>
                <td>₱${parseFloat(sale.cash_tendered || 0).toFixed(2)}</td>
                <td style="color: #718096;">₱${parseFloat(sale.change_amount || 0).toFixed(2)}</td>
                <td style="text-align: center;">
                    <button onclick="window.viewReceipt('${sale.receipt_no}')" style="background: white; border: 1px solid #ddd; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: #4E372C; font-weight: bold; transition: 0.2s;" onmouseover="this.style.background='#f4ece6'" onmouseout="this.style.background='white'">
                        <i class="fa fa-eye"></i> View
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const totalSalesElem = document.getElementById('totalSales');
    const totalTransElem = document.getElementById('totalTransactions');

    if(totalSalesElem) {
        totalSalesElem.innerText = '₱' + runningTotalSales.toLocaleString('en-PH', {minimumFractionDigits: 2});
    }
    if(totalTransElem) {
        totalTransElem.innerText = runningTotalTrans;
    }
}

window.resetFilter = function() {
    const today = new Date().toISOString().split('T')[0];
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    const searchInput = document.getElementById('searchReceipt');
    const quickFilter = document.getElementById('quickFilter');

    if (startInput) startInput.value = today;
    if (endInput) endInput.value = today;
    if (searchInput) searchInput.value = '';
    if (quickFilter) quickFilter.value = 'today'; 
    
    window.loadSales();
}

window.applyQuickFilter = function() {
    const filter = document.getElementById('quickFilter').value;
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    
    if (!startInput || !endInput || filter === 'custom') return;

    const today = new Date();
    let start = new Date();
    let end = new Date();

    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (filter === 'today') {
    } 
    else if (filter === 'yesterday') {
        start.setDate(today.getDate() - 1);
        end.setDate(today.getDate() - 1);
    } 
    else if (filter === 'this_week') {
        const firstDay = today.getDate() - today.getDay(); 
        start.setDate(firstDay);
    } 
    else if (filter === 'this_month') {
        start.setDate(1); 
    }

    startInput.value = formatDate(start);
    endInput.value = formatDate(end);
    
    window.loadSales();
}

// 📍 Gihimong window.viewReceipt aron dili matago sa SPA
window.viewReceipt = async function(receiptNo) {
    try {
        const cacheBuster = new Date().getTime();
        const response = await fetch(`../../api/cashier/get_receipt.php?receipt_no=${receiptNo}&_t=${cacheBuster}`);
        const result = await response.json();

        if (result.success) {
            const sale = result.sale;
            const items = result.items || [];

            const safeSetText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };

            safeSetText('recNo', sale.receipt_no || receiptNo);
            safeSetText('recDate', sale.sale_date || 'Unknown Date');

            const currentStatus = sale.status ? sale.status : 'COMPLETED';
            const statusElem = document.getElementById('recStatus');
            
            if (statusElem) {
                statusElem.innerText = "STATUS: " + currentStatus.toUpperCase();
                statusElem.style.color = currentStatus === 'Voided' ? '#c62828' : '#2ecc71';
                
                const oldReason = document.getElementById('voidReasonDisplay');
                if (oldReason) oldReason.remove();
                
                if (currentStatus === 'Voided' && result.void_reason) {
                    statusElem.insertAdjacentHTML('afterend', `<p id="voidReasonDisplay" style="margin: 5px 0 0 0; font-size: 11px; color: #c62828; font-style: italic;">Reason: ${result.void_reason}</p>`);
                }
            }

            let itemsHtml = '';
            if (items.length > 0) {
                items.forEach(item => {
                    const qty = parseInt(item.quantity || 1);
                    const price = parseFloat(item.price || 0);
                    itemsHtml += `
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                            <span>${qty}x ${item.product_name || 'Item'}</span>
                            <span>₱${(qty * price).toFixed(2)}</span>
                        </div>
                    `;
                });
            } else {
                itemsHtml = '<div style="text-align: center; color: #888; font-size: 12px;">Walay items nga nakita.</div>';
            }
            const recItems = document.getElementById('recItems');
            if (recItems) recItems.innerHTML = itemsHtml;

            const total = parseFloat(sale.total_amount || 0);
            const cash = parseFloat(sale.cash_tendered || 0);
            const change = parseFloat(sale.change_amount || 0);

            safeSetText('recTotal', `₱${total.toFixed(2)}`);
            safeSetText('recPayment', `₱${cash.toFixed(2)}`);
            safeSetText('recChange', `₱${change.toFixed(2)}`);

            const modal = document.getElementById('receiptModal');
            if (modal) {
                modal.style.display = 'flex';
            } else {
                alert("Error: Wala nakit-i ang HTML sa Modal.");
            }

        } else {
            alert("API Error: " + result.message);
        }
    } catch (err) {
        console.error("View Receipt Error:", err);
        alert("Naay error. Pislita ang F12 para makita sa console.");
    }
}

window.closeReceipt = function() {
    const modal = document.getElementById('receiptModal');
    if (modal) modal.style.display = 'none';
}
</script>

<script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>