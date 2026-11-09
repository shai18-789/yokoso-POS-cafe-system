<?php
session_start();
// (Optional) Pwede ka mag-add og role checking diri puhon
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barista') { ... }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barista Screen | Yokoso Cafe</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #e9e4e1; }
        /* Ticket Design */
        .ticket-card {
            background: white;
            border-top: 5px solid #e74c3c;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 280px;
            display: flex;
            flex-direction: column;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px dashed #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .order-item {
            font-size: 15px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            gap: 10px;
        }
        .qty-badge {
            background: #4E372C;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-done {
            margin-top: 15px;
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-done:hover { background: #27ae60; }
    </style>
</head>
<body>

<div class="app-container" style="display: block; padding: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fa fa-coffee" style="font-size: 30px; color: #4E372C;"></i>
            <h2 style="margin: 0; color: #4E372C;">Barista Queue</h2>
        </div>
        <div style="display: flex; gap: 15px; align-items: center;">
            
            <button onclick="openRecipeBook()" style="background: #34495e; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <i class="fa fa-book"></i> 📖 Recipe Book
            </button>

            <span id="connectionStatus" style="color: #e74c3c; font-weight: bold; font-size: 14px;"><i class="fa fa-circle"></i> Disconnected</span>
            <div id="clock" style="font-size: 20px; font-weight: bold; color: #333;">00:00</div>
        </div>
    </div>

    <div id="orderBoard" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start;">
        <div id="emptyBoard" style="width: 100%; text-align: center; color: #888; margin-top: 50px; font-size: 18px;">
            <i class="fa fa-mug-hot" style="font-size: 50px; color: #ccc; margin-bottom: 15px;"></i>
            <p>Walay pending nga order. Relax sa ta! ☕</p>
        </div>
    </div>

</div>

<div id="recipeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: #fff; width: 500px; border-radius: 12px; padding: 20px; max-height: 80vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #4E372C;">📖 Barista Cheat Sheet</h3>
            <button onclick="closeRecipeBook()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #e74c3c;">&times;</button>
        </div>
        <div id="recipeList" style="overflow-y: auto; flex: 1; padding-right: 10px;">
            </div>
    </div>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>

<script>
// ==========================================
    // WEBSOCKET CONNECTION & TICKET LOGIC
    // ==========================================
    const socket = io('http://localhost:3000');
    const statusIndicator = document.getElementById('connectionStatus');
    const orderBoard = document.getElementById('orderBoard');
    const emptyBoard = document.getElementById('emptyBoard');

    socket.on('connect', () => {
        statusIndicator.innerHTML = '<i class="fa fa-circle"></i> Connected (Live)';
        statusIndicator.style.color = '#2ecc71';
    });

    socket.on('disconnect', () => {
        statusIndicator.innerHTML = '<i class="fa fa-circle"></i> Disconnected';
        statusIndicator.style.color = '#e74c3c';
    });

    socket.on('new_order_alert', (orderData) => {
        if (emptyBoard) emptyBoard.style.display = 'none';
        createTicket(orderData);
    });

    // API Call to Update Database (Gidugang ang receiptNo)
    function updateOrderStatus(orderId, receiptNo, newStatus, callback) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('receipt_no', receiptNo); // 📍 Gipadala na nato ang Resibo!
        formData.append('status', newStatus);

        fetch('../../api/barista/update_status.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                if(callback) callback();
            } else {
                alert("Error updating status: " + data.message);
            }
        })
        .catch(err => console.error("Error updating status:", err));
    }

    // Function para mabuhat ang ticket
    function createTicket(data) {
        const timeNow = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        let itemsHtml = '';
        
        data.items.forEach(item => {
            let noteHTML = '';
            if (item.notes && item.notes.trim() !== "") {
                noteHTML = `<div style="color: #e74c3c; font-size: 12px; margin-left: 35px; font-style: italic;">
                                <i class="fa fa-arrow-right"></i> Note: ${item.notes}
                            </div>`;
            }

            itemsHtml += `
                <div style="margin-bottom: 8px;">
                    <div style="display: flex; gap: 10px; font-weight: bold; font-size: 14px; margin-bottom: 2px;">
                        <span class="qty-badge">${item.qty}x</span>
                        <span>${item.name}</span>
                    </div>
                    ${noteHTML}
                </div>
            `;
        });

        const ticketId = 'ticket-' + data.order_id;
        const btnId = 'btn-' + data.order_id;
        
        // 📍 Gidugang nato ang data.receipt_no sa onclick function
        const ticketHtml = `
            <div class="ticket-card" id="${ticketId}">
                <div class="ticket-header">
                    <strong style="font-size: 16px; color: #e74c3c;">${data.receipt_no}</strong>
                    <span style="font-size: 13px; color: #888;">${timeNow}</span>
                </div>
                
                <div style="flex: 1; padding: 10px 0;">
                    ${itemsHtml}
                </div>
                
                <button id="${btnId}" class="btn-done" style="background: #f39c12; margin-top: 15px;" onclick="progressTicket('${ticketId}', '${btnId}', ${data.order_id}, '${data.receipt_no}')">
                    <i class="fa fa-fire"></i> Start Prep
                </button>
            </div>
        `;

        orderBoard.insertAdjacentHTML('afterbegin', ticketHtml);
    }

    // Logic sa progressive button (Gidawat ang receiptNo)
    function progressTicket(ticketElementId, btnId, orderId, receiptNo) {
        const btn = document.getElementById(btnId);
        
        if (btn.innerText.includes('Start Prep')) {
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
            btn.disabled = true;
            
            updateOrderStatus(orderId, receiptNo, 'Preparing', () => {
                btn.innerHTML = '<i class="fa fa-check-circle"></i> Mark Ready';
                btn.style.background = '#3498db'; 
                btn.disabled = false;
            });
            
        } else if (btn.innerText.includes('Mark Ready')) {
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Finishing...';
            btn.disabled = true;

            updateOrderStatus(orderId, receiptNo, 'Completed', () => {
                const ticket = document.getElementById(ticketElementId);
                if (ticket) ticket.remove();
                
                if (orderBoard.children.length === 1 && orderBoard.children[0].id === 'emptyBoard') {
                    emptyBoard.style.display = 'block';
                }
            });
        }
    }

    // ==========================================
    // RECIPE BOOK LOGIC
    // ==========================================
    function openRecipeBook() {
        document.getElementById('recipeModal').style.display = 'flex';
        fetchRecipes();
    }

    function closeRecipeBook() {
        document.getElementById('recipeModal').style.display = 'none';
    }

    async function fetchRecipes() {
        const recipeList = document.getElementById('recipeList');
        recipeList.innerHTML = '<div style="text-align: center; padding: 20px; color: #888;"><i class="fa fa-spinner fa-spin"></i> Loading recipes...</div>';

        try {
            const response = await fetch('../../api/barista/get_recipes.php');
            const data = await response.json();

            let html = '';
            data.forEach(item => {
                let recipeText = item.recipe_text ? item.recipe_text : '<span style="color:#aaa; font-style:italic;">Walay recipe nga na-save. Ask the Admin.</span>';
                html += `
                    <div style="background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                        <h4 style="margin: 0 0 8px 0; color: #333; font-size: 15px;">☕ ${item.product_name}</h4>
                        <div style="margin: 0; font-size: 13px; color: #555; white-space: pre-wrap; line-height: 1.5;">${recipeText}</div>
                    </div>
                `;
            });
            
            if(data.length === 0) {
                html = '<div style="text-align: center; color: #e74c3c;">Walay nakit-an nga menu sa database.</div>';
            }
            
            recipeList.innerHTML = html;
        } catch (err) {
            console.error("Error fetching recipes:", err);
            recipeList.innerHTML = '<div style="color: red; text-align: center;">Dili ka-connect sa server.</div>';
        }
    }

    // Clock function
    setInterval(() => {
        document.getElementById('clock').innerHTML = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }, 1000);
</script>

</body>
</html>