<?php
header('Content-Type: application/json');
require_once 'database.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ticket ID provided']);
    exit;
}

$ticketId = $_GET['id'];

try {
    // Get assignment information using the normalized structure
    $stmt = $pdo->prepare("
        SELECT 
            t.technical_id,
            CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
            t.assigned_date,
            DATE_FORMAT(t.assigned_date, '%M %d, %Y %h:%i %p') as formatted_date,
            t.status
        FROM tickets t
        LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
        WHERE t.ticket_id = ?
    ");
    
    $stmt->execute([$ticketId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['technical_id']) {
        echo json_encode([
            'has_tech' => true,
            'tech_name' => $result['tech_name'],
            'assigned_date' => $result['formatted_date'] ?? 'Unknown date',
            'technical_id' => $result['technical_id'],
            'status' => $result['status']
        ]);
    } else {
        echo json_encode(['has_tech' => false]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'has_tech' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>