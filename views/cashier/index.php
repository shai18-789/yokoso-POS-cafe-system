<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cashier') {
    header("Location: ../../index.php");
    exit();
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier POS | Cafe System</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern Filter Buttons */
        .filter-btn { padding: 8px 16px; border-radius: 20px; border: 1px solid #d7ccc8; background: white; color: #555; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
        .filter-btn:hover { border-color: #e67e22; color: #e67e22; }
        .filter-btn.active { background: #e67e22; color: white; border-color: #e67e22; }
        
        .type-btn { padding: 6px 12px; border-radius: 15px; border: 1px solid #ddd; background: #f9f9f9; color: #666; cursor: pointer; font-size: 12px; transition: 0.2s; }
        .type-btn.active { background: #4E372C; color: white; border-color: #4E372C; }
    </style>
</head>
<body>

<div class="app-container">
    <div class="sidebar">
        <div class="brand">
            <img src="../../assets/img/logo.png" class="sidebar-logo" onerror="this.src='https://via.placeholder.com/45'">
            <h3>Yokoso Cafe</h3>
            <p style="font-size: 10px; color: #aaa; margin-top:-5px;">POS System v1.0</p>
        </div>
        
        <nav>
            <a href="javascript:void(0)" onclick="switchTab('orderTab', this)" class="nav-link active"><i class="fa fa-desktop"></i> New Order</a>
            <a href="javascript:void(0)" onclick="switchTab('historyTab', this)" class="nav-link"><i class="fa fa-folder-open"></i> Order History</a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="time-box">
                <div class="time-display" id="clock" style="font-size: 24px;">00:00</div>
            </div>
            <a href="../../logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" style="background: #f4ece6; padding: 20px;">
        
        <div id="orderTab" class="pos-layout" style="display: flex; gap: 20px; height: 100%;">
            
            <div class="menu-side" style="flex: 7; display: flex; flex-direction: column;">
                
            <div style="background: transparent; margin-bottom: 20px;">
                    
                    <style>
                        /* Custom Nipis nga Scrollbar para sa Categories */
                        .custom-scroll::-webkit-scrollbar { height: 6px; }
                        .custom-scroll::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
                        .custom-scroll::-webkit-scrollbar-thumb { background: #d7ccc8; border-radius: 10px; }
                        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #bcaaa4; }
                    </style>
                    <div style="display: flex; gap: 10px; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 15px;" id="categoryFilters" class="custom-scroll">
                        <button class="filter-btn active" data-cat="All">Loading Categories...</button>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                        
                        <div class="search-wrap" style="position: relative; flex: 1;">
                            <i class="fa fa-search" style="position: absolute; left: 15px; top: 12px; color: #aaa;"></i>
                            <input type="text" id="menuSearch" placeholder="Search menu..." style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s;" onfocus="this.style.borderColor='#e67e22'" onblur="this.style.borderColor='#ddd'">
                        </div>
                        
                        <div style="position: relative; width: 160px;">
                            <select id="typeFilterSelect" style="width: 100%; padding: 10px 30px 10px 15px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-size: 14px; appearance: none; background: white; cursor: pointer; color: #555; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                                <option value="All">All Types</option>
                                <option value="Hot">☕ Hot</option>
                                <option value="Iced">🧊 Iced</option>
                            </select>
                            <i class="fa fa-chevron-down" style="position: absolute; right: 15px; top: 14px; color: #aaa; pointer-events: none; font-size: 12px;"></i>
                        </div>

                    </div>
                </div>
                
                <div class="menu-grid" id="posMenuDisplay" style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; padding-right: 5px;">
                </div>
            </div>

            <div class="cart-panel" style="flex: 3; background: white; border-radius: 15px; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
                <div class="cart-header" style="background: #4E372C; color: white; padding: 20px; display: flex; align-items: center; gap: 15px;">
                    <i class="fa fa-shopping-cart" style="font-size: 24px;"></i>
                    <div>
                        <h3 style="margin: 0; font-size: 18px;">Current Order</h3>
                        <span id="itemCount" style="font-size: 12px; opacity: 0.8;">0 items added</span>
                    </div>
                </div>

                <div class="cart-items" id="cartDisplay" style="flex: 1; overflow-y: auto; padding: 20px;">
                    <div class="empty-state" style="text-align: center; color: #ccc; margin-top: 50px;">
                        <p>Cart is empty</p>
                    </div>
                </div>
                
                <div class="cart-summary" style="padding: 20px; border-top: 1px solid #eee; background: #fafafa;">
                    <div class="payment-method-group" style="margin-bottom: 15px;">
                        <label style="font-size: 12px; color: #777; margin-bottom: 5px; display: block;">Payment Method</label>
                        <div class="payment-options" style="display: flex; gap: 5px;">
                            <label style="flex: 1; background: white; border: 1px solid #ddd; padding: 8px 5px; border-radius: 8px; text-align: center; cursor: pointer; font-size: 12px;">
                                <input type="radio" name="payment" value="Cash" checked> Cash
                            </label>
                            <label style="flex: 1; background: white; border: 1px solid #ddd; padding: 8px 5px; border-radius: 8px; text-align: center; cursor: pointer; font-size: 12px;">
                                <input type="radio" name="payment" value="GCash"> GCash
                            </label>
                            <label style="flex: 1; background: white; border: 1px solid #ddd; padding: 8px 5px; border-radius: 8px; text-align: center; cursor: pointer; font-size: 12px;">
                                <input type="radio" name="payment" value="Card"> Card
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 12px; color: #777;">Discount</label>
                        <select id="discountSelect" onchange="updateTotals()" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                            <option value="0">No Discount</option>
                            <option value="10">10% Off (Promo)</option>
                            <option value="20">20% Off (Senior/PWD)</option>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; color: #555;">
                        <span>Subtotal</span> <span id="subtotal">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; color: #e74c3c;">
                        <span>Discount</span> <span id="discountAmt">- ₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; color: #555;">
                        <span>Tax (12% VAT)</span> <span id="taxAmt">₱0.00</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #333; margin-top: 10px; padding-top: 10px; border-top: 2px dashed #ddd;">
                        <span>Total</span> <span id="total">₱0.00</span>
                    </div>

                    <button class="btn-checkout" onclick="processCheckout()" style="width: 100%; padding: 15px; background: #4E372C; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; margin-top: 15px; cursor: pointer;">
                        <i class="fa fa-paper-plane"></i> PROCEED TO PAYMENT
                    </button>
                </div>
            </div>
        </div>

        <div id="historyTab" style="display: none; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: 100%; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="margin: 0; color: #4E372C;"><i class="fa fa-folder-open"></i> Order History</h2>
                <div class="search-wrap" style="position: relative; width: 300px;">
                    <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #aaa;"></i>
                    <input type="text" id="historySearch" placeholder="Search receipt no..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ddd; border-radius: 20px;">
                </div>
            </div>
            
            <div style="flex: 1; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f4ece6; color: #4E372C; text-align: left;">
                            <th style="padding: 15px; border-radius: 8px 0 0 8px;">Receipt No.</th>
                            <th style="padding: 15px;">Date & Time</th>
                            <th style="padding: 15px;">Total Amount</th>
                            <th style="padding: 15px; border-radius: 0 8px 8px 0; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<div id="sizeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 350px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <h2 id="modalProductName" style="margin-top: 0; margin-bottom: 5px; color: #4b3832; font-size: 22px;">Product Name</h2>
        <p style="color: #666; margin-bottom: 20px; font-size: 14px;">Choose size:</p>
        
        <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 20px;">
            <button id="btnMedium" style="flex: 1; padding: 15px; background: #f39c12; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; display: none; transition: 0.2s;">
                Medium (16oz)<br><small id="priceMedium" style="font-size: 13px;">₱0.00</small>
            </button>
            <button id="btnLarge" style="flex: 1; padding: 15px; background: #d35400; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; display: none; transition: 0.2s;">
                Large (22oz)<br><small id="priceLarge" style="font-size: 13px;">₱0.00</small>
            </button>
        </div>
        
        <button onclick="document.getElementById('sizeModal').style.display='none'" style="width: 100%; padding: 10px; background: #eee; border: none; border-radius: 5px; cursor: pointer; color: #333; font-weight: bold;">Cancel</button>
    </div>
</div>

<div class="modal-overlay" id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 350px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
        <h2 style="margin-top: 0; color: #4b3832; font-size: 22px;">Receive Payment</h2>
        <p style="color: #666; font-size: 15px; margin-bottom: 15px;">Total Due: <strong id="paymentModalTotal" style="font-size: 20px; color: #e74c3c;">₱0.00</strong></p>
        
        <div style="margin-bottom: 15px; text-align: left;">
            <label style="display: block; font-size: 13px; color: #777; margin-bottom: 8px; font-weight: bold;">Cash Tendered:</label>
            <input type="number" id="cashTenderedInput" min="0" oninput="this.value = Math.abs(this.value)" style="width: 100%; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; border: 2px solid #ddd; border-radius: 8px; outline: none; transition: 0.2s;" onfocus="this.style.borderColor='#4b3832'" onblur="this.style.borderColor='#ddd'">
        </div>

        <div style="margin-bottom: 20px;">
            <button onclick="setExactPayment()" style="width: 100%; padding: 10px; background: #e67e22; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s;" onmouseover="this.style.background='#d35400'" onmouseout="this.style.background='#e67e22'">
                <i class="fa fa-money-bill-wave"></i> Exact Amount
            </button>
        </div>

        <div style="display: flex; gap: 10px;">
            <button onclick="closePaymentModal()" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Cancel</button>
            <button onclick="confirmCashPayment()" style="flex: 1; padding: 12px; background: #4b3832; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;"><i class="fa fa-check"></i> Confirm</button>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #receiptModal, #receiptModal * { visibility: visible; }
        #receiptModal { position: absolute; left: 0; top: 0; background: white; width: 100%; height: 100%; display: block !important; }
        .receipt-card { box-shadow: none !important; margin: 0 auto; width: 80mm !important; }
        .no-print { display: none !important; }
    }
</style>
<div class="modal-overlay" id="receiptModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center;">
    <div class="receipt-card" style="background: white; width: 320px; padding: 25px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); font-family: 'Courier New', Courier, monospace; color: #333;">
        <div style="text-align: center; border-bottom: 1px dashed #333; padding-bottom: 15px; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 22px; font-weight: bold;">YOKOSO CAFE</h2>
            <p style="margin: 5px 0 0 0; font-size: 12px;">Cagayan De Oro City</p>
            <p style="margin: 10px 0 0 0; font-size: 12px;">Receipt #: <span id="recNo">REC-0000</span></p>
            <p style="margin: 0; font-size: 12px;">Date: <span id="recDate"></span></p>
            <p style="margin: 5px 0 0 0; font-size: 13px; font-weight: bold;" id="recStatus"></p>
        </div>
        <div id="recItems" style="min-height: 100px; margin-bottom: 15px;"></div>
        <div style="border-top: 1px dashed #333; padding-top: 15px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px;">
                <span>Subtotal:</span> <span id="recSubtotal">₱0.00</span>
            </div>
            <div id="recDiscountContainer" style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px; color: #d35400; display: none;">
                <span>Discount:</span> <span id="recDiscount">- ₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px;">
                <span>VAT (12%):</span> <span id="recTax">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px;">
                <span>TOTAL DUE:</span> <span id="recTotal">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px;">
                <span>Tendered (<span id="recMethod">Cash</span>):</span> <span id="recPayment">₱0.00</span>
            </div>
            <div id="recChangeContainer" style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px; color: #d35400; font-weight: bold;">
                <span>CHANGE:</span> <span id="recChange">₱0.00</span>
            </div>
        </div>
        <div style="text-align: center; margin-top: 25px; font-size: 12px;">
            <p style="margin: 0;">Thank you! Please come again.</p>
            <p style="margin: 5px 0 0 0; font-size: 10px; color: #888;">Powered by Yokoso POS v1.0</p>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 25px;" class="no-print">
            <button onclick="printReceipt()" style="flex: 1; padding: 12px; background: #4E372C; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;"><i class="fa fa-print"></i> Print</button>
            <button id="btnCloseReceiptBtn" onclick="closeReceipt()" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">New Order</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="alertModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 300px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
        <i class="fa fa-exclamation-circle" style="font-size: 40px; color: #e74c3c; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px 0; color: #333;">Notice</h3>
        <p id="alertModalMessage" style="color: #666; font-size: 14px; margin-bottom: 20px;">Message here</p>
        <button onclick="document.getElementById('alertModal').style.display='none'" style="width: 100%; padding: 12px; background: #4E372C; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">OK</button>
    </div>
</div>

<div class="modal-overlay" id="processingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 3000; justify-content: center; align-items: center; flex-direction: column; color: white;">
    <i class="fa fa-circle-notch fa-spin" style="font-size: 60px; color: #e67e22; margin-bottom: 20px;"></i>
    <h2 id="processingTitle" style="margin: 0; font-size: 24px; letter-spacing: 1px;">Processing...</h2>
    <p style="color: #aaa; margin-top: 10px; font-size: 14px;">Please do not close this window.</p>
</div>

<div class="modal-overlay" id="digitalPaymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 350px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
        
        <h2 id="digitalTitle" style="margin-top: 0; color: #007DFE; font-size: 22px;"><i class="fa fa-mobile-screen"></i> GCash Payment</h2>
        <p style="color: #666; font-size: 15px; margin-bottom: 15px;">Amount to Pay: <strong id="digitalTotal" style="font-size: 24px; color: #333;">₱0.00</strong></p>
        
        <div id="qrContainer" style="margin: 15px auto; width: 150px; height: 150px; background: #f9f9f9; padding: 10px; border-radius: 10px; border: 2px dashed #ccc;">
        </div>

        <div style="margin-bottom: 25px; text-align: left;">
            <label style="display: block; font-size: 13px; color: #777; margin-bottom: 8px; font-weight: bold;">PayMongo Reference No:</label>
            <input type="text" id="refNumberInput" placeholder="Auto-filled by system" style="width: 100%; padding: 12px; font-size: 14px; text-align: center; border: 2px solid #ddd; border-radius: 8px; outline: none; background: #f9f9f9;">
        </div>

        <div style="display: flex; gap: 10px;">
            <button onclick="document.getElementById('digitalPaymentModal').style.display='none'" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Cancel</button>
            <button id="btnVerifyDigital" onclick="verifyDigitalPayment()" style="flex: 1; padding: 12px; background: #1fb6ff; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                Verify & Save
            </button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="noteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 350px; box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 15px 0; color: #4E372C; font-size: 18px;"><i class="fa fa-edit"></i> Special Instruction</h3>
        
        <input type="text" id="noteInput" placeholder="e.g., Less Ice, Oat Milk" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; margin-bottom: 20px; font-size: 14px; outline: none; transition: 0.2s;" onfocus="this.style.borderColor='#4E372C'" onblur="this.style.borderColor='#ddd'">
        
        <div style="display: flex; gap: 10px;">
            <button onclick="closeNoteModal()" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Cancel</button>
            <button onclick="saveNote()" style="flex: 1; padding: 12px; background: #4E372C; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Save Note</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="voidModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 320px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
        <i class="fa fa-shield-alt" style="font-size: 40px; color: #c62828; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px 0; color: #333;">Admin Authorization</h3>
        
        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">Palihug i-type ang Admin PIN aron ma-void ang resibo <br><strong id="voidReceiptText" style="color:#c62828;"></strong></p>

        <div style="margin-bottom: 15px; text-align: left;">
            <label style="font-size: 12px; color: #777; font-weight: bold; margin-bottom: 5px; display: block;">Void Reason:</label>
            <select id="voidReasonSelect" onchange="toggleOtherReason()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #f9f9f9; cursor: pointer; margin-bottom: 10px;">
                <option value="" disabled selected>Select a reason...</option>
                <option value="Customer Cancelled Order">Customer Cancelled Order</option>
                <option value="Wrong Item Entered">Wrong Item Entered</option>
                <option value="Duplicate Order">Duplicate Order</option>
                <option value="Item Out of Stock">Item Out of Stock</option>
                <option value="Incorrect Price">Incorrect Price</option>
                <option value="System Error">System Error</option>
                <option value="Testing Transaction">Testing Transaction</option>
                <option value="Other">Other (Please Specify)</option>
            </select>
            
            <input type="text" id="otherReasonInput" placeholder="Palihug i-type ang rason dinhi..." style="display: none; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fff;" onfocus="this.style.borderColor='#4E372C'" onblur="this.style.borderColor='#ddd'">
        </div>

        <input type="password" id="adminPinInput" placeholder="Enter PIN (12345)" style="width: 100%; padding: 12px; font-size: 18px; text-align: center; letter-spacing: 5px; border: 2px solid #ddd; border-radius: 8px; outline: none; margin-bottom: 20px;" onfocus="this.style.borderColor='#c62828'" onblur="this.style.borderColor='#ddd'">
        
        <div style="display: flex; gap: 10px;">
            <button onclick="closeVoidModal()" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Cancel</button>
            <button onclick="submitVoidOrder()" id="btnConfirmVoid" style="flex: 1; padding: 12px; background: #c62828; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Confirm Void</button>
        </div>
    </div>
</div>

<script src="../../assets/js/cashier.js?v=<?php echo time(); ?>"></script>
</body>
</html>