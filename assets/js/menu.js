/* ==========================================
   1. VARIABLES & INITIAL LOAD
   ========================================== */
var allMenuItems = [];

// Tawgon dayon para sa unang load
loadMenu();

/* ==========================================
   2. EVENT DELEGATION (The SPA Fix)
   ========================================== */
// Maminaw sa tibuok document para bisag unsang dynamic buttons, madakpan.
/*document.addEventListener('click', function(e) {
    // 1. Para sa ADD Button (dapat ang button sa menu.php naay id="addNewMenuBtn")
    if (e.target && e.target.id === 'addNewMenuBtn') {
        openMenuModal();
    }

    // 2. Para sa EDIT Button (Gigamitan og class para sa delegation)
    const editBtn = e.target.closest('.edit-menu-btn');
    if (editBtn) {
        const id = editBtn.getAttribute('data-id');
        editMenu(id);
    }

    // 3. Para sa CLOSE Modal
    if (e.target.classList.contains('close-modal') || e.target.id === 'menuModal') {
        closeMenuModal();
    }
});

// Para sa SEARCH ug FILTER
document.addEventListener('input', function(e) {
    if (e.target.id === 'searchMenu' || e.target.id === 'categoryFilter') {
        renderMenuCards();
    }
});

// Para sa FORM SUBMISSION
document.addEventListener('submit', function(e) {
    if (e.target.id === 'menuForm') {
        e.preventDefault();
        saveMenu();
    }
}); */

/* ==========================================
   2. EVENT DELEGATION (The SPA Fix - Anti Duplicate)
   ========================================== */
// KINI ANG GUARD: I-check kung nabutang na ba ang mga listener
/* ==========================================
   2. EVENT DELEGATION (The SPA Fix - Anti Duplicate)
   ========================================== */
// KINI ANG GUARD: I-check kung nabutang na ba ang mga listener
if (!window.menuEventsAdded) {
    
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'addNewMenuBtn') {
            openMenuModal();
        }

        const editBtn = e.target.closest('.edit-menu-btn');
        if (editBtn) {
            const id = editBtn.getAttribute('data-id');
            editMenu(id);
        }

        // 3. TOGGLE AVAILABILITY Button (KINI ANG BAG-O)
        /*const toggleBtn = e.target.closest('.toggle-menu-btn');
        if (toggleBtn) {
            const id = toggleBtn.getAttribute('data-id');
            const status = toggleBtn.getAttribute('data-status');
            toggleMenuStatus(id, status);
        }*/

        // 3. Para sa CLOSE Modal (Bulletproof Fix)
        if (e.target.closest('.modal-close') || e.target.closest('.close-modal') || e.target.id === 'menuModal') {
            closeMenuModal(); // <--- KINI ANG SULOD, DILI '...'
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.id === 'searchMenu' || e.target.id === 'categoryFilter') {
            renderMenuCards();
        }
    });

    document.addEventListener('submit', function(e) {
        if (e.target.id === 'menuForm') {
            e.preventDefault();
            saveMenu();
        }
    });

    // Markahan nato nga nabutang na, para dili mag-doble inig balik sa page
    window.menuEventsAdded = true; 
}

/* ==========================================
   3. DATA FETCHING & RENDERING
   ========================================== */
/*function loadMenu() {
    fetch('../../api/admin/get_menu.php?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        allMenuItems = data; 
        renderMenuCards();  
    })
    .catch(error => console.error('Error fetching menu:', error));
} */

/* ==========================================
   3. DATA FETCHING & RENDERING (UPDATED)
   ========================================== */
function loadMenu() {
    fetch('../../api/admin/get_menu.php?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        allMenuItems = data; 
        renderMenuCards();  
        
        // 📍 PUGSON NATO OG TAWAG ANG DROPDOWN KADA LOAD SA MENU!
        if (typeof window.loadMenuDropdowns === 'function') {
            window.loadMenuDropdowns();
        }
    })
    .catch(error => console.error('Error fetching menu:', error));
}

