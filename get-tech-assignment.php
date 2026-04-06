<?php
// Prevent session locking which causes connection resets on shared hosts
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Start output buffering to catch any unexpected output
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

// Fix the path to database.php
require_once __DIR__ . '/database.php';

// Function to clean output and send JSON
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
    // Check if tech_id is provided
    if (!isset($_GET['tech_id']) || $_GET['tech_id'] === '') {
        sendJsonResponse([
            'success' => false,
            'error' => 'No technician ID provided'
        ]);
    }

    $techId = intval($_GET['tech_id']);

    // Get technician details (optimized query, avoid SELECT *)
    $techStmt = $pdo->prepare("SELECT technical_id, firstname, lastname, email, contact_viber, branch, position, is_active FROM technical_staff WHERE technical_id = ?");
    $techStmt->execute([$techId]);
    $technician = $techStmt->fetch(PDO::FETCH_ASSOC);

    if (!$technician) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Technician not found'
        ]);
    }

    // Get technician's tickets
    $ticketStmt = $pdo->prepare("
        SELECT 
            t.ticket_id,
            t.concern_description,
            t.status,
            t.date_requested,
            COALESCE(c.company_name, 'Unknown Company') as company_name,
            COALESCE(c.contact_person, 'No contact') as contact_person,
            COALESCE(c.contact_number, 'No contact') as contact_number,
            c.email,
            COALESCE(cn.concern_name, 'General Support') as concern_type
        FROM tickets t
        LEFT JOIN clients c ON t.company_id = c.client_id
        LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
        WHERE t.technical_id = ?
        ORDER BY t.created_at DESC
        LIMIT 100
    ");
    $ticketStmt->execute([$techId]);
    $tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get performance data
    $perfStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_ticket,
            COALESCE(SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END), 0) as resolve,
            COALESCE(SUM(CASE WHEN status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END), 0) as pending
        FROM tickets 
        WHERE technical_id = ?
    ");
    $perfStmt->execute([$techId]);
    $performance = $perfStmt->fetch(PDO::FETCH_ASSOC);

    // Ensure performance data is properly formatted
    $performanceData = [
        'total_ticket' => (int)($performance['total_ticket'] ?? 0),
        'resolve' => (int)($performance['resolve'] ?? 0),
        'pending' => (int)($performance['pending'] ?? 0)
    ];

    $response = [
        'success' => true,
        'technician' => $technician,
        'tickets' => $tickets,
        'performance' => $performanceData
    ];

    // Handle Iframe fallback ping by outputting data directly to parent window!
    if (isset($_GET['iframe']) && $_GET['iframe'] == '1') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html');
        $jsonPayload = json_encode($response);
        echo "<script>if(window.parent && window.parent.iframeClearanceObtained) { window.parent.iframeClearanceObtained(" . $techId . ", " . $jsonPayload . "); }</script>";
        exit;
    }

    sendJsonResponse($response);

} catch (PDOException $e) {
    error_log("Database error in get-tech-assignment.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in get-tech-assignment.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'An error occurred'
    ]);
}
?>