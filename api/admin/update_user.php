<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Siguroha nga Admin ra ang maka-edit
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    // GIKUHA NATO ANG 3 KA BAG-ONG NGALAN GIKAN SA FORM
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    $password = $_POST['password'] ?? ''; // Optional nga usbon

    // VALIDATION: Gi-require ang First ug Last name (Puwede ra blangko ang Middle)
    if (empty($id) || empty($first_name) || empty($last_name) || empty($username) || empty($role) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Palihog isulod ang tanang detalye (First ug Last Name required).']);
        exit;
    }

    try {
        if (!empty($password)) {
            // Update APIL ang password kung naay gi-type
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, username = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$first_name, $middle_name, $last_name, $username, $role, $status, $hashed_password, $id]);
        } else {
            // Update ra sa detalye, DILI usbon ang password kung blangko
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, username = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$first_name, $middle_name, $last_name, $username, $role, $status, $id]);
        }

        // ==========================================
        // SYSTEM LOGS TRACKING (Safely inside the try block!)
        // ==========================================
        $admin_id = $_SESSION['user_id'] ?? 0;
        $action = "Edit User";
        // I added the $id here so you know exactly which account was edited!
        $details = "Admin updated account details for username: " . $username . " (ID: " . $id . ")";
        logAction($pdo, $admin_id, $action, $details);
        // ==========================================

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>