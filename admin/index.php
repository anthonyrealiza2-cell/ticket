<?php
require_once '../auth_check.php';
require_once '../database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch all dashboard statistics cleanly
$stats = [];
$stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM tickets");
$stats['total'] = $stmtTotal->fetch()['total'] ?? 0;

$stmtPending = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'Pending'");
$stats['pending'] = $stmtPending->fetch()['total'] ?? 0;

$stmtResolved = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'Resolved'");
$stats['resolved'] = $stmtResolved->fetch()['total'] ?? 0;

$stmtTechs = $pdo->query("SELECT COUNT(*) as total FROM technical_staff");
$stats['technical_count'] = $stmtTechs->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TicketFlow - Dashboard</title>
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/tickets.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../navbar.php'; ?>

        <div class="page-header" style="margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="color: var(--text-primary); font-size: 2rem;">Dashboard Overview</h1>
                    <p style="color: var(--text-muted);">Monitor your ticket metrics and team performance</p>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="new-ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Ticket
                    </a>
                    <a href="reports.php" class="btn btn-secondary" style="background: var(--bg-hover); color: var(--text-primary); border: 1px solid var(--border-color);">
                        <i class="fas fa-chart-pie"></i> View Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info">
                    <h3>Total Tickets</h3>
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3>Resolved</h3>
                    <div class="stat-number"><?php echo number_format($stats['resolved']); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
                <div class="stat-info">
                    <h3>Technical Staff</h3>
                    <div class="stat-number"><?php echo number_format($stats['technical_count']); ?></div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content Area -->
        <div class="dashboard-grid">
            
            <!-- Recent Tickets Feed -->
            <div class="card">
                <div class="card-header" style="justify-content: space-between;">
                    <div><i class="fas fa-history"></i> Recent Activity</div>
                    <a href="tickets.php" style="font-size: 0.9rem; color: var(--accent-primary); text-decoration: none; font-weight: 500;">View All</a>
                </div>
                
                <div class="ticket-feed">
                    <?php
                    $stmt = $pdo->query("
                        SELECT t.*, c.company_name, c.contact_person 
                        FROM tickets t 
                        LEFT JOIN clients c ON t.company_id = c.client_id 
                        ORDER BY t.created_at DESC LIMIT 10
                    ");
                    
                    if ($stmt->rowCount() > 0):
                        while($ticket = $stmt->fetch()):
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                    ?>
                        <div class="ticket-feed-card" data-priority="<?= $priorityClass ?>">
                            <div class="feed-id">#<?= $ticket['ticket_id'] ?></div>
                            
                            <div class="feed-content">
                                <div class="feed-title"><?= htmlspecialchars($ticket['company_name'] ?? 'Unknown Client') ?></div>
                                <div class="feed-meta">
                                    <span><i class="fas fa-tag"></i><?= htmlspecialchars(substr($ticket['concern_description'] ?? 'No description', 0, 40)) ?>...</span>
                                    <span><i class="fas fa-calendar-alt"></i><?= date('M d', strtotime($ticket['date_requested'] ?? date('Y-m-d'))) ?></span>
                                </div>
                            </div>
                            
                            <div class="feed-badges">
                                <span class="badge badge-<?= $statusClass ?>"><?= $ticket['status'] ?></span>
                            </div>
                            
                            <div class="feed-actions">
                                <button class="btn btn-primary btn-sm" onclick="viewTicket(<?= $ticket['ticket_id'] ?>)" title="Quick View">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent tickets found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Performing Technicals Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i> Top Performers
                </div>
                
                <div class="achievement-list">
                    <?php
                    $stmt = $pdo->query("
                        SELECT * FROM vw_tech_performance 
                        WHERE total_ticket > 0 
                        ORDER BY performance_rate DESC 
                        LIMIT 5
                    ");
                    
                    if ($stmt->rowCount() > 0):
                        while($tech = $stmt->fetch()):
                            // Get Initials for Avatar
                            $nameParts = explode(' ', trim($tech['full_name']));
                            $initials = '';
                            if(count($nameParts) > 0) {
                                $initials .= strtoupper(substr($nameParts[0], 0, 1));
                                if(count($nameParts) > 1) {
                                    $initials .= strtoupper(substr(end($nameParts), 0, 1));
                                }
                            }
                            
                            $rate = floatval($tech['performance_rate']);
                            $strokeColor = $rate >= 80 ? 'success' : 'primary';
                    ?>
                        <div class="achievement-card">
                            <div class="tech-avatar"><?= $initials ?></div>
                            
                            <div class="tech-info">
                                <div class="tech-name"><?= htmlspecialchars($tech['full_name']) ?></div>
                                <div class="tech-stats">
                                    <i class="fas fa-clipboard-check"></i> <?= $tech['resolve'] ?>/<?= $tech['total_ticket'] ?> Tickets Resolved
                                </div>
                            </div>
                            
                            <div class="tech-progress">
                                <svg viewBox="0 0 36 36" class="circular-chart <?= $strokeColor ?>">
                                    <path class="circle-bg"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path class="circle"
                                        stroke-dasharray="<?= $rate ?>, 100"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <text x="18" y="20.35" class="percentage"><?= round($rate) ?>%</text>
                                </svg>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <p>No performance data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- End Dashboard Grid -->

    </div> <!-- End Container -->

    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal()" title="Close">&times;</button>
            </div>
            <div id="ticketDetails" style="max-height: 70vh; overflow-y: auto; padding-right: 10px;"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../script.js"></script>
    <script src="../dashboard.js"></script>
</body>
</html>