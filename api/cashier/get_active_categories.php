<?php
/*require_once '../../config/database.php';

try {
    // GI-LUAGAN NATO ANG QUERY:
    // Gidugang nato ang '1' kay kasagaran sa system, ang Active kay 1, ug ang Inactive kay 0.
    $stmt = $pdo->query("SELECT id, category_name FROM categories WHERE status IN ('Active', 'active', '1', 1) ORDER BY category_name ASC");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);

} catch (PDOException $e) {
    // KUNG MAG-ERROR (Pananglitan: dili 'status' ang ngalan sa column sa imong database), 
    // Mopagawas nig error message para makita nato.
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}*/


// 📍 I-add gihapon nato ang CORS headers para kung gamiton nimo ni sa Flutter puhon, dili mo-error
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 📍 Importante: Sultihi ang browser nga JSON format ni
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    // GI-LUAGAN NATO ANG QUERY:
    // Gidugang nato ang '1' kay kasagaran sa system, ang Active kay 1, ug ang Inactive kay 0.
    $stmt = $pdo->query("SELECT id, category_name FROM categories WHERE status IN ('Active', 'active', '1', 1) ORDER BY category_name ASC");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);

} catch (PDOException $e) {
    // KUNG MAG-ERROR, Mopagawas nig error message para makita nato.
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>