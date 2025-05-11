/**
 * Admin-specific JavaScript for the University Print System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterForm = document.getElementById('filter-form');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        // Auto-apply filters when select values change
        const filterSelects = filterForm.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', applyFilters);
        });
        
        function applyFilters() {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Redirect to the filtered URL
            window.location.href = `index.php?page=admin&${params.toString()}`;
        }
    }
    
    // Modal functionality
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.style.display = 'flex';
                
                // Close modal when clicking on backdrop or close button
                const closeButtons = modal.querySelectorAll('.modal-close, .modal-cancel');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        modal.style.display = 'none';
                    });
                });
                
                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }
        });
    });
    
    // Update print job status
    const statusForms = document.querySelectorAll('.update-status-form');
    
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const jobId = formData.get('job_id');
            const newStatus = formData.get('status');
            const statusCell = document.querySelector(`#job-${jobId} .job-status-cell`);
            
            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the status badge in the table
                    if (statusCell) {
                        const statusBadgeClass = getStatusBadgeClass(newStatus);
                        statusCell.innerHTML = `<span class="status-badge ${statusBadgeClass}">${getStatusLabel(newStatus)}</span>`;
                    }
                    
                    // Show success message
                    showNotification('Status updated successfully', 'success');
                } else {
                    showNotification(data.message || 'Error updating status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'pending': return 'status-pending';
            case 'printing': return 'status-printing';
            case 'ready': return 'status-ready';
            case 'completed': return 'status-completed';
            case 'cancelled': return 'status-cancelled';
            default: return '';
        }
    }
    
    function getStatusLabel(status) {
        switch (status) {
            case 'pending': return 'Pending';
            case 'printing': return 'Printing';
            case 'ready': return 'Ready for Pickup';
            case 'completed': return 'Completed';
            case 'cancelled': return 'Cancelled';
            default: return status;
        }
    }
    
    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.style.position = 'fixed';
        notification.style.bottom = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1000';
        notification.style.minWidth = '300px';
        notification.innerText = message;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 3000);
    }
    
    // Add credits to user
    const addCreditsForm = document.getElementById('add-credits-form');
    
    if (addCreditsForm) {
        addCreditsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Credits added successfully', 'success');
                    // Update the credits display if available
                    const creditsDisplay = document.getElementById('user-credits');
                    if (creditsDisplay && data.newBalance) {
                        creditsDisplay.textContent = '$' + data.newBalance;
                    }
                    // Reset the form
                    addCreditsForm.reset();
                } else {
                    showNotification(data.message || 'Error adding credits', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    }
});