<?php
session_start();
require_once '../../config/database.php'; // Siguraduha nga mao ni ang saktong path sa imong database connection
header('Content-Type: application/json; charset=utf-8');

try {
    // Basahon ang JSON data gikan sa JavaScript fetch
    $data = json_decode(file_get_contents("php://input"), true);
    
    $inventory_id = isset($data['id']) ? (int)$data['id'] : 0;
    $amount = isset($data['amount']) ? (int)$data['amount'] : 0;
    $expiration_date = $data['expiration_date'] ?? '';

    // I-check kung kumpleto ba ang gipadala
    if ($inventory_id <= 0 || $amount <= 0 || empty($expiration_date)) {
        echo json_encode(["success" => false, "error" => "Kuwaang ang impormasyon (ID, Amount, o Expiration Date)."]);
        exit();
    }

    // MAGSUGOD TA OG DATABASE TRANSACTION (Para dungan ma-save sa duha ka table)
    $pdo->beginTransaction();

    // 1. I-save ang "BATCH" nga naay Expiration Date (Kini ang para sa FIFO nato)
    $stmtBatch = $pdo->prepare("INSERT INTO inventory_batches (inventory_id, quantity, expiration_date) VALUES (?, ?, ?)");
    $stmtBatch->execute([$inventory_id, $amount, $expiration_date]);

    // 2. I-update ang TOTAL STOCK didto sa main 'inventory' table
    $stmtMain = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE id = ?");
    $stmtMain->execute([$amount, $inventory_id]);

    // I-commit (I-save permanently ang duha ka actions)
    $pdo->commit();

    // ==========================================
    // SYSTEM LOGS TRACKING (GI-FIX NA NAKO DARI)
    // ==========================================
    $admin_id = $_SESSION['user_id'] ?? 0; // Para di mag-error kung walay session
    $action = "Restock Inventory";
    $details = "Admin restocked " . $amount . " items to Inventory ID: " . $inventory_id;
    logAction($pdo, $admin_id, $action, $details);
    // ==========================================

    // KINI DAPAT ANG PINAKA-LAST SA TRY BLOCK
    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    // Kung naay mo-error sa bisag asa atong duha (Batch o Main), i-cancel ang transaction para dili magkagubot ang data.
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); 
    }
    echo json_encode(["success" => false, "error" => "Database Error: " . $e->getMessage()]);
}
?>