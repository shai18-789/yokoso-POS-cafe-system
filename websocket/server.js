const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json()); // Para makabasa og JSON gikan sa PHP

const server = http.createServer(app);

// Setup sa WebSocket
const io = new Server(server, {
    cors: {
        origin: "*", // Pwede maka-connect bisan kinsa (localhost)
        methods: ["GET", "POST"]
    }
});

// Kung naay mo-connect nga Barista
io.on('connection', (socket) => {
    console.log('Usa ka user ni-connect: ' + socket.id);

    socket.on('disconnect', () => {
        console.log('User na-disconnect: ' + socket.id);
    });
});

// API endpoint nga tawagon sa PHP inig human og Checkout
app.post('/api/new-order', (req, res) => {
    const orderData = req.body;
    
    console.log("BAG-ONG ORDER NADAYON! Resibo:", orderData.receipt_no);
    
    // I-broadcast ang order padulong sa tanang naka-connect (Barista)
    io.emit('new_order_alert', orderData);
    
    res.json({ success: true, message: "Na-broadcast na sa Barista!" });
});

// Paandaron ang server sa Port 3000
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`=================================`);
    console.log(`🚀 YOKOSO WEBSOCKET SERVER NAG-ANDAR NA!`);
    console.log(`📡 Naminaw sa Port: ${PORT}`);
    console.log(`=================================`);
});