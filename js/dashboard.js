// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Setup navigation
    setupNavigation();
    
    // Load initial data
    loadDashboardData();
});

async function initializeDashboard() {
    // Check authentication first
    const isAuthenticated = await auth.checkSession();
    if (!isAuthenticated) {
        window.location.href = 'login.html';
        return;
    }
    
    // Update user info in navbar
    if (auth.sessionInfo) {
        ui.updateElementText('currentUsername', auth.sessionInfo.username);
    }
    
    // Check permissions and hide unauthorized elements
    checkPermissions();
}

function setupNavigation() {
    // Sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // User dropdown
    const userBtn = document.querySelector('.user-btn');
    if (userBtn) {
        userBtn.addEventListener('click', toggleUserMenu);
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('userDropdown');
        const userBtn = document.querySelector('.user-btn');
        
        if (dropdown && userBtn && !userBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
    
    // Handle navigation clicks
    document.querySelectorAll('.nav-item a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.closest('.nav-item').getAttribute('data-page');
            if (page) {
                showPage(page);
            }
        });
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed');
    }
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function showPage(pageId) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.style.display = 'none';
    });
    
    // Show selected page
    const targetPage = document.getElementById(pageId + '-page');
    if (targetPage) {
        targetPage.style.display = 'block';
        targetPage.classList.add('fade-in');
    }
    
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeItem = document.querySelector(`[data-page="${pageId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
    
    // Load page-specific data
    loadPageData(pageId);
}

async function loadPageData(pageId) {
    switch (pageId) {
        case 'dashboard':
            await loadDashboardStats();
            await loadRecentActivities();
            break;
        case 'users':
            await loadUsers();
            break;
        case 'sessions':
            await loadActiveSessions();
            break;
        // Add other page loaders as needed
    }
}

async function loadDashboardData() {
    await loadDashboardStats();
    await loadRecentActivities();
}

async function loadDashboardStats() {
    try {
        ui.showLoading(true);
        
        // Mock data - replace with actual API calls
        const stats = {
            totalUsers: 5,
            activeSessions: 2,
            totalAccounts: 12,
            todayTransactions: 8
        };
        
        // Update stat cards
        ui.updateElementText('totalUsers', stats.totalUsers);
        ui.updateElementText('activeSessions', stats.activeSessions);
        ui.updateElementText('totalAccounts', stats.totalAccounts);
        ui.updateElementText('todayTransactions', stats.todayTransactions);
        
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        ui.showError('خطا در بارگذاری آمار داشبرد');
    } finally {
        ui.showLoading(false);
    }
}

async function loadRecentActivities() {
    try {
        // Mock data - replace with actual API call
        const activities = [
            {
                icon: 'user-plus',
                title: 'کاربر جدید ایجاد شد',
                time: '5 دقیقه پیش',
                color: 'success'
            },
            {
                icon: 'sign-in-alt',
                title: 'ورود کاربر admin',
                time: '10 دقیقه پیش',
                color: 'primary'
            },
            {
                icon: 'edit',
                title: 'بروزرسانی حساب مالی',
                time: '25 دقیقه پیش',
                color: 'warning'
            },
            {
                icon: 'trash',
                title: 'حذف تراکنش',
                time: '1 ساعت پیش',
                color: 'danger'
            }
        ];
        
        const activitiesContainer = document.getElementById('recentActivities');
        if (activitiesContainer) {
            activitiesContainer.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: var(--${activity.color}-color)">
                        <i class="fas fa-${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="title">${activity.title}</div>
                        <div class="time">${activity.time}</div>
                    </div>
                </div>
            `).join('');
        }
        
    } catch (error) {
        console.error('Error loading recent activities:', error);
    }
}

async function loadUsers() {
    try {
        ui.showLoading(true);
        
        const response = await api.get('users.php');
        
        if (response && response.success) {
            displayUsers(response.users);
        } else {
            ui.showError('خطا در بارگذاری لیست کاربران');
        }
        
    } catch (error) {
        console.error('Error loading users:', error);
        ui.showError('خطا در بارگذاری لیست کاربران');
    } finally {
        ui.showLoading(false);
    }
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${getRoleLabel(user.role)}</td>
            <td>
                <span class="status-badge status-${user.status}">
                    ${getStatusLabel(user.status)}
                </span>
            </td>
            <td>${user.last_login ? ui.formatDateTime(new Date(user.last_login).getTime() / 1000) : 'هرگز'}</td>
            <td>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})" data-permission="users_edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" data-permission="users_delete" ${user.role === 'admin' ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Check permissions for action buttons
    checkPermissions();
}

function getRoleLabel(role) {
    const roles = {
        'admin': 'مدیر ارشد',
        'manager': 'مدیر',
        'user': 'کاربر'
    };
    return roles[role] || role;
}

function getStatusLabel(status) {
    const statuses = {
        'active': 'فعال',
        'inactive': 'غیرفعال',
        'suspended': 'معلق'
    };
    return statuses[status] || status;
}

