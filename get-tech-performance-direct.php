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

// Prevent session locking which causes connection resets on shared hosts
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

function sendJsonResponse($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    // Set headers right before sending to ensure clean minimal response
    header('Content-Type: application/json');
    header('X-Requested-With: XMLHttpRequest');
    echo json_encode($data);
    exit;
}

try {
    if (!isset($_GET['id']) || $_GET['id'] === '') {
        sendJsonResponse([
            'success' => false,
            'error' => 'No technician ID provided',
            'total_ticket' => 0,
            'resolve' => 0,
            'pending' => 0
        ]);
    }

    $techId = intval($_GET['id']);
    
    // Get performance data
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_ticket,
            COALESCE(SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END), 0) as resolve,
            COALESCE(SUM(CASE WHEN status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END), 0) as pending
        FROM tickets 
        WHERE technical_id = ?
    ");
    
    $stmt->execute([$techId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'total_ticket' => (int)($result['total_ticket'] ?? 0),
        'resolve' => (int)($result['resolve'] ?? 0),
        'pending' => (int)($result['pending'] ?? 0)
    ];
    
    sendJsonResponse($response);

} catch (PDOException $e) {
    error_log("Database error in get-tech-performance-direct.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error occurred',
        'total_ticket' => 0,
        'resolve' => 0,
        'pending' => 0
    ]);
} catch (Exception $e) {
    error_log("General error in get-tech-performance-direct.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'An error occurred',
        'total_ticket' => 0,
        'resolve' => 0,
        'pending' => 0
    ]);
}
?>