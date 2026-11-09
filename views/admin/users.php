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
    <title>Manage Users | Admin</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
<body>

<div class="app-container">
    <div class="sidebar">
        <div class="brand">
            <img src="../../assets/img/logo.png" class="sidebar-logo" onerror="this.src='https://via.placeholder.com/45'">
            <h3>Cafe Admin <span class="version">v1.0</span></h3>
        </div>
        
        <nav>
            <a href="index.php" class="nav-link"><i class="fa fa-home"></i> Dashboard</a>
            <a href="users.php" class="nav-link active"><i class="fa fa-users"></i> Manage Users</a>
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
        <style>
            .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e74c3c; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: #2ecc71; }
            input:checked + .slider:before { transform: translateX(20px); }
            .action-icons-container { display: flex; align-items: center; gap: 15px; }
            .edit-icon-btn { background: none; border: none; color: #333; font-size: 18px; cursor: pointer; transition: 0.2s; }
            .edit-icon-btn:hover { color: #3498db; }
        </style>

        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p class="subtitle">Create and manage accounts for your staff</p>
            </div>
            <button class="btn-primary" onclick="openModal()"><i class="fa fa-user-plus"></i> Add New User</button>
        </div>

        <div class="table-controls" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <input type="text" id="searchInput" placeholder="Search name or username..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            
            <select id="roleFilter" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <option value="All">All Roles</option>
                <option value="Admin">Admin</option>
                <option value="Cashier">Cashier</option>
                <option value="Barista">Barista</option>
            </select>
        </div>
        
        <div>
            <table id="userTable">
                <thead>
    <tr>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
                <tbody id="userList">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Add New User</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="userForm">
            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="input-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" id="middle_name" required>
            </div>
            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
        
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="input-group">
                <label>Role</label>
                <select name="role" id="role">
                    <option value="Cashier">Cashier</option>
                    <option value="Barista">Barista</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary btn-full">Save Account</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Edit User Details</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form id="editUserForm" onsubmit="event.preventDefault(); handleUpdateUser(event, this);">
            <input type="hidden" id="edit_id" name="id">

            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="edit_first_name" required>
            </div>
            
            <div class="input-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" id="edit_middle_name">
            </div>
            
            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="edit_last_name" required>
            </div>
        
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
            </div>
            
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="input-group" style="flex: 1; margin-bottom: 0;">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="Admin">Admin</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Barista">Barista</option>
                    </select>
                </div>
                <div class="input-group" style="flex: 1; margin-bottom: 0;">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label>New Password <small style="color: #888; font-weight: normal;">(Leave blank if not changing)</small></label>
                <input type="password" name="password" id="edit_password" placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-primary btn-full">Update Account</button>
        </form>
    </div>
</div>

<script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>