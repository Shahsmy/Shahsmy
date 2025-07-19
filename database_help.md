# 🔧 راهنمای حل مشکلات دیتابیس

## مشکلات رایج و راه‌حل‌ها

### 1️⃣ خطای "Database connection failed"

#### علل احتمالی:
- MySQL/XAMPP خاموش است
- تنظیمات اتصال اشتباه است
- دیتابیس وجود ندارد

#### راه‌حل:
```bash
# بررسی وضعیت MySQL
sudo systemctl status mysql

# روشن کردن MySQL
sudo systemctl start mysql

# برای XAMPP
sudo /opt/lampp/lampp start
```

### 2️⃣ خطای "Access denied"

#### علل احتمالی:
- نام کاربری یا رمز عبور اشتباه
- کاربر دسترسی ندارد

#### راه‌حل:
1. بررسی تنظیمات در `config/database.php`
2. تست اتصال با phpMyAdmin
3. ایجاد کاربر جدید:

```sql
CREATE USER 'newuser'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON exchange_accounting.* TO 'newuser'@'localhost';
FLUSH PRIVILEGES;
```

### 3️⃣ خطای "Database does not exist"

#### راه‌حل:
```sql
CREATE DATABASE exchange_accounting 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### 4️⃣ خطای "Table doesn't exist"

#### راه‌حل:
1. اجرای فایل `database_setup.sql`
2. یا استفاده از اسکریپت `install.php`

## 🛠️ ابزارهای تشخیص

### فایل‌های تست:
- `test_db.php` - تست کامل اتصال
- `database_simple.php` - تست ساده و ایجاد دیتابیس
- `install.php` - نصب کامل

### مراحل تشخیص:

1. **بررسی MySQL:**
   ```bash
   mysql -u root -p
   SHOW DATABASES;
   ```

2. **تست PHP:**
   ```php
   <?php
   phpinfo();
   // بررسی PDO MySQL extension
   ?>
   ```

3. **تست اتصال:**
   ```php
   try {
       $pdo = new PDO('mysql:host=localhost', 'root', '');
       echo "اتصال موفق!";
   } catch (PDOException $e) {
       echo "خطا: " . $e->getMessage();
   }
   ```

## 🔧 تنظیمات محیط

### XAMPP:
1. Control Panel را باز کنید
2. Apache و MySQL را start کنید
3. از طریق `http://localhost/phpmyadmin` دیتابیس را بررسی کنید

### WAMP:
1. وارپ سرور را اجرا کنید
2. آیکن سبز رنگ باشد
3. MySQL service فعال باشد

### MAMP:
1. MAMP را اجرا کنید
2. Start Servers کلیک کنید
3. WebStart page را باز کنید

## 📋 چک‌لیست حل مشکل

- [ ] MySQL/XAMPP روشن است
- [ ] PHP PDO extension نصب است
- [ ] فایل `config/database.php` وجود دارد
- [ ] تنظیمات database صحیح است
- [ ] دیتابیس ایجاد شده است
- [ ] جداول وجود دارند
- [ ] کاربر ادمین موجود است

## 🚀 راه‌حل سریع

### برای شروع سریع:

1. **اجرای database_simple.php:**
   ```
   http://localhost/your-project/database_simple.php
   ```

2. **اجرای install.php:**
   ```
   http://localhost/your-project/install.php
   ```

3. **ورود با:**
   - نام کاربری: `admin`
   - رمز عبور: `admin123`

## 📞 دریافت کمک

اگر مشکل حل نشد:

1. پیام خطای دقیق را بفرستید
2. نسخه PHP و MySQL را اعلام کنید
3. سیستم عامل خود را بگویید
4. نوع سرور محلی (XAMPP/WAMP/...) را مشخص کنید