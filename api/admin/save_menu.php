<?php
session_start();
error_reporting(0); // Turn off hidden PHP errors so JSON doesn't break
header('Content-Type: application/json');

// 1. KINI ANG GIDUGANG: We require your database config to get $pdo and logAction()
require_once '../../config/database.php';

// We will keep your mysqli connection here so your image upload and menu saving doesn't break
$host = '127.0.0.1';
$user = 'root';
$password = ''; 
$database = 'cafe_pos_db';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Connection failed"]);
    exit();
}

// 1. GET DATA FROM FRONTEND
$id = $_POST['item_id'] ?? '';
$name = $_POST['product_name'] ?? '';
$cat_id = $_POST['category_id'] ?? 1;
$price = $_POST['price'] ?? 0;
$recipe = $_POST['recipe_text'] ?? '';

// Get large price
$price_large = (isset($_POST['price_large']) && trim($_POST['price_large']) !== '') ? (float)$_POST['price_large'] : null;

// ==========================================
// IMAGE UPLOAD LOGIC
// ==========================================
$imagePath = ''; 

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../assets/img/'; 
    $fileName = time() . '_' . basename($_FILES['image']['name']);
    $targetFilePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        $imagePath = 'assets/img/' . $fileName; 
    }
}

// ==========================================
// DATABASE SAVE LOGIC
// ==========================================
if ($id === '') {
    // ADD NEW PRODUCT
    if ($imagePath === '') {
        $imagePath = 'assets/img/placeholder.png'; 
    }
    
    $sql = "INSERT INTO products (category_id, product_name, price, price_large, image, recipe_text) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddss", $cat_id, $name, $price, $price_large, $imagePath, $recipe);
    
} else {
    // EDIT PRODUCT
    if ($imagePath !== '') {
        $sql = "UPDATE products SET category_id=?, product_name=?, price=?, price_large=?, image=?, recipe_text=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isddssi", $cat_id, $name, $price, $price_large, $imagePath, $recipe, $id);
    } else {
        $sql = "UPDATE products SET category_id=?, product_name=?, price=?, price_large=?, recipe_text=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isddsi", $cat_id, $name, $price, $price_large, $recipe, $id);
    }
}

if ($stmt->execute()) {
    // ==========================================
    // SYSTEM LOGS TRACKING (Fixed to use our global logAction)
    // ==========================================
    $admin_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 1;
    $actionType = ($id === '') ? 'Add Menu Item' : 'Edit Menu Item';
    $logDetails = ($id === '') ? "Admin added new product: $name" : "Admin updated product: $name";
    
    // We pass $pdo here specifically for the logAction function!
    logAction($pdo, $admin_id, $actionType, $logDetails);
    // ==========================================

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>