function renderMenuCards() {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;

    const searchTerm = document.getElementById('searchMenu')?.value.toLowerCase() || '';
    const selectedCat = document.getElementById('categoryFilter')?.value || 'all';
    
    grid.innerHTML = ''; 

    const filteredItems = allMenuItems.filter(item => {
        const name = (item.product_name || '').toLowerCase();
        const matchText = name.includes(searchTerm);
        const matchCat = (selectedCat === 'all') || (item.category_id == selectedCat);
        return matchText && matchCat;
    });

    if (filteredItems.length === 0) {
        grid.innerHTML = '<p style="text-align:center; width:100%; padding:20px; color:#666;">Walay nakit-an nga menu item.</p>';
        return;
    }

    filteredItems.forEach(item => {
        // LOGIC PARA SA DUHA KA PRESYO
        const pMed = parseFloat(item.price || 0);
        const pLrg = parseFloat(item.price_large || 0);
        let priceDisplay = '';

        if (pMed > 0 && pLrg > 0) {
            priceDisplay = `M: ₱${pMed.toFixed(2)} | L: ₱${pLrg.toFixed(2)}`;
        } else if (pLrg > 0 && pMed === 0) {
            priceDisplay = `L: ₱${pLrg.toFixed(2)}`;
        } else {
            priceDisplay = `₱${pMed.toFixed(2)}`;
        }
        const imgSrc = item.image ? `../../${item.image}` : '../../assets/img/placeholder.png';
        const isAvailable = parseInt(item.is_available) === 1;

        // KINI ANG BAG-ONG DESIGN (Naay Toggle Switch ug Icon)
        grid.innerHTML += `
            <div class="menu-card" style="background: white; border-radius: 15px; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; position: relative; ${!isAvailable ? 'opacity: 0.6;' : ''}">
                <div style="position: relative; height: 180px; background: #f9f9f9; border-radius: 10px; margin-bottom: 15px; overflow: hidden;">
                    ${!isAvailable ? `<span style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; z-index:2;">UNAVAILABLE</span>` : ''}
                    <span style="position: absolute; top: 10px; right: 10px; background: #5A3E31; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; z-index:2;">${priceDisplay}</span>
                    <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h3 style="font-size: 16px; font-weight: bold; margin: 0;">${item.product_name}</h3>
                <p style="font-size: 12px; color: #777; margin-bottom: 15px; flex-grow: 1;">${item.recipe_text || 'No description'}</p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                    <button type="button" class="edit-menu-btn" data-id="${item.id}" title="Edit Item" style="background: none; border: none; color: #333; cursor: pointer; transition: 0.2s; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 5px; padding: 0;" onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#333'">
                        <i class="fa fa-edit"></i>
                    </button>
                    
                    <label class="switch" title="${isAvailable ? 'Click to Disable' : 'Click to Enable'}" style="margin: 0;">
                        <input type="checkbox" ${isAvailable ? 'checked' : ''} onchange="window.toggleMenuStatus(${item.id}, ${item.is_available}, this)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>`;
    });
}

/* ==========================================
   4. MODAL & SAVE LOGIC
   ========================================== */
/* ==========================================
   MODAL & SAVE LOGIC (Gi-ayo para mawala ang 'localhost says')
   ========================================== */
/* ==========================================
   MODAL & SAVE LOGIC (Confirm-First Toggle Fix)
   ========================================== */
