<?php
// cors headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // 1. Saktong SQL query gamit ang status ug category_name
    $stmt = $pdo->query("SELECT id, category_name as name FROM categories WHERE status = 1 ORDER BY id ASC");
    
    // 2. KINI ANG LINYA NGA NAWALA GANIHA:
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. I-send balik sa Flutter
    echo json_encode($categories);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>