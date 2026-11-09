/* ==========================================
   1. GLOBAL INITIALIZATION & CLOCK
   ========================================== */
window.cafeUsersList = window.cafeUsersList || []; 
window.cafeCategoriesList = window.cafeCategoriesList || []; // Gidugang para sa category filter

window.updateClock = function() {
    const clockElement = document.getElementById('clock');
    if (clockElement) {
        const now = new Date();
        clockElement.innerHTML = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
}
if (window.clockInterval) clearInterval(window.clockInterval);
window.clockInterval = setInterval(window.updateClock, 1000);
window.updateClock();

/* ==========================================
   2. EVENT DELEGATION
   ========================================== */
if (!window.adminEventsLoaded) {
    window.adminEventsLoaded = true;

    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('userList') && typeof window.loadUsers === 'function') window.loadUsers();
        if (document.getElementById('logList') && typeof window.loadAuditLogs === 'function') window.loadAuditLogs();
        if (document.getElementById('categoryTableBody') && typeof window.loadCategories === 'function') window.loadCategories();
        checkGlobalInventoryAlerts(); 
    });

    document.addEventListener('input', function(e) {
        if (e.target.id === 'searchInput' && typeof window.renderTable === 'function') window.renderTable();
        // CATEGORY SEARCH
        if (e.target.id === 'searchCategory' && typeof window.renderCategoryTable === 'function') window.renderCategoryTable(); 
        // INVENTORY SEARCH (Kini ang kulang ganiha!)
        if (e.target.id === 'searchInventory' && typeof window.renderInventoryTable === 'function') window.renderInventoryTable();
    });

    document.addEventListener('change', function(e) {
        if (e.target.id === 'roleFilter' && typeof window.renderTable === 'function') window.renderTable();
        
        // Kay parehas man silag ID ('statusFilter'), atong ilhon kung asa nga page ta dapita
        if (e.target.id === 'statusFilter') {
            if (window.location.pathname.includes('inventory.php') && typeof window.renderInventoryTable === 'function') {
                window.renderInventoryTable();
            } else if (typeof window.renderCategoryTable === 'function') {
                window.renderCategoryTable();
            }
        }
    });

    document.addEventListener('submit', function(e) {
        if (e.target.id === 'userForm' && typeof window.handleAddUser === 'function') window.handleAddUser(e, e.target); 
        if (e.target.id === 'editUserForm' && typeof window.handleUpdateUser === 'function') window.handleUpdateUser(e, e.target); 
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'addNewUserBtn' && typeof window.openModal === 'function') {
            window.openModal();
        }

        const editBtn = e.target.closest('.edit-user-btn');
        if (editBtn && typeof window.editUser === 'function') window.editUser(editBtn.getAttribute('data-id'));

        const toggleBtn = e.target.closest('.toggle-status-btn');
        if (toggleBtn && typeof window.toggleStatus === 'function') window.toggleStatus(toggleBtn.getAttribute('data-id'), toggleBtn.getAttribute('data-status'));

        if (e.target.classList.contains('close-modal') || e.target.id === 'userModal' || e.target.id === 'editModal') {
            if (typeof window.closeModal === 'function') window.closeModal();
            if (typeof window.closeEditModal === 'function') window.closeEditModal();
        }

        /* SMART SPA ROUTER */
        const link = e.target.closest('.sidebar .nav-link');
        if (!link) return;
        const url = link.getAttribute('href');
        if (url.includes('logout.php') || url === '#' || url === '') return;

        e.preventDefault();
        document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        fetch(url).then(res => res.text()).then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const main = document.querySelector('.main-content');
            
            if (main && doc.querySelector('.main-content')) {
                main.innerHTML = doc.querySelector('.main-content').innerHTML;
                
const modalIds = ['userModal', 'editModal', 'menuModal', 'theCategoryModal', 'inventoryModal', 'restockModal', 'stockOutModal', 'notificationModal', 'receiptModal'];                modalIds.forEach(id => {
                    const oldModal = document.getElementById(id);
                    if (oldModal) oldModal.remove();
                    const newModal = doc.getElementById(id);
                    if (newModal) document.body.appendChild(newModal);
                });

                doc.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(a => newScript.setAttribute(a.name, a.value));
                    if (oldScript.src) {
                        const fileName = oldScript.src.split('/').pop().split('?')[0]; 
                        const isLoaded = Array.from(document.scripts).some(s => s.src && s.src.split('/').pop().split('?')[0] === fileName);
                        if (isLoaded) return; 
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    } else {
                        newScript.appendChild(document.createTextNode(`{ ${oldScript.innerHTML} }`));
                    }
                    document.body.appendChild(newScript).parentNode.removeChild(newScript);
                });
            }
            
            window.history.pushState({}, '', url);
            
            setTimeout(() => {
                if (url.includes('users.php') && typeof window.loadUsers === 'function') window.loadUsers();
                if (url.includes('menu.php')) {
                    if (typeof window.loadMenu === 'function') window.loadMenu();
                    else if (typeof loadMenu === 'function') loadMenu();
                }
                if (url.includes('system_logs.php') && typeof window.loadAuditLogs === 'function') window.loadAuditLogs();
                if (url.includes('category.php') && typeof window.loadCategories === 'function') window.loadCategories();
                
                // INVENTORY LOADER (Kini ang kulang ganiha!)
                if (url.includes('inventory.php') && typeof window.loadInventory === 'function') window.loadInventory();
                
                checkGlobalInventoryAlerts(); 
            }, 100);
        });
    });

    window.addEventListener('popstate', () => location.reload());
}

