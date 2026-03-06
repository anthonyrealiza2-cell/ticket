<?php
header('Content-Type: application/json');
require_once 'database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No technician ID provided']);
    exit;
}

$tech_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM vw_tech_performance WHERE technical_id = ?");
    $stmt->execute([$tech_id]);
    $performance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($performance) {
        echo json_encode($performance);
    } else {
        echo json_encode([
            'total_ticket' => 0,
            'resolve' => 0,
            'pending' => 0,
            'performance_rate' => 0
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>