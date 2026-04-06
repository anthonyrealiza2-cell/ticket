<?php
require_once '../auth_check.php';
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
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="../css/new-tickets.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* ── Inline Add-Product Styles ──────────────────────────────────────── */
    .add-product-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 7px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--accent-primary);
        cursor: pointer;
        transition: opacity 0.2s;
        user-select: none;
    }
    .add-product-toggle:hover { opacity: 0.75; }
    .add-product-inline {
        margin-top: 10px;
        padding: 14px;
        background: var(--bg-secondary);
        border: 1px dashed var(--border-color);
        border-radius: 12px;
        animation: fadeSlideDown 0.2s ease;
    }
    @keyframes fadeSlideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .add-product-row {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .add-product-row .form-control {
        flex: 1;
        min-width: 120px;
    }
    .btn-add-product {
        padding: 10px 14px !important;
        font-size: 0.85rem !important;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .add-product-hint {
        display: block;
        margin-top: 8px;
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .add-product-hint.success { color: var(--success); }
    .add-product-hint.error   { color: var(--danger);  }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- New Ticket Form -->
        <div class="card">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-pen-fancy"></i>
                    Create New Support Ticket
                </div>
                <button type="button" class="btn-clear" onclick="clearForm()" title="Clear Form">
                    <i class="fas fa-eraser"></i>
                </button>
            </div>
            
            <form id="ticketForm" onsubmit="submitTicket(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Company Name
                        </label>
                        <input type="text" class="form-control" id="companyName" 
                               placeholder="Enter company name" list="companyList" autocomplete="off" required>
                        <datalist id="companyList">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT DISTINCT company_name FROM clients WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name");
                                while($client = $stmt->fetch()) {
                                    echo "<option value='" . htmlspecialchars($client['company_name'], ENT_QUOTES) . "'>";
                                }
                            } catch(Exception $e) {
                                // Silent fallback
                            }
                            ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Contact Person
                        </label>
                        <input type="text" class="form-control" id="contactPerson" 
                               placeholder="Full name" autocomplete="off" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Contact Number
                        </label>
                        <input type="tel" class="form-control" id="contactNumber" 
                               placeholder="+63 XXX XXX XXXX" autocomplete="off" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" 
                               placeholder="company@email.com" autocomplete="off">
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
                        <!-- Inline Add Product -->
                        <div class="add-product-toggle" id="addProductToggle" onclick="toggleAddProduct()">
                            <i class="fas fa-plus-circle"></i> Add new product
                        </div>
                        <div class="add-product-inline" id="addProductInline" style="display:none;">
                            <div class="add-product-row">
                                <input type="text" id="newProductName" class="form-control" placeholder="Product name" autocomplete="off">
                                <input type="text" id="newProductVersion" class="form-control" placeholder="Version (e.g. 2.0)" autocomplete="off" style="max-width: 130px;">
                                <button type="button" class="btn btn-primary btn-add-product" onclick="submitNewProduct()">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                                <button type="button" class="btn btn-secondary btn-add-product" onclick="toggleAddProduct()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="add-product-hint" id="addProductHint"></small>
                        </div>
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
                               value="<?php echo date('Y-m-d'); ?>" autocomplete="off" required>
                    </div>
                </div>

                <div class="flex justify-end">
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

    function clearForm() {
        document.getElementById('ticketForm').reset();
        
        // Trigger visual validation reset
        document.querySelectorAll('.form-control').forEach(input => {
            input.style.borderColor = 'var(--border-color)';
        });
        
        // Reset dynamic requirements
        toggleDescriptionRequirement();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleDescriptionRequirement();
    });

    // ── Inline Add Product ──────────────────────────────────────────────────
    function toggleAddProduct() {
        const inlineDiv = document.getElementById('addProductInline');
        const toggle    = document.getElementById('addProductToggle');
        const isOpen    = inlineDiv.style.display !== 'none';
        inlineDiv.style.display = isOpen ? 'none' : 'block';
        toggle.style.display    = isOpen ? 'inline-flex' : 'none';
        if (!isOpen) {
            document.getElementById('newProductName').focus();
        } else {
            document.getElementById('addProductHint').textContent    = '';
            document.getElementById('addProductHint').className      = 'add-product-hint';
            document.getElementById('newProductName').value          = '';
            document.getElementById('newProductVersion').value       = '';
        }
    }

    async function submitNewProduct() {
        const name    = document.getElementById('newProductName').value.trim();
        const version = document.getElementById('newProductVersion').value.trim() || '1.0';
        const hint    = document.getElementById('addProductHint');

        if (!name) {
            hint.textContent = '⚠ Product name is required.';
            hint.className   = 'add-product-hint error';
            document.getElementById('newProductName').focus();
            return;
        }

        hint.textContent = 'Saving...';
        hint.className   = 'add-product-hint';

        try {
            const res  = await fetch('../add-product.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ product_name: name, version })
            });
            const data = await res.json();

            if (!data.success) {
                hint.textContent = '✗ ' + (data.message || 'Failed to add product.');
                hint.className   = 'add-product-hint error';
                return;
            }

            // Append to dropdown if not duplicate, then select it
            const select = document.getElementById('product');
            const label  = data.label;
            let   opt    = select.querySelector(`option[value="${CSS.escape(label)}"]`);
            if (!opt) {
                opt = new Option(label, label);
                select.add(opt);
            }
            select.value = label;
            opt.style.borderColor = 'var(--success)';

            hint.textContent = data.duplicate
                ? '✓ Product already exists — selected for you.'
                : '✓ Product added and selected!';
            hint.className = 'add-product-hint success';

            // Auto-close after 1.5 s
            setTimeout(toggleAddProduct, 1500);
        } catch (e) {
            hint.textContent = '✗ Network error. Please try again.';
            hint.className   = 'add-product-hint error';
        }
    }

    // Allow pressing Enter in product name to submit
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('newProductName');
        if (nameInput) {
            nameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); submitNewProduct(); }
            });
        }
    });
    </script>
</body>
</html>