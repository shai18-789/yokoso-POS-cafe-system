<?php
/*session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Cashier') {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History | Yokoso Cafe</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <a href="index.php" class="nav-link"><i class="fa fa-desktop"></i> New Order</a>
            <a href="history.php" class="nav-link active"><i class="fa fa-folder-open"></i> Order History</a>
        </nav>
        <div class="sidebar-footer">
            <div class="time-box">
                <div class="time-display" id="clock" style="font-size: 24px;">00:00</div>
            </div>
            <a href="../../logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" style="background: #f4ece6; padding: 20px;">
        <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: 100%; display: flex; flex-direction: column;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <div>
                    <h2 style="color: #4E372C; margin: 0;"><i class="fa fa-clock-rotate-left"></i> Today's Transactions</h2>
                    <p style="color: #888; margin: 5px 0 0 0; font-size: 13px;">View and reprint receipts from your shift.</p>
                </div>
                <div class="search-wrap" style="position: relative; width: 300px;">
                    <i class="fa fa-search" style="position: absolute; left: 15px; top: 12px; color: #aaa;"></i>
                    <input type="text" id="historySearch" placeholder="Search Receipt No..." style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #ddd; border-radius: 8px; outline: none;">
                </div>
            </div>

            <div style="flex: 1; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #4E372C; color: white;">
                            <th style="padding: 12px 15px; border-radius: 8px 0 0 0;">Receipt No.</th>
                            <th style="padding: 12px 15px;">Time</th>
                            <th style="padding: 12px 15px;">Total Amount</th>
                            <th style="padding: 12px 15px;">Payment</th>
                            <th style="padding: 12px 15px; text-align: center; border-radius: 0 8px 0 0;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        </tbody>
                </table>
            </div>
            
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
        </div>
        
        <div id="recItems" style="min-height: 100px; margin-bottom: 15px;"></div>
        
        <div style="border-top: 1px dashed #333; padding-top: 15px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px;">
                <span>Subtotal:</span>
                <span id="recSubtotal">₱0.00</span>
            </div>
            <div id="recDiscountContainer" style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px; color: #d35400; display: none;">
                <span>Discount:</span>
                <span id="recDiscount">- ₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px;">
                <span>VAT (12%):</span>
                <span id="recTax">₱0.00</span>
            </div>

            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px;">
                <span>TOTAL DUE:</span>
                <span id="recTotal">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px;">
                <span>Tendered:</span>
                <span id="recPayment">₱0.00</span>
            </div>
            <div id="recChangeContainer" style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px; color: #d35400; font-weight: bold;">
                <span>CHANGE:</span>
                <span id="recChange">₱0.00</span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 25px; font-size: 12px;">
            <p style="margin: 0;">Thank you! Please come again.</p>
            <p style="margin: 5px 0 0 0; font-size: 10px; color: #888;">Powered by Yokoso POS v1.0</p>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 25px;" class="no-print">
            <button onclick="printReceipt()" style="flex: 1; padding: 12px; background: #4E372C; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;"><i class="fa fa-print"></i> Print</button>
            <button onclick="closeReceipt()" style="flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Close</button>
        </div>
    </div>
</div>
<script src="../../assets/js/cashier.js?v=<?php echo time(); ?>"></script>
</body>
</html>*/