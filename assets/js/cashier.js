let cart = [];
let menuItems = [];
let currentCategory = 'All';
let currentType = 'All';

// ==========================================
// 1. CLOCK FUNCTION
// ==========================================
function updateClock() {
    const clockElement = document.getElementById('clock');
    if (clockElement) {
        const now = new Date();
        clockElement.innerHTML = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
}
setInterval(updateClock, 1000);
updateClock();

// ==========================================
// 2. INITIALIZE (Gi-update para sa Dropdown)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    loadPOSMenu();

    // Setup Search Listener
    const searchInput = document.getElementById('menuSearch');
    if (searchInput) {
        searchInput.addEventListener('input', renderMenu);
    }

    // 📍 BAG-O: Listener para sa Type Filter (Dropdown)
    const typeFilter = document.getElementById('typeFilterSelect');
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            currentType = this.value; // Kuhaon ang "All", "Hot", o "Iced"
            renderMenu();             // I-refresh ang menu display
        });
    }
});

// ==========================================
// 3. FETCH MENU FOR CASHIER
// ==========================================
async function loadPOSMenu() {
    const display = document.getElementById('posMenuDisplay');
    if (display) {
        display.innerHTML = '<p style="grid-column: 1/-1; text-align:center; padding: 20px; color:#888;"><i class="fa fa-spinner fa-spin"></i> Nag-kuha sa menu... Palihug hulat.</p>';
    }

    try {
        const response = await fetch('../../api/admin/get_menu.php');
        
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
        
        const text = await response.text();
        
        try {
            const data = JSON.parse(text);
            
            if (data.error) {
                display.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:red; padding: 20px;"><b>API Error:</b> ${data.error}</p>`;
                return;
            }
            
            menuItems = data;
            renderMenu();
        } catch (e) {
            console.error("RAW PHP ERROR (Menu):", text);
            display.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:red; padding: 20px;"><b>PHP Error:</b> Gi-block sa server o naay sayop sa PHP. Tan-awa ang Console (F12).</p>';
        }
    } catch (err) {
        console.error("Fetch Error:", err);
        display.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:red; padding: 20px;"><b>Connection Error:</b> Dili makita ang get_menu.php.</p>';
    }
}

