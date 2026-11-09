<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    $host = '127.0.0.1';
    $user = 'root';
    $password = ''; 
    $database = 'cafe_pos_db'; 

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        echo json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]);
        exit();
    }

    $conn->set_charset("utf8mb4");

    // SQL UPDATE: Nag-JOIN ta sa products table para ma-ihap ang items (item_count)
    // Nag-apil na sad ta sa 'status' para sa toggle
    $sql = "SELECT 
                c.id, 
                c.category_name, 
                c.status, 
                COUNT(p.id) AS item_count 
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.id ASC";
            
    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit();
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        // Siguraduhon nato nga integer ang status ug count
        $row['status'] = (int)$row['status'];
        $row['item_count'] = (int)$row['item_count'];
        $categories[] = $row;
    }

    echo json_encode($categories);

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["error" => "System Exception: " . $e->getMessage()]);
}
?>