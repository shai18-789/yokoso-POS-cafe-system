<?php
/*class Logger {
    public static function log($pdo, $userId, $action, $details) {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $details]);
    }
}*/

// Example usage sa api/auth/register.php:
// Logger::log($pdo, $_SESSION['user_id'], 'CREATE_USER', "Created new user: $username");



class Logger {
    public static function log($db, $userId, $action, $details) {
        // 1. Check kung ang koneksyon kay PDO (Kini ang gamit sa imong Login)
        if ($db instanceof PDO) {
            $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->execute([$userId, $action, $details]);
            }
        } 
        // 2. Check kung ang koneksyon kay mysqli (Kini ang gamit sa imong Menu ug POS)
        elseif ($db instanceof mysqli) {
            $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $userId, $action, $details);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>