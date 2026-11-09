<?php
$host = 'localhost';
$db   = 'cafe_pos_db'; // Siguroha nga mao ni ang ngalan sa imong DB sa phpMyAdmin
$user = 'root';        // Default sa XAMPP
$pass = '';            // Default sa XAMPP (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Kini mogawas sa imong Network tab kon mapakyas ang connection
    die("Connection failed: " . $e->getMessage());
}
// Add this at the bottom of your config/database.php file
function logAction($pdo, $user_id, $action, $details) {
    try {
        // 1. FALLBACK PARA SA SESSION ID: Pangitaon niya tanang posibleng ngalan sa imong ID
        if (empty($user_id) || $user_id == 0) {
            $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 1; // 1 (Default Admin ID kung wala gyud makit-an)
        }

        // 2. COLUMN NAME FIX: Atong giilisan og 'created_at' kay mao may naa sa imong Javascript
        $sql = "INSERT INTO system_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details]);

    } catch (PDOException $e) {
        // 3. ERROR TRACKER: Kung mo-error gihapon, isulat niya sa usa ka text file aron atong makita!
        file_put_contents(__DIR__ . '/log_error.txt', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    }
}