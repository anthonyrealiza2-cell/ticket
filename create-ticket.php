<?php
header('Content-Type: application/json');
require_once 'database.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['company_name', 'contact_person', 'contact_number', 'email', 'description', 'priority', 'date_requested'];
foreach($required as $field) {
    if(empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if client already exists
    $checkClient = $pdo->prepare("SELECT client_id FROM clients WHERE company_name = ? AND email = ?");
    $checkClient->execute([$data['company_name'], $data['email']]);
    $existingClient = $checkClient->fetch();
    
    // If client doesn't exist, add them
    if(!$existingClient) {
        $addClient = $pdo->prepare("INSERT INTO clients (company_name, contact_person, contact_number, email, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())");
        $addClient->execute([
            $data['company_name'],
            $data['contact_person'],
            $data['contact_number'],
            $data['email']
        ]);
        $clientId = $pdo->lastInsertId();
    } else {
        $clientId = $existingClient['client_id'];
        
        // Update client info if changed
        $updateClient = $pdo->prepare("UPDATE clients SET contact_person = ?, contact_number = ? WHERE client_id = ?");
        $updateClient->execute([
            $data['contact_person'],
            $data['contact_number'],
            $clientId
        ]);
    }
    
    // Combine product and concern for the concern field
    $concern_text = "Product: " . ($data['product'] ?? 'N/A') . "\n";
    $concern_text .= "Concern Type: " . ($data['concern'] ?? 'N/A') . "\n";
    $concern_text .= "Description: " . $data['description'];
    
    // Insert ticket with company_id
    $sql = "INSERT INTO tickets (company_name, company_id, contact_person, contact_number, email, 
            concern, date_requested, priority, status, created_at) VALUES 
            (:company_name, :company_id, :contact_person, :contact_number, :email, 
            :concern, :date_requested, :priority, 'Pending', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':company_name' => $data['company_name'],
        ':company_id' => $clientId,
        ':contact_person' => $data['contact_person'],
        ':contact_number' => $data['contact_number'],
        ':email' => $data['email'],
        ':concern' => $concern_text,
        ':date_requested' => $data['date_requested'],
        ':priority' => $data['priority']
    ]);
    
    $pdo->commit();
    
    if($result) {
        echo json_encode(['success' => true, 'message' => 'Ticket created successfully and client updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket']);
    }
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>