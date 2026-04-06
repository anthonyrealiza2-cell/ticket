<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketId = $data['ticket_id'];
$userId = $data['user_id'] ?? 1;

try {
    $pdo->beginTransaction();
    
    // Check if ticket exists in archive
    $checkStmt = $pdo->prepare("
        SELECT 
            ticket_id, company_id, technical_personnel, technical_id, 
            assigned_date, product_id, concern_id, concern_description, 
            date_requested, submitted_date, finish_date, solution, 
            remarks, priority, status, assigned, created_at, updated_at 
        FROM tickets_archive 
        WHERE ticket_id = ?
    ");
    $checkStmt->execute([$ticketId]);
    $archivedTicket = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$archivedTicket) {
        echo json_encode(['success' => false, 'message' => 'Archived ticket not found']);
        exit;
    }
    
    // Insert back to main table
    $restoreStmt = $pdo->prepare("
        INSERT INTO tickets (
            ticket_id, company_id, technical_personnel, technical_id, 
            assigned_date, product_id, concern_id, concern_description, 
            date_requested, submitted_date, finish_date, solution, 
            remarks, priority, status, assigned, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $restoreStmt->execute(array_values($archivedTicket));
    
    // Delete from archive
    $deleteStmt = $pdo->prepare("DELETE FROM tickets_archive WHERE ticket_id = ?");
    $deleteStmt->execute([$ticketId]);
    
    // Log the action
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, ticket_id, details, created_at) 
        VALUES (?, 'RESTORE', ?, ?, NOW())
    ");
    $logStmt->execute([$userId, $ticketId, "Restored ticket #$ticketId from archive"]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket restored successfully',
        'ticket_id' => $ticketId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>