// Resolve base path: if loaded from a subdirectory (e.g. /admin/ or /tech/), walk up one level
const _basePath = (function() {
    const depth = window.location.pathname.split('/').filter(Boolean).length;
    // /ticket/admin/index.php → depth 3 → need '../'
    // /ticket/index.php       → depth 2 → no prefix needed
    const scriptPath = document.currentScript ? document.currentScript.src : '';
    // dashboard.js always lives at root, so base is one level above /admin/
    const parts = window.location.pathname.split('/');
    parts.pop(); // remove filename
    const dir = parts[parts.length - 1]; // last segment = current folder name
    return (dir === 'admin' || dir === 'tech') ? '../' : '';
})();

function viewTicket(ticketId) {
    // Show loading state
    const details = document.getElementById('ticketDetails');
    details.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><p style="margin-top: 20px;">Loading ticket details...</p></div>';
    
    const modal = document.getElementById('viewTicketModal');
    modal.style.display = 'flex';
    
    fetch(`${_basePath}get-tickets.php?id=${ticketId}`)

        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                details.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }
            
            // Log the data to console for debugging
            console.log('Ticket data:', data);
            
            const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '');
            const priorityClass = (data.priority || 'medium').toLowerCase();
            
            // Format contact info with fallbacks
            const contactNumber = data.contact_number && data.contact_number.trim() !== '' 
                ? data.contact_number 
                : '<span class="no-data">Not provided</span>';
                
            const email = data.email && data.email.trim() !== '' 
                ? data.email 
                : '<span class="no-data">Not provided</span>';
            
            details.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="ticket-detail-item">
                        <i class="fas fa-hashtag"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Ticket ID</div>
                            <div class="ticket-detail-value">#${data.ticket_id}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-building"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Company</div>
                            <div class="ticket-detail-value">${data.company_name || '<span class="no-data">Not specified</span>'}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-user"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Contact Person</div>
                            <div class="ticket-detail-value">${data.contact_person || '<span class="no-data">Not specified</span>'}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-phone"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Contact Number</div>
                            <div class="ticket-detail-value">${contactNumber}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-envelope"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Email</div>
                            <div class="ticket-detail-value">${email}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-box"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Product</div>
                            <div class="ticket-detail-value">${data.product_name || 'N/A'} ${data.version || ''}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Concern Type</div>
                            <div class="ticket-detail-value">${data.concern_type || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-align-left"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Description</div>
                            <div class="ticket-description">${data.concern_description || 'No description provided'}</div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-flag"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Priority</div>
                            <div class="ticket-detail-value">
                                <span class='badge badge-${priorityClass}'>${data.priority || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-info-circle"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Status</div>
                            <div class="ticket-detail-value">
                                <span class='badge badge-${statusClass}'>${data.status || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ticket-detail-item">
                        <i class="fas fa-calendar"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Date Requested</div>
                            <div class="ticket-detail-value">${data.date_requested ? new Date(data.date_requested).toLocaleString() : 'N/A'}</div>
                        </div>
                    </div>
                    
                    ${data.tech_firstname ? `
                    <div class="ticket-detail-item">
                        <i class="fas fa-user-cog"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Assigned To</div>
                            <div class="ticket-detail-value">${data.tech_firstname} ${data.tech_lastname}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.finish_date ? `
                    <div class="ticket-detail-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Date Finished</div>
                            <div class="ticket-detail-value">${new Date(data.finish_date).toLocaleString()}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.solution ? `
                    <div class="ticket-detail-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="ticket-detail-content">
                            <div class="ticket-detail-label">Solution</div>
                            <div class="ticket-detail-value">${data.solution}</div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            details.innerHTML = `<div class="alert alert-danger">Error fetching ticket details: ${error.message}</div>`;
        });
}

function closeModal() {
    document.getElementById('viewTicketModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('viewTicketModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
