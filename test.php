<?php
// Test file to check PHP and database connection
echo "<h2>تست سیستم</h2>";

// Test PHP
echo "<p>✅ PHP در حال کار است - نسخه: " . phpversion() . "</p>";

// Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p>✅ اتصال به پایگاه داده موفقیت‌آمیز</p>";
        
        // Test if users table exists
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ جدول کاربران موجود است</p>";
            
            // Test if admin user exists
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "<p>✅ کاربر admin موجود است</p>";
            } else {
                echo "<p>❌ کاربر admin موجود نیست</p>";
            }
        } else {
            echo "<p>❌ جدول کاربران موجود نیست</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ خطا در اتصال به پایگاه داده: " . $e->getMessage() . "</p>";
}

// Test if API files exist
$apiFiles = ['api/login.php', 'api/logout.php', 'api/session.php', 'api/users.php'];
foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ فایل $file موجود است</p>";
    } else {
        echo "<p>❌ فایل $file موجود نیست</p>";
    }
}

echo "<hr>";
echo "<h3>اطلاعات ورود:</h3>";
echo "<p>نام کاربری: <strong>admin</strong></p>";
echo "<p>رمز عبور: <strong>admin123</strong></p>";
echo "<p><a href='login.html'>رفتن به صفحه ورود</a></p>";
?>