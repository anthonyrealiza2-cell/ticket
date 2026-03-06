<?php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['technical_id']) || !isset($data['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$technicalId = $data['technical_id'];
$isActive = $data['is_active'] ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE technical_staff SET is_active = ? WHERE technical_id = ?");
    $stmt->execute([$isActive, $technicalId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Technician status updated successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>