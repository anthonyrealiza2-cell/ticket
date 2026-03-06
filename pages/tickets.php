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
        <div class="page-header">
            <h1>Ticket Management</h1>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search tickets...">
                </div>
                
                <!-- Bulk Actions Dropdown -->
                <!-- Bulk Actions Dropdown -->
<div class="bulk-actions-container" id="bulkActions" style="display: none;">
    <button class="btn btn-primary" onclick="toggleBulkMenu()">
        <i class="fas fa-check-double"></i> Action Menu <i class="fas fa-caret-down"></i>
    </button>
    <div class="bulk-dropdown" id="bulkDropdown">
        <div class="bulk-dropdown-header">
            <span><i class="fas fa-layer-group"></i> Selected: <span id="selectedCount">0</span> tickets</span>
            <button class="btn-sm btn-info" onclick="selectAll()">Select All</button>
            <button class="btn-sm btn-secondary" onclick="clearSelection()">Clear</button>
        </div>
        <div class="bulk-dropdown-item" onclick="openBulkAssignModal()">
            <i class="fas fa-user-plus" style="color: var(--success);"></i>
            <span>Assign to Technician</span>
        </div>
        <div class="bulk-dropdown-item" onclick="openBulkStatusModal()">
            <i class="fas fa-sync-alt" style="color: var(--warning);"></i>
            <span>Update Status</span>
        </div>
        <div class="bulk-dropdown-item" onclick="openBulkPriorityModal()">
            <i class="fas fa-flag" style="color: var(--info);"></i>
            <span>Update Priority</span>
        </div>
        <div class="bulk-dropdown-divider"></div>
        <div class="bulk-dropdown-item delete" onclick="openBulkArchiveModal()">
            <i class="fas fa-archive" style="color: var(--warning);"></i>
            <span>Archive Selected</span>
        </div>
    </div>
