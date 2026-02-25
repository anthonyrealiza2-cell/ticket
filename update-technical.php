<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("UPDATE technical_staff SET 
                           firstname = ?, 
                           lastname = ?, 
                           email = ?, 
                           contact_viber = ?, 
                           branch = ?, 
                           position = ? 
                           WHERE technical_id = ?");
    
    $stmt->execute([
        $data['firstname'],
        $data['lastname'],
        $data['email'],
        $data['contact'],
        $data['branch'],
        $data['position'],
        $data['technical_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Technical staff updated successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>