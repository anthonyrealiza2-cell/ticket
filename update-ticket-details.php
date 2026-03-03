<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if client exists with this company name
    $checkClient = $pdo->prepare("SELECT client_id FROM clients WHERE company_name = ?");
    $checkClient->execute([$data['company_name']]);
    $existingClient = $checkClient->fetch();
    
    if ($existingClient) {
        // Client exists, use existing client_id
        $clientId = $existingClient['client_id'];
        
        // Update existing client information
        $updateClient = $pdo->prepare("
            UPDATE clients 
            SET contact_person = ?, 
                contact_number = ?, 
                email = ? 
            WHERE client_id = ?
        ");
        $updateClient->execute([
            $data['contact_person'],
            $data['contact_number'],
            $data['email'] ?? null,
            $clientId
        ]);
    } else {
        // Create new client
        $insertClient = $pdo->prepare("
            INSERT INTO clients (company_name, contact_person, contact_number, email, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insertClient->execute([
            $data['company_name'],
            $data['contact_person'],
            $data['contact_number'],
            $data['email'] ?? null
        ]);
        $clientId = $pdo->lastInsertId();
    }
    
    // Update ticket with new company_id
    $updateTicketCompany = $pdo->prepare("UPDATE tickets SET company_id = ? WHERE ticket_id = ?");
    $updateTicketCompany->execute([$clientId, $data['ticket_id']]);
    
    // Get product_id from product name/version
    $productId = null;
    if (!empty($data['product'])) {
        $productName = explode(' v', $data['product'])[0];
        $version = strpos($data['product'], ' v') !== false ? explode(' v', $data['product'])[1] : null;
        
        $productStmt = $pdo->prepare("SELECT product_id FROM products WHERE product_name = ? AND (version = ? OR ? IS NULL)");
        $productStmt->execute([$productName, $version, $version]);
        $product = $productStmt->fetch();
        $productId = $product ? $product['product_id'] : null;
    }
    
    // Get concern_id from concern name
    $concernId = null;
    if (!empty($data['concern'])) {
        $concernStmt = $pdo->prepare("SELECT concern_id FROM concerns WHERE concern_name = ?");
        $concernStmt->execute([$data['concern']]);
        $concern = $concernStmt->fetch();
        $concernId = $concern ? $concern['concern_id'] : null;
    }
    
    // Update ticket details
    $updateTicket = $pdo->prepare("
        UPDATE tickets 
        SET product_id = ?,
            concern_id = ?,
            concern_description = ?,
            priority = ?,
            date_requested = ?
        WHERE ticket_id = ?
    ");
    $updateTicket->execute([
        $productId,
        $concernId,
        $data['description'],
        $data['priority'],
        $data['date_requested'],
        $data['ticket_id']
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>