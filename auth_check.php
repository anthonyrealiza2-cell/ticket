<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tech_id'])) {
    $base_path = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/tech/') !== false) ? '../' : '';
    header("Location: " . $base_path . "index.php");
    exit;
}

// Protection for /admin/ pages
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $isAdmin    = ($_SESSION['tech_position'] ?? '') === 'Admin';
    $scriptName = basename($_SERVER['PHP_SELF']);

    if (!$isAdmin) {
        // Non-admin techs may access certain pages if they have the privilege
        // Load privilege columns from DB (with migration guard)
        require_once __DIR__ . '/database.php';

        $priv_tickets   = 1;
        $priv_technical = 1;
        $priv_reports   = 1;

        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM technical_staff LIKE 'can_view_tickets'")->fetchColumn();
            if ($colCheck) {
                $pStmt = $pdo->prepare("SELECT can_view_tickets, can_view_technical, can_view_reports FROM technical_staff WHERE technical_id = ?");
                $pStmt->execute([$_SESSION['tech_id']]);
                $privRow = $pStmt->fetch();
                if ($privRow) {
                    $priv_tickets   = (int) $privRow['can_view_tickets'];
                    $priv_technical = (int) $privRow['can_view_technical'];
                    $priv_reports   = (int) $privRow['can_view_reports'];
                }
            }
        } catch (Exception $e) {
            // Columns may not exist yet — keep defaults (allow all)
        }

        // Map pages to their required privilege
        $pagePrivMap = [
            'tickets.php'   => $priv_tickets,
            'technical.php' => $priv_technical,
            'reports.php'   => $priv_reports,
        ];

        $allowed = $pagePrivMap[$scriptName] ?? 0;

        if (!$allowed) {
            header("Location: ../tech/index.php");
            exit;
        }
    }
}
?>
