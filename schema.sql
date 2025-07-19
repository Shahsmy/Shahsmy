-- Accounts Table
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- e.g., asset, liability, equity, income, expense
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00
);

-- Transactions Table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description VARCHAR(255),
    date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    account_id INT NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Transaction Splits Table (for splitting transactions into multiple categories)
CREATE TABLE transaction_splits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    account_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Budgets Table
CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    account_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Invoices Table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) NOT NULL -- e.g., sent, paid, overdue
);

-- Invoice Items Table
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- Users Table (Enhanced)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user', -- admin, manager, user
    permissions TEXT, -- JSON string of permissions
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL
);

-- User Sessions Table
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User Activity Log
CREATE TABLE user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Permissions Table
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50)
);

-- User Permissions Junction Table
CREATE TABLE user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_by INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id),
    UNIQUE KEY unique_user_permission (user_id, permission_id)
);

-- Insert Default Permissions
INSERT INTO permissions (name, description, category) VALUES
('dashboard_view', 'View dashboard', 'dashboard'),
('users_view', 'View users list', 'users'),
('users_create', 'Create new users', 'users'),
('users_edit', 'Edit user information', 'users'),
('users_delete', 'Delete users', 'users'),
('accounts_view', 'View accounts', 'accounting'),
('accounts_manage', 'Manage accounts', 'accounting'),
('transactions_view', 'View transactions', 'accounting'),
('transactions_manage', 'Manage transactions', 'accounting'),
('reports_view', 'View reports', 'reports'),
('system_settings', 'Manage system settings', 'system');

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, password_hash, role, permissions, status) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 
'["dashboard_view","users_view","users_create","users_edit","users_delete","accounts_view","accounts_manage","transactions_view","transactions_manage","reports_view","system_settings"]', 
'active');
