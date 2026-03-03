<?php
require_once '../database.php';

// Build filter conditions
$whereClause = "";
$params = [];

if (isset($_GET['client_id'])) {
    $whereClause = " WHERE t.company_id = ?";
    $params[] = $_GET['client_id'];
} elseif (isset($_GET['tech_id'])) {
    $whereClause = " WHERE t.technical_id = ?";
    $params[] = $_GET['tech_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - TicketFlow</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/tickets.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <style>
        /* Actions Menu Styles */
        .actions-menu-container {
            position: relative;
            display: inline-block;
        }
        
        .actions-menu-btn {
            background: var(--bg-hover);
            border: none;
            color: var(--text-secondary);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .actions-menu-btn:hover {
            background: var(--accent-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }
        
        .actions-menu-btn i {
            font-size: 1.2rem;
        }
        
        .actions-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 0;
            min-width: 180px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: none;
            animation: slideDown 0.2s ease;
        }
        
        .actions-dropdown.show {
            display: block;
        }
        
        .actions-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.95rem;
        }
        
        .actions-dropdown-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        
        .actions-dropdown-item i {
            width: 18px;
            font-size: 1rem;
        }
        
        .actions-dropdown-item.view i { color: var(--info); }
        .actions-dropdown-item.assign i { color: var(--success); }
        .actions-dropdown-item.edit i { color: var(--warning); }
        .actions-dropdown-item.delete i { color: var(--danger); }
        
        .actions-dropdown-divider {
            height: 1px;
            background: var(--border-color);
            margin: 8px 0;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Disabled state for resolved tickets */
        tr[data-status="Resolved"] .actions-dropdown-item.assign,
        tr[data-status="Closed"] .actions-dropdown-item.assign {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Delete Modal */
        .delete-warning {
            text-align: center;
            padding: 20px;
        }
        
        .delete-warning i {
            font-size: 4rem;
            color: var(--danger);
            margin-bottom: 15px;
        }
        
        .delete-warning h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .delete-warning p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .delete-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-delete {
            background: var(--gradient-3);
            color: white;
        }
        
        .btn-delete:hover {
            box-shadow: 0 10px 25px rgba(255, 118, 117, 0.3);
        }
        
        .btn-cancel {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-cancel:hover {
            background: var(--bg-hover);
            border-color: var(--accent-primary);
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
                <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="new-ticket.php" class="nav-link"><i class="fas fa-plus-circle"></i> New Ticket</a>
                <a href="tickets.php" class="nav-link active"><i class="fas fa-list"></i> Tickets</a>
                <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="technical.php" class="nav-link"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Header with Actions -->
        <div class="flex justify-between" style="margin-bottom: 20px;">
            <h1 style="color: var(--text-primary);">Ticket Management</h1>
            <div class="flex">
                <div class="search-box" style="margin-right: 10px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search tickets...">
                </div>
                <button class="btn btn-success" onclick="exportToExcel()" style="background: var(--gradient-2); margin-right: 10px;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn btn-success" onclick="exportTickets()">
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex" style="margin-bottom: 20px; gap: 10px; flex-wrap: wrap;">
            <?php
            $filters = ['all', 'pending', 'assigned', 'in progress', 'resolved'];
            $filterLabels = ['All', 'Pending', 'Assigned', 'In Progress', 'Resolved'];
            foreach ($filters as $index => $filter) {
                echo "<button class='filter-tab' onclick='filterTickets(\"$filter\")'>{$filterLabels[$index]}</button>";
            }
            ?>
        </div>

        <!-- Tickets Table -->
        <div class="card">
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
                            <th>Assigned To</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT t.ticket_id, t.priority, t.status, t.date_requested, t.concern_description,
                                   c.company_name, c.contact_person,
                                   CONCAT(ts.firstname, ' ', ts.lastname) as tech_name 
                            FROM tickets t 
                            LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id 
                            LEFT JOIN clients c ON t.company_id = c.client_id
                            ORDER BY t.created_at DESC
                        ");
                        
                        while ($ticket = $stmt->fetch()):
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                            $isResolved = in_array($ticket['status'], ['Resolved', 'Closed']);
                        ?>
                            <tr data-status="<?= $ticket['status'] ?>" data-ticket-id="<?= $ticket['ticket_id'] ?>">
                                <td>#<?= $ticket['ticket_id'] ?></td>
                                <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['contact_person']) ?></td>
                                <td><?= htmlspecialchars(substr($ticket['concern_description'] ?? '', 0, 50)) ?>...</td>
                                <td><span class='badge badge-<?= $priorityClass ?>'><?= $ticket['priority'] ?></span></td>
                                <td><span class='badge badge-<?= $statusClass ?>'><?= $ticket['status'] ?></span></td>
                                <td><?= $ticket['tech_name'] ?? '<span class="unassigned">Unassigned</span>' ?></td>
                                <td><?= date('M d, Y', strtotime($ticket['date_requested'])) ?></td>
                                <td class='actions-cell'>
                                    <div class='actions-menu-container'>
                                        <button class='actions-menu-btn' onclick='toggleActionsMenu(this)'>
                                            <i class='fas fa-ellipsis-v'></i>
                                        </button>
                                        <div class='actions-dropdown'>
                                            <button class='actions-dropdown-item view' onclick='viewTicket(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-eye'></i> View Details
                                            </button>
                                            <?php if (!$isResolved): ?>
                                            <button class='actions-dropdown-item assign' onclick='checkExistingAssignment(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-user-plus'></i> Assign Technical
                                            </button>
                                            <?php endif; ?>
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
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal('viewTicketModal')">&times;</button>
            </div>
            <div id="ticketDetails"></div>
        </div>
    </div>

    <!-- Assign Technical Modal -->
    <div class="modal" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign Technical Staff</h2>
                <button class="modal-close" onclick="closeModal('assignModal')">&times;</button>
            </div>
            <form id="assignForm" onsubmit="assignTechnical(event)">
                <input type="hidden" id="assignTicketId">
                <div class="form-group">
                    <label class="form-label">Select Technical Staff</label>
                    <select class="form-control" id="technicalId" required>
                        <option value="">Choose staff...</option>
                        <?php
                        $techs = $pdo->query("SELECT * FROM technical_staff ORDER BY firstname");
                        while ($tech = $techs->fetch()):
                        ?>
                            <option value="<?= $tech['technical_id'] ?>">
                                <?= htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' - ' . $tech['position']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('assignModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reassign Confirmation Modal -->
    <div class="modal" id="reassignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> Reassign Technical Staff</h2>
                <button class="modal-close" onclick="closeModal('reassignModal')">&times;</button>
            </div>
            <div id="reassignDetails" style="margin-bottom: 20px; padding: 15px; background: var(--bg-secondary); border-radius: 12px; border-left: 4px solid var(--warning);"></div>
            <div class="flex justify-between">
                <div class="flex" style="gap: 10px;">
                    <button type="button" class="btn btn-info" onclick="keepExistingAssignment()">
                        <i class="fas fa-check"></i> Keep Current
                    </button>
                    <button type="button" class="btn btn-warning" onclick="proceedToReassign()">
                        <i class="fas fa-exchange-alt"></i> Reassign
                    </button>
                </div>
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

    <script src="../script.js"></script>
    <script>
        // State Management
        let pendingTicketId = null;
        let pendingTechId = null;
        let currentTechName = '';

        // Utility Functions
        function formatDate(date) {
            return date.toISOString().slice(0, 10);
        }

        // Menu Management
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.actions-menu-container')) {
                document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        function toggleActionsMenu(button) {
            const dropdown = button.nextElementSibling;
            const isShowing = dropdown.classList.contains('show');
            
            document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
            });
            
            if (!isShowing) {
                dropdown.classList.add('show');
            }
        }

        // Table Functions
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('ticketsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) {
                    if (cells[j] && cells[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        function filterTickets(status) {
            const table = document.getElementById('ticketsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                if (status === 'all') {
                    rows[i].style.display = '';
                } else {
                    const ticketStatus = rows[i].getAttribute('data-status');
                    rows[i].style.display = (ticketStatus && ticketStatus.toLowerCase() === status.toLowerCase()) ? '' : 'none';
                }
            }
        }

        // Assignment Functions
        function checkExistingAssignment(ticketId) {
            showLoading();
            
            fetch(`../get-ticket-assignment.php?id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.error) {
                        showNotification(data.error, 'danger');
                        return;
                    }
                    
                    if (data.status === 'Resolved' || data.status === 'Closed') {
                        showNotification('Cannot assign - ticket is resolved or closed', 'warning');
                        return;
                    }
                    
                    if (data.has_tech) {
                        pendingTicketId = ticketId;
                        currentTechName = data.tech_name;
                        
                        document.getElementById('reassignDetails').innerHTML = `
                            <p style="margin-bottom: 15px; font-size: 1.1rem;">
                                <i class="fas fa-info-circle" style="color: var(--warning); margin-right: 8px;"></i>
                                This ticket is currently assigned to:
                            </p>
                            <div style="background: var(--bg-card); padding: 15px; border-radius: 8px;">
                                <p><strong><i class="fas fa-user-cog"></i> Current Technical:</strong> ${data.tech_name}</p>
                                <p><strong><i class="fas fa-clock"></i> Assigned Since:</strong> ${data.assigned_date}</p>
                                <p><strong><i class="fas fa-info-circle"></i> Current Status:</strong> ${data.status}</p>
                            </div>
                            <p style="color: var(--text-secondary); margin-top: 10px;">
                                Would you like to reassign to a different technical staff or keep the current assignment?
                            </p>
                        `;
                        
                        openModal('reassignModal');
                    } else {
                        document.getElementById('assignTicketId').value = ticketId;
                        openModal('assignModal');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error checking assignment: ' + error.message, 'danger');
                });
        }

        function keepExistingAssignment() {
            closeModal('reassignModal');
            pendingTicketId = null;
            pendingTechId = null;
            currentTechName = '';
            showNotification('Current assignment preserved', 'info');
        }

        function proceedToReassign() {
            const ticketId = pendingTicketId;
            closeModal('reassignModal');
            
            if (ticketId) {
                document.getElementById('assignTicketId').value = ticketId;
                openModal('assignModal');
                pendingTicketId = null;
                pendingTechId = null;
                currentTechName = '';
            } else {
                showNotification('No ticket selected for reassignment', 'warning');
            }
        }

        function assignTechnical(event) {
            event.preventDefault();
            
            const ticketId = document.getElementById('assignTicketId').value;
            const techId = document.getElementById('technicalId').value;
            
            if (!techId || !ticketId) {
                showNotification('Please select a technical staff', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../update-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    technical_id: techId,
                    action: 'assign'
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Ticket assigned successfully!', 'success');
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

        // Ticket View Function
function viewTicket(id) {
    document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
        menu.classList.remove('show');
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
            
            // Format contact number to ensure it displays properly
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
                { wch: 12 }, { wch: 12 }, { wch: 25 }, { wch: 15 }
            ];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
            XLSX.writeFile(wb, `tickets_export_${formatDate(new Date())}.xlsx`);
            
            showNotification('Excel file downloaded successfully!', 'success');
        }

        function exportTickets() {
            const table = document.getElementById('ticketsTable');
            const rows = table.querySelectorAll('tr');
            const csv = [];
            
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td, th').forEach((cell, index) => {
                    if (index < row.querySelectorAll('td, th').length - 1) {
                        let text = cell.innerText.replace(/#/g, '').replace(/"/g, '""');
                        rowData.push('"' + text + '"');
                    }
                });
                csv.push(rowData.join(','));
            });
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tickets_export_${formatDate(new Date())}.csv`;
            a.click();
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
            const modals = ['viewTicketModal', 'assignModal', 'reassignModal', 'statusModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>