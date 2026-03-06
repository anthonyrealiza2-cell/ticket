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
    $checkStmt = $pdo->prepare("SELECT * FROM tickets_archive WHERE ticket_id = ?");
    $checkStmt->execute([$ticketId]);
    $archivedTicket = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$archivedTicket) {
        echo json_encode(['success' => false, 'message' => 'Archived ticket not found']);
        exit;
    }
    
    // Remove archive-specific columns before restoring
    unset($archivedTicket['archived_at']);
    unset($archivedTicket['archived_by']);
    unset($archivedTicket['archive_reason']);
    
    // Insert back to main table
    $columns = implode(', ', array_keys($archivedTicket));
    $placeholders = implode(', ', array_fill(0, count($archivedTicket), '?'));
    
    $restoreStmt = $pdo->prepare("INSERT INTO tickets ($columns) VALUES ($placeholders)");
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