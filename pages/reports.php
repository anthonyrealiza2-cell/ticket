<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-ticket-alt"></i>
                <h2>TicketFlow</h2>
            </div>
            <div class="nav-links">
                <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="new-ticket.php" class="nav-link"><i class="fas fa-plus-circle"></i> New Ticket</a>
                <a href="tickets.php" class="nav-link"><i class="fas fa-list"></i> Tickets</a>
                <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="technical.php" class="nav-link"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Header -->
        <div class="flex justify-between" style="margin-bottom: 30px;">
            <h1 style="color: var(--text-primary);">Analytics & Reports</h1>
            <div class="export-buttons">
                <button class="export-btn-pdf" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i>
                    <span>Export PDF</span>
                </button>
                <button class="export-btn-excel" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i>
                    <span>Export Excel</span>
                </button>
            </div>
        </div>

        <?php
        // Get date filters
        $dateFrom = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
        $dateTo = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
        
        // Total tickets in period
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $totalTickets = $stmt->fetch()['total'];
        
        // Resolved tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Resolved' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $resolvedTickets = $stmt->fetch()['total'];
        
        // Closed tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Closed' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $closedTickets = $stmt->fetch()['total'];
        
        // Pending tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Pending' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $pendingTickets = $stmt->fetch()['count'] ?? 0;
        
        // In Progress tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'In Progress' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $inProgressTickets = $stmt->fetch()['count'] ?? 0;
        
        // Assigned tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Assigned' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $assignedTickets = $stmt->fetch()['count'] ?? 0;
        
        // Total completed (Resolved + Closed)
        $completedTickets = $resolvedTickets + $closedTickets;
        
        // Resolution rate
        $resolutionRate = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;
        
        // Get solution statistics
        $solutionStats = [];
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_solved,
                AVG(LENGTH(solution)) as avg_solution_length,
                COUNT(CASE WHEN solution IS NOT NULL AND solution != '' THEN 1 END) as with_solution,
                COUNT(CASE WHEN solution IS NULL OR solution = '' THEN 1 END) as without_solution
            FROM tickets 
            WHERE status IN ('Resolved', 'Closed') 
            AND DATE(date_requested) BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $solutionStats = $stmt->fetch();
        
        // Get most common solutions/remarks
        $commonSolutions = [];
        $stmt = $pdo->prepare("
            SELECT 
                solution,
                COUNT(*) as frequency
            FROM tickets 
            WHERE status IN ('Resolved', 'Closed') 
            AND solution IS NOT NULL 
            AND solution != ''
            AND DATE(date_requested) BETWEEN ? AND ?
            GROUP BY solution
            ORDER BY frequency DESC
            LIMIT 10
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $commonSolutions = $stmt->fetchAll();
        
        // Get resolution time statistics
        $resolutionTimeStats = [];
        $stmt = $pdo->prepare("
            SELECT 
                AVG(TIMESTAMPDIFF(HOUR, date_requested, finish_date)) as avg_hours,
                MIN(TIMESTAMPDIFF(HOUR, date_requested, finish_date)) as min_hours,
                MAX(TIMESTAMPDIFF(HOUR, date_requested, finish_date)) as max_hours
            FROM tickets 
            WHERE status IN ('Resolved', 'Closed') 
            AND finish_date IS NOT NULL
            AND DATE(date_requested) BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $resolutionTimeStats = $stmt->fetch();
        
        // Get all resolved/closed tickets
        $resolvedStmt = $pdo->prepare("
            SELECT 
                t.ticket_id,
                c.company_name,
                t.concern_description,
                t.solution,
                t.finish_date,
                t.date_requested,
                CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
                TIMESTAMPDIFF(HOUR, t.date_requested, t.finish_date) as hours_to_resolve
            FROM tickets t
            LEFT JOIN clients c ON t.company_id = c.client_id
            LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
            WHERE t.status IN ('Resolved', 'Closed')
            AND DATE(t.date_requested) BETWEEN ? AND ?
            ORDER BY t.finish_date DESC
        ");
        $resolvedStmt->execute([$dateFrom, $dateTo]);
        $allResolvedDetails = $resolvedStmt->fetchAll();
        $totalResolvedCount = count($allResolvedDetails);
        
        // Set initial display count
        $initialDisplayCount = 5;
        $showMoreCount = 14;
        ?>

        <!-- Date Filter -->
        <div class="report-filters">
            <div class="filter-group">
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        From Date
                    </label>
                    <input type="date" id="dateFrom" class="form-control" 
                           value="<?php echo $dateFrom; ?>">
                </div>
                
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-calendar-check"></i>
                        To Date
                    </label>
                    <input type="date" id="dateTo" class="form-control" 
                           value="<?php echo $dateTo; ?>">
                </div>
                
                <div class="filter-item filter-button">
                    <label class="filter-label">&nbsp;</label>
                    <button class="btn btn-primary" onclick="applyDateFilter()">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </div>
            
            <!-- Quick filter options -->
            <div class="quick-filters">
                <span class="quick-filter-label">
                    <i class="fas fa-bolt"></i> Quick Select:
                </span>
                <div class="quick-filter-buttons">
                    <button class="export-btn" onclick="setDateRange('today')">
                        <i class="fas fa-sun"></i> Today
                    </button>
                    <button class="export-btn" onclick="setDateRange('yesterday')">
                        <i class="fas fa-calendar-day"></i> Yesterday
                    </button>
                    <button class="export-btn" onclick="setDateRange('week')">
                        <i class="fas fa-calendar-week"></i> This Week
                    </button>
                    <button class="export-btn" onclick="setDateRange('month')">
                        <i class="fas fa-calendar-alt"></i> This Month
                    </button>
                    <button class="export-btn" onclick="setDateRange('lastmonth')">
                        <i class="fas fa-calendar-minus"></i> Last Month
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="value"><?php echo $totalTickets; ?></div>
                <div class="label">Total Tickets</div>
                <div class="trend">
                    <i class="fas fa-ticket-alt"></i> All tickets in period
                </div>
            </div>
            
            <div class="summary-card">
                <div class="value" style="color: var(--success);"><?php echo $completedTickets; ?></div>
                <div class="label">Completed</div>
                <div class="trend trend-up">
                    <i class="fas fa-check-circle"></i> Resolved + Closed
                </div>
            </div>
            
            <div class="summary-card">
                <div class="value" style="color: var(--warning);"><?php echo $pendingTickets + $inProgressTickets + $assignedTickets; ?></div>
                <div class="label">Active</div>
                <div class="trend trend-down">
                    <i class="fas fa-clock"></i> Pending / In Progress
                </div>
            </div>
            
            <div class="summary-card">
                <div class="value" style="color: var(--info);"><?php echo $resolutionRate; ?>%</div>
                <div class="label">Resolution Rate</div>
                <div class="trend">
                    <i class="fas fa-chart-line"></i> Overall performance
                </div>
            </div>
        </div>

        <!-- Solution Statistics Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
            <div class="card" style="padding: 20px;">
                <div style="font-size: 2rem; color: var(--success); margin-bottom: 10px;">
                    <?php echo $solutionStats['total_solved'] ?? 0; ?>
                </div>
                <div style="color: var(--text-secondary);">Total Solved</div>
            </div>
            
            <div class="card" style="padding: 20px;">
                <div style="font-size: 2rem; color: var(--info); margin-bottom: 10px;">
                    <?php echo $solutionStats['with_solution'] ?? 0; ?>
                </div>
                <div style="color: var(--text-secondary);">With Solution Notes</div>
            </div>
            
            <div class="card" style="padding: 20px;">
                <div style="font-size: 2rem; color: var(--warning); margin-bottom: 10px;">
                    <?php echo $solutionStats['without_solution'] ?? 0; ?>
                </div>
                <div style="color: var(--text-secondary);">Missing Solution</div>
            </div>
            
            <div class="card" style="padding: 20px;">
                <div style="font-size: 2rem; color: var(--accent-primary); margin-bottom: 10px;">
                    <?php echo round($resolutionTimeStats['avg_hours'] ?? 0, 1); ?>h
                </div>
                <div style="color: var(--text-secondary);">Avg Resolution Time</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="stats-grid">
            <!-- Ticket Status Chart -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Ticket Status Distribution
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Priority Distribution Chart -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Priority Levels
                </div>
                <div class="chart-container">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="stats-grid">
            <!-- Daily Trends -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Daily Ticket Trends
                </div>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Technical Performance -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i> Top Technical Staff
                </div>
                <div class="chart-container">
                    <canvas id="techChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Common Solutions/Remarks Section -->
        <?php if (!empty($commonSolutions)): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <i class="fas fa-lightbulb"></i> Most Common Solutions & Remarks
            </div>
            <div class="table-container">
                <table id="solutionsTable">
                    <thead>
                        <tr>
                            <th style="width: 70%">Solution / Remark</th>
                            <th style="width: 30%">Frequency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commonSolutions as $solution): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($solution['solution'], 0, 100)) . (strlen($solution['solution']) > 100 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge-info"><?php echo $solution['frequency']; ?> times</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resolved Tickets Details with Show More -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <i class="fas fa-check-circle"></i> Resolved & Closed Tickets Details
                <span class="badge-info" style="margin-left: 10px;">Total: <?php echo $totalResolvedCount; ?> records</span>
            </div>
            <div class="table-container">
                <table class="resolved-tickets-table" id="resolvedTicketsTable">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Company</th>
                            <th>Concern</th>
                            <th>Solution / Remarks</th>
                            <th>Resolved Date</th>
                            <th>Resolution Time</th>
                            <th>Technician</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rowCount = 0;
                        foreach ($allResolvedDetails as $ticket): 
                            $rowCount++;
                            $hidden = $rowCount > $initialDisplayCount ? 'hidden-row' : '';
                            $hours = $ticket['hours_to_resolve'];
                            if ($hours < 24) {
                                $timeDisplay = $hours . ' hours';
                            } else {
                                $days = floor($hours / 24);
                                $remainingHours = $hours % 24;
                                $timeDisplay = $days . ' days ' . ($remainingHours > 0 ? $remainingHours . ' hours' : '');
                            }
                        ?>
                        <tr class="resolved-row <?php echo $hidden; ?>" data-row-id="<?php echo $rowCount; ?>">
                            <td>#<?php echo $ticket['ticket_id']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['company_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($ticket['concern_description'], 0, 50)) . '...'; ?></td>
                            <td>
                                <?php if ($ticket['solution']): ?>
                                    <span class="tooltip-trigger" title="<?php echo htmlspecialchars($ticket['solution']); ?>">
                                        <?php echo htmlspecialchars(substr($ticket['solution'], 0, 50)) . (strlen($ticket['solution']) > 50 ? '...' : ''); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-warning">No solution recorded</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($ticket['finish_date'])); ?></td>
                            <td><?php echo $timeDisplay; ?></td>
                            <td><?php echo $ticket['tech_name'] ?? 'Unassigned'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($totalResolvedCount > $initialDisplayCount): ?>
                        <tr class="show-more-row" id="showMoreRow">
                            <td colspan="7">
                                <button class="show-more-btn" onclick="showMoreRecords()" id="showMoreBtn">
                                    <i class="fas fa-chevron-down"></i> Show <?php echo $showMoreCount; ?> More Records
                                </button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <i class="fas fa-chart-simple"></i> Detailed Statistics
            </div>
            
            <?php
            // Get priority breakdown
            $priorityStats = [];
            $priorities = ['Low', 'Medium', 'High'];
            foreach($priorities as $priority) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE priority = ? AND DATE(date_requested) BETWEEN ? AND ?");
                $stmt->execute([$priority, $dateFrom, $dateTo]);
                $priorityStats[$priority] = $stmt->fetch()['count'];
            }
            
            // Get daily trends for last 7 days
            $dailyTrends = [];
            $dailyLabels = [];
            for($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE DATE(date_requested) = ?");
                $stmt->execute([$date]);
                $dailyTrends[] = $stmt->fetch()['count'];
                $dailyLabels[] = date('M d', strtotime($date));
            }
            
            // Get technical performance from view
            $techData = [];
            $techLabels = [];
            $stmt = $pdo->query("
                SELECT full_name, performance_rate 
                FROM vw_tech_performance 
                WHERE total_ticket > 0
                ORDER BY performance_rate DESC
                LIMIT 5
            ");
            while($row = $stmt->fetch()) {
                $techLabels[] = $row['full_name'];
                $techData[] = $row['performance_rate'];
            }
            ?>

            <div class="table-container">
                <table id="statsTable">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Low Priority Tickets</td>
                            <td><?php echo $priorityStats['Low']; ?></td>
                            <td>
                                <div class="progress-bar" style="width: 200px;">
                                    <div class="progress" style="width: <?php echo $totalTickets > 0 ? ($priorityStats['Low'] / $totalTickets * 100) : 0; ?>%; background: var(--success);">
                                        <?php echo $totalTickets > 0 ? round(($priorityStats['Low'] / $totalTickets * 100), 1) : 0; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Medium Priority Tickets</td>
                            <td><?php echo $priorityStats['Medium']; ?></td>
                            <td>
                                <div class="progress-bar" style="width: 200px;">
                                    <div class="progress" style="width: <?php echo $totalTickets > 0 ? ($priorityStats['Medium'] / $totalTickets * 100) : 0; ?>%; background: var(--warning);">
                                        <?php echo $totalTickets > 0 ? round(($priorityStats['Medium'] / $totalTickets * 100), 1) : 0; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>High Priority Tickets</td>
                            <td><?php echo $priorityStats['High']; ?></td>
                            <td>
                                <div class="progress-bar" style="width: 200px;">
                                    <div class="progress" style="width: <?php echo $totalTickets > 0 ? ($priorityStats['High'] / $totalTickets * 100) : 0; ?>%; background: var(--danger);">
                                        <?php echo $totalTickets > 0 ? round(($priorityStats['High'] / $totalTickets * 100), 1) : 0; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Tickets with Solutions</td>
                            <td><?php echo $solutionStats['with_solution'] ?? 0; ?></td>
                            <td>
                                <div class="progress-bar" style="width: 200px;">
                                    <div class="progress" style="width: <?php echo ($completedTickets > 0 && isset($solutionStats['with_solution'])) ? ($solutionStats['with_solution'] / $completedTickets * 100) : 0; ?>%; background: var(--info);">
                                        <?php echo ($completedTickets > 0 && isset($solutionStats['with_solution'])) ? round(($solutionStats['with_solution'] / $completedTickets * 100), 1) : 0; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Average Solution Length</td>
                            <td colspan="2"><?php echo isset($solutionStats['avg_solution_length']) ? round($solutionStats['avg_solution_length']) : 0; ?> characters</td>
                        </tr>
                        <tr>
                            <td>Average Resolution Time</td>
                            <td colspan="2"><?php echo isset($resolutionTimeStats['avg_hours']) ? round($resolutionTimeStats['avg_hours'], 1) : 0; ?> hours</td>
                        </tr>
                        <tr>
                            <td>Fastest Resolution</td>
                            <td colspan="2"><?php echo isset($resolutionTimeStats['min_hours']) ? round($resolutionTimeStats['min_hours'], 1) : 0; ?> hours</td>
                        </tr>
                        <tr>
                            <td>Slowest Resolution</td>
                            <td colspan="2"><?php echo isset($resolutionTimeStats['max_hours']) ? round($resolutionTimeStats['max_hours'], 1) : 0; ?> hours</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
    <script>
    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Quick date range selection
    function setDateRange(range) {
        const today = new Date();
        const fromDate = document.getElementById('dateFrom');
        const toDate = document.getElementById('dateTo');
        
        // Remove active class from all quick filter buttons
        document.querySelectorAll('.quick-filter-buttons .export-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        if(event.target) {
            event.target.classList.add('active');
        }
        
        switch(range) {
            case 'today':
                fromDate.value = formatDate(today);
                toDate.value = formatDate(today);
                break;
                
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                fromDate.value = formatDate(yesterday);
                toDate.value = formatDate(yesterday);
                break;
                
            case 'week':
                const weekStart = new Date(today);
                const day = today.getDay();
                const diff = day === 0 ? 6 : day - 1;
                weekStart.setDate(today.getDate() - diff);
                fromDate.value = formatDate(weekStart);
                toDate.value = formatDate(today);
                break;
                
            case 'month':
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                fromDate.value = formatDate(monthStart);
                toDate.value = formatDate(today);
                break;
                
            case 'lastmonth':
                const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                fromDate.value = formatDate(lastMonthStart);
                toDate.value = formatDate(lastMonthEnd);
                break;
        }
    }

    // Show more records function
    let currentDisplayCount = <?php echo $initialDisplayCount; ?>;
    const showMoreCount = <?php echo $showMoreCount; ?>;
    const totalRecords = <?php echo $totalResolvedCount; ?>;

    function showMoreRecords() {
        const rows = document.querySelectorAll('.resolved-row');
        const showMoreBtn = document.getElementById('showMoreBtn');
        const showMoreRow = document.getElementById('showMoreRow');
        
        let newDisplayCount = currentDisplayCount + showMoreCount;
        if (newDisplayCount > totalRecords) {
            newDisplayCount = totalRecords;
        }
        
        // Show rows up to newDisplayCount
        for (let i = currentDisplayCount; i < newDisplayCount; i++) {
            if (rows[i]) {
                rows[i].classList.remove('hidden-row');
            }
        }
        
        currentDisplayCount = newDisplayCount;
        
        // Update button text or remove if all records are shown
        if (currentDisplayCount >= totalRecords) {
            showMoreRow.remove();
        } else {
            const remainingCount = totalRecords - currentDisplayCount;
            const nextShowCount = Math.min(showMoreCount, remainingCount);
            showMoreBtn.innerHTML = `<i class="fas fa-chevron-down"></i> Show ${nextShowCount} More Records`;
        }
    }

    // Chart initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Status Chart Data
        <?php
        $statuses = ['Pending', 'Assigned', 'In Progress', 'Resolved', 'Closed'];
        $statusColors = ['#ff7675', '#74b9ff', '#fdcb6e', '#00b894', '#6c5ce7'];
        $statusStats = [$pendingTickets, $assignedTickets, $inProgressTickets, $resolvedTickets, $closedTickets];
        ?>

        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statuses); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusStats); ?>,
                    backgroundColor: <?php echo json_encode($statusColors); ?>,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Priority Chart
        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High'],
                datasets: [{
                    label: 'Number of Tickets',
                    data: [
                        <?php echo $priorityStats['Low']; ?>,
                        <?php echo $priorityStats['Medium']; ?>,
                        <?php echo $priorityStats['High']; ?>
                    ],
                    backgroundColor: ['#00b894', '#fdcb6e', '#ff7675'],
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#2f3542' },
                        ticks: { 
                            color: '#fff',
                            stepSize: 1,
                            callback: function(value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#fff' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                let total = <?php echo $totalTickets; ?>;
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value} tickets (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Trends Chart
        new Chart(document.getElementById('trendsChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'Tickets Created',
                    data: <?php echo json_encode($dailyTrends); ?>,
                    borderColor: '#6c5ce7',
                    backgroundColor: 'rgba(108, 92, 231, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6c5ce7',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#2f3542' },
                        ticks: { 
                            color: '#fff',
                            stepSize: 1,
                            callback: function(value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#fff' }
                    }
                },
                plugins: {
                    legend: { 
                        labels: { color: '#fff' }
                    }
                }
            }
        });

        // Technical Performance Chart
        new Chart(document.getElementById('techChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($techLabels); ?>,
                datasets: [{
                    label: 'Performance %',
                    data: <?php echo json_encode($techData); ?>,
                    backgroundColor: '#00b894',
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#2f3542' },
                        ticks: { 
                            color: '#fff',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: { 
                        grid: { display: false },
                        ticks: { color: '#fff' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Performance: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    });

    function applyDateFilter() {
        const from = document.getElementById('dateFrom').value;
        const to = document.getElementById('dateTo').value;
        if(from && to) {
            window.location.href = `reports.php?from=${from}&to=${to}`;
        } else {
            showNotification('Please select both dates', 'warning');
        }
    }

    function exportReport(format) {
        if(format === 'pdf') {
            window.print();
        } else if(format === 'excel') {
            exportToExcel();
        }
    }

    function exportToExcel() {
        // Get the current date for filename
        const date = new Date();
        const dateStr = date.toISOString().slice(0,10);
        
        // Prepare data for Excel
        const data = [
            ['TicketFlow Report', ''],
            ['Date Range', `${document.getElementById('dateFrom').value} to ${document.getElementById('dateTo').value}`],
            ['Generated On', new Date().toLocaleString()],
            ['', ''],
            ['SUMMARY STATISTICS', ''],
            ['Total Tickets', '<?php echo $totalTickets; ?>'],
            ['Resolved Tickets', '<?php echo $resolvedTickets; ?>'],
            ['Closed Tickets', '<?php echo $closedTickets; ?>'],
            ['Completed Tickets (Resolved+Closed)', '<?php echo $completedTickets; ?>'],
            ['Pending Tickets', '<?php echo $pendingTickets; ?>'],
            ['In Progress Tickets', '<?php echo $inProgressTickets; ?>'],
            ['Assigned Tickets', '<?php echo $assignedTickets; ?>'],
            ['Resolution Rate', '<?php echo $resolutionRate; ?>%'],
            ['', ''],
            ['SOLUTION STATISTICS', ''],
            ['Total Solved Tickets', '<?php echo $solutionStats['total_solved'] ?? 0; ?>'],
            ['With Solution Notes', '<?php echo $solutionStats['with_solution'] ?? 0; ?>'],
            ['Missing Solution', '<?php echo $solutionStats['without_solution'] ?? 0; ?>'],
            ['Avg Resolution Time', '<?php echo isset($resolutionTimeStats['avg_hours']) ? round($resolutionTimeStats['avg_hours'], 1) : 0; ?> hours'],
            ['', ''],
            ['PRIORITY BREAKDOWN', ''],
            ['Low Priority', '<?php echo $priorityStats['Low']; ?>'],
            ['Medium Priority', '<?php echo $priorityStats['Medium']; ?>'],
            ['High Priority', '<?php echo $priorityStats['High']; ?>'],
            ['', ''],
            ['STATUS DISTRIBUTION', ''],
        ];
        
        // Add status distribution
        <?php
        foreach($statuses as $index => $status) {
            echo "data.push(['$status', '{$statusStats[$index]}']);\n";
        }
        ?>
        
        // Add daily trends
        data.push(['', '']);
        data.push(['DAILY TRENDS (Last 7 Days)', '']);
        <?php
        for($i = 0; $i < count($dailyLabels); $i++) {
            echo "data.push(['{$dailyLabels[$i]}', '{$dailyTrends[$i]}']);\n";
        }
        ?>
        
        // Add technical performance
        data.push(['', '']);
        data.push(['TOP TECHNICAL PERFORMANCE', '']);
        <?php
        for($i = 0; $i < count($techLabels); $i++) {
            echo "data.push(['{$techLabels[$i]}', '{$techData[$i]}%']);\n";
        }
        ?>
        
        // Add common solutions
        <?php if (!empty($commonSolutions)): ?>
        data.push(['', '']);
        data.push(['MOST COMMON SOLUTIONS', '']);
        <?php
        foreach($commonSolutions as $solution) {
            echo "data.push(['" . addslashes(substr($solution['solution'], 0, 50)) . "...', '{$solution['frequency']} times']);\n";
        }
        ?>
        <?php endif; ?>
        
        // Add resolved tickets details
        data.push(['', '']);
        data.push(['RESOLVED TICKETS DETAILS', '']);
        data.push(['Ticket #', 'Company', 'Solution', 'Resolved Date', 'Resolution Time', 'Technician']);
        
        <?php foreach ($allResolvedDetails as $ticket): 
            $hours = $ticket['hours_to_resolve'];
            if ($hours < 24) {
                $timeDisplay = $hours . ' hours';
            } else {
                $days = floor($hours / 24);
                $remainingHours = $hours % 24;
                $timeDisplay = $days . ' days ' . ($remainingHours > 0 ? $remainingHours . ' hours' : '');
            }
        ?>
        data.push([
            '#<?php echo $ticket['ticket_id']; ?>',
            '<?php echo addslashes($ticket['company_name']); ?>',
            '<?php echo addslashes(substr($ticket['solution'] ?? 'No solution', 0, 100)); ?>',
            '<?php echo date('Y-m-d H:i', strtotime($ticket['finish_date'])); ?>',
            '<?php echo $timeDisplay; ?>',
            '<?php echo addslashes($ticket['tech_name'] ?? 'Unassigned'); ?>'
        ]);
        <?php endforeach; ?>
        
        // Create worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(data);
        
        // Set column widths
        ws['!cols'] = [
            { wch: 30 },
            { wch: 30 }
        ];
        
        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Ticket Report');
        
        // Save file
        XLSX.writeFile(wb, `ticket_report_${dateStr}.xlsx`);
        
        showNotification('Excel file downloaded successfully!', 'success');
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            ${message}
        `;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
</body>
</html>