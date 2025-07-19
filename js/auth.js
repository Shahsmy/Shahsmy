// Authentication and API handling module
const API_BASE = 'api/';

// API utility functions
const api = {
    async request(endpoint, options = {}) {
        const url = API_BASE + endpoint;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                if (response.status === 401) {
                    // Session expired, redirect to login
                    if (window.location.pathname !== '/login.html') {
                        window.location.href = 'login.html';
                    }
                    return null;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    },

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint, data = null) {
        const options = { method: 'DELETE' };
        if (data) {
            options.body = JSON.stringify(data);
        }
        return this.request(endpoint, options);
    }
};

// Authentication module
const auth = {
    currentUser: null,
    sessionInfo: null,

    async login(username, password) {
        try {
            const response = await api.post('login.php', {
                username: username,
                password: password
            });

            if (response && response.success) {
                this.currentUser = response.user;
                this.sessionInfo = response.session;
                
                // Store session info for quick access
                localStorage.setItem('userRole', response.user.role);
                localStorage.setItem('userPermissions', JSON.stringify(response.user.permissions));
                
                return response;
            }
            
            return response;
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: 'خطا در ارتباط با سرور' };
        }
    },

    async logout() {
        try {
            await api.post('logout.php');
        } catch (error) {
            console.error('Logout error:', error);
        }
        
        // Clear local data
        this.currentUser = null;
        this.sessionInfo = null;
        localStorage.removeItem('userRole');
        localStorage.removeItem('userPermissions');
        
        // Redirect to login
        window.location.href = 'login.html';
    },

    async checkSession() {
        try {
            const response = await api.get('session.php');
            
            if (response && response.success) {
                this.sessionInfo = response.session;
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Session check error:', error);
            return false;
        }
    },

    hasPermission(permission) {
        if (!this.currentUser || !this.currentUser.permissions) {
            return false;
        }
        return this.currentUser.permissions.includes(permission);
    },

    requirePermission(permission) {
        if (!this.hasPermission(permission)) {
            throw new Error('دسترسی غیرمجاز');
        }
    },

    isAdmin() {
        return this.currentUser && this.currentUser.role === 'admin';
    },

    isManager() {
        return this.currentUser && ['admin', 'manager'].includes(this.currentUser.role);
    }
};

// UI utility functions
const ui = {
    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    },

    showMessage(message, type = 'info', duration = 5000) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message
        const messageEl = document.createElement('div');
        messageEl.className = `message ${type}-message fade-in`;
        messageEl.textContent = message;

        // Insert at the top of the page
        const container = document.querySelector('.page-content') || document.body;
        container.insertBefore(messageEl, container.firstChild);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                messageEl.remove();
            }, duration);
        }

        return messageEl;
    },

    showError(message, duration = 7000) {
        return this.showMessage(message, 'error', duration);
    },

    showSuccess(message, duration = 5000) {
        return this.showMessage(message, 'success', duration);
    },

    showWarning(message, duration = 6000) {
        return this.showMessage(message, 'warning', duration);
    },

    formatDateTime(timestamp) {
        if (!timestamp) return '-';
        const date = new Date(timestamp * 1000);
        return date.toLocaleString('fa-IR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    formatDuration(seconds) {
        if (!seconds || seconds < 0) return '-';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    },

    formatTimeRemaining(seconds) {
        if (!seconds || seconds < 0) return '00:00';
        
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },

    updateElementText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text;
        }
    },

    toggleClass(elementId, className, add = null) {
        const element = document.getElementById(elementId);
        if (element) {
            if (add === null) {
                element.classList.toggle(className);
            } else if (add) {
                element.classList.add(className);
            } else {
                element.classList.remove(className);
            }
        }
    },

    hideElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'none';
        }
    },

    showElement(elementId, display = 'block') {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = display;
        }
    },

    clearForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            // Clear any error states
            const errorFields = form.querySelectorAll('.error');
            errorFields.forEach(field => field.classList.remove('error'));
        }
    }
};

// Permission checking for UI elements
function checkPermissions() {
    const permissionsStr = localStorage.getItem('userPermissions');
    if (!permissionsStr) return;
    
    const permissions = JSON.parse(permissionsStr);
    
    // Hide elements that require permissions the user doesn't have
    document.querySelectorAll('[data-permission]').forEach(element => {
        const requiredPermission = element.getAttribute('data-permission');
        if (!permissions.includes(requiredPermission)) {
            element.style.display = 'none';
        }
    });
}

// Initialize permission checking when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    checkPermissions();
});

// Handle session expiry
function handleSessionExpiry() {
    ui.showError('جلسه شما منقضی شده است. لطفاً مجدداً وارد شوید.');
    setTimeout(() => {
        auth.logout();
    }, 2000);
}

// Global error handler
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    ui.showError('خطای غیرمنتظره‌ای رخ داده است');
});

// Export for global access
window.api = api;
window.auth = auth;
window.ui = ui;