-- نرم‌افزار حسابداری صرافی - ساختار پایگاه داده
-- Exchange Accounting Software Database Schema

-- جدول کاربران با سطوح دسترسی
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'accountant', 'operator') DEFAULT 'operator',
    permissions TEXT, -- JSON format for page permissions
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول بانک‌ها
CREATE TABLE banks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_name VARCHAR(100) NOT NULL,
    bank_code VARCHAR(10),
    swift_code VARCHAR(11),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول حساب‌های بانکی
CREATE TABLE bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_id INT NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    iban VARCHAR(26),
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('current', 'savings', 'business') DEFAULT 'current',
    balance DECIMAL(20, 8) DEFAULT 0,
    currency_id INT DEFAULT 1, -- ریال ایران به عنوان پیش‌فرض
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE RESTRICT
);

-- جدول ارزها
CREATE TABLE currencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    currency_code VARCHAR(3) NOT NULL UNIQUE, -- USD, EUR, IRR, etc.
    currency_name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10),
    decimal_places INT DEFAULT 2,
    exchange_rate DECIMAL(15, 8) DEFAULT 1, -- نرخ به ریال
    is_base BOOLEAN DEFAULT FALSE, -- ریال ایران
    status ENUM('active', 'inactive') DEFAULT 'active',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول صندوق‌ها
CREATE TABLE cash_boxes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    currency_id INT NOT NULL,
    balance DECIMAL(20, 8) DEFAULT 0,
    location VARCHAR(100),
    responsible_user_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    FOREIGN KEY (responsible_user_id) REFERENCES users(id)
);

-- جدول طرف‌حساب‌ها
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contact_name VARCHAR(100) NOT NULL,
    contact_type ENUM('supplier', 'exchanger', 'customer', 'other') NOT NULL,
    company_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    national_id VARCHAR(20),
    economic_code VARCHAR(20),
    bank_account_info TEXT, -- JSON format
    credit_limit DECIMAL(20, 8) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول تراکنش‌های بانکی (اپلود اکسل)
CREATE TABLE bank_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_account_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_time TIME,
    description TEXT,
    branch_code VARCHAR(10),
    user_code VARCHAR(20),
    receipt_number VARCHAR(50),
    withdrawal DECIMAL(20, 8) DEFAULT 0,
    deposit DECIMAL(20, 8) DEFAULT 0,
    balance DECIMAL(20, 8),
    additional_info TEXT,
    psp VARCHAR(50),
    payer_name VARCHAR(100),
    iban VARCHAR(26),
    acceptor_id VARCHAR(50),
    source_card VARCHAR(16),
    dest_card VARCHAR(16),
    tracking_number VARCHAR(50),
    serial_number VARCHAR(50),
    reference_number VARCHAR(50),
    payment_id VARCHAR(50),
    country VARCHAR(50),
    notes TEXT,
    terminal_number VARCHAR(20),
    source_dest_account VARCHAR(50),
    is_processed BOOLEAN DEFAULT FALSE,
    linked_sale_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
);

-- جدول فروش ارز
CREATE TABLE currency_sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_date DATE NOT NULL,
    sale_time TIME DEFAULT NULL,
    customer_id INT,
    currency_from_id INT NOT NULL, -- ارز مبدا
    currency_to_id INT NOT NULL,   -- ارز مقصد
    amount_from DECIMAL(20, 8) NOT NULL, -- مقدار ارز مبدا
    amount_to DECIMAL(20, 8) NOT NULL,   -- مقدار ارز مقصد
    exchange_rate DECIMAL(15, 8) NOT NULL, -- نرخ تبدیل
    commission DECIMAL(20, 8) DEFAULT 0, -- کمیسیون
    total_received DECIMAL(20, 8) NOT NULL, -- کل مبلغ دریافتی
    payment_method ENUM('cash', 'bank_transfer', 'card') DEFAULT 'cash',
    bank_account_id INT NULL, -- در صورت انتقال بانکی
    cash_box_id INT NULL,     -- در صورت نقدی
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES contacts(id),
    FOREIGN KEY (currency_from_id) REFERENCES currencies(id),
    FOREIGN KEY (currency_to_id) REFERENCES currencies(id),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (cash_box_id) REFERENCES cash_boxes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول خرید ارز
CREATE TABLE currency_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_date DATE NOT NULL,
    purchase_time TIME DEFAULT NULL,
    supplier_id INT,
    currency_from_id INT NOT NULL, -- ارز پرداختی
    currency_to_id INT NOT NULL,   -- ارز دریافتی
    amount_from DECIMAL(20, 8) NOT NULL, -- مقدار ارز پرداختی
    amount_to DECIMAL(20, 8) NOT NULL,   -- مقدار ارز دریافتی
    exchange_rate DECIMAL(15, 8) NOT NULL, -- نرخ تبدیل
    commission DECIMAL(20, 8) DEFAULT 0, -- کمیسیون
    total_paid DECIMAL(20, 8) NOT NULL, -- کل مبلغ پرداختی
    payment_method ENUM('cash', 'bank_transfer', 'card') DEFAULT 'cash',
    bank_account_id INT NULL,
    cash_box_id INT NULL,
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES contacts(id),
    FOREIGN KEY (currency_from_id) REFERENCES currencies(id),
    FOREIGN KEY (currency_to_id) REFERENCES currencies(id),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (cash_box_id) REFERENCES cash_boxes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول تطبیق واریزی‌ها با فروش ارز