// ==========================================
// 4. RENDER MENU CARDS
// ==========================================
function renderMenu() {
    const display = document.getElementById('posMenuDisplay');
    const searchInput = document.getElementById('menuSearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    
    if (!display) return;
    display.innerHTML = '';

    const filtered = menuItems.filter(item => {
        // I-filter out daan ang mga Unavailable (is_available == 0)
        if (parseInt(item.is_available) === 0) return false;

        const name = item.product_name.toLowerCase();
        const matchesSearch = name.includes(searchTerm);
        const matchesCat = currentCategory === 'All' || item.category_id == currentCategory;
        
        let matchesType = true;
        if (currentType === 'Hot') matchesType = name.includes('hot');
        if (currentType === 'Iced') matchesType = name.includes('iced');

        return matchesSearch && matchesCat && matchesType;
    });

    if (filtered.length === 0) {
        display.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#888; margin-top: 50px;">No items found matching your filters.</p>';
        return;
    }

    filtered.forEach(item => {
        const pMed = parseFloat(item.price || 0);
        const pLrg = parseFloat(item.price_large || 0);
        let priceDisplay = '';

        if (pMed > 0 && pLrg > 0) {
            priceDisplay = `M: ₱${pMed.toFixed(2)} | L: ₱${pLrg.toFixed(2)}`;
        } else if (pLrg > 0 && pMed <= 0) {
            priceDisplay = `L: ₱${pLrg.toFixed(2)}`;
        } else {
            priceDisplay = `₱${pMed.toFixed(2)}`;
        }

        const imgSrc = item.image && item.image !== '' ? `../../${item.image}` : '../../assets/img/placeholder.png';

        let catName = 'COFFEE';
        if(item.category_id == 2) catName = 'NON-COFFEE';
        if(item.category_id == 3) catName = 'BARISTA DRINKS';

        const cardHTML = `
            <div class="product-card" 
                 onclick="handleItemClick(${item.id})" 
                 style="background: white; border-radius: 15px; padding: 15px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.03); position: relative; display: flex; flex-direction: column; transition: transform 0.2s;" 
                 onmouseover="this.style.transform='translateY(-3px)'" 
                 onmouseout="this.style.transform='translateY(0)'">
                
                <div style="position: absolute; top: 10px; right: 10px; background: rgba(78, 55, 44, 0.85); color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold; font-size: 11px; z-index: 2;">${priceDisplay}</div>
                
                <div style="height: 130px; background: #f9f9f9; border-radius: 10px; margin-bottom: 12px; overflow: hidden; display:flex; align-items:center; justify-content:center;">
                    <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=No+Image';">
                </div>
                
                <h4 style="margin: 0 0 4px 0; font-size: 15px; color: #333; font-weight: 700;">${item.product_name}</h4>
                <span style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 0.5px;">${catName}</span>
            </div>
        `;
        display.innerHTML += cardHTML;
    });
}

// ==========================================
// 4.5. SIZE MODAL LOGIC
// ==========================================
function handleItemClick(id) {
    const item = menuItems.find(i => i.id == id);
    if (!item) return;

    const pMed = parseFloat(item.price || 0);
    const pLrg = parseFloat(item.price_large || 0);

    if (pMed > 0 && pLrg <= 0) {
        addToCart(item.id, item.product_name, pMed);
        return;
    } else if (pLrg > 0 && pMed <= 0) {
        addToCart(item.id + '_L', item.product_name + ' (Large)', pLrg);
        return;
    }

    document.getElementById('modalProductName').innerText = item.product_name;
    
    const btnMed = document.getElementById('btnMedium');
    const btnLrg = document.getElementById('btnLarge');

    if (pMed > 0) {
        btnMed.style.display = 'block';
        document.getElementById('priceMedium').innerText = `₱${pMed.toFixed(2)}`;
        btnMed.onclick = () => {
            addToCart(item.id, item.product_name, pMed);
            document.getElementById('sizeModal').style.display = 'none';
        };
    } else {
        btnMed.style.display = 'none';
    }

    if (pLrg > 0) {
        btnLrg.style.display = 'block';
        document.getElementById('priceLarge').innerText = `₱${pLrg.toFixed(2)}`;
        btnLrg.onclick = () => {
            addToCart(item.id + '_L', item.product_name + ' (Large)', pLrg);
            document.getElementById('sizeModal').style.display = 'none';
        };
    } else {
        btnLrg.style.display = 'none';
    }

    document.getElementById('sizeModal').style.display = 'flex';
}

// ==========================================
// 5. CART LOGIC
// ==========================================
function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        existingItem.qty++;
    } else {
        cart.push({ id, name, price, qty: 1, notes: '' });
    }
    renderCart();
}

function updateQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) cart.splice(index, 1);
    renderCart();
}

