<?php
// 📍 Gidugang ang CORS headers aron dili i-block sa Chrome
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$user = 'root';
$password = ''; 
$database = 'cafe_pos_db'; 

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// 📍 GI-USAB NGA QUERY: Gi-join nato ang categories table aron makuha ang saktong category_name
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query error: " . $conn->error]);
    exit();
}

$menu_items = [];
while($row = $result->fetch_assoc()) {
    // Siguraduhon nato nga kung naay null, dili mag-error
    if (empty($row['category_name'])) {
        $row['category_name'] = 'Uncategorized';
    }
    
    $menu_items[] = $row;
}

echo json_encode($menu_items);
$conn->close();
?>