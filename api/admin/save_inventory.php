<?php
session_start();
// Gamiton nato ang imong config para parehas sa get_inventory!
require_once '../../config/database.php'; 
header('Content-Type: application/json; charset=utf-8');

try {
    // Kuhaon ang data gikan sa form
    $id = $_POST['id'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $description = $_POST['description'] ?? ''; 
    $stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0;
    $unit = $_POST['unit'] ?? '';
   // $low_stock_threshold = isset($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 0;
    
    // KINI ANG BAG-O: Kuhaon ang is_expirable (0 kung packaging, 1 kung ingredient)
    $is_expirable = isset($_POST['is_expirable']) ? (int)$_POST['is_expirable'] : 1;

    if (empty($item_name) || empty($unit)) {
        echo json_encode(["success" => false, "error" => "Item name and unit are required"]);
        exit();
    }

    // Set up variables for the System Log
    $log_action = "";
    $log_details = "";

    if ($id === '') {
        // PAG-ADD OG BAG-O NGA ITEM
        $sql = "INSERT INTO inventory (item_name, description, stock_quantity, unit, is_expirable) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$item_name, $description, $stock_quantity, $unit, $is_expirable]);

        $log_action = "Add Inventory Item";
        $log_details = "Admin added new inventory item: " . $item_name;
    } else {
        // PAG-EDIT SA DAAN NGA ITEM
        $sql = "UPDATE inventory SET item_name = ?, description = ?, stock_quantity = ?, unit = ?, is_expirable = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$item_name, $description, $stock_quantity, $unit, $is_expirable, $id]);

        $log_action = "Update Inventory Item";
        $log_details = "Admin updated inventory item: " . $item_name;
    }

    // ==========================================
    // SYSTEM LOGS TRACKING (Inside the try block!)
    // ==========================================
    $admin_id = $_SESSION['user_id'] ?? 0;
    logAction($pdo, $admin_id, $log_action, $log_details);
    // ==========================================

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database Error: " . $e->getMessage()]);
}
?>