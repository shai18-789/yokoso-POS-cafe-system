<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$conn = new mysqli('127.0.0.1', 'root', '', 'cafe_pos_db');

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit();
}

// Kuhaon ang mga latest orders (50 kabuok)
$orders_query = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 50");
$orders = [];

if ($orders_query) {
    while ($order = $orders_query->fetch_assoc()) {
        $order_id = $order['id'];
        
        // Kuhaon ang mga items sa matag order, gi-join ang products table para sa pangalan sa kape
        $items_query = $conn->query("
            SELECT oi.*, p.product_name 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = $order_id
        ");
        
        $items = [];
        if ($items_query) {
            while ($item = $items_query->fetch_assoc()) {
                $items[] = $item;
            }
        }
        $order['items'] = $items;
        $orders[] = $order;
    }
}

echo json_encode($orders);
$conn->close();
?>