/* ==========================================
   3. USER MANAGEMENT FUNCTIONS
   ========================================== */
window.loadUsers = function() {
    fetch('../../api/admin/get_users.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        window.cafeUsersList = data;
        window.renderTable();
    })
    .catch(err => console.error('Error loading users:', err));
}

window.renderTable = function() {
    const userList = document.getElementById('userList');
    if (!userList) return;

    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('roleFilter')?.value || 'All';

    userList.innerHTML = '';
    const filtered = window.cafeUsersList.filter(user => {
        const fullName = `${user.first_name || ''} ${user.middle_name || ''} ${user.last_name || ''}`.toLowerCase();
        const matchesSearch = fullName.includes(searchTerm) || (user.username || '').toLowerCase().includes(searchTerm);
        const matchesRole = roleFilter === 'All' || user.role === roleFilter;
        return matchesSearch && matchesRole;
    });

    if (filtered.length === 0) {
        userList.innerHTML = '<tr><td colspan="7" style="text-align:center;">No users found.</td></tr>';
        return;
    }

    filtered.forEach(user => {
        const isActive = user.status === 'Active';
        const statusColor = isActive ? '#2e7d32' : '#888'; 

        userList.innerHTML += `
            <tr style="border-bottom: 1px solid #eee; transition: 0.2s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                <td style="padding: 15px;">${user.first_name || ''}</td>
                <td style="padding: 15px;">${user.middle_name || ''}</td>
                <td style="padding: 15px;">${user.last_name || ''}</td>
                <td style="padding: 15px;">${user.username}</td>
                <td style="padding: 15px;"><span style="color: #666; font-size: 13px;">${user.role}</span></td>
                <td style="padding: 15px; color: ${statusColor}; font-weight: bold; font-size: 14px;">${user.status}</td>
                <td style="padding: 15px;">
                    <div class="action-icons-container">
                        <button class="edit-icon-btn" onclick="window.editUser(${user.id})" title="Edit User">
                            <i class="fa fa-edit"></i>
                        </button>
                        <label class="switch" title="${isActive ? 'Click to Deactivate' : 'Click to Activate'}">
                            <input type="checkbox" ${isActive ? 'checked' : ''} onchange="window.toggleStatus(${user.id}, '${user.status}', this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </td>
            </tr>`;
    });
}

window.handleAddUser = function(e, form) {
    e.preventDefault();
    fetch('../../api/auth/register.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.showCustomAlert("User created successfully!", "success");
            window.closeModal();
            form.reset(); 
            window.loadUsers();
        } else { window.showCustomAlert(data.message, "error"); }
    });
}

window.handleUpdateUser = function(e, form) {
    e.preventDefault();
    fetch('../../api/admin/update_user.php', { method: 'POST', body: new FormData(form) })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showCustomAlert("User updated successfully!", "success");
            window.closeEditModal();
            window.loadUsers();
        } else { window.showCustomAlert(data.message, "error"); }
    });
}

window.toggleStatus = function(id, currentStatus, checkboxElement) {
    const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
    const actionWord = currentStatus === 'Active' ? 'Deactivate' : 'Activate';
    
    window.showCustomConfirm(`Are you sure you want to ${actionWord} this user?`, 
        function() {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', newStatus);
            fetch('../../api/admin/update_status.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.showCustomAlert(`User has been set to ${newStatus}!`, "success");
                    window.loadUsers(); 
                } else { 
                    window.showCustomAlert(data.message, "error"); 
                    checkboxElement.checked = (currentStatus === 'Active');
                }
            });
        },
        function() {
            checkboxElement.checked = (currentStatus === 'Active');
        }
    );
}

window.editUser = function(id) {
    const user = window.cafeUsersList.find(u => u.id == id);
    if (user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_first_name').value = user.first_name || '';
        document.getElementById('edit_middle_name').value = user.middle_name || '';
        document.getElementById('edit_last_name').value = user.last_name || '';
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        const modal = document.getElementById('editModal');
        if (modal) modal.style.display = 'flex';
    }
}

