<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Tickets - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/tickets.css">
    <link rel="stylesheet" href="../css/modal.css">
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
                <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="new-ticket.php" class="nav-link"><i class="fas fa-plus-circle"></i> New Ticket</a>
                <a href="tickets.php" class="nav-link"><i class="fas fa-list"></i> Tickets</a>
                <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="technical.php" class="nav-link"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-archive"></i> Archived Tickets</h1>
            <div class="header-actions">
                <a href="tickets.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
            </div>
        </div>

        <!-- Archived Tickets Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Archived Tickets List
            </div>
            <div class="table-container">
                <table id="archiveTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Concern</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Archived Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT a.*, 
                                   c.company_name, 
                                   c.contact_person,
                                   CONCAT(ts.firstname, ' ', ts.lastname) as tech_name
                            FROM tickets_archive a
                            LEFT JOIN clients c ON a.company_id = c.client_id
                            LEFT JOIN technical_staff ts ON a.technical_id = ts.technical_id
                            ORDER BY a.archived_at DESC
                        ");
                        
                        while ($ticket = $stmt->fetch()):
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                        ?>
                            <tr>
                                <td>#<?= $ticket['ticket_id'] ?></td>
                                <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['contact_person']) ?></td>
                                <td><?= htmlspecialchars(substr($ticket['concern_description'] ?? '', 0, 50)) ?>...</td>
                                <td><span class='badge badge-<?= $priorityClass ?>'><?= $ticket['priority'] ?></span></td>
                                <td><span class='badge badge-<?= $statusClass ?>'><?= $ticket['status'] ?></span></td>
                                <td><?= date('M d, Y H:i', strtotime($ticket['archived_at'])) ?></td>
                                <td><?= htmlspecialchars($ticket['archive_reason'] ?? 'N/A') ?></td>
                                <td class='actions-cell'>
                                    <button class='btn btn-success btn-sm' onclick='restoreTicket(<?= $ticket["ticket_id"] ?>)' title='Restore Ticket'>
                                        <i class='fas fa-trash-restore'></i>
                                    </button>
                                    <button class='btn btn-info btn-sm' onclick='viewArchivedTicket(<?= $ticket["ticket_id"] ?>)' title='View Details'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Archived Ticket Modal -->
    <div class="modal" id="viewArchiveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Archived Ticket Details</h2>
                <button class="modal-close" onclick="closeModal('viewArchiveModal')">&times;</button>
            </div>
            <div id="archiveTicketDetails"></div>
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewArchiveModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
    function restoreTicket(id) {
        if(confirm('Are you sure you want to restore this ticket?')) {
            showLoading();
            
            fetch('../restore-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    ticket_id: id,
                    user_id: 1 // Replace with actual user ID from session
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if(data.success) {
                    showNotification('Ticket restored successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Error restoring ticket', 'danger');
            });
        }
    }

    function viewArchivedTicket(id) {
        showLoading();
        
        fetch(`../get-archived-ticket.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.error) {
                    showNotification(data.error, 'danger');
                    return;
                }
                
                const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
                const priorityClass = (data.priority || 'medium').toLowerCase();
                
                document.getElementById('archiveTicketDetails').innerHTML = `
                    <div style="display: grid; gap: 15px;">
                        <p><strong><i class="fas fa-hashtag"></i> Ticket ID:</strong> #${data.ticket_id}</p>
                        <p><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name || 'N/A'}</p>
                        <p><strong><i class="fas fa-user"></i> Contact:</strong> ${data.contact_person || 'N/A'}</p>
                        <p><strong><i class="fas fa-phone"></i> Contact Number:</strong> ${data.contact_number || 'N/A'}</p>
                        <p><strong><i class="fas fa-box"></i> Product:</strong> ${data.product_name || 'N/A'} ${data.version || ''}</p>
                        <p><strong><i class="fas fa-exclamation-triangle"></i> Concern:</strong> ${data.concern_type || 'N/A'}</p>
                        <p><strong><i class="fas fa-align-left"></i> Description:</strong></p>
                        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 10px;">${data.concern_description || ''}</div>
                        <p><strong><i class="fas fa-flag"></i> Priority:</strong> 
                            <span class='badge badge-${priorityClass}'>${data.priority}</span>
                        </p>
                        <p><strong><i class="fas fa-info-circle"></i> Status:</strong> 
                            <span class='badge badge-${statusClass}'>${data.status}</span>
                        </p>
                        <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> 
                            ${new Date(data.date_requested).toLocaleString()}
                        </p>
                        <p><strong><i class="fas fa-archive"></i> Archived Date:</strong> 
                            ${new Date(data.archived_at).toLocaleString()}
                        </p>
                        <p><strong><i class="fas fa-comment"></i> Archive Reason:</strong> 
                            ${data.archive_reason || 'N/A'}
                        </p>
                        ${data.solution ? `<p><strong><i class="fas fa-check-circle"></i> Solution:</strong> ${data.solution}</p>` : ''}
                    </div>
                `;
                
                openModal('viewArchiveModal');
            })
            .catch(error => {
                hideLoading();
                showNotification('Error fetching ticket details', 'danger');
            });
    }

    function showLoading() {
        const spinner = document.createElement('div');
        spinner.id = 'loadingSpinner';
        spinner.className = 'modal';
        spinner.style.background = 'rgba(0,0,0,0.5)';
        spinner.innerHTML = '<div class="spinner"></div>';
        spinner.style.display = 'flex';
        document.body.appendChild(spinner);
    }

    function hideLoading() {
        const spinner = document.getElementById('loadingSpinner');
        if(spinner) spinner.remove();
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
        
        setTimeout(() => notification.remove(), 3000);
    }

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['viewArchiveModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };
    </script>
</body>
</html>