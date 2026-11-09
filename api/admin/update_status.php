<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? ''; // Usually 'Active' or 'Inactive'

if ($id && $status) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        
        // If the database successfully updates...
        if ($stmt->execute([$status, $id])) {
            
            // ==========================================
            // SYSTEM LOGS TRACKING (Safely inside the success zone!)
            // ==========================================
            $admin_id = $_SESSION['user_id'] ?? 0;
            $action = "Update User Status";
            
            // This will now say: "Admin changed User ID: 5 status to Inactive"
            $details = "Admin changed User ID: " . $id . " status to " . $status;
            
            logAction($pdo, $admin_id, $action, $details);
            // ==========================================

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status.']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
}
?>