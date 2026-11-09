<?php
session_start();

// 📍 Idugang kini nga mga CORS headers para dili i-block sa Flutter Web!
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php'; 

header('Content-Type: application/json');

// ... (ug padayon ang imong code sa ubos)

$receipt_no = $_GET['receipt_no'] ?? '';

if (!$receipt_no) {
    echo json_encode(['success' => false, 'message' => 'Walay resibo nga gi-send.']);
    exit;
}

try {
    // 1. Pangitaon ang Total ug Details sa sales table
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE receipt_no = ?");
    $stmt->execute([$receipt_no]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Wala makit-i ang resibo.']);
        exit;
    }

    // 2. Pangitaon ang mga Kape nga gi-order sa sale_items table
    $stmtItems = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
    $stmtItems->execute([$sale['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 3. 📍 BULLETPROOF: Pangitaon ang Void Reason sa System Logs kung Voided na siya
    $void_reason = null;
    if ($sale['status'] === 'Voided') {
        try {
            // Pangitaon nato ang log gamit ang simple query para iwas error sa table structure
            $logStmt = $pdo->prepare("SELECT * FROM system_logs WHERE details LIKE ? ORDER BY id DESC LIMIT 1");
            $logStmt->execute(["%VOIDED Receipt: $receipt_no%"]);
            $log = $logStmt->fetch(PDO::FETCH_ASSOC);

            if ($log && isset($log['details'])) {
                // Kuhaon nato ang text taliwala sa "Reason: " ug " | Approved"
                $parts = explode('| Reason: ', $log['details']);
                if (isset($parts[1])) {
                    $reason_part = explode('| Approved', $parts[1]);
                    $void_reason = trim($reason_part[0]);
                }
            }
        } catch (PDOException $ex) {
            // Dili nato i-crash ang script kung wala ang table o error ang column
            $void_reason = "Could not fetch reason."; 
        }
        
        // Kung walay nakit-an nga log
        if (!$void_reason) {
            $void_reason = "No reason specified.";
        }
    }

    echo json_encode([
        'success' => true,
        'sale' => $sale,
        'items' => $items,
        'void_reason' => $void_reason // 📍 Gidugang nato sa pag-send balik sa Admin!
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>