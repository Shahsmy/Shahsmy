<?php
// Script to check and reset admin password
echo "<h2>بررسی و تنظیم مجدد رمز عبور admin</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p>✅ اتصال به پایگاه داده برقرار شد</p>";
        
        // Get admin user info
        $stmt = $db->prepare("SELECT id, username, password_hash, failed_login_attempts, locked_until FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p><strong>اطلاعات حساب admin:</strong></p>";
            echo "<p>شناسه: " . $admin['id'] . "</p>";
            echo "<p>نام کاربری: " . $admin['username'] . "</p>";
            echo "<p>تلاش‌های ناموفق: " . $admin['failed_login_attempts'] . "</p>";
            echo "<p>قفل تا: " . ($admin['locked_until'] ? $admin['locked_until'] : 'قفل نیست') . "</p>";
            
            // Test current password
            $testPassword = 'admin123';
            echo "<hr>";
            echo "<h3>تست رمز عبور فعلی:</h3>";
            
            if (password_verify($testPassword, $admin['password_hash'])) {
                echo "<p style='color: green;'>✅ رمز عبور '$testPassword' صحیح است</p>";
            } else {
                echo "<p style='color: red;'>❌ رمز عبور '$testPassword' اشتباه است</p>";
                echo "<p>در حال تنظیم مجدد رمز عبور...</p>";
                
                // Reset password to admin123
                $newPasswordHash = password_hash($testPassword, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin'");
                
                if ($updateStmt->execute([$newPasswordHash])) {
                    echo "<p style='color: green;'>✅ رمز عبور admin به '$testPassword' تنظیم شد</p>";
                } else {
                    echo "<p style='color: red;'>❌ خطا در تنظیم رمز عبور</p>";
                }
            }
            
            // Unlock account anyway
            echo "<hr>";
            echo "<h3>باز کردن قفل حساب:</h3>";
            $unlockStmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin'");
            
            if ($unlockStmt->execute()) {
                echo "<p style='color: green;'>✅ حساب admin باز شد</p>";
            } else {
                echo "<p style='color: red;'>❌ خطا در باز کردن قفل</p>";
            }
            
            // Final verification
            echo "<hr>";
            echo "<h3>تست نهایی:</h3>";
            $finalStmt = $db->prepare("SELECT username, password_hash, failed_login_attempts, locked_until FROM users WHERE username = 'admin'");
            $finalStmt->execute();
            $finalAdmin = $finalStmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($testPassword, $finalAdmin['password_hash'])) {
                echo "<p style='color: green;'>✅ تست نهایی موفق: رمز عبور '$testPassword' کار می‌کند</p>";
                echo "<p>تلاش‌های ناموفق: " . $finalAdmin['failed_login_attempts'] . "</p>";
                echo "<p>وضعیت قفل: " . ($finalAdmin['locked_until'] ? $finalAdmin['locked_until'] : 'باز') . "</p>";
                
                echo "<hr>";
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
                echo "<h3 style='color: #155724; margin-top: 0;'>🎉 همه چیز آماده است!</h3>";
                echo "<p style='color: #155724;'><strong>اطلاعات ورود:</strong></p>";
                echo "<p style='color: #155724;'>نام کاربری: <strong>admin</strong></p>";
                echo "<p style='color: #155724;'>رمز عبور: <strong>admin123</strong></p>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>❌ تست نهایی ناموفق</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ حساب admin پیدا نشد</p>";
            
            // Create admin user
            echo "<hr>";
            echo "<h3>ایجاد حساب admin جدید:</h3>";
            
            $newPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $permissions = '["dashboard_view","users_view","users_create","users_edit","users_delete","accounts_view","accounts_manage","transactions_view","transactions_manage","reports_view","system_settings"]';
            
            $createStmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, permissions, status) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($createStmt->execute(['admin', 'admin@example.com', $newPasswordHash, 'admin', $permissions, 'active'])) {
                echo "<p style='color: green;'>✅ حساب admin جدید ایجاد شد</p>";
            } else {
                echo "<p style='color: red;'>❌ خطا در ایجاد حساب admin</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ خطا در اتصال به پایگاه داده</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>لینک‌های مفید:</h3>";
echo "<p><a href='simple-login.html'>تست ورود ساده</a></p>";
echo "<p><a href='login.html'>صفحه ورود اصلی</a></p>";
echo "<p><a href='test.php'>تست سیستم</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 700px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
    direction: rtl;
}
a {
    color: #007bff;
    text-decoration: none;
    padding: 5px 10px;
    background: #e7f3ff;
    border-radius: 3px;
    margin: 5px;
    display: inline-block;
}
a:hover {
    background: #cce7ff;
}
</style>