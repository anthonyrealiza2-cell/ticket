<?php
header('Content-Type: application/json');
require_once 'database.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
// Validate required fields (email is now optional)
$required = ['company_name', 'contact_person', 'contact_number', 'description', 'priority', 'date_requested'];
foreach($required as $field) {
    if(empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

// Email is optional, but if provided must be valid
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => "Invalid email format"]);
    exit;
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
    
    // Insert ticket with new normalized structure
    $sql = "INSERT INTO tickets (company_id, concern_description, date_requested, priority, status, 
            product_id, concern_id, created_at) VALUES 
            (:company_id, :concern_description, :date_requested, :priority, 'Pending', 
            :product_id, :concern_id, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':company_id' => $clientId,
        ':concern_description' => $data['description'],
        ':date_requested' => $data['date_requested'],
        ':priority' => $data['priority'],
        ':product_id' => $productId,
        ':concern_id' => $concernId
    ]);
    
    $pdo->commit();
    
    if($result) {
        echo json_encode(['success' => true, 'message' => 'Ticket created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket']);
    }
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>