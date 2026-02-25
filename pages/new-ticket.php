<?php
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Ticket - TicketFlow</title>
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
                               placeholder="company@email.com" required>
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
                                $stmt = $pdo->query("SELECT * FROM products");
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
                        <select class="form-control" id="concern" required>
                            <option value="">Select Concern</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT * FROM concerns");
                                while($concern = $stmt->fetch()) {
                                    echo "<option value='{$concern['concern_name']}'>{$concern['concern_name']}</option>";
                                }
                            } catch(Exception $e) {
                                echo "<option value=''>No concerns available</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Detailed Description
                    </label>
                    <textarea class="form-control" id="description" 
                              placeholder="Please describe the issue in detail..." required></textarea>
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
                        <input type="datetime-local" class="form-control" id="dateRequested" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>" required>
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

    function submitTicket(event) {
        event.preventDefault();
        showLoading();
        
        const formData = {
            company_name: document.getElementById('companyName').value,
            contact_person: document.getElementById('contactPerson').value,
            contact_number: document.getElementById('contactNumber').value,
            email: document.getElementById('email').value,
            product: document.getElementById('product').value,
            concern: document.getElementById('concern').value,
            description: document.getElementById('description').value,
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
            if(this.value.trim()) {
                this.style.borderColor = '#00b894';
            } else {
                this.style.borderColor = '#ff7675';
            }
        });
    });
    </script>
</body>
</html>