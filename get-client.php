<?php
// Start output buffering to prevent whitespace corruption
ob_start();

header('Content-Type: application/json');

// Use absolute path
require_once __DIR__ . '/database.php';

try {
    if(isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->execute([$_GET['id']]);
        
        // Use FETCH_ASSOC
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        if($client) {
            echo json_encode($client, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Client not found'], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }
    } else {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode(['error' => 'No ID provided'], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    // Catch database or syntax errors silently
    error_log("General error in get-client.php: " . $e->getMessage());
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred'
    ]);
}
?>