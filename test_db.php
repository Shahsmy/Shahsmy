<?php
/**
 * اسکریپت تست اتصال پایگاه داده
 * برای تشخیص و حل مشکلات اتصال
 */

echo "<h2>🔍 تست اتصال پایگاه داده</h2>";

// بررسی وجود فایل تنظیمات
if (!file_exists('config/database.php')) {
    echo "<div style='color: red;'>❌ فایل config/database.php یافت نشد!</div>";
    echo "<p>لطفاً ابتدا install.php را اجرا کنید.</p>";
    exit;
}

require_once 'config/database.php';

echo "<h3>📊 تنظیمات فعلی:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
echo "<li><strong>Username:</strong> " . DB_USER . "</li>";
echo "<li><strong>Password:</strong> " . (empty(DB_PASS) ? "(خالی)" : "***") . "</li>";
echo "</ul>";

echo "<h3>🔌 تست اتصال:</h3>";

// تست 1: اتصال به MySQL (بدون دیتابیس)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    echo "<div style='color: green;'>✅ اتصال به MySQL موفق</div>";
    
    // تست 2: بررسی وجود دیتابیس
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green;'>✅ دیتابیس '" . DB_NAME . "' وجود دارد</div>";
        
        // تست 3: اتصال به دیتابیس
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            echo "<div style='color: green;'>✅ اتصال به دیتابیس موفق</div>";
            
            // تست 4: بررسی جداول
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($tables) > 0) {
                echo "<div style='color: green;'>✅ جداول یافت شد (" . count($tables) . " جدول)</div>";
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                }
                echo "</ul>";
                
                // تست 5: تست تابع getDB()
                try {
                    $db = getDB();
                    echo "<div style='color: green;'>✅ تابع getDB() کار می‌کند</div>";
                    
                    // تست 6: بررسی جدول کاربران
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
                    $stmt->execute();
                    $userCount = $stmt->fetchColumn();
                    echo "<div style='color: green;'>✅ تعداد کاربران: $userCount</div>";
                    
                    echo "<hr>";
                    echo "<div style='color: green; font-size: 18px;'><strong>🎉 همه چیز درست کار می‌کند!</strong></div>";
                    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به صفحه ورود</a></p>";
                    
                } catch (Exception $e) {
                    echo "<div style='color: red;'>❌ خطا در تابع getDB(): " . $e->getMessage() . "</div>";
                }
                
            } else {
                echo "<div style='color: orange;'>⚠️ هیچ جدولی یافت نشد - نیاز به اجرای database_setup.sql</div>";
                echo "<p><a href='install.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>اجرای اسکریپت نصب</a></p>";
            }
            
        } catch (PDOException $e) {
            echo "<div style='color: red;'>❌ خطا در اتصال به دیتابیس: " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ دیتابیس '" . DB_NAME . "' وجود ندارد</div>";
        echo "<h3>🛠️ حل مشکل:</h3>";
        echo "<p>گزینه 1: دیتابیس را دستی ایجاد کنید:</p>";
        echo "<code>CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code>";
        echo "<p>گزینه 2: <a href='install.php'>اسکریپت نصب خودکار</a> را اجرا کنید</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ خطا در اتصال به MySQL: " . $e->getMessage() . "</div>";
    
    echo "<h3>🛠️ راه‌حل‌های احتمالی:</h3>";
    echo "<ul>";
    echo "<li>مطمئن شوید MySQL/XAMPP در حال اجراست</li>";
    echo "<li>نام کاربری و رمز عبور را بررسی کنید</li>";
    echo "<li>آدرس سرور (host) را بررسی کنید</li>";
    echo "<li>تنظیمات فایروال را بررسی کنید</li>";
    echo "</ul>";
}

// نمایش اطلاعات PHP
echo "<h3>ℹ️ اطلاعات محیط:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>PDO MySQL:</strong> " . (extension_loaded('pdo_mysql') ? '✅ فعال' : '❌ غیرفعال') . "</li>";
echo "<li><strong>Current Directory:</strong> " . getcwd() . "</li>";
echo "</ul>";

?>

<style>
body { font-family: 'Tahoma', Arial, sans-serif; direction: rtl; text-align: right; padding: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
ul { list-style-type: disc; margin-right: 20px; }
code { background: #f4f4f4; padding: 5px; border-radius: 3px; font-family: monospace; }
</style>