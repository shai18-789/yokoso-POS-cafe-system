<?php
session_start();

// 📍 Idugang kini nga mga CORS headers!
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php'; 

header('Content-Type: application/json');

try {
    // Kuhaon ang pinakabag-o nga halin (sorted by date descending)
    // Limitahan nato og 50 para dili mo-hinay ang system kung libo na ang data
    
    // 📍 GIDUGANG ANG "status" DIRI NGA LINYA:
    $stmt = $pdo->query("SELECT receipt_no, total_amount, sale_date, status FROM sales ORDER BY sale_date DESC LIMIT 50");
    
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $sales]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>