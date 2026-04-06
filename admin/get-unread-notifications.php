<?php
header('Content-Type: application/json');
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be a logged-in admin
if (!isset($_SESSION['tech_id']) || ($_SESSION['tech_position'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Total unread across all companies
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_unread
        FROM tickets
        WHERE status IN ('Pending', 'In Progress', 'Assigned')
          AND is_viewed = 0
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_unread = (int) ($result['total_unread'] ?? 0);

    // Per-company breakdown (keyed by lowercase company name, matching group keys in tickets.php)
    $stmt = $pdo->query("
        SELECT c.company_name
        FROM tickets t
        LEFT JOIN clients c ON t.company_id = c.client_id
        WHERE t.status IN ('Pending', 'In Progress', 'Assigned')
          AND t.is_viewed = 0
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $companies = [];
    foreach ($rows as $row) {
        $key = strtolower(trim($row['company_name'] ?? 'unknown company'));
        $companies[$key] = ($companies[$key] ?? 0) + 1;
    }

    // Resolved ticket notifications (unread — for bell badge)
    $rStmt = $pdo->query("
        SELECT COUNT(*) as cnt FROM tickets
        WHERE status = 'Resolved' AND is_viewed = 0 AND technical_id IS NOT NULL
    ");
    $resolved_unread = (int) ($rStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    echo json_encode([
        'success'         => true,
        'total_unread'    => $total_unread,
        'resolved_unread' => $resolved_unread,
        'companies'       => $companies,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
