<?php
session_start();
require_once '../../config/database.php'; // Unified connection and logAction function
header('Content-Type: application/json; charset=utf-8');

try {
    // Read the JSON data sent from JavaScript
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = $data['id'] ?? '';
    $name = $data['name'] ?? '';
    $status = isset($data['status']) ? (int)$data['status'] : 1; 

    if (empty($name)) {
        echo json_encode(["success" => false, "error" => "Category name is required"]);
        exit();
    }

    // Set up the variables for our System Log
    $log_action = "";
    $log_details = "";

    if ($id === '') {
        // ADD NEW CATEGORY
        $sql = "INSERT INTO categories (category_name, status) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $status]);
        
        $log_action = "Add Category";
        $log_details = "Admin added new category: " . $name;
    } else {
        // UPDATE EXISTING CATEGORY
        $sql = "UPDATE categories SET category_name = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $status, $id]);
        
        $log_action = "Update Category";
        $log_details = "Admin updated category: " . $name;
    }

    if ($success) {
        // ==========================================
        // SYSTEM LOGS TRACKING (Inside the success block!)
        // ==========================================
        $admin_id = $_SESSION['user_id'] ?? 0;
        logAction($pdo, $admin_id, $log_action, $log_details);
        // ==========================================

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Execute Failed to save category."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>