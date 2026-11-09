<?php
session_start();
require_once '../../config/database.php';

// I-set nato ang saktong oras sa Pilipinas aron dili ma-late ang pag-ihap sa petsa
date_default_timezone_set('Asia/Manila');

try {
    // Gidugang nato ang DATEDIFF() aron SQL na ang mo-kwenta pila ka adlaw nabilin
    $sql = "SELECT i.*, 
            (SELECT expiration_date FROM inventory_batches b WHERE b.inventory_id = i.id AND b.quantity > 0 ORDER BY expiration_date ASC LIMIT 1) as next_expiration,
            (SELECT quantity FROM inventory_batches b WHERE b.inventory_id = i.id AND b.quantity > 0 ORDER BY expiration_date ASC LIMIT 1) as expiring_quantity,
            DATEDIFF(
                (SELECT expiration_date FROM inventory_batches b WHERE b.inventory_id = i.id AND b.quantity > 0 ORDER BY expiration_date ASC LIMIT 1), 
                CURDATE()
            ) as days_to_expire
            FROM inventory i ORDER BY i.item_name ASC";
            
    $stmt = $pdo->query($sql);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // KINI ANG BAG-O: I-tag daan sa PHP kung unsa ang status para sayon na lang sa Javascript!
    foreach ($inventory as &$item) {
        $item['expiration_status'] = 'Good'; // Default status
        
        if ($item['next_expiration']) {
            $days = (int)$item['days_to_expire'];
            
            if ($days < 0) {
                $item['expiration_status'] = 'Expired';
            } elseif ($days === 0) {
                $item['expiration_status'] = 'Expires Today';
            } elseif ($days <= 7) { // I-warning kung 7 days or less na lang
                $item['expiration_status'] = 'Expiring Soon';
            }
        } else {
             $item['expiration_status'] = 'No Expiration';
        }
    }

    echo json_encode($inventory);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>