window.toggleMenuStatus = function(id, currentStatus, checkboxElement) {
    // 1. I-PREVENT ANG DEFAULT SLIDE: Ibalik dayon ang switch sa daan niyang pwesto usa pa ta mangutana
    const isCurrentlyActive = (parseInt(currentStatus) === 1);
    checkboxElement.checked = isCurrentlyActive; 

    // 2. Karon mangutana na ta (wala pa mo-slide ang switch)
    const newStatus = isCurrentlyActive ? 0 : 1; 
    const actionText = newStatus === 1 ? "ENABLE" : "DISABLE";

    if (typeof window.showCustomConfirm === 'function') {
        window.showCustomConfirm(
            `Sigurado ka nga gusto nimo i-${actionText} kini nga item?`, 
            function() {
                // KUNG MO CLICK OG "YES" (Continue)
                const formData = new FormData();
                formData.append('id', id);
                formData.append('is_available', newStatus);

                // Karon nato tinuoron og pa-slide ang switch kay ni-confirm na ang user
                checkboxElement.checked = !isCurrentlyActive; 

                fetch('../../api/admin/update_menu_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.showCustomAlert(`Item is now ${newStatus === 1 ? 'Available' : 'Unavailable'}!`, "success");
                        loadMenu(); 
                    } else {
                        window.showCustomAlert("Database Error: " + (data.message || data.error), "error");
                        // Kung error sa database, ibalik sa daan nga pwesto ang switch
                        checkboxElement.checked = isCurrentlyActive;
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    window.showCustomAlert("Dili ma-contact ang server.", "error");
                    // Kung error ang internet, ibalik sa daan nga pwesto
                    checkboxElement.checked = isCurrentlyActive;
                });
            },
            function() {
                // KUNG MO CLICK OG "CANCEL" 
                // Wala na tay buhaton kay gipugngan naman nato ang pag-slide sa Step 1 pa lang!
                console.log("Toggle action cancelled by user.");
            }
        );
    }
}

function saveMenu() {
    const form = document.getElementById('menuForm');
    const formData = new FormData(form);

    // Apilon nato ang image kung naay gi-upload
    const imageInput = document.getElementById('image');
    if (imageInput && imageInput.files.length > 0) {
        formData.append('image', imageInput.files[0]);
    }

    fetch('../../api/admin/save_menu.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // KINI ANG FIX: Gigamit ang showCustomAlert imbes nga alert()
            showCustomAlert("Nasa-save na ang item!", "success");
            closeMenuModal();
            loadMenu(); 
        } else {
            // Kung naay error, custom alert gihapon
            showCustomAlert("Error: " + (data.error || data.message), "error");
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        showCustomAlert("Server error. Check console.", "error");
    });
}

function editMenu(id) {
    const item = allMenuItems.find(i => i.id == id);
    if (item) {
        document.getElementById('item_id').value = item.id;
        document.getElementById('product_name').value = item.product_name;
        document.getElementById('category_id').value = item.category_id;
        document.getElementById('price').value = item.price;
        const plInput = document.getElementById('price_large');
if(plInput) plInput.value = item.price_large !== null ? item.price_large : '';
        document.getElementById('recipe_text').value = item.recipe_text || '';
        
        const statusDropdown = document.getElementById('is_available');
        if(statusDropdown) statusDropdown.value = item.is_available;
        
        const modalTitle = document.querySelector('#menuModal h2');
        if(modalTitle) modalTitle.innerText = "Edit Menu Item";

        document.getElementById('menuModal').style.display = 'flex';
    }
}

function openMenuModal() {
    const form = document.getElementById('menuForm');
    if(form) form.reset();
    document.getElementById('item_id').value = ''; 
    
    const modalTitle = document.querySelector('#menuModal h2');
    if(modalTitle) modalTitle.innerText = "Add New Menu Item";
    const plInput = document.getElementById('price_large');
if(plInput) plInput.value = '';

    document.getElementById('menuModal').style.display = 'flex';
}

// KINI ANG BULLETPROOF CLOSE FUNCTION
window.closeMenuModal = function() {
    const modal = document.getElementById('menuModal');
    if (modal) {
        modal.style.display = 'none';
        // I-reset ang porma para inig abli sunod, limpyo
        const form = document.getElementById('menuForm');
        if (form) form.reset();
    }
};
/* ==========================================
   5. SMART SPA ROUTER
   ========================================== */
