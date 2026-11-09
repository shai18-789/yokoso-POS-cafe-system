<?php
session_start();
require_once '../../config/database.php';

// Safe include para sa Logger
$logger_path = '../admin/logger.php';
if (file_exists($logger_path)) {
    include_once $logger_path;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));
$receipt_no = $data->receipt_no ?? '';
$pin = $data->pin ?? '';
$reason = $data->reason ?? 'No Reason Provided'; // 📍 Gidawat nato ang rason

$ADMIN_PIN = "12345"; 

if (empty($receipt_no) || empty($pin)) {
    echo json_encode(['success' => false, 'message' => 'Nawala ang Resibo o ang PIN.']);
    exit;
}

if ($pin !== $ADMIN_PIN) {
    echo json_encode(['success' => false, 'message' => 'Sayop ang Admin PIN!']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. I-check daan
    $stmt = $pdo->prepare("SELECT id, status, total_amount FROM sales WHERE receipt_no = ?");
    $stmt->execute([$receipt_no]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception("Wala makit-i ang resibo.");
    }
    if ($sale['status'] === 'Voided') {
        throw new Exception("Kini nga order na-void na daan!");
    }

    // 2. I-update ang sales ngadto sa Voided
    $stmt = $pdo->prepare("UPDATE sales SET status = 'Voided' WHERE receipt_no = ?");
    $stmt->execute([$receipt_no]);
    
    // I-update sad ang orders table
    $stmtOrd = $pdo->prepare("UPDATE orders SET status = 'Voided' WHERE total_amount = ? AND status != 'Voided' LIMIT 1");
    $stmtOrd->execute([$sale['total_amount']]);

    // 3. I-save sa System Logs para makita sa Admin!
    $loggedInUser = $_SESSION['user_id'] ?? 0;
    if (class_exists('Logger')) {
        $logMessage = "VOIDED Receipt: $receipt_no | Reason: $reason | Approved via PIN";
        Logger::log($pdo, $loggedInUser, 'Void Transaction', $logMessage);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Na-void na ang order!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>