function renderCart() {
    const cartDisplay = document.getElementById('cartDisplay');
    if (!cartDisplay) return;

    if (cart.length === 0) {
        cartDisplay.innerHTML = '<div class="empty-state" style="text-align:center; color:#ccc; margin-top:50px;"><p>Cart is empty</p></div>';
        updateTotals();
        return;
    }

    let html = '';
    cart.forEach((item, index) => {
        let noteDisplay = (item.notes && item.notes.trim() !== '') 
            ? `<div style="font-size: 11px; color: #e74c3c; margin-top: 3px; font-style: italic;">
                   <i class="fa fa-info-circle"></i> Note: ${item.notes}
               </div>` 
            : '';

        html += `
        <div class="cart-item" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 12px 0;">
            <div class="cart-item-info" style="flex: 1;">
                <h4 style="margin: 0 0 5px 0; font-size: 14px; color: #333;">${item.name}</h4>
                <div class="price" style="color: #888; font-size: 12px;">₱${(item.price * item.qty).toFixed(2)}</div>
                ${noteDisplay} 
                <button onclick="addNoteToItem(${index})" style="background: none; border: none; color: #3498db; cursor: pointer; font-size: 11px; padding: 0; margin-top: 4px;">
                    <i class="fa fa-edit"></i> Add Note
                </button>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="qty-controls" style="display: flex; align-items: center; gap: 5px; background: #f4ece6; padding: 4px 8px; border-radius: 15px;">
                    <button onclick="updateQty(${index}, -1)" style="border:none; background:none; cursor:pointer; font-weight:bold; color:#4E372C; padding: 0 8px;">-</button>
                    <input type="text" value="${item.qty}" oninput="setExactQty(${index}, this.value, this)" onfocus="this.select()" 
                           style="width: 35px; text-align: center; background: transparent; border: none; border-bottom: 1px solid transparent; font-size: 13px; font-weight: bold; color: #333; outline: none; transition: 0.2s;"
                           onmouseover="this.style.borderBottom='1px solid #aaa'" onmouseout="this.style.borderBottom='1px solid transparent'">
                    <button onclick="updateQty(${index}, 1)" style="border:none; background:none; cursor:pointer; font-weight:bold; color:#4E372C; padding: 0 8px;">+</button>
                </div>
                <button onclick="removeItem(${index})" style="background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 16px; padding: 5px; transition: 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Remove Item">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>`;
    });
    
    cartDisplay.innerHTML = html;
    updateTotals();
}

let currentNoteIndex = null;

function addNoteToItem(index) {
    currentNoteIndex = index;
    let currentNote = cart[index].notes || ''; 
    document.getElementById('noteInput').value = currentNote;
    document.getElementById('noteModal').style.display = 'flex';
    setTimeout(() => document.getElementById('noteInput').focus(), 100);
}

function closeNoteModal() {
    document.getElementById('noteModal').style.display = 'none';
    currentNoteIndex = null;
}

function saveNote() {
    if (currentNoteIndex !== null) {
        let noteValue = document.getElementById('noteInput').value;
        cart[currentNoteIndex].notes = noteValue;
        renderCart(); 
    }
    closeNoteModal();
}

let currentSubtotal = 0;
let currentDiscount = 0;
let currentTax = 0;
let currentTotal = 0;

function updateTotals() {
    currentSubtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
    
    const discountPercent = parseFloat(document.getElementById('discountSelect').value);
    currentDiscount = currentSubtotal * (discountPercent / 100);
    
    currentTotal = currentSubtotal - currentDiscount; 
    const vatableAmount = currentTotal / 1.12;        
    currentTax = currentTotal - vatableAmount;        
    
    document.getElementById('subtotal').innerText = `₱${currentSubtotal.toFixed(2)}`;
    document.getElementById('discountAmt').innerText = `- ₱${currentDiscount.toFixed(2)}`;
    document.getElementById('taxAmt').innerText = `₱${currentTax.toFixed(2)}`;
    document.getElementById('total').innerText = `₱${currentTotal.toFixed(2)}`;
    document.getElementById('itemCount').innerText = `${totalItems} items added`;
}

// ==========================================
// 6. CHECKOUT LOGIC (WITH PAYMONGO INTEGRATION)
// ==========================================
let pendingOrderData = {}; 

function showAlertModal(msg) {
    document.getElementById('alertModalMessage').innerText = msg;
    document.getElementById('alertModal').style.display = 'flex';
}

