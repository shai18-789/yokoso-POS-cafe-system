<?php
session_start();

// 📍 IDUGANG KINI NGA MGA CORS HEADERS PARA DILI MA-BLOCK ANG CHECKOUT!
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// SAFE INCLUDE: I-check usa kung nag-exist ba ang logger.php...
// ... (ug padayon sa imong code sa ubos)
// SAFE INCLUDE: I-check usa kung nag-exist ba ang logger.php aron dili mo-gawas ang Warning error
$logger_path = '../admin/logger.php';
if (file_exists($logger_path)) {
    include_once $logger_path;
}
require_once '../../config/database.php'; 

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

try {
    $pdo->beginTransaction();

    $cashier_id = $_SESSION['user_id'] ?? 1;
    $total = $data->total;
    $payment_method = $data->payment_method;
    $cash_tendered = $data->cash_tendered;
    $receipt_no = $data->receipt_no;
    $change_amount = $cash_tendered - $total;

    // 1. Save to orders table (Gihimong Pending)
    $stmtOrder = $pdo->prepare("INSERT INTO orders (cashier_id, total_amount, payment_method, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
    $stmtOrder->execute([$cashier_id, $total, $payment_method]);
    $order_id = $pdo->lastInsertId();

    // 2. Save to sales table (Gidugang ang status = 'Pending')
    $stmtSale = $pdo->prepare("INSERT INTO sales (receipt_no, total_amount, cash_tendered, change_amount, status, sale_date) VALUES (?, ?, ?, ?, 'Pending', NOW())");
    $stmtSale->execute([$receipt_no, $total, $cash_tendered, $change_amount]);
    $sale_id = $pdo->lastInsertId();

    // 3. Pre-prepare statements for performance
    $stmtOrderItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, modifiers, notes) VALUES (?, ?, ?, '', '')");
    $stmtSaleItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    // Prepare statements for Auto-Deduct (GIBUTANGAN OG COMMENT)
    // $stmtGetIngredients = $pdo->prepare("SELECT inventory_id, amount_needed FROM product_ingredients WHERE product_id = ?");
    // $stmtUpdateInventory = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?");

    foreach ($data->items as $item) {
        
        // BUG FIX: I-convert ang "3_L" ngadto sa 3 aron dili masuko ang database!
        $real_product_id = (int)$item->id; 

        // Save for Barista & Sales History (Gamiton ang real_product_id)
        $stmtOrderItem->execute([$order_id, $real_product_id, $item->qty]);
        $subtotal = $item->price * $item->qty;
        $stmtSaleItem->execute([$sale_id, $item->name, $item->qty, $item->price, $subtotal]);

        // ==========================================
        // AUTO-DEDUCT LOGIC (Option B: Grams/ML) - GI-DISABLE NA KINI
        // ==========================================
        /*
        // 1. Kuhaon ang ingredients sa kape
        $stmtGetIngredients->execute([$real_product_id]);
        $ingredients = $stmtGetIngredients->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ingredients as $ing) {
            // 2. I-compute pila ang i-minus (needed * qty order)
            $total_deduct = $ing['amount_needed'] * $item->qty;
            
            // 3. I-minus na sa inventory table
            $stmtUpdateInventory->execute([$total_deduct, $ing['inventory_id']]);
        }
        */
        // ==========================================
    } // 📍 KINI ANG SAKTO NGA CLOSING BRACE (WALA NA APIL SA COMMENT)

    // BUG FIX: Gamiton ang $pdo imbes nga $conn para sa Logger
    $loggedInUser = $_SESSION['user_id'] ?? 0;
    if (class_exists('Logger')) {
        Logger::log($pdo, $loggedInUser, 'Checkout', "Processed order. Receipt: $receipt_no | Total: ₱$total");
    }

    $pdo->commit();

    // 4. NODE.JS SOCKET DATA (Kitchen Display)
    $socketData = [
        'order_id' => $order_id,
        'receipt_no' => $receipt_no,
        'items' => $data->items
    ];

    $ch = curl_init('http://localhost:3000/api/new-order');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($socketData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    curl_exec($ch);
    curl_close($ch);

    // KUNG SUCCESS TANAN, I-SEND SA JAVASCRIPT
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>