/* ==========================================
   4. SYSTEM LOGS & MODAL HELPERS
   ========================================== */
window.loadAuditLogs = function() {
    fetch('../../api/admin/get_logs.php').then(res => res.json()).then(data => {
        const logList = document.getElementById('logList');
        if(!logList) return;
        logList.innerHTML = data.map(log => `
            <tr>
                <td>${log.created_at}</td>
                <td style="color:#2e7d32;"><strong>${log.fullname}</strong></td>
                <td><span style="background:#eee; padding:3px 8px; border-radius:4px; font-size:12px;">${log.action}</span></td>
                <td><small>${log.details}</small></td>
            </tr>`).join('');
    });
}

window.openModal = function() { document.getElementById('userModal')?.classList.add('active'); }
window.closeModal = function() { document.getElementById('userModal')?.classList.remove('active'); }
window.closeEditModal = function() { const m = document.getElementById('editModal'); if(m) m.style.display = 'none'; }


/* ==========================================
   5. CUSTOM ALERTS (Success/Confirm)
   ========================================== */
window.showCustomAlert = function(message, type = 'success') {
    const overlay = document.createElement('div');
    overlay.style = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;";
    const color = type === 'success' ? '#2ecc71' : '#e74c3c';
    overlay.innerHTML = `
        <div style="background:white; padding:30px; border-radius:10px; text-align:center; min-width:300px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <div style="font-size:45px; margin-bottom:10px;">${type === 'success' ? '✅' : '⚠️'}</div>
            <h3 style="margin:0 0 10px;">${type === 'success' ? 'Success!' : 'Error!'}</h3>
            <p style="color:#666; margin-bottom:20px;">${message}</p>
            <button onclick="this.parentElement.parentElement.remove()" style="background:${color}; color:white; border:none; padding:10px 30px; border-radius:5px; cursor:pointer; font-weight:bold;">OK</button>
        </div>`;
    document.body.appendChild(overlay);
}

