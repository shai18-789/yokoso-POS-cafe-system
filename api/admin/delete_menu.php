<?php
session_start();
require_once '../../config/database.php'; // Gitawag nato ang saktong database connection
header('Content-Type: application/json; charset=utf-8');

try {
    $data = json_decode(file_get_contents("php://input"));
    $id = $data->id ?? '';

    if ($id) {
        // I-delete ang product gamit ang PDO setup nimo
        $sql = "DELETE FROM products WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id])) {
            
            // ==========================================
            // SYSTEM LOGS TRACKING (Masulod lang kung success ang delete)
            // ==========================================
            $admin_id = $_SESSION['user_id'] ?? 0; 
            $action = "Delete Menu Item";
            $details = "Admin deleted Menu Item ID: " . $id;
            logAction($pdo, $admin_id, $action, $details);
            // ==========================================

            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Dili ma-delete."]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Walay ID nga gipadala."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database Error: " . $e->getMessage()]);
}
?>