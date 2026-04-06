<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once 'database.php';

// Admin only
if (($_SESSION['tech_position'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tech_id = (int) ($_GET['tech_id'] ?? 0);
if (!$tech_id) {
    echo json_encode(['success' => false, 'message' => 'Missing tech_id']);
    exit;
}

try {
    // Auto-create columns if they don't exist
    $cols = $pdo->query("SHOW COLUMNS FROM technical_staff LIKE 'can_view_tickets'")->fetchColumn();
    if (!$cols) {
        $pdo->exec("ALTER TABLE technical_staff 
            ADD COLUMN can_view_tickets TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN can_view_technical TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN can_view_reports TINYINT(1) NOT NULL DEFAULT 1");
    }

    $stmt = $pdo->prepare("
        SELECT technical_id, firstname, lastname, position,
               COALESCE(can_view_tickets,   1) as can_view_tickets,
               COALESCE(can_view_technical, 1) as can_view_technical,
               COALESCE(can_view_reports,   1) as can_view_reports
        FROM technical_staff
        WHERE technical_id = ?
    ");
    $stmt->execute([$tech_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Tech not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