window.showCustomConfirm = function(message, onConfirm, onCancel) {
    const overlay = document.createElement('div');
    overlay.style = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;";
    overlay.innerHTML = `
        <div style="background:white; padding:30px; border-radius:10px; text-align:center; min-width:300px;">
            <div style="font-size:45px; margin-bottom:10px;">❓</div>
            <h3 style="margin:0 0 10px;">Confirm Action</h3>
            <p style="color:#666; margin-bottom:20px;">${message}</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="cnclBtn" style="padding:10px 20px; border-radius:5px; cursor:pointer; border:none; background:#eee;">Cancel</button>
                <button id="okBtn" style="padding:10px 20px; border-radius:5px; cursor:pointer; border:none; background:#f39c12; color:white;">Yes, Continue</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    
    document.getElementById('cnclBtn').onclick = () => { 
        overlay.remove(); 
        if (typeof onCancel === 'function') onCancel();
    };
    
    document.getElementById('okBtn').onclick = () => { 
        overlay.remove(); 
        onConfirm(); 
    };
}

/* ==========================================
   6. CATEGORY MANAGEMENT FUNCTIONS
   ========================================== */
window.showCatPopup = function(id = '', name = '', status = 1) {
    const idInput = document.getElementById('theCatId');
    const nameInput = document.getElementById('theCatName');
    const statusCheckbox = document.getElementById('categoryStatus'); 
    const statusLabel = document.getElementById('statusLabel');       
    const modalTitle = document.getElementById('theCatTitle');
    const modal = document.getElementById('theCategoryModal');

    if (idInput && nameInput && modalTitle && modal) {
        idInput.value = id;
        nameInput.value = name;
        modalTitle.innerText = id === '' ? 'Add Category' : 'Edit Category';

        if (statusCheckbox) {
            statusCheckbox.checked = (status == 1);
            if (statusLabel) {
                statusLabel.innerText = statusCheckbox.checked ? "Active" : "Inactive";
                statusLabel.style.color = statusCheckbox.checked ? "#2e7d32" : "#c62828";
            }
        }
        modal.style.display = 'flex';
    }
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'categoryStatus') {
        const statusLabel = document.getElementById('statusLabel');
        if (statusLabel) {
            statusLabel.innerText = e.target.checked ? "Active" : "Inactive";
            statusLabel.style.color = e.target.checked ? "#2e7d32" : "#c62828";
        }
    }
});

window.hideCatPopup = function() {
    const modal = document.getElementById('theCategoryModal');
    if (modal) modal.style.display = 'none';
}

window.loadCategories = function() {
    fetch('../../api/admin/get_categories.php?t=' + new Date().getTime())
    .then(res => res.text())
    .then(text => {
        if (!text || text.trim() === '') throw new Error("BLANK_PHP");
        return JSON.parse(text);
    })
    .then(data => {
        if(data.error) {
            const tbody = document.getElementById('categoryTableBody');
            if(tbody) tbody.innerHTML = `<tr><td colspan="3" style="color: red; padding: 15px; font-weight: bold;">Backend Error: ${data.error}</td></tr>`;
            return;
        }
        window.cafeCategoriesList = data; 
        window.renderCategoryTable();     
    })
    .catch(err => {
        console.error("Category Load Error:", err);
        const tbody = document.getElementById('categoryTableBody');
        if (err.message === "BLANK_PHP" && tbody) tbody.innerHTML = '<tr><td colspan="3" style="color: red; padding: 15px;">Blank response! Check get_categories.php</td></tr>';
    });
}

// KINI ANG MO-DRAW SA TABLE UG MO-FILTER
window.renderCategoryTable = function() {
    const tbody = document.getElementById('categoryTableBody');
    if (!tbody) return;

    const searchTerm = document.getElementById('searchCategory')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || 'All';

    tbody.innerHTML = '';

    const filtered = window.cafeCategoriesList.filter(cat => {
        const catName = (cat.name || cat.category_name || '').toLowerCase();
        const matchesSearch = catName.includes(searchTerm);
        const matchesStatus = statusFilter === 'All' || cat.status.toString() === statusFilter;
        return matchesSearch && matchesStatus;
    });

    if(filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="color: #888; padding: 15px; font-weight: bold; text-align: center;">No categories found.</td></tr>';
        return;
    }

    filtered.forEach(cat => {
        const catName = cat.name || cat.category_name || 'Unknown Category';
        const safeName = catName.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        const statusBadge = cat.status == 1 
            ? `<span style="background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px; font-weight: bold;">Active</span>`
            : `<span style="background: #ffebee; color: #c62828; padding: 3px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px; font-weight: bold;">Inactive</span>`;
        const itemCount = cat.item_count || 0;

        tbody.innerHTML += `
            <tr onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'" style="transition: 0.2s; background: white;">
                <td style="padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #666;">#${cat.id}</td>
                <td style="padding: 15px; border-bottom: 1px solid #eee;">
                    <div style="font-weight: bold; color: #333; font-size: 15px;">${catName} ${statusBadge}</div>
                    <div style="font-size: 12px; color: #888; margin-top: 4px;">${itemCount} items</div>
                </td>
                <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                    <button type="button" onclick="window.showCatPopup('${cat.id}', '${safeName}', ${cat.status})" title="Edit Category" style="background: none; border: none; color: #333; cursor: pointer; transition: 0.2s; font-size: 20px; padding: 0;" onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#333'">
                        <i class="fa fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

window.submitCategory = function(e) {
    e.preventDefault();
    const idInput = document.getElementById('theCatId');
    const nameInput = document.getElementById('theCatName');
    const statusCheckbox = document.getElementById('categoryStatus');
    const statusValue = statusCheckbox && statusCheckbox.checked ? 1 : 0;

    fetch('../../api/admin/save_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id: idInput.value, 
            name: nameInput.value,
            status: statusValue
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.hideCatPopup();
            window.loadCategories(); 
            window.showCustomAlert("Category saved successfully!", "success");
        } else {
            window.showCustomAlert('Error saving category: ' + data.error, "error");
        }
    })
    .catch(err => console.error(err));
}

/* ==========================================
   7. SMART GLOBAL INVENTORY BADGE
   ========================================== */
function checkGlobalInventoryAlerts() {
    const navLinks = document.querySelectorAll('.sidebar nav a');
    let invLink = null;
    navLinks.forEach(link => {
        if (link.href.includes('inventory.php') || link.innerText.includes('Inventory')) {
            invLink = link;
        }
    });

    if (!invLink) return; 

    fetch('/yokoso_g1/api/admin/get_inventory.php?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        if (data.error || !Array.isArray(data)) return;

        let globalAlerts = 0;
        data.forEach(item => {
            const qty = parseInt(item.stock_quantity) || 0;
            const threshold = parseInt(item.low_stock_threshold) || 0;

            if (qty === 0 || qty <= threshold) {
                globalAlerts++;
            } else if (item.next_expiration && item.is_expirable != 0 && item.next_expiration !== '2099-12-31') {
                const expDate = new Date(item.next_expiration);
                expDate.setHours(0,0,0,0);
                const today = new Date(); today.setHours(0,0,0,0);
                if (Math.ceil((expDate - today) / (1000 * 60 * 60 * 24)) <= 30) {
                    globalAlerts++;
                }
            }
        });

        let badge = document.getElementById('sidebarInventoryBadge');
        if (!badge) {
            invLink.style.position = 'relative';
            badge = document.createElement('span');
            badge.id = 'sidebarInventoryBadge';
            badge.style.cssText = 'position: absolute; top: 8px; right: 15px; background: #e74c3c; color: white; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); cursor: pointer;';
            invLink.appendChild(badge);
        }

        badge.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation(); 
            if (window.location.pathname.includes('inventory.php')) {
                if(typeof openNotificationModal === 'function') openNotificationModal();
            } else {
                window.location.href = 'inventory.php'; 
            }
        };

        if (globalAlerts > 0) {
            badge.innerText = globalAlerts;
            badge.style.display = 'block';
        } else {
            if(badge) badge.style.display = 'none';
        }
    })
    .catch(err => console.log("Inventory global check skipped."));
}


