<?php
require_once '../auth_check.php';
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Tickets - TicketFlow</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/tickets.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-archive"></i> Archived Tickets</h1>
            <div class="header-actions">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search archived tickets...">
                </div>
                
                <!-- Bulk Actions Bar (Fixed Top Navigation) -->
                <div class="bulk-actions-bar" id="bulkActions">
                    <div class="bulk-bar-content">
                        <div class="bulk-bar-left">
                            <span class="bulk-count">
                                <i class="fas fa-layer-group"></i> 
                                <span id="selectedCount">0</span> tickets selected
                            </span>
                        </div>
                        <div class="bulk-bar-right">
                            <button class="btn btn-success btn-sm" onclick="openBulkRestoreModal()">
                                <i class="fas fa-trash-restore"></i> Restore Selected
                            </button>
                            <button class="btn-icon bulk-close-btn" onclick="clearSelectionArchive()" title="Close Actions">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <a href="tickets.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
            </div>
        </div>

        <!-- Archived Tickets List -->
        <div class="tickets-container">
            <div class="company-group">
                <div class="company-group-header expanded" style="cursor: default;">
                    <div class="company-header-content">
                        <div class="company-info-main">
                            <div class="company-avatar" style="background: var(--warning);">
                                <i class="fas fa-archive"></i>
                            </div>
                            <div class="company-details">
                                <h3 class="company-name">Archived Tickets</h3>
                                <div class="company-meta">
                                    <span><i class="fas fa-info-circle"></i> View and restore deleted tickets</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="company-tickets" style="display: block;">
                    <div class="table-responsive">
                        <table class="tickets-table" id="archiveTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAllCheckbox" class="ticket-checkbox" onchange="toggleSelectAll(this)">
                                    </th>
                                    <th style="width: 80px;">ID</th>
                                    <th>Concern / Company</th>
                                    <th style="width: 120px;">Priority</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 150px;">Archived Date</th>
                                    <th>Reason</th>
                                    <th style="width: 120px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT a.*, 
                                           c.company_name, 
                                           c.contact_person,
                                           CONCAT(ts.firstname, ' ', ts.lastname) as tech_name
                                    FROM tickets_archive a
                                    LEFT JOIN clients c ON a.company_id = c.client_id
                                    LEFT JOIN technical_staff ts ON a.technical_id = ts.technical_id
                                    ORDER BY a.archived_at DESC
                                ");
                                
                                if ($stmt->rowCount() == 0):
                                ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state" style="border: none; background: transparent; padding: 40px 20px;">
                                                <i class="fas fa-box-open"></i>
                                                <h3>No archived tickets</h3>
                                                <p>Your archive is currently empty.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                else:
                                    while ($ticket = $stmt->fetch()):
                                        $priorityClass = strtolower($ticket['priority'] ?? 'medium');
                                        $statusClass = strtolower(str_replace(' ', '', $ticket['status'] ?? 'pending'));
                                        
                                        $archivedDate = new DateTime($ticket['archived_at']);
                                        $now = new DateTime();
                                        $interval = $archivedDate->diff($now);
                                        $timeAgo = $interval->days == 0 ? 'Today' : ($interval->days == 1 ? 'Yesterday' : $interval->days . ' days ago');
                                ?>
                                    <tr class="ticket-row" data-status="<?= $ticket['status'] ?>" data-ticket-id="<?= $ticket['ticket_id'] ?>" data-priority="<?= $ticket['priority'] ?>">
                                        <td class="table-checkbox">
                                            <input type="checkbox" class="ticket-checkbox" value="<?= $ticket['ticket_id'] ?>" onchange="updateBulkActions()">
                                        </td>
                                        
                                        <td class="ticket-id">#<?= $ticket['ticket_id'] ?></td>
                                        
                                        <td class="ticket-concern-cell">
                                            <div class="concern-text" title="<?= htmlspecialchars($ticket['concern_description'] ?? '') ?>">
                                                <?= htmlspecialchars(substr($ticket['concern_description'] ?? 'No description', 0, 40)) ?>...
                                            </div>
                                            <div class="ticket-contact">
                                                <i class="fas fa-building"></i> <?= htmlspecialchars($ticket['company_name']) ?>
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
                                            <div class="date-main"><?= date('M d, Y', strtotime($ticket['archived_at'])) ?></div>
                                            <div class="time-ago"><?= $timeAgo ?></div>
                                        </td>
                                        
                                        <td>
                                            <div style="font-size: 0.9rem; color: var(--text-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($ticket['archive_reason'] ?? 'N/A') ?>">
                                                <i class="fas fa-comment-dots"></i> <?= htmlspecialchars($ticket['archive_reason'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-icon" onclick="viewArchivedTicket(<?= $ticket['ticket_id'] ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon" onclick="restoreTicket(<?= $ticket['ticket_id'] ?>)" title="Restore Ticket" style="color: var(--success); border-color: rgba(0, 184, 148, 0.3);">
                                                    <i class="fas fa-trash-restore"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Archived Ticket Modal -->
    <div class="modal" id="viewArchiveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Archived Ticket Details</h2>
                <button class="modal-close" onclick="closeModal('viewArchiveModal')">&times;</button>
            </div>
            <div id="archiveTicketDetails"></div>
            <div class="flex justify-end" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('viewArchiveModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Bulk Restore Confirmation Modal -->
    <div class="modal" id="bulkRestoreModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Restore Multiple Tickets</h2>
                <button class="modal-close" onclick="closeModal('bulkRestoreModal')">&times;</button>
            </div>
            <div class="delete-warning">
                <i class="fas fa-trash-restore" style="color: var(--success);"></i>
                <h3>Are you sure?</h3>
                <p id="bulkRestoreMessage">You are about to restore <strong id="bulkRestoreCount">0</strong> tickets.</p>
                <input type="hidden" id="bulkRestoreIds">
                <div class="delete-actions">
                    <button class="btn btn-cancel" onclick="closeModal('bulkRestoreModal')">Cancel</button>
                    <button class="btn btn-success" onclick="bulkRestore()">Restore All</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // State Management
    let selectedTickets = new Set();

    // Bulk Operations Functions
    function updateBulkActions() {
        const visibleCheckboxes = document.querySelectorAll('.ticket-checkbox:not([style*="display: none"])');
        const checkedVisible = document.querySelectorAll('.ticket-checkbox:checked:not([style*="display: none"])');
        
        selectedTickets.clear();
        checkedVisible.forEach(cb => selectedTickets.add(cb.value));
        
        const count = selectedTickets.size;
        document.getElementById('selectedCount').textContent = count;
        
        // Update select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const visibleCheckboxesCount = visibleCheckboxes.length;
        
        if (selectAllCheckbox) {
            if (visibleCheckboxesCount > 0) {
                selectAllCheckbox.checked = count === visibleCheckboxesCount;
                selectAllCheckbox.indeterminate = count > 0 && count < visibleCheckboxesCount;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }
        
        // Show/hide bulk actions if needed
        const bulkActions = document.getElementById('bulkActions');
        if (bulkActions) {
            if (count > 0) {
                bulkActions.classList.add('visible');
            } else {
                bulkActions.classList.remove('visible');
            }
        }
    }

    function clearSelectionArchive() {
        document.querySelectorAll('.ticket-checkbox').forEach(cb => {
            cb.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkActions();
    }

    function toggleSelectAll(checkbox) {
        const visibleCheckboxes = document.querySelectorAll('.ticket-checkbox:not([style*="display: none"])');
        
        visibleCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        
        updateBulkActions();
    }
    
    function openBulkRestoreModal() {
        const ids = Array.from(selectedTickets);
        if (ids.length === 0) {
            showNotification('No tickets selected', 'warning');
            return;
        }
        
        document.getElementById('bulkRestoreIds').value = ids.join(',');
        document.getElementById('bulkRestoreCount').textContent = ids.length;
        openModal('bulkRestoreModal');
    }

    function bulkRestore() {
        const ids = document.getElementById('bulkRestoreIds').value.split(',');
        
        showLoading();
        
        fetch('../bulk-restore-tickets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ticket_ids: ids,
                user_id: 1 // Replace with actual user ID from session
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification(`Successfully restored ${data.restored} tickets`, 'success');
                closeModal('bulkRestoreModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error during bulk restore', 'danger');
        });
    }

    function searchTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('archiveTable');
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
        
        // Uncheck all when searching
        document.querySelectorAll('.ticket-checkbox').forEach(cb => {
            cb.checked = false;
        });
        
        updateBulkActions();
    }

    function restoreTicket(id) {
        if(confirm('Are you sure you want to restore this ticket?')) {
            showLoading();
            
            fetch('../restore-ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    ticket_id: id,
                    user_id: 1 // Replace with actual user ID from session
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if(data.success) {
                    showNotification('Ticket restored successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Error restoring ticket', 'danger');
            });
        }
    }

    function viewArchivedTicket(id) {
        showLoading();
        
        fetch(`../get-archived-ticket.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.error) {
                    showNotification(data.error, 'danger');
                    return;
                }
                
                const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
                const priorityClass = (data.priority || 'medium').toLowerCase();
                
                document.getElementById('archiveTicketDetails').innerHTML = `
                    <div style="display: grid; gap: 15px;">
                        <p><strong><i class="fas fa-hashtag"></i> Ticket ID:</strong> #${data.ticket_id}</p>
                        <p><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name || 'N/A'}</p>
                        <p><strong><i class="fas fa-user"></i> Contact:</strong> ${data.contact_person || 'N/A'}</p>
                        <p><strong><i class="fas fa-phone"></i> Contact Number:</strong> ${data.contact_number || 'N/A'}</p>
                        <p><strong><i class="fas fa-box"></i> Product:</strong> ${data.product_name || 'N/A'} ${data.version || ''}</p>
                        <p><strong><i class="fas fa-exclamation-triangle"></i> Concern:</strong> ${data.concern_type || 'N/A'}</p>
                        <p><strong><i class="fas fa-align-left"></i> Description:</strong></p>
                        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 10px;">${data.concern_description || ''}</div>
                        <p><strong><i class="fas fa-flag"></i> Priority:</strong> 
                            <span class='badge badge-${priorityClass}'>${data.priority}</span>
                        </p>
                        <p><strong><i class="fas fa-info-circle"></i> Status:</strong> 
                            <span class='badge badge-${statusClass}'>${data.status}</span>
                        </p>
                        <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> 
                            ${new Date(data.date_requested).toLocaleString()}
                        </p>
                        <p><strong><i class="fas fa-archive"></i> Archived Date:</strong> 
                            ${new Date(data.archived_at).toLocaleString()}
                        </p>
                        <p><strong><i class="fas fa-comment"></i> Archive Reason:</strong> 
                            ${data.archive_reason || 'N/A'}
                        </p>
                        ${data.solution ? `<p><strong><i class="fas fa-check-circle"></i> Solution:</strong> ${data.solution}</p>` : ''}
                    </div>
                `;
                
                openModal('viewArchiveModal');
            })
            .catch(error => {
                hideLoading();
                showNotification('Error fetching ticket details', 'danger');
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
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'flex';
        } else {
            const newSpinner = document.createElement('div');
            newSpinner.id = 'loadingSpinner';
            newSpinner.className = 'modal';
            newSpinner.style.background = 'rgba(0,0,0,0.5)';
            newSpinner.innerHTML = '<div class="spinner"></div>';
            newSpinner.style.display = 'flex';
            document.body.appendChild(newSpinner);
        }
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
        
        setTimeout(() => notification.remove(), 3000);
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['viewArchiveModal', 'bulkRestoreModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateBulkActions();
    });
    </script>
</body>
</html>