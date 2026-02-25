<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO technical_staff (firstname, lastname, email, contact_viber, branch, position, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $data['firstname'],
        $data['lastname'],
        $data['email'],
        $data['contact'],
        $data['branch'],
        $data['position']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Technical staff added successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>