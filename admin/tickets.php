<?php
require_once '../auth_check.php';
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
        <?php include '../navbar.php'; ?>

        <!-- Header with Actions -->
        <div class="page-header">
            <div class="header-title">
                <h1>Ticket Management</h1>
                <p class="text-muted">Manage and track all support tickets</p>
            </div>
            <div class="header-actions">

                <!-- Bulk Actions Bar (Fixed Top Navigation) -->
                <div class="bulk-actions-bar" id="bulkActions">
                    <div class="bulk-bar-content">
                        <div class="bulk-bar-left">
                            <span class="bulk-count">
                                <i class="fas fa-layer-group"></i>
                                <span id="selectedCount">0</span> tickets selected
                            </span>
                            <button class="btn btn-sm" onclick="selectAll()"
                                style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white;">
                                Select All
                            </button>
                            <button class="btn btn-sm" onclick="clearSelection()"
                                style="background: transparent; border-color: rgba(255,255,255,0.2); color: white;">
                                Clear
                            </button>
                        </div>
                        <div class="bulk-bar-right">
                            <button class="btn btn-success btn-sm" onclick="openBulkAssignModal()">
                                <i class="fas fa-user-plus"></i> Assign
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="openBulkStatusModal()"
                                style="color: #fff; background: var(--warning); border-color: var(--warning);">
                                <i class="fas fa-sync-alt"></i> Status
                            </button>
                            <button class="btn btn-info btn-sm" onclick="openBulkPriorityModal()">
                                <i class="fas fa-flag"></i> Priority
                            </button>
                            <div class="bulk-divider"></div>
                            <button class="btn btn-danger btn-sm" onclick="openBulkArchiveModal()">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                            <button class="btn-icon bulk-close-btn" onclick="clearSelection()" title="Close Actions">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <a href="view-archive.php" class="btn btn-secondary">
                    <i class="fas fa-archive"></i> Archive
                </a>
                <button class="btn btn-secondary" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <?php
            // Get ticket statistics
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
                FROM tickets
            ")->fetch();
            ?>
            <div class="stat-card">
                <div class="stat-icon stat-icon-total">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Tickets</span>
                    <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= $stats['pending'] ?? 0 ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">In Progress</span>
                    <span class="stat-value"><?= $stats['in_progress'] ?? 0 ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-resolved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Resolved</span>
                    <span class="stat-value"><?= $stats['resolved'] ?? 0 ?></span>
                </div>
            </div>
        </div>

        <!-- Filter Tabs + Search -->
        <div class="filter-section">

            <div class="filter-tabs">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()"
                        placeholder="Search by ID, company, concern...">
                </div>
                <?php
                $filters = ['all', 'pending', 'in progress', 'resolved'];
                $filterLabels = ['All Tickets', 'Pending', 'In Progress', 'Resolved'];
                $filterIcons = ['fa-ticket-alt', 'fa-clock', 'fa-spinner', 'fa-check-circle'];
                foreach ($filters as $index => $filter) {
                    $activeClass = (isset($_GET['filter']) && $_GET['filter'] === $filter) || (!isset($_GET['filter']) && $filter === 'all') ? 'active' : '';
                    echo "<button class='filter-tab $activeClass' onclick='filterTickets(\"$filter\")'>
                            <i class='fas {$filterIcons[$index]}'></i>
                            <span>{$filterLabels[$index]}</span>
                          </button>";
                }
                ?>
            </div>

        </div>

        <!-- Tickets Table with Grouping -->
        <div class="tickets-container">
            <?php
            $sql = "
                SELECT t.ticket_id, t.priority, t.status, t.date_requested, t.concern_description, t.is_viewed,
                       c.company_name, c.contact_person, c.client_id, c.contact_number, c.email,
                       CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
                       t.technical_id,
                       cn.concern_name as concern_type
                FROM tickets t 
                LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id 
                LEFT JOIN clients c ON t.company_id = c.client_id
                LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
                $whereClause
                ORDER BY t.created_at DESC
            ";

            if (!empty($params)) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($sql);
            }

            $tickets = $stmt->fetchAll();

            // Group tickets by company (using normalized company name instead of ID to merge duplicates)
            $groupedTickets = [];
            foreach ($tickets as $ticket) {
                $companyNameRaw = $ticket['company_name'] ?? 'Unknown Company';
                $groupKey = strtolower(trim($companyNameRaw));

                if (!isset($groupedTickets[$groupKey])) {
                    $groupedTickets[$groupKey] = [
                        'company_name' => $companyNameRaw,
                        'contact_person' => $ticket['contact_person'] ?? 'Unknown',
                        'contact_number' => $ticket['contact_number'] ?? '',
                        'email' => $ticket['email'] ?? '',
                        'tickets' => []
                    ];
                }
                $groupedTickets[$groupKey]['tickets'][] = $ticket;
            }

            $groupIndex = 0;
            foreach ($groupedTickets as $groupKey => $group):
                $ticketCount = count($group['tickets']);
                $expanded = '';

                $hasNewTicket = false;
                $allResolved = true;
                foreach ($group['tickets'] as $ticket) {
                    if (in_array($ticket['status'], ['Pending', 'In Progress', 'Assigned']) && empty($ticket['is_viewed'])) {
                        $hasNewTicket = true;
                    }
                    if (!in_array($ticket['status'], ['Resolved', 'Closed'])) {
                        $allResolved = false;
                    }
                }
                $groupStatusClass = $allResolved ? 'group-status-resolved' : 'group-status-active';
                ?>
                <!-- Company Group Card -->
                <div class="company-group" style="position: relative;">
                    <?php if ($hasNewTicket): ?>
                        <span class="company-new-badge" id="company-badge-<?= $groupIndex ?>">New</span>
                    <?php endif; ?>
                    <div class="company-group-header <?= $expanded ?> <?= $groupStatusClass ?>"
                        data-company-id="<?= htmlspecialchars($groupKey) ?>" data-group-index="<?= $groupIndex ?>"
                        data-email="<?= htmlspecialchars($group['email'] ?? '') ?>"
                        data-contact="<?= htmlspecialchars($group['contact_number'] ?? '') ?>"
                        onclick="toggleCompanyGroup(<?= $groupIndex ?>)">
                        <div class="company-header-content">
                            <div class="company-info-main">
                                <div class="group-toggle-btn">
                                    <i class="fas fa-chevron-right group-collapsed-icon"></i>
                                </div>
                                <div class="company-avatar">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="company-details">
                                    <h3 class="company-name"><?= htmlspecialchars($group['company_name']) ?></h3>
                                    <div class="company-meta">
                                        <span><i class="fas fa-user"></i>
                                            <?= htmlspecialchars($group['contact_person']) ?></span>
                                        <?php if (!empty($group['contact_number'])): ?>
                                            <span><i class="fas fa-phone"></i>
                                                <?= htmlspecialchars($group['contact_number']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="company-stats">
                                <span class="ticket-count">
                                    <i class="fas fa-ticket-alt"></i>
                                    <?= $ticketCount ?>     <?= $ticketCount === 1 ? 'Ticket' : 'Tickets' ?>
                                </span>
                                <span class="expand-hint">
                                    View details
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Ticket Cards for this Company -->
                    <div class="company-tickets-wrapper company-group-<?= $groupIndex ?>">
                        <div class="company-tickets">
                            <div class="table-responsive">
                                <table class="tickets-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th style="width: 80px;">Ticket ID</th>
                                            <th>Concern / Subject</th>
                                            <th style="width: 120px;">Priority</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 150px;">Assigned</th>
                                            <th style="width: 120px;">Requested</th>
                                            <th style="width: 150px; text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($group['tickets'] as $ticket):
                                            $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                                            $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                                            // Fix: Check if ticket is resolved or closed
                                            $isResolvedOrClosed = in_array($ticket['status'], ['Resolved', 'Closed']);
                                            $isUnassigned = is_null($ticket['technical_id']);

                                            // Format date
                                            $dateRequested = new DateTime($ticket['date_requested']);
                                            $now = new DateTime();
                                            $interval = $dateRequested->diff($now);

                                            if ($interval->days == 0) {
                                                $timeAgo = 'Today';
                                            } elseif ($interval->days == 1) {
                                                $timeAgo = 'Yesterday';
                                            } else {
                                                $timeAgo = $interval->days . ' days';
                                            }
                                            ?>
                                            <tr class="ticket-row" data-priority="<?= $ticket['priority'] ?>"
                                                data-status="<?= $ticket['status'] ?>"
                                                data-ticket-id="<?= $ticket['ticket_id'] ?>"
                                                data-company-id="<?= htmlspecialchars($groupKey) ?>"
                                                data-is-unassigned="<?= $isUnassigned ? 'true' : 'false' ?>"
                                                onclick="if(typeof handleRowClick === 'function') handleRowClick(event, <?= $ticket['ticket_id'] ?>, <?= $groupIndex ?>)">

                                                <td class="table-checkbox">
                                                    <input type="checkbox" class="ticket-checkbox"
                                                        value="<?= $ticket['ticket_id'] ?>" onchange="updateBulkActions()">
                                                </td>

                                                <td class="ticket-id">
                                                    #<?= $ticket['ticket_id'] ?>
                                                </td>

                                                <td class="ticket-concern-cell">
                                                    <div class="concern-text"
                                                        title="<?= htmlspecialchars($ticket['concern_type'] ?? 'No concern specified') ?>">
                                                        <?= htmlspecialchars($ticket['concern_type'] ?? 'No concern specified') ?>
                                                    </div>
                                                    <div class="ticket-contact">
                                                        <i class="fas fa-user-tie"></i>
                                                        <?= htmlspecialchars($ticket['contact_person']) ?>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="badge badge-<?= $priorityClass ?>">
                                                        <i class="fas fa-flag"></i> <?= $ticket['priority'] ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="badge badge-<?= $statusClass ?>">
                                                        <i class="fas fa-circle"></i> <?= $ticket['status'] ?>
                                                    </span>
                                                </td>

                                                <td class="ticket-assigned-cell">
                                                    <?php if ($ticket['tech_name']): ?>
                                                        <div class="assigned-tech"><i class="fas fa-user-cog"></i>
                                                            <?= htmlspecialchars($ticket['tech_name']) ?></div>
                                                    <?php else: ?>
                                                        <span class="unassigned"><i class="fas fa-user-times"></i> Unassigned</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="ticket-date-cell">
                                                    <div class="date-main">
                                                        <?= date('M d, Y', strtotime($ticket['date_requested'])) ?>
                                                    </div>
                                                    <div class="time-ago"><?= $timeAgo ?></div>
                                                </td>

                                                <td>
                                                    <div class="table-actions">
                                                        <button class="btn-icon"
                                                            onclick="viewTicket(<?= $ticket["ticket_id"] ?>)"
                                                            title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <!-- Always show Assign button for unassigned tickets, and for assigned tickets that are not resolved/closed -->
                                                        <?php if (!$isResolvedOrClosed): ?>
                                                            <button class="btn-icon"
                                                                onclick="checkExistingAssignment(<?= $ticket["ticket_id"] ?>)"
                                                                title="<?= $isUnassigned ? 'Assign Technician' : 'Reassign Technician' ?>">
                                                                <i
                                                                    class="fas <?= $isUnassigned ? 'fa-user-plus' : 'fa-exchange-alt' ?>"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <button class="btn-icon"
                                                            onclick="editTicket(<?= $ticket["ticket_id"] ?>)"
                                                            title="Edit Ticket">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon"
                                                            onclick="updateStatus(<?= $ticket["ticket_id"] ?>)"
                                                            title="Update Status">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <button class="btn-icon delete"
                                                            onclick="confirmArchive(<?= $ticket["ticket_id"] ?>)"
                                                            title="Archive">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $groupIndex++;
            endforeach;

            if (empty($groupedTickets)):
                ?>
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>No tickets found</h3>
                    <p>Create a new ticket to get started</p>
                    <a href="new-ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create New Ticket
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- All Modals -->
    <!-- View Ticket Modal -->
    <div class="modal" id="viewTicketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ticket Details</h2>
                <button class="modal-close" onclick="closeModal('viewTicketModal')">&times;</button>
            </div>
            <div id="ticketDetails" class="ticket-details-content"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewTicketModal')">Close</button>
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
                        <input type="text" class="form-control" id="editCompanyName" list="editCompanyList"
                            autocomplete="off" required>
                        <datalist id="editCompanyList">
                            <?php
                            $clients = $pdo->query("SELECT DISTINCT company_name FROM clients WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name");
                            while ($client = $clients->fetch()):
                                ?>
                                <option value="<?= htmlspecialchars($client['company_name'], ENT_QUOTES) ?>"></option>
                                <?php
                            endwhile;
                            ?>
                        </datalist>
                        <small style="color: var(--text-muted);">Enter company name (will be created if not
                            exists)</small>
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
                                <option
                                    value="<?= htmlspecialchars($product['product_name'] . ' v' . $product['version']) ?>">
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

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTicketModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unified Assign/Reassign Modal -->
    <div class="modal" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="assignModalTitle">Assign Technical Staff</h2>
                <button class="modal-close" onclick="closeModal('assignModal')">&times;</button>
            </div>

            <div id="currentTechSection" class="current-tech-section"
                style="display: none; background: rgba(108, 92, 231, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--accent-primary);">
                <!-- Dynamic Content Here -->
            </div>

            <form id="assignForm" onsubmit="assignTechnical(event)">
                <input type="hidden" id="assignTicketId">
                <div class="form-group">
                    <label class="form-label" id="assignSelectLabel">Select Technical Staff</label>
                    <select class="form-control" id="technicalId" required>
                        <option value="">Choose staff...</option>
                        <?php
                        $techs = $pdo->query("SELECT * FROM technical_staff WHERE is_active = 1 ORDER BY firstname");
                        while ($tech = $techs->fetch()):
                            ?>
                            <option value="<?= $tech['technical_id'] ?>">
                                <?= htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' - ' . $tech['position']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="field-hint">
                        <i class="fas fa-info-circle"></i>
                        Only active technicians are shown
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="assignSubmitBtn">Assign</button>
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
                        $techs = $pdo->query("SELECT * FROM technical_staff WHERE is_active = 1 ORDER BY firstname");
                        while ($tech = $techs->fetch()):
                            ?>
                            <option value="<?= $tech['technical_id'] ?>">
                                <?= htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' - ' . $tech['position']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="field-hint">
                        <i class="fas fa-info-circle"></i>
                        Only active technicians are shown
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('bulkAssignModal')">Cancel</button>
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
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Solution / Remarks <span style="color:var(--danger)">*</span></label>
                    <textarea class="form-control" id="bulkSolution" rows="3" placeholder="Enter solution or remarks..."
                        required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('bulkStatusModal')">Cancel</button>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('bulkPriorityModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Priority</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Archive Modal -->
    <div class="modal" id="bulkArchiveModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Archive Multiple Tickets</h2>
                <button class="modal-close" onclick="closeModal('bulkArchiveModal')">&times;</button>
            </div>
            <div class="delete-warning">
                <i class="fas fa-archive" style="color: var(--warning);"></i>
                <h3>Are you sure?</h3>
                <p id="bulkArchiveMessage">You are about to archive <strong id="bulkArchiveCount">0</strong> tickets.
                    They can be restored from the archive.</p>
                <div class="form-group" style="margin-top: 15px;">
                    <label class="form-label">Archive Reason (Optional)</label>
                    <textarea class="form-control" id="bulkArchiveReason" rows="2"
                        placeholder="Enter reason for archiving..."></textarea>
                </div>
                <input type="hidden" id="bulkArchiveIds">
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('bulkArchiveModal')">Cancel</button>
                    <button class="btn btn-warning" onclick="bulkArchive()">Archive All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Archive Modal -->
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
                    <textarea class="form-control" id="archiveReason" rows="2"
                        placeholder="Enter reason for archiving..."></textarea>
                </div>
                <input type="hidden" id="archiveTicketId">
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
                    <button class="btn btn-warning" onclick="archiveTicket()">Archive</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
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
                    <textarea class="form-control" id="solution" rows="4"
                        placeholder="Enter solution or remarks..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
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

            <input type="file" id="excelFileInput" accept=".xlsx,.xls,.csv" style="display: none;"
                onchange="handleFileSelect(this)">

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
                        When checked, rows with all empty fields will be ignored. Uncheck to import even empty rows
                        (will use defaults).
                    </small>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                    <button class="btn btn-success" onclick="processImport()" id="importButton">Start Import</button>
                </div>
            </div>

            <div id="importLog" class="import-log" style="display: none;"></div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner">
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

        // Delete Ticket
        window.deleteTicket = function (id) {
            if (confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
                showLoading();
                fetch('../delete-ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ticket_id: id })
                })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
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
                        showNotification('Error deleting ticket', 'danger');
                    });
            }
        };

        // Bulk Operations State
        let selectedTickets = new Set();

        // Notification Badge helper
        window.hideNavBadge = function () {
            const badge = document.querySelector('.nav-badge');
            if (badge) badge.style.display = 'none';
        };

        window.markAsViewed = function (data) {
            fetch('../mark-viewed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).catch(e => console.error(e));
        };

        window.hideCompanyBadge = function (groupIndex) {
            const badge = document.getElementById(`company-badge-${groupIndex}`);
            if (badge && badge.style.display !== 'none') {
                badge.style.display = 'none';

                const header = document.querySelector(`.company-group-header[data-group-index="${groupIndex}"]`);
                if (header) {
                    // data-company-id holds the normalized company name string (not a numeric ID)
                    const companyName = header.getAttribute('data-company-id');
                    markAsViewed({ company_name: companyName });
                }
            }
        };

        window.hideCompanyBadgeByTicketId = function (id) {
            const row = document.querySelector(`.ticket-row[data-ticket-id="${id}"]`);
            if (row) {
                const header = row.closest('.company-group').querySelector('.company-group-header');
                if (header) {
                    const groupIndex = header.getAttribute('data-group-index');
                    const badge = document.getElementById(`company-badge-${groupIndex}`);
                    if (badge && badge.style.display !== 'none') {
                        hideCompanyBadge(groupIndex);
                    } else {
                        markAsViewed({ ticket_id: id });
                    }
                }
            }
        };

        window.handleRowClick = function (event, ticketId, groupIndex) {
            const target = event.target;
            if (target.tagName !== 'BUTTON' && target.tagName !== 'INPUT' && !target.closest('button')) {
                hideCompanyBadge(groupIndex);
                hideNavBadge();
            }
        };

        // Group Toggle Function
        window.toggleCompanyGroup = function (groupIndex) {
            hideNavBadge();
            hideCompanyBadge(groupIndex);
            const ticketsWrapper = document.querySelector(`.company-group-${groupIndex}`);
            const header = document.querySelector(`.company-group-header[data-group-index="${groupIndex}"]`);
            if (!ticketsWrapper || !header) return;

            const collapsedIcon = header.querySelector('.group-collapsed-icon');
            const expandHint = header.querySelector('.expand-hint i');

            const isExpanded = header.classList.contains('expanded');

            if (!isExpanded) {
                // Expand
                header.classList.add('expanded');
                ticketsWrapper.classList.add('expanded');
            } else {
                // Collapse
                header.classList.remove('expanded');
                ticketsWrapper.classList.remove('expanded');
            }
            if (typeof updateBulkActions === 'function') updateBulkActions();
        };

        // Expand all groups
        window.expandAllGroups = function () {
            const headers = document.querySelectorAll('.company-group-header');
            headers.forEach((header, index) => {
                const ticketsWrapper = document.querySelector(`.company-group-${index}`);

                if (ticketsWrapper) {
                    ticketsWrapper.classList.add('expanded');
                }
                header.classList.add('expanded');
            });
        };

        // Collapse all groups
        window.collapseAllGroups = function () {
            const headers = document.querySelectorAll('.company-group-header');
            headers.forEach((header, index) => {
                const ticketsWrapper = document.querySelector(`.company-group-${index}`);

                if (ticketsWrapper) {
                    ticketsWrapper.classList.remove('expanded');
                }
                header.classList.remove('expanded');
            });
        };

        // Utility Functions
        window.formatDate = function (date) {
            return date.toISOString().slice(0, 10);
        };

        // Bulk Operations Functions
        window.updateBulkActions = function () {
            const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');

            selectedTickets.clear();
            checkboxes.forEach(cb => selectedTickets.add(cb.value));

            const count = selectedTickets.size;
            document.getElementById('selectedCount').textContent = count;

            const bulkBar = document.getElementById('bulkActions');
            if (count > 0) {
                bulkBar.classList.add('visible');
            } else {
                bulkBar.classList.remove('visible');
            }
        };

        window.toggleSelectAll = function (checkbox) {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        };

        window.selectAll = function () {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
            updateBulkActions();
        };

        window.clearSelection = function () {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });

            // Also uncheck the master select all checkboxes that might exist
            const selectAllCheckboxes = document.querySelectorAll('#selectAllCheckbox');
            selectAllCheckboxes.forEach(cb => cb.checked = false);

            updateBulkActions();
        };

        window.getSelectedIds = function () {
            return Array.from(selectedTickets);
        };

        // Filter Function
        window.filterTickets = function (status) {
            const groups = document.querySelectorAll('.company-group');

            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            groups.forEach(group => {
                const tickets = group.querySelectorAll('.ticket-row');
                let hasVisibleTicket = false;

                tickets.forEach(ticket => {
                    if (status === 'all') {
                        ticket.style.display = 'table-row';
                        hasVisibleTicket = true;
                    } else if (status === 'unassigned') {
                        const isUnassigned = ticket.getAttribute('data-is-unassigned') === 'true';
                        ticket.style.display = isUnassigned ? 'table-row' : 'none';
                        if (isUnassigned) hasVisibleTicket = true;
                    } else {
                        const ticketStatus = ticket.getAttribute('data-status');
                        const match = ticketStatus && ticketStatus.toLowerCase() === status.toLowerCase();
                        ticket.style.display = match ? 'table-row' : 'none';
                        if (match) hasVisibleTicket = true;
                    }
                });

                group.style.display = hasVisibleTicket ? 'block' : 'none';
            });

            clearSelection();
        };

        // Search Function
        window.searchTable = function () {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const groups = document.querySelectorAll('.company-group');

            groups.forEach(group => {
                const tickets = group.querySelectorAll('.ticket-row');
                const companyName = group.querySelector('.company-name').textContent.toLowerCase();
                let hasMatch = false;

                tickets.forEach(ticket => {
                    const ticketId = ticket.querySelector('.ticket-id').textContent.toLowerCase();
                    const concern = ticket.querySelector('.concern-text').textContent.toLowerCase();
                    const contact = ticket.querySelector('.ticket-contact').textContent.toLowerCase();

                    const matches = ticketId.includes(searchTerm) ||
                        concern.includes(searchTerm) ||
                        contact.includes(searchTerm) ||
                        companyName.includes(searchTerm);

                    ticket.style.display = matches ? 'table-row' : 'none';
                    if (matches) hasMatch = true;
                });

                group.style.display = hasMatch ? 'block' : 'none';
            });

            clearSelection();
        };

        // Bulk Action Functions
        window.openBulkAssignModal = function () {
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
        };

        window.openBulkStatusModal = function () {
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
        };

        window.openBulkPriorityModal = function () {
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
        };

        window.openBulkArchiveModal = function () {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                showNotification('No tickets selected', 'warning');
                return;
            }

            document.getElementById('bulkArchiveIds').value = ids.join(',');
            document.getElementById('bulkArchiveCount').textContent = ids.length;
            document.getElementById('bulkArchiveMessage').innerHTML = `You are about to archive <strong>${ids.length}</strong> tickets. They can be restored from the archive.`;

            openModal('bulkArchiveModal');
        };

        // Bulk Assign
        window.bulkAssign = function (event) {
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
        };

        // Bulk Status Update
        window.bulkStatusUpdate = function (event) {
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
        };

        // Bulk Priority Update
        window.bulkPriorityUpdate = function (event) {
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
        };

        // Bulk Archive
        window.bulkArchive = function () {
            const ids = document.getElementById('bulkArchiveIds').value.split(',');
            const reason = document.getElementById('bulkArchiveReason').value;

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
                .then(response => response.json())
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
                    showNotification('Error during bulk archive', 'danger');
                });
        };

        // Single Ticket Archive
        window.confirmArchive = function (ticketId) {
            document.getElementById('archiveTicketId').value = ticketId;
            openModal('archiveModal');
        };

        window.archiveTicket = function () {
            const ticketId = document.getElementById('archiveTicketId').value;
            const reason = document.getElementById('archiveReason').value;

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
                .then(response => response.json())
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
                    showNotification('Error archiving ticket', 'danger');
                });
        };

        // Edit Ticket Functions
        window.editTicket = function (id) {
            hideNavBadge();
            hideCompanyBadgeByTicketId(id);
            showLoading();

            fetch(`../get-tickets.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();

                    if (data.error) {
                        showNotification(data.error, 'danger');
                        return;
                    }

                    const ticketCard = document.querySelector(`.ticket-row[data-ticket-id="${id}"]`);
                    const companyId = ticketCard.getAttribute('data-company-id');

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
                    showNotification('Error fetching ticket details', 'danger');
                });
        };

        window.submitEditTicket = function (event) {
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
        };

        // Assignment Functions
        // Assignment Functions - UPDATED with anti-hotlink protection
        window.checkExistingAssignment = function (ticketId) {
            hideNavBadge();
            hideCompanyBadgeByTicketId(ticketId);
            showLoading();

            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();

            fetch(`../get-ticket-assignment.php?id=${ticketId}&_=${timestamp}`, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    // Check if response is HTML (InfinityFree protection page)
                    if (text.trim().startsWith('<')) {
                        console.error('Received HTML instead of JSON. This is likely InfinityFree anti-hotlink protection.');
                        console.log('First 200 chars of response:', text.substring(0, 200));

                        // Try one more time with a longer delay
                        return new Promise(resolve => {
                            setTimeout(() => {
                                fetch(`../get-ticket-assignment.php?id=${ticketId}&_=${timestamp + 1}`, {
                                    headers: {
                                        'Cache-Control': 'no-cache',
                                        'Pragma': 'no-cache',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                    .then(res => res.text())
                                    .then(retryText => {
                                        try {
                                            resolve(JSON.parse(retryText));
                                        } catch (e) {
                                            console.error('Retry also failed with HTML');
                                            throw new Error('Server returned HTML - possible rate limiting. Please wait a moment and try again.');
                                        }
                                    })
                                    .catch(() => {
                                        throw new Error('Server returned HTML - possible rate limiting. Please wait a moment and try again.');
                                    });
                            }, 1000); // Wait 1 second before retry
                        });
                    }

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e, '\nRaw response:', text);
                        throw new Error('Invalid response from server');
                    }
                })
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

                    const assignModalTitle = document.getElementById('assignModalTitle');
                    const assignSubmitBtn = document.getElementById('assignSubmitBtn');
                    const assignSelectLabel = document.getElementById('assignSelectLabel');
                    const currentTechSection = document.getElementById('currentTechSection');
                    const assignTicketId = document.getElementById('assignTicketId');
                    const technicalId = document.getElementById('technicalId');

                    if (data.has_tech) {
                        currentTechName = data.tech_name;

                        if (assignModalTitle) assignModalTitle.innerHTML = '<i class="fas fa-exchange-alt" style="color: var(--warning);"></i> Reassign Ticket';
                        if (assignSubmitBtn) {
                            assignSubmitBtn.textContent = 'Reassign';
                            assignSubmitBtn.className = 'btn btn-warning';
                        }
                        if (assignSelectLabel) assignSelectLabel.textContent = 'Select New Technical Staff';

                        if (currentTechSection) {
                            currentTechSection.style.display = 'block';
                            currentTechSection.innerHTML = `
                            <p style="margin-bottom: 10px; font-weight: 500; font-size: 0.9rem; color: var(--text-secondary);">
                                <i class="fas fa-info-circle"></i> This ticket is currently assigned
                            </p>
                            <div class="assignment-info" style="display: grid; gap: 8px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Current Technical:</span>
                                    <strong style="color: var(--text-primary);"><i class="fas fa-user-hard-hat" style="color: var(--accent-primary);"></i> ${data.tech_name}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Assigned Since:</span>
                                    <strong style="color: var(--text-primary);">${data.assigned_date}</strong>
                                </div>
                            </div>
                        `;
                        }
                    } else {
                        if (assignModalTitle) assignModalTitle.textContent = 'Assign Technical Staff';
                        if (assignSubmitBtn) {
                            assignSubmitBtn.textContent = 'Assign';
                            assignSubmitBtn.className = 'btn btn-primary';
                        }
                        if (assignSelectLabel) assignSelectLabel.textContent = 'Select Technical Staff';
                        if (currentTechSection) currentTechSection.style.display = 'none';
                    }

                    if (assignTicketId) assignTicketId.value = ticketId;
                    if (technicalId) technicalId.value = '';
                    openModal('assignModal');
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification(error.message || 'Error checking assignment', 'danger');
                });
        };

        window.assignTechnical = function (event) {
            event.preventDefault();

            const assignTicketId = document.getElementById('assignTicketId');
            const technicalId = document.getElementById('technicalId');

            if (!assignTicketId || !assignTicketId.value) {
                showNotification('No ticket selected', 'warning');
                return;
            }

            const ticketId = assignTicketId.value;
            const techId = technicalId ? technicalId.value : '';

            if (!techId) {
                showNotification('Please select a technical staff', 'warning');
                return;
            }

            showLoading();

            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();

            fetch('../update-ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    technical_id: techId,
                    action: 'assign'
                })
            })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    // Check if response is HTML
                    if (text.trim().startsWith('<')) {
                        console.error('Received HTML instead of JSON:', text.substring(0, 200));
                        throw new Error('Server returned HTML - possible rate limiting. Please wait a moment and try again.');
                    }

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e, '\nRaw response:', text);
                        throw new Error('Invalid response from server');
                    }
                })
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Ticket assigned successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error: ' + (data.message || 'Unknown error'), 'danger');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification(error.message || 'Error assigning ticket', 'danger');
                });
        };

        // Ticket View Function
        window.viewTicket = function (id) {
            hideNavBadge();
            hideCompanyBadgeByTicketId(id);
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

                    document.getElementById('ticketDetails').innerHTML = `
                        <div class="ticket-detail-grid">
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Basic Information</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Ticket ID:</span>
                                    <span class="detail-value">#${data.ticket_id}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Company:</span>
                                    <span class="detail-value">${data.company_name || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Contact Person:</span>
                                    <span class="detail-value">${data.contact_person || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Contact Number:</span>
                                    <span class="detail-value">${data.contact_number || 'Not provided'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${data.email || 'Not provided'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h4><i class="fas fa-cog"></i> Ticket Details</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Product:</span>
                                    <span class="detail-value">${data.product_name || 'N/A'} ${data.version || ''}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Concern Type:</span>
                                    <span class="detail-value">${data.concern_type || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Priority:</span>
                                    <span class="detail-value"><span class='badge badge-${priorityClass}'>${data.priority}</span></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value"><span class='badge badge-${statusClass}'>${data.status}</span></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Date Requested:</span>
                                    <span class="detail-value">${data.date_requested ? new Date(data.date_requested).toLocaleString() : 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section full-width">
                                <h4><i class="fas fa-align-left"></i> Description</h4>
                                <div class="description-box">${data.concern_description || 'No description provided'}</div>
                            </div>
                            
                            ${data.tech_firstname ? `
                            <div class="detail-section">
                                <h4><i class="fas fa-user-cog"></i> Assignment</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Assigned To:</span>
                                    <span class="detail-value">${data.tech_firstname} ${data.tech_lastname}</span>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${data.solution ? `
                            <div class="detail-section">
                                <h4><i class="fas fa-check-circle"></i> Solution</h4>
                                <div class="detail-row">
                                    <span class="detail-value">${data.solution}</span>
                                </div>
                                ${data.finish_date ? `
                                <div class="detail-row">
                                    <span class="detail-label">Date Finished:</span>
                                    <span class="detail-value">${new Date(data.finish_date).toLocaleString()}</span>
                                </div>
                                ` : ''}
                            </div>
                            ` : ''}
                        </div>
                    `;

                    openModal('viewTicketModal');
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error fetching ticket details', 'danger');
                });
        }

        // Status Update Functions
        function updateStatus(id) {
            hideNavBadge();
            hideCompanyBadgeByTicketId(id);
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
                    showNotification('Error updating status', 'danger');
                });
        }

        // Export Functions
        window.exportToExcel = function () {
            expandAllGroups();

            const tickets = document.querySelectorAll('.ticket-row');
            const data = [];

            // Headers
            data.push(['ID', 'Company', 'Contact Person', 'Contact Number', 'Concern', 'Priority', 'Status', 'Assigned To', 'Date']);

            // Sanitizer helper
            const sanitize = (text) => {
                if (!text) return '';
                // Remove HTML tags
                let clean = text.replace(/<[^>]*>?/gm, '');
                // Remove emojis and FontAwesome icons
                clean = clean.replace(/[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F600}-\u{1F64F}\u{1F680}-\u{1F6FF}\u{1F900}-\u{1F9FF}\u{1FA70}-\u{1FAFF}\u{2B50}\u{23E9}-\u{23F3}]/gu, '');
                // Also remove specific icons if they bypassed regex
                clean = clean.replace(/[🐢⚡🔥]/g, '');
                return clean.trim().replace(/\s+/g, ' ');
            };

            tickets.forEach(ticket => {
                const header = ticket.closest('.company-group').querySelector('.company-group-header');
                const row = [];

                row.push(sanitize(ticket.querySelector('.ticket-id')?.textContent.replace('#', '')));
                row.push(sanitize(header.querySelector('.company-name')?.textContent));
                row.push(sanitize(ticket.querySelector('.ticket-contact')?.textContent));
                row.push(sanitize(header.getAttribute('data-contact')));

                // Concern text
                row.push(sanitize(ticket.querySelector('.concern-text')?.textContent));

                // Priority
                row.push(sanitize(ticket.getAttribute('data-priority')));

                // Status
                row.push(sanitize(ticket.getAttribute('data-status')));

                // Assigned To
                const assignedCell = ticket.querySelector('.ticket-assigned-cell');
                row.push(sanitize(assignedCell ? assignedCell.textContent : 'Unassigned'));

                // Date
                row.push(sanitize(ticket.querySelector('.date-main')?.textContent));

                data.push(row);
            });

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);

            // Apply Layout and Styling

            // 1. Column Widths
            ws['!cols'] = [
                { wch: 12 }, // ID 
                { wch: 30 }, // Company 
                { wch: 25 }, // Contact Person
                { wch: 20 }, // Contact Number
                { wch: 60 }, // Concern
                { wch: 15 }, // Priority
                { wch: 18 }, // Status
                { wch: 25 }, // Assigned To
                { wch: 20 }  // Date
            ];

            // 2. Auto-Filter setup (based on data length)
            const range = XLSX.utils.decode_range(ws['!ref']);
            ws['!autofilter'] = { ref: `A1:I${range.e.r + 1}` };

            // 3. Loop through every cell to add styles
            for (let R = range.s.r; R <= range.e.r; ++R) {
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    const address = XLSX.utils.encode_cell({ r: R, c: C });
                    if (!ws[address]) continue;

                    // Initialize empty style object if missing
                    if (!ws[address].s) ws[address].s = {};

                    // Thin gray borders for ALL cells
                    ws[address].s.border = {
                        top: { style: 'thin', color: { rgb: 'CCCCCC' } },
                        bottom: { style: 'thin', color: { rgb: 'CCCCCC' } },
                        left: { style: 'thin', color: { rgb: 'CCCCCC' } },
                        right: { style: 'thin', color: { rgb: 'CCCCCC' } }
                    };

                    // Specific Header Styling (Row 1 is r: 0)
                    if (R === 0) {
                        ws[address].s.fill = {
                            patternType: 'solid',
                            fgColor: { rgb: '4CAF50' }
                        };
                        ws[address].s.font = {
                            bold: true,
                            color: { rgb: 'FFFFFF' },
                            sz: 12
                        };
                        ws[address].s.alignment = {
                            horizontal: 'center',
                            vertical: 'center'
                        };
                    } else {
                        // Data alignment (Top)
                        ws[address].s.alignment = {
                            vertical: 'top',
                            wrapText: true // helps text not get cut off even more
                        };
                    }
                }
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
            XLSX.writeFile(wb, `tickets_export_${window.formatDate ? window.formatDate(new Date()) : new Date().toISOString().slice(0, 10)}.xlsx`);

            collapseAllGroups();
            if (typeof showNotification === 'function') {
                showNotification('Excel file downloaded successfully!', 'success');
            }
        };

        window.exportTickets = function () {
            expandAllGroups();

            const tickets = document.querySelectorAll('.ticket-row');
            const csv = [];

            // Headers
            csv.push('ID,Company,Contact Person,Contact Number,Concern,Priority,Status,Assigned To,Date');

            // Sanitizer helper
            const sanitize = (text) => {
                if (!text) return '';
                let clean = text.replace(/<[^>]*>?/gm, '');
                clean = clean.replace(/[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F600}-\u{1F64F}\u{1F680}-\u{1F6FF}\u{1F900}-\u{1F9FF}\u{1FA70}-\u{1FAFF}\u{2B50}\u{23E9}-\u{23F3}]/gu, '');
                clean = clean.replace(/[🐢⚡🔥]/g, '');
                return clean.trim().replace(/\s+/g, ' ');
            };

            tickets.forEach(ticket => {
                const header = ticket.closest('.company-group').querySelector('.company-group-header');
                const assignedCell = ticket.querySelector('.ticket-assigned-cell');

                const row = [
                    sanitize(ticket.querySelector('.ticket-id')?.textContent.replace('#', '')),
                    sanitize(header.querySelector('.company-name')?.textContent),
                    sanitize(ticket.querySelector('.ticket-contact')?.textContent),
                    sanitize(header.getAttribute('data-contact')),
                    sanitize(ticket.querySelector('.concern-text')?.textContent),
                    sanitize(ticket.getAttribute('data-priority')),
                    sanitize(ticket.getAttribute('data-status')),
                    sanitize(assignedCell ? assignedCell.textContent : 'Unassigned'),
                    sanitize(ticket.querySelector('.date-main')?.textContent)
                ].map(cell => `"${cell.replace(/"/g, '""')}"`).join(',');

                csv.push(row);
            });

            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tickets_export_${window.formatDate ? window.formatDate(new Date()) : new Date().toISOString().slice(0, 10)}.csv`;
            a.click();

            collapseAllGroups();
            if (typeof showNotification === 'function') {
                showNotification('CSV file downloaded successfully!', 'success');
            }
        };

        // Import Functions
        function openImportModal() {
            clearImportState();
            openModal('importModal');
        }

        function downloadTemplate() {
            window.location.href = '../download-template.php?type=tickets';
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;

            selectedFile = file;
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('fileInfo').style.display = 'flex';
            document.getElementById('importOptions').style.display = 'block';

            const reader = new FileReader();
            reader.onload = function (e) {
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
                    importData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '', blankrows: true });

                } catch (error) {
                    console.error('Error parsing file:', error);
                    showNotification('Error parsing file', 'danger');
                }
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
            selectedFile = null;
            importData = null;
        }

        function clearImportState() {
            clearSelectedFile();
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function processImport() {
            if (!importData || importData.length < 2) {
                showNotification('No data to import', 'warning');
                return;
            }

            const duplicateHandling = document.getElementById('duplicateHandling').value;
            const skipEmptyRows = document.getElementById('skipEmptyRows').checked;

            document.getElementById('progressBarContainer').style.display = 'block';
            document.getElementById('importButton').disabled = true;

            const records = [];
            for (let i = 1; i < importData.length; i++) {
                const row = importData[i];
                if (!row || row.length === 0) continue;

                const record = {
                    company: row[1] ? row[1].toString().trim() : '',
                    contact: row[2] ? row[2].toString().trim() : '',
                    contact_number: row[3] ? row[3].toString().trim() : '',
                    concern: row[4] ? row[4].toString().trim() : '',
                    priority: row[5] ? row[5].toString().trim() : '',
                    status: row[6] ? row[6].toString().trim() : '',
                    date: row[8] ? row[8].toString().trim() : ''
                };

                if (skipEmptyRows && !record.company && !record.contact && !record.concern) {
                    continue;
                }

                records.push(record);
            }

            fetch('../import-tickets.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    records: records,
                    duplicate_handling: duplicateHandling
                })
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('progressBar').style.width = '100%';
                    if (data.success) {
                        showNotification(`Import completed: ${data.imported} tickets added`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Import failed: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error during import', 'danger');
                })
                .finally(() => {
                    document.getElementById('importButton').disabled = false;
                });
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
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            const modals = [
                'viewTicketModal', 'editTicketModal', 'assignModal',
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

        // Group toggle function handled at the top of scripts section

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateBulkActions();
        });
    </script>
</body>

</html>