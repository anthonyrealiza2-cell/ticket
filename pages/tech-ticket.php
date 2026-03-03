<?php
require_once '../database.php';

// Get technician ID from URL
$tech_id = isset($_GET['tech_id']) ? $_GET['tech_id'] : null;

if (!$tech_id) {
    header('Location: technical.php');
    exit;
}

// Get technician info
$techStmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
$techStmt->execute([$tech_id]);
$technician = $techStmt->fetch();

if (!$technician) {
    header('Location: technical.php');
    exit;
}

// Get tickets assigned to this technician using direct JOIN
$ticketStmt = $pdo->prepare("
    SELECT 
        t.ticket_id,
        t.technical_id,
        t.assigned_date,
        t.product_id,
        t.concern_id,
        t.concern_description,
        t.date_requested,
        t.submitted_date,
        t.finish_date,
        t.solution,
        t.remarks,
        t.priority,
        t.status,
        t.assigned,
        t.created_at,
        t.updated_at,
        c.company_name,
        c.contact_person,
        c.email,
        c.contact_number,
        p.product_name,
        p.version,
        cn.concern_name AS concern_type,
        ts.firstname AS tech_firstname,
        ts.lastname AS tech_lastname
    FROM tickets t
    LEFT JOIN clients c ON t.company_id = c.client_id
    LEFT JOIN products p ON t.product_id = p.product_id
    LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
    LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
    WHERE t.technical_id = ?
    ORDER BY t.created_at DESC
");
$ticketStmt->execute([$tech_id]);
$tickets = $ticketStmt->fetchAll();
$ticketCount = count($tickets);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?> - Tickets | TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/tickets.css">
    <link rel="stylesheet" href="../css/tech-ticket.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
 
</head>
<body class="technical-page">
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
                <a href="technical.php" class="nav-link active"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Back Button -->
        <div class="back-button">
            <a href="technical.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Technical Staff
            </a>
        </div>

        <!-- Header with Export -->
        <div class="flex justify-between" style="margin-bottom: 20px;">
            <div></div>
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-excel" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Technician Header -->
        <div class="tech-header">
            <h1>
                <i class="fas fa-user-cog"></i> 
                <?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?>
            </h1>
            <p>
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($technician['email']); ?> • 
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($technician['contact_viber']); ?> • 
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($technician['branch']); ?> • 
                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($technician['position']); ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="tech-stats">
            <?php
            // Get performance from view
            $perfStmt = $pdo->prepare("SELECT * FROM vw_tech_performance WHERE technical_id = ?");
            $perfStmt->execute([$tech_id]);
            $performance = $perfStmt->fetch();
            
            $resolved = $performance['resolve'] ?? 0;
            $total = $performance['total_ticket'] ?? 0;
            $pending = $performance['pending'] ?? 0;
            $rate = $performance['performance_rate'] ?? 0;
            ?>
            
            <div class="tech-stat-card">
                <div class="tech-stat-value"><?php echo $total; ?></div>
                <div class="tech-stat-label">Total Assigned Tickets</div>
            </div>
            
            <div class="tech-stat-card">
                <div class="tech-stat-value" style="color: var(--success);"><?php echo $resolved; ?></div>
                <div class="tech-stat-label">Resolved</div>
            </div>
            
            <div class="tech-stat-card">
                <div class="tech-stat-value" style="color: var(--warning);"><?php echo $pending; ?></div>
                <div class="tech-stat-label">Active Tickets</div>
            </div>
            
            <div class="tech-stat-card">
                <div class="tech-stat-value" style="color: var(--info);"><?php echo $rate; ?>%</div>
                <div class="tech-stat-label">Performance Rate</div>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-ticket-alt"></i> Assigned Tickets
            </div>
            
            <?php if ($ticketCount > 0): ?>
                <div class="table-container">
                    <table id="ticketsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Concern</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date Assigned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): 
                                $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                                $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                            ?>
                            <tr data-status="<?= $ticket['status'] ?>" data-ticket-id="<?= $ticket['ticket_id'] ?>">
                                <td>#<?= $ticket['ticket_id'] ?></td>
                                <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['contact_person']) ?></td>
                                <td><?= htmlspecialchars(substr($ticket['concern_description'] ?? '', 0, 50)) ?>...</td>
                                <td><span class='badge badge-<?= $priorityClass ?>'><?= $ticket['priority'] ?></span></td>
                                <td><span class='badge badge-<?= $statusClass ?>'><?= $ticket['status'] ?></span></td>
                                <td><?= isset($ticket['assigned_date']) && $ticket['assigned_date'] ? date('M d, Y', strtotime($ticket['assigned_date'])) : 'N/A' ?></td>
                                <td class="actions-cell">
                                    <div class='actions-menu-container'>
                                        <button class='actions-menu-btn' onclick='toggleActionsMenu(this, event)'>
                                            <i class='fas fa-ellipsis-v'></i>
                                        </button>
                                        <div class='actions-dropdown'>
                                            <button class='actions-dropdown-item view' onclick='viewTicket(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-eye'></i> View Details
                                            </button>
                                            <button class='actions-dropdown-item edit' onclick='updateStatus(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-edit'></i> Update Status
                                            </button>
                                            <div class='actions-dropdown-divider'></div>
                                            <button class='actions-dropdown-item delete' onclick='confirmDelete(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-trash-alt'></i> Delete Ticket
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>No Tickets Assigned</h3>
                    <p>This technician currently has no tickets assigned to them.</p>
                    <a href="tickets.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Assign Tickets
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal('viewTicketModal')">&times;</button>
            </div>
            <div id="ticketDetails"></div>
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewTicketModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Ticket Status</h2>
                <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form id="statusForm" onsubmit="updateStatusSubmit(event)">
                <input type="hidden" id="statusTicketId">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="ticketStatus" required>
                        <option value="Pending">Pending</option>
                        <option value="Assigned">Assigned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Solution / Remarks</label>
                    <textarea class="form-control" id="solution" rows="4" placeholder="Enter solution or remarks..."></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Delete Ticket</h2>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Are you sure?</h3>
                <p>This action cannot be undone. The ticket will be permanently deleted.</p>
                <input type="hidden" id="deleteTicketId">
                <div class="delete-actions">
                    <button class="btn btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button class="btn btn-delete" onclick="deleteTicket()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // State Management
        let pendingTicketId = null;

        // Menu Management - FIXED POSITIONING WITH AUTO UPWARD ADJUSTMENT
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.actions-menu-container')) {
                document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                    menu.classList.remove('show');
                    menu.classList.remove('upward');
                });
            }
        });

        function toggleActionsMenu(button, event) {
            event.stopPropagation();
            
            const dropdown = button.nextElementSibling;
            const isShowing = dropdown.classList.contains('show');
            
            // Close all other menus
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
                menu.classList.remove('upward');
            });
            
            if (!isShowing) {
                // Get button position and viewport dimensions
                const rect = button.getBoundingClientRect();
                const dropdownHeight = 200; // Approximate height of dropdown
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                
                // Remove any existing positioning classes
                dropdown.classList.remove('upward');
                
                // Position dropdown based on available space
                if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                    // Not enough space below, open upward
                    dropdown.classList.add('upward');
                    dropdown.style.top = 'auto';
                    dropdown.style.bottom = '100%';
                } else {
                    // Enough space below, open downward normally
                    dropdown.style.top = '100%';
                    dropdown.style.bottom = 'auto';
                }
                
                // Ensure dropdown stays within viewport horizontally
                dropdown.style.right = '0';
                
                dropdown.classList.add('show');
            }
        }

        // Ticket View Function
        function viewTicket(id) {
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
                menu.classList.remove('upward');
            });
            
            showLoading();
            
            fetch(`../get-tickets.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.error) {
                        showNotification(data.error, 'danger');
                        return;
                    }
                    
                    const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
                    const priorityClass = (data.priority || 'medium').toLowerCase();
                    
                    // Format contact info
                    const contactNumber = data.contact_number || 'Not provided';
                    const email = data.email || 'Not provided';
                    
                    document.getElementById('ticketDetails').innerHTML = `
                        <div style="display: grid; gap: 15px;">
                            <p><strong><i class="fas fa-hashtag"></i> Ticket ID:</strong> #${data.ticket_id}</p>
                            <p><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name || 'N/A'}</p>
                            <p><strong><i class="fas fa-user"></i> Contact Person:</strong> ${data.contact_person || 'N/A'}</p>
                            <p><strong><i class="fas fa-phone"></i> Contact Number:</strong> ${contactNumber}</p>
                            <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${email}</p>
                            <p><strong><i class="fas fa-box"></i> Product:</strong> ${data.product_name || 'N/A'} ${data.version || ''}</p>
                            <p><strong><i class="fas fa-exclamation-triangle"></i> Concern Type:</strong> ${data.concern_type || 'N/A'}</p>
                            <p><strong><i class="fas fa-align-left"></i> Description:</strong></p>
                            <div style="background: var(--bg-secondary); padding: 15px; border-radius: 10px; white-space: pre-line;">${data.concern_description || ''}</div>
                            <p><strong><i class="fas fa-flag"></i> Priority:</strong> 
                                <span class='badge badge-${priorityClass}'>${data.priority}</span>
                            </p>
                            <p><strong><i class="fas fa-info-circle"></i> Status:</strong> 
                                <span class='badge badge-${statusClass}'>${data.status}</span>
                            </p>
                            <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> 
                                ${data.date_requested ? new Date(data.date_requested).toLocaleString() : 'N/A'}
                            </p>
                            <p><strong><i class="fas fa-clock"></i> Date Assigned:</strong> 
                                ${data.assigned_date ? new Date(data.assigned_date).toLocaleString() : 'N/A'}
                            </p>
                            ${data.tech_firstname ? `<p><strong><i class="fas fa-user-cog"></i> Assigned To:</strong> ${data.tech_firstname} ${data.tech_lastname}</p>` : ''}
                            ${data.solution ? `<p><strong><i class="fas fa-check-circle"></i> Solution:</strong> ${data.solution}</p>` : ''}
                            ${data.finish_date ? `<p><strong><i class="fas fa-clock"></i> Date Finished:</strong> ${new Date(data.finish_date).toLocaleString()}</p>` : ''}
                        </div>
                    `;
                    
                    openModal('viewTicketModal');
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error fetching ticket details: ' + error.message, 'danger');
                });
        }

        // Status Update Functions
        function updateStatus(id) {
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
                menu.classList.remove('upward');
            });
            
            document.getElementById('statusTicketId').value = id;
            openModal('statusModal');
        }

        function updateStatusSubmit(event) {
            event.preventDefault();
            
            const ticketId = document.getElementById('statusTicketId').value;
            const status = document.getElementById('ticketStatus').value;
            const solution = document.getElementById('solution').value;
            
            if (!ticketId) {
                showNotification('No ticket selected', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../update-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    status: status,
                    solution: solution,
                    action: 'status'
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Status updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error: ' + error.message, 'danger');
            });
        }

        // Delete Functions
        function confirmDelete(ticketId) {
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
                menu.classList.remove('upward');
            });
            
            document.getElementById('deleteTicketId').value = ticketId;
            openModal('deleteModal');
        }

        function deleteTicket() {
            const ticketId = document.getElementById('deleteTicketId').value;
            
            if (!ticketId) {
                showNotification('No ticket selected', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../delete-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                closeModal('deleteModal');
                
                if (data.success) {
                    showNotification('Ticket deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error: ' + error.message, 'danger');
            });
        }

        // Export Functions
        function exportToExcel() {
            const table = document.getElementById('ticketsTable');
            const rows = table.querySelectorAll('tr');
            const data = [];
            
            // Headers
            const headers = [];
            rows[0].querySelectorAll('th').forEach((th, index) => {
                if (index < rows[0].querySelectorAll('th').length - 1) {
                    headers.push(th.innerText);
                }
            });
            data.push(headers);
            
            // Rows
            for (let i = 1; i < rows.length; i++) {
                const row = [];
                rows[i].querySelectorAll('td').forEach((td, index) => {
                    if (index < rows[i].querySelectorAll('td').length - 1) {
                        let value = td.innerText.trim();
                        if (index === 0) value = value.replace('#', '');
                        if (index === 3) value = value.replace('...', '');
                        row.push(value);
                    }
                });
                data.push(row);
            }
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            ws['!cols'] = [
                { wch: 10 }, { wch: 25 }, { wch: 20 }, { wch: 50 },
                { wch: 12 }, { wch: 12 }, { wch: 15 }, { wch: 15 }
            ];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Technician Tickets');
            XLSX.writeFile(wb, `tech_tickets_${<?= $tech_id ?>}_${formatDate(new Date())}.xlsx`);
            
            showNotification('Excel file downloaded successfully!', 'success');
        }

        // Utility Functions
        function formatDate(date) {
            return date.toISOString().slice(0, 10);
        }

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Loading Functions
        function showLoading() {
            if (!document.getElementById('loadingSpinner')) {
                const spinner = document.createElement('div');
                spinner.id = 'loadingSpinner';
                spinner.className = 'modal';
                spinner.style.background = 'rgba(0, 0, 0, 0.5)';
                spinner.style.backdropFilter = 'blur(3px)';
                spinner.innerHTML = '<div class="spinner"></div>';
                spinner.style.display = 'flex';
                document.body.appendChild(spinner);
            }
        }

        function hideLoading() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.remove();
        }

        // Notification Function
        function showNotification(message, type) {
            document.querySelectorAll('.alert').forEach(n => n.remove());
            
            const icons = {
                success: 'fa-check-circle',
                warning: 'fa-exclamation-triangle',
                danger: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `
                <i class="fas ${icons[type] || icons.info}"></i>
                ${message}
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: var(--shadow-lg);
            `;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['viewTicketModal', 'statusModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Handle window resize to close dropdowns
        window.addEventListener('resize', function() {
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
                menu.classList.remove('upward');
            });
        });
    </script>
</body>
</html>