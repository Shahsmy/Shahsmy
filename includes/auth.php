<?php
session_start();

// تشخیص مسیر صحیح برای فایل database.php
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    die('فایل تنظیمات پایگاه داده یافت نشد!');
}

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? AND status = 'active'",
            [$username]
        );
        
        if ($user && md5($password) === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['permissions'] = json_decode($user['permissions'], true);
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasPermission($page) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // ادمین به همه صفحات دسترسی دارد
        if ($_SESSION['role'] === 'admin') {
            return true;
        }
        
        // بررسی دسترسی‌های خاص
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($page, $permissions) || in_array('all', $permissions);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requirePermission($page) {
        $this->requireLogin();
        if (!$this->hasPermission($page)) {
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role'],
                'permissions' => $_SESSION['permissions']
            ];
        }
        return null;
    }
}

// فانکشن‌های کمکی
function checkPermission($page) {
    $auth = new Auth();
    return $auth->hasPermission($page);
}

function requirePermission($page) {
    $auth = new Auth();
    $auth->requirePermission($page);
}

function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}

// تابع نمایش تاریخ شمسی
function jalaliDate($date, $showTime = false) {
    if (!$date) return '';
    
    // شبیه‌سازی تبدیل تاریخ میلادی به شمسی
    $timestamp = strtotime($date);
    $gregorianDate = date('Y-m-d', $timestamp);
    
    if ($showTime) {
        return $gregorianDate . ' ' . date('H:i', $timestamp);
    }
    
    return $gregorianDate;
}

// تابع نمایش حجم فایل
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// تابع نمایش اعداد فارسی
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, '.', ',');
}
?>