/* ==========================================
   8. INVENTORY MANAGEMENT FUNCTIONS
   ========================================== */
window.allInventory = [];

window.loadInventory = function() {
    fetch('../../api/admin/get_inventory.php?t=' + Date.now())
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.error) {
                const tbody = document.getElementById('inventoryTableBody');
                if(tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red; padding: 20px;">API Error: ${data.error}</td></tr>`;
                return;
            }
            if (!Array.isArray(data)) return;
            
            window.allInventory = data;
            window.renderInventoryTable();
        } catch(e) {
            console.error("JSON Error in Inventory:", e);
        }
    })
    .catch(err => console.error("Fetch Error in Inventory:", err));
}

window.renderInventoryTable = function() {
    try {
        const tbody = document.getElementById('inventoryTableBody');
        if(!tbody) return;

        const searchInput = document.getElementById('searchInventory');
        const statusFilter = document.getElementById('statusFilter');

        const searchStr = searchInput ? searchInput.value.toLowerCase() : '';
        const filterStat = statusFilter ? statusFilter.value : 'all';
        
        tbody.innerHTML = '';

        let countGood = 0; let countLow = 0; let countOut = 0; let countExpiring = 0;
        let totalAlerts = 0; let alertsHTML = '';

        window.allInventory.forEach(item => {
            // SAFE PANGALAN ARON DILI MAG-ERROR KUNG NAAY QUOTES
            const itemName = item.item_name || 'Unknown Item';
            const qty = parseInt(item.stock_quantity) || 0;
            const threshold = parseInt(item.low_stock_threshold) || 0;
            const safeAlertName = itemName.replace(/'/g, "\\'").replace(/"/g, '&quot;');

            if (qty === 0) {
                countOut++;
                alertsHTML += `<div onclick="window.filterFromAlert('${safeAlertName}')" style="padding: 10px; background: #ffebee; border-left: 4px solid #f44336; margin-bottom: 8px; border-radius: 4px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"><strong>❌ ${itemName}</strong> - Out of Stock! <span style="float:right; color:#f44336; font-size:12px;"><i class="fa fa-search"></i> Search</span></div>`;
                totalAlerts++;
            } else if (qty <= threshold) {
                countLow++;
                alertsHTML += `<div onclick="window.filterFromAlert('${safeAlertName}')" style="padding: 10px; background: #fff8e1; border-left: 4px solid #ffc107; margin-bottom: 8px; border-radius: 4px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"><strong>⚠️ ${itemName}</strong> - Low Stock (${qty} left) <span style="float:right; color:#ffb300; font-size:12px;"><i class="fa fa-search"></i> Search</span></div>`;
                totalAlerts++;
            } else {
                countGood++;
            }

            if (item.next_expiration && item.is_expirable != 0 && item.next_expiration !== '2099-12-31') {
                const expDate = new Date(item.next_expiration);
                expDate.setHours(0,0,0,0); 
                const today = new Date();
                today.setHours(0,0,0,0);
                const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
                
                if (diffDays <= 30) {
                    countExpiring++;
                    if (diffDays < 0) {
                        alertsHTML += `<div onclick="window.filterFromAlert('${safeAlertName}')" style="padding: 10px; background: #ffebee; border-left: 4px solid #d32f2f; margin-bottom: 8px; border-radius: 4px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"><strong>🚨 ${itemName}</strong> - Expired na! <span style="float:right; color:#d32f2f; font-size:12px;"><i class="fa fa-search"></i> Search</span></div>`;
                        totalAlerts++;
                    } else {
                        alertsHTML += `<div onclick="window.filterFromAlert('${safeAlertName}')" style="padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800; margin-bottom: 8px; border-radius: 4px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"><strong>⏳ ${itemName}</strong> - Expiring in ${diffDays} days! <span style="float:right; color:#f57c00; font-size:12px;"><i class="fa fa-search"></i> Search</span></div>`;
                        totalAlerts++;
                    }
                }
            }
        });

        if (document.getElementById('summaryGood')) document.getElementById('summaryGood').innerText = countGood;
        if (document.getElementById('summaryLow')) document.getElementById('summaryLow').innerText = countLow;
        if (document.getElementById('summaryOut')) document.getElementById('summaryOut').innerText = countOut;
        if (document.getElementById('summaryExpiring')) document.getElementById('summaryExpiring').innerText = countExpiring;

        const sidebarBadge = document.getElementById('sidebarInventoryBadge');
        const notifList = document.getElementById('notificationList');
        if (sidebarBadge && notifList) {
            if (totalAlerts > 0) {
                sidebarBadge.innerText = totalAlerts;
                sidebarBadge.style.display = 'block';
                notifList.innerHTML = alertsHTML;
            } else {
                sidebarBadge.style.display = 'none';
                notifList.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">No current alerts. Everything is good!</p>';
            }
        }

        const filteredItems = window.allInventory.filter(item => {
            const itemName = item.item_name || '';
            const matchName = itemName.toLowerCase().includes(searchStr) || (item.description || '').toLowerCase().includes(searchStr);
            if (!matchName) return false;

            const qty = parseInt(item.stock_quantity) || 0;
            const threshold = parseInt(item.low_stock_threshold) || 0;
            let itemStatus = 'good';
            if (qty === 0) itemStatus = 'out';
            else if (qty <= threshold) itemStatus = 'low';

            let isExpiringSoon = false;
            if (item.next_expiration && item.is_expirable != 0 && item.next_expiration !== '2099-12-31') {
                const expDate = new Date(item.next_expiration);
                expDate.setHours(0,0,0,0); 
                const today = new Date();
                today.setHours(0,0,0,0);
                if (Math.ceil((expDate - today) / (1000 * 60 * 60 * 24)) <= 30) isExpiringSoon = true;
            }

            const isIngredient = (item.is_expirable != 0);

            if (filterStat === 'all') return true;
            if (filterStat === 'expiring') return isExpiringSoon;
            if (filterStat === 'ingredients') return isIngredient;
            if (filterStat === 'packaging') return !isIngredient;
            return itemStatus === filterStat;
        });

        if (filteredItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">Walay nakit-an nga item.</td></tr>';
            return;
        }

        filteredItems.forEach(item => {
            const itemName = item.item_name || 'Unknown Item';
            let statusHTML = ''; let quantityClass = '';
            const qty = parseInt(item.stock_quantity) || 0;
            const threshold = parseInt(item.low_stock_threshold) || 0;

            if (qty === 0) {
                statusHTML = `<span style="background: #e74c3c; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;"><i class="fa fa-times-circle"></i> Out of Stock</span>`;
                quantityClass = 'color: #e74c3c; font-weight: bold;';
            } else if (qty <= threshold) {
                statusHTML = `<span style="background: #e67e22; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;"><i class="fa fa-exclamation-triangle"></i> Low Stock</span>`;
                quantityClass = 'color: #e67e22; font-weight: bold;';
            } else {
                statusHTML = `<span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;"><i class="fa fa-check-circle"></i> Good</span>`;
                quantityClass = 'color: #2e7d32; font-weight: bold;';
            }

            let expHTML = '<span style="color: #ccc; font-style: italic; font-size: 12px;">No Stock Data</span>';
            if (item.next_expiration === '2099-12-31' || item.is_expirable == 0) {
                expHTML = '<span style="color: #888; font-weight: bold; background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 12px;">N/A (No Expiry)</span>';
            } else if (item.next_expiration) {
                const expDate = new Date(item.next_expiration);
                expDate.setHours(0,0,0,0); const today = new Date(); today.setHours(0,0,0,0);
                const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
                
                if (diffDays < 0) {
                    expHTML = `<span style="background: #c0392b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Expired! (${item.expiring_quantity} ${item.unit})</span><br><small style="color: #c0392b;">${item.next_expiration}</small>`;
                } else if (diffDays <= 30) {
                    expHTML = `<span style="background: #e67e22; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Exp in ${diffDays} days (${item.expiring_quantity} ${item.unit})</span><br><small style="color: #e67e22;">${item.next_expiration}</small>`;
                } else {
                    expHTML = `<span style="color: #27ae60; font-weight: bold; font-size: 13px;">${item.next_expiration}</span><br><small style="color: #888;">(${item.expiring_quantity} ${item.unit} in this batch)</small>`;
                }
            }

            const safeName = itemName.replace(/'/g, "\\'").replace(/"/g, '&quot;');

            tbody.innerHTML += `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px;">
                        <div style="font-weight: bold; font-size: 15px; color: #333;">${itemName}</div>
                        <div style="font-size: 12px; color: #888; margin-top: 4px; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${item.description}">${item.description || '<i>No description</i>'}</div>
                    </td>
                    <td style="padding: 15px; font-size: 18px; ${quantityClass}">${qty}</td> 
                    <td style="padding: 15px;">${item.unit}</td>
                    <td style="padding: 15px;">${expHTML}</td>
                    <td style="padding: 15px;">${statusHTML}</td>
                    <td style="padding: 15px; width: 220px;">
                        <button style="background: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; padding: 6px 8px; border-radius: 4px; cursor: pointer; margin-right: 3px; font-weight: bold;" onclick="window.restockItem(${item.id}, '${safeName}', ${qty}, '${item.unit}')" title="Restock"><i class="fa fa-plus"></i></button>
                        <button style="background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; padding: 6px 8px; border-radius: 4px; cursor: pointer; margin-right: 3px; font-weight: bold;" onclick="window.stockOutItem(${item.id}, '${safeName}', ${qty}, '${item.unit}')" title="Stock Out (Pull out to bar)"><i class="fa fa-minus"></i></button>
                        <button style="background: #f9f9f9; color: #333; border: 1px solid #ddd; padding: 6px 8px; border-radius: 4px; cursor: pointer; font-weight: bold;" onclick="window.editInventory(${item.id})" title="Edit Item Details"><i class="fa fa-edit"></i></button>
                    </td>
                </tr>`;
        });
    } catch(e) {
        console.error("Error drawing table:", e);
    }
}

