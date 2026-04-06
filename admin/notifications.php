<?php
require_once '../auth_check.php';
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Admin-only page
if (($_SESSION['tech_position'] ?? '') !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

// ── Mark all as read action ──────────────────────────────────────────────────
if (isset($_GET['mark_all_read'])) {
    $pdo->exec("UPDATE tickets SET is_viewed = 1 WHERE status = 'Resolved'");
    header('Location: notifications.php');
    exit;
}

// ── Dismiss single notification ──────────────────────────────────────────────
if (isset($_GET['dismiss']) && is_numeric($_GET['dismiss'])) {
    $stmt = $pdo->prepare("UPDATE tickets SET is_viewed = 1 WHERE ticket_id = ?");
    $stmt->execute([(int) $_GET['dismiss']]);
    header('Location: notifications.php');
    exit;
}

// ── Pagination ───────────────────────────────────────────────────────────────
$perPage = 15;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Total count
$totalStmt = $pdo->query("
    SELECT COUNT(*) FROM tickets t
    LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
    WHERE t.status = 'Resolved'
      AND t.technical_id IS NOT NULL
");
$totalCount = (int) $totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

// ── Auto-read: capture unread count FIRST, then mark all as read ─────────────
// This clears the navbar badge as soon as the page is opened.
$unreadStmt = $pdo->query("
    SELECT COUNT(*) FROM tickets
    WHERE status = 'Resolved' AND is_viewed = 0 AND technical_id IS NOT NULL
");
$unreadCount = (int) $unreadStmt->fetchColumn();

// Mark all as read silently on page open
if ($unreadCount > 0) {
    $pdo->exec("UPDATE tickets SET is_viewed = 1 WHERE status = 'Resolved' AND is_viewed = 0");
}

// Notifications query
$stmt = $pdo->prepare("
    SELECT
        t.ticket_id,
        t.concern_description,
        t.solution,
        t.priority,
        t.finish_date,
        t.is_viewed,
        c.company_name,
        c.contact_person,
        CONCAT(ts.firstname, ' ', ts.lastname) AS tech_name,
        ts.position AS tech_position,
        cn.concern_name AS concern_type
    FROM tickets t
    LEFT JOIN clients c         ON t.company_id   = c.client_id
    LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
    LEFT JOIN concerns cn        ON t.concern_id   = cn.concern_id
    WHERE t.status = 'Resolved'
      AND t.technical_id IS NOT NULL
    ORDER BY t.finish_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <?php include '../navbar.php'; ?>

    <!-- ── Page Header ─────────────────────────────────────────────────── -->
    <div class="notif-page-header">
        <h1 class="notif-page-title">
            Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="notif-count-pill"><?= $unreadCount ?> new</span>
            <?php endif; ?>
        </h1>

        <div class="notif-header-actions">
            <?php if ($unreadCount > 0): ?>
            <a href="notifications.php?mark_all_read=1" class="notif-mark-all-btn">
                <i class="fas fa-check-double"></i> Mark all as read
            </a>
            <?php endif; ?>
            <a href="tickets.php" class="notif-mark-all-btn" style="background:transparent;">
                <i class="fas fa-list"></i> View all tickets
            </a>
        </div>
    </div>

    <!-- ── Notification List ───────────────────────────────────────────── -->
    <div class="notif-container">
        <?php if (empty($notifications)): ?>
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No resolved ticket notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif):
                $isUnread    = empty($notif['is_viewed']);
                $priorityKey = strtolower($notif['priority'] ?? 'medium');
                $finishDate  = $notif['finish_date']
                    ? date('d M Y \a\t g:i A', strtotime($notif['finish_date']))
                    : 'Date not recorded';
                $solutionPreview = $notif['solution']
                    ? htmlspecialchars(substr($notif['solution'], 0, 160)) . (strlen($notif['solution']) > 160 ? '...' : '')
                    : null;
                $descPreview = htmlspecialchars(substr($notif['concern_description'] ?? '', 0, 140));
            ?>
            <div class="notif-item <?= $isUnread ? 'unread' : '' ?>">

                <!-- Dismiss × -->
                <a href="notifications.php?dismiss=<?= $notif['ticket_id'] ?>"
                   class="notif-dismiss" title="Mark as read">
                    <i class="fas fa-times"></i>
                </a>

                <!-- ── LEFT BODY ──────────────────────────────────────── -->
                <div class="notif-body">
                    <!-- Tag pill: "Resolved" + priority -->
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <span class="notif-tag tag-resolved">
                            <i class="fas fa-check-circle"></i> Resolved
                        </span>
                        <span class="notif-tag tag-<?= $priorityKey ?>">
                            <?= htmlspecialchars(ucfirst($priorityKey)) ?> Priority
                        </span>
                    </div>

                    <!-- Title: who resolved what -->
                    <div class="notif-title">
                        Ticket #<?= $notif['ticket_id'] ?> resolved —
                        <?= htmlspecialchars($notif['company_name'] ?? 'Unknown Company') ?>
                        <?php if ($notif['concern_type']): ?>
                            <span style="font-weight:500;color:var(--text-secondary);font-size:0.88rem;">
                                · <?= htmlspecialchars($notif['concern_type']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Description preview -->
                    <?php if ($descPreview): ?>
                    <div class="notif-description"><?= $descPreview ?></div>
                    <?php endif; ?>

                    <!-- Solution snippet -->
                    <?php if ($solutionPreview): ?>
                    <div class="notif-solution">
                        <i class="fas fa-lightbulb" style="color:var(--accent-primary);margin-right:4px;"></i>
                        <?= $solutionPreview ?>
                    </div>
                    <?php endif; ?>

                    <!-- Technician name (accent color, like notif.png) -->
                    <div class="notif-tech-name">
                        <i class="fas fa-user-cog"></i>
                        <?= htmlspecialchars($notif['tech_name'] ?? 'Unknown Technician') ?>
                    </div>
                </div>

                <!-- ── RIGHT META ─────────────────────────────────────── -->
                <div class="notif-meta">
                    <div class="notif-date">
                        <i class="fas fa-clock"></i>
                        <?= $finishDate ?>
                    </div>
                    <div class="notif-ticket-id">#<?= $notif['ticket_id'] ?></div>
                </div>

            </div>
            <?php endforeach; ?>

            <!-- ── Pagination ────────────────────────────────────────── -->
            <?php if ($totalPages > 1): ?>
            <div class="notif-load-more" style="justify-content:space-between;padding:16px 28px;">
                <?php if ($page > 1): ?>
                    <a href="notifications.php?page=<?= $page - 1 ?>" class="notif-mark-all-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <span style="color:var(--text-muted);font-size:0.85rem;">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a href="notifications.php?page=<?= $page + 1 ?>" class="notif-mark-all-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div><!-- /notif-container -->

</div><!-- /container -->

<script src="../script.js"></script>
</body>
</html>
