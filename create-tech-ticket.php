<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if client exists
    $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE company_name = ? AND contact_person = ?");
    $stmt->execute([$data['company_name'], $data['contact_person']]);
    $client = $stmt->fetch();
    
    if ($client) {
        $clientId = $client['client_id'];
        
        // Update contact number if provided
        if (!empty($data['contact_number'])) {
            $updateStmt = $pdo->prepare("UPDATE clients SET contact_number = ?, email = ? WHERE client_id = ?");
            $updateStmt->execute([$data['contact_number'], $data['email'], $clientId]);
        }
    } else {
        // Create new client
        $stmt = $pdo->prepare("INSERT INTO clients (company_name, contact_person, contact_number, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['company_name'], $data['contact_person'], $data['contact_number'], $data['email']]);
        $clientId = $pdo->lastInsertId();
    }
    
    // Get product ID
    $productName = explode(' v', $data['product'])[0];
    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_name LIKE ? LIMIT 1");
    $stmt->execute(['%' . $productName . '%']);
    $product = $stmt->fetch();
    $productId = $product ? $product['product_id'] : 1;
    
    // Get concern ID
    $stmt = $pdo->prepare("SELECT concern_id FROM concerns WHERE concern_name LIKE ? LIMIT 1");
    $stmt->execute(['%' . $data['concern'] . '%']);
    $concern = $stmt->fetch();
    $concernId = $concern ? $concern['concern_id'] : 3;
    
    // Parse date
    $dateRequested = date('Y-m-d H:i:s', strtotime($data['date_requested']));
    
    // Insert ticket
    $stmt = $pdo->prepare("
        INSERT INTO tickets (
            company_id, technical_id, product_id, concern_id,
            concern_description, date_requested, priority, status,
            assigned, assigned_date, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'In Progress', 1, NOW(), NOW(), NOW())
    ");
    
    $stmt->execute([
        $clientId,
        $data['technical_id'],
        $productId,
        $concernId,
        $data['description'],
        $dateRequested,
        $data['priority']
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Ticket created successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>