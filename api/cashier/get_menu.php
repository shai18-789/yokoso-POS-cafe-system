<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    // JOIN categories so category_name is included in result
    $query = "
        SELECT p.*,
               c.category_name,
               (SELECT MIN(i.stock_quantity / pi.amount_needed)
                FROM product_ingredients pi
                JOIN inventory i ON pi.inventory_id = i.id
                WHERE pi.product_id = p.id) as stock_level
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY c.id, p.product_name ASC";

    $stmt = $pdo->query($query);
    $menu = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // If stock_level <= 0 AND product has ingredients, mark unavailable
        // If no ingredients set (NULL), keep is_available as-is
        if ($row['stock_level'] !== null && $row['stock_level'] <= 0) {
            $row['is_available'] = 0;
        }
        $menu[] = $row;
    }

    echo json_encode($menu);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>