<?php
// SAKTO NGA PATH PARA SA IMONG DATABASE
include '../../config/database.php'; 

header('Content-Type: application/json');

try {
    // I-check nato kung MySQLi o PDO imong gamit para walay error
    $recipes = [];

    // Kung nag-gamit kag $conn (MySQLi)
    if (isset($conn)) {
        $query = "SELECT product_name, recipe_text FROM products ORDER BY product_name ASC";
        $result = $conn->query($query);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $recipes[] = $row;
            }
        }
    } 
    // Kung nag-gamit kag $pdo (PDO)
    else if (isset($pdo)) {
        $stmt = $pdo->query("SELECT product_name, recipe_text FROM products ORDER BY product_name ASC");
        $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($recipes);

} catch (Exception $e) {
    // Kung naay error, i-print ang error aron makita nato
    echo json_encode(['error' => $e->getMessage()]);
}
?>