async function loadActiveSessions() {
    try {
        ui.showLoading(true);
        
        // Mock data - replace with actual API call to get active sessions
        const sessions = [
            {
                username: 'admin',
                email: 'admin@example.com',
                ip_address: '192.168.1.100',
                login_time: new Date(Date.now() - 3600000).toISOString(), // 1 hour ago
                last_activity: new Date(Date.now() - 300000).toISOString(), // 5 minutes ago
                user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        ];
        
        displaySessions(sessions);
        
    } catch (error) {
        console.error('Error loading active sessions:', error);
        ui.showError('خطا در بارگذاری جلسات فعال');
    } finally {
        ui.showLoading(false);
    }
}

function displaySessions(sessions) {
    const tbody = document.getElementById('sessionsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = sessions.map(session => `
        <tr>
            <td>${session.username}</td>
            <td>${session.email}</td>
            <td>${session.ip_address}</td>
            <td>${ui.formatDateTime(new Date(session.login_time).getTime() / 1000)}</td>
            <td>${ui.formatDateTime(new Date(session.last_activity).getTime() / 1000)}</td>
            <td>${getBrowserInfo(session.user_agent)}</td>
        </tr>
    `).join('');
}

function getBrowserInfo(userAgent) {
    if (userAgent.includes('Chrome')) return 'Chrome';
    if (userAgent.includes('Firefox')) return 'Firefox';
    if (userAgent.includes('Safari')) return 'Safari';
    if (userAgent.includes('Edge')) return 'Edge';
    return 'سایر';
}

function refreshSessions() {
    loadActiveSessions();
    ui.showSuccess('لیست جلسات بروزرسانی شد');
}

// User management functions
function showCreateUserModal() {
    const modal = document.getElementById('createUserModal');
    if (modal) {
        modal.style.display = 'flex';
        setupPermissionsGrid();
        ui.clearForm('createUserForm');
    }
}

function closeCreateUserModal() {
    const modal = document.getElementById('createUserModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function setupPermissionsGrid() {
    const permissionsGrid = document.getElementById('permissionsGrid');
    if (!permissionsGrid) return;
    
    const permissions = [
        { id: 'dashboard_view', label: 'مشاهده داشبرد' },
        { id: 'users_view', label: 'مشاهده کاربران' },
        { id: 'users_create', label: 'ایجاد کاربر' },
        { id: 'users_edit', label: 'ویرایش کاربر' },
        { id: 'users_delete', label: 'حذف کاربر' },
        { id: 'accounts_view', label: 'مشاهده حساب‌ها' },
        { id: 'accounts_manage', label: 'مدیریت حساب‌ها' },
        { id: 'transactions_view', label: 'مشاهده تراکنش‌ها' },
        { id: 'transactions_manage', label: 'مدیریت تراکنش‌ها' },
        { id: 'reports_view', label: 'مشاهده گزارش‌ها' },
        { id: 'system_settings', label: 'تنظیمات سیستم' }
    ];
    
    permissionsGrid.innerHTML = permissions.map(permission => `
        <div class="permission-item">
            <input type="checkbox" id="perm_${permission.id}" name="permissions" value="${permission.id}">
            <label for="perm_${permission.id}">${permission.label}</label>
        </div>
    `).join('');
}

// Setup create user form submission
document.addEventListener('DOMContentLoaded', function() {
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', handleCreateUser);
    }
});

async function handleCreateUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const permissions = Array.from(formData.getAll('permissions'));
    
    const userData = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role'),
        permissions: permissions,
        status: 'active'
    };
    
    try {
        ui.showLoading(true);
        
        const response = await api.post('users.php', userData);
        
        if (response && response.success) {
            ui.showSuccess('کاربر با موفقیت ایجاد شد');
            closeCreateUserModal();
            loadUsers(); // Refresh users list
        } else {
            ui.showError(response.message || 'خطا در ایجاد کاربر');
        }
        
    } catch (error) {
        console.error('Error creating user:', error);
        ui.showError('خطا در ایجاد کاربر');
    } finally {
        ui.showLoading(false);
    }
}

function editUser(userId) {
    // TODO: Implement user editing
    ui.showInfo('ویرایش کاربر در نسخه آینده پیاده‌سازی خواهد شد');
}

async function deleteUser(userId) {
    if (!confirm('آیا از حذف این کاربر اطمینان دارید؟')) {
        return;
    }
    
    try {
        ui.showLoading(true);
        
        const response = await api.delete('users.php', { id: userId });
        
        if (response && response.success) {
            ui.showSuccess('کاربر با موفقیت حذف شد');
            loadUsers(); // Refresh users list
        } else {
            ui.showError(response.message || 'خطا در حذف کاربر');
        }
        
    } catch (error) {
        console.error('Error deleting user:', error);
        ui.showError('خطا در حذف کاربر');
    } finally {
        ui.showLoading(false);
    }
}

// Profile and settings (placeholder functions)
function showProfile() {
    ui.showInfo('صفحه پروفایل در نسخه آینده پیاده‌سازی خواهد شد');
}

function showSettings() {
    ui.showInfo('صفحه تنظیمات در نسخه آینده پیاده‌سازی خواهد شد');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Handle role change in create user form
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('newRole');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            updatePermissionsForRole(selectedRole);
        });
    }
});

function updatePermissionsForRole(role) {
    const permissionCheckboxes = document.querySelectorAll('#permissionsGrid input[type="checkbox"]');
    
    // Clear all checkboxes first
    permissionCheckboxes.forEach(cb => cb.checked = false);
    
    // Set permissions based on role
    const rolePermissions = {
        'admin': ['dashboard_view', 'users_view', 'users_create', 'users_edit', 'users_delete', 
                 'accounts_view', 'accounts_manage', 'transactions_view', 'transactions_manage', 
                 'reports_view', 'system_settings'],
        'manager': ['dashboard_view', 'users_view', 'accounts_view', 'accounts_manage', 
                   'transactions_view', 'transactions_manage', 'reports_view'],
        'user': ['dashboard_view', 'accounts_view', 'transactions_view', 'reports_view']
    };
    
    const permissions = rolePermissions[role] || [];
    permissions.forEach(permission => {
        const checkbox = document.getElementById(`perm_${permission}`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
}