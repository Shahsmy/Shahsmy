<?php
echo "<h1>🧪 تست کلاس Database</h1>";

require_once 'config/database.php';

try {
    // تست اتصال مستقیم
    echo "<h3>1️⃣ تست getDB():</h3>";
    $pdo = getDB();
    echo "<p style='color: green;'>✅ getDB() کار می‌کند</p>";
    
    // تست کلاس Database
    echo "<h3>2️⃣ تست کلاس Database:</h3>";
    $db = new Database();
    
    // تست connect
    $connection = $db->connect();
    echo "<p style='color: green;'>✅ connect() کار می‌کند</p>";
    
    // تست prepare
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    echo "<p style='color: green;'>✅ prepare() کار می‌کند</p>";
    
    // تست query
    $result = $db->query("SELECT COUNT(*) as user_count FROM users");
    echo "<p style='color: green;'>✅ query() کار می‌کند</p>";
    
    // تست fetchOne
    $userData = $db->fetchOne("SELECT COUNT(*) as user_count FROM users");
    echo "<p style='color: green;'>✅ fetchOne() کار می‌کند - تعداد کاربران: " . $userData['user_count'] . "</p>";
    
    // تست fetchAll
    $allUsers = $db->fetchAll("SELECT username, full_name FROM users LIMIT 5");
    echo "<p style='color: green;'>✅ fetchAll() کار می‌کند - " . count($allUsers) . " کاربر پیدا شد</p>";
    
    // نمایش کاربران
    if (!empty($allUsers)) {
        echo "<h4>👥 کاربران موجود:</h4>";
        echo "<ul>";
        foreach ($allUsers as $user) {
            echo "<li>{$user['full_name']} ({$user['username']})</li>";
        }
        echo "</ul>";
    }
    
    // تست جداول
    echo "<h3>3️⃣ بررسی جداول:</h3>";
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<p style='color: green;'>✅ " . count($tables) . " جدول موجود است</p>";
    
    echo "<h4>📋 لیست جداول:</h4>";
    echo "<div style='column-count: 3; column-gap: 20px;'>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<p>• $tableName</p>";
    }
    echo "</div>";
    
    // تست ارزها
    echo "<h3>4️⃣ تست ارزها:</h3>";
    $currencies = $db->fetchAll("SELECT currency_code, currency_name, symbol FROM currencies");
    echo "<p style='color: green;'>✅ " . count($currencies) . " ارز موجود است</p>";
    
    if (!empty($currencies)) {
        echo "<h4>💰 ارزهای موجود:</h4>";
        echo "<ul>";
        foreach ($currencies as $currency) {
            echo "<li>{$currency['currency_name']} ({$currency['currency_code']}) - {$currency['symbol']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 همه تست‌ها موفق!</h3>";
    echo "<p><strong>کلاس Database کامل کار می‌کند</strong></p>";
    echo "<p><a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به داشبورد</a></p>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>رفتن به صفحه ورود</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ خطا!</h3>";
    echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>راه‌حل:</strong></p>";
    echo "<ul>";
    echo "<li>ابتدا fix_database.php را اجرا کنید</li>";
    echo "<li>XAMPP روشن باشد</li>";
    echo "<li>MySQL فعال باشد</li>";
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
    max-width: 1200px;
    margin: 0 auto;
    background: #f8f9fa;
}
h1, h3, h4 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 8px 0;
    font-size: 16px;
}
ul {
    margin: 10px 0;
}
li {
    margin: 5px 0;
}
a {
    margin: 5px;
}
</style>