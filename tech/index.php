<?php
require_once '../auth_check.php';
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect Admins accidentally landing here
if (($_SESSION['tech_position'] ?? '') === 'Admin') {
    header("Location: ../admin/index.php");
    exit;
}

$tech_id = $_SESSION['tech_id'] ?? 0;

// Fetch tickets ONLY for this technician
$ticketStmt = $pdo->prepare("
    SELECT 
        t.ticket_id, t.priority, t.status, t.date_requested, t.concern_description, t.is_viewed,
        c.company_name, c.contact_person, c.contact_number, c.email, c.client_id,
        CONCAT(ts.firstname, ' ', ts.lastname) as tech_name,
        t.technical_id, cn.concern_name as concern_type
    FROM tickets t
    LEFT JOIN clients c ON t.company_id = c.client_id
    LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
    LEFT JOIN technical_staff ts ON t.technical_id = ts.technical_id
    WHERE t.technical_id = ?
    ORDER BY t.created_at DESC
");
$ticketStmt->execute([$tech_id]);
$tickets = $ticketStmt->fetchAll();

// Get performance data ONLY for this technician
$perfStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_ticket,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolve,
        SUM(CASE WHEN status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END) as pending
    FROM tickets 
    WHERE technical_id = ?
");
$perfStmt->execute([$tech_id]);
$perf = $perfStmt->fetch();

$totalTickets = $perf['total_ticket'] ?? 0;
$resolved = $perf['resolve'] ?? 0;
$pending = $perf['pending'] ?? 0;

// Group tickets by company (using normalized company name)
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

// Fetch form dependencies for Add Ticket Modal
$formCompanies = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT company_name FROM clients WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name");
    $formCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$formProducts = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY product_name");
    $formProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$formConcerns = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM concerns 
        ORDER BY CASE WHEN concern_name = 'Others' THEN 1 ELSE 0 END, concern_name ASC
    ");
    $formConcerns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Dashboard - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/technical.css"> <!-- For company-stats header -->
    <link rel="stylesheet" href="../css/tickets.css"> <!-- For tickets table layout -->
    <link rel="stylesheet" href="../css/new-tickets.css"> <!-- For the Add Ticket form inside the modal -->
    <link rel="stylesheet" href="tech-page.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="technical-page">
    <div class="container container-tech">
        <?php include '../navbar.php'; ?>

        <div class="tech-main tech-main-full" id="techMainContent">
            <div class="tech-main-header">
                <div class="tech-title-section tech-title-header">
                    <div>
                        <h2>
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($_SESSION['tech_firstname'] ?? 'Technician') ?>
                        </h2>
                        <span class="status-badge active">Active</span>
                    </div>

                    <!-- Bulk Actions Bar -->
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
                                <button class="btn btn-warning btn-sm" onclick="openBulkStatusModal()"
                                    style="color: #fff; background: var(--warning); border-color: var(--warning);">
                                    <i class="fas fa-sync-alt"></i> Status
                                </button>
                                <button class="btn-icon bulk-close-btn" onclick="clearSelection()"
                                    title="Close Actions">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tech-header-actions">
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                        <button class="btn btn-primary btn-sm tech-add-btn" onclick="openAddTicketModal()">
                            <i class="fas fa-plus"></i> Add Ticket
                        </button>
                    </div>
                </div>
            </div>

            <!-- Company Stats identical to Admin view -->
            <div class="company-stats">
                <div class="company-stat-card primary-stat">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-details">
                        <div class="value"><?= count($tickets) ?></div>
                        <div class="label">Assigned Tickets</div>
                    </div>
                </div>
                <div class="company-stat-card success-stat">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <div class="value"><?= $resolved ?></div>
                        <div class="label">Resolved</div>
                    </div>
                </div>
                <div class="company-stat-card warning-stat">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <div class="value"><?= $pending ?></div>
                        <div class="label">Active Tickets</div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="tech-filter-section">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="techSearchInput" placeholder="Search by #Ticket ID, Company, or Concern...">
                </div>
                <div class="filter-tabs" id="techFilterTabs">
                    <button class="filter-tab active" data-filter="all"><i class="fas fa-list"></i> All Records</button>
                    <button class="filter-tab" data-filter="in progress"><i class="fas fa-spinner"></i> In
                        Progress</button>
                    <button class="filter-tab" data-filter="resolved"><i class="fas fa-check-circle"></i>
                        Resolved</button>
                </div>
            </div>

            <!-- Grouped Tickets Container -->
            <div class="tickets-container tech-tickets-wrapper">
                <?php
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
                    <div class="company-group tech-company-group">
                        <span class="company-new-badge" id="company-badge-<?= htmlspecialchars($groupKey) ?>"
                            style="display: <?= $hasNewTicket ? 'flex' : 'none' ?>;"></span>
                        <div class="company-group-header <?= $expanded ?> <?= $groupStatusClass ?>"
                            data-company-id="<?= htmlspecialchars($groupKey) ?>" data-group-index="<?= $groupIndex ?>"
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
                                <div class="company-stats-custom tech-company-stats">
                                    <span class="ticket-count tech-ticket-count">
                                        <i class="fas fa-ticket-alt tech-icon-accent"></i>
                                        <?= $ticketCount ?>     <?= $ticketCount === 1 ? 'Ticket' : 'Tickets' ?>
                                    </span>
                                    <span class="expand-hint tech-expand-hint">
                                        View details
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Individual Ticket Cards for this Company -->
                        <div class="company-tickets-wrapper company-group-<?= $groupIndex ?>">
                            <div class="company-tickets tech-company-tickets">
                                <div class="table-responsive">
                                    <table class="tickets-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;"></th>
                                                <th>Ticket ID</th>
                                                <th>Subject</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Requested</th>
                                                <th style="text-align: center;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($group['tickets'] as $ticket):
                                                $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                                                $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));

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
                                                            <?= htmlspecialchars($ticket['contact_person'] ?? 'Unknown') ?>
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
                                                    <td class="ticket-date-cell">
                                                        <div class="date-main">
                                                            <?= date('M d, Y', strtotime($ticket['date_requested'])) ?></div>
                                                        <div class="time-ago"><?= $timeAgo ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="table-actions tech-table-actions">
                                                            <button class="btn-icon"
                                                                onclick="viewTicket(<?= $ticket['ticket_id'] ?>)"
                                                                title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if (!in_array($ticket['status'], ['Resolved', 'Closed'])): ?>
                                                                <button class="btn-icon warning"
                                                                    onclick="updateStatus(<?= $ticket['ticket_id'] ?>)"
                                                                    title="Update Status">
                                                                    <i class="fas fa-sync-alt"></i>
                                                                </button>
                                                            <?php endif; ?>
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
                        <i class="fas fa-glass-cheers"></i>
                        <h3>No Assigned Tickets</h3>
                        <p>You have no tickets in your queue! Great job.</p>
                    </div>
                <?php endif; ?>
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
            <div id="ticketDetails" class="ticket-details-content"></div>
            <div class="modal-footer tech-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewTicketModal')">Close</button>
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
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Solution / Remarks <span style="color:var(--danger)">*</span></label>
                    <textarea class="form-control" id="solution" rows="4" placeholder="Enter solution or remarks..."
                        required></textarea>
                </div>
                <div class="modal-footer tech-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
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
            <div class="bulk-info" id="bulkStatusInfo"
                style="margin-bottom: 15px; background: rgba(255,193,7,0.1); padding: 10px; border-radius: 8px;"></div>
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
                <div class="modal-footer tech-modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('bulkStatusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Ticket Modal -->
    <div class="modal" id="addTicketModal">
        <div class="modal-content tech-modal-content-lg">
            <div class="modal-header tech-modal-header-bordered">
                <h2 class="tech-modal-title">
                    <i class="fas fa-pen-fancy tech-icon-accent"></i>
                    Create New Support Ticket
                </h2>
                <button class="modal-close tech-modal-close" onclick="closeModal('addTicketModal')">&times;</button>
            </div>

            <form id="addTicketForm" onsubmit="submitNewTicket(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Company Name
                        </label>
                        <input type="text" class="form-control" id="newCompanyName" placeholder="Enter company name"
                            list="companyList" autocomplete="off" required>
                        <datalist id="companyList">
                            <?php foreach ($formCompanies as $c): ?>
                                <option value="<?= htmlspecialchars($c['company_name'], ENT_QUOTES) ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Contact Person
                        </label>
                        <input type="text" class="form-control" id="newContactPerson" placeholder="Full name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Contact Number
                        </label>
                        <input type="tel" class="form-control" id="newContactNumber" placeholder="+63 XXX XXX XXXX"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="newEmail" placeholder="company@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-box"></i> Product
                        </label>
                        <select class="form-control" id="newProduct" required>
                            <option value="">Select Product</option>
                            <?php foreach ($formProducts as $p): ?>
                                <option value="<?= htmlspecialchars($p['product_name'] . ' v' . $p['version']) ?>">
                                    <?= htmlspecialchars($p['product_name'] . ' v' . $p['version']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($formProducts))
                                echo "<option value=''>No products available</option>"; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-exclamation-triangle"></i> Concern Type
                        </label>
                        <select class="form-control" id="newConcern" required
                            onchange="toggleNewTicketDescriptionRequirement()">
                            <option value="">Select Concern</option>
                            <?php foreach ($formConcerns as $c): ?>
                                <option value="<?= htmlspecialchars($c['concern_name']) ?>"
                                    data-id="<?= $c['concern_id'] ?>">
                                    <?= htmlspecialchars($c['concern_name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($formConcerns))
                                echo "<option value=''>No concerns available</option>"; ?>
                        </select>
                        <small class="field-hint" id="newConcernHint">
                            <i class="fas fa-info-circle"></i> Select "Others" if your concern is not listed
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" id="newDescriptionLabel">
                        <i class="fas fa-align-left"></i> Detailed Description
                        <span id="newRequiredIndicator" class="tech-danger-indicator">*</span>
                    </label>
                    <textarea class="form-control" id="newDescription"
                        placeholder="Please describe the issue in detail..."></textarea>
                    <small class="field-hint" id="newDescriptionHint">
                        <i class="fas fa-info-circle"></i>
                        <span id="newHintText">Description is optional for standard concerns</span>
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Priority Level
                        </label>
                        <select class="form-control" id="newPriority" required>
                            <option value="Low">🐢 Low - Minor issue</option>
                            <option value="Medium" selected>⚡ Medium - Normal priority</option>
                            <option value="High">🔥 High - Critical issue</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i> Date Requested
                        </label>
                        <input type="date" class="form-control" id="newDateRequested" value="<?= date('Y-m-d') ?>"
                            required>
                    </div>
                </div>

                <div class="modal-footer tech-modal-footer-bordered">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('addTicketModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Create Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="modal tech-loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="../script.js"></script>
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
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

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Toggle Company Group Expand/Collapse
        function toggleCompanyGroup(index) {
            const header = document.querySelector(`.company-group-header[data-group-index="${index}"]`);
            const wrapper = document.querySelector(`.company-group-${index}`);
            const expandHint = header ? header.querySelector('.expand-hint') : null;

            if (!header || !wrapper) return;

            const isExpanded = header.classList.contains('expanded');

            if (isExpanded) {
                // Collapse
                header.classList.remove('expanded');
                wrapper.classList.remove('expanded');
                if (expandHint) {
                    expandHint.innerHTML = 'View details <i class="fas fa-chevron-right"></i>';
                }
            } else {
                // Expand
                header.classList.add('expanded');
                wrapper.classList.add('expanded');
                if (expandHint) {
                    expandHint.innerHTML = 'Hide details <i class="fas fa-chevron-down"></i>';
                }

                // Mark as viewed for notification badge
                const compId = header.getAttribute('data-company-id');
                if (compId) {
                    fetch('../mark-viewed.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ company_name: compId })
                    }).then(() => {
                        const badge = document.getElementById(`company-badge-${compId}`);
                        if (badge) badge.style.display = 'none';
                    }).catch(err => console.error(err));
                }
            }
        }

        // Dynamic badge updates from navbar.php's fetch
        window.updateCompanyBadges = function (unreadCompaniesMap) {
            // Hide all badges first
            document.querySelectorAll('.company-new-badge').forEach(badge => {
                badge.style.display = 'none';
            });

            // Show badges for companies that have unread tickets
            for (const [groupKey, count] of Object.entries(unreadCompaniesMap)) {
                // escape the selector carefully
                const safeKey = CSS.escape(groupKey);
                const badge = document.getElementById(`company-badge-${groupKey}`);
                if (badge) {
                    // If you want a dot, keep it empty or set a specific style. 
                    // We'll just leave it as an empty span which can be styled as a dot.
                    badge.style.display = 'flex';
                }
            }
        };

        function viewTicket(id) {
            showLoading();

            // Ensure mark as viewed when tech opens ticket
            fetch(`../mark-viewed.php?id=${id}`, { method: 'POST' }).catch(e => console.log('Notice only'));

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
                    <div class="tech-ticket-detail-grid">
                        <div class="detail-section">
                            <h4><i class="fas fa-info-circle"></i> Basic Information</h4>
                            <p><strong>Ticket ID:</strong> #${data.ticket_id}</p>
                            <p><strong>Company:</strong> ${data.company_name || 'N/A'}</p>
                            <p><strong>Contact Person:</strong> ${data.contact_person || 'N/A'}</p>
                            <p><strong>Contact Number:</strong> ${data.contact_number || 'Not provided'}</p>
                            <p><strong>Email:</strong> ${data.email || 'Not provided'}</p>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-cog"></i> Ticket Details</h4>
                            <p><strong>Product:</strong> ${data.product_name || 'N/A'} ${data.version || ''}</p>
                            <p><strong>Concern Type:</strong> ${data.concern_type || 'N/A'}</p>
                            <p><strong>Priority:</strong> <span class='badge badge-${priorityClass}'>${data.priority}</span></p>
                            <p><strong>Status:</strong> <span class='badge badge-${statusClass}'>${data.status}</span></p>
                            <p><strong>Date Requested:</strong> ${data.date_requested ? new Date(data.date_requested).toLocaleString() : 'N/A'}</p>
                        </div>
                        
                        <div class="detail-section tech-detail-full-width">
                            <h4><i class="fas fa-align-left"></i> Description</h4>
                            <div class="tech-description-box">${data.concern_description || 'No description provided'}</div>
                        </div>
                        
                        ${data.solution ? `
                        <div class="detail-section tech-detail-full-width">
                            <h4><i class="fas fa-check-circle"></i> Solution / Remarks</h4>
                            <div class="tech-description-box">${data.solution}</div>
                            ${data.finish_date ? `<p class="tech-solution-text"><strong>Date Finished:</strong> ${new Date(data.finish_date).toLocaleString()}</p>` : ''}
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
                        closeModal('statusModal');
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

        function openAddTicketModal() {
            document.getElementById('addTicketForm').reset();
            document.getElementById('newDateRequested').value = new Date().toISOString().split('T')[0];
            toggleNewTicketDescriptionRequirement();
            openModal('addTicketModal');
        }

        function toggleNewTicketDescriptionRequirement() {
            const concernSelect = document.getElementById('newConcern');
            const description = document.getElementById('newDescription');
            const requiredIndicator = document.getElementById('newRequiredIndicator');
            const hintText = document.getElementById('newHintText');

            const isOthers = concernSelect.value === 'Others';

            if (isOthers) {
                description.required = true;
                requiredIndicator.style.display = 'inline';
                hintText.textContent = 'Description is required for "Others"';
                description.placeholder = 'Please describe your concern in detail (required)';
            } else {
                description.required = false;
                requiredIndicator.style.display = 'none';
                hintText.textContent = 'Description is optional for standard concerns';
                description.placeholder = 'Please describe the issue in detail... (optional)';
            }
        }

        function submitNewTicket(event) {
            event.preventDefault();

            const concernSelect = document.getElementById('newConcern');
            const description = document.getElementById('newDescription');

            if (concernSelect.value === 'Others' && !description.value.trim()) {
                showNotification('Please provide a description for "Others" concern', 'danger');
                description.focus();
                return;
            }

            showLoading();

            const formData = {
                company_name: document.getElementById('newCompanyName').value,
                contact_person: document.getElementById('newContactPerson').value,
                contact_number: document.getElementById('newContactNumber').value,
                email: document.getElementById('newEmail').value,
                product: document.getElementById('newProduct').value,
                concern: concernSelect.value,
                description: description.value || null,
                priority: document.getElementById('newPriority').value,
                date_requested: document.getElementById('newDateRequested').value
            };

            fetch('../create-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Ticket created successfully! 🎉', 'success');
                        closeModal('addTicketModal');
                        setTimeout(() => location.reload(), 1500);
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

        // Close modals when clicking outside
        window.onclick = function (event) {
            const modals = ['viewTicketModal', 'statusModal', 'addTicketModal', 'bulkStatusModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Bulk Operations State
        let selectedTickets = new Set();

        // Add markAsViewed to mimic admin functionality for row clicks
        window.markAsViewed = function (data) {
            fetch('../mark-viewed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).catch(e => console.error(e));
        };

        window.hideNavBadge = function () {
            // Dummy function in case it's used elsewhere, nav-badge handling might be limited in tech page
        };

        window.hideCompanyBadge = function (groupIndex) {
            const badge = document.getElementById(`company-badge-${groupIndex}`);
            if (badge && badge.style.display !== 'none') {
                badge.style.display = 'none';
            }
        };

        window.handleRowClick = function (event, ticketId, groupIndex) {
            const target = event.target;
            if (target.tagName !== 'BUTTON' && target.tagName !== 'INPUT' && !target.closest('button')) {
                hideCompanyBadge(groupIndex);
            }
        };

        window.updateBulkActions = function () {
            const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');

            selectedTickets.clear();
            checkboxes.forEach(cb => selectedTickets.add(cb.value));

            const count = selectedTickets.size;
            const countElem = document.getElementById('selectedCount');
            if (countElem) countElem.textContent = count;

            const bulkBar = document.getElementById('bulkActions');
            if (bulkBar) {
                if (count > 0) {
                    bulkBar.classList.add('visible');
                } else {
                    bulkBar.classList.remove('visible');
                }
            }
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
            updateBulkActions();
        };

        window.getSelectedIds = function () {
            return Array.from(selectedTickets);
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

        window.expandAllGroups = function () {
            const headers = document.querySelectorAll('.company-group-header');
            headers.forEach((header) => {
                const index = header.getAttribute('data-group-index');
                const wrapper = document.querySelector(`.company-group-${index}`);
                const icon = header.querySelector('.group-collapsed-icon');
                const expandHint = header.querySelector('.expand-hint');

                if (wrapper) {
                    wrapper.style.display = 'block';
                }
                header.classList.add('expanded');
                if (icon) icon.style.transform = 'rotate(90deg)';
                if (expandHint) expandHint.innerHTML = 'Hide details <i class="fas fa-chevron-down"></i>';
            });
        };

        window.collapseAllGroups = function () {
            const headers = document.querySelectorAll('.company-group-header');
            headers.forEach((header) => {
                const index = header.getAttribute('data-group-index');
                const wrapper = document.querySelector(`.company-group-${index}`);
                const icon = header.querySelector('.group-collapsed-icon');
                const expandHint = header.querySelector('.expand-hint');

                if (wrapper) {
                    wrapper.style.display = 'none';
                }
                header.classList.remove('expanded');
                if (icon) icon.style.transform = 'rotate(0deg)';
                if (expandHint) expandHint.innerHTML = expandHint.innerHTML.includes('Click to expand') ? 'Click to expand <i class="fas fa-chevron-right"></i>' : 'View details <i class="fas fa-chevron-right"></i>';
            });
        };

        window.formatDate = function (date) {
            return date.toISOString().slice(0, 10);
        };

        window.exportToExcel = function () {
            expandAllGroups();

            const tickets = document.querySelectorAll('.ticket-row');
            const data = [];

            // Headers
            data.push(['ID', 'Company', 'Contact Person', 'Contact Number', 'Concern', 'Priority', 'Status', 'Date']);

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
                if (!header) return;

                const row = [];

                row.push(sanitize(ticket.querySelector('.ticket-id')?.textContent.replace('#', '')));
                row.push(sanitize(header.querySelector('.company-name')?.textContent));
                row.push(sanitize(ticket.querySelector('.ticket-contact')?.textContent));

                // Contact Number (it's the second span in company-meta if exists)
                const metaSpans = header.querySelectorAll('.company-meta span');
                row.push(sanitize(metaSpans.length > 1 ? metaSpans[1].textContent : ''));

                // Concern text
                row.push(sanitize(ticket.querySelector('.concern-text')?.textContent));

                // Priority
                row.push(sanitize(ticket.getAttribute('data-priority')));

                // Status
                row.push(sanitize(ticket.getAttribute('data-status')));

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
                { wch: 20 }  // Date
            ];

            // 2. Auto-Filter setup (based on data length)
            const range = XLSX.utils.decode_range(ws['!ref']);
            ws['!autofilter'] = { ref: `A1:H${range.e.r + 1}` };

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
                            wrapText: true
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
    </script>

    <script>
        // Tech Dashboard Filter Logic
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('techSearchInput');
            const filterTabs = document.querySelectorAll('#techFilterTabs .filter-tab');
            const companyGroups = document.querySelectorAll('.tech-company-group');
            let currentFilter = 'all';

            function filterTickets() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

                companyGroups.forEach((group, index) => {
                    // Get company header text which includes company name, contact person, and number
                    const header = group.querySelector('.company-group-header');
                    const headerText = header ? header.textContent.toLowerCase() : '';
                    const groupMatchesSearch = headerText.includes(searchTerm);

                    const rows = group.querySelectorAll('.ticket-row');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const status = (row.getAttribute('data-status') || '').toLowerCase();
                        const textContent = row.textContent.toLowerCase();

                        // Match if either the ticket details OR the company header details match the search
                        const matchesSearch = groupMatchesSearch || textContent.includes(searchTerm);
                        const matchesTab = currentFilter === 'all' || status === currentFilter;

                        if (matchesSearch && matchesTab) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Show/hide entire company group based on visible tickets
                    if (visibleCount === 0) {
                        group.style.display = 'none';
                    } else {
                        group.style.display = '';
                        // Dynamically update group header ticket count
                        const countBadge = group.querySelector('.tech-ticket-count');
                        if (countBadge) {
                            countBadge.innerHTML = `<i class="fas fa-ticket-alt tech-icon-accent"></i> ${visibleCount} Ticket${Math.abs(visibleCount) !== 1 ? 's' : ''}`;
                        }
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterTickets);
            }

            filterTabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    currentFilter = e.currentTarget.getAttribute('data-filter');
                    filterTickets();
                });
            });
        });
    </script>
</body>

</html>