<?php
header('Content-Type: application/json');
require_once 'database.php';

if(isset($_GET['id'])) {
    // Get single ticket with all details
    $stmt = $pdo->prepare("
        SELECT 
            t.ticket_id,
            t.company_id,
            t.technical_id,
            t.assigned_date,
            t.product_id,
            t.concern_id,
            t.concern_description,
            t.date_requested,
            t.submitted_date,
            t.finish_date,
            t.solution,
            t.remarks,
            t.priority,
            t.status,
            t.assigned,
            t.created_at,
            t.updated_at,
            c.company_name,
            c.contact_person,
            c.contact_number,
            c.email,
            p.product_name,
            p.version,
            cn.concern_name AS concern_type,
            ts.firstname AS tech_firstname,
            ts.lastname AS tech_lastname
        FROM tickets t
        LEFT JOIN clients c ON t.company_id = c.client_id
        LEFT JOIN products p ON t.product_id = p.product_id
        LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
        LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $ticket = $stmt->fetch();
    
    if($ticket) {
        echo json_encode($ticket);
    } else {
        echo json_encode(['error' => 'Ticket not found']);
    }
} else {
    // Get all tickets with basic info
    $stmt = $pdo->query("
        SELECT 
            t.ticket_id,
            t.priority,
            t.status,
            t.date_requested,
            t.concern_description,
            c.company_name,
            c.contact_person,
            c.contact_number,
            c.email,
            CONCAT(ts.firstname, ' ', ts.lastname) as tech_name
        FROM tickets t 
        LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id 
        LEFT JOIN clients c ON t.company_id = c.client_id
        ORDER BY t.created_at DESC
    ");
    $tickets = $stmt->fetchAll();
    echo json_encode($tickets);
}
?>