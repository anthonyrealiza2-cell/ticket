<?php
require_once '../auth_check.php';
require_once '../database.php';

// Enable error logging but not display
ini_set('display_errors', 0);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="../css/client.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="clients-page">
    <div class="container">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Header -->
        <div class="flex justify-between page-header" style="margin-bottom: 20px;">
            <h1>Client Management</h1>
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-success" onclick="exportToExcel()" style="background: var(--gradient-2);">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="table-container">
                <table id="clientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Total Tickets</th>
                            <th>Open Tickets</th>
                            <th>Resolved</th>
                            <th>Last Ticket</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Use direct query instead of view to avoid permission issues
                            $stmt = $pdo->query("
                                SELECT 
                                    c.client_id,
                                    c.company_name,
                                    c.contact_person,
                                    c.contact_number,
                                    c.email,
                                    c.created_at,
                                    COUNT(t.ticket_id) as total_tickets,
                                    SUM(CASE WHEN t.status IN ('Pending', 'In Progress', 'Assigned') THEN 1 ELSE 0 END) as open_tickets,
                                    SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                                    MAX(t.date_requested) as last_ticket_date
                                FROM clients c
                                LEFT JOIN tickets t ON c.client_id = t.company_id
                                GROUP BY c.client_id, c.company_name, c.contact_person, c.contact_number, c.email, c.created_at
                                ORDER BY last_ticket_date DESC
                            ");
                            
                            while($client = $stmt->fetch()) {
                                $totalTickets = $client['total_tickets'] ?? 0;
                                $openTickets = $client['open_tickets'] ?? 0;
                                $resolvedTickets = $client['resolved_tickets'] ?? 0;
                                $lastTicketDate = $client['last_ticket_date'] ? date('M d, Y', strtotime($client['last_ticket_date'])) : 'No tickets';
                                
                                echo "<tr>";
                                echo "<td>#{$client['client_id']}</td>";
                                echo "<td><strong>" . htmlspecialchars($client['company_name']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($client['contact_person'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($client['contact_number'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($client['email'] ?? 'N/A') . "</td>";
                                echo "<td><span class='badge badge-info'>{$totalTickets}</span></td>";
                                echo "<td><span class='badge badge-warning'>{$openTickets}</span></td>";
                                echo "<td><span class='badge badge-success'>{$resolvedTickets}</span></td>";
                                echo "<td>{$lastTicketDate}</td>";
                                echo "<td>
                                        <button class='btn btn-primary btn-sm' onclick='viewClientTickets({$client['client_id']})' title='View Tickets'>
                                            <i class='fas fa-ticket-alt'></i>
                                        </button>
                                        <button class='btn btn-info btn-sm' onclick='viewClientDetails({$client['client_id']})' title='View Details'>
                                            <i class='fas fa-eye'></i>
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } catch (PDOException $e) {
                            // Log error but show user-friendly message
                            error_log("Database error in clients.php: " . $e->getMessage());
                            echo "<tr><td colspan='10' style='text-align: center; color: var(--danger); padding: 40px;'>
                                    <i class='fas fa-exclamation-triangle' style='font-size: 2rem; margin-bottom: 10px;'></i>
                                    <h3>Error loading clients</h3>
                                    <p>Please try again later or contact support.</p>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Client Modal (Hidden by default) -->
    <div class="modal" id="addClientModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client</h2>
                <button class="modal-close" onclick="closeModal('addClientModal')">&times;</button>
            </div>
            <form id="addClientForm" onsubmit="addClient(event)">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="companyName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contactPerson" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contactNumber" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" required>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="closeModal('addClientModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Client Details Modal -->
    <div class="modal" id="viewClientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Client Details</h2>
                <button class="modal-close" onclick="closeModal('viewClientModal')">&times;</button>
            </div>
            <div id="clientDetails"></div>
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewClientModal')">Close</button>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
    <script>
    function openAddClientModal() {
        document.getElementById('addClientModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function addClient(event) {
        event.preventDefault();
        
        const formData = {
            company_name: document.getElementById('companyName').value,
            contact_person: document.getElementById('contactPerson').value,
            contact_number: document.getElementById('contactNumber').value,
            email: document.getElementById('email').value
        };
        
        showLoading();
        
        fetch('../add-client.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if(data.success) {
                showNotification('Client added successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('Error adding client', 'danger');
        });
    }

    function viewClientTickets(clientId) {
        window.location.href = `tickets.php?client_id=${clientId}`;
    }

    function viewClientDetails(clientId) {
        showLoading();
        
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        fetch(`../get-client.php?id=${clientId}&_=${timestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                // Check for HTML response (InfinityFree protection)
                if (text.trim().startsWith('<')) {
                    throw new Error('Server returned HTML. Please try again.');
                }
                return JSON.parse(text);
            })
            .then(data => {
                hideLoading();
                if(data.success === false || data.error) {
                    throw new Error(data.error || 'Unknown error occurred');
                }
                const details = document.getElementById('clientDetails');
                details.innerHTML = `
                    <div style="display: grid; gap: 15px;">
                        <p><strong><i class="fas fa-id-badge"></i> Client ID:</strong> #${data.client_id}</p>
                        <p><strong><i class="fas fa-building"></i> Company Name:</strong> ${escapeHtml(data.company_name)}</p>
                        <p><strong><i class="fas fa-user"></i> Contact Person:</strong> ${escapeHtml(data.contact_person)}</p>
                        <p><strong><i class="fas fa-phone"></i> Contact Number:</strong> ${escapeHtml(data.contact_number)}</p>
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> ${escapeHtml(data.email)}</p>
                        <p><strong><i class="fas fa-calendar"></i> Joined Date:</strong> ${data.created_at ? new Date(data.created_at).toLocaleDateString() : 'N/A'}</p>
                    </div>
                `;
                openModal('viewClientModal');
            })
            .catch(error => {
                hideLoading();
                console.error('Error fetching client details:', error);
                showNotification('Error loading client details: ' + error.message, 'danger');
            });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function exportToExcel() {
        // Get table data
        const table = document.getElementById('clientsTable');
        const rows = table.querySelectorAll('tr');
        
        // Prepare data array for Excel
        const data = [];
        
        // Get headers (excluding Actions column)
        const headers = [];
        const headerCells = rows[0].querySelectorAll('th');
        for (let i = 0; i < headerCells.length - 1; i++) {
            headers.push(headerCells[i].innerText);
        }
        data.push(headers);
        
        // Get rows data (excluding Actions column)
        for (let i = 1; i < rows.length; i++) {
            const row = [];
            const cells = rows[i].querySelectorAll('td');
            
            // Process each cell except the last one (Actions)
            for (let j = 0; j < cells.length - 1; j++) {
                let cellValue = cells[j].innerText.trim();
                
                // Remove # symbol from ID
                if (j === 0) {
                    cellValue = cellValue.replace('#', '');
                }
                // Remove any HTML tags from badge values
                else if (j >= 5 && j <= 7) {
                    // Extract just the number from badge spans
                    const badgeMatch = cellValue.match(/\d+/);
                    if (badgeMatch) {
                        cellValue = badgeMatch[0];
                    }
                }
                
                row.push(cellValue);
            }
            data.push(row);
        }
        
        // Create worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(data);
        
        // Set column widths for better readability
        const colWidths = [
            { wch: 10 }, // ID
            { wch: 30 }, // Company Name
            { wch: 25 }, // Contact Person
            { wch: 20 }, // Contact Number
            { wch: 35 }, // Email
            { wch: 15 }, // Total Tickets
            { wch: 15 }, // Open Tickets
            { wch: 15 }, // Resolved
            { wch: 20 }  // Last Ticket
        ];
        ws['!cols'] = colWidths;
        
        XLSX.utils.book_append_sheet(wb, ws, 'Clients');
        XLSX.writeFile(wb, `clients_export_${new Date().toISOString().slice(0,10)}.xlsx`);
        
        showNotification('Excel file downloaded successfully!', 'success');
    }

    function showLoading() {
        const spinner = document.createElement('div');
        spinner.id = 'loadingSpinner';
        spinner.className = 'modal';
        spinner.style.background = 'rgba(0,0,0,0.5)';
        spinner.style.backdropFilter = 'blur(3px)';
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

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    </script>
</body>
</html>