</div>

                <a href="view-archive.php" class="btn btn-info">
                    <i class="fas fa-archive"></i> View Archive
                </a>
                <button class="btn btn-info" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn btn-success" onclick="exportTickets()">
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <?php
            $filters = ['all', 'pending', 'assigned', 'in progress', 'resolved', 'closed', 'unassigned'];
            $filterLabels = ['All', 'Pending', 'Assigned', 'In Progress', 'Resolved', 'Closed', 'Unassigned'];
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
                            <th width="40">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                            </th>
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
                                <td>
                                    <input type="checkbox" class="ticket-checkbox" value="<?= $ticket['ticket_id'] ?>" onchange="updateBulkActions()">
                                </td>
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
                                            <button class='actions-dropdown-item delete' onclick='confirmArchive(<?= $ticket["ticket_id"] ?>)'>
                                                <i class='fas fa-archive'></i> Archive Ticket
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

    <!-- Assign Technical Modal (Single) -->
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

    <!-- Bulk Assign Modal -->
    <div class="modal" id="bulkAssignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus" style="color: var(--success);"></i> Bulk Assign Tickets</h2>
                <button class="modal-close" onclick="closeModal('bulkAssignModal')">&times;</button>
            </div>
            <div class="bulk-info" id="bulkAssignInfo"></div>
            <form id="bulkAssignForm" onsubmit="bulkAssign(event)">
                <input type="hidden" id="bulkAssignIds">
                <div class="form-group">
                    <label class="form-label">Select Technical Staff</label>
                    <select class="form-control" id="bulkTechnicalId" required>
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
                    <button type="button" class="btn btn-danger" onclick="closeModal('bulkAssignModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Selected</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Status Modal -->
    <div class="modal" id="bulkStatusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-sync-alt" style="color: var(--warning);"></i> Bulk Update Status</h2>
                <button class="modal-close" onclick="closeModal('bulkStatusModal')">&times;</button>
            </div>
            <div class="bulk-info" id="bulkStatusInfo"></div>
            <form id="bulkStatusForm" onsubmit="bulkStatusUpdate(event)">
                <input type="hidden" id="bulkStatusIds">
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select class="form-control" id="bulkNewStatus" required>
                        <option value="">Select Status...</option>
                        <option value="Pending">Pending</option>
                        <option value="Assigned">Assigned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Solution / Remarks (Optional)</label>
                    <textarea class="form-control" id="bulkSolution" rows="3" placeholder="Enter solution or remarks for resolved/closed tickets..."></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('bulkStatusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Priority Modal -->
    <div class="modal" id="bulkPriorityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-flag" style="color: var(--info);"></i> Bulk Update Priority</h2>
                <button class="modal-close" onclick="closeModal('bulkPriorityModal')">&times;</button>
            </div>
            <div class="bulk-info" id="bulkPriorityInfo"></div>
            <form id="bulkPriorityForm" onsubmit="bulkPriorityUpdate(event)">
                <input type="hidden" id="bulkPriorityIds">
                <div class="form-group">
                    <label class="form-label">New Priority</label>
                    <select class="form-control" id="bulkNewPriority" required>
                        <option value="">Select Priority...</option>
                        <option value="Low">🐢 Low</option>
                        <option value="Medium">⚡ Medium</option>
                        <option value="High">🔥 High</option>
                    </select>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('bulkPriorityModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Priority</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Archive Confirmation Modal -->
    <!-- Bulk Archive Confirmation Modal -->
<div class="modal" id="bulkArchiveModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Archive Multiple Tickets</h2>
            <button class="modal-close" onclick="closeModal('bulkArchiveModal')">&times;</button>
        </div>
        <div class="delete-warning">
            <i class="fas fa-archive" style="color: var(--warning);"></i>
            <h3>Are you sure?</h3>
            <p id="bulkArchiveMessage">You are about to archive <strong id="bulkArchiveCount">0</strong> tickets. They can be restored from the archive.</p>
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Archive Reason (Optional)</label>
                <textarea class="form-control" id="bulkArchiveReason" rows="2" placeholder="Enter reason for archiving..."></textarea>
            </div>
            <input type="hidden" id="bulkArchiveIds">
            <div class="delete-actions">
                <button class="btn btn-cancel" onclick="closeModal('bulkArchiveModal')">Cancel</button>
                <button class="btn btn-warning" onclick="bulkArchive()">Archive All</button>
            </div>
        </div>
    </div>
</div>

    <!-- Single Archive Confirmation Modal -->
<div class="modal" id="archiveModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Archive Ticket</h2>
            <button class="modal-close" onclick="closeModal('archiveModal')">&times;</button>
        </div>
        <div class="delete-warning">
            <i class="fas fa-archive" style="color: var(--warning);"></i>
            <h3>Are you sure?</h3>
            <p>This ticket will be moved to archive. It can be restored later.</p>
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Archive Reason (Optional)</label>
                <textarea class="form-control" id="archiveReason" rows="2" placeholder="Enter reason for archiving..."></textarea>
            </div>
            <input type="hidden" id="archiveTicketId">
            <div class="delete-actions">
                <button class="btn btn-cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button class="btn btn-warning" onclick="archiveTicket()">Archive</button>
            </div>
        </div>
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

    <!-- Update Status Modal (Single) -->
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

    <!-- Import Modal -->
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
                <button class="btn-sm btn-danger" onclick="clearSelectedFile()" style="margin-left: auto;">
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

        // Bulk Operations State
        let selectedTickets = new Set();

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
            if (!event.target.closest('.bulk-actions-container')) {
                document.getElementById('bulkDropdown')?.classList.remove('show');
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

        function toggleBulkMenu() {
            document.getElementById('bulkDropdown').classList.toggle('show');
        }

        // Bulk Operations Functions
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');
            selectedTickets.clear();
            checkboxes.forEach(cb => selectedTickets.add(cb.value));
            
            const count = selectedTickets.size;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('bulkActions').style.display = count > 0 ? 'inline-block' : 'none';
            
            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.ticket-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
                selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
            }
        }

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        }

        function selectAll() {
            document.getElementById('selectAllCheckbox').checked = true;
            toggleSelectAll(document.getElementById('selectAllCheckbox'));
        }

        function clearSelection() {
            document.getElementById('selectAllCheckbox').checked = false;
            toggleSelectAll(document.getElementById('selectAllCheckbox'));
            document.getElementById('bulkDropdown').classList.remove('show');
        }

        function getSelectedIds() {
            return Array.from(selectedTickets);
        }

        // Bulk Assign
        function openBulkAssignModal() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                showNotification('No tickets selected', 'warning');
                return;
            }
            document.getElementById('bulkAssignIds').value = ids.join(',');
            document.getElementById('bulkAssignInfo').innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>Assigning <strong>${ids.length}</strong> ticket(s) to technician</span>
            `;
            openModal('bulkAssignModal');
        }

        function bulkAssign(event) {
            event.preventDefault();
            
            const ids = document.getElementById('bulkAssignIds').value.split(',');
            const techId = document.getElementById('bulkTechnicalId').value;
            
            if (!techId) {
                showNotification('Please select a technician', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../bulk-assign-tickets.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_ids: ids,
                    technical_id: techId
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(`Successfully assigned ${data.updated} tickets`, 'success');
                    closeModal('bulkAssignModal');
                    clearSelection();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error during bulk assign', 'danger');
            });
        }

        // Bulk Status Update
        function openBulkStatusModal() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                showNotification('No tickets selected', 'warning');
                return;
            }
            document.getElementById('bulkStatusIds').value = ids.join(',');
            document.getElementById('bulkStatusInfo').innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>Updating status for <strong>${ids.length}</strong> ticket(s)</span>
            `;
            openModal('bulkStatusModal');
        }

        function bulkStatusUpdate(event) {
            event.preventDefault();
            
            const ids = document.getElementById('bulkStatusIds').value.split(',');
            const status = document.getElementById('bulkNewStatus').value;
            const solution = document.getElementById('bulkSolution').value;
            
            if (!status) {
                showNotification('Please select a status', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../bulk-status-update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_ids: ids,
                    status: status,
                    solution: solution
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(`Successfully updated ${data.updated} tickets`, 'success');
                    closeModal('bulkStatusModal');
                    clearSelection();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error during bulk status update', 'danger');
            });
        }

        // Bulk Priority Update
        function openBulkPriorityModal() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                showNotification('No tickets selected', 'warning');
                return;
            }
            document.getElementById('bulkPriorityIds').value = ids.join(',');
            document.getElementById('bulkPriorityInfo').innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>Updating priority for <strong>${ids.length}</strong> ticket(s)</span>
            `;
            openModal('bulkPriorityModal');
        }

        function bulkPriorityUpdate(event) {
            event.preventDefault();
            
            const ids = document.getElementById('bulkPriorityIds').value.split(',');
            const priority = document.getElementById('bulkNewPriority').value;
            
            if (!priority) {
                showNotification('Please select a priority', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('../bulk-priority-update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_ids: ids,
                    priority: priority
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(`Successfully updated ${data.updated} tickets`, 'success');
                    closeModal('bulkPriorityModal');
                    clearSelection();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error during bulk priority update', 'danger');
            });
        }

        // Bulk Archive
       // Bulk Archive
function openBulkArchiveModal() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showNotification('No tickets selected', 'warning');
        return;
    }
    
    // Check if elements exist before setting properties
    const bulkArchiveIds = document.getElementById('bulkArchiveIds');
    const bulkArchiveCount = document.getElementById('bulkArchiveCount');
    const bulkArchiveMessage = document.getElementById('bulkArchiveMessage');
    
    if (bulkArchiveIds) bulkArchiveIds.value = ids.join(',');
    if (bulkArchiveCount) bulkArchiveCount.textContent = ids.length;
    if (bulkArchiveMessage) {
        bulkArchiveMessage.innerHTML = `You are about to archive <strong>${ids.length}</strong> tickets. They can be restored from the archive.`;
    }
    
    openModal('bulkArchiveModal');
}

function bulkArchive() {
    const bulkArchiveIds = document.getElementById('bulkArchiveIds');
    const bulkArchiveReason = document.getElementById('bulkArchiveReason');
    
    if (!bulkArchiveIds || !bulkArchiveIds.value) {
        showNotification('No tickets selected', 'warning');
        return;
    }
    
    const ids = bulkArchiveIds.value.split(',');
    const reason = bulkArchiveReason ? bulkArchiveReason.value : '';
    
    showLoading();
    
    fetch('../bulk-archive-tickets.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            ticket_ids: ids,
            reason: reason,
            user_id: 1 // Replace with actual user ID from session
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Server returned invalid JSON');
        }
    })
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(`Successfully archived ${data.archived} tickets`, 'success');
            closeModal('bulkArchiveModal');
            clearSelection();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Error during bulk archive: ' + error.message, 'danger');
    });
}

        // Single Ticket Archive
       // Single Ticket Archive
function confirmArchive(ticketId) {
    const archiveTicketId = document.getElementById('archiveTicketId');
    const archiveModal = document.getElementById('archiveModal');
    
    if (archiveTicketId) {
        archiveTicketId.value = ticketId;
    }
    
    if (archiveModal) {
        openModal('archiveModal');
    } else {
        showNotification('Archive modal not found', 'danger');
    }
}

function archiveTicket() {
    const archiveTicketId = document.getElementById('archiveTicketId');
    const archiveReason = document.getElementById('archiveReason');
    
    if (!archiveTicketId || !archiveTicketId.value) {
        showNotification('No ticket selected', 'warning');
        return;
    }
    
    const ticketId = archiveTicketId.value;
    const reason = archiveReason ? archiveReason.value : '';
    
    showLoading();
    
    fetch('../archive-ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            ticket_id: ticketId,
            reason: reason,
            user_id: 1 // Replace with actual user ID from session
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Server returned invalid JSON');
        }
    })
    .then(data => {
        hideLoading();
        closeModal('archiveModal');
        
        if (data.success) {
            showNotification('Ticket archived successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Error archiving ticket: ' + error.message, 'danger');
    });
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
                
                for (let j = 1; j < cells.length - 1; j++) {
                    if (cells[j] && cells[j].innerText.toUpperCase().indexOf(filter) > -1) {
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
                    
                    const row = document.querySelector(`tr[data-ticket-id="${id}"]`);
                    const companyId = row.getAttribute('data-company-id');
                    
                    document.getElementById('editTicketId').value = data.ticket_id;
                    document.getElementById('editTicketIdDisplay').textContent = data.ticket_id;
                    document.getElementById('editOriginalCompanyId').value = companyId;
                    document.getElementById('editCompanyName').value = data.company_name || '';
                    document.getElementById('editContactPerson').value = data.contact_person || '';
                    document.getElementById('editContactNumber').value = data.contact_number || '';
                    document.getElementById('editEmail').value = data.email || '';
                    
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

        // Export Functions
        function exportToExcel() {
            const table = document.getElementById('ticketsTable');
            const rows = table.querySelectorAll('tr');
            const data = [];
            
            const headers = [];
            rows[0].querySelectorAll('th').forEach((th, index) => {
                if (index > 0 && index < rows[0].querySelectorAll('th').length - 1) {
                    headers.push(th.innerText);
                }
            });
            data.push(headers);
            
            for (let i = 1; i < rows.length; i++) {
                const row = [];
                rows[i].querySelectorAll('td').forEach((td, index) => {
                    if (index > 0 && index < rows[i].querySelectorAll('td').length - 1) {
                        let value = td.innerText.trim();
                        if (index === 1) value = value.replace('#', '');
                        if (index === 4) value = value.replace('...', '');
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
                    if (index > 0 && index < row.querySelectorAll('td, th').length - 1) {
                        let text = cell.innerText.replace(/#/g, '').replace(/"/g, '""');
                        rowData.push('"' + text + '"');
                    }
                });
                if (rowData.length > 0) {
                    csv.push(rowData.join(','));
                }
            });
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tickets_export_${formatDate(new Date())}.csv`;
            a.click();
        }

        // IMPORT FUNCTIONS
        function openImportModal() {
            clearImportState();
            openModal('importModal');
        }

        function downloadTemplate() {
            window.location.href = '../download-template.php?type=tickets';
            addLogEntry('Excel template download started - please wait...', 'info');
            
            setTimeout(() => {
                addLogEntry('Template download should start automatically', 'success');
            }, 1000);
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;
            
            selectedFile = file;
            
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('fileInfo').style.display = 'flex';
            
            importLog = [];
            document.getElementById('importLog').innerHTML = '';
            document.getElementById('importLog').style.display = 'none';
            
            document.getElementById('importOptions').style.display = 'block';
            
            addLogEntry(`Loading file: ${file.name} (${formatFileSize(file.size)})`, 'info');
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    const data = e.target.result;
                    
                    let workbook;
                    if (file.name.toLowerCase().endsWith('.csv')) {
                        const lines = data.split('\n');
                        const csvData = lines.map(line => line.split(','));
                        workbook = XLSX.utils.book_new();
                        const worksheet = XLSX.utils.aoa_to_sheet(csvData);
                        XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');
                    } else {
                        const arrayBuffer = e.target.result;
                        workbook = XLSX.read(arrayBuffer, { type: 'array' });
                    }
                    
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    
                    importData = XLSX.utils.sheet_to_json(worksheet, { 
                        header: 1,
                        defval: '',
                        blankrows: true
                    });
                    
                    console.log('Raw import data:', importData);
                    
                    if (!importData || importData.length === 0) {
                        addLogEntry('File contains no data', 'error');
                        return;
                    }
                    
                    if (importData.length < 1) {
                        addLogEntry('File is empty', 'error');
                        return;
                    }
                    
                    const headers = importData[0] || [];
                    addLogEntry(`Found ${importData.length} rows in file`, 'info');
                    addLogEntry(`Headers: ${headers.join(' | ')}`, 'info');
                    
                    let dataRows = 0;
                    for (let i = 1; i < importData.length; i++) {
                        const row = importData[i];
                        if (row && row.some(cell => cell && cell.toString().trim() !== '')) {
                            dataRows++;
                        }
                    }
                    
                    addLogEntry(`${dataRows} rows contain data (excluding header)`, 'success');
                    
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

        function processImport() {
            if (!importData || importData.length < 2) {
                addLogEntry('No data to import - please select a valid file with data', 'error');
                return;
            }
            
            const duplicateHandling = document.getElementById('duplicateHandling').value;
            const skipEmptyRows = document.getElementById('skipEmptyRows').checked;
            
            document.getElementById('progressBarContainer').style.display = 'block';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('importButton').disabled = true;
            
            addLogEntry('', 'info');
            addLogEntry('='.repeat(50), 'info');
            addLogEntry(`STARTING IMPORT PROCESS`, 'info');
            addLogEntry('='.repeat(50), 'info');
            
            const headers = importData[0] || [];
            addLogEntry(`Headers: ${headers.join(' | ')}`, 'info');
            
            const records = [];
            let emptyRowCount = 0;
            
            for (let i = 1; i < importData.length; i++) {
                const row = importData[i];
                
                if (!row || row.length === 0) {
                    emptyRowCount++;
                    continue;
                }
                
                const record = {
                    ticket_id: row[0] ? row[0].toString().trim() : '',
                    company: row[1] ? row[1].toString().trim() : '',
                    contact: row[2] ? row[2].toString().trim() : '',
                    contact_number: row[3] ? row[3].toString().trim() : '',
                    concern: row[4] ? row[4].toString().trim() : '',
                    priority: row[5] ? row[5].toString().trim() : '',
                    status: row[6] ? row[6].toString().trim() : '',
                    assigned_to: row[7] ? row[7].toString().trim() : '',
                    date: row[8] ? row[8].toString().trim() : ''
                };
                
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
            
            const importUrl = '../import-tickets.php';
            
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
            
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                document.getElementById('progressBar').style.width = progress + '%';
                if (progress >= 90) clearInterval(interval);
            }, 200);
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
            const modals = [
                'viewTicketModal', 'editTicketModal', 'assignModal', 'reassignModal', 
                'statusModal', 'importModal', 'bulkAssignModal',
                'bulkStatusModal', 'bulkPriorityModal', 'bulkArchiveModal', 'archiveModal'
            ];
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
            
            updateBulkActions();
        });
    </script>
</body>
</html>