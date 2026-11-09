<?php
// I-force nga JSON gyud ang ipadala
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$host = '127.0.0.1';
$user = 'root';
$password = ''; 
$database = 'cafe_pos_db'; 

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode([["fullname" => "System", "action" => "Error", "details" => "DB Connection Failed", "created_at" => ""]]);
    exit();
}

// GI-UPDATE: Gigamitan natog LEFT JOIN aron isumpay ang users table ug makuha ang First Name ug Role
$sql = "SELECT l.id, l.user_id, l.action, l.details, l.created_at, u.first_name, u.last_name, u.role 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.id DESC LIMIT 100";
        
$result = $conn->query($sql);

if (!$result) {
    echo json_encode([["fullname" => "System", "action" => "Error", "details" => "SQL Error: " . $conn->error, "created_at" => ""]]);
    exit();
}

$logs = [];
while($row = $result->fetch_assoc()) {
    
    // LOGIC PARA SA PANGALAN:
    // Kung naay nakit-an nga user, i-format nga "Role Firstname" (e.g., "Admin Mariane")
    // Kung na-delete na ang user sa database, "User ID: X" ang mogawas para safe gihapon.
    if (!empty($row['first_name'])) {
        $fullname = $row['role'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
    } else {
        $fullname = 'User ID: ' . $row['user_id'];
    }
    
    $dateObj = date_create($row['created_at']);
    $formattedDate = $dateObj ? date_format($dateObj, 'M d, Y h:i A') : $row['created_at'];
    
    $logs[] = [
        "fullname" => trim($fullname),
        "action" => $row['action'],
        "details" => $row['details'],
        "created_at" => $formattedDate
    ];
}

echo json_encode($logs);
$conn->close();
?>