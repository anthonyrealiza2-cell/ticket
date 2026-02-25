<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO clients (company_name, contact_person, contact_number, email, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([
        $data['company_name'],
        $data['contact_person'],
        $data['contact_number'],
        $data['email']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Client added successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>