<?php
require_once 'database.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TicketFlow - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="pages/new-ticket.php" class="nav-link"><i class="fas fa-plus-circle"></i> New Ticket</a>
                <a href="pages/tickets.php" class="nav-link"><i class="fas fa-list"></i> Tickets</a>
                <a href="pages/clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="pages/technical.php" class="nav-link"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="pages/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php
            // Get total tickets
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
            $totalTickets = $stmt->fetch()['total'] ?? 0;
            
            // Get pending tickets
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'Pending'");
            $pendingTickets = $stmt->fetch()['total'] ?? 0;
            
            // Get resolved tickets
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'Resolved'");
            $resolvedTickets = $stmt->fetch()['total'] ?? 0;
            
            // Get technical staff count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM technical_staff");
            $technicalCount = $stmt->fetch()['total'] ?? 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info">
                    <h3>Total Tickets</h3>
                    <div class="stat-number"><?php echo $totalTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <div class="stat-number"><?php echo $pendingTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3>Resolved</h3>
                    <div class="stat-number"><?php echo $resolvedTickets; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
                <div class="stat-info">
                    <h3>Technical Staff</h3>
                    <div class="stat-number"><?php echo $technicalCount; ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Tickets
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Concern</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM tickets ORDER BY created_at DESC LIMIT 10");
                        while($ticket = $stmt->fetch()) {
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower($ticket['status'] ?? 'pending');
                            echo "<tr>";
                            echo "<td>#{$ticket['ticket_id']}</td>";
                            echo "<td>{$ticket['company_name']}</td>";
                            echo "<td>{$ticket['contact_person']}</td>";
                            echo "<td>" . substr($ticket['concern'] ?? '', 0, 30) . "...</td>";
                            echo "<td><span class='badge badge-{$priorityClass}'>{$ticket['priority']}</span></td>";
                            echo "<td><span class='badge badge-{$statusClass}'>{$ticket['status']}</span></td>";
                            echo "<td>" . date('M d, Y', strtotime($ticket['date_requested'] ?? date('Y-m-d'))) . "</td>";
                            echo "<td>
                                    <button class='btn btn-primary btn-sm' onclick='viewTicket({$ticket['ticket_id']})'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performing Technicals -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i> Top Performing Technicals
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Total Tickets</th>
                            <th>Resolved</th>
                            <th>Unresolved</th>
                            <th>Performance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT *, 
                                             (resolve / NULLIF(total_ticket, 0) * 100) as performance_rate 
                                             FROM technical_staff 
                                             WHERE total_ticket > 0 
                                             ORDER BY performance_rate DESC 
                                             LIMIT 5");
                        while($tech = $stmt->fetch()) {
                            $rate = round($tech['performance_rate'] ?? 0, 1);
                            echo "<tr>";
                            echo "<td>{$tech['firstname']} {$tech['lastname']}</td>";
                            echo "<td>{$tech['technical_id']}</td>";
                            echo "<td>{$tech['total_ticket']}</td>";
                            echo "<td>{$tech['resolve']}</td>";
                            echo "<td>{$tech['unresolve']}</td>";
                            echo "<td>
                                    <div class='progress-bar'>
                                        <div class='progress' style='width: {$rate}%'>{$rate}%</div>
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

    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="ticketDetails"></div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function viewTicket(ticketId) {
        fetch(`get-tickets.php?id=${ticketId}`)
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('viewTicketModal');
                const details = document.getElementById('ticketDetails');
                
                details.innerHTML = `
                    <div style="display: grid; gap: 15px;">
                        <p><strong><i class="fas fa-hashtag"></i> Ticket ID:</strong> #${data.ticket_id}</p>
                        <p><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name}</p>
                        <p><strong><i class="fas fa-user"></i> Contact:</strong> ${data.contact_person}</p>
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${data.email}</p>
                        <p><strong><i class="fas fa-phone"></i> Contact #:</strong> ${data.contact_number}</p>
                        <p><strong><i class="fas fa-comment"></i> Concern:</strong> ${data.concern}</p>
                        <p><strong><i class="fas fa-flag"></i> Priority:</strong> 
                            <span class='badge badge-${(data.priority || 'medium').toLowerCase()}'>${data.priority}</span>
                        </p>
                        <p><strong><i class="fas fa-info-circle"></i> Status:</strong> 
                            <span class='badge badge-${(data.status || 'pending').toLowerCase()}'>${data.status}</span>
                        </p>
                        <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> 
                            ${new Date(data.date_requested).toLocaleString()}
                        </p>
                        ${data.technical_personnel ? `<p><strong><i class="fas fa-user-cog"></i> Assigned To:</strong> ${data.technical_personnel}</p>` : ''}
                    </div>
                `;
                
                modal.style.display = 'flex';
            });
    }

    function closeModal() {
        document.getElementById('viewTicketModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('viewTicketModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>