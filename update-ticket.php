<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    if($data['action'] === 'assign') {
        // Check if already assigned to different tech
        $checkStmt = $pdo->prepare("SELECT technical_id FROM tickets WHERE ticket_id = ?");
        $checkStmt->execute([$data['ticket_id']]);
        $oldTechId = $checkStmt->fetch()['technical_id'];
        
        // If reassigning to different tech
        if($oldTechId && $oldTechId != $data['technical_id']) {
            // Decrement old tech's stats
            $updateOld = $pdo->prepare("UPDATE technical_staff SET total_ticket = total_ticket - 1 WHERE technical_id = ?");
            $updateOld->execute([$oldTechId]);
        }
        
        // Assign new technical staff
        $stmt = $pdo->prepare("UPDATE tickets SET technical_id = ?, status = 'Assigned', assigned = TRUE WHERE ticket_id = ?");
        $stmt->execute([$data['technical_id'], $data['ticket_id']]);
        
        // If this is a new assignment (no previous tech)
        if(!$oldTechId) {
            // Update new tech's stats
            $updateNew = $pdo->prepare("UPDATE technical_staff SET total_ticket = total_ticket + 1 WHERE technical_id = ?");
            $updateNew->execute([$data['technical_id']]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Ticket assigned successfully']);
    } 
    else if($data['action'] === 'status') {
        // Get current ticket info
        $ticketStmt = $pdo->prepare("SELECT technical_id, status FROM tickets WHERE ticket_id = ?");
        $ticketStmt->execute([$data['ticket_id']]);
        $ticket = $ticketStmt->fetch();
        $oldStatus = $ticket['status'];
        $techId = $ticket['technical_id'];
        
        // Update status
        $finishDate = ($data['status'] === 'Resolved' || $data['status'] === 'Closed') ? 'NOW()' : 'NULL';
        $sql = "UPDATE tickets SET status = ?, solution = ?, finish_date = " . $finishDate . " WHERE ticket_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['status'], $data['solution'], $data['ticket_id']]);
        
        // Update technical staff stats based on status changes
        if($techId) {
            // If resolving a ticket
            if($data['status'] === 'Resolved' && $oldStatus !== 'Resolved') {
                $stmt = $pdo->prepare("UPDATE technical_staff SET resolve = resolve + 1 WHERE technical_id = ?");
                $stmt->execute([$techId]);
            }
            // If closing a ticket (from resolved to closed)
            else if($data['status'] === 'Closed' && $oldStatus === 'Resolved') {
                $stmt = $pdo->prepare("UPDATE technical_staff SET resolve = resolve - 1, unresolve = unresolve + 1 WHERE technical_id = ?");
                $stmt->execute([$techId]);
            }
            // If reopening a ticket (from closed to something else)
            else if($oldStatus === 'Closed' && $data['status'] !== 'Closed') {
                $stmt = $pdo->prepare("UPDATE technical_staff SET resolve = resolve + 1, unresolve = unresolve - 1 WHERE technical_id = ?");
                $stmt->execute([$techId]);
            }
            // If moving from resolved to in progress
            else if($oldStatus === 'Resolved' && $data['status'] !== 'Resolved' && $data['status'] !== 'Closed') {
                $stmt = $pdo->prepare("UPDATE technical_staff SET resolve = resolve - 1 WHERE technical_id = ?");
                $stmt->execute([$techId]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>