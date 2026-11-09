<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Cashier'; // Default role
    $status = 'Active'; // Default status sa bag-ong user

    if (empty($first_name) || empty($last_name) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Palihog isulod ang tanang detalye.']);
        exit;
    }

    try {
        // Check if the username already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists!']);
            exit;
        }

        // Hash the password and save to database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $middle_name, $last_name, $username, $hashed_password, $role, $status]);
        
        // ==========================================
        // SYSTEM LOGS TRACKING (Safely inside the success zone!)
        // ==========================================
        // Grab the ID of the Admin who is creating this account
        $admin_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 1;
        $action = "Add New User";
        
        // This will dynamically log: "Admin created new Cashier account: shaina"
        $details = "Admin created new " . $role . " account: " . $username;
        
        logAction($pdo, $admin_id, $action, $details);
        // ==========================================

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>