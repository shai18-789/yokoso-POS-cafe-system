<?php
session_start();
// Pugngan ang cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories | Admin</title>
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
            <a href="category.php" class="nav-link active"><i class="fa fa-tags"></i> Categories</a>
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
    
    <div class="main-content" style="padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #333;">Categories</h1>
                <p style="margin: 5px 0 0 0; color: #777;">Manage your product categories</p>
            </div>
            <button onclick="showCatPopup()" style="background: #4b3832; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#3a2a25'" onmouseout="this.style.background='#4b3832'">
                <i class="fa fa-plus"></i> Add Category
            </button>
        </div>

        <div class="table-controls" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <input type="text" id="searchCategory" placeholder="Search category name..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            
            <select id="statusFilter" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; cursor: pointer;">
                <option value="All">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #4E372C; color: white; text-transform: uppercase; font-size: 13px;">
                    <tr>
                        <th style="padding: 15px; text-align: left; width: 10%;">ID</th>
                        <th style="padding: 15px; text-align: left; width: 70%;">Category Name</th>
                        <th style="padding: 15px; text-align: center; width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="categoryTableBody">
                    <tr>
                        <td colspan="3" style="text-align: center; color: #888; padding: 30px;"><i class="fa fa-spinner fa-spin"></i> Loading categories...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="theCategoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal" style="background: white; padding: 25px; border-radius: 10px; width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="theCatTitle" style="margin: 0; color: #333;">Add Category</h2>
            <button type="button" onclick="hideCatPopup()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #888;">&times;</button>
        </div>

        <form onsubmit="submitCategory(event)">            
            <input type="hidden" id="theCatId">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">Category Name</label>
                <input type="text" id="theCatName" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px;" placeholder="e.g., Frappe, Pastries, Add-ons">
            </div>

            <div style="margin-top: 15px; margin-bottom: 10px;">
    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold;">
        <input type="checkbox" id="categoryStatus" checked style="width: 18px; height: 18px; cursor: pointer;">
        <span id="statusLabel" style="color: #2e7d32;">Active</span>
    </label>
</div>
            
            <button type="submit" style="width: 100%; background: #4b3832; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.2s;" onmouseover="this.style.background='#3a2a25'" onmouseout="this.style.background='#4b3832'">Save Category</button>
        </form>
    </div>
</div>

<script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>