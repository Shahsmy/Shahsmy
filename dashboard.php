<?php
require_once 'includes/auth.php';

// بررسی ورود کاربر
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// دریافت اطلاعات کاربر
$db = getDB();
$user = getCurrentUser();

$page_title = 'داشبورد';
include 'includes/header.php';
?>

<div class="dashboard-content">
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>کاربران</h3>
                    <p class="stat-number">
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
                            $stmt->execute();
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-info">
                    <h3>بانک‌ها</h3>
                    <p class="stat-number">
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM banks WHERE status = 'active'");
                            $stmt->execute();
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3>ارزها</h3>
                    <p class="stat-number">
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM currencies WHERE is_active = 1");
                            $stmt->execute();
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-address-book"></i>
                </div>
                <div class="stat-info">
                    <h3>مخاطبین</h3>
                    <p class="stat-number">
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM contacts WHERE status = 'active'");
                            $stmt->execute();
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tachometer-alt"></i> خوش آمدید!</h5>
                </div>
                <div class="card-body">
                    <div class="welcome-message">
                        <h3>سلام <?= htmlspecialchars($user['full_name']) ?>!</h3>
                        <p>به سیستم مدیریت صرافی خوش آمدید.</p>
                        
                        <div class="quick-links mt-4">
                            <h5>دسترسی سریع:</h5>
                            <div class="row">
                                <?php if (checkPermission('users') || $user['role'] === 'admin'): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="users.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-users"></i> مدیریت کاربران
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('manage_banks')): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="banks.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-university"></i> بانک‌ها
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('manage_currencies')): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="currencies.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-coins"></i> ارزها
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('manage_contacts')): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="contacts.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-address-book"></i> مخاطبین
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
    margin-bottom: 20px;
}

.stat-icon {
    font-size: 3rem;
    color: #007bff;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
    margin: 0;
}

.stat-card h3 {
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 5px;
}

.welcome-message {
    text-align: center;
}

.quick-links .btn {
    margin-bottom: 10px;
}
</style>

<?php include 'includes/footer.php'; ?>