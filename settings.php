<?php
require_once 'includes/auth.php';
checkPermission('manage_settings');

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_company':
                $settings = [
                    'company_name' => trim($_POST['company_name']),
                    'company_address' => trim($_POST['company_address']),
                    'company_phone' => trim($_POST['company_phone']),
                    'company_email' => trim($_POST['company_email']),
                    'company_website' => trim($_POST['company_website']),
                    'company_tax_id' => trim($_POST['company_tax_id'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success = "اطلاعات شرکت با موفقیت بروزرسانی شد";
                break;
                
            case 'update_defaults':
                $settings = [
                    'default_currency' => (int)$_POST['default_currency'],
                    'decimal_places' => (int)$_POST['decimal_places'],
                    'date_format' => $_POST['date_format'],
                    'timezone' => $_POST['timezone'],
                    'language' => $_POST['language']
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success = "تنظیمات پیش‌فرض با موفقیت بروزرسانی شد";
                break;
                
            case 'backup_database':
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = 'backups/' . $backup_file;
                
                // Create backups directory if it doesn't exist
                if (!is_dir('backups')) {
                    mkdir('backups', 0755, true);
                }
                
                // Get database configuration
                $host = DB_HOST;
                $dbname = DB_NAME;
                $username = DB_USER;
                $password = DB_PASS;
                
                // Create backup command
                $command = "mysqldump --host=$host --user=$username --password=$password $dbname > $backup_path";
                
                // Execute backup (note: in production, use more secure methods)
                $output = [];
                $return_var = 0;
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    $success = "پشتیبان‌گیری با موفقیت انجام شد: $backup_file";
                } else {
                    $error = "خطا در پشتیبان‌گیری";
                }
                break;
                
            case 'clear_cache':
                // Clear session cache
                if (isset($_SESSION['cache'])) {
                    unset($_SESSION['cache']);
                }
                
                // Clear any temporary files
                $temp_files = glob('temp/*');
                foreach ($temp_files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                
                $success = "کش سیستم پاک شد";
                break;
        }
    }
}

// Get current settings
function getSetting($key, $default = '') {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

// Get currencies for default selection
$stmt = $db->prepare("SELECT id, name FROM currencies ORDER BY name");
$stmt->execute();
$currencies = $stmt->fetchAll();

// Get system stats
$stats = [];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stats['users'] = $stmt->fetchColumn();

// Total contacts
$stmt = $db->prepare("SELECT COUNT(*) FROM contacts");
$stmt->execute();
$stats['contacts'] = $stmt->fetchColumn();

// Total transactions today
$stmt = $db->prepare("SELECT COUNT(*) FROM contact_accounts WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['transactions_today'] = $stmt->fetchColumn();

// Database size
$stmt = $db->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'database_size' FROM information_schema.tables WHERE table_schema = ?");
$stmt->execute([DB_NAME]);
$stats['database_size'] = $stmt->fetchColumn();

$page_title = "تنظیمات سیستم";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>تنظیمات سیستم</h2>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-3">
            <!-- Settings Navigation -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">دسته‌بندی تنظیمات</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#company" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                        <i class="fas fa-building me-2"></i>اطلاعات شرکت
                    </a>
                    <a href="#defaults" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="fas fa-cog me-2"></i>تنظیمات پیش‌فرض
                    </a>
                    <a href="#backup" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="fas fa-database me-2"></i>پشتیبان‌گیری
                    </a>
                    <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="fas fa-server me-2"></i>اطلاعات سیستم
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Company Information -->
                <div class="tab-pane fade show active" id="company">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">اطلاعات شرکت</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_company">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام شرکت</label>
                                        <input type="text" name="company_name" class="form-control" 
                                               value="<?= htmlspecialchars(getSetting('company_name')) ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">شماره مالیاتی</label>
                                        <input type="text" name="company_tax_id" class="form-control" 
                                               value="<?= htmlspecialchars(getSetting('company_tax_id')) ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">آدرس</label>
                                    <textarea name="company_address" class="form-control" rows="3"><?= htmlspecialchars(getSetting('company_address')) ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">تلفن</label>
                                        <input type="text" name="company_phone" class="form-control" 
                                               value="<?= htmlspecialchars(getSetting('company_phone')) ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ایمیل</label>
                                        <input type="email" name="company_email" class="form-control" 
                                               value="<?= htmlspecialchars(getSetting('company_email')) ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">وب‌سایت</label>
                                        <input type="url" name="company_website" class="form-control" 
                                               value="<?= htmlspecialchars(getSetting('company_website')) ?>">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Default Settings -->
                <div class="tab-pane fade" id="defaults">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">تنظیمات پیش‌فرض</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_defaults">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ارز پیش‌فرض</label>
                                        <select name="default_currency" class="form-select">
                                            <?php foreach ($currencies as $currency): ?>
                                            <option value="<?= $currency['id'] ?>" 
                                                    <?= getSetting('default_currency') == $currency['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($currency['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">تعداد اعشار</label>
                                        <select name="decimal_places" class="form-select">
                                            <option value="0" <?= getSetting('decimal_places', '2') == '0' ? 'selected' : '' ?>>0</option>
                                            <option value="1" <?= getSetting('decimal_places', '2') == '1' ? 'selected' : '' ?>>1</option>
                                            <option value="2" <?= getSetting('decimal_places', '2') == '2' ? 'selected' : '' ?>>2</option>
                                            <option value="3" <?= getSetting('decimal_places', '2') == '3' ? 'selected' : '' ?>>3</option>
                                            <option value="4" <?= getSetting('decimal_places', '2') == '4' ? 'selected' : '' ?>>4</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">فرمت تاریخ</label>
                                        <select name="date_format" class="form-select">
                                            <option value="Y/m/d" <?= getSetting('date_format', 'Y/m/d') == 'Y/m/d' ? 'selected' : '' ?>>سال/ماه/روز</option>
                                            <option value="d/m/Y" <?= getSetting('date_format', 'Y/m/d') == 'd/m/Y' ? 'selected' : '' ?>>روز/ماه/سال</option>
                                            <option value="Y-m-d" <?= getSetting('date_format', 'Y/m/d') == 'Y-m-d' ? 'selected' : '' ?>>سال-ماه-روز</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">منطقه زمانی</label>
                                        <select name="timezone" class="form-select">
                                            <option value="Asia/Tehran" <?= getSetting('timezone', 'Asia/Tehran') == 'Asia/Tehran' ? 'selected' : '' ?>>تهران</option>
                                            <option value="UTC" <?= getSetting('timezone', 'Asia/Tehran') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">زبان</label>
                                        <select name="language" class="form-select">
                                            <option value="fa" <?= getSetting('language', 'fa') == 'fa' ? 'selected' : '' ?>>فارسی</option>
                                            <option value="en" <?= getSetting('language', 'fa') == 'en' ? 'selected' : '' ?>>English</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Maintenance -->
                <div class="tab-pane fade" id="backup">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">پشتیبان‌گیری و نگهداری</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>پشتیبان‌گیری از پایگاه داده</h6>
                                    <p class="text-muted">ایجاد پشتیبان کامل از پایگاه داده</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-download"></i> پشتیبان‌گیری
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>پاک‌سازی کش</h6>
                                    <p class="text-muted">پاک‌سازی فایل‌های موقت و کش سیستم</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-broom"></i> پاک‌سازی
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6>فایل‌های پشتیبان</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>نام فایل</th>
                                            <th>اندازه</th>
                                            <th>تاریخ</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $backup_dir = 'backups/';
                                        if (is_dir($backup_dir)) {
                                            $backups = glob($backup_dir . '*.sql');
                                            foreach ($backups as $backup) {
                                                $filename = basename($backup);
                                                $size = formatFileSize(filesize($backup));
                                                $date = date('Y-m-d H:i:s', filemtime($backup));
                                                echo "<tr>";
                                                echo "<td>$filename</td>";
                                                echo "<td>$size</td>";
                                                echo "<td>$date</td>";
                                                echo "<td><a href='$backup' class='btn btn-sm btn-primary' download>دانلود</a></td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="tab-pane fade" id="system">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">اطلاعات سیستم</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>آمار سیستم</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>تعداد کاربران:</td>
                                            <td><strong><?= number_format($stats['users']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>تعداد مخاطبین:</td>
                                            <td><strong><?= number_format($stats['contacts']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>تراکنش‌های امروز:</td>
                                            <td><strong><?= number_format($stats['transactions_today']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>حجم پایگاه داده:</td>
                                            <td><strong><?= $stats['database_size'] ?> MB</strong></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>اطلاعات سرور</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>نسخه PHP:</td>
                                            <td><strong><?= phpversion() ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>سرور وب:</td>
                                            <td><strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص' ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>سیستم عامل:</td>
                                            <td><strong><?= php_uname('s') . ' ' . php_uname('r') ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>حافظه PHP:</td>
                                            <td><strong><?= ini_get('memory_limit') ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tabs
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="pill"]'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>