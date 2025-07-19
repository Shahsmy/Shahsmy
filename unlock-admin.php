<?php
// Script to unlock admin account
echo "<h2>باز کردن قفل حساب admin</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p>✅ اتصال به پایگاه داده برقرار شد</p>";
        
        // Check current status of admin account
        $stmt = $db->prepare("SELECT username, failed_login_attempts, locked_until FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p><strong>وضعیت فعلی حساب admin:</strong></p>";
            echo "<p>تلاش‌های ناموفق: " . $admin['failed_login_attempts'] . "</p>";
            echo "<p>قفل تا: " . ($admin['locked_until'] ? $admin['locked_until'] : 'قفل نیست') . "</p>";
            
            // Unlock the admin account
            $updateQuery = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin'";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute()) {
                echo "<p style='color: green;'>✅ <strong>حساب admin با موفقیت باز شد!</strong></p>";
                
                // Verify the unlock
                $stmt = $db->prepare("SELECT username, failed_login_attempts, locked_until FROM users WHERE username = 'admin'");
                $stmt->execute();
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<p><strong>وضعیت جدید:</strong></p>";
                echo "<p>تلاش‌های ناموفق: " . $admin['failed_login_attempts'] . "</p>";
                echo "<p>قفل تا: " . ($admin['locked_until'] ? $admin['locked_until'] : 'قفل نیست') . "</p>";
                
            } else {
                echo "<p style='color: red;'>❌ خطا در باز کردن قفل حساب</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ حساب admin پیدا نشد</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ خطا در اتصال به پایگاه داده</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>مراحل بعدی:</h3>";
echo "<ol>";
echo "<li>حالا می‌توانید با اطلاعات زیر وارد شوید:</li>";
echo "<li><strong>نام کاربری:</strong> admin</li>";
echo "<li><strong>رمز عبور:</strong> admin123</li>";
echo "<li><a href='simple-login.html'>رفتن به صفحه ورود ساده</a></li>";
echo "<li><a href='login.html'>رفتن به صفحه ورود اصلی</a></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>نکته:</h3>";
echo "<p>اگر این مشکل دوباره پیش آمد، احتمالاً به دلیل وارد کردن رمز عبور اشتباه است.</p>";
echo "<p>بعد از 5 تلاش ناموفق، حساب برای 30 دقیقه قفل می‌شود.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
    direction: rtl;
}
</style>