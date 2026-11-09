<?php
session_start();
// Gamiton nato ang imong standard nga database connection
require_once '../../config/database.php'; 

header('Content-Type: application/json');

$start_date = $_GET['start'] ?? '';
$end_date = $_GET['end'] ?? '';

try {
    $sql = "SELECT * FROM sales";
    $params = []; // Diri nato isulod ang mga dates aron safe

    // I-filter by Date kung gibutangan sa user
    if (!empty($start_date) && !empty($end_date)) {
        // Mogamit tag '?' aron dili ma-SQL injection
        $sql .= " WHERE sale_date >= ? AND sale_date <= ?";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
    }

    $sql .= " ORDER BY sale_date DESC"; // Pinaka-latest sa taas

    // I-prepare ug i-execute (Kini ang safe nga paagi!)
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Kuhaon tanan results as array
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sales);

} catch (PDOException $e) {
    // Kung naay error sa database, i-send as JSON aron dili mo-crash ang Javascript
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>