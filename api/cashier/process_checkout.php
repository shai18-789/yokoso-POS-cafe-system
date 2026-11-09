<?php
session_start();
include '../../config/database.php'; // Siguradoha nga husto ang path sa imong db connection

header('Content-Type: application/json');

// Kuhaon ang data gikan sa imong Cashier JS
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit();
}

$conn->begin_transaction(); // Sugod sa transaction para safe

try {
    // 1. I-save sa SALES table (Admin Sales Report)
    $receipt_no = "REC-" . date('ymd') . "-" . rand(100, 999);
    $total_amount = $data['total'];
    $cash_tendered = $data['tendered'];
    $change_amount = $data['change'];
    $payment_method = $data['payment_method'];

    $stmt = $conn->prepare("INSERT INTO sales (receipt_no, total_amount, cash_tendered, change_amount, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("sddd", $receipt_no, $total_amount, $cash_tendered, $change_amount);
    $stmt->execute();
    $sale_id = $conn->insert_id;

    // 2. I-process ang matag kape nga gi-order
    foreach ($data['items'] as $item) {
        // I-save ang item sa sale_items (para sa History)
        // Kuhaon ang notes gikan sa JS (kung walay sulod, himoong blank)
        $notes = isset($item['notes']) ? $item['notes'] : '';

        // I-save ang item ug ang notes sa sale_items
        $stmt_item = $conn->prepare("INSERT INTO sale_items (sale_id, product_name, quantity, price, subtotal, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $subtotal = $item['price'] * $item['qty'];
        $stmt_item->bind_param("isidds", $sale_id, $item['name'], $item['qty'], $item['price'], $subtotal, $notes);
        $stmt_item->execute();

        // --- KINI ANG KONEKSYON SA ADMIN INVENTORY ---
        // Pangitaon ang ingredients ani nga kape (e.g. Beans, Milk)
        $ing_query = "SELECT inventory_id, amount_needed FROM product_ingredients WHERE product_id = ?";
        $stmt_ing = $conn->prepare($ing_query);
        $stmt_ing->bind_param("i", $item['id']);
        $stmt_ing->execute();
        $ingredients = $stmt_ing->get_result();

        while ($ing = $ingredients->fetch_assoc()) {
            $total_needed = $ing['amount_needed'] * $item['qty'];
            
            // I-MINUS ang stock sa Admin Inventory
            $update_inv = "UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt_upd = $conn->prepare($update_inv);
            $stmt_upd->bind_param("di", $total_needed, $ing['inventory_id']);
            $stmt_upd->execute();
        }
    }

    $conn->commit();
    echo json_encode([
        'status' => 'success', 
        'receipt_no' => $receipt_no,
        'message' => 'Order processed and stock updated!'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}