<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Ticket - TicketFlow</title>
    <!-- <link rel="stylesheet" href="../style.css"> -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/new-tickets.css">
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
                <a href="new-ticket.php" class="nav-link active"><i class="fas fa-plus-circle"></i> New Ticket</a>
                <a href="tickets.php" class="nav-link"><i class="fas fa-list"></i> Tickets</a>
                <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
                <a href="technical.php" class="nav-link"><i class="fas fa-user-cog"></i> Technical</a>
                <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </nav>

        <!-- New Ticket Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-pen-fancy"></i>
                Create New Support Ticket
            </div>
            
            <form id="ticketForm" onsubmit="submitTicket(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Company Name
                        </label>
                        <input type="text" class="form-control" id="companyName" 
                               placeholder="Enter company name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Contact Person
                        </label>
                        <input type="text" class="form-control" id="contactPerson" 
                               placeholder="Full name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Contact Number
                        </label>
                        <input type="tel" class="form-control" id="contactNumber" 
                               placeholder="+63 XXX XXX XXXX" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" 
                               placeholder="company@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-box"></i> Product
                        </label>
                        <select class="form-control" id="product" required>
                            <option value="">Select Product</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT * FROM products ORDER BY product_name");
                                while($product = $stmt->fetch()) {
                                    echo "<option value='{$product['product_name']} v{$product['version']}'>
                                            {$product['product_name']} v{$product['version']}
                                          </option>";
                                }
                            } catch(Exception $e) {
                                echo "<option value=''>No products available</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                   <div class="form-group">
    <label class="form-label">
        <i class="fas fa-exclamation-triangle"></i> Concern Type
    </label>
    <select class="form-control" id="concern" required onchange="toggleDescriptionRequirement()">
        <option value="">Select Concern</option>
        <?php
        try {
            // This query puts 'Others' at the bottom by using a CASE statement in ORDER BY
            $stmt = $pdo->query("
                SELECT * FROM concerns 
                ORDER BY 
                    CASE 
                        WHEN concern_name = 'Others' THEN 1 
                        ELSE 0 
                    END, 
                    concern_name ASC
            ");
            
            while($concern = $stmt->fetch()) {
                echo "<option value='{$concern['concern_name']}' data-id='{$concern['concern_id']}'>
                        {$concern['concern_name']}
                      </option>";
            }
        } catch(Exception $e) {
            echo "<option value=''>No concerns available</option>";
        }
        ?>
    </select>
    <small class="field-hint" id="concernHint">
        <i class="fas fa-info-circle"></i>
        Select "Others" if your concern is not listed
    </small>
</div>
                </div>

                <div class="form-group">
                    <label class="form-label" id="descriptionLabel">
                        <i class="fas fa-align-left"></i> Detailed Description
                        <span id="requiredIndicator" style="color: var(--danger); margin-left: 4px; display: none;">*</span>
                    </label>
                    <textarea class="form-control" id="description" 
                              placeholder="Please describe the issue in detail..." 
                              <?php 
                              // Initially not required, will be toggled by JavaScript
                              ?>></textarea>
                    <small class="field-hint" id="descriptionHint">
                        <i class="fas fa-info-circle"></i>
                        <span id="hintText">Description is optional for standard concerns</span>
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Priority Level
                        </label>
                        <select class="form-control" id="priority" required>
                            <option value="Low">🐢 Low - Minor issue</option>
                            <option value="Medium" selected>⚡ Medium - Normal priority</option>
                            <option value="High">🔥 High - Critical issue</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar"></i> Date Requested
                        </label>
                        <input type="date" class="form-control" id="dateRequested" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button type="button" class="btn btn-danger" onclick="window.location.href='../index.php'">
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
    <div id="loadingSpinner" class="modal" style="background: transparent; display: none;">
        <div class="spinner"></div>
    </div>

    <script>
    function showLoading() {
        document.getElementById('loadingSpinner').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            ${message}
        `;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '3000';
        notification.style.minWidth = '300px';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Toggle description requirement based on concern selection
    function toggleDescriptionRequirement() {
        const concernSelect = document.getElementById('concern');
        const description = document.getElementById('description');
        const requiredIndicator = document.getElementById('requiredIndicator');
        const hintText = document.getElementById('hintText');
        
        // Check if "Others" is selected
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
        
        // Update visual state
        if (description.value.trim()) {
            description.style.borderColor = '#00b894';
        } else {
            description.style.borderColor = isOthers ? '#ff7675' : 'var(--border-color)';
        }
    }

    function submitTicket(event) {
        event.preventDefault();
        
        // Get form elements
        const concernSelect = document.getElementById('concern');
        const description = document.getElementById('description');
        
        // Validate description if "Others" is selected
        if (concernSelect.value === 'Others' && !description.value.trim()) {
            showNotification('Please provide a description for "Others" concern', 'danger');
            description.style.borderColor = '#ff7675';
            description.focus();
            return;
        }
        
        showLoading();
        
        const formData = {
            company_name: document.getElementById('companyName').value,
            contact_person: document.getElementById('contactPerson').value,
            contact_number: document.getElementById('contactNumber').value,
            email: document.getElementById('email').value,
            product: document.getElementById('product').value,
            concern: concernSelect.value,
            description: description.value || null, // Send null if empty
            priority: document.getElementById('priority').value,
            date_requested: document.getElementById('dateRequested').value
        };
        
        fetch('../create-ticket.php', {
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
                showNotification('Ticket created successfully! 🎉', 'success');
                setTimeout(() => {
                    window.location.href = 'tickets.php';
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

    // Real-time validation
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', function() {
            if(this.id === 'description') {
                const concernSelect = document.getElementById('concern');
                const isOthers = concernSelect.value === 'Others';
                
                if(isOthers && !this.value.trim()) {
                    this.style.borderColor = '#ff7675';
                } else if(this.value.trim()) {
                    this.style.borderColor = '#00b894';
                } else {
                    this.style.borderColor = 'var(--border-color)';
                }
            } else {
                if(this.value.trim()) {
                    this.style.borderColor = '#00b894';
                } else {
                    this.style.borderColor = 'var(--border-color)';
                }
            }
        });
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleDescriptionRequirement();
    });
    </script>
</body>
</html>