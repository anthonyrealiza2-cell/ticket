<?php
header('Content-Type: application/json');
require_once 'database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ticket ID provided']);
    exit;
}

$ticketId = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            c.company_name,
            c.contact_person,
            c.contact_number,
            c.email,
            p.product_name,
            p.version,
            cn.concern_name AS concern_type,
            CONCAT(ts.firstname, ' ', ts.lastname) as tech_name
        FROM tickets_archive a
        LEFT JOIN clients c ON a.company_id = c.client_id
        LEFT JOIN products p ON a.product_id = p.product_id
        LEFT JOIN concerns cn ON a.concern_id = cn.concern_id
        LEFT JOIN technical_staff ts ON a.technical_id = ts.technical_id
        WHERE a.ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ticket) {
        echo json_encode($ticket);
    } else {
        echo json_encode(['error' => 'Archived ticket not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>