<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_ids']) || !isset($data['priority'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketIds = $data['ticket_ids'];
$priority = $data['priority'];

try {
    $pdo->beginTransaction();
    
    $updated = 0;
    $errors = 0;
    
    $stmt = $pdo->prepare("UPDATE tickets SET priority = ? WHERE ticket_id = ?");
    
    foreach ($ticketIds as $ticketId) {
        try {
            $stmt->execute([$priority, $ticketId]);
            $updated++;
        } catch (Exception $e) {
            $errors++;
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