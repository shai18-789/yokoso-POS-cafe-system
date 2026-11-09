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
    <title>Menu Management | Admin</title>
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
            <a href="menu.php" class="nav-link active"><i class="fa fa-coffee"></i> Menu</a>
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

    <div class="main-content" style="padding: 30px;">
        
        <style>
            .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin: 0; }
            .switch input { opacity: 0; width: 0; height: 0; margin: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e74c3c; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: #2ecc71; }
            input:checked + .slider:before { transform: translateX(20px); }
        </style>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #333;">Menu Management</h1>
                <p style="margin: 5px 0 0 0; color: #777;">Add or edit your coffee and pastry offerings</p>
            </div>
            <button id="addNewMenuBtn" style="background: #4b3832; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                + Add Menu Item
            </button>
        </div>

        <div class="table-controls" style="display: flex; gap: 15px; margin-bottom: 20px;">
            <input type="text" id="searchMenu" placeholder="Search item name..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            
            <select id="categoryFilter" style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; cursor: pointer;">
                <option value="all">All Categories</option>
            </select>
        </div>

        <div id="menuGrid" class="menu-grid">
            <p>Loading menu items...</p>
        </div>
    </div>
</div>

<div class="modal-overlay" id="menuModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="modal" style="background: white; padding: 20px; border-radius: 10px; width: 400px;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0;">Manage Item</h2>
            <button class="modal-close" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form id="menuForm" enctype="multipart/form-data">            
            <input type="hidden" name="item_id" id="item_id" value="">
            
            <div class="input-group" style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">Product Name</label>
                <input type="text" name="product_name" id="product_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            
            <div class="input-group" style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">Category</label>
                <select name="category_id" id="category_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="" disabled selected>Select a Category...</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="price" style="font-weight: bold; display: block; margin-bottom: 5px;">Price (Medium/16oz) ₱</label>
                    <input type="number" step="0.01" id="price" name="price" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;" placeholder="0.00">
                </div>
                <div style="flex: 1;">
                    <label for="price_large" style="font-weight: bold; display: block; margin-bottom: 5px;">Price (Large/22oz) ₱</label>
                    <input type="number" step="0.01" id="price_large" name="price_large" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;" placeholder="I-blanko kung wala">
                </div>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label>Product Image</label><br>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="input-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Recipe / Instructions</label>
                <textarea name="recipe_text" id="recipe_text" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"></textarea>
            </div>

            <div class="input-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Availability Status</label>
                <select name="is_available" id="is_available" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="1">Available</option>
                    <option value="0">Unavailable</option>
                </select>
            </div>

            <button type="submit" style="width: 100%; background: #4b3832; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold;">Save Menu Item</button>
        </form>
    </div>
</div>

<script>
function forceLoadDropdowns() {
    fetch('../../api/admin/get_categories.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        let filterDD = document.getElementById('categoryFilter');
        let modalDD = document.getElementById('category_id');

        let filterOpts = '<option value="all">All Categories</option>';
        let modalOpts = '<option value="" disabled selected>Select a Category...</option>';

        data.forEach(cat => {
            if(cat.status == 1 || cat.status === "1") {
                let catName = cat.category_name || cat.name;
                filterOpts += `<option value="${cat.id}">${catName}</option>`;
                modalOpts += `<option value="${cat.id}">${catName}</option>`;
            }
        });

        if (filterDD) filterDD.innerHTML = filterOpts;
        if (modalDD) modalDD.innerHTML = modalOpts;
    })
    .catch(err => console.log("Dropdown fetch error:", err));
}

setTimeout(forceLoadDropdowns, 300);

document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'addNewMenuBtn' || e.target.closest('.edit-menu-btn')) {
        setTimeout(forceLoadDropdowns, 100); 
    }
});
</script>

<script src="../../assets/js/menu.js?v=<?php echo time(); ?>"></script>

</body>
</html>