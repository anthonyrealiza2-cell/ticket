<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - TicketFlow</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-success" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="flex justify-between align-center" style="flex-wrap: wrap; gap: 15px;">
                <div class="flex" style="gap: 15px; flex-wrap: wrap;">
                    <div>
                        <label class="form-label">From Date</label>
                        <input type="date" id="dateFrom" class="form-control" style="width: 200px;" 
                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div>
                        <label class="form-label">To Date</label>
                        <input type="date" id="dateTo" class="form-control" style="width: 200px;" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="applyDateFilter()" style="height: fit-content;">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </div>

        <?php
        // Get date filters
        $dateFrom = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
        $dateTo = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
        
        // =============================================
        // FETCH ALL CHART DATA
        // =============================================
        
        // 1. Status Distribution Data
        $statuses = ['Pending', 'Assigned', 'In Progress', 'Resolved', 'Unresolved', 'Closed'];
        $statusColors = ['#ff7675', '#74b9ff', '#fdcb6e', '#00b894', '#ff9f43', '#6c5ce7'];
        $statusData = [];
        
        foreach($statuses as $status) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE status = ? AND DATE(date_requested) BETWEEN ? AND ?");
            $stmt->execute([$status, $dateFrom, $dateTo]);
            $statusData[] = $stmt->fetch()['count'];
        }
        
        // 2. Priority Distribution Data
        $priorities = ['Low', 'Medium', 'High'];
        $priorityColors = ['#00b894', '#fdcb6e', '#ff7675'];
        $priorityData = [];
        
        foreach($priorities as $priority) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE priority = ? AND DATE(date_requested) BETWEEN ? AND ?");
            $stmt->execute([$priority, $dateFrom, $dateTo]);
            $priorityData[] = $stmt->fetch()['count'];
        }
        
        // 3. Daily Trends (Last 7 days)
        $dailyLabels = [];
        $dailyData = [];
        for($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE DATE(date_requested) = ?");
            $stmt->execute([$date]);
            $dailyData[] = $stmt->fetch()['count'];
            $dailyLabels[] = date('M d', strtotime($date));
        }
        
        // 4. Technical Performance Data (Top 5)
        $techNames = [];
        $techResolved = [];
        $techTotal = [];
        $techPerformance = [];
        
        $stmt = $pdo->query("SELECT 
                             CONCAT(firstname, ' ', lastname) as name,
                             resolve,
                             total_ticket,
                             ROUND((resolve / NULLIF(total_ticket, 0)) * 100, 1) as performance
                             FROM technical_staff 
                             WHERE total_ticket > 0 
                             ORDER BY performance DESC 
                             LIMIT 5");
        while($tech = $stmt->fetch()) {
            $techNames[] = $tech['name'];
            $techResolved[] = $tech['resolve'];
            $techTotal[] = $tech['total_ticket'];
            $techPerformance[] = $tech['performance'];
        }
        
        // 5. Summary Statistics
        // Total tickets in period
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $totalTickets = $stmt->fetch()['total'];
        
        // Resolved tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Resolved' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $resolvedTickets = $stmt->fetch()['total'];
        
        // Pending tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status IN ('Pending', 'Assigned', 'In Progress') AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $pendingTickets = $stmt->fetch()['total'];
        
        // Unresolved tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'Unresolved' AND DATE(date_requested) BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $unresolvedTickets = $stmt->fetch()['total'];
        
        // Resolution rate
        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;
        ?>

        <!-- Summary Cards -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info">
                    <h3>Total Tickets</h3>
                    <div class="stat-number"><?php echo $totalTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle" style="background: var(--success);"></i></div>
                <div class="stat-info">
                    <h3>Resolved</h3>
                    <div class="stat-number"><?php echo $resolvedTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock" style="background: var(--warning);"></i></div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <div class="stat-number"><?php echo $pendingTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-circle" style="background: #ff9f43;"></i></div>
                <div class="stat-info">
                    <h3>Unresolved</h3>
                    <div class="stat-number"><?php echo $unresolvedTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-percent" style="background: var(--info);"></i></div>
                <div class="stat-info">
                    <h3>Resolution Rate</h3>
                    <div class="stat-number"><?php echo $resolutionRate; ?>%</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 25px;">
            <!-- Ticket Status Chart -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Ticket Status Distribution
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Priority Distribution Chart -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Priority Levels
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 25px;">
            <!-- Daily Trends -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Daily Ticket Trends (Last 7 Days)
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Technical Performance -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i> Top Technical Staff Performance
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="techChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-simple"></i> Detailed Statistics
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Count</th>
                            <th>Percentage</th>
                            <th>Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Status breakdown
                        foreach($statuses as $index => $status) {
                            $count = $statusData[$index];
                            $percentage = $totalTickets > 0 ? round(($count / $totalTickets) * 100, 1) : 0;
                            $color = $statusColors[$index];
                            echo "<tr>";
                            echo "<td><strong>{$status}</strong></td>";
                            echo "<td>{$count}</td>";
                            echo "<td>{$percentage}%</td>";
                            echo "<td>
                                    <div class='progress-bar' style='width: 200px;'>
                                        <div class='progress' style='width: {$percentage}%; background: {$color};'>{$percentage}%</div>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        // Priority breakdown
                        foreach($priorities as $index => $priority) {
                            $count = $priorityData[$index];
                            $percentage = $totalTickets > 0 ? round(($count / $totalTickets) * 100, 1) : 0;
                            $color = $priorityColors[$index];
                            echo "<tr>";
                            echo "<td><strong>{$priority} Priority</strong></td>";
                            echo "<td>{$count}</td>";
                            echo "<td>{$percentage}%</td>";
                            echo "<td>
                                    <div class='progress-bar' style='width: 200px;'>
                                        <div class='progress' style='width: {$percentage}%; background: {$color};'>{$percentage}%</div>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Wait for DOM to load completely
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing charts...');
        
        // =============================================
        // 1. Status Chart (Pie Chart)
        // =============================================
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($statuses); ?>,
                    datasets: [{
                        data: <?php echo json_encode($statusData); ?>,
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
            console.log('Status chart created');
        }

        // =============================================
        // 2. Priority Chart (Bar Chart)
        // =============================================
        const priorityCtx = document.getElementById('priorityChart');
        if (priorityCtx) {
            new Chart(priorityCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($priorities); ?>,
                    datasets: [{
                        label: 'Number of Tickets',
                        data: <?php echo json_encode($priorityData); ?>,
                        backgroundColor: <?php echo json_encode($priorityColors); ?>,
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
            console.log('Priority chart created');
        }

        // =============================================
        // 3. Trends Chart (Line Chart)
        // =============================================
        const trendsCtx = document.getElementById('trendsChart');
        if (trendsCtx) {
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dailyLabels); ?>,
                    datasets: [{
                        label: 'Tickets Created',
                        data: <?php echo json_encode($dailyData); ?>,
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
            console.log('Trends chart created');
        }

        // =============================================
        // 4. Technical Performance Chart (Bar Chart)
        // =============================================
        const techCtx = document.getElementById('techChart');
        if (techCtx) {
            new Chart(techCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($techNames); ?>,
                    datasets: [{
                        label: 'Performance %',
                        data: <?php echo json_encode($techPerformance); ?>,
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
                                    let value = context.raw || 0;
                                    let index = context.dataIndex;
                                    return [
                                        `Performance: ${value}%`,
                                        `Resolved: ${<?php echo json_encode($techResolved); ?>[index]}`,
                                        `Total: ${<?php echo json_encode($techTotal); ?>[index]}`
                                    ];
                                }
                            }
                        }
                    }
                }
            });
            console.log('Technical chart created');
        }
    });

    // =============================================
    // HELPER FUNCTIONS
    // =============================================
    
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
        } else {
            // Export to CSV
            const data = [
                ['Report Summary', 'Value'],
                ['Date Range', `${document.getElementById('dateFrom').value} to ${document.getElementById('dateTo').value}`],
                ['Total Tickets', '<?php echo $totalTickets; ?>'],
                ['Resolved Tickets', '<?php echo $resolvedTickets; ?>'],
                ['Pending Tickets', '<?php echo $pendingTickets; ?>'],
                ['Unresolved Tickets', '<?php echo $unresolvedTickets; ?>'],
                ['Resolution Rate', '<?php echo $resolutionRate; ?>%'],
                [],
                ['Status Breakdown', 'Count'],
                <?php
                foreach($statuses as $index => $status) {
                    echo "['{$status}', '{$statusData[$index]}'],";
                }
                ?>
                [],
                ['Priority Breakdown', 'Count'],
                <?php
                foreach($priorities as $index => $priority) {
                    echo "['{$priority}', '{$priorityData[$index]}'],";
                }
                ?>
            ];
            
            let csv = '';
            data.forEach(row => {
                csv += row.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ticket_report_${new Date().toISOString().slice(0,10)}.csv`;
            a.click();
        }
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

    <style>
    /* Print styles for PDF export */
    @media print {
        .navbar, .btn, .modal {
            display: none !important;
        }
        
        body {
            background: white;
            padding: 20px;
        }
        
        .card {
            border: 1px solid #ddd;
            box-shadow: none;
            break-inside: avoid;
            background: white;
        }
        
        canvas {
            max-height: 250px;
        }
        
        * {
            color: black !important;
        }
    }
    </style>
</body>
</html>