// 📍 KINI ANG NAWALA GANIHA PARA MO-WORK ANG PAG CLICK SA ALERT
window.filterFromAlert = function(itemName) {
    window.closeNotificationModal();
    const searchInput = document.getElementById('searchInventory');
    if (searchInput) {
        searchInput.value = itemName;
    }
    window.renderInventoryTable();
}

// MODAL CONTROLS
window.openInventoryModal = function() {
    document.getElementById('inventoryForm').reset();
    document.getElementById('inv_id').value = '';
    document.getElementById('modalTitle').innerText = 'Add Stock Item';
    if(document.getElementById('item_no_expire')) document.getElementById('item_no_expire').checked = false; 
    document.getElementById('inventoryModal').style.display = 'flex';
}
window.closeInventoryModal = function() { document.getElementById('inventoryModal').style.display = 'none'; }

window.editInventory = function(id) {
    try {
        const item = window.allInventory.find(i => i.id == id);
        if (item) {
            // SAFE SETTER: Aron dili mo-crash kung naay missing nga ID sa HTML
            const safeSetValue = (elemId, val) => {
                const el = document.getElementById(elemId);
                if (el) el.value = val;
                else console.warn("Pahibalo: Wala makit-i sa HTML ang input box nga:", elemId);
            };

            safeSetValue('inv_id', item.id);
            safeSetValue('item_name', item.item_name);
            safeSetValue('inv_description', item.description || '');
            safeSetValue('stock_quantity', parseInt(item.stock_quantity) || 0);
            safeSetValue('unit', item.unit || '');
            
            // Checkbox para sa Expiration
            const noExpireCb = document.getElementById('item_no_expire');
            if (noExpireCb) { 
                noExpireCb.checked = (item.is_expirable == 0); 
            }

            // Modal Title
            const titleEl = document.getElementById('modalTitle');
            if (titleEl) titleEl.innerText = 'Edit Stock Item';

            // ABILHON ANG MODAL
            const modal = document.getElementById('inventoryModal');
            if (modal) {
                modal.style.display = 'flex';
            } else {
                alert("Error: Wala makit-i ang tibuok 'inventoryModal' sa imong HTML code.");
            }
        }
    } catch (e) {
        console.error("Edit Inventory Error:", e);
        alert("Naay gamay nga Javascript error. Pislita ang F12 para makita sa console.");
    }
}

