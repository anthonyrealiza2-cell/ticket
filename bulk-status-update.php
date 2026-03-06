<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_ids']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketIds = $data['ticket_ids'];
$status = $data['status'];
$solution = $data['solution'] ?? '';

try {
    $pdo->beginTransaction();
    
    $updated = 0;
    $errors = 0;
    
    if (in_array($status, ['Resolved', 'Closed'])) {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = ?, 
                solution = ?,
                finish_date = NOW()
            WHERE ticket_id = ?
        ");
        
        foreach ($ticketIds as $ticketId) {
            try {
                $stmt->execute([$status, $solution, $ticketId]);
                $updated++;
            } catch (Exception $e) {
                $errors++;
            }
        }
    } else {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = ?,
                finish_date = NULL
            WHERE ticket_id = ?
        ");
        
        foreach ($ticketIds as $ticketId) {
            try {
                $stmt->execute([$status, $ticketId]);
                $updated++;
            } catch (Exception $e) {
                $errors++;
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'errors' => $errors,
        'message' => "Successfully updated $updated tickets"
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>