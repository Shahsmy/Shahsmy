<?php
/**
 * Installation Script for Currency Exchange System
 * نمونه اسکریپت نصب سیستم مدیریت صرافی
 */

// Prevent running after installation
if (file_exists('config/database.php') && !isset($_GET['force'])) {
    die('سیستم قبلاً نصب شده است. برای نصب مجدد از پارامتر ?force=1 استفاده کنید.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Step 1: Database Configuration
if ($_POST && $step == 1) {
    $host = trim($_POST['host']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $database = trim($_POST['database']);
    
    // Test database connection
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$database`");
        
        // Create config file
        $config_content = "<?php
// Database configuration
define('DB_HOST', '$host');
define('DB_NAME', '$database');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'سیستم مدیریت صرافی');
define('APP_VERSION', '1.0.0');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Tehran');
?>";

        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        file_put_contents('config/database.php', $config_content);
        
        $success = 'اتصال به پایگاه داده با موفقیت برقرار شد!';
        $step = 2;
        
    } catch (PDOException $e) {
        $error = 'خطا در اتصال به پایگاه داده: ' . $e->getMessage();
    }
}

// Step 2: Create Tables
if ($_POST && $step == 2) {
    require_once 'config/database.php';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sql = file_get_contents('database_setup.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $success = 'جداول پایگاه داده با موفقیت ایجاد شدند!';
        $step = 3;
        
    } catch (PDOException $e) {
        $error = 'خطا در ایجاد جداول: ' . $e->getMessage();
    }
}

// Step 3: Create admin user
if ($_POST && $step == 3) {
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];
    $admin_fullname = trim($_POST['admin_fullname']);
    
    if (strlen($admin_password) < 6) {
        $error = 'رمز عبور باید حداقل 6 کاراکتر باشد';
    } else {
        require_once 'config/database.php';
        
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update admin user
            $hashed_password = md5($admin_password);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, full_name = ? WHERE role = 'admin'");
            $stmt->execute([$admin_username, $hashed_password, $admin_fullname]);
            
            $success = 'کاربر مدیر با موفقیت ایجاد شد!';
            $step = 4;
            
        } catch (PDOException $e) {
            $error = 'خطا در ایجاد کاربر مدیر: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم مدیریت صرافی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazir', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { max-width: 600px; margin: 50px auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(45deg, #ff6b6b, #ee5a52); color: white; border-radius: 15px 15px 0 0; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 10px; font-weight: bold; }
        .step.active { background: #28a745; color: white; }
        .step.completed { background: #007bff; color: white; }
        .step.pending { background: #dee2e6; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="card">
            <div class="card-header text-center">
                <h1 class="mb-0">🏦 نصب سیستم مدیریت صرافی</h1>
                <p class="mb-0 mt-2">نسخه ۱.۰.۰</p>
            </div>
            <div class="card-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending' ?>">1</div>
                    <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending' ?>">2</div>
                    <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending' ?>">3</div>
                    <div class="step <?= $step >= 4 ? 'completed' : 'pending' ?>">4</div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <!-- Step 1: Database Configuration -->
                <?php if ($step == 1): ?>
                    <h3>مرحله ۱: تنظیمات پایگاه داده</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">آدرس سرور</label>
                            <input type="text" name="host" class="form-control" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نام کاربری</label>
                            <input type="text" name="username" class="form-control" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نام پایگاه داده</label>
                            <input type="text" name="database" class="form-control" value="currency_exchange" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">ادامه</button>
                    </form>
                <?php endif; ?>
                
                <!-- Step 2: Create Tables -->
                <?php if ($step == 2): ?>
                    <h3>مرحله ۲: ایجاد جداول</h3>
                    <p>جداول پایگاه داده ایجاد خواهند شد.</p>
                    <form method="POST">
                        <button type="submit" class="btn btn-primary w-100">ایجاد جداول</button>
                    </form>
                <?php endif; ?>
                
                <!-- Step 3: Admin User -->
                <?php if ($step == 3): ?>
                    <h3>مرحله ۳: ایجاد کاربر مدیر</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">نام کاربری مدیر</label>
                            <input type="text" name="admin_username" class="form-control" value="admin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور مدیر</label>
                            <input type="password" name="admin_password" class="form-control" required>
                            <div class="form-text">حداقل 6 کاراکتر</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نام کامل مدیر</label>
                            <input type="text" name="admin_fullname" class="form-control" value="مدیر سیستم" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">ایجاد کاربر مدیر</button>
                    </form>
                <?php endif; ?>
                
                <!-- Step 4: Complete -->
                <?php if ($step == 4): ?>
                    <div class="text-center">
                        <h3 class="text-success">🎉 نصب با موفقیت تکمیل شد!</h3>
                        <p>سیستم آماده استفاده است.</p>
                        <div class="alert alert-info">
                            <strong>نکات مهم:</strong><br>
                            • فایل install.php را برای امنیت حذف کنید<br>
                            • از طریق dashboard.php وارد سیستم شوید<br>
                            • تنظیمات شرکت را از بخش تنظیمات کامل کنید
                        </div>
                        <a href="login.php" class="btn btn-success btn-lg">ورود به سیستم</a>
                        <a href="#" onclick="deleteInstallFile()" class="btn btn-danger btn-lg">حذف فایل نصب</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function deleteInstallFile() {
        if (confirm('آیا از حذف فایل نصب اطمینان دارید؟')) {
            fetch('install.php?action=delete_install', {method: 'POST'})
                .then(() => {
                    alert('فایل نصب حذف شد');
                    window.location.href = 'login.php';
                });
        }
    }
    </script>
</body>
</html>

<?php
// Handle delete install file request
if (isset($_GET['action']) && $_GET['action'] == 'delete_install' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    unlink(__FILE__);
    exit('deleted');
}
?>