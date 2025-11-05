/**
 * VehicSmart Main JavaScript File
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('VehicSmart JS loaded');
    
    // Initialize any tooltips
    initTooltips();
    
    // Initialize notification system
    initNotifications();
    
    // Set up event listeners for modals
    initModals();

    // Check for alerts or messages
    checkUserAlerts();
});

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            
            const tooltipElement = document.createElement('div');
            tooltipElement.classList.add('tooltip');
            tooltipElement.textContent = tooltipText;
            
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 10}px`;
            tooltipElement.style.left = `${rect.left + (rect.width / 2) - (tooltipElement.offsetWidth / 2)}px`;
            tooltipElement.style.opacity = '1';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = document.querySelector('.tooltip');
            if (tooltipElement) {
                tooltipElement.remove();
            }
        });
    });
}

/**
 * Initialize notification system
 */
function initNotifications() {
    // Create notification container if it doesn't exist
    let notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.classList.add('fixed', 'top-4', 'right-4', 'z-50');
        document.body.appendChild(notificationContainer);
    }
}

/**
 * Show notification
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notification-container');
    
    const notification = document.createElement('div');
    notification.classList.add(
        'notification',
        'bg-white',
        'rounded-lg',
        'shadow-lg',
        'p-4',
        'mb-3',
        'flex',
        'items-start',
        'max-w-sm',
        'w-full'
    );
    
    // Add border color based on type
    switch(type) {
        case 'success':
            notification.classList.add('border-l-4', 'border-green-500');
            break;
        case 'error':
            notification.classList.add('border-l-4', 'border-red-500');
            break;
        case 'warning':
            notification.classList.add('border-l-4', 'border-yellow-500');
            break;
        default:
            notification.classList.add('border-l-4', 'border-blue-500');
    }
    
    // Create icon based on type
    let iconClass = '';
    switch(type) {
        case 'success':
            iconClass = 'fa-check-circle text-green-500';
            break;
        case 'error':
            iconClass = 'fa-times-circle text-red-500';
            break;
        case 'warning':
            iconClass = 'fa-exclamation-triangle text-yellow-500';
            break;
        default:
            iconClass = 'fa-info-circle text-blue-500';
    }
    
    notification.innerHTML = `
        <div class="flex-shrink-0 mr-3">
            <i class="fas ${iconClass} text-lg"></i>
        </div>
        <div class="w-full">
            <p class="text-sm text-gray-800">${message}</p>
        </div>
        <button class="ml-4 text-gray-400 hover:text-gray-600 notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    // Add animation
    notification.classList.add('slide-in-up');
    
    // Auto-dismiss after duration
    const timeout = setTimeout(() => {
        dismissNotification(notification);
    }, duration);
    
    // Add click listener to close button
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        clearTimeout(timeout);
        dismissNotification(notification);
    });
}

/**
 * Dismiss notification with animation
 * @param {HTMLElement} notification - The notification element
 */
function dismissNotification(notification) {
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-10px)';
    notification.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
    
    setTimeout(() => {
        notification.remove();
    }, 300);
}

/**
 * Initialize modals
 */
function initModals() {
    // Open modal triggers
    document.querySelectorAll('[data-modal-target]').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-target');
            openModal(modalId);
        });
    });
    
    // Close modal triggers
    document.querySelectorAll('[data-modal-close]').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.closest('.modal').id;
            closeModal(modalId);
        });
    });
    
    // Close on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

/**
 * Open modal
 * @param {string} modalId - The ID of the modal to open
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Add animation
    setTimeout(() => {
        modal.querySelector('.modal-content').classList.add('scale-100', 'opacity-100');
        modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

/**
 * Close modal
 * @param {string} modalId - The ID of the modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.querySelector('.modal-content').classList.remove('scale-100', 'opacity-100');
    modal.querySelector('.modal-content').classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 300);
}

/**
 * Check for user alerts or messages
 */
function checkUserAlerts() {
    // Make API request to check for new messages or alerts
    fetch('/api/alerts/list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.alerts && data.alerts.length > 0) {
                // Update notification badge
                updateNotificationBadge(data.alerts.length);
                
                // Show notification for the latest alert if it's urgent
                const urgentAlerts = data.alerts.filter(alert => alert.priority === 'high' && !alert.read);
                if (urgentAlerts.length > 0) {
                    showNotification(urgentAlerts[0].message, 'warning');
                }
            }
        })
        .catch(error => {
            console.error('Error checking alerts:', error);
        });
}

/**
 * Update notification badge
 * @param {number} count - The number of notifications
 */
function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

/**
 * API Request Helper
 * @param {string} url - API endpoint URL
 * @param {string} method - HTTP method
 * @param {object} data - Request data
 * @returns {Promise} - Fetch promise
 */
function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include'
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
}
