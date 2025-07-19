<?php
session_start();

// اگر کاربر لاگین کرده، به داشبورد هدایت شود
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// بررسی وجود فایل تنظیمات
if (!file_exists('config/database.php')) {
    header('Location: install.php');
    exit;
}

// تست اتصال به دیتابیس
try {
    require_once 'config/database.php';
    $db = getDB();
    
    // بررسی وجود جدول کاربران
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        header('Location: database_simple.php');
        exit;
    }
    
    // اگر همه چیز درست است، به صفحه ورود هدایت شود
    header('Location: login.php');
    
} catch (Exception $e) {
    // اگر مشکلی در دیتابیس وجود دارد
    header('Location: database_simple.php');
}
exit;
?>