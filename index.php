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
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/tickets.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Ticket Details Styling */
        .ticket-detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
        }
        
        .ticket-detail-item i {
            width: 24px;
            font-size: 1.2rem;
            color: var(--accent-primary);
            margin-top: 2px;
        }
        
        .ticket-detail-content {
            flex: 1;
        }
        
        .ticket-detail-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        
        .ticket-detail-value {
            color: var(--text-primary);
            font-weight: 500;
            word-break: break-word;
        }
        
        .ticket-description {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 10px;
            white-space: pre-line;
            line-height: 1.6;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            margin-top: 10px;
        }
        
        .badge-container {
            display: inline-block;
        }
        
        .no-data {
            color: var(--text-muted);
            font-style: italic;
        }
    </style>
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
                        $stmt = $pdo->query("
                            SELECT t.*, c.company_name, c.contact_person 
                            FROM tickets t 
                            LEFT JOIN clients c ON t.company_id = c.client_id 
                            ORDER BY t.created_at DESC LIMIT 10
                        ");
                        while($ticket = $stmt->fetch()):
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                        ?>
                            <tr>
                                <td>#<?= $ticket['ticket_id'] ?></td>
                                <td><?= htmlspecialchars($ticket['company_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($ticket['contact_person'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(substr($ticket['concern_description'] ?? '', 0, 30)) ?>...</td>
                                <td><span class='badge badge-<?= $priorityClass ?>'><?= $ticket['priority'] ?></span></td>
                                <td><span class='badge badge-<?= $statusClass ?>'><?= $ticket['status'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($ticket['date_requested'] ?? date('Y-m-d'))) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-sm' onclick='viewTicket(<?= $ticket['ticket_id'] ?>)'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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
                            <th>Total Tickets</th>
                            <th>Resolved</th>
                            <th>Performance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT * FROM vw_tech_performance 
                            WHERE total_ticket > 0 
                            ORDER BY performance_rate DESC 
                            LIMIT 5
                        ");
                        while($tech = $stmt->fetch()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($tech['full_name']) ?></td>
                                <td><?= $tech['total_ticket'] ?></td>
                                <td><?= $tech['resolve'] ?></td>
                                <td>
                                    <div class='progress-bar'>
                                        <div class='progress' style='width: <?= $tech['performance_rate'] ?>%'><?= $tech['performance_rate'] ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="ticketDetails" style="max-height: 70vh; overflow-y: auto; padding-right: 10px;"></div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function viewTicket(ticketId) {
        // Show loading state
        const details = document.getElementById('ticketDetails');
        details.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><p style="margin-top: 20px;">Loading ticket details...</p></div>';
        
        const modal = document.getElementById('viewTicketModal');
        modal.style.display = 'flex';
        
        fetch(`get-tickets.php?id=${ticketId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    details.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    return;
                }
                
                // Log the data to console for debugging
                console.log('Ticket data:', data);
                
                const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
                const priorityClass = (data.priority || 'medium').toLowerCase();
                
                // Format contact info with fallbacks
                const contactNumber = data.contact_number && data.contact_number.trim() !== '' 
                    ? data.contact_number 
                    : '<span class="no-data">Not provided</span>';
                    
                const email = data.email && data.email.trim() !== '' 
                    ? data.email 
                    : '<span class="no-data">Not provided</span>';
                
                details.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div class="ticket-detail-item">
                            <i class="fas fa-hashtag"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Ticket ID</div>
                                <div class="ticket-detail-value">#${data.ticket_id}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-building"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Company</div>
                                <div class="ticket-detail-value">${data.company_name || '<span class="no-data">Not specified</span>'}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-user"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Contact Person</div>
                                <div class="ticket-detail-value">${data.contact_person || '<span class="no-data">Not specified</span>'}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-phone"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Contact Number</div>
                                <div class="ticket-detail-value">${contactNumber}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-envelope"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Email</div>
                                <div class="ticket-detail-value">${email}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-box"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Product</div>
                                <div class="ticket-detail-value">${data.product_name || 'N/A'} ${data.version || ''}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Concern Type</div>
                                <div class="ticket-detail-value">${data.concern_type || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-align-left"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Description</div>
                                <div class="ticket-description">${data.concern_description || 'No description provided'}</div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-flag"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Priority</div>
                                <div class="ticket-detail-value">
                                    <span class='badge badge-${priorityClass}'>${data.priority || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-info-circle"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Status</div>
                                <div class="ticket-detail-value">
                                    <span class='badge badge-${statusClass}'>${data.status || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ticket-detail-item">
                            <i class="fas fa-calendar"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Date Requested</div>
                                <div class="ticket-detail-value">${data.date_requested ? new Date(data.date_requested).toLocaleString() : 'N/A'}</div>
                            </div>
                        </div>
                        
                        ${data.tech_firstname ? `
                        <div class="ticket-detail-item">
                            <i class="fas fa-user-cog"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Assigned To</div>
                                <div class="ticket-detail-value">${data.tech_firstname} ${data.tech_lastname}</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${data.finish_date ? `
                        <div class="ticket-detail-item">
                            <i class="fas fa-check-circle"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Date Finished</div>
                                <div class="ticket-detail-value">${new Date(data.finish_date).toLocaleString()}</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${data.solution ? `
                        <div class="ticket-detail-item">
                            <i class="fas fa-check-circle"></i>
                            <div class="ticket-detail-content">
                                <div class="ticket-detail-label">Solution</div>
                                <div class="ticket-detail-value">${data.solution}</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                details.innerHTML = `<div class="alert alert-danger">Error fetching ticket details: ${error.message}</div>`;
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