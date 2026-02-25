<?php
require_once '../database.php';

$whereClause = "";
$params = [];

// Check if filtering by client
if(isset($_GET['client_id'])) {
    $whereClause = " WHERE t.company_id = ?";
    $params[] = $_GET['client_id'];
}

// Check if filtering by technical
if(isset($_GET['tech_id'])) {
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
    <link rel="stylesheet" href="../style.css">
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
                <button class="btn btn-success" onclick="exportTickets()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Filter Tabs (UPDATED with Unresolved) -->
<div class="flex" style="margin-bottom: 20px; gap: 10px; flex-wrap: wrap;">
    <button class="btn btn-sm" onclick="filterTickets('all')" style="background: var(--accent-primary);">All</button>
    <button class="btn btn-sm" onclick="filterTickets('Pending')" style="background: var(--danger);">Pending</button>
    <button class="btn btn-sm" onclick="filterTickets('Assigned')" style="background: var(--info);">Assigned</button>
    <button class="btn btn-sm" onclick="filterTickets('In Progress')" style="background: var(--warning);">In Progress</button>
    <button class="btn btn-sm" onclick="filterTickets('Resolved')" style="background: var(--success);">Resolved</button>
    <button class="btn btn-sm" onclick="filterTickets('Unresolved')" style="background: #ff9f43;">Unresolved</button>
    <button class="btn btn-sm" onclick="filterTickets('Closed')" style="background: var(--text-muted);">Closed</button>
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
                        $stmt = $pdo->query("SELECT t.*, 
                                             CONCAT(ts.firstname, ' ', ts.lastname) as tech_name 
                                             FROM tickets t 
                                             LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id 
                                             ORDER BY t.created_at DESC");
                        while($ticket = $stmt->fetch()) {
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));                            echo "<tr data-status='{$ticket['status']}'>";
                            echo "<td>#{$ticket['ticket_id']}</td>";
                            echo "<td>{$ticket['company_name']}</td>";
                            echo "<td>{$ticket['contact_person']}</td>";
                            echo "<td>" . substr($ticket['concern'] ?? '', 0, 50) . "...</td>";
                            echo "<td><span class='badge badge-{$priorityClass}'>{$ticket['priority']}</span></td>";
                            echo "<td><span class='badge badge-{$statusClass}'>{$ticket['status']}</span></td>";
                            echo "<td>" . ($ticket['tech_name'] ?? 'Unassigned') . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($ticket['date_requested'])) . "</td>";
                            echo "<td>
                                    <button class='btn btn-primary btn-sm' onclick='viewTicket({$ticket['ticket_id']})'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <button class='btn btn-success btn-sm' onclick='assignTech({$ticket['ticket_id']})'>
                                        <i class='fas fa-user-plus'></i>
                                    </button>
                                    <button class='btn btn-info btn-sm' onclick='updateStatus({$ticket['ticket_id']})'>
                                        <i class='fas fa-edit'></i>
                                    </button>
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
                        $techs = $pdo->query("SELECT * FROM technical_staff");
                        while($tech = $techs->fetch()) {
                            echo "<option value='{$tech['technical_id']}'>
                                    {$tech['firstname']} {$tech['lastname']} - {$tech['position']}
                                  </option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('assignModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal (UPDATED with Unresolved) -->
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
                    <option value="Unresolved">Unresolved</option>
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

    <script src="../script.js"></script>
    <script>
    // Search function
    function searchTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('ticketsTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName('td');
            let found = false;
            for (let j = 0; j < td.length - 1; j++) {
                if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            tr[i].style.display = found ? '' : 'none';
        }
    }

    // Filter by status
    function filterTickets(status) {
        const table = document.getElementById('ticketsTable');
        const tr = table.getElementsByTagName('tr');
        
        for (let i = 1; i < tr.length; i++) {
            if (status === 'all') {
                tr[i].style.display = '';
            } else {
                const ticketStatus = tr[i].getAttribute('data-status');
                tr[i].style.display = ticketStatus === status ? '' : 'none';
            }
        }
    }

    // View ticket details (UPDATED WITH ALL FIELDS)
function viewTicket(id) {
    fetch(`../get-tickets.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            const details = document.getElementById('ticketDetails');
            
            // Parse concern to extract product and concern type
            let product = 'N/A';
            let concernType = 'N/A';
            let description = data.concern || '';
            
            if (data.concern) {
                const lines = data.concern.split('\n');
                lines.forEach(line => {
                    if (line.startsWith('Product:')) {
                        product = line.replace('Product:', '').trim();
                    } else if (line.startsWith('Concern Type:')) {
                        concernType = line.replace('Concern Type:', '').trim();
                    } else if (line.startsWith('Description:')) {
                        description = line.replace('Description:', '').trim();
                    }
                });
            }
            
            const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
            const priorityClass = (data.priority || 'medium').toLowerCase();
            
            details.innerHTML = `
                <div style="display: grid; gap: 15px;">
                    <p><strong><i class="fas fa-hashtag"></i> Ticket ID:</strong> #${data.ticket_id}</p>
                    <p><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name}</p>
                    <p><strong><i class="fas fa-user"></i> Contact:</strong> ${data.contact_person}</p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${data.email}</p>
                    <p><strong><i class="fas fa-phone"></i> Phone:</strong> ${data.contact_number}</p>
                    <p><strong><i class="fas fa-box"></i> Product:</strong> ${product}</p>
                    <p><strong><i class="fas fa-exclamation-triangle"></i> Concern Type:</strong> ${concernType}</p>
                    <p><strong><i class="fas fa-align-left"></i> Description:</strong></p>
                    <div style="background: var(--bg-secondary); padding: 15px; border-radius: 10px; white-space: pre-line;">${description}</div>
                    <p><strong><i class="fas fa-flag"></i> Priority:</strong> 
                        <span class='badge badge-${priorityClass}'>${data.priority}</span>
                    </p>
                    <p><strong><i class="fas fa-info-circle"></i> Status:</strong> 
                        <span class='badge badge-${statusClass}'>${data.status}</span>
                    </p>
                    <p><strong><i class="fas fa-calendar"></i> Date Submitted:</strong> 
                        ${new Date(data.date_requested).toLocaleString()}
                    </p>
                    <p><strong><i class="fas fa-clock"></i> Date Finished:</strong> 
                        ${data.finish_date ? new Date(data.finish_date).toLocaleString() : 'Not finished yet'}
                    </p>
                    ${data.tech_name ? `<p><strong><i class="fas fa-user-cog"></i> Assigned To:</strong> ${data.tech_name}</p>` : ''}
                    ${data.solution ? `<p><strong><i class="fas fa-check-circle"></i> Solution:</strong> ${data.solution}</p>` : ''}
                </div>
            `;
            openModal('viewTicketModal');
        });
}

    // Assign technical staff
    function assignTech(id) {
        document.getElementById('assignTicketId').value = id;
        openModal('assignModal');
    }

    function assignTechnical(event) {
        event.preventDefault();
        const ticketId = document.getElementById('assignTicketId').value;
        const techId = document.getElementById('technicalId').value;
        
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
            if(data.success) {
                showNotification('Ticket assigned successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        });
    }

    // Update status
    function updateStatus(id) {
        document.getElementById('statusTicketId').value = id;
        openModal('statusModal');
    }

    function updateStatusSubmit(event) {
        event.preventDefault();
        const ticketId = document.getElementById('statusTicketId').value;
        const status = document.getElementById('ticketStatus').value;
        const solution = document.getElementById('solution').value;
        
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
            if(data.success) {
                showNotification('Status updated successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        });
    }

    // Export tickets
    function exportTickets() {
        const table = document.getElementById('ticketsTable');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        for (const row of rows) {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            for (let i = 0; i < cols.length - 1; i++) { // Exclude actions column
                rowData.push('"' + cols[i].innerText.replace(/"/g, '""') + '"');
            }
            csv.push(rowData.join(','));
        }
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'tickets_export.csv';
        a.click();
    }

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    </script>
</body>
</html>