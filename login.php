<?php
session_start();

// اگر کاربر لاگین کرده به داشبورد هدایت شود
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'نام کاربری و رمز عبور الزامی است';
    } else {
        // تلاش برای اتصال به دیتابیس
        try {
            require_once 'config/database.php';
            $db = getDB();
            
            // جستجوی کاربر
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && md5($password) === $user['password']) {
                // ورود موفق
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['permissions'] = json_decode($user['permissions'], true);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است';
            }
            
        } catch (Exception $e) {
            $error = 'خطا در اتصال به پایگاه داده. لطفاً database_simple.php را اجرا کنید.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود - سیستم مدیریت صرافی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .login-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #f1f3f4;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            color: white;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .error-alert {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .demo-info {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .setup-links {
            text-align: center;
            margin-top: 15px;
        }
        .setup-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        .setup-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="mb-0">
                    <i class="fas fa-university"></i>
                    سیستم مدیریت صرافی
                </h1>
                <p class="mb-0 mt-2">ورود به سیستم</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <div class="demo-info">
                    <strong>🔑 اطلاعات ورود پیش‌فرض:</strong><br>
                    <strong>نام کاربری:</strong> admin<br>
                    <strong>رمز عبور:</strong> admin123
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            نام کاربری
                        </label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'admin' ?>" 
                               required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            رمز عبور
                        </label>
                        <input type="password" name="password" class="form-control" 
                               value="admin123" required>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        ورود به سیستم
                    </button>
                </form>
                
                <div class="setup-links">
                    <strong>نصب و راه‌اندازی:</strong><br>
                    <a href="database_simple.php">
                        <i class="fas fa-database"></i>
                        راه‌اندازی سریع
                    </a>
                    <a href="install.php">
                        <i class="fas fa-cog"></i>
                        نصب کامل
                    </a>
                    <a href="test_db.php">
                        <i class="fas fa-vial"></i>
                        تست اتصال
                    </a>
                </div>
                
                <div class="mt-4 text-center text-muted">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        سیستم مدیریت جامع صرافی - نسخه ۱.۰
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>