function processCheckout() {
    if (cart.length === 0) {
        return showAlertModal("Empty cart! Please select items first.");
    }

    const paymentRadio = document.querySelector('input[name="payment"]:checked');
    const paymentMethod = paymentRadio ? paymentRadio.value : 'Cash';
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const discountSelect = document.getElementById('discountSelect');
    const discountPercent = discountSelect ? parseFloat(discountSelect.value) : 0;
    const discountAmount = subtotal * (discountPercent / 100);
    
    const finalTotal = subtotal - discountAmount;
    const vatableAmount = finalTotal / 1.12;
    const taxAmount = finalTotal - vatableAmount;

    pendingOrderData = { finalTotal, subtotal, discountAmount, taxAmount, paymentMethod };

    if (paymentMethod === 'Cash') {
        document.getElementById('paymentModalTotal').innerText = `₱${finalTotal.toFixed(2)}`;
        const cashInput = document.getElementById('cashTenderedInput');
        cashInput.value = finalTotal.toFixed(2);
        
        document.getElementById('paymentModal').style.display = 'flex';
        setTimeout(() => cashInput.select(), 100); 
        
    } else if (paymentMethod === 'GCash' || paymentMethod === 'Card') {
        document.getElementById('digitalTotal').innerText = `₱${finalTotal.toFixed(2)}`;
        const digitalTitle = document.getElementById('digitalTitle');
        const qrContainer = document.getElementById('qrContainer');
        const btnVerify = document.getElementById('btnVerifyDigital');
        
        digitalTitle.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating PayMongo Link...';
        digitalTitle.style.color = '#1fb6ff'; 
        qrContainer.innerHTML = '<p style="margin-top:50px; color:#888;">Connecting to API...</p>';
        btnVerify.style.background = '#1fb6ff';
        
        document.getElementById('refNumberInput').value = ''; 
        document.getElementById('digitalPaymentModal').style.display = 'flex';

        fetch('../../api/cashier/create_paymongo_link.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ total: finalTotal })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                digitalTitle.innerHTML = paymentMethod === 'GCash' ? '<i class="fa fa-mobile-screen"></i> Scan to Pay (GCash)' : '<i class="fa fa-credit-card"></i> Scan to Pay (Card)';
                
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(data.checkout_url)}`;
                qrContainer.innerHTML = `<img src="${qrUrl}" style="width: 100%; height: 100%; object-fit: contain;">
                                         <a href="${data.checkout_url}" target="_blank" style="display:block; margin-top:10px; font-size:11px; color:#1fb6ff; text-decoration:none;">Open Checkout Link</a>`;
                
                document.getElementById('refNumberInput').value = data.reference_number;
            } else {
                qrContainer.innerHTML = `<p style="color:red; margin-top:50px;">API Error.</p>`;
                digitalTitle.innerHTML = "Payment Failed";
                console.error(data.message);
            }
        })
        .catch(err => {
            console.error("PayMongo Fetch Error:", err);
            qrContainer.innerHTML = `<p style="color:red; margin-top:50px;">Connection Error.</p>`;
        });
    }
}

function verifyDigitalPayment() {
    const refNum = document.getElementById('refNumberInput').value.trim();
    
    if (refNum === '') {
        showAlertModal("Palihug i-type ang Reference Number gikan sa PayMongo!");
        return;
    }

    const btnVerify = document.getElementById('btnVerifyDigital');
    const originalText = btnVerify.innerHTML;
    btnVerify.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving Order...';
    btnVerify.disabled = true;

    setTimeout(() => {
        document.getElementById('digitalPaymentModal').style.display = 'none';
        btnVerify.innerHTML = originalText;
        btnVerify.disabled = false;
        
        finalizeCheckout(pendingOrderData.finalTotal);
    }, 1000);
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function confirmCashPayment() {
    const cashInputVal = parseFloat(document.getElementById('cashTenderedInput').value);
    const finalTotal = pendingOrderData.finalTotal;

    if (isNaN(cashInputVal) || cashInputVal < finalTotal) {
        showAlertModal("Invalid amount! Ang gibayad kay kulang.");
        return;
    }

    closePaymentModal();
    finalizeCheckout(cashInputVal);
}

async function finalizeCheckout(cashTendered) {
    const { finalTotal, subtotal, discountAmount, taxAmount, paymentMethod } = pendingOrderData;
    const receiptNo = 'REC-' + (Math.floor(Math.random() * 90000) + 10000);

    const orderData = {
        receipt_no: receiptNo,
        payment_method: paymentMethod,
        total: finalTotal, 
        cash_tendered: cashTendered,
        items: cart
    };

    try {
        const response = await fetch('../../api/cashier/create_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        
        const text = await response.text();

        try {
            const data = JSON.parse(text); 

            if (data.success) {
                showReceipt(finalTotal, cashTendered, paymentMethod, receiptNo, subtotal, discountAmount, taxAmount);
            } else {
                showAlertModal("Error saving order: " + data.message);
            }
        } catch (jsonErr) {
            console.error("RAW PHP ERROR SA CHECKOUT:", text);
            showAlertModal("PHP Error: Palihug pislita ang F12 unya adto sa Console aron makita ang tinuod nga error.");
        }
        
    } catch (err) {
        console.error("Fetch Checkout Error:", err);
        showAlertModal("Network Error: Wala makita ang create_order.php file sa server.");
    }
}

// ==========================================
// 7. SHOW RECEIPT FUNCTION
// ==========================================
function showReceipt(total, cash, method, receiptNo, subtotal, discount, tax) {
    document.getElementById('receiptModal').style.display = 'flex';
    
    document.getElementById('recNo').innerText = receiptNo;
    document.getElementById('recDate').innerText = new Date().toLocaleString();
    
    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                <span>${item.qty}x ${item.name}</span>
                <span>₱${(item.price * item.qty).toFixed(2)}</span>
            </div>
        `;
    });
    document.getElementById('recItems').innerHTML = itemsHtml;
    
    document.getElementById('recSubtotal').innerText = `₱${subtotal.toFixed(2)}`;
    document.getElementById('recTax').innerText = `₱${tax.toFixed(2)}`;
    
    if (discount > 0) {
        document.getElementById('recDiscountContainer').style.display = 'flex';
        document.getElementById('recDiscount').innerText = `- ₱${discount.toFixed(2)}`;
    } else {
        document.getElementById('recDiscountContainer').style.display = 'none';
    }

    document.getElementById('recTotal').innerText = `₱${total.toFixed(2)}`;
    document.getElementById('recMethod').innerText = method;
    document.getElementById('recPayment').innerText = `₱${cash.toFixed(2)}`;
    
    if (method === 'Cash') {
        let change = cash - total;
        document.getElementById('recChangeContainer').style.display = 'flex';
        document.getElementById('recChange').innerText = `₱${change.toFixed(2)}`;
    } else {
        document.getElementById('recChangeContainer').style.display = 'none';
    }
    document.getElementById('btnCloseReceiptBtn').innerText = 'New Order';
}

