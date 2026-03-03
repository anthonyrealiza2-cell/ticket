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

// Get products for dropdown
$products = $pdo->query("SELECT * FROM products ORDER BY product_name")->fetchAll();

// Get concerns for dropdown
$concerns = $pdo->query("SELECT * FROM concerns ORDER BY concern_name")->fetchAll();

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
    <link rel="stylesheet" href="../css/new-tickets.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Add Ticket button styling */
        .btn-add-ticket {
            background: var(--gradient-2);
            color: white;
            margin-right: 10px;
        }
        
        .btn-add-ticket:hover {
            box-shadow: 0 10px 25px rgba(0, 184, 148, 0.4);
        }
        
        /* Form styling for modal */
        .tech-ticket-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .tech-ticket-form .form-group {
            margin-bottom: 20px;
        }
        
        .tech-ticket-form .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .tech-ticket-form .form-label i {
            color: var(--accent-primary);
            margin-right: 8px;
            width: 18px;
        }
        
        .tech-ticket-form .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            font-family: "Inter", sans-serif;
        }
        
        .tech-ticket-form .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--shadow-glow);
            background: var(--bg-hover);
        }
        
        .tech-ticket-form .form-control::placeholder {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .tech-ticket-form select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c7293' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 45px;
        }
        
        .tech-ticket-form textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .assigned-info {
            background: var(--bg-secondary);
            border-left: 4px solid var(--success);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .assigned-info i {
            color: var(--success);
            font-size: 1.2rem;
        }
        
        .assigned-info span {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .assigned-info strong {
            color: var(--accent-primary);
        }
        
        @media (max-width: 768px) {
            .tech-ticket-form .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
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

        <!-- Header with Actions -->
        <div class="flex justify-between" style="margin-bottom: 20px;">
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-add-ticket" onclick="openAddTicketModal()">
                    <i class="fas fa-plus-circle"></i> Add Ticket
                </button>
            </div>
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
                                    <button class='btn btn-primary btn-sm' onclick='viewTicket(<?= $ticket["ticket_id"] ?>)' title='View Details'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <button class='btn btn-info btn-sm' onclick='updateStatus(<?= $ticket["ticket_id"] ?>)' title='Update Status'>
                                        <i class='fas fa-edit'></i>
                                    </button>
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
                    <button class="btn btn-primary" onclick="openAddTicketModal()">
                        <i class="fas fa-plus-circle"></i> Create First Ticket
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Ticket Modal -->
    <div class="modal" id="addTicketModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle" style="color: var(--success);"></i> Add New Ticket for <?= htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']) ?></h2>
                <button class="modal-close" onclick="closeModal('addTicketModal')">&times;</button>
            </div>
            
            <div class="assigned-info">
                <i class="fas fa-user-check"></i>
                <span>This ticket will be automatically assigned to: <strong><?= htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']) ?></strong></span>
            </div>
            
            <form id="techTicketForm" class="tech-ticket-form" onsubmit="submitTechTicket(event)">
                <input type="hidden" id="techId" value="<?= $tech_id ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Company Name *
                        </label>
                        <input type="text" class="form-control" id="companyName" placeholder="Enter company name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Contact Person *
                        </label>
                        <input type="text" class="form-control" id="contactPerson" placeholder="Full name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Contact Number *
                        </label>
                        <input type="tel" class="form-control" id="contactNumber" placeholder="+63 XXX XXX XXXX" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" placeholder="company@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-box"></i> Product *
                        </label>
                        <select class="form-control" id="product" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= htmlspecialchars($product['product_name'] . ' v' . $product['version']) ?>">
                                    <?= htmlspecialchars($product['product_name'] . ' v' . $product['version']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-exclamation-triangle"></i> Concern Type *
                        </label>
                        <select class="form-control" id="concern" required>
                            <option value="">Select Concern</option>
                            <?php foreach ($concerns as $concern): ?>
                                <option value="<?= htmlspecialchars($concern['concern_name']) ?>">
                                    <?= htmlspecialchars($concern['concern_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Detailed Description *
                    </label>
                    <textarea class="form-control" id="description" placeholder="Please describe the issue in detail..." required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Priority Level *
                        </label>
                        <select class="form-control" id="priority" required>
                            <option value="Low">🐢 Low - Minor issue</option>
                            <option value="Medium" selected>⚡ Medium - Normal priority</option>
                            <option value="High">🔥 High - Critical issue</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i> Date Requested *
                        </label>
                        <input type="datetime-local" class="form-control" id="dateRequested" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>

                <div class="flex justify-between" style="margin-top: 30px;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('addTicketModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Create Ticket
                    </button>
                </div>
            </form>
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

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="modal" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); display: none;">
        <div class="spinner"></div>
    </div>

    <script>
    // State Management
    let pendingTicketId = null;

    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Add Ticket Modal
    function openAddTicketModal() {
        // Reset form
        document.getElementById('techTicketForm').reset();
        // Set default date
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('dateRequested').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        openModal('addTicketModal');
    }

    // Submit Tech Ticket
    function submitTechTicket(event) {
        event.preventDefault();
        
        const formData = {
            company_name: document.getElementById('companyName').value,
            contact_person: document.getElementById('contactPerson').value,
            contact_number: document.getElementById('contactNumber').value,
            email: document.getElementById('email').value,
            product: document.getElementById('product').value,
            concern: document.getElementById('concern').value,
            description: document.getElementById('description').value,
            priority: document.getElementById('priority').value,
            date_requested: document.getElementById('dateRequested').value,
            technical_id: document.getElementById('techId').value
        };
        
        showLoading();
        
        fetch('../create-tech-ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if(data.success) {
                showNotification('Ticket created and assigned successfully! 🎉', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('An error occurred while creating the ticket', 'danger');
        });
    }

    // Ticket View Function
    function viewTicket(id) {
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

    // Loading Functions
    function showLoading() {
        document.getElementById('loadingSpinner').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
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
        const modals = ['addTicketModal', 'viewTicketModal', 'statusModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Real-time validation for form inputs
    document.querySelectorAll('.tech-ticket-form .form-control').forEach(input => {
        input.addEventListener('input', function() {
            if(this.value.trim()) {
                this.style.borderColor = '#00b894';
            } else {
                this.style.borderColor = '#ff7675';
            }
        });
    });
    </script>
</body>
</html>