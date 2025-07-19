<?php
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

// اگر کاربر قبلاً لاگین کرده باشد
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - نرم‌افزار حسابداری صرافی</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: right;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 1.2rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 50px 15px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #e74c3c;
        }
        
        .footer-text {
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .loading {
            display: none;
            margin-right: 10px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h1>نرم‌افزار حسابداری صرافی</h1>
            <p>ورود به سیستم</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">نام کاربری</label>
                <div class="input-group">
                    <input type="text" 
                           name="username" 
                           id="username" 
                           class="form-control" 
                           required
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="input-group">
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           required
                           autocomplete="current-password">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                ورود به سیستم
            </button>
        </form>
        
        <div class="footer-text">
            <p>نسخه 1.0 - تمامی حقوق محفوظ است</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            const loading = loginBtn.querySelector('.loading');
            
            loginBtn.disabled = true;
            loading.style.display = 'inline-block';
            
            // اگر پس از 10 ثانیه پاسخی نیامد، دکمه را فعال کن
            setTimeout(() => {
                loginBtn.disabled = false;
                loading.style.display = 'none';
            }, 10000);
        });
        
        // فوکوس خودکار روی اولین فیلد
        document.getElementById('username').focus();
        
        // مدیریت Enter key
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>