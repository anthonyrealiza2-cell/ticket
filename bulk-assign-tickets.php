<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_ids']) || !isset($data['technical_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$ticketIds = $data['ticket_ids'];
$technicalId = $data['technical_id'];

try {
    $pdo->beginTransaction();
    
    $updated = 0;
    $errors = 0;
    
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET technical_id = ?, 
            assigned = 1,
            assigned_date = NOW(),
            status = CASE 
                WHEN status = 'Pending' THEN 'Assigned'
                ELSE status
            END
        WHERE ticket_id = ?
    ");
    
    foreach ($ticketIds as $ticketId) {
        try {
            $stmt->execute([$technicalId, $ticketId]);
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
        'message' => "Successfully assigned $updated tickets"
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>