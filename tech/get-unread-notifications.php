<?php
header('Content-Type: application/json');
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure it's accessed by a logged-in user (admin or tech)
if (!isset($_SESSION['tech_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$tech_id = $_SESSION['tech_id'];
$is_admin = ($_SESSION['tech_position'] ?? '') === 'Admin';

try {
    if ($is_admin) {
        $stmt = $pdo->query("SELECT COUNT(*) as total_unread FROM tickets WHERE status IN ('Pending', 'In Progress', 'Assigned') AND is_viewed = 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'total_unread' => (int) $result['total_unread'],
            'companies' => []
        ]);
    } else {
        // Group by company name to match the UI grouping logic
        $stmt = $pdo->prepare("
            SELECT t.is_viewed, c.company_name
            FROM tickets t
            LEFT JOIN clients c ON t.company_id = c.client_id
            WHERE t.technical_id = ? AND t.status IN ('Pending', 'In Progress', 'Assigned') AND t.is_viewed = 0
        ");
        $stmt->execute([$tech_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_unread = count($tickets);
        $companies = [];
        
        foreach ($tickets as $ticket) {
            $companyNameRaw = $ticket['company_name'] ?? 'Unknown Company';
            $groupKey = strtolower(trim($companyNameRaw));
            
            if (!isset($companies[$groupKey])) {
                $companies[$groupKey] = 0;
            }
            $companies[$groupKey]++;
        }
        
        echo json_encode([
            'success' => true,
            'total_unread' => $total_unread,
            'companies' => $companies
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