function closeReceipt() {
    document.getElementById('receiptModal').style.display = 'none';
    cart = []; 
    const discountSelect = document.getElementById('discountSelect');
    if (discountSelect) discountSelect.value = "0";
    const cashRadio = document.querySelector('input[name="payment"][value="Cash"]');
    if (cashRadio) cashRadio.checked = true;
    renderCart();
}

function printReceipt() {
    window.print();
}

// ==========================================
// 8. ORDER HISTORY LOGIC
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const historyTable = document.getElementById('historyTableBody');
    if (historyTable) {
        loadHistory();
        const searchInput = document.getElementById('historySearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('#historyTableBody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    }
});

async function loadHistory() {
    const tbody = document.getElementById('historyTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px;"><i class="fa fa-spinner fa-spin"></i> Gakuha sa data... Palihug hulat.</td></tr>';

    try {
        const response = await fetch('../../api/cashier/get_history.php');
        
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const text = await response.text(); 
        
        try {
            const result = JSON.parse(text); 
            
            tbody.innerHTML = '';
            
            if (result.success && result.data.length > 0) {
                result.data.forEach(sale => {
                    const total = parseFloat(sale.total_amount).toFixed(2);
                    const dateObj = new Date(sale.sale_date);
                    const timeStr = dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const dateStr = dateObj.toLocaleDateString();

                    const isVoided = sale.status === 'Voided';
                    let statusBadge = '';
                    
                    if (sale.status === 'Voided') {
                        statusBadge = '<span style="background: #ffebee; color: #c62828; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold;">VOIDED</span>';
                    } else if (sale.status === 'Pending') {
                        statusBadge = '<span style="background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold;">PENDING</span>';
                    } else if (sale.status === 'Preparing') {
                        statusBadge = '<span style="background: #cce5ff; color: #004085; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold;">PREPARING</span>';
                    } else {
                        statusBadge = '<span style="background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold;">COMPLETED</span>';
                    }

                    const actionButtons = isVoided
                        ? `<button onclick="viewReceipt('${sale.receipt_no}')" style="background: white; border: 1px solid #ddd; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: #4E372C; font-weight: bold; transition: 0.2s;"><i class="fa fa-eye"></i> View</button>`
                        : `<button onclick="viewReceipt('${sale.receipt_no}')" style="background: white; border: 1px solid #ddd; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: #4E372C; font-weight: bold; transition: 0.2s;"><i class="fa fa-eye"></i> View</button>
                           <button onclick="promptVoid('${sale.receipt_no}')" style="background: #ffebee; border: 1px solid #ffcdd2; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: #c62828; font-weight: bold; transition: 0.2s; margin-left: 5px;"><i class="fa fa-ban"></i> Void</button>`;

                    const rowStyle = isVoided ? "opacity: 0.6; background: #fdfdfd;" : "transition: 0.2s;";
                    const priceStyle = isVoided ? "text-decoration: line-through; color: #999;" : "color: #333;";

                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid #eee; ${rowStyle}" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 15px; font-weight: bold; color: #4E372C;">${sale.receipt_no} <br>${statusBadge}</td>
                            <td style="padding: 15px; color: #666;">${dateStr} <span style="font-size:12px; color:#aaa;">${timeStr}</span></td>
                            <td style="padding: 15px; font-weight: bold; ${priceStyle}">₱${total}</td>
                            <td style="padding: 15px; text-align: center;">
                                ${actionButtons}
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #888;">Walay nakit-an nga resibo.</td></tr>';
            }
        } catch (jsonErr) {
            console.error("RAW PHP ERROR OUTPUT:", text);
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: red; padding: 20px;"><b>Error sa PHP File:</b> Palihug press F12 ug tan-awa ang Console.</td></tr>';
        }

    } catch (err) {
        console.error("Network Error:", err);
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color: red; padding: 20px;"><b>Connection Error:</b> Dili makita ang get_history.php file.</td></tr>`;
    }
}

function setExactQty(index, value, element) {
    let newQty = parseInt(value);
    if (isNaN(newQty) || newQty < 0) return; 
    if (newQty === 0) {
        removeItem(index);
        return;
    }
    cart[index].qty = newQty;
    const priceDisplay = element.closest('.cart-item').querySelector('.price');
    priceDisplay.innerText = '₱' + (cart[index].price * newQty).toFixed(2);
    updateTotals();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

// BULLETPROOF VIEW RECEIPT PARA SA CASHIER
async function viewReceipt(receiptNo) {
    try {
        // 1. CACHE BUSTER: Idugang nato ang time karon aron dili mo-gamit og 'daan' nga data ang Chrome
        const cacheBuster = new Date().getTime();
        const response = await fetch(`../../api/cashier/get_receipt.php?receipt_no=${receiptNo}&_t=${cacheBuster}`);
        const result = await response.json();

        if (result.success) {
            const sale = result.sale;
            const items = result.items || [];

            // 2. SAFE CHECKS: Function aron dili mo-crash kung naay nakalimtan nga 'id' sa HTML
            const safeSetText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };

            safeSetText('recNo', sale.receipt_no);
            safeSetText('recDate', sale.sale_date);

            // 3. Status Update (Safe)
            const currentStatus = sale.status ? sale.status : 'COMPLETED';
            const statusElem = document.getElementById('recStatus');
            if (statusElem) {
                statusElem.innerText = "STATUS: " + currentStatus.toUpperCase();
                statusElem.style.color = currentStatus === 'Voided' ? '#c62828' : '#2ecc71';
                
                // Void Reason
                const oldReason = document.getElementById('voidReasonDisplay');
                if (oldReason) oldReason.remove();
                
                if (currentStatus === 'Voided' && result.void_reason) {
                    statusElem.insertAdjacentHTML('afterend', `<p id="voidReasonDisplay" style="margin: 5px 0 0 0; font-size: 11px; color: #c62828; font-style: italic;">Reason: ${result.void_reason}</p>`);
                }
            }

            // 4. Items Update
            let itemsHtml = '';
            items.forEach(item => {
                const qty = parseInt(item.quantity || 1);
                const price = parseFloat(item.price || 0);
                itemsHtml += `
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                        <span>${qty}x ${item.product_name}</span>
                        <span>₱${(qty * price).toFixed(2)}</span>
                    </div>
                `;
            });
            const recItems = document.getElementById('recItems');
            if(recItems) recItems.innerHTML = itemsHtml;

            // 5. Totals Update
            const total = parseFloat(sale.total_amount || 0);
            const cash = parseFloat(sale.cash_tendered || 0);
            const change = parseFloat(sale.change_amount || 0);

            safeSetText('recTotal', `₱${total.toFixed(2)}`);
            safeSetText('recPayment', `₱${cash.toFixed(2)}`);
            safeSetText('recChange', `₱${change.toFixed(2)}`);
            safeSetText('recSubtotal', `₱${total.toFixed(2)}`); // Apil ang subtotal para sa Cashier Screen

            // 6. I-ABLI ANG MODAL
            const modal = document.getElementById('receiptModal');
            if (modal) modal.style.display = 'flex';

        } else {
            alert("API Error: " + result.message);
        }
    } catch (err) {
        console.error("View Receipt Error:", err);
        alert("Naay gamay nga error. Pislita ang F12 para makita sa console.");
    }
}

// ==========================================
// 9. DYNAMIC TAB SWITCHING
// ==========================================
function switchTab(tabId, element) {
    document.getElementById('orderTab').style.display = 'none';
    const historyTab = document.getElementById('historyTab');
    if (historyTab) historyTab.style.display = 'none';
    
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => link.classList.remove('active'));
    
    if (tabId === 'orderTab') {
        document.getElementById('orderTab').style.display = 'flex';
    } else {
        if (historyTab) {
            historyTab.style.display = 'flex';
            loadHistory(); 
        }
    }
    
    element.classList.add('active');
}

function setExactPayment() {
    if (pendingOrderData && pendingOrderData.finalTotal) {
        document.getElementById('cashTenderedInput').value = pendingOrderData.finalTotal.toFixed(2);
    }
}

/* ==========================================
   DYNAMIC CATEGORY LOADER
   ========================================== */
function loadActiveCategories() {
    fetch('../../api/cashier/get_active_categories.php')
        .then(response => {
            if (!response.ok) throw new Error("HTTP Error " + response.status);
            return response.json();
        })
        .then(data => {
            const container = document.getElementById('categoryFilters');
            if (!container) return;

            let html = `<button class="filter-btn active" data-cat="All">All</button>`;
            const icons = ['fa-coffee', 'fa-mug-hot', 'fa-star', 'fa-cookie', 'fa-ice-cream', 'fa-leaf'];

            data.forEach((cat, index) => {
                let icon = icons[index % icons.length];
                html += `<button class="filter-btn" data-cat="${cat.id}"><i class="fa ${icon}"></i> ${cat.category_name}</button>`;
            });

            container.innerHTML = html;
            attachCategoryClickEvents();
        })
        .catch(error => {
            console.error("Error loading categories:", error);
            const container = document.getElementById('categoryFilters');
            if (container) {
                container.innerHTML = `<button class="filter-btn active" style="background:#e74c3c; border-color:#e74c3c; color:white;">API Error. Pislita ang F12</button>`;
            }
        });
}

function attachCategoryClickEvents() {
    const filterBtns = document.querySelectorAll('#categoryFilters .filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            currentCategory = this.getAttribute('data-cat');
            renderMenu(); 
        });
    });
}

