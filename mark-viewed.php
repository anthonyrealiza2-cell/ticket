<?php
require_once 'database.php';

header('Content-Type: application/json');

// Get JSON post data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['ticket_id'])) {
    $stmt = $pdo->prepare("UPDATE tickets SET is_viewed = 1 WHERE ticket_id = ?");
    $stmt->execute([$data['ticket_id']]);
    echo json_encode(['success' => true]);
} elseif (isset($data['company_id'])) {
    $stmt = $pdo->prepare("UPDATE tickets SET is_viewed = 1 WHERE company_id = ? AND status IN ('Pending', 'In Progress', 'Assigned')");
    $stmt->execute([$data['company_id']]);
    echo json_encode(['success' => true]);
} elseif (isset($data['company_name'])) {
    $stmt = $pdo->prepare("UPDATE tickets SET is_viewed = 1 WHERE company_id IN (SELECT client_id FROM clients WHERE LOWER(TRIM(company_name)) = LOWER(TRIM(?))) AND status IN ('Pending', 'In Progress', 'Assigned')");
    $stmt->execute([$data['company_name']]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
}
