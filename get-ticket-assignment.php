<?php
// Start output buffering
ob_start();

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/database.php';

// Removed random delay to prevent timeouts on InfinityFree
// sleep(rand(1, 3));

function sendJsonResponse($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        sendJsonResponse([
            'success' => false,
            'has_tech' => false,
            'error' => 'No ticket ID provided'
        ]);
    }

    $ticketId = intval($_GET['id']);

    // Get assignment information
    $stmt = $pdo->prepare("
        SELECT 
            t.technical_id,
            CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
            t.assigned_date,
            DATE_FORMAT(t.assigned_date, '%M %d, %Y %h:%i %p') as formatted_date,
            t.status,
            ts.is_active
        FROM tickets t
        LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
        WHERE t.ticket_id = ?
    ");
    
    $stmt->execute([$ticketId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['technical_id']) {
        sendJsonResponse([
            'success' => true,
            'has_tech' => true,
            'tech_name' => $result['tech_name'],
            'assigned_date' => $result['formatted_date'] ?? 'Unknown date',
            'technical_id' => $result['technical_id'],
            'status' => $result['status'],
            'is_active' => $result['is_active'] ?? 1
        ]);
    } else {
        sendJsonResponse([
            'success' => true,
            'has_tech' => false
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in get-ticket-assignment.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'has_tech' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in get-ticket-assignment.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'has_tech' => false,
        'error' => 'Server error occurred'
    ]);
}
?>