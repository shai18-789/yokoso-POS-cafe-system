<?php
require_once '../../config/database.php';

// Fetch all active orders and their items
$query = "SELECT o.id, o.status, 
          GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ') as items_summary
          FROM orders o
          JOIN order_items oi ON o.id = oi.order_id
          JOIN products p ON oi.product_id = p.id
          WHERE o.status NOT IN ('Completed', 'Cancelled')
          GROUP BY o.id
          ORDER BY o.created_at ASC";

$stmt = $pdo->query($query);
echo json_encode($stmt->fetchAll());