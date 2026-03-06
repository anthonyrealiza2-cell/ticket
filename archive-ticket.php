<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketId = $data['ticket_id'];
$userId = $data['user_id'] ?? 1; // Default to admin if not specified
$reason = $data['reason'] ?? 'Manual archive';

try {
    $pdo->beginTransaction();
    
    // Get ticket details before archiving
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Insert into archive table
    $archiveStmt = $pdo->prepare("
        INSERT INTO tickets_archive 
        SELECT t.*, NOW() as archived_at, ? as archived_by, ? as archive_reason
        FROM tickets t 
        WHERE t.ticket_id = ?
    ");
    $archiveStmt->execute([$userId, $reason, $ticketId]);
    
    // Delete from main table
    $deleteStmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $deleteStmt->execute([$ticketId]);
    
    // Log the action (if activity_log table exists)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, ticket_id, details, created_at) 
            VALUES (?, 'ARCHIVE', ?, ?, NOW())
        ");
        $logStmt->execute([$userId, $ticketId, "Archived ticket #$ticketId: $reason"]);
    } catch (Exception $e) {
        // Activity log table might not exist, ignore error
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket archived successfully',
        'ticket_id' => $ticketId
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>