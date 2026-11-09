<?php
/*header('Content-Type: application/json');

// I-adjust lang ni kung lahi imong settings
$host = '127.0.0.1';
$user = 'root';
$password = ''; 
$database = 'cafe_pos_db';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// ANG BAG-ONG QUERY
// Dapat ingon ani ang pagka-select sa mga ngalan:
$sql = "SELECT id, first_name, middle_name, last_name, username, role, status FROM users ORDER BY id DESC";
$result = $conn->query($sql);

$users = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
$conn->close();
?>*/

header('Content-Type: application/json');

// Database Connection
require_once '../../config/database.php'; // I-adjust lang kung lahi ang path sa imong db config

if (!$pdo) {
    echo json_encode(["error" => "Database connection failed!"]);
    exit();
}

try {
    // KINI ANG PINAKA-IMPORTANTE NGA LINYA: Gikuha nato ang 3 ka bag-ong ngalan
    $stmt = $pdo->query("SELECT id, first_name, middle_name, last_name, username, role, status FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
?>