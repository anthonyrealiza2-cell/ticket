<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require_once 'database.php';

// Log the request for debugging
error_log("Import request received");

// Get the JSON data
$input = file_get_contents('php://input');
error_log("Input data: " . $input);

$data = json_decode($input, true);

if (!$data || !isset($data['records']) || !is_array($data['records'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid data received',
        'debug' => [
            'data_received' => $data,
            'input_raw' => substr($input, 0, 500)
        ]
    ]);
    exit;
}

$records = $data['records'];
$duplicateHandling = $data['duplicate_handling'] ?? 'skip';
$skipEmptyRows = $data['skip_empty_rows'] ?? true;

try {
    // Check if database connection works
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    $pdo->beginTransaction();
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    $emptyRows = 0;
    $details = [];
    
    // Get valid concern types from database for validation
    $validConcerns = [];
    $concernStmt = $pdo->query("SELECT concern_id, concern_name FROM concerns");
    while ($c = $concernStmt->fetch()) {
        $validConcerns[strtoupper(trim($c['concern_name']))] = $c['concern_id'];
    }
    
    foreach ($records as $index => $record) {
        $rowNum = $index + 2;
        
        // Check if row has required data
        $hasData = false;
        foreach (['company', 'contact', 'concern'] as $field) {
            if (!empty(trim($record[$field] ?? ''))) {
                $hasData = true;
                break;
            }
        }
        
        if (!$hasData) {
            if ($skipEmptyRows) {
                $emptyRows++;
                $details[] = [
                    'type' => 'info',
                    'message' => "Row {$rowNum}: Empty row skipped"
                ];
                continue;
            }
        }
        
        // Get values with defaults
        $company = !empty(trim($record['company'] ?? '')) ? trim($record['company']) : 'DEFAULT COMPANY';
        $contact = !empty(trim($record['contact'] ?? '')) ? trim($record['contact']) : 'DEFAULT CONTACT';
        $contactNumber = !empty(trim($record['contact_number'] ?? '')) ? trim($record['contact_number']) : null;
        $concernInput = !empty(trim($record['concern'] ?? '')) ? trim($record['concern']) : 'SOFTWARE SUPPORT';
        $priority = !empty(trim($record['priority'] ?? '')) ? trim($record['priority']) : 'Medium';
        $status = !empty(trim($record['status'] ?? '')) ? trim($record['status']) : 'Pending';
        $assignedTo = !empty(trim($record['assigned_to'] ?? '')) ? trim($record['assigned_to']) : null;
        $dateValue = !empty(trim($record['date'] ?? '')) ? trim($record['date']) : date('Y-m-d H:i:s');
        
        $details[] = [
            'type' => 'info',
            'message' => "Row {$rowNum}: Processing - Company: {$company}, Contact: {$contact}"
        ];
        
        // Find or create client with contact number
        $stmt = $pdo->prepare("SELECT client_id, contact_number FROM clients WHERE company_name = ? AND contact_person = ?");
        $stmt->execute([$company, $contact]);
        $client = $stmt->fetch();
        
        if ($client) {
            $clientId = $client['client_id'];
            
            // Update contact number if provided and different
            if ($contactNumber && $contactNumber !== $client['contact_number']) {
                $updateStmt = $pdo->prepare("UPDATE clients SET contact_number = ? WHERE client_id = ?");
                $updateStmt->execute([$contactNumber, $clientId]);
                $details[] = [
                    'type' => 'info',
                    'message' => "Row {$rowNum}: Updated contact number for existing client"
                ];
            }
        } else {
            // Create new client with contact number
            $stmt = $pdo->prepare("INSERT INTO clients (company_name, contact_person, contact_number) VALUES (?, ?, ?)");
            $stmt->execute([$company, $contact, $contactNumber]);
            $clientId = $pdo->lastInsertId();
            $details[] = [
                'type' => 'success',
                'message' => "Row {$rowNum}: New client created with contact number"
            ];
        }
        
        // Find concern ID based on input (matches new ticket behavior)
        $concernId = 3; // Default to SOFTWARE SUPPORT
        $concernUpper = strtoupper(trim($concernInput));
        
        // Try to match the concern with database
        foreach ($validConcerns as $concernName => $cId) {
            if (strpos($concernUpper, strtoupper($concernName)) !== false || 
                strpos(strtoupper($concernName), $concernUpper) !== false) {
                $concernId = $cId;
                break;
            }
        }
        
        // If no match found, try LIKE query
        if ($concernId == 3 && $concernInput !== 'SOFTWARE SUPPORT') {
            $likeStmt = $pdo->prepare("SELECT concern_id FROM concerns WHERE concern_name LIKE ? LIMIT 1");
            $likeStmt->execute(['%' . $concernInput . '%']);
            $likeResult = $likeStmt->fetch();
            if ($likeResult) {
                $concernId = $likeResult['concern_id'];
            }
        }
        
        // Parse date
        try {
            $date = new DateTime($dateValue);
            $dateRequested = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $dateRequested = date('Y-m-d H:i:s');
        }
        
        // Find technical staff if assigned
        $technicalId = null;
        if ($assignedTo && $assignedTo !== 'Unassigned') {
            $stmt = $pdo->prepare("SELECT technical_id FROM technical_staff WHERE CONCAT(firstname, ' ', lastname) LIKE ?");
            $stmt->execute(['%' . $assignedTo . '%']);
            $tech = $stmt->fetch();
            if ($tech) {
                $technicalId = $tech['technical_id'];
            }
        }
        
        // Get default product ID (first product)
        $productStmt = $pdo->query("SELECT product_id FROM products ORDER BY product_id LIMIT 1");
        $product = $productStmt->fetch();
        $productId = $product ? $product['product_id'] : 1;
        
        // Insert ticket
        $stmt = $pdo->prepare("
            INSERT INTO tickets (
                company_id, technical_id, product_id, concern_id,
                concern_description, date_requested, priority, status,
                assigned, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        try {
            $stmt->execute([
                $clientId,
                $technicalId,
                $productId,
                $concernId,
                $concernInput, // Store the original concern description
                $dateRequested,
                $priority,
                $status,
                $technicalId ? 1 : 0
            ]);
            
            $imported++;
            $ticketId = $pdo->lastInsertId();
            $details[] = [
                'type' => 'success',
                'message' => "Row {$rowNum}: Ticket #{$ticketId} imported successfully (Concern: {$concernInput})"
            ];
            
        } catch (PDOException $e) {
            $details[] = [
                'type' => 'error',
                'message' => "Row {$rowNum}: Database error - " . $e->getMessage()
            ];
            $errors++;
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'total' => count($records),
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
        'empty_rows' => $emptyRows,
        'details' => $details
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>