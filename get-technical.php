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
            'error' => 'No technician ID provided'
        ]);
    }

    $tech_id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
    $stmt->execute([$tech_id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($technician) {
        $technician['success'] = true;
        sendJsonResponse($technician);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => 'Technician not found'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in get-technical.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in get-technical.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'An error occurred'
    ]);
}
?>