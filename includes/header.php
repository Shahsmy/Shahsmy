<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$db = new Database();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'نرم‌افزار حسابداری صرافی'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h3>صرافی حسابداری</h3>
                <div class="subtitle">سیستم مدیریت جامع</div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>داشبورد</span>
                    </a>
                </li>
                
                <?php if (checkPermission('users') || $currentUser['role'] === 'admin'): ?>
                <li>
                    <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>مدیریت کاربران</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('banks')): ?>
                <li>
                    <a href="banks.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'banks.php' ? 'active' : ''; ?>">
                        <i class="fas fa-university"></i>
                        <span>بانک‌ها و حساب‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('currencies')): ?>
                <li>
                    <a href="currencies.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'currencies.php' ? 'active' : ''; ?>">
                        <i class="fas fa-coins"></i>
                        <span>مدیریت ارزها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('cash_boxes')): ?>
                <li>
                    <a href="cash_boxes.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'cash_boxes.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cash-register"></i>
                        <span>صندوق‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('contacts')): ?>
                <li>
                    <a href="contacts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'contacts.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-book"></i>
                        <span>طرف‌حساب‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('accounts_overview')): ?>
                <li>
                    <a href="accounts_overview.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'accounts_overview.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>مرور حساب‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('excel_upload')): ?>
                <li>
                    <a href="excel_upload.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'excel_upload.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-excel"></i>
                        <span>بارگذاری اکسل</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('currency_sales')): ?>
                <li>
                    <a href="currency_sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'currency_sales.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-area"></i>
                        <span>فروش ارز</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('deposit_allocation')): ?>
                <li>
                    <a href="deposit_allocation.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'deposit_allocation.php' ? 'active' : ''; ?>">
                        <i class="fas fa-link"></i>
                        <span>تطبیق واریزی‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('withdrawal_allocation')): ?>
                <li>
                    <a href="withdrawal_allocation.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'withdrawal_allocation.php' ? 'active' : ''; ?>">
                        <i class="fas fa-unlink"></i>
                        <span>تطبیق برداشت‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('contacts_summary')): ?>
                <li>
                    <a href="contacts_summary.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'contacts_summary.php' ? 'active' : ''; ?>">
                        <i class="fas fa-balance-scale"></i>
                        <span>خلاصه طرف‌حساب‌ها</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('profit_loss')): ?>
                <li>
                    <a href="profit_loss.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profit_loss.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>سود و زیان</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('reports')): ?>
                <li>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>گزارشات</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (checkPermission('settings') || $currentUser['role'] === 'admin'): ?>
                <li>
                    <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>تنظیمات</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <h1><?php echo $pageTitle ?? 'داشبورد'; ?></h1>
                <div class="user-info">
                    <span class="user-name">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </span>
                    <span class="badge badge-info"><?php echo $currentUser['role']; ?></span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        خروج
                    </a>
                </div>
            </div>
            
            <div class="content-area">
                <?php
                // نمایش پیام‌های سیستم
                if (isset($_GET['success'])) {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_GET['success']) . '</div>';
                }
                if (isset($_GET['error'])) {
                    $errorMessage = $_GET['error'];
                    if ($errorMessage === 'access_denied') {
                        $errorMessage = 'شما به این بخش دسترسی ندارید';
                    }
                    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($errorMessage) . '</div>';
                }
                if (isset($_GET['warning'])) {
                    echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_GET['warning']) . '</div>';
                }
                if (isset($_GET['info'])) {
                    echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($_GET['info']) . '</div>';
                }
                ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

// بازیابی وضعیت sidebar
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.getElementById('sidebar').classList.add('collapsed');
    }
});
</script>