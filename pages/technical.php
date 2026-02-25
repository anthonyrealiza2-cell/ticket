<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Staff - TicketFlow</title>
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
                <a href="tickets.php" class="nav-link"><i class="fas fa-list"></i> Tickets</a>
                <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="technical.php" class="nav-link active"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- Header -->
        <div class="flex justify-between" style="margin-bottom: 20px;">
            <h1 style="color: var(--text-primary);">Technical Staff Management</h1>
            <button class="btn btn-primary" onclick="openAddTechModal()">
                <i class="fas fa-plus-circle"></i> Add Technical Staff
            </button>
        </div>

        <!-- Performance Overview -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <?php
            // Get total staff
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM technical_staff");
            $totalStaff = $stmt->fetch()['total'];
            
            // Get total resolved tickets
            $stmt = $pdo->query("SELECT SUM(resolve) as total FROM technical_staff");
            $totalResolved = $stmt->fetch()['total'] ?? 0;
            
            // Get average performance
            $stmt = $pdo->query("SELECT AVG(resolve / NULLIF(total_ticket, 0) * 100) as avg_perf FROM technical_staff WHERE total_ticket > 0");
            $avgPerf = round($stmt->fetch()['avg_perf'] ?? 0, 1);
            
            // Get top performer
            $stmt = $pdo->query("SELECT CONCAT(firstname, ' ', lastname) as name, 
                                 (resolve / total_ticket * 100) as perf 
                                 FROM technical_staff 
                                 WHERE total_ticket > 0 
                                 ORDER BY perf DESC LIMIT 1");
            $topPerformer = $stmt->fetch();
            ?>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
                <div class="stat-info">
                    <h3>Total Staff</h3>
                    <div class="stat-number"><?php echo $totalStaff; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3>Total Resolved</h3>
                    <div class="stat-number"><?php echo $totalResolved; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3>Avg Performance</h3>
                    <div class="stat-number"><?php echo $avgPerf; ?>%</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-crown"></i></div>
                <div class="stat-info">
                    <h3>Top Performer</h3>
                    <div class="stat-number"><?php echo $topPerformer['name'] ?? 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Technical Staff Table (REMOVED CLOSED COLUMN) -->
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Branch</th>
                            <th>Position</th>
                            <th>Total Tickets</th>
                            <th>Resolved</th>
                            <th>Pending</th>
                            <th>Performance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT *, 
                                             (resolve / NULLIF(total_ticket, 0) * 100) as performance,
                                             (total_ticket - resolve) as pending
                                             FROM technical_staff 
                                             ORDER BY performance DESC");
                        while($tech = $stmt->fetch()) {
                            $performance = round($tech['performance'] ?? 0, 1);
                            $perfClass = $performance >= 80 ? 'success' : ($performance >= 50 ? 'warning' : 'danger');
                            $pending = $tech['total_ticket'] - $tech['resolve'];
                            
                            echo "<tr>";
                            echo "<td>#{$tech['technical_id']}</td>";
                            echo "<td><strong>{$tech['firstname']} {$tech['lastname']}</strong></td>";
                            echo "<td>{$tech['email']}</td>";
                            echo "<td>{$tech['contact_viber']}</td>";
                            echo "<td>{$tech['branch']}</td>";
                            echo "<td>{$tech['position']}</td>";
                            echo "<td><span class='badge badge-info'>{$tech['total_ticket']}</span></td>";
                            echo "<td><span class='badge badge-success'>{$tech['resolve']}</span></td>";
                            echo "<td><span class='badge badge-warning'>{$pending}</span></td>";
                            echo "<td>
                                    <div class='progress-bar' style='width: 100px;'>
                                        <div class='progress' style='width: {$performance}%; background: var(--{$perfClass});'>{$performance}%</div>
                                    </div>
                                  </td>";
                            echo "<td>
                                    <button class='btn btn-primary btn-sm' onclick='viewTech({$tech['technical_id']})' title='View Details'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <button class='btn btn-success btn-sm' onclick='editTech({$tech['technical_id']})' title='Edit'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='btn btn-info btn-sm' onclick='viewTechTickets({$tech['technical_id']})' title='View Tickets'>
                                        <i class='fas fa-ticket-alt'></i>
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

    <script src="../script.js"></script>
    <script>
    function openAddTechModal() {
        document.getElementById('addTechModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // View technical staff details
    function viewTech(id) {
        fetch(`../get-technical.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                const details = document.getElementById('techDetails');
                const performance = data.total_ticket > 0 ? ((data.resolve / data.total_ticket) * 100).toFixed(1) : 0;
                const pending = data.total_ticket - data.resolve;
                
                details.innerHTML = `
                    <div style="display: grid; gap: 15px;">
                        <p><strong><i class="fas fa-id-badge"></i> ID:</strong> #${data.technical_id}</p>
                        <p><strong><i class="fas fa-user"></i> Name:</strong> ${data.firstname} ${data.lastname}</p>
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${data.email}</p>
                        <p><strong><i class="fas fa-phone"></i> Contact:</strong> ${data.contact_viber}</p>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Branch:</strong> ${data.branch}</p>
                        <p><strong><i class="fas fa-briefcase"></i> Position:</strong> ${data.position}</p>
                        <p><strong><i class="fas fa-ticket-alt"></i> Total Tickets:</strong> ${data.total_ticket}</p>
                        <p><strong><i class="fas fa-check-circle"></i> Resolved:</strong> ${data.resolve}</p>
                        <p><strong><i class="fas fa-clock"></i> Pending:</strong> ${pending}</p>
                        <p><strong><i class="fas fa-chart-line"></i> Performance:</strong> 
                            <div class='progress-bar' style='width: 100%; margin-top: 5px;'>
                                <div class='progress' style='width: ${performance}%;'>${performance}%</div>
                            </div>
                        </p>
                    </div>
                `;
                openModal('viewTechModal');
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

    function viewTechTickets(id) {
        window.location.href = `tickets.php?tech_id=${id}`;
    }

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function showLoading() {
        const spinner = document.createElement('div');
        spinner.id = 'loadingSpinner';
        spinner.className = 'modal';
        spinner.style.background = 'transparent';
        spinner.innerHTML = '<div class="spinner"></div>';
        spinner.style.display = 'flex';
        document.body.appendChild(spinner);
    }

    function hideLoading() {
        const spinner = document.getElementById('loadingSpinner');
        if(spinner) spinner.remove();
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
    </script>
</body>
</html>