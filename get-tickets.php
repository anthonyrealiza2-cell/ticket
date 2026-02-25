<?php
header('Content-Type: application/json');
require_once 'database.php';

if(isset($_GET['id'])) {
    // Get single ticket
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$_GET['id']]);
    $ticket = $stmt->fetch();
    
    if($ticket) {
        echo json_encode($ticket);
    } else {
        echo json_encode(['error' => 'Ticket not found']);
    }
} else {
    // Get all tickets
    $stmt = $pdo->query("SELECT * FROM tickets ORDER BY created_at DESC");
    $tickets = $stmt->fetchAll();
    echo json_encode($tickets);
}
?>