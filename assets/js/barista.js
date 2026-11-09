// ==========================================
// 1. INITIAL LOAD & STATUS UPDATES
// ==========================================
function fetchOrders() {
    fetch('../../api/barista/get_orders.php')
    .then(res => res.json())
    .then(orders => {
        const lists = {
            'Pending': document.getElementById('pendingList'),
            'Preparing': document.getElementById('preparingList'),
            'Ready': document.getElementById('readyList')
        };

        // Clear lists first
        Object.values(lists).forEach(list => {
            if(list) list.innerHTML = '';
        });

        orders.forEach(order => {
            const card = `
                <div class="queue-card">
                    <h5>Order #${order.id}</h5>
                    <small>${order.items_summary}</small>
                    <div class="queue-actions">
                        ${getStatusButton(order.id, order.status)}
                    </div>
                </div>`;
            
            if (lists[order.status]) {
                lists[order.status].innerHTML += card;
            }
        });
    })
    .catch(err => console.error("Error fetching orders:", err));
}

function getStatusButton(id, status) {
    if (status === 'Pending') return `<button class="btn-small" onclick="updateStatus(${id}, 'Preparing')">Start Prep</button>`;
    if (status === 'Preparing') return `<button class="btn-small" onclick="updateStatus(${id}, 'Ready')">Mark Ready</button>`;
    if (status === 'Ready') return `<button class="btn-small" onclick="updateStatus(${id}, 'Completed')">Done</button>`;
    return '';
}

function updateStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('order_id', id);
    formData.append('status', newStatus);

    // Karon nag-change ta padung sa 'Completed', i-wala na ang card sa barista screen
    fetch('../../api/barista/update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            fetchOrders(); // Refresh UI after update
        } else {
            console.error("Failed to update status:", data.message);
        }
    })
    .catch(err => console.error("Error updating status:", err));
}

// Initial call inig abli sa page
fetchOrders(); 
setInterval(fetchOrders, 3000); // Polling every 3 seconds aron dili ma-miss ang manual updates

// ==========================================
// 2. WEBSOCKET LOGIC (Real-time receive)
// ==========================================
const ws = new WebSocket('ws://localhost:3000');

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);

    if (data.type === 'new_order') {
        const orderData = data.order; 
        
        let itemsHTML = '';
        orderData.items.forEach(item => {
            let noteHTML = '';
            if (item.notes && item.notes.trim() !== "") {
                noteHTML = `<div style="color: #e74c3c; font-size: 12px; margin-left: 25px; font-style: italic;">
                                <i class="fa fa-arrow-right"></i> Note: ${item.notes}
                            </div>`;
            }

            itemsHTML += `
                <div style="margin-bottom: 8px;">
                    <div style="display: flex; gap: 10px; font-weight: bold; font-size: 14px;">
                        <span style="background: #4E372C; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;">${item.qty}x</span>
                        <span>${item.name}</span>
                    </div>
                    ${noteHTML} 
                </div>
            `;
        });

        const pendingList = document.getElementById('pendingList');
        if(pendingList) {
            const ticketCard = `
                <div class="queue-card" style="border-left: 4px solid #e74c3c;">
                    <h5>Resibo: ${orderData.receipt_no}</h5>
                    <div>${itemsHTML}</div>
                    <div class="queue-actions" style="margin-top: 10px;">
                        <button class="btn-small" style="background: #2ecc71; color: white; border: none; padding: 5px 10px; cursor: pointer;" onclick="fetchOrders()">🔄 Refresh Board</button>
                    </div>
                </div>
            `;
            pendingList.innerHTML = ticketCard + pendingList.innerHTML; 
        }
    }
};