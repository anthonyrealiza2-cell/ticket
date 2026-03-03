<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['client_id']) || !isset($data['remarks'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Get company name
    $clientStmt = $pdo->prepare("SELECT company_name FROM clients WHERE client_id = ?");
    $clientStmt->execute([$data['client_id']]);
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Client not found']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO remarks (client_id, company, date_requested, scheduled_date, finished_date, remarks) 
        VALUES (?, ?, NOW(), ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['client_id'],
        $client['company_name'],
        $data['scheduled_date'] ?? null,
        $data['finished_date'] ?? null,
        $data['remarks']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Remark added successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>