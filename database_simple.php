<?php
// تست ساده اتصال پایگاه داده

echo "<h1>تست اتصال ساده به دیتابیس</h1>";

// تنظیمات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'exchange_accounting';

try {
    // تست اتصال اولیه
    echo "<p>🔄 در حال تست اتصال...</p>";
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    echo "<p style='color: green;'>✅ اتصال به MySQL موفق!</p>";
    
    // ایجاد دیتابیس اگر وجود ندارد
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ دیتابیس '$database' آماده است!</p>";
    
    // اتصال به دیتابیس
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ اتصال به دیتابیس '$database' موفق!</p>";
    
    // بررسی جداول
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) == 0) {
        echo "<p style='color: orange;'>⚠️ هیچ جدولی وجود ندارد. در حال ایجاد جداول...</p>";
        
        // ایجاد جدول کاربران
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'manager', 'cashier', 'viewer') DEFAULT 'viewer',
            permissions JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        ";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ جدول users ایجاد شد!</p>";
        
        // اضافه کردن کاربر ادمین
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, role, permissions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', md5('admin123'), 'مدیر سیستم', 'admin', '["all"]']);
        echo "<p style='color: green;'>✅ کاربر ادمین ایجاد شد!</p>";
        echo "<p><strong>نام کاربری:</strong> admin</p>";
        echo "<p><strong>رمز عبور:</strong> admin123</p>";
        
    } else {
        echo "<p style='color: green;'>✅ تعداد جداول موجود: " . count($tables) . "</p>";
        
        // بررسی کاربران
        if (in_array('users', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ تعداد کاربران: $userCount</p>";
        }
    }
    
    echo "<hr>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #155724;'>🎉 همه چیز آماده است!</h3>";
    echo "<p>دیتابیس با موفقیت راه‌اندازی شد.</p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>ورود به سیستم</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ خطا در اتصال!</h3>";
    echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
    
    echo "<h4>راه‌حل‌های احتمالی:</h4>";
    echo "<ul>";
    echo "<li>مطمئن شوید XAMPP یا MySQL در حال اجراست</li>";
    echo "<li>از طریق phpMyAdmin به دیتابیس متصل شوید</li>";
    echo "<li>نام کاربری و رمز عبور را بررسی کنید</li>";
    echo "<li>اگر از XAMPP استفاده می‌کنید، Apache و MySQL را روشن کنید</li>";
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
    max-width: 800px;
    margin: 0 auto;
}
h1 {
    color: #333;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 10px 0;
    font-size: 16px;
}
ul {
    margin-right: 20px;
}
</style>