CREATE TABLE deposit_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_transaction_id INT NOT NULL,
    currency_sale_id INT NOT NULL,
    allocated_amount DECIMAL(20, 8) NOT NULL,
    allocation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    allocated_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (bank_transaction_id) REFERENCES bank_transactions(id),
    FOREIGN KEY (currency_sale_id) REFERENCES currency_sales(id),
    FOREIGN KEY (allocated_by) REFERENCES users(id)
);

-- جدول تطبیق برداشت‌ها
CREATE TABLE withdrawal_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_transaction_id INT NOT NULL,
    contact_id INT NOT NULL,
    allocated_amount DECIMAL(20, 8) NOT NULL,
    currency_id INT, -- در صورت مشخص کردن نرخ ارز
    exchange_rate DECIMAL(15, 8),
    allocation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    allocated_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (bank_transaction_id) REFERENCES bank_transactions(id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    FOREIGN KEY (allocated_by) REFERENCES users(id)
);

-- جدول حساب‌های طرف‌حساب‌ها (برای ردیابی بدهی/بستانکاری)
CREATE TABLE contact_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contact_id INT NOT NULL,
    currency_id INT NOT NULL,
    balance DECIMAL(20, 8) DEFAULT 0, -- مثبت: بستانکار، منفی: بدهکار
    last_transaction_date TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    UNIQUE KEY unique_contact_currency (contact_id, currency_id)
);

-- جدول لاگ تغییرات موجودی
CREATE TABLE balance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_type ENUM('bank_account', 'cash_box', 'contact_account') NOT NULL,
    account_id INT NOT NULL, -- ID حساب مربوطه
    currency_id INT NOT NULL,
    old_balance DECIMAL(20, 8) NOT NULL,
    new_balance DECIMAL(20, 8) NOT NULL,
    change_amount DECIMAL(20, 8) NOT NULL,
    change_type ENUM('deposit', 'withdrawal', 'transfer', 'adjustment') NOT NULL,
    reference_type VARCHAR(50), -- نوع تراکنش مرجع
    reference_id INT, -- ID تراکنش مرجع
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول تنظیمات سیستم
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- جدول خرید ارز
CREATE TABLE currency_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_date DATE NOT NULL,
    purchase_time TIME,
    supplier_id INT,
    currency_from_id INT NOT NULL,
    currency_to_id INT NOT NULL,
    amount_from DECIMAL(15,4) NOT NULL,
    amount_to DECIMAL(15,4) NOT NULL,
    exchange_rate DECIMAL(15,8) NOT NULL,
    commission DECIMAL(15,2) DEFAULT 0,
    total_paid DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'card') NOT NULL,
    bank_account_id INT,
    cash_box_id INT,
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_supplier (supplier_id),
    INDEX idx_currency_pair (currency_from_id, currency_to_id),
    FOREIGN KEY (supplier_id) REFERENCES contacts(id),
    FOREIGN KEY (currency_from_id) REFERENCES currencies(id),
    FOREIGN KEY (currency_to_id) REFERENCES currencies(id),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (cash_box_id) REFERENCES cash_boxes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول بارگذاری فایل‌های اکسل
CREATE TABLE excel_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    upload_type ENUM('deposits_withdrawals', 'deposits_only', 'withdrawals_only') NOT NULL,
    bank_account_id INT,
    upload_date DATE NOT NULL,
    status ENUM('pending', 'processing', 'processed', 'error') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    total_records INT DEFAULT 0,
    success_records INT DEFAULT 0,
    error_records INT DEFAULT 0,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- جدول تخصیص برداشت‌ها
CREATE TABLE withdrawal_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contact_id INT NOT NULL,
    withdrawal_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    allocated_by INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    FOREIGN KEY (withdrawal_id) REFERENCES withdrawals(id),
    FOREIGN KEY (allocated_by) REFERENCES users(id)
);

-- جدول تنظیمات سیستم
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- درج داده‌های اولیه
INSERT INTO currencies (currency_code, currency_name, symbol, decimal_places, is_base) VALUES
('IRR', 'ریال ایران', '﷼', 0, TRUE),
('USD', 'دلار آمریکا', '$', 2, FALSE),
('EUR', 'یورو', '€', 2, FALSE),
('GBP', 'پوند انگلیس', '£', 2, FALSE),
('AED', 'درهم امارات', 'د.إ', 2, FALSE),
('TRY', 'لیر ترکیه', '₺', 2, FALSE);

INSERT INTO users (username, password, full_name, role, permissions) VALUES
('admin', MD5('admin123'), 'مدیر سیستم', 'admin', '["all"]');

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('company_name', 'صرافی نمونه', 'نام شرکت'),
('company_address', '', 'آدرس شرکت'),
('company_phone', '', 'تلفن شرکت'),
('default_currency', '1', 'ارز پیش‌فرض سیستم'),
('decimal_places', '2', 'تعداد اعشار برای نمایش مبالغ');