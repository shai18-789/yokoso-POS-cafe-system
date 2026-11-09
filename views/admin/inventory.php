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
    <title>Inventory Management | Admin</title>
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
            <a href="inventory.php" class="nav-link active" style="position: relative;">
                <i class="fa fa-boxes"></i> Inventory
                <span id="sidebarInventoryBadge" onclick="event.preventDefault(); openNotificationModal();" style="position: absolute; top: 8px; right: 15px; background: #e74c3c; color: white; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); cursor: pointer; display: none;">0</span>
            </a>            
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

    <div class="main-content" style="padding: 30px;">
        
        <style>
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
                cursor: pointer;
                border: 1px solid #edf2f7;
            }
            .kpi-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            }
            .kpi-card.good { border-left: 5px solid #2ecc71; }
            .kpi-card.low { border-left: 5px solid #e67e22; }
            .kpi-card.out { border-left: 5px solid #e74c3c; }
            .kpi-card.expiring { border-left: 5px solid #c0392b; }
            .kpi-content h4 {
                margin: 0; color: #718096; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
            }
            .kpi-value {
                display: block; font-size: 28px; font-weight: 800; margin-top: 5px;
            }
            .kpi-icon {
                font-size: 32px; opacity: 0.2;
            }
            .modal-overlay {
                display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
            }
            .modal {
                background: white; padding: 25px; border-radius: 10px; width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
        </style>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; color: #333;">Inventory Management</h1>
                <p style="margin: 5px 0 0 0; color: #777;">Monitor your stocks and supplies</p>
            </div>
            <button class="btn-primary" style="background: #4b3832; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px;" onclick="openInventoryModal()">
                <i class="fa fa-plus"></i> Add New Item
            </button>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card good" onclick="filterFromCard('good')">
                <div class="kpi-content">
                    <h4>Good Stock</h4>
                    <span class="kpi-value" id="summaryGood" style="color: #2ecc71;">0</span>
                </div>
                <div class="kpi-icon" style="color: #2ecc71;"><i class="fa fa-check-circle"></i></div>
            </div>
            
            <div class="kpi-card low" onclick="filterFromCard('low')">
                <div class="kpi-content">
                    <h4>Low Stock</h4>
                    <span class="kpi-value" id="summaryLow" style="color: #e67e22;">0</span>
                </div>
                <div class="kpi-icon" style="color: #e67e22;"><i class="fa fa-exclamation-triangle"></i></div>
            </div>

            <div class="kpi-card out" onclick="filterFromCard('out')">
                <div class="kpi-content">
                    <h4>Out of Stock</h4>
                    <span class="kpi-value" id="summaryOut" style="color: #e74c3c;">0</span>
                </div>
                <div class="kpi-icon" style="color: #e74c3c;"><i class="fa fa-times-circle"></i></div>
            </div>

            <div class="kpi-card expiring" onclick="filterFromCard('expiring')">
                <div class="kpi-content">
                    <h4>Expiring / Expired</h4>
                    <span class="kpi-value" id="summaryExpiring" style="color: #c0392b;">0</span>
                </div>
                <div class="kpi-icon" style="color: #c0392b;"><i class="fa fa-hourglass-half"></i></div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="position: relative; flex: 1;">
                <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #aaa;"></i>
                <input type="text" id="searchInventory" placeholder="Search for items..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ddd; border-radius: 5px; outline: none;">
            </div>
            
            <div style="position: relative; width: 230px;">
                <i class="fa fa-filter" style="position: absolute; left: 12px; top: 12px; color: #aaa; pointer-events: none;"></i>
                
                <select id="statusFilter" style="width: 100%; padding: 10px 30px 10px 35px; border: 1px solid #ddd; border-radius: 5px; background: white; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none;">
                    <option value="all">All Items</option>
                    <option value="good">In-Stock (Good)</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                    <option disabled>──────────</option>
                    <option value="expiring">⚠️ Expiring Soon / Expired</option>
                    <option disabled>──────────</option>
                    <option value="ingredients">☕ Ingredients</option>
                    <option value="packaging">📦 Packaging</option>
                </select>
                
                <i class="fa fa-chevron-down" style="position: absolute; right: 12px; top: 14px; color: #aaa; pointer-events: none; font-size: 12px;"></i>
            </div>
        </div>

        <table class="inventory-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
            <thead style="background: #4b3832; color: white; text-align: left;">
                <tr>
                    <th style="padding: 15px;">Item Name & Details</th>
                    <th style="padding: 15px;">Stock Quantity</th>
                    <th style="padding: 15px;">Unit</th>
                    <th style="padding: 15px;">Next Expiration</th> 
                    <th style="padding: 15px;">Status</th>
                    <th style="padding: 15px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr><td colspan="6" style="text-align:center; padding: 30px; color: #888;">Loading inventory...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="inventoryModal">
    <div class="modal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 id="modalTitle" style="margin: 0;">Add Stock Item</h2>
            <button onclick="closeInventoryModal()" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form id="inventoryForm" onsubmit="event.preventDefault(); saveInventory();">            
            <input type="hidden" name="id" id="inv_id" value="">
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-weight: bold;">Item Name</label>
                <input type="text" name="item_name" id="item_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-weight: bold;">Description</label>
                <input type="text" name="description" id="inv_description" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-weight: bold;">Unit</label>
                <select name="unit" id="unit" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="pcs">Pieces (pcs)</option>
                    <option value="pack">Pack</option>
                    <option value="kg">Kilogram (kg)</option>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; cursor: pointer; color: #d32f2f; font-size: 13px; background: #ffebee; padding: 8px; border-radius: 5px;">
                    <input type="checkbox" id="item_no_expire" style="margin-right: 10px;"> No Expiration Date (Packaging)
                </label>
            </div>
            <button type="submit" style="width: 100%; background: #4b3832; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold;">Save Item</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="restockModal">
    <div class="modal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Restock Item</h2>
            <button onclick="closeRestockModal()" style="border: none; background: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="restockForm" onsubmit="event.preventDefault(); submitRestock();">
            <input type="hidden" id="restock_inv_id">
            <p id="restock_item_name" style="font-weight: bold; color: #0d47a1; background: #e3f2fd; padding: 10px; border-radius: 5px; text-align: center;"></p>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold;">Quantity to Add</label>
                <input type="number" id="restock_qty" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold;">Expiration Date</label>
                <input type="date" id="restock_exp_date" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <button type="submit" style="width: 100%; background: #0d47a1; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold;">Submit Restock</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="stockOutModal">
    <div class="modal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #e65100;">Stock Out</h2>
            <button onclick="closeStockOutModal()" style="border: none; background: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="stockOutForm" onsubmit="event.preventDefault(); submitStockOut();">
            <input type="hidden" id="stockout_inv_id">
            <p id="stockout_item_name" style="font-weight: bold; color: #e65100; background: #fff3e0; padding: 10px; border-radius: 5px; text-align: center;"></p>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold;">Quantity to Deduct</label>
                <input type="number" id="stockout_qty" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            <button type="submit" style="width: 100%; background: #e65100; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold;">Deduct Stock</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="notificationModal">
    <div class="modal" style="width: 450px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <h2 style="margin: 0; color: #d32f2f;"><i class="fa fa-bell"></i> Inventory Alerts</h2>
            <button onclick="closeNotificationModal()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #888;">&times;</button>
        </div>
        
        <div id="notificationList" style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
            </div>

        <button onclick="closeNotificationModal()" style="width: 100%; margin-top: 15px; background: #f1f5f9; color: #4a5568; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Close</button>
    </div>
</div>

<script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>