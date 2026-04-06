<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once 'database.php';

// Admin only
if (($_SESSION['tech_position'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['tech_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Auto-create columns if they don't exist (migration guard)
    $cols = $pdo->query("SHOW COLUMNS FROM technical_staff LIKE 'can_view_tickets'")->fetchColumn();
    if (!$cols) {
        $pdo->exec("ALTER TABLE technical_staff 
            ADD COLUMN can_view_tickets TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN can_view_technical TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN can_view_reports TINYINT(1) NOT NULL DEFAULT 1");
    }

    $stmt = $pdo->prepare("
        UPDATE technical_staff 
        SET can_view_tickets   = ?,
            can_view_technical = ?,
            can_view_reports   = ?
        WHERE technical_id = ?
    ");

    $stmt->execute([
        (int) ($data['can_view_tickets']   ?? 1),
        (int) ($data['can_view_technical'] ?? 1),
        (int) ($data['can_view_reports']   ?? 1),
        (int)  $data['tech_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Privileges updated']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
