<?php
require_once '../database.php';
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
    <style>
        /* New layout styles */
        .technical-dashboard {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            margin-top: 25px;
        }

        /* Sidebar Styles */
        .tech-sidebar {
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            height: fit-content;
        }

        .tech-sidebar-header {
            background: var(--gradient-1);
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .tech-sidebar-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .tech-sidebar-header h2 {
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-sidebar-header p {
            position: relative;
            z-index: 1;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .tech-sidebar-header p i {
            margin-right: 5px;
        }

        .tech-list {
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
        }

        .tech-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin-bottom: 5px;
        }

        .tech-item:hover {
            background: var(--bg-hover);
            transform: translateX(5px);
        }

        .tech-item.active {
            background: var(--bg-hover);
            border-left: 4px solid var(--accent-primary);
        }

        .tech-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tech-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .tech-info h4 {
            color: var(--text-primary);
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .tech-info p {
            color: var(--text-muted);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tech-info p i {
            font-size: 0.7rem;
        }

        .tech-rating {
            display: flex;
            align-items: center;
            gap: 2px;
            color: #ffd700;
        }

        .tech-rating i {
            font-size: 0.8rem;
        }

        .tech-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ecc71;
        }

        .view-details-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        /* Main Content Styles */
        .tech-main {
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            padding: 25px;
        }

        .tech-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .tech-main-header h2 {
            color: var(--text-primary);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-main-header h2 i {
            color: var(--accent-primary);
        }

        .company-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .company-stat-card {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .company-stat-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-primary);
            margin-bottom: 5px;
        }

        .company-stat-card .label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .company-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .company-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 2fr;
            align-items: center;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .company-item:hover {
            transform: translateX(5px);
            border-color: var(--accent-primary);
        }

        .company-name {
            color: var(--text-primary);
            font-weight: 500;
        }

        .person-in-charge {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-secondary);
        }

        .person-in-charge .stars {
            color: #ffd700;
            margin-left: 5px;
        }

        .contact-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-status .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ecc71;
        }

        .contact-status .status-dot.offline {
            background: #95a5a6;
        }

        .contact-status span {
            color: var(--text-secondary);
        }

        .objectives {
            color: var(--text-primary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .objectives i {
            color: var(--warning);
            font-size: 0.8rem;
        }

        .add-company-btn {
            margin-top: 20px;
            padding: 15px;
            background: var(--bg-secondary);
            border: 2px dashed var(--border-color);
            border-radius: 16px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-company-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-secondary);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1200px) {
            .technical-dashboard {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .company-item {
                grid-template-columns: 1fr;
                gap: 10px;
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

        <!-- Performance Overview Cards (Keep these) -->
        <div class="technical-stats-grid">
            <?php
            // Get total staff
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM technical_staff");
            $totalStaff = $stmt->fetch()['total'];
            
            // Get total resolved tickets from view
            $stmt = $pdo->query("SELECT SUM(resolve) as total FROM vw_tech_performance");
            $totalResolved = $stmt->fetch()['total'] ?? 0;
            
            // Get average performance from view
            $stmt = $pdo->query("SELECT AVG(performance_rate) as avg_perf FROM vw_tech_performance WHERE total_ticket > 0");
            $avgPerf = round($stmt->fetch()['avg_perf'] ?? 0, 1);
            
            // Get top performer from view
            $stmt = $pdo->query("SELECT full_name as name FROM vw_tech_performance WHERE total_ticket > 0 ORDER BY performance_rate DESC LIMIT 1");
            $topPerformer = $stmt->fetch();
            ?>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-users-cog"></i></div>
                <div class="technical-stat-info">
                    <h3>Total Staff</h3>
                    <div class="technical-stat-number"><?php echo $totalStaff; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="technical-stat-info">
                    <h3>Total Resolved</h3>
                    <div class="technical-stat-number"><?php echo $totalResolved; ?></div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="technical-stat-info">
                    <h3>Avg Performance</h3>
                    <div class="technical-stat-number"><?php echo $avgPerf; ?>%</div>
                </div>
            </div>
            
            <div class="technical-stat-card">
                <div class="technical-stat-icon"><i class="fas fa-crown"></i></div>
                <div class="technical-stat-info">
                    <h3>Top Performer</h3>
                    <div class="technical-stat-number"><?php echo $topPerformer['name'] ?? 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- New Layout: Sidebar + Main Content -->
        <div class="technical-dashboard">
            <!-- Left Sidebar - List of Technicians -->
            <div class="tech-sidebar">
                <div class="tech-sidebar-header">
                    <h2><i class="fas fa-users-cog"></i> Technical Staff</h2>
                    <p><i class="fas fa-user-check"></i> <?php echo $totalStaff; ?> active members</p>
                </div>
                
                <div class="tech-list" id="techList">
                    <?php
                    $techStmt = $pdo->query("
                        SELECT ts.*, 
                               v.total_ticket, 
                               v.resolve, 
                               v.pending, 
                               v.performance_rate,
                               COALESCE(v.total_ticket, 0) as ticket_count
                        FROM technical_staff ts
                        LEFT JOIN vw_tech_performance v ON ts.technical_id = v.technical_id
                        ORDER BY v.performance_rate DESC
                    ");
                    
                    $firstTech = null;
                    while($tech = $techStmt->fetch()) {
                        if (!$firstTech) $firstTech = $tech;
                        
                        // Calculate rating stars based on performance
                        $rating = floor(($tech['performance_rate'] ?? 0) / 20); // 0-5 stars
                        ?>
                        <div class="tech-item" onclick="selectTech(<?php echo $tech['technical_id']; ?>, this)" data-tech-id="<?php echo $tech['technical_id']; ?>">
                            <div class="tech-item-left">
                                <div class="tech-avatar">
                                    <?php echo strtoupper(substr($tech['firstname'], 0, 1) . substr($tech['lastname'], 0, 1)); ?>
                                </div>
                                <div class="tech-info">
                                    <h4><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></h4>
                                    <p>
                                        <span><i class="fas fa-ticket-alt"></i> <?php echo $tech['ticket_count']; ?> tickets</span>
                                        <span class="tech-rating">
                                            <?php for($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i < $rating ? '' : '-o'; ?>" style="color: <?php echo $i < $rating ? '#ffd700' : 'var(--text-muted)'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
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
                            t.priority,
                            t.status,
                            t.date_requested,
                            c.company_name,
                            c.contact_person,
                            c.contact_number,
                            c.email,
                            cn.concern_name as concern_type
                        FROM tickets t
                        JOIN clients c ON t.company_id = c.client_id
                        LEFT JOIN concerns cn ON t.concern_id = cn.concern_id
                        WHERE t.technical_id = ?
                        ORDER BY t.created_at DESC
                    ");
                    $ticketStmt->execute([$firstTech['technical_id']]);
                    $tickets = $ticketStmt->fetchAll();
                    
                    // Get performance data
                    $perfStmt = $pdo->prepare("SELECT * FROM vw_tech_performance WHERE technical_id = ?");
                    $perfStmt->execute([$firstTech['technical_id']]);
                    $perf = $perfStmt->fetch();
                ?>
                <div class="tech-main-header">
                    <h2>
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($firstTech['firstname'] . ' ' . $firstTech['lastname']); ?>
                    </h2>
                    <div class="tech-main-stats">
                        <span class="badge badge-info">Performance: <?php echo round($perf['performance_rate'] ?? 0, 1); ?>%</span>
                    </div>
                </div>

                <!-- Company Stats -->
                <div class="company-stats">
                    <div class="company-stat-card">
                        <div class="value"><?php echo count($tickets); ?></div>
                        <div class="label">Total Companies</div>
                    </div>
                    <div class="company-stat-card">
                        <div class="value"><?php echo $perf['resolve'] ?? 0; ?></div>
                        <div class="label">Resolved</div>
                    </div>
                    <div class="company-stat-card">
                        <div class="value"><?php echo $perf['pending'] ?? 0; ?></div>
                        <div class="label">Active</div>
                    </div>
                </div>

                <!-- Company List -->
                <div class="company-list" id="companyList">
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach ($tickets as $ticket): 
                            // Calculate rating based on priority/status
                            $rating = $ticket['priority'] == 'High' ? 4 : ($ticket['priority'] == 'Medium' ? 3 : 2);
                        ?>
                        <div class="company-item">
                            <div class="company-name">
                                <?php echo htmlspecialchars($ticket['company_name']); ?>
                            </div>
                            <div class="person-in-charge">
                                <?php echo htmlspecialchars($ticket['contact_person']); ?>
                                <span class="stars">
                                    <?php for($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i < $rating ? '' : '-o'; ?>" style="color: <?php echo $i < $rating ? '#ffd700' : 'var(--text-muted)'; ?>; font-size: 0.7rem;"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <div class="contact-status">
                                <span class="status-dot <?php echo $ticket['status'] == 'Resolved' ? 'offline' : ''; ?>"></span>
                                <span><?php echo $ticket['contact_number'] ?? 'No contact'; ?></span>
                            </div>
                            <div class="objectives">
                                <i class="fas fa-tasks"></i>
                                <?php 
                                $objective = $ticket['concern_type'] ?? 'General Support';
                                if (strpos($objective, 'Install') !== false) {
                                    echo 'Installation';
                                } elseif (strpos($objective, 'Train') !== false) {
                                    echo 'Training';
                                } elseif (strpos($objective, 'Support') !== false) {
                                    echo 'Support';
                                } else {
                                    echo $objective;
                                }
                                ?>
                                <?php if ($ticket['status'] == 'In Progress'): ?>
                                    <span class="badge badge-warning" style="margin-left: 8px;">Active</span>
                                <?php endif; ?>
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
                        <input type="text" class="form-control" id="editBranch" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" id="editPosition" required>
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
                        <input type="text" class="form-control" id="branch" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" required>
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

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="modal" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); display: none;">
        <div class="spinner"></div>
    </div>

    <script>
    let currentTechId = <?php echo $firstTech['technical_id'] ?? 0; ?>;

    // Initialize - highlight first tech
    document.addEventListener('DOMContentLoaded', function() {
        const firstTech = document.querySelector('.tech-item');
        if (firstTech) {
            firstTech.classList.add('active');
        }
    });

   function selectTech(techId, element) {
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
    
    // Fetch technician's data and update main content
    // CHANGE THIS LINE - remove the extra 's'
    fetch(`../get-tech-assignment.php?tech_id=${techId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            updateMainContent(data);
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error loading technician data', 'danger');
        });
}

    function updateMainContent(data) {
        const mainContent = document.getElementById('techMainContent');
        
        let html = `
            <div class="tech-main-header">
                <h2>
                    <i class="fas fa-user-circle"></i> 
                    ${data.technician.firstname} ${data.technician.lastname}
                </h2>
                <div class="tech-main-stats">
                    <span class="badge badge-info">Performance: ${data.performance.performance_rate || 0}%</span>
                </div>
            </div>

            <div class="company-stats">
                <div class="company-stat-card">
                    <div class="value">${data.tickets.length}</div>
                    <div class="label">Total Companies</div>
                </div>
                <div class="company-stat-card">
                    <div class="value">${data.performance.resolve || 0}</div>
                    <div class="label">Resolved</div>
                </div>
                <div class="company-stat-card">
                    <div class="value">${data.performance.pending || 0}</div>
                    <div class="label">Active</div>
                </div>
            </div>

            <div class="company-list" id="companyList">
        `;
        
        if (data.tickets.length > 0) {
            data.tickets.forEach(ticket => {
                const rating = ticket.priority == 'High' ? 4 : (ticket.priority == 'Medium' ? 3 : 2);
                html += `
                    <div class="company-item">
                        <div class="company-name">
                            ${escapeHtml(ticket.company_name)}
                        </div>
                        <div class="person-in-charge">
                            ${escapeHtml(ticket.contact_person)}
                            <span class="stars">
                                ${generateStars(rating)}
                            </span>
                        </div>
                        <div class="contact-status">
                            <span class="status-dot ${ticket.status == 'Resolved' ? 'offline' : ''}"></span>
                            <span>${ticket.contact_number || 'No contact'}</span>
                        </div>
                        <div class="objectives">
                            <i class="fas fa-tasks"></i>
                            ${getObjectiveText(ticket.concern_type)}
                            ${ticket.status == 'In Progress' ? '<span class="badge badge-warning" style="margin-left: 8px;">Active</span>' : ''}
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
            <div class="add-company-btn" onclick="openAddTicketModal(${data.technician.technical_id})">
                <i class="fas fa-plus-circle"></i>
                <span>Add new ticket for this technician</span>
            </div>
        `;
        
        mainContent.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function generateStars(rating) {
        let stars = '';
        for (let i = 0; i < 5; i++) {
            stars += `<i class="fas fa-star${i < rating ? '' : '-o'}" style="color: ${i < rating ? '#ffd700' : 'var(--text-muted)'}; font-size: 0.7rem;"></i>`;
        }
        return stars;
    }

    function getObjectiveText(concernType) {
        if (!concernType) return 'General Support';
        if (concernType.includes('Install')) return 'Installation';
        if (concernType.includes('Train')) return 'Training';
        if (concernType.includes('Support')) return 'Support';
        return concernType;
    }

    function openAddTechModal() {
        document.getElementById('addTechModal').style.display = 'flex';
    }

    function openAddTicketModal(techId) {
        document.getElementById('techId').value = techId;
        
        // Set default date
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('dateRequested').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        
        // Reset form
        document.getElementById('techTicketForm').reset();
        
        openModal('addTicketModal');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    // View technical staff details
    // View technical staff details
function viewTech(id) {
    fetch(`../get-technical.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            const details = document.getElementById('techDetails');
            
            // Get performance from view
            fetch(`../get-tech-performance.php?id=${id}`)
                .then(perfResponse => perfResponse.json())
                .then(perf => {
                    const performance = perf.performance_rate || 0;
                    const pending = perf.pending || 0;
                    
                    details.innerHTML = `
                        <div style="display: grid; gap: 15px;">
                            <p><strong><i class="fas fa-id-badge"></i> ID:</strong> #${data.technical_id}</p>
                            <p><strong><i class="fas fa-user"></i> Name:</strong> ${data.firstname} ${data.lastname}</p>
                            <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${data.email}</p>
                            <p><strong><i class="fas fa-phone"></i> Contact:</strong> ${data.contact_viber}</p>
                            <p><strong><i class="fas fa-map-marker-alt"></i> Branch:</strong> ${data.branch}</p>
                            <p><strong><i class="fas fa-briefcase"></i> Position:</strong> ${data.position}</p>
                            <p><strong><i class="fas fa-ticket-alt"></i> Total Tickets:</strong> ${perf.total_ticket || 0}</p>
                            <p><strong><i class="fas fa-check-circle"></i> Resolved:</strong> ${perf.resolve || 0}</p>
                            <p><strong><i class="fas fa-clock"></i> Pending:</strong> ${pending}</p>
                            <p><strong><i class="fas fa-chart-line"></i> Performance:</strong> 
                                <div class='progress-bar' style='width: 100%; margin-top: 5px;'>
                                    <div class='progress' style='width: ${performance}%;'>${performance}%</div>
                                </div>
                            </p>
                        </div>
                    `;
                    
                    openModal('viewTechModal');
                })
                .catch(() => {
                    details.innerHTML = `
                        <div style="display: grid; gap: 15px;">
                            <p><strong><i class="fas fa-id-badge"></i> ID:</strong> #${data.technical_id}</p>
                            <p><strong><i class="fas fa-user"></i> Name:</strong> ${data.firstname} ${data.lastname}</p>
                            <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${data.email}</p>
                            <p><strong><i class="fas fa-phone"></i> Contact:</strong> ${data.contact_viber}</p>
                            <p><strong><i class="fas fa-map-marker-alt"></i> Branch:</strong> ${data.branch}</p>
                            <p><strong><i class="fas fa-briefcase"></i> Position:</strong> ${data.position}</p>
                        </div>
                    `;
                    openModal('viewTechModal');
                });
        });
}

    // Edit technical staff
    function editTech(id) {
        fetch(`../get-technical.php?id=${id}`)
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

    // Update technical staff
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
                closeModal('addTicketModal');
                // Refresh the current technician's view
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

    function exportToExcel() {
        // Get current technician's data
        const companies = document.querySelectorAll('.company-item');
        const data = [['Company', 'Person In-Charge', 'Contact', 'Objectives']];
        
        companies.forEach(company => {
            const cells = company.querySelectorAll('div');
            const row = [];
            cells.forEach(cell => row.push(cell.innerText));
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

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['viewTechModal', 'editTechModal', 'addTechModal', 'addTicketModal'];
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