<?php
header('Content-Type: application/json');
require_once 'database.php';

if (!isset($_GET['tech_id'])) {
    echo json_encode(['error' => 'No technician ID provided']);
    exit;
}

$tech_id = $_GET['tech_id'];

try {
    // Get technician info
    $techStmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
    $techStmt->execute([$tech_id]);
    $technician = $techStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$technician) {
        echo json_encode(['error' => 'Technician not found']);
        exit;
    }
    
    // Get performance data
    $perfStmt = $pdo->prepare("SELECT * FROM vw_tech_performance WHERE technical_id = ?");
    $perfStmt->execute([$tech_id]);
    $performance = $perfStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get tickets
    $ticketStmt = $pdo->prepare("
        SELECT 
            t.ticket_id,
            t.concern_description,
            t.priority,
            t.status,
            t.date_requested,
            c.company_name,
            c.contact_person,
            c.contact_number,
            c.email,
            cn.concern_name as concern_type
        FROM tickets t
        JOIN clients c ON t.company_id = c.client_id
        LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
        WHERE t.technical_id = ?
        ORDER BY t.created_at DESC
    ");
    $ticketStmt->execute([$tech_id]);
    $tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'technician' => $technician,
        'performance' => $performance ?: ['total_ticket' => 0, 'resolve' => 0, 'pending' => 0, 'performance_rate' => 0],
        'tickets' => $tickets
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>