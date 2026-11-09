<?php
session_start();
header('Content-Type: application/json');

// Make sure this path matches exactly what you use in get_inventory.php
require_once '../../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;

    if ($id > 0 && $amount > 0) {
        try {
            // We use a transaction so if anything fails, it rolls back automatically
            $pdo->beginTransaction();

            // 1. Check if we have enough total stock in the main table
            $check_sql = "SELECT stock_quantity FROM inventory WHERE id = ?";
            $stmt = $pdo->prepare($check_sql);
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception("Item not found.");
            }
            if ($item['stock_quantity'] < $amount) {
                throw new Exception("Not enough stock! You only have " . $item['stock_quantity'] . " left.");
            }

            // 2. FIFO LOGIC: Fetch batches starting from the oldest/closest to expire
            $batch_sql = "SELECT id, quantity FROM inventory_batches WHERE inventory_id = ? AND quantity > 0 ORDER BY expiration_date ASC";
            $batch_stmt = $pdo->prepare($batch_sql);
            $batch_stmt->execute([$id]);
            $batches = $batch_stmt->fetchAll(PDO::FETCH_ASSOC);

            $remaining_to_deduct = $amount;

            foreach ($batches as $batch) {
                if ($remaining_to_deduct <= 0) break; // Stop if we deducted everything

                if ($batch['quantity'] <= $remaining_to_deduct) {
                    // If the batch has LESS OR EQUAL to what we need, empty the batch
                    $remaining_to_deduct -= $batch['quantity'];
                    $update_batch = $pdo->prepare("UPDATE inventory_batches SET quantity = 0 WHERE id = ?");
                    $update_batch->execute([$batch['id']]);
                } else {
                    // If the batch has MORE than what we need, deduct partially
                    $update_batch = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
                    $update_batch->execute([$remaining_to_deduct, $batch['id']]);
                    $remaining_to_deduct = 0; 
                }
            }

            // 3. Finally, update the total stock in the main inventory table
            $update_main = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $update_main->execute([$amount, $id]);

            // Save all changes
            $pdo->commit();

            // ==========================================
            // SYSTEM LOGS TRACKING (Safely inside the success zone!)
            // ==========================================
            $admin_id = $_SESSION['user_id'] ?? 0;
            $log_action = "Stock Out";
            $log_details = "Admin deducted " . $amount . " items from Inventory ID: " . $id;
            logAction($pdo, $admin_id, $log_action, $log_details);
            // ==========================================

            echo json_encode(["success" => true, "message" => "Stock deducted successfully using FIFO."]);

        } catch (Exception $e) {
            // Cancel changes if there's an error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Please enter a valid amount."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
}
?>