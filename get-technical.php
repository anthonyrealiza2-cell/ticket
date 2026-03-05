<?php
header('Content-Type: application/json');
require_once 'database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No technician ID provided']);
    exit;
}

$tech_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
    $stmt->execute([$tech_id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($technician) {
        echo json_encode($technician);
    } else {
        echo json_encode(['error' => 'Technician not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>