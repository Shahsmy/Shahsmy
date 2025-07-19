<?php
echo "<h1>🔧 حل کامل مشکلات دیتابیس</h1>";

// تنظیمات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'exchange_accounting';

try {
    // اتصال به MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ اتصال به MySQL موفق</p>";
    
    // ایجاد یا انتخاب دیتابیس
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    echo "<p style='color: green;'>✅ دیتابیس '$database' آماده</p>";
    
    // ایجاد تمام جداول مورد نیاز
    echo "<h3>📋 ایجاد جداول:</h3>";
    
    // جدول users
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        role ENUM('admin', 'manager', 'cashier', 'viewer') DEFAULT 'viewer',
        permissions JSON,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول users</p>";
    
    // جدول currencies
    $sql = "
    CREATE TABLE IF NOT EXISTS currencies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        currency_code VARCHAR(10) UNIQUE NOT NULL,
        currency_name VARCHAR(50) NOT NULL,
        symbol VARCHAR(10),
        exchange_rate DECIMAL(15,8) DEFAULT 1,
        decimal_places INT DEFAULT 2,
        is_base BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول currencies</p>";
    
    // جدول banks
    $sql = "
    CREATE TABLE IF NOT EXISTS banks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20),
        swift_code VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول banks</p>";
    
    // جدول bank_accounts
    $sql = "
    CREATE TABLE IF NOT EXISTS bank_accounts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bank_id INT NOT NULL,
        account_name VARCHAR(100) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        currency_id INT NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_id) REFERENCES banks(id),
        FOREIGN KEY (currency_id) REFERENCES currencies(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول bank_accounts</p>";
    
    // جدول cash_boxes
    $sql = "
    CREATE TABLE IF NOT EXISTS cash_boxes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        currency_id INT NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0,
        responsible_user_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (currency_id) REFERENCES currencies(id),
        FOREIGN KEY (responsible_user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول cash_boxes</p>";
    
    // جدول contacts
    $sql = "
    CREATE TABLE IF NOT EXISTS contacts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        category ENUM('customer', 'supplier', 'partner', 'other') DEFAULT 'customer',
        bank_name VARCHAR(100),
        bank_account VARCHAR(50),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول contacts</p>";
    
    // جدول deposits
    $sql = "
    CREATE TABLE IF NOT EXISTS deposits (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bank_account_id INT NOT NULL,
        currency_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        deposit_date DATE NOT NULL,
        description TEXT,
        status ENUM('pending', 'allocated', 'completed') DEFAULT 'pending',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
        FOREIGN KEY (currency_id) REFERENCES currencies(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول deposits</p>";
    
    // جدول withdrawals
    $sql = "
    CREATE TABLE IF NOT EXISTS withdrawals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bank_account_id INT NOT NULL,
        currency_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        withdrawal_date DATE NOT NULL,
        description TEXT,
        status ENUM('pending', 'allocated', 'completed') DEFAULT 'pending',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
        FOREIGN KEY (currency_id) REFERENCES currencies(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول withdrawals</p>";
    
    // جدول currency_sales
    $sql = "
    CREATE TABLE IF NOT EXISTS currency_sales (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sale_date DATE NOT NULL,
        sale_time TIME,
        customer_id INT,
        currency_from_id INT NOT NULL,
        currency_to_id INT NOT NULL,
        amount_from DECIMAL(15,4) NOT NULL,
        amount_to DECIMAL(15,4) NOT NULL,
        exchange_rate DECIMAL(15,8) NOT NULL,
        commission DECIMAL(15,2) DEFAULT 0,
        total_received DECIMAL(15,2) NOT NULL,
        payment_method ENUM('cash', 'bank_transfer', 'card') NOT NULL,
        bank_account_id INT,
        cash_box_id INT,
        notes TEXT,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sale_date (sale_date),
        INDEX idx_customer (customer_id),
        INDEX idx_currency_pair (currency_from_id, currency_to_id),
        FOREIGN KEY (customer_id) REFERENCES contacts(id),
        FOREIGN KEY (currency_from_id) REFERENCES currencies(id),
        FOREIGN KEY (currency_to_id) REFERENCES currencies(id),
        FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
        FOREIGN KEY (cash_box_id) REFERENCES cash_boxes(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول currency_sales</p>";
    
    // جدول currency_purchases
    $sql = "
    CREATE TABLE IF NOT EXISTS currency_purchases (
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
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول currency_purchases</p>";
    
    // جدول excel_uploads
    $sql = "
    CREATE TABLE IF NOT EXISTS excel_uploads (
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
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول excel_uploads</p>";
    
    // جدول deposit_allocations
    $sql = "
    CREATE TABLE IF NOT EXISTS deposit_allocations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        currency_sale_id INT NOT NULL,
        deposit_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        description TEXT,
        allocated_by INT NOT NULL,
        allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (currency_sale_id) REFERENCES currency_sales(id),
        FOREIGN KEY (deposit_id) REFERENCES deposits(id),
        FOREIGN KEY (allocated_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول deposit_allocations</p>";
    
    // جدول withdrawal_allocations
    $sql = "
    CREATE TABLE IF NOT EXISTS withdrawal_allocations (
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
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول withdrawal_allocations</p>";
    
    // جدول contact_accounts
    $sql = "
    CREATE TABLE IF NOT EXISTS contact_accounts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        contact_id INT NOT NULL,
        currency_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        transaction_type ENUM('sale', 'purchase', 'deposit', 'withdrawal') NOT NULL,
        reference_type VARCHAR(50),
        reference_id INT,
        description TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (contact_id) REFERENCES contacts(id),
        FOREIGN KEY (currency_id) REFERENCES currencies(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول contact_accounts</p>";
    
    // جدول system_settings
    $sql = "
    CREATE TABLE IF NOT EXISTS system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ جدول system_settings</p>";
    
    echo "<h3>💾 درج داده‌های اولیه:</h3>";
    
    // ارزها
    $currencies = [
        ['IRR', 'ریال ایران', '﷼', 1, 0, 1],
        ['USD', 'دلار آمریکا', '$', 50000, 2, 0],
        ['EUR', 'یورو', '€', 55000, 2, 0],
        ['GBP', 'پوند انگلیس', '£', 65000, 2, 0],
        ['AED', 'درهم امارات', 'د.إ', 13500, 2, 0],
        ['TRY', 'لیر ترکیه', '₺', 1700, 2, 0]
    ];
    
    foreach ($currencies as $currency) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO currencies (currency_code, currency_name, symbol, exchange_rate, decimal_places, is_base) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($currency);
    }
    echo "<p style='color: green;'>✅ ارزهای پیش‌فرض</p>";
    
    // کاربر ادمین
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, role, permissions) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', md5('admin123'), 'مدیر سیستم', 'admin', '["all"]']);
    echo "<p style='color: green;'>✅ کاربر ادمین</p>";
    
    // تنظیمات سیستم
    $settings = [
        ['company_name', 'صرافی نمونه', 'نام شرکت'],
        ['company_address', '', 'آدرس شرکت'],
        ['company_phone', '', 'تلفن شرکت'],
        ['default_currency', '1', 'ارز پیش‌فرض سیستم'],
        ['decimal_places', '2', 'تعداد اعشار برای نمایش مبالغ']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    }
    echo "<p style='color: green;'>✅ تنظیمات پیش‌فرض</p>";
    
    // بررسی نهایی
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>🎉 خلاصه نهایی:</h3>";
    echo "<p><strong>تعداد جداول ایجاد شده:</strong> " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // تست کاربر ادمین
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if ($adminExists) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724;'>🎉 همه چیز آماده است!</h3>";
        echo "<p><strong>اطلاعات ورود:</strong></p>";
        echo "<p><strong>نام کاربری:</strong> admin</p>";
        echo "<p><strong>رمز عبور:</strong> admin123</p>";
        echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>ورود به سیستم</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ خطا در دیتابیس!</h3>";
    echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>راه‌حل:</strong></p>";
    echo "<ul>";
    echo "<li>مطمئن شوید XAMPP روشن است</li>";
    echo "<li>MySQL سرویس فعال باشد</li>";
    echo "<li>تنظیمات اتصال درست باشد</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Tahoma, Arial, sans-serif;
    direction: rtl;
    text-align: right;
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
    background: #f8f9fa;
}
h1, h3 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 8px 0;
    font-size: 16px;
}
ul {
    column-count: 3;
    column-gap: 20px;
}
li {
    margin: 5px 0;
    break-inside: avoid;
}
</style>