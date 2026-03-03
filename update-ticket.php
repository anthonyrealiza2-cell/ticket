<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Log received data for debugging (optional - remove in production)
error_log("Update ticket received: " . print_r($data, true));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    if($data['action'] === 'assign') {
        // Check if ticket exists
        $checkStmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
        $checkStmt->execute([$data['ticket_id']]);
        $ticket = $checkStmt->fetch();
        
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
        }
        
        $oldTechId = $ticket['technical_id'];
        $currentStatus = $ticket['status'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // If reassigning to different tech
        if($oldTechId && $oldTechId != $data['technical_id']) {
            // Update ticket with new technical staff and status
            $stmt = $pdo->prepare("UPDATE tickets SET 
                                   technical_id = ?, 
                                   status = 'In Progress', 
                                   assigned = TRUE,
                                   assigned_date = NOW() 
                                   WHERE ticket_id = ?");
            $stmt->execute([$data['technical_id'], $data['ticket_id']]);
        } 
        // New assignment (no previous tech)
        else if(!$oldTechId) {
            // Assign new technical staff
            $stmt = $pdo->prepare("UPDATE tickets SET 
                                   technical_id = ?, 
                                   status = 'In Progress', 
                                   assigned = TRUE,
                                   assigned_date = NOW() 
                                   WHERE ticket_id = ?");
            $stmt->execute([$data['technical_id'], $data['ticket_id']]);
        }
        // Same tech - just update status if needed
        else {
            // If status is not already 'In Progress', update it
            if($currentStatus !== 'In Progress') {
                $stmt = $pdo->prepare("UPDATE tickets SET status = 'In Progress' WHERE ticket_id = ?");
                $stmt->execute([$data['ticket_id']]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Ticket assigned and now In Progress']);
    } 
    else if($data['action'] === 'status') {
        // Get current ticket info
        $ticketStmt = $pdo->prepare("SELECT technical_id, status FROM tickets WHERE ticket_id = ?");
        $ticketStmt->execute([$data['ticket_id']]);
        $ticket = $ticketStmt->fetch();
        
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
        }
        
        $oldStatus = $ticket['status'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update status
        if ($data['status'] === 'Resolved' || $data['status'] === 'Closed') {
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, solution = ?, finish_date = NOW() WHERE ticket_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, solution = ?, finish_date = NULL WHERE ticket_id = ?");
        }
        $stmt->execute([$data['status'], $data['solution'] ?? '', $data['ticket_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch(PDOException $e) {
    // Rollback transaction if started
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>