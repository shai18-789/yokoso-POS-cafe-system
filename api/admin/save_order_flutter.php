<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Dawaton ang pre-flight request sa Chrome
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$conn = new mysqli('127.0.0.1', 'root', '', 'cafe_pos_db');
if ($conn->connect_error) { echo json_encode(["error" => "DB Failed"]); exit(); }

// Basahon ang JSON gikan sa Flutter
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) { echo json_encode(["error" => "Walay data nga niabot"]); exit(); }

$receipt_no = $conn->real_escape_string($data['receipt_no']);
$total = (float)$data['total_amount'];
$method = $conn->real_escape_string($data['payment_method']);
$tendered = (float)$data['tendered'];
$change = (float)$data['change'];
$cashier_id = 6; // Hardcoded una base sa imong screenshot

// 1. I-save sa `orders` table
$conn->query("INSERT INTO orders (cashier_id, total_amount, payment_method, status) VALUES ($cashier_id, $total, '$method', 'Completed')");
$order_id = $conn->insert_id;

// 2. I-save ang matag kape sa `order_items` table
if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['price'];
        $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, $pid, $qty, $price)");
    }
}

// 3. I-save sa `sales` table
$conn->query("INSERT INTO sales (receipt_no, total_amount, cash_tendered, change_amount, status) VALUES ('$receipt_no', $total, $tendered, $change, 'Completed')");

echo json_encode(["success" => true, "message" => "Na-save na sa database!"]);
$conn->close();
?>