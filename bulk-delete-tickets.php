<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketIds = $data['ticket_ids'];
$userId = $data['user_id'] ?? 1; // Default to admin if not specified
$reason = $data['reason'] ?? 'Bulk archive';

try {
    $pdo->beginTransaction();
    
    $archived = 0;
    $errors = 0;
    $failedIds = [];
    
    // Prepare statements
    $archiveStmt = $pdo->prepare("
        INSERT INTO tickets_archive (
            ticket_id, company_id, technical_personnel, technical_id, 
            assigned_date, product_id, concern_id, concern_description, 
            date_requested, submitted_date, finish_date, solution, 
            remarks, priority, status, assigned, 
            created_at, updated_at, archived_at, archived_by, archive_reason
        )
        SELECT 
            t.ticket_id, t.company_id, t.technical_personnel, t.technical_id, 
            t.assigned_date, t.product_id, t.concern_id, t.concern_description, 
            t.date_requested, t.submitted_date, t.finish_date, t.solution, 
            t.remarks, t.priority, t.status, t.assigned, 
            t.created_at, t.updated_at, NOW() as archived_at, ? as archived_by, ? as archive_reason
        FROM tickets t 
        WHERE t.ticket_id = ?
    ");
    
    $deleteStmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    
    foreach ($ticketIds as $ticketId) {
        try {
            // First check if ticket exists
            $checkStmt = $pdo->prepare("SELECT ticket_id FROM tickets WHERE ticket_id = ?");
            $checkStmt->execute([$ticketId]);
            
            if ($checkStmt->rowCount() > 0) {
                // Archive the ticket
                $archiveStmt->execute([$userId, $reason, $ticketId]);
                
                // Delete from main table
                $deleteStmt->execute([$ticketId]);
                
                $archived++;
            } else {
                $failedIds[] = $ticketId;
                $errors++;
            }
            
        } catch (Exception $e) {
            $errors++;
            $failedIds[] = $ticketId;
        }
    }
    
    // Log bulk action
    if ($archived > 0) {
        $logStmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, created_at) 
            VALUES (?, 'BULK_ARCHIVE', ?, NOW())
        ");
        $logStmt->execute([
            $userId, 
            "Bulk archived $archived tickets. Failed: " . implode(',', $failedIds)
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'archived' => $archived,
        'errors' => $errors,
        'failed_ids' => $failedIds,
        'message' => "Successfully archived $archived tickets" . ($errors > 0 ? ", $errors failed" : "")
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>