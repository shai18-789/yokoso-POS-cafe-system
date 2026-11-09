<?php
session_start();
require_once '../../config/database.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'fetch') {
    $stmt = $pdo->query("SELECT p.*, c.category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id");
    echo json_encode($stmt->fetchAll());
    exit(); // Always good to exit after sending data back
}

if ($action === 'add') {
    $name = $_POST['product_name'] ?? '';
    $cat = $_POST['category_id'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $recipe = $_POST['recipe_text'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO products (product_name, category_id, price, recipe_text) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$name, $cat, $price, $recipe]);
    
    // ==========================================
    // SYSTEM LOGS TRACKING (Inside the ADD block!)
    // ==========================================
    if ($success) {
        $admin_id = $_SESSION['user_id'] ?? 0; 
        $log_action = "Add Menu Item"; // Changed variable name so it doesn't conflict with $_REQUEST['action']
        $details = "Admin added new menu item: " . $name; // Changed to $name to match your variable!
        
        logAction($pdo, $admin_id, $log_action, $details);
    }
    // ==========================================

    echo json_encode(['success' => $success]);
    exit();
}
?>