document.addEventListener('click', function(e) {
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
            // 1. I-update ang Main Content
            main.innerHTML = doc.querySelector('.main-content').innerHTML;
            
            // 2. KINI ANG SIKRETO NGA NAWALA: I-kopya ang mga Modals!
            const modalIds = ['userModal', 'editModal', 'menuModal']; 
            modalIds.forEach(id => {
                const oldModal = document.getElementById(id);
                if (oldModal) oldModal.remove();
                
                const newModal = doc.getElementById(id);
                if (newModal) document.body.appendChild(newModal);
            });

            // 3. I-dagan ang mga Scripts
            doc.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(a => newScript.setAttribute(a.name, a.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                document.body.appendChild(newScript).parentNode.removeChild(newScript);
            });
        }

        /* ==========================================
   5. GLOBAL ALERTS PARA SA MENU
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
};

window.showCustomConfirm = function(message, onConfirm) {
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
    document.getElementById('cnclBtn').onclick = () => overlay.remove();
    document.getElementById('okBtn').onclick = () => { overlay.remove(); onConfirm(); };
};
        
        window.history.pushState({}, '', url);
        
        // 4. Tawgon ang mga functions pagkahuman og load
        // 4. Tawgon ang mga functions pagkahuman og load
setTimeout(() => {
    if (url.includes('users.php')) loadUsers();
    if (url.includes('menu.php')) { 
        if(typeof loadMenu === 'function') loadMenu(); 
        if(typeof window.loadMenuDropdowns === 'function') window.loadMenuDropdowns(); // 📍 KINI ANG GIDUGANG!
    }
    if (url.includes('system_logs.php')) loadAuditLogs();
}, 100);
    });
});

// Function para i-load ang categories didto sa duha ka dropdown sa Menu page
/* ==========================================
   DROPDOWN LOADER WITH DEBUGGER (UPDATED)
   ========================================== */
// BULLETPROOF DROPDOWN LOADER
window.loadMenuDropdowns = function() {
    console.log("🟡 Nag-start loadMenuDropdowns...");

    fetch('../../api/admin/get_categories.php?t=' + Date.now())
    .then(res => res.text()) // Kuhaon as text para safe
    .then(text => {
        console.log("🟢 Raw Categories Data:", text); // Makita nato ang sulod sa console
        try {
            const data = JSON.parse(text); 

            // Gamiton nato querySelectorAll para makuha niya bisag naay SPA duplicates
            const filterDropdowns = document.querySelectorAll('#categoryFilter');
            const modalDropdowns = document.querySelectorAll('#category_id');

            // I-limpyo ang tanang nakit-an nga dropdowns
            filterDropdowns.forEach(dropdown => {
                dropdown.innerHTML = '<option value="all">All Categories</option>';
            });
            modalDropdowns.forEach(dropdown => {
                dropdown.innerHTML = '<option value="" disabled selected>Select a Category...</option>';
            });

            // I-check kung array ba ang gibalik sa PHP
            if (Array.isArray(data)) {
                data.forEach(cat => {
                    if (cat.status == 1) { // Kung Active lang
                        const catName = cat.name || cat.category_name || "Unknown";
                        const optionHTML = `<option value="${cat.id}">${catName}</option>`;
                        
                        // I-sulod ang option sa TANAANG dropdown nga nakit-an
                        filterDropdowns.forEach(dropdown => dropdown.innerHTML += optionHTML);
                        modalDropdowns.forEach(dropdown => dropdown.innerHTML += optionHTML);
                    }
                });
                console.log("✅ Na-pasulod na ang mga Categories!");
            } else {
                console.error("🔴 PHP Wala nag-return og Array:", data);
            }
        } catch (e) {
            console.error("🔴 JSON Error sa Dropdown:", e);
        }
    })
    .catch(err => console.error("🔴 Fetch connection error:", err));
};

// 📍 SIGURADUHON NGA MATAWAG INIG REFRESH SA PAGE (F5)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.loadMenuDropdowns === 'function') {
        window.loadMenuDropdowns();
    }
});