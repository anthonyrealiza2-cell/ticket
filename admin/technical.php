<?php
require_once '../auth_check.php';
require_once '../database.php';

// Inside technical.php, near the top:
$allTechsStmt = $pdo->query("SELECT * FROM technical_staff");
$allTechs = $allTechsStmt->fetchAll(PDO::FETCH_ASSOC);

$allTicketsStmt = $pdo->query("
    SELECT t.*, c.company_name, c.contact_person, c.contact_number, cn.concern_name 
    FROM tickets t 
    LEFT JOIN clients c ON t.company_id = c.client_id 
    LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
");
$allTickets = $allTicketsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Staff - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/technical.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Inject the PHP data directly into JavaScript variables with UTF8 and fallback protection
        const globalTechData = <?php echo json_encode($allTechs, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
        const globalTicketData = <?php echo json_encode($allTickets, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
    </script>
</head>
<body class="technical-page">
    <div class="container">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Header -->
        <div class="page-header">
            <h1>Technical Staff Management</h1>
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-excel" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button class="btn btn-primary" onclick="openAddTechModal()">
                    <i class="fas fa-plus-circle"></i> Add Technical Staff
                </button>
            </div>
        </div>

        <!-- Performance Overview Cards -->
        <div class="technical-stats-grid">
            <?php
            // Get total staff (all staff)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM technical_staff");
            $totalStaff = $stmt->fetch()['total'];
            
            // Get active staff count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM technical_staff WHERE is_active = 1");
            $activeStaff = $stmt->fetch()['total'];
            
            // Get total resolved tickets (direct query)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'Resolved'");
            $totalResolved = $stmt->fetch()['total'] ?? 0;
            
            // Get total assigned tickets (direct query)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE technical_id IS NOT NULL");
            $totalAssigned = $stmt->fetch()['total'] ?? 0;
            
            // Get top performer based on MOST RESOLVED TICKETS (direct query)
            $stmt = $pdo->query("
                SELECT 
                    CONCAT(ts.firstname, ' ', ts.lastname) as name,
                    COUNT(t.ticket_id) as total_ticket,
                    SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) as resolve
                FROM technical_staff ts
                LEFT JOIN tickets t ON ts.technical_id = t.technical_id
                WHERE ts.is_active = 1
                GROUP BY ts.technical_id
                ORDER BY resolve DESC 
                LIMIT 1
            ");
            $topPerformer = $stmt->fetch();
            
            // Get second place for comparison
            $stmt = $pdo->query("
                SELECT 
                    CONCAT(ts.firstname, ' ', ts.lastname) as name,
                    SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) as resolve
                FROM technical_staff ts
                LEFT JOIN tickets t ON ts.technical_id = t.technical_id
                WHERE ts.is_active = 1
                GROUP BY ts.technical_id
                ORDER BY resolve DESC 
                LIMIT 1, 1
            ");
            $secondPlace = $stmt->fetch();
            ?>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-users-cog"></i></div>
                <div class="technical-stat-info">
                    <h3>Total Staff</h3>
                    <div class="technical-stat-number"><?php echo $totalStaff; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="technical-stat-info">
                    <h3>Active Staff</h3>
                    <div class="technical-stat-number"><?php echo $activeStaff; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="technical-stat-info">
                    <h3>Total Assigned</h3>
                    <div class="technical-stat-number"><?php echo $totalAssigned; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="technical-stat-info">
                    <h3>Total Resolved</h3>
                    <div class="technical-stat-number"><?php echo $totalResolved; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card <?php echo $topPerformer ? 'top-performer' : ''; ?>">
                <div class="technical-stat-icon"><i class="fas fa-crown"></i></div>
                <div class="technical-stat-info">
                    <h3>Top Performer</h3>
                    <div class="technical-stat-number"><?php echo $topPerformer['name'] ?? 'N/A'; ?></div>
                    <?php if ($topPerformer): ?>
                        <div class="stat-detail">
                            <i class="fas fa-check-circle"></i> <?php echo $topPerformer['resolve']; ?> resolved
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- New Layout: Sidebar + Main Content -->
        <div class="technical-dashboard">
            <!-- Left Sidebar - List of Technicians -->
            <div class="tech-sidebar">
                <div class="tech-sidebar-header">
                    <h2><i class="fas fa-users-cog"></i> Technical Staff</h2>
                    <p><i class="fas fa-user-check"></i> <?php echo $activeStaff; ?> active / <?php echo $totalStaff; ?> total</p>
                </div>
                
                <div class="tech-list" id="techList">
                    <?php
                    // Direct query without view
                    $techStmt = $pdo->query("
                        SELECT 
                            ts.*,
                            COUNT(t.ticket_id) as total_ticket,
                            SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) as resolve,
                            SUM(CASE WHEN t.status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END) as pending,
                            COALESCE(COUNT(t.ticket_id), 0) as ticket_count
                        FROM technical_staff ts
                        LEFT JOIN tickets t ON ts.technical_id = t.technical_id
                        GROUP BY ts.technical_id
                        ORDER BY ts.is_active DESC, resolve DESC
                    ");
                    
                    $firstTech = null;
                    while($tech = $techStmt->fetch()) {
                        if (!$firstTech) $firstTech = $tech;
                        $isActive = $tech['is_active'] ?? 1;
                        $ticketCount = $tech['ticket_count'] ?? 0;
                        $resolved = $tech['resolve'] ?? 0;
                        ?>
                        <div class="tech-item <?php echo $isActive ? '' : 'inactive'; ?>" 
                             onclick="selectTech(<?php echo $tech['technical_id']; ?>, this)" 
                             data-tech-id="<?php echo $tech['technical_id']; ?>">
                            <div class="tech-item-left">
                                <div class="tech-avatar" style="<?php echo !$isActive ? 'opacity: 0.6;' : ''; ?>">
                                    <?php echo strtoupper(substr($tech['firstname'], 0, 1) . substr($tech['lastname'], 0, 1)); ?>
                                </div>
                                <div class="tech-info">
                                    <h4>
                                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                        <?php if (!$isActive): ?>
                                            <span class="inactive-badge">(Inactive)</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p>
                                        <i class="fas fa-ticket-alt"></i> <?php echo $ticketCount; ?> assigned | 
                                        <i class="fas fa-check-circle" style="color: var(--success);"></i> <?php echo $resolved; ?> resolved
                                    </p>
                                </div>
                            </div>
                            <button class="view-details-btn" onclick="event.stopPropagation(); viewTech(<?php echo $tech['technical_id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Right Main Content - Assigned Companies/Tickets -->
            <div class="tech-main" id="techMainContent">
                <?php if ($firstTech): 
                    // Get tickets for first technician
                    $ticketStmt = $pdo->prepare("
                        SELECT 
                            t.ticket_id,
                            t.concern_description,
                            t.status,
                            t.date_requested,
                            c.company_name,
                            c.contact_person,
                            c.contact_number,
                            c.email,
                            cn.concern_name as concern_type
                        FROM tickets t
                        LEFT JOIN clients c ON t.company_id = c.client_id
                        LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
                        WHERE t.technical_id = ?
                        ORDER BY t.created_at DESC
                    ");
                    $ticketStmt->execute([$firstTech['technical_id']]);
                    $tickets = $ticketStmt->fetchAll();
                    
                    // Get performance data - direct query
                    $perfStmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total_ticket,
                            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolve,
                            SUM(CASE WHEN status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END) as pending
                        FROM tickets 
                        WHERE technical_id = ?
                    ");
                    $perfStmt->execute([$firstTech['technical_id']]);
                    $perf = $perfStmt->fetch();
                    
                    $isActive = $firstTech['is_active'] ?? 1;
                    $totalTickets = $perf['total_ticket'] ?? 0;
                    $resolved = $perf['resolve'] ?? 0;
                    $pending = $perf['pending'] ?? 0;
                ?>
                <div class="tech-main-header">
                    <div class="tech-title-section">
                        <h2>
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($firstTech['firstname'] . ' ' . $firstTech['lastname']); ?>
                        </h2>
                        <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="tech-header-right">
                        <button class="btn btn-privileges" onclick="openPrivilegesModal(<?php echo $firstTech['technical_id']; ?>)">
                            <i class="fas fa-shield-alt"></i> User Privileges
                        </button>
                        <div class="status-toggle-container">
                            <span class="toggle-label">Status:</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="techStatusToggle" 
                                       <?php echo $isActive ? 'checked' : ''; ?> 
                                       onchange="toggleTechnicianStatus(<?php echo $firstTech['technical_id']; ?>, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Company Stats -->
                <div class="company-stats">
                    <div class="company-stat-card">
                        <div class="value"><?php echo count($tickets); ?></div>
                        <div class="label">Active Companies</div>
                    </div>
                    <div class="company-stat-card">
                        <div class="value"><?php echo $resolved; ?></div>
                        <div class="label">Resolved</div>
                    </div>
                    <div class="company-stat-card">
                        <div class="value"><?php echo $pending; ?></div>
                        <div class="label">Active Tickets</div>
                    </div>
                </div>

                <!-- Scrollable Company List Container -->
                <div class="company-list-container">
                    <div class="company-list" id="companyList">
                        <h3><i class="fas fa-building"></i> Assigned Companies/Tickets</h3>
                        <?php if (count($tickets) > 0): ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <div class="company-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="company-name">
                                    <?php echo htmlspecialchars($ticket['company_name'] ?? 'Unknown Company'); ?>
                                    <small>Ticket #<?php echo $ticket['ticket_id']; ?></small>
                                </div>
                                <div class="ticket-status-actions" style="display: flex; gap: 10px; align-items: center;">
                                    <?php 
                                        $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                                    ?>
                                    <span class="badge badge-<?php echo $statusClass; ?>" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 4px;">
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                    <button class="btn-icon warning" onclick="event.stopPropagation(); updateStatus(<?php echo $ticket['ticket_id']; ?>)" title="Update Status">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button class="btn-icon" onclick="event.stopPropagation(); viewTicket(<?php echo $ticket['ticket_id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-building"></i>
                                <h3>No Assigned Companies</h3>
                                <p>This technician currently has no tickets assigned.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Company Button -->
                <div class="add-company-btn" onclick="openAddTicketModal(<?php echo $firstTech['technical_id']; ?>)">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add new ticket for this technician</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Technical Staff Modal -->
    <div class="modal" id="viewTechModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Technical Staff Details</h2>
                <button class="modal-close" onclick="closeModal('viewTechModal')">&times;</button>
            </div>
            <div id="techDetails"></div>
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewTechModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Technical Staff Modal -->
    <div class="modal" id="editTechModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Technical Staff</h2>
                <button class="modal-close" onclick="closeModal('editTechModal')">&times;</button>
            </div>
            <form id="editTechForm" onsubmit="updateTechnical(event)">
                <input type="hidden" id="editTechId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="editFirstname" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="editLastname" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact/Viber</label>
                    <input type="text" class="form-control" id="editContact" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <select class="form-control" id="editBranch" required>
                            <option value="DAVAO">DAVAO</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <select class="form-control" id="editPosition" required>
                            <option value="Technical">Technical</option>
                            <option value="Sales">Sales</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editTechModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Technical Staff Modal -->
    <div class="modal" id="addTechModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Technical Staff</h2>
                <button class="modal-close" onclick="closeModal('addTechModal')">&times;</button>
            </div>
            <form id="addTechForm" onsubmit="addTechnical(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="techEmail" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact/Viber</label>
                    <input type="text" class="form-control" id="techContact" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <select class="form-control" id="branch" required>
                            <option value="DAVAO">DAVAO</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <select class="form-control" id="position" required>
                            <option value="Technical">Technical</option>
                            <option value="Sales">Sales</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('addTechModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Ticket Modal -->
    <div class="modal" id="addTicketModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle" style="color: var(--success);"></i> Add New Ticket</h2>
                <button class="modal-close" onclick="closeModal('addTicketModal')">&times;</button>
            </div>
            
            <div class="assigned-info" id="assignedTechInfo">
                <i class="fas fa-user-check"></i>
                <span>This ticket will be automatically assigned to the selected technician</span>
            </div>
            
            <form id="techTicketForm" class="tech-ticket-form" onsubmit="submitTechTicket(event)">
                <input type="hidden" id="techId" value="">
                
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
                            <i class="fas fa-exclamation-triangle"></i> Concern Type *
                        </label>
                        <select class="form-control" id="concern" required>
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
            <div id="ticketDetails" class="ticket-details-content"></div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
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
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Privileges Modal -->
    <div class="modal" id="privilegesModal">
        <div class="modal-content privileges-modal-content">
            <div class="modal-header privileges-modal-header">
                <div class="privileges-modal-title-wrap">
                    <div class="privileges-modal-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2>User Privileges</h2>
                        <p class="privileges-modal-subtitle" id="privilegesTechName">Loading...</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeModal('privilegesModal')">&times;</button>
            </div>

            <div class="privileges-modal-body">
                <p class="privileges-desc">
                    <i class="fas fa-info-circle"></i>
                    Control which pages this technician can access from their navigation menu.
                </p>

                <div class="privileges-list">
                    <label class="privilege-item">
                        <div class="privilege-info">
                            <div class="privilege-icon"><i class="fas fa-list"></i></div>
                            <div>
                                <div class="privilege-name">Tickets Page</div>
                                <div class="privilege-desc-text">View and manage assigned tickets</div>
                            </div>
                        </div>
                        <label class="priv-toggle-switch">
                            <input type="checkbox" id="priv_tickets" class="priv-checkbox" checked>
                            <span class="priv-toggle-slider"></span>
                        </label>
                    </label>

                    <label class="privilege-item">
                        <div class="privilege-info">
                            <div class="privilege-icon"><i class="fas fa-users-cog"></i></div>
                            <div>
                                <div class="privilege-name">Technical Staff Page</div>
                                <div class="privilege-desc-text">View the technical staff section</div>
                            </div>
                        </div>
                        <label class="priv-toggle-switch">
                            <input type="checkbox" id="priv_technical" class="priv-checkbox" checked>
                            <span class="priv-toggle-slider"></span>
                        </label>
                    </label>

                    <label class="privilege-item">
                        <div class="privilege-info">
                            <div class="privilege-icon"><i class="fas fa-chart-bar"></i></div>
                            <div>
                                <div class="privilege-name">Reports Page</div>
                                <div class="privilege-desc-text">Access analytics and reports</div>
                            </div>
                        </div>
                        <label class="priv-toggle-switch">
                            <input type="checkbox" id="priv_reports" class="priv-checkbox" checked>
                            <span class="priv-toggle-slider"></span>
                        </label>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('privilegesModal')">Cancel</button>
                <button class="btn btn-primary" onclick="savePrivileges()">
                    <i class="fas fa-save"></i> Save Privileges
                </button>
            </div>

            <input type="hidden" id="privilegesTechId" value="">
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="modal" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); display: none;">
        <div class="spinner"></div>
    </div>

    <script src="../script.js"></script>
  <script>
let currentTechId = <?php echo $firstTech['technical_id'] ?? 0; ?>;
// Removed request queuing variables

// Initialize - highlight first tech and run pre-flight check
document.addEventListener('DOMContentLoaded', async function() {
    const firstTech = document.querySelector('.tech-item');
    if (firstTech) {
        firstTech.classList.add('active');
    }
    
    // InfinityFree Pre-Flight Check
    // Silently fetch a lightweight endpoint to ensure the AES cookie is set 
    // before the user starts clicking around and triggering concurrent API requests.
    try {
        console.log("Running security pre-flight check...");
        // "Prime" the connection by fetching the assigned tickets for the initially selected technician
        const preflight = await fetch(`../get-tech-assignment.php?tech_id=${currentTechId || 0}`, {
            method: 'GET',
            headers: { 
                'Accept': 'application/json, text/plain, */*',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });
        
        const text = await preflight.text();
        const textTrimmed = text.trim().toLowerCase();
        
        let isJsonValid = false;
        try {
            JSON.parse(text);
            isJsonValid = true;
        } catch (e) {}
        
        if (!isJsonValid || textTrimmed === '' || textTrimmed.startsWith('<')) {
            console.log("Pre-flight caught AES challenge or invalid JSON. Triggering 3-second cooldown...");
            // Wait 3 seconds for browser to set the cookie before allowing user clicks to process
            await new Promise(resolve => setTimeout(resolve, 3000));
        } else {
            console.log("Pre-flight successful. API ready.");
        }
    } catch (e) {
        console.log("Pre-flight check failed (expected if blocked):", e);
    }
    
    // Session heartbeat to keep InfinityFree security ticket warm
    setInterval(() => {
        fetch('../heartbeat.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json, text/plain, */*' },
            credentials: 'include'
        }).then(res => res.text())
          .then(text => {
              if (text.startsWith('<')) {
                  console.log("Heartbeat encountered AES. Will be handled smoothly in background.");
              }
          })
          .catch(e => console.log('Heartbeat skipped'));
    }, 30000); // Every 30 seconds
});

function selectTech(techId, element) {
    // Execute immediately without queuing
    executeSelectTech(techId, element);
}

async function executeSelectTech(techId, element) {
    // Remove active class from all tech items
    document.querySelectorAll('.tech-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to selected item
    element.classList.add('active');
    
    // Update current tech ID
    currentTechId = techId;
    
    // Show loading
    showLoading();
    
    try {
        let attempts = 0;
        const maxAttempts = 3;
        let data = null;
        let requestSuccessful = false;

        while (attempts < maxAttempts && !requestSuccessful) {
            attempts++;
            try {
                // Request Bloating: add random string padding to avoid tiny bot probe detection
                const padding = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                // Added unique timestamp to bust InfinityFree active connection cache
                const response = await fetch(`../get-tech-assignment.php?tech_id=${techId}&pad=${padding}&v=${Date.now()}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json, text/plain, */*',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include' // Crucial for InfinityFree cookies
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                const textTrimmed = text.trim().toLowerCase();
                
                // Handle InfinityFree HTML response elegantly (includes empty strings, <script>, <html>, etc)
                if (textTrimmed === '' || textTrimmed.startsWith('<')) {
                    console.log(`[Attempt ${attempts}/${maxAttempts}] InfinityFree security challenge active or connection reset. Retrying...`);
                    
                    if (attempts >= maxAttempts) {
                        hideLoading();
                        showNotification('Security verification required. Please refresh the page manually.', 'warning');
                        return;
                    }
                    
                    console.log(`Waiting 2000ms before next attempt...`);
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    continue; // Loop again
                }
                
                // Normal JSON response
                try {
                    data = JSON.parse(text);
                    requestSuccessful = true;
                } catch (e) {
                    console.error("Invalid JSON:", text.substring(0, 100));
                    throw new Error("Received invalid data from server.");
                }
                
            } catch (fetchError) {
                if (attempts >= maxAttempts) {
                    throw fetchError; // Re-throw if out of attempts
                }
                console.log(`[Attempt ${attempts}/${maxAttempts}] Fetch failed, retrying...`, fetchError);
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
        }
        
        hideLoading();
        
        if (data && data.success === false) {
            showNotification(data.error || 'Error loading technician data', 'danger');
            return;
        }
        
        if (data) {
            updateMainContent(data);
        }
        
        if (attempts >= maxAttempts && !requestSuccessful) {
            console.log("Max retry attempts reached, stopping execution for this technician.");
        }
    } catch (error) {
        hideLoading();
        console.error('Error in selectTech:', error);
        showNotification('Error loading data: ' + error.message, 'danger');
    }
}

function updateMainContent(data) {
    const mainContent = document.getElementById('techMainContent');
    
    // Check if data and data.technician exist
    if (!data || !data.technician) {
        showNotification('Invalid data received', 'danger');
        return;
    }
    
    const isActive = data.technician.is_active ?? 1;
    const totalTickets = data.performance?.total_ticket || 0;
    const resolved = data.performance?.resolve || 0;
    const pending = data.performance?.pending || 0;
    
        let html = `
        <div class="tech-main-header">
            <div class="tech-title-section">
                <h2>
                    <i class="fas fa-user-circle"></i> 
                    ${escapeHtml(data.technician.firstname)} ${escapeHtml(data.technician.lastname)}
                </h2>
                <span class="status-badge ${isActive ? 'active' : 'inactive'}">
                    ${isActive ? 'Active' : 'Inactive'}
                </span>
            </div>
            <div class="tech-header-right">
                <button class="btn btn-privileges" onclick="openPrivilegesModal(${data.technician.technical_id})">
                    <i class="fas fa-shield-alt"></i> User Privileges
                </button>
                <div class="status-toggle-container">
                    <span class="toggle-label">Status:</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="techStatusToggle" 
                               ${isActive ? 'checked' : ''} 
                               onchange="toggleTechnicianStatus(${data.technician.technical_id}, this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="company-stats">
            <div class="company-stat-card">
                <div class="value">${data.tickets?.length || 0}</div>
                <div class="label">Active Companies</div>
            </div>
            <div class="company-stat-card">
                <div class="value">${resolved}</div>
                <div class="label">Resolved</div>
            </div>
            <div class="company-stat-card">
                <div class="value">${pending}</div>
                <div class="label">Active Tickets</div>
            </div>
        </div>

        <div class="company-list-container">
            <div class="company-list" id="companyList">
                <h3><i class="fas fa-building"></i> Assigned Companies/Tickets</h3>
    `;
    
    if (data.tickets && data.tickets.length > 0) {
        data.tickets.forEach(ticket => {
            html += `
                <div class="company-item" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="company-name">
                        ${escapeHtml(ticket.company_name || 'Unknown Company')}
                        <small>Ticket #${ticket.ticket_id}</small>
                    </div>
                    <div class="ticket-status-actions" style="display: flex; gap: 10px; align-items: center;">
                        <span class="badge badge-${(ticket.status || 'pending').toLowerCase().replace(' ', '')}" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 4px;">
                            ${ticket.status || 'Pending'}
                        </span>
                        <button class="btn-icon warning" onclick="event.stopPropagation(); updateStatus(${ticket.ticket_id})" title="Update Status">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="btn-icon" onclick="event.stopPropagation(); viewTicket(${ticket.ticket_id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No Assigned Companies</h3>
                <p>This technician currently has no tickets assigned.</p>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
        <div class="add-company-btn" onclick="openAddTicketModal(${data.technician.technical_id})">
            <i class="fas fa-plus-circle"></i>
            <span>Add new ticket for this technician</span>
        </div>
    `;
    
    mainContent.innerHTML = html;
}

async function viewTech(id) {
    showLoading();
    
    try {
        // Fetch tech details FIRST
        const techRes = await fetch(`../get-technical.php?id=${id}&_=${new Date().getTime()}`, { credentials: 'include' });
        if (!techRes.ok) throw new Error(`Server returned ${techRes.status}`);
        const techText = await techRes.text();
        
        if (techText.trim().startsWith('<') || techText.trim() === '') {
            throw new Error('Security challenge blocked the request. Please refresh the page.');
        }
        
        const techData = JSON.parse(techText);
        if (techData.success === false) throw new Error(techData.error || 'Technician not found');

        // Fetch performance SECOND (Prevents InfinityFree concurrent connection limits)
        const perfRes = await fetch(`../get-tech-performance-direct.php?id=${id}&_=${new Date().getTime()}`, { credentials: 'include' });
        if (!perfRes.ok) throw new Error(`Server returned ${perfRes.status}`);
        const perfText = await perfRes.text();
        
        let perfData = { total_ticket: 0, resolve: 0, pending: 0 };
        if (!perfText.trim().startsWith('<') && perfText.trim() !== '') {
            perfData = JSON.parse(perfText);
        }

        hideLoading();
        
        const totalTickets = perfData.total_ticket || 0;
        const resolved = perfData.resolve || 0;
        const pending = perfData.pending || 0;
        const isActive = techData.is_active ?? 1;
        
        const techDetailsHtml = `
            <div style="display: grid; gap: 15px;">
                <p><strong><i class="fas fa-id-badge"></i> ID:</strong> #${techData.technical_id}</p>
                <p><strong><i class="fas fa-user"></i> Name:</strong> ${escapeHtml(techData.firstname)} ${escapeHtml(techData.lastname)}</p>
                <p><strong><i class="fas fa-toggle-on"></i> Status:</strong> 
                    <span class="status-badge ${isActive ? 'active' : 'inactive'}" style="display: inline-block; margin-left: 5px; padding: 4px 8px;">
                        ${isActive ? 'Active' : 'Inactive'}
                    </span>
                </p>
                <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${escapeHtml(techData.email)}</p>
                <p><strong><i class="fas fa-phone"></i> Contact:</strong> ${escapeHtml(techData.contact_viber)}</p>
                <p><strong><i class="fas fa-map-marker-alt"></i> Branch:</strong> ${escapeHtml(techData.branch)}</p>
                <p><strong><i class="fas fa-briefcase"></i> Position:</strong> ${escapeHtml(techData.position)}</p>
                
                <div style="background: var(--gradient-1); padding: 15px; border-radius: 12px; color: white; margin: 10px 0;">
                    <h4 style="margin-bottom: 10px;">Ticket Summary</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700;">${totalTickets}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">Total Assigned</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #ffd700;">${resolved}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">Resolved</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700;">${pending}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('techDetails').innerHTML = techDetailsHtml;
        
        // Add Edit Button in the modal footer dynamically or inside the details
        // We'll replace the generic Close button with a flex container holding both Edit and Close
        const modalFooter = document.querySelector('#viewTechModal .flex.justify-end');
        if (modalFooter) {
            modalFooter.innerHTML = `
                <button class="btn btn-warning" onclick="editTech(${techData.technical_id})" style="margin-right: 10px;">
                    <i class="fas fa-edit"></i> Edit Info
                </button>
                <button class="btn btn-primary" onclick="closeModal('viewTechModal')">Close</button>
            `;
        }
        
        openModal('viewTechModal');

    } catch (error) {
        hideLoading();
        console.error('Error in viewTech:', error);
        showNotification('Error fetching details: ' + error.message, 'danger');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getObjectiveText(concernType) {
    if (!concernType) return 'General Support';
    if (concernType.includes('Install')) return 'Installation';
    if (concernType.includes('Train')) return 'Training';
    if (concernType.includes('Support')) return 'Support';
    return concernType;
}

function toggleTechnicianStatus(techId, isActive) {
    showLoading();
    
    fetch('../update-technician-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            technical_id: techId,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(`Technician ${isActive ? 'activated' : 'deactivated'} successfully`, 'success');
            
            const techItem = document.querySelector(`.tech-item[data-tech-id="${techId}"]`);
            if (techItem) {
                if (isActive) {
                    techItem.classList.remove('inactive');
                    const inactiveBadge = techItem.querySelector('.inactive-badge');
                    if (inactiveBadge) inactiveBadge.remove();
                    techItem.querySelector('.tech-avatar').style.opacity = '1';
                } else {
                    techItem.classList.add('inactive');
                    const nameElement = techItem.querySelector('h4');
                    if (nameElement && !nameElement.querySelector('.inactive-badge')) {
                        nameElement.innerHTML += ' <span class="inactive-badge">(Inactive)</span>';
                    }
                    techItem.querySelector('.tech-avatar').style.opacity = '0.6';
                }
            }
            
            const statusBadge = document.querySelector('.tech-main-header .status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge ${isActive ? 'active' : 'inactive'}`;
                statusBadge.textContent = isActive ? 'Active' : 'Inactive';
            }
        } else {
            showNotification('Error updating status: ' + data.message, 'danger');
            const toggle = document.getElementById('techStatusToggle');
            if (toggle) toggle.checked = !isActive;
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Error updating technician status', 'danger');
        const toggle = document.getElementById('techStatusToggle');
        if (toggle) toggle.checked = !isActive;
    });
}

function openAddTechModal() {
    document.getElementById('addTechModal').style.display = 'flex';
}

function openAddTicketModal(techId) {
    document.getElementById('techId').value = techId;
    
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('dateRequested').value = `${year}-${month}-${day}T${hours}:${minutes}`;
    
    document.getElementById('techTicketForm').reset();
    
    openModal('addTicketModal');
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function editTech(id) {
    fetch(`../get-technical.php?id=${id}`, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            document.getElementById('editTechId').value = data.technical_id;
            document.getElementById('editFirstname').value = data.firstname;
            document.getElementById('editLastname').value = data.lastname;
            document.getElementById('editEmail').value = data.email;
            document.getElementById('editContact').value = data.contact_viber;
            document.getElementById('editBranch').value = data.branch;
            document.getElementById('editPosition').value = data.position;
            openModal('editTechModal');
        });
}

function updateTechnical(event) {
    event.preventDefault();
    
    const formData = {
        technical_id: document.getElementById('editTechId').value,
        firstname: document.getElementById('editFirstname').value,
        lastname: document.getElementById('editLastname').value,
        email: document.getElementById('editEmail').value,
        contact: document.getElementById('editContact').value,
        branch: document.getElementById('editBranch').value,
        position: document.getElementById('editPosition').value
    };
    
    showLoading();
    
    fetch('../update-technical.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if(data.success) {
            showNotification('Technical staff updated successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + data.message, 'danger');
        }
    });
}

function addTechnical(event) {
    event.preventDefault();
    
    const formData = {
        firstname: document.getElementById('firstname').value,
        lastname: document.getElementById('lastname').value,
        email: document.getElementById('techEmail').value,
        contact: document.getElementById('techContact').value,
        branch: document.getElementById('branch').value,
        position: document.getElementById('position').value
    };
    
    showLoading();
    
    fetch('../add-technical.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if(data.success) {
            showNotification('Technical staff added successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + data.message, 'danger');
        }
    });
}

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
        credentials: 'include',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if(data.success) {
            showNotification('Ticket created and assigned successfully! 🎉', 'success');
            closeModal('addTicketModal');
            if (currentTechId) {
                const techElement = document.querySelector(`.tech-item[data-tech-id="${currentTechId}"]`);
                if (techElement) {
                    selectTech(currentTechId, techElement);
                }
            }
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
            
            document.getElementById('ticketDetails').innerHTML = `
                <div class="ticket-detail-grid" style="display: grid; gap: 20px;">
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
                    
                    <div class="detail-section full-width" style="grid-column: 1 / -1;">
                        <h4><i class="fas fa-align-left"></i> Description</h4>
                        <div class="description-box" style="background: var(--bg-hover); padding: 15px; border-radius: 8px;">${data.concern_description || 'No description provided'}</div>
                    </div>
                    
                    ${data.solution ? `
                    <div class="detail-section full-width" style="grid-column: 1 / -1;">
                        <h4><i class="fas fa-check-circle"></i> Solution / Remarks</h4>
                        <div class="description-box" style="background: var(--bg-hover); padding: 15px; border-radius: 8px;">${data.solution}</div>
                        ${data.finish_date ? `<p style="margin-top: 10px;"><strong>Date Finished:</strong> ${new Date(data.finish_date).toLocaleString()}</p>` : ''}
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
            if (currentTechId) {
                const techElement = document.querySelector(`.tech-item[data-tech-id="${currentTechId}"]`);
                if (techElement) {
                    selectTech(currentTechId, techElement);
                }
            }
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

function exportToExcel() {
    const companies = document.querySelectorAll('.company-item');
    const data = [['Company', 'Person In-Charge', 'Contact', 'Objectives', 'Status']];
    
    companies.forEach(company => {
        const row = [];
        const name = company.querySelector('.company-name')?.innerText?.split('\n')[0] || '';
        const person = company.querySelector('.person-in-charge')?.innerText || '';
        const contact = company.querySelector('.contact-status span:last-child')?.innerText || '';
        const objective = company.querySelector('.objectives')?.innerText.replace(/Active|Done/g, '').trim() || '';
        const status = company.querySelector('.status-badge-ticket')?.innerText || '';
        
        row.push(name);
        row.push(person);
        row.push(contact);
        row.push(objective);
        row.push(status);
        data.push(row);
    });
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(data);
    XLSX.utils.book_append_sheet(wb, ws, 'Companies');
    XLSX.writeFile(wb, `companies_${new Date().toISOString().slice(0,10)}.xlsx`);
    
    showNotification('Excel file downloaded successfully!', 'success');
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

// ── User Privileges Modal ─────────────────────────────────────────────────────
async function openPrivilegesModal(techId) {
    document.getElementById('privilegesTechId').value = techId;
    document.getElementById('privilegesTechName').textContent = 'Loading...';
    openModal('privilegesModal');

    try {
        const res  = await fetch(`../get-privileges.php?tech_id=${techId}`, { credentials: 'include' });
        const text = await res.text();
        if (text.trim().startsWith('<')) throw new Error('Server challenge');
        const data = JSON.parse(text);
        if (!data.success) throw new Error(data.message || 'Failed');

        const d = data.data;
        document.getElementById('privilegesTechName').textContent =
            `${escapeHtml(d.firstname)} ${escapeHtml(d.lastname)} — ${escapeHtml(d.position)}`;
        document.getElementById('priv_tickets').checked   = !!parseInt(d.can_view_tickets);
        document.getElementById('priv_technical').checked = !!parseInt(d.can_view_technical);
        document.getElementById('priv_reports').checked   = !!parseInt(d.can_view_reports);
    } catch (e) {
        showNotification('Error loading privileges: ' + e.message, 'danger');
        closeModal('privilegesModal');
    }
}

async function savePrivileges() {
    const techId = document.getElementById('privilegesTechId').value;
    if (!techId) return;

    const payload = {
        tech_id:            parseInt(techId),
        can_view_tickets:   document.getElementById('priv_tickets').checked   ? 1 : 0,
        can_view_technical: document.getElementById('priv_technical').checked ? 1 : 0,
        can_view_reports:   document.getElementById('priv_reports').checked   ? 1 : 0
    };

    try {
        const res  = await fetch('../update-privileges.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const text = await res.text();
        if (text.trim().startsWith('<')) throw new Error('Server challenge');
        const data = JSON.parse(text);
        if (!data.success) throw new Error(data.message || 'Failed');
        showNotification('Privileges saved successfully!', 'success');
        closeModal('privilegesModal');
    } catch (e) {
        showNotification('Error saving privileges: ' + e.message, 'danger');
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = ['viewTechModal', 'editTechModal', 'addTechModal', 'addTicketModal', 'viewTicketModal', 'statusModal', 'privilegesModal'];
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