window.saveInventory = function(e) {
    if (e) e.preventDefault(); // Pang-sagang kung na-trigger sa form submit

    const form = document.getElementById('inventoryForm');
    const formData = new FormData(form);
    
    const noExpireCb = document.getElementById('item_no_expire');
    const isExpirable = (noExpireCb && noExpireCb.checked) ? 0 : 1;
    formData.append('is_expirable', isExpirable); 

    // 📍 BUG FIX: Anti-Zero Stock Logic
    const invId = document.getElementById('inv_id').value;
    
    if (invId) {
        // KUNG NAG-EDIT: Pangitaon ang daan nga stock ug i-apil og send
        const existingItem = window.allInventory.find(i => i.id == invId);
        if (existingItem && !formData.has('stock_quantity')) {
            formData.append('stock_quantity', existingItem.stock_quantity);
        }
    } else {
        // KUNG NAG-ADD OG BAG-O: I-set sa 0 ang initial stock
        if (!formData.has('stock_quantity')) {
            formData.append('stock_quantity', 0);
        }
    }

    fetch('../../api/admin/save_inventory.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.closeInventoryModal(); 
            window.showCustomAlert("Item saved successfully!", "success"); 
            window.loadInventory(); 
        } else {
            window.showCustomAlert("Error: " + data.error, "error"); 
        }
    })
    .catch(err => { 
        console.error("Save Inventory Error:", err);
        window.showCustomAlert("System Error. Dili ka-connect sa server.", "error"); 
    });
}

