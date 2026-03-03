<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get ticket info before deletion (for logging if needed)
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$data['ticket_id']]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Delete the ticket
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$data['ticket_id']]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>