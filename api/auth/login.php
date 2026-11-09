<?php
session_start();
require_once '../../config/database.php';
require_once '../../core/logger.php'; 
header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) { 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            
            // ANG FIX: Gi-combine nato ang first ug last name kay wala na tay fullname sa database!
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['name']    = $fullName;

            Logger::log($pdo, $user['id'], 'Login', $user['username'] . ' logged into the system.');
            
            $redirect = '';
            if ($user['role'] == 'Admin') {
                // I-redirect padulong sa Dashboard
                $redirect = 'views/admin/index.php'; 
            } else if ($user['role'] == 'Cashier') {
                $redirect = 'views/cashier/index.php';
            } else {
                $redirect = 'views/barista/index.php';
            }

            echo json_encode(['success' => true, 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sayop ang password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Username not found.']);
    }
}
?>