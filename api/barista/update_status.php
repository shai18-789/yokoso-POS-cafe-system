<?php
session_start();
require_once '../../config/database.php';

$order_id = $_POST['order_id'] ?? '';
$receipt_no = $_POST['receipt_no'] ?? '';
$new_status = $_POST['status'] ?? ''; 

if (empty($order_id) || empty($receipt_no) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Kulang ang data.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. I-update diritso ang orders table gamit ang order_id
    $stmt1 = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt1->execute([$new_status, $order_id]);

    // 2. I-update diritso ang sales table gamit ang receipt_no
    $stmt2 = $pdo->prepare("UPDATE sales SET status = ? WHERE receipt_no = ?");
    $stmt2->execute([$new_status, $receipt_no]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>