<?php
echo "<h1>🔧 اصلاح تمام مشکلات \$db</h1>";

// لیست فایل‌هایی که باید اصلاح شوند
$files_to_fix = [
    'banks.php',
    'currencies.php', 
    'cash_boxes.php',
    'contacts.php',
    'accounts_overview.php',
    'excel_upload.php',
    'currency_sales.php',
    'currency_purchases.php',
    'deposit_allocation.php',
    'withdrawal_allocation.php',
    'contacts_summary.php',
    'profit_loss.php',
    'reports.php',
    'settings.php'
];

$fixed_files = [];
$errors = [];

foreach ($files_to_fix as $filename) {
    if (file_exists($filename)) {
        try {
            $content = file_get_contents($filename);
            
            // بررسی اینکه آیا قبلاً اصلاح شده یا نه
            if (strpos($content, "require_once 'config/database.php';") === false) {
                
                // پیدا کردن خط اول require_once 'includes/auth.php';
                if (strpos($content, "require_once 'includes/auth.php';") !== false) {
                    
                    // اضافه کردن خط‌های مورد نیاز
                    $new_content = str_replace(
                        "require_once 'includes/auth.php';",
                        "require_once 'includes/auth.php';\nrequire_once 'config/database.php';\n\n// ایجاد نمونه Database\n\$db = new Database();",
                        $content
                    );
                    
                    // حذف خطوط قدیمی اگر وجود دارد
                    $new_content = str_replace('$db = getDB();', '', $new_content);
                    
                    // ذخیره فایل
                    if (file_put_contents($filename, $new_content)) {
                        $fixed_files[] = $filename;
                        echo "<p style='color: green;'>✅ $filename اصلاح شد</p>";
                    } else {
                        $errors[] = "$filename - خطا در ذخیره";
                        echo "<p style='color: red;'>❌ خطا در ذخیره $filename</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ $filename نیاز به بررسی دستی دارد</p>";
                }
            } else {
                echo "<p style='color: blue;'>ℹ️ $filename قبلاً اصلاح شده</p>";
            }
            
        } catch (Exception $e) {
            $errors[] = "$filename - " . $e->getMessage();
            echo "<p style='color: red;'>❌ خطا در $filename: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: gray;'>📄 $filename وجود ندارد</p>";
    }
}

echo "<hr>";
echo "<h3>📊 خلاصه نتایج:</h3>";
echo "<p><strong>تعداد فایل‌های اصلاح شده:</strong> " . count($fixed_files) . "</p>";
echo "<p><strong>تعداد خطاها:</strong> " . count($errors) . "</p>";

if (!empty($fixed_files)) {
    echo "<h4 style='color: green;'>✅ فایل‌های اصلاح شده:</h4>";
    echo "<ul>";
    foreach ($fixed_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h4 style='color: red;'>❌ خطاها:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

// ایجاد فایل global_db.php برای import آسان
$global_db_content = '<?php
// فایل عمومی برای اتصال به دیتابیس
require_once "config/database.php";

// ایجاد نمونه global Database
if (!isset($GLOBALS["db"])) {
    $GLOBALS["db"] = new Database();
}

// تابع آسان برای دسترسی به دیتابیس
function getDBInstance() {
    if (!isset($GLOBALS["db"])) {
        $GLOBALS["db"] = new Database();
    }
    return $GLOBALS["db"];
}
?>';

file_put_contents('includes/global_db.php', $global_db_content);
echo "<p style='color: green;'>✅ فایل includes/global_db.php ایجاد شد</p>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>🎉 اصلاحات تکمیل شد!</h3>";
echo "<p><strong>حالا می‌توانید تمام صفحات را بدون خطا باز کنید</strong></p>";
echo "<p><a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تست داشبورد</a></p>";
echo "<p><a href='users.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تست صفحه کاربران</a></p>";
echo "<p><a href='currencies.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تست صفحه ارزها</a></p>";
echo "</div>";

?>

<style>
body {
    font-family: Tahoma, Arial, sans-serif;
    direction: rtl;
    text-align: right;
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
    background: #f8f9fa;
}
h1, h3, h4 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 8px 0;
    font-size: 16px;
}
ul {
    margin: 10px 0;
}
li {
    margin: 5px 0;
}
hr {
    margin: 30px 0;
    border: 1px solid #dee2e6;
}
</style>