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
    
    // Check if client exists (by company name only, ignoring contact person differences)
    $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE company_name = ?");
    $stmt->execute([$data['company_name']]);
    $client = $stmt->fetch();
    
    if ($client) {
        $clientId = $client['client_id'];
        
        // Update contact info if provided
        if (!empty($data['contact_number']) || !empty($data['email'])) {
            $updateStmt = $pdo->prepare("
                UPDATE clients 
                SET contact_number = COALESCE(?, contact_number),
                    email = COALESCE(?, email)
                WHERE client_id = ?
            ");
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
    $stmt = $pdo->prepare("SELECT concern_id FROM concerns WHERE concern_name = ?");
    $stmt->execute([$data['concern']]);
    $concern = $stmt->fetch();
    $concernId = $concern ? $concern['concern_id'] : 3; // Default to 3 (SOFTWARE SUPPORT)
    
    // Parse date
    $dateRequested = date('Y-m-d H:i:s', strtotime($data['date_requested']));
    
    // Insert ticket (description can be NULL)
    $stmt = $pdo->prepare("
        INSERT INTO tickets (
            company_id, product_id, concern_id,
            concern_description, date_requested, priority, status,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
    ");
    
    $stmt->execute([
        $clientId,
        $productId,
        $concernId,
        $data['description'], // This can be NULL or empty string
        $dateRequested,
        $data['priority']
    ]);
    
    $ticketId = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Ticket created successfully',
        'ticket_id' => $ticketId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>