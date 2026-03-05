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

        /* Edit Ticket Modal Styles */
        .edit-ticket-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .edit-ticket-form .form-group {
            margin-bottom: 20px;
        }
        
        .edit-ticket-form .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .edit-ticket-form .form-label i {
            color: var(--accent-primary);
            margin-right: 8px;
            width: 18px;
        }
        
        .edit-ticket-form .form-control {
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
        
        .edit-ticket-form .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--shadow-glow);
            background: var(--bg-hover);
        }
        
        .edit-ticket-form .form-control::placeholder {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .edit-ticket-form select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c7293' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 45px;
        }
        
        .edit-ticket-form textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .current-info {
            background: var(--bg-secondary);
            border-left: 4px solid var(--info);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .current-info i {
            color: var(--info);
            font-size: 1.2rem;
        }
        
        .current-info span {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .current-info strong {
            color: var(--accent-primary);
        }
        
        @media (max-width: 768px) {
            .edit-ticket-form .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        /* Active filter tab */
        .filter-tab.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        /* Import Modal Styles */
        .import-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .import-option {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .import-option:hover {
            border-color: var(--accent-primary);
            background: var(--bg-hover);
        }
        
        .import-option i {
            font-size: 2.5rem;
            color: var(--accent-primary);
            margin-bottom: 10px;
        }
        
        .import-option p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .file-info {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-info i {
            color: var(--success);
            font-size: 1.5rem;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            margin: 15px 0;
            overflow: hidden;
            display: none;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--gradient-2);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .import-log {
            max-height: 300px;
            overflow-y: auto;
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .log-entry.success {
            color: var(--success);
        }
        
        .log-entry.error {
            color: var(--danger);
        }
        
        .log-entry.warning {
            color: var(--warning);
        }
        
        .log-entry i {
            width: 20px;
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
                <!-- IMPORT BUTTON - NEW -->
                <button class="btn btn-info" onclick="openImportModal()" style="margin-right: 10px; background: var(--info);">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-success" onclick="exportToExcel()" style="background: var(--gradient-2); margin-right: 10px;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn btn-success" onclick="exportTickets()">
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>

        <!-- Filter Tabs - Added Unassigned -->
        <div class="flex" style="margin-bottom: 20px; gap: 10px; flex-wrap: wrap;">
            <?php
            $filters = ['all', 'pending', 'assigned', 'in progress', 'resolved', 'unassigned'];
            $filterLabels = ['All', 'Pending', 'Assigned', 'In Progress', 'Resolved', 'Unassigned'];
            foreach ($filters as $index => $filter) {
                $activeClass = (isset($_GET['filter']) && $_GET['filter'] === $filter) || (!isset($_GET['filter']) && $filter === 'all') ? 'active' : '';
                echo "<button class='filter-tab $activeClass' onclick='filterTickets(\"$filter\")'>{$filterLabels[$index]}</button>";
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
                                   c.company_name, c.contact_person, c.client_id,
                                   CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
                                   t.technical_id
                            FROM tickets t 
                            LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id 
                            LEFT JOIN clients c ON t.company_id = c.client_id
                            ORDER BY t.created_at DESC
                        ");
                        
                        while ($ticket = $stmt->fetch()):
                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                            $isResolved = in_array($ticket['status'], ['Resolved', 'Closed']);
                            $isUnassigned = is_null($ticket['technical_id']);
                        ?>
                            <tr data-status="<?= $ticket['status'] ?>" 
                                data-ticket-id="<?= $ticket['ticket_id'] ?>"
                                data-company-id="<?= $ticket['client_id'] ?>"
                                data-is-unassigned="<?= $isUnassigned ? 'true' : 'false' ?>">
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
                                            <button class='actions-dropdown-item edit' onclick='editTicket(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-edit'></i> Edit Ticket
                                            </button>
                                            <button class='actions-dropdown-item edit' onclick='updateStatus(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-sync-alt'></i> Update Status
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
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewTicketModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Ticket Modal -->
    <div class="modal" id="editTicketModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fas fa-edit" style="color: var(--warning);"></i> Edit Ticket</h2>
                <button class="modal-close" onclick="closeModal('editTicketModal')">&times;</button>
            </div>
            
            <div class="current-info" id="editTicketInfo">
                <i class="fas fa-info-circle"></i>
                <span>Editing Ticket #<span id="editTicketIdDisplay"></span></span>
            </div>
            
            <form id="editTicketForm" class="edit-ticket-form" onsubmit="submitEditTicket(event)">
                <input type="hidden" id="editTicketId">
                <input type="hidden" id="editOriginalCompanyId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Company Name
                        </label>
                        <input type="text" class="form-control" id="editCompanyName" required>
                        <small style="color: var(--text-muted);">Enter company name (will be created if not exists)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Contact Person
                        </label>
                        <input type="text" class="form-control" id="editContactPerson" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Contact Number
                        </label>
                        <input type="tel" class="form-control" id="editContactNumber" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="editEmail" placeholder="company@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-box"></i> Product
                        </label>
                        <select class="form-control" id="editProduct" required>
                            <option value="">Select Product</option>
                            <?php
                            $products = $pdo->query("SELECT * FROM products ORDER BY product_name");
                            while ($product = $products->fetch()):
                            ?>
                                <option value="<?= htmlspecialchars($product['product_name'] . ' v' . $product['version']) ?>">
                                    <?= htmlspecialchars($product['product_name'] . ' v' . $product['version']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-exclamation-triangle"></i> Concern Type
                        </label>
                        <select class="form-control" id="editConcern" required>
                            <option value="">Select Concern</option>
                            <?php
                            $concerns = $pdo->query("SELECT * FROM concerns ORDER BY concern_name");
                            while ($concern = $concerns->fetch()):
                            ?>
                                <option value="<?= htmlspecialchars($concern['concern_name']) ?>">
                                    <?= htmlspecialchars($concern['concern_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Detailed Description
                    </label>
                    <textarea class="form-control" id="editDescription" rows="4" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Priority Level
                        </label>
                        <select class="form-control" id="editPriority" required>
                            <option value="Low">🐢 Low - Minor issue</option>
                            <option value="Medium">⚡ Medium - Normal priority</option>
                            <option value="High">🔥 High - Critical issue</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i> Date Requested
                        </label>
                        <input type="datetime-local" class="form-control" id="editDateRequested" required>
                    </div>
                </div>

                <div class="flex justify-between" style="margin-top: 30px;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editTicketModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
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

    <!-- IMPORT MODAL - NEW -->
    <div class="modal" id="importModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-import" style="color: var(--info);"></i> Import Tickets from Excel</h2>
                <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
            </div>
            
            <div class="import-options">
                <div class="import-option" onclick="downloadTemplate()">
                    <i class="fas fa-download"></i>
                    <h3>Download Template</h3>
                    <p>Get Excel template with correct format</p>
                </div>
                <div class="import-option" onclick="document.getElementById('excelFileInput').click()">
                    <i class="fas fa-upload"></i>
                    <h3>Upload File</h3>
                    <p>Select Excel file to import</p>
                </div>
            </div>
            
            <input type="file" id="excelFileInput" accept=".xlsx,.xls,.csv" style="display: none;" onchange="handleFileSelect(this)">
            
            <div id="fileInfo" class="file-info" style="display: none;">
                <i class="fas fa-file-excel"></i>
                <div>
                    <strong id="fileName"></strong><br>
                    <small id="fileSize"></small>
                </div>
                <button class="btn btn-sm btn-danger" onclick="clearSelectedFile()" style="margin-left: auto;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="progress-bar-container" id="progressBarContainer">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            
            <div id="importOptions" style="display: none;">
    <div class="form-group">
        <label class="form-label">
            <i class="fas fa-exclamation-triangle"></i> Duplicate Handling
        </label>
        <select class="form-control" id="duplicateHandling">
            <option value="skip">Skip duplicates (recommended)</option>
            <option value="update">Update existing tickets</option>
            <option value="create">Create new even if duplicate</option>
        </select>
    </div>
    
    <!-- NEW: Skip empty rows option -->
    <div class="form-group" style="margin-top: 10px;">
        <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="skipEmptyRows" checked style="width: 16px; height: 16px;"> 
            <span><i class="fas fa-trash-alt"></i> Skip completely empty rows</span>
        </label>
        <small style="color: var(--text-muted); display: block; margin-left: 24px;">
            When checked, rows with all empty fields will be ignored. Uncheck to import even empty rows (will use defaults).
        </small>
    </div>
    
    <div class="flex justify-between" style="margin-top: 20px;">
        <button class="btn btn-danger" onclick="closeModal('importModal')">
            <i class="fas fa-times"></i> Cancel
        </button>
        <button class="btn btn-success" onclick="processImport()" id="importButton">
            <i class="fas fa-cloud-upload-alt"></i> Start Import
        </button>
    </div>
</div>
            
            <div id="importLog" class="import-log" style="display: none;"></div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="modal" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); display: none;">
        <div class="spinner"></div>
    </div>

    <script src="../script.js"></script>
    <script>
        // State Management
        let pendingTicketId = null;
        let pendingTechId = null;
        let currentTechName = '';
        
        // Import State
        let selectedFile = null;
        let importData = null;
        let importLog = [];

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
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            for (let i = 1; i < rows.length; i++) {
                if (status === 'all') {
                    rows[i].style.display = '';
                } else if (status === 'unassigned') {
                    const isUnassigned = rows[i].getAttribute('data-is-unassigned') === 'true';
                    rows[i].style.display = isUnassigned ? '' : 'none';
                } else {
                    const ticketStatus = rows[i].getAttribute('data-status');
                    rows[i].style.display = (ticketStatus && ticketStatus.toLowerCase() === status.toLowerCase()) ? '' : 'none';
                }
            }
        }

        // Edit Ticket Functions
        function editTicket(id) {
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
                    
                    // Get the row to access company_id
                    const row = document.querySelector(`tr[data-ticket-id="${id}"]`);
                    const companyId = row.getAttribute('data-company-id');
                    
                    // Populate form fields
                    document.getElementById('editTicketId').value = data.ticket_id;
                    document.getElementById('editTicketIdDisplay').textContent = data.ticket_id;
                    document.getElementById('editOriginalCompanyId').value = companyId;
                    document.getElementById('editCompanyName').value = data.company_name || '';
                    document.getElementById('editContactPerson').value = data.contact_person || '';
                    document.getElementById('editContactNumber').value = data.contact_number || '';
                    document.getElementById('editEmail').value = data.email || '';
                    
                    // Set product
                    const productSelect = document.getElementById('editProduct');
                    const productValue = data.product_name ? `${data.product_name} v${data.version}` : '';
                    if (productValue) {
                        for (let option of productSelect.options) {
                            if (option.value === productValue) {
                                option.selected = true;
                                break;
                            }
                        }
                    }
                    
                    // Set concern
                    const concernSelect = document.getElementById('editConcern');
                    if (data.concern_type) {
                        for (let option of concernSelect.options) {
                            if (option.value === data.concern_type) {
                                option.selected = true;
                                break;
                            }
                        }
                    }
                    
                    document.getElementById('editDescription').value = data.concern_description || '';
                    document.getElementById('editPriority').value = data.priority || 'Medium';
                    
                    // Format date for datetime-local
                    if (data.date_requested) {
                        const date = new Date(data.date_requested);
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        document.getElementById('editDateRequested').value = `${year}-${month}-${day}T${hours}:${minutes}`;
                    }
                    
                    openModal('editTicketModal');
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error fetching ticket details: ' + error.message, 'danger');
                });
        }

        function submitEditTicket(event) {
            event.preventDefault();
            
            const formData = {
                ticket_id: document.getElementById('editTicketId').value,
                original_company_id: document.getElementById('editOriginalCompanyId').value,
                company_name: document.getElementById('editCompanyName').value,
                contact_person: document.getElementById('editContactPerson').value,
                contact_number: document.getElementById('editContactNumber').value,
                email: document.getElementById('editEmail').value,
                product: document.getElementById('editProduct').value,
                concern: document.getElementById('editConcern').value,
                description: document.getElementById('editDescription').value,
                priority: document.getElementById('editPriority').value,
                date_requested: document.getElementById('editDateRequested').value
            };
            
            showLoading();
            
            fetch('../update-ticket-details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Ticket updated successfully!', 'success');
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
                showNotification('An error occurred while updating the ticket', 'danger');
            });
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

        // IMPORT FUNCTIONS - NEW
        function openImportModal() {
            clearImportState();
            openModal('importModal');
        }

        function downloadTemplate() {
    // Go up one level to access files in the root directory
    window.location.href = '../download-template.php?type=tickets';
    addLogEntry('Excel template download started - please wait...', 'info');
    
    // Small delay to show the message
    setTimeout(() => {
        addLogEntry('Template download should start automatically', 'success');
    }, 1000);
}

        // Updated handleFileSelect function
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    selectedFile = file;
    
    // Display file info
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('fileInfo').style.display = 'flex';
    
    // Clear previous logs
    importLog = [];
    document.getElementById('importLog').innerHTML = '';
    document.getElementById('importLog').style.display = 'none';
    
    // Show import options
    document.getElementById('importOptions').style.display = 'block';
    
    addLogEntry(`Loading file: ${file.name} (${formatFileSize(file.size)})`, 'info');
    
    // Read and parse file
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            // Get the data
            const data = e.target.result;
            
            // Parse based on file type
            let workbook;
            if (file.name.toLowerCase().endsWith('.csv')) {
                // Parse CSV
                const lines = data.split('\n');
                const csvData = lines.map(line => line.split(','));
                workbook = XLSX.utils.book_new();
                const worksheet = XLSX.utils.aoa_to_sheet(csvData);
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');
            } else {
                // Parse Excel file
                const arrayBuffer = e.target.result;
                workbook = XLSX.read(arrayBuffer, { type: 'array' });
            }
            
            // Get first sheet
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // Convert to JSON with header option
            importData = XLSX.utils.sheet_to_json(worksheet, { 
                header: 1,
                defval: '', // Default value for empty cells
                blankrows: true // Include blank rows
            });
            
            console.log('Raw import data:', importData); // Debug log
            
            // Validate and process data
            if (!importData || importData.length === 0) {
                addLogEntry('File contains no data', 'error');
                return;
            }
            
            // Check if file has headers
            if (importData.length < 1) {
                addLogEntry('File is empty', 'error');
                return;
            }
            
            // Get headers (first row)
            const headers = importData[0] || [];
            addLogEntry(`Found ${importData.length} rows in file`, 'info');
            addLogEntry(`Headers: ${headers.join(' | ')}`, 'info');
            
            // Count rows with data (excluding header)
            let dataRows = 0;
            for (let i = 1; i < importData.length; i++) {
                const row = importData[i];
                if (row && row.some(cell => cell && cell.toString().trim() !== '')) {
                    dataRows++;
                }
            }
            
            addLogEntry(`${dataRows} rows contain data (excluding header)`, 'success');
            
            // Show preview of first data row
            if (importData.length > 1) {
                const firstDataRow = importData[1];
                addLogEntry('Sample first data row:', 'info');
                for (let j = 0; j < Math.min(headers.length, firstDataRow.length); j++) {
                    if (firstDataRow[j] && firstDataRow[j].toString().trim() !== '') {
                        addLogEntry(`  ${headers[j]}: ${firstDataRow[j]}`, 'info');
                    }
                }
            }
            
        } catch (error) {
            console.error('Error parsing file:', error);
            addLogEntry('Error parsing file: ' + error.message, 'error');
            addLogEntry('Make sure the file is a valid Excel or CSV file', 'warning');
        }
    };
    
    reader.onerror = function(error) {
        console.error('FileReader error:', error);
        addLogEntry('Error reading file: ' + error, 'error');
    };
    
    // Read as array buffer for Excel files, as text for CSV
    if (file.name.toLowerCase().endsWith('.csv')) {
        reader.readAsText(file);
    } else {
        reader.readAsArrayBuffer(file);
    }
}
        function clearSelectedFile() {
            document.getElementById('excelFileInput').value = '';
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('importOptions').style.display = 'none';
            document.getElementById('importLog').style.display = 'none';
            document.getElementById('progressBarContainer').style.display = 'none';
            document.getElementById('importLog').innerHTML = '';
            selectedFile = null;
            importData = null;
            importLog = [];
        }

        function clearImportState() {
            clearSelectedFile();
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function addLogEntry(message, type) {
            importLog.push({ message, type, time: new Date() });
            updateLogDisplay();
        }

        function updateLogDisplay() {
            const logDiv = document.getElementById('importLog');
            logDiv.style.display = 'block';
            
            let html = '';
            importLog.forEach(entry => {
                const icon = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle',
                    info: 'fa-info-circle'
                }[entry.type] || 'fa-info-circle';
                
                html += `<div class="log-entry ${entry.type}">
                    <i class="fas ${icon}"></i>
                    <span>[${entry.time.toLocaleTimeString()}] ${entry.message}</span>
                </div>`;
            });
            
            logDiv.innerHTML = html;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

       
// Updated processImport function
function processImport() {
    if (!importData || importData.length < 2) {
        addLogEntry('No data to import - please select a valid file with data', 'error');
        return;
    }
    
    const duplicateHandling = document.getElementById('duplicateHandling').value;
    const skipEmptyRows = document.getElementById('skipEmptyRows').checked;
    
    // Show progress bar
    document.getElementById('progressBarContainer').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('importButton').disabled = true;
    
    addLogEntry('', 'info');
    addLogEntry('='.repeat(50), 'info');
    addLogEntry(`STARTING IMPORT PROCESS`, 'info');
    addLogEntry('='.repeat(50), 'info');
    
    // Get headers (first row)
    const headers = importData[0] || [];
    addLogEntry(`Headers: ${headers.join(' | ')}`, 'info');
    
    // Prepare data for import
    const records = [];
    let emptyRowCount = 0;
    
    for (let i = 1; i < importData.length; i++) {
        const row = importData[i];
        
        if (!row || row.length === 0) {
            emptyRowCount++;
            continue;
        }
        
        // Map columns based on header position
        const record = {
    ticket_id: row[0] ? row[0].toString().trim() : '',
    company: row[1] ? row[1].toString().trim() : '',
    contact: row[2] ? row[2].toString().trim() : '',
    contact_number: row[3] ? row[3].toString().trim() : '', // NEW: Contact Number
    concern: row[4] ? row[4].toString().trim() : '',        // Concern moved to column 4
    priority: row[5] ? row[5].toString().trim() : '',       // Priority at column 5
    status: row[6] ? row[6].toString().trim() : '',         // Status at column 6
    assigned_to: row[7] ? row[7].toString().trim() : '',    // Assigned To at column 7
    date: row[8] ? row[8].toString().trim() : ''            // Date at column 8
};
        
        // Check if row has data
        const hasData = record.company || record.contact || record.concern;
        
        if (hasData) {
            records.push(record);
            addLogEntry(`Row ${i + 1}: Found data - ${record.company || 'Empty'}`, 'info');
        } else {
            emptyRowCount++;
        }
    }
    
    addLogEntry(`Total rows with data: ${records.length}`, 'info');
    addLogEntry(`Empty rows skipped: ${emptyRowCount}`, 'info');
    
    if (records.length === 0) {
        addLogEntry('❌ No data rows to import', 'error');
        document.getElementById('importButton').disabled = false;
        document.getElementById('progressBarContainer').style.display = 'none';
        return;
    }
    
    addLogEntry('📤 Sending data to server...', 'info');
    
    // IMPORTANT: Use the correct path
    // If tickets.php is in /pages/ folder, use '../import-tickets.php'
    // If tickets.php is in root folder, use 'import-tickets.php'
    
    const importUrl = '../import-tickets.php'; // Change this based on your structure
    
    fetch(importUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            records: records,
            duplicate_handling: duplicateHandling,
            skip_empty_rows: skipEmptyRows
        })
    })
    .then(async response => {
        const text = await response.text();
        console.log('Raw response:', text);
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Server returned invalid JSON. Check PHP errors.');
        }
    })
    .then(data => {
        document.getElementById('progressBar').style.width = '100%';
        
        if (data.success) {
            addLogEntry('✅ Import completed successfully!', 'success');
            addLogEntry(`📊 Imported: ${data.imported}`, 'success');
            addLogEntry(`⚠️ Skipped: ${data.skipped}`, 'warning');
            addLogEntry(`❌ Errors: ${data.errors}`, data.errors > 0 ? 'error' : 'info');
            
            if (data.details) {
                data.details.forEach(detail => {
                    addLogEntry(detail.message, detail.type);
                });
            }
            
            showNotification(`Import completed: ${data.imported} tickets added`, 'success');
            
            setTimeout(() => location.reload(), 3000);
        } else {
            addLogEntry('❌ Import failed: ' + data.message, 'error');
            showNotification('Import failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        addLogEntry('❌ Error: ' + error.message, 'error');
        addLogEntry('Check browser console for details', 'warning');
        showNotification('Error during import', 'danger');
    })
    .finally(() => {
        document.getElementById('importButton').disabled = false;
        document.getElementById('progressBarContainer').style.display = 'none';
    });
    
    // Simulate progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        document.getElementById('progressBar').style.width = progress + '%';
        if (progress >= 90) clearInterval(interval);
    }, 200);
}

// Add this to your import modal HTML in tickets.php
// Add this option in the import options div:
/*
<div class="form-group" style="margin-top: 10px;">
    <label class="form-label">
        <input type="checkbox" id="skipEmptyRows" checked> 
        Skip completely empty rows
    </label>
    <small style="color: var(--text-muted); display: block;">
        When checked, rows with all empty fields will be ignored
    </small>
</div>
*/

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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
            const modals = ['viewTicketModal', 'editTicketModal', 'assignModal', 'reassignModal', 'statusModal', 'deleteModal', 'importModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Set active filter based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const filterParam = urlParams.get('filter');
            if (filterParam) {
                filterTickets(filterParam);
            }
        });
       
    </script>
</body>
</html>