window.restockItem = function(id, name, currentStock, unit) {
    document.getElementById('restock_inv_id').value = id;
    document.getElementById('restock_item_name').innerText = `Adding: ${name} (Current: ${currentStock} ${unit})`;
    document.getElementById('restock_qty').value = '';
    document.getElementById('restock_exp_date').value = '';
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('restock_exp_date').setAttribute('min', today);
    document.getElementById('restockModal').style.display = 'flex';
}
window.closeRestockModal = function() { document.getElementById('restockModal').style.display = 'none'; }

window.submitRestock = function() {
    const id = document.getElementById('restock_inv_id').value;
    const qty = parseInt(document.getElementById('restock_qty').value);
    let expDate = document.getElementById('restock_exp_date').value;
    
    // 📍 SMART CHECK: Pangitaon sa system kung "Expirable" ba kining item-a
    const item = window.allInventory.find(i => i.id == id);
    const isExpirable = item ? (item.is_expirable != 0) : true;

    // Checkbox fallback (kung gibutangan nimo sa HTML)
    const noExpireCb = document.getElementById('no_expire_cb');
    const isChecked = noExpireCb ? noExpireCb.checked : false;

    // Kung DILI ma-pan-os ang item (e.g. Cups, Lids) O na-check ang box:
    // Automatic natong butangan og '2099-12-31' aron mo-lusot sa database.
    if (!isExpirable || isChecked) {
        expDate = '2099-12-31';
    }

    // Validation: Kung naay saktong quantity ug naay date (dili na blangko)
    if (qty > 0 && expDate !== '') {
        fetch('../../api/admin/restock_inventory.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, amount: qty, expiration_date: expDate })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.closeRestockModal();
                window.showCustomAlert("Stock added successfully!", "success");
                window.loadInventory();
            } else { 
                window.showCustomAlert("Error: " + data.error, "error"); 
            }
        })
        .catch(e => { 
            console.error(e);
            window.showCustomAlert("System Error. Dili ka-connect sa server.", "error"); 
        });
    } else {
        window.showCustomAlert("Palihug butangi og saktong quantity ug expiration date.", "error");
    }
}

window.toggleExpireDate = function() {
    const isChecked = document.getElementById('no_expire_cb').checked;
    const dateInput = document.getElementById('restock_exp_date');
    if(isChecked) {
        dateInput.value = ''; dateInput.disabled = true; dateInput.removeAttribute('required'); dateInput.style.backgroundColor = '#f0f0f0';
    } else {
        dateInput.disabled = false; dateInput.setAttribute('required', 'true'); dateInput.style.backgroundColor = 'white';
    }
}

window.openNotificationModal = function() { document.getElementById('notificationModal').style.display = 'flex'; }
window.closeNotificationModal = function() { document.getElementById('notificationModal').style.display = 'none'; }

window.filterFromCard = function(filterValue) {
    const dropdown = document.getElementById('statusFilter');
    if (dropdown) { dropdown.value = filterValue; window.renderInventoryTable(); }
}

window.stockOutItem = function(id, name, currentStock, unit) {
    if (currentStock <= 0) { window.showCustomAlert("Out of stock na ni! Walay ma-pull out.", "error"); return; }
    document.getElementById('stockout_inv_id').value = id;
    document.getElementById('stockout_item_name').innerText = `Pulling out: ${name}\n(Available: ${currentStock} ${unit})`;
    document.getElementById('stockout_qty').value = '';
    document.getElementById('stockout_qty').setAttribute('max', currentStock); 
    document.getElementById('stockOutModal').style.display = 'flex';
}

window.closeStockOutModal = function() { document.getElementById('stockOutModal').style.display = 'none'; }

window.submitStockOut = function() {
    const id = document.getElementById('stockout_inv_id').value;
    const qty = parseInt(document.getElementById('stockout_qty').value);

    if (qty > 0) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('amount', qty);

        fetch('../../api/admin/stockout_inventory.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.closeStockOutModal();
                window.showCustomAlert("Stock deducted successfully!", "success");
                window.loadInventory();
            } else { window.showCustomAlert("Error: " + data.error, "error"); }
        }).catch(e => { window.showCustomAlert("System Error.", "error"); });
    } else {
        window.showCustomAlert("Palihug butangi og saktong number.", "error");
    }
}