// I-load inig abli sa page
document.addEventListener('DOMContentLoaded', () => {
    loadActiveCategories();
});

// ... (ubang code sa taas)

// ==========================================
// 10. VOID ORDER LOGIC
// ==========================================
let currentReceiptToVoid = '';

// BAG-O: Function para mo-show/hide sa text box
function toggleOtherReason() {
    const select = document.getElementById('voidReasonSelect');
    const input = document.getElementById('otherReasonInput');
    if (select.value === 'Other') {
        input.style.display = 'block';
        input.focus(); // Ibutang diritso ang cursor para maka-type dayon
    } else {
        input.style.display = 'none';
        input.value = ''; // Limpyohan kung mo-change sa laing option
    }
}

function promptVoid(receiptNo) {
    currentReceiptToVoid = receiptNo;
    document.getElementById('voidReceiptText').innerText = receiptNo;
    document.getElementById('adminPinInput').value = ''; 
    document.getElementById('voidReasonSelect').value = ''; 
    
    // Tagoan daan ang text box inig abli sa modal
    const otherInput = document.getElementById('otherReasonInput');
    if(otherInput) {
        otherInput.style.display = 'none';
        otherInput.value = '';
    }
    
    document.getElementById('voidModal').style.display = 'flex';
    setTimeout(() => document.getElementById('adminPinInput').focus(), 100);
}

function closeVoidModal() {
    document.getElementById('voidModal').style.display = 'none';
    currentReceiptToVoid = '';
}

function submitVoidOrder() {
    const pin = document.getElementById('adminPinInput').value;
    let reason = document.getElementById('voidReasonSelect').value;

    if (pin.trim() === '') {
        showAlertModal("Palihug i-type ang Admin PIN.");
        return;
    }
    
    if (reason === '') {
        showAlertModal("Palihug pagpili og rason kung nganong i-void ni nga order.");
        return;
    }

    // 📍 BAG-O: Kung "Other", kuhaon nato ang gi-type sa cashier didto sa textbox
    if (reason === 'Other') {
        const otherReason = document.getElementById('otherReasonInput').value.trim();
        if (otherReason === '') {
            showAlertModal("Palihug i-type ang tinuod nga rason diha sa text box.");
            return;
        }
        // I-combine aron nindot basahon sa System Logs (ex. "Other: Nasuko si kustomer")
        reason = "Other: " + otherReason; 
    }

    const btn = document.getElementById('btnConfirmVoid');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    fetch('../../api/cashier/void_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receipt_no: currentReceiptToVoid, pin: pin, reason: reason })
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = 'Confirm Void';
        btn.disabled = false;

        if (data.success) {
            closeVoidModal();
            showAlertModal("Success! Na-void na ang order.");
            loadHistory(); 
        } else {
            showAlertModal("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = 'Confirm Void';
        btn.disabled = false;
        showAlertModal("Connection Error.");
    });
}