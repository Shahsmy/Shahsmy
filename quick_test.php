<?php
echo "<h2>🧪 تست سریع سیستم</h2>";

// تست 1: بررسی فایل‌های اساسی
$files = [
    'config/database.php',
    'includes/auth.php', 
    'login.php',
    'index.php'
];

echo "<h3>📁 بررسی فایل‌ها:</h3>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file موجود است</p>";
    } else {
        echo "<p style='color: red;'>❌ $file موجود نیست</p>";
    }
}

// تست 2: بررسی توابع
echo "<h3>🔧 بررسی توابع:</h3>";
$functions = ['formatNumber', 'formatDate', 'formatJalaliDate', 'formatFileSize'];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p style='color: green;'>✅ تابع $func تعریف شده</p>";
    } else {
        echo "<p style='color: red;'>❌ تابع $func تعریف نشده</p>";
    }
}

// تست 3: include کردن فایل‌ها
echo "<h3>📥 تست include:</h3>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ config/database.php بارگذاری شد</p>";
    
    require_once 'includes/auth.php';
    echo "<p style='color: green;'>✅ includes/auth.php بارگذاری شد</p>";
    
    echo "<p style='color: green;'><strong>🎉 همه چیز درست است!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
}

// تست 4: تست توابع
echo "<h3>🧮 تست عملکرد توابع:</h3>";
try {
    echo "<p>formatNumber(1234.56): " . formatNumber(1234.56, 2) . "</p>";
    echo "<p>formatDate('2024-01-15'): " . formatDate('2024-01-15') . "</p>";
    echo "<p>formatFileSize(1048576): " . formatFileSize(1048576) . "</p>";
    echo "<p style='color: green;'>✅ تمام توابع کار می‌کنند</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا در توابع: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🚀 مرحله بعد:</h3>";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به صفحه اصلی</a></p>";
echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به صفحه ورود</a></p>";
?>

<style>
body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
h2, h3 { color: #333; }
</style>