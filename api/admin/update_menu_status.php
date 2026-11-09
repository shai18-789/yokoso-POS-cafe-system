<?php
session_start(); // MUST BE AT THE VERY TOP!
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../config/database.php'; // Use your standard config file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 0;

    if ($id > 0) {
        try {
            // Use $pdo directly to match your database.php connection
            $sql = "UPDATE products SET is_available = :status WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $success = $stmt->execute();

            if ($success) {
                // ==========================================
                // SYSTEM LOGS TRACKING (Safely inside the success zone!)
                // ==========================================
                $admin_id = $_SESSION['user_id'] ?? 0;
                $action = "Update Menu Status";
                
                // Make the log smarter! Tell us exactly what the admin did.
                $status_text = ($status === 1) ? "Available" : "Unavailable";
                $details = "Admin marked Menu ID: " . $id . " as " . $status_text;
                
                logAction($pdo, $admin_id, $action, $details);
                // ==========================================

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to execute query.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Menu ID.']);
    }
}
?>