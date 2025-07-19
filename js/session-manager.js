// Session Management with automatic timeout and activity tracking
class SessionManager {
    constructor() {
        this.sessionTimer = null;
        this.warningTimer = null;
        this.isWarningShown = false;
        this.lastActivity = Date.now();
        this.sessionTimeout = 30 * 60 * 1000; // 30 minutes in milliseconds
        this.warningTime = 5 * 60 * 1000; // Show warning 5 minutes before timeout
        this.heartbeatInterval = 60 * 1000; // Check session every minute
        
        this.init();
    }
    
    init() {
        // Only initialize if we're not on the login page
        if (window.location.pathname.includes('login.html')) {
            return;
        }
        
        // Check if user is authenticated
        this.checkAuthentication();
        
        // Setup activity tracking
        this.setupActivityTracking();
        
        // Start session monitoring
        this.startSessionMonitoring();
        
        // Setup heartbeat
        this.startHeartbeat();
        
        // Update session info display
        this.updateSessionDisplay();
    }
    
    async checkAuthentication() {
        try {
            const response = await api.get('session.php');
            
            if (!response || !response.success) {
                // Not authenticated, redirect to login
                window.location.href = 'login.html';
                return;
            }
            
            // Update auth module with session info
            auth.sessionInfo = response.session;
            auth.currentUser = {
                id: response.session.user_id,
                username: response.session.username,
                email: response.session.email,
                role: response.session.role,
                permissions: response.session.permissions
            };
            
            // Update last activity
            this.lastActivity = Date.now();
            
        } catch (error) {
            console.error('Authentication check failed:', error);
            window.location.href = 'login.html';
        }
    }
    
    setupActivityTracking() {
        // Track user activities
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateActivity();
            }, true);
        });
        
        // Track page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateActivity();
            }
        });
    }
    
    updateActivity() {
        this.lastActivity = Date.now();
        
        // Hide warning if it's shown
        if (this.isWarningShown) {
            this.hideSessionWarning();
        }
        
        // Update activity timestamp in session
        this.updateSessionActivity();
    }
    
    async updateSessionActivity() {
        try {
            // This API call will update the last_activity timestamp in the database
            await api.get('session.php');
        } catch (error) {
            console.error('Failed to update session activity:', error);
        }
    }
    
    startSessionMonitoring() {
        // Check session timeout every 30 seconds
        this.sessionTimer = setInterval(() => {
            this.checkSessionTimeout();
        }, 30000);
    }
    
    startHeartbeat() {
        // Send heartbeat to server every minute
        setInterval(async () => {
            try {
                const response = await api.get('session.php');
                
                if (!response || !response.success) {
                    this.handleSessionExpiry();
                } else {
                    // Update session timer display
                    this.updateSessionTimerDisplay(response.session.time_remaining);
                }
                
            } catch (error) {
                console.error('Heartbeat failed:', error);
                // Don't immediately logout on heartbeat failure
                // Could be temporary network issue
            }
        }, this.heartbeatInterval);
    }
    
    checkSessionTimeout() {
        const now = Date.now();
        const timeSinceLastActivity = now - this.lastActivity;
        
        // Check if we need to show warning (5 minutes before timeout)
        if (timeSinceLastActivity >= (this.sessionTimeout - this.warningTime) && !this.isWarningShown) {
            this.showSessionWarning();
        }
        
        // Check if session has expired
        if (timeSinceLastActivity >= this.sessionTimeout) {
            this.handleSessionExpiry();
        }
    }
    
    showSessionWarning() {
        this.isWarningShown = true;
        const modal = document.getElementById('sessionWarningModal');
        
        if (modal) {
            modal.style.display = 'flex';
            
            // Start countdown in warning modal
            this.startWarningCountdown();
        }
    }
    
    hideSessionWarning() {
        this.isWarningShown = false;
        const modal = document.getElementById('sessionWarningModal');
        
        if (modal) {
            modal.style.display = 'none';
        }
        
        // Clear warning countdown
        if (this.warningTimer) {
            clearInterval(this.warningTimer);
            this.warningTimer = null;
        }
    }
    
    startWarningCountdown() {
        const timerElement = document.getElementById('warningTimer');
        let timeRemaining = this.warningTime / 1000; // 5 minutes in seconds
        
        this.warningTimer = setInterval(() => {
            timeRemaining--;
            
            if (timerElement) {
                timerElement.textContent = ui.formatTimeRemaining(timeRemaining);
            }
            
            if (timeRemaining <= 0) {
                this.handleSessionExpiry();
            }
        }, 1000);
    }
    
    handleSessionExpiry() {
        // Clear all timers
        if (this.sessionTimer) {
            clearInterval(this.sessionTimer);
        }
        if (this.warningTimer) {
            clearInterval(this.warningTimer);
        }
        
        // Show expiry message
        ui.showWarning('جلسه شما به دلیل عدم فعالیت منقضی شد', 3000);
        
        // Logout after showing message
        setTimeout(() => {
            auth.logout();
        }, 3000);
    }
    
    extendSession() {
        // Reset activity time
        this.updateActivity();
        
        // Hide warning
        this.hideSessionWarning();
        
        // Optionally notify server about session extension
        this.updateSessionActivity();
        
        ui.showSuccess('جلسه شما تمدید شد', 3000);
    }
    
    updateSessionDisplay() {
        // Update session info in the dashboard
        setInterval(() => {
            this.displaySessionInfo();
        }, 1000);
    }
    
    async displaySessionInfo() {
        if (!auth.sessionInfo) return;
        
        const now = Math.floor(Date.now() / 1000);
        const sessionDuration = now - auth.sessionInfo.login_time;
        const timeSinceLastActivity = (Date.now() - this.lastActivity) / 1000;
        const timeRemaining = Math.max(0, (this.sessionTimeout / 1000) - timeSinceLastActivity);
        
        // Update session timer in navbar
        const sessionTimerEl = document.getElementById('sessionTimer');
        if (sessionTimerEl) {
            sessionTimerEl.textContent = ui.formatTimeRemaining(timeRemaining);
            
            // Change color based on time remaining
            if (timeRemaining < 300) { // Less than 5 minutes
                sessionTimerEl.parentElement.classList.add('warning');
            } else {
                sessionTimerEl.parentElement.classList.remove('warning');
            }
        }
        
        // Update session info in dashboard widgets
        ui.updateElementText('loginTime', ui.formatDateTime(auth.sessionInfo.login_time));
        ui.updateElementText('sessionDuration', ui.formatDuration(sessionDuration));
        ui.updateElementText('lastActivity', this.formatLastActivity(timeSinceLastActivity));
        
        // Update IP address (if available)
        if (auth.sessionInfo.ip_address) {
            ui.updateElementText('userIP', auth.sessionInfo.ip_address);
        }
    }
    
    formatLastActivity(seconds) {
        if (seconds < 60) {
            return 'همین الان';
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            return `${minutes} دقیقه پیش`;
        } else {
            const hours = Math.floor(seconds / 3600);
            return `${hours} ساعت پیش`;
        }
    }
    
    updateSessionTimerDisplay(timeRemaining) {
        const sessionTimerEl = document.getElementById('sessionTimer');
        if (sessionTimerEl && timeRemaining) {
            sessionTimerEl.textContent = ui.formatTimeRemaining(timeRemaining);
            
            // Update warning state
            if (timeRemaining < 300) { // Less than 5 minutes
                sessionTimerEl.parentElement.classList.add('warning');
            } else {
                sessionTimerEl.parentElement.classList.remove('warning');
            }
        }
    }
    
    // Manual logout
    logout() {
        auth.logout();
    }
    
    // Clean up when page unloads
    cleanup() {
        if (this.sessionTimer) {
            clearInterval(this.sessionTimer);
        }
        if (this.warningTimer) {
            clearInterval(this.warningTimer);
        }
    }
}

// Initialize session manager
let sessionManager;

document.addEventListener('DOMContentLoaded', function() {
    sessionManager = new SessionManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (sessionManager) {
        sessionManager.cleanup();
    }
});

// Global functions for HTML onclick handlers
function extendSession() {
    if (sessionManager) {
        sessionManager.extendSession();
    }
}

function logout() {
    if (sessionManager) {
        sessionManager.logout();
    } else {
        auth.logout();
    }
}

// Handle browser tab switching
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && sessionManager) {
        // Tab became visible, update activity
        sessionManager.updateActivity();
    }
});

// Export for global access
window.sessionManager = sessionManager;