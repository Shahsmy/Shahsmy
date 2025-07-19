# راهنمای حل مشکل

## مراحل حل مشکل "لود مداوم":

### ۱. بررسی محیط
ابتدا فایل `test.php` را در مرورگر باز کنید:
```
http://localhost/test.php
```

### ۲. اگر test.php کار نمی‌کند:
- PHP نصب نیست یا فعال نیست
- وب سرور (Apache/Nginx) در حال اجرا نیست
- فایل‌ها در مسیر اشتباه قرار دارند

### ۳. اگر test.php کار می‌کند اما خطای پایگاه داده دارید:
فایل `config/database.php` را ویرایش کنید:
```php
private $host = 'localhost';
private $db_name = 'dashboard_db';  // نام پایگاه داده خود را وارد کنید
private $username = 'root';         // نام کاربری MySQL
private $password = '';             // رمز عبور MySQL
```

### ۴. ایجاد پایگاه داده:
```sql
CREATE DATABASE dashboard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

سپس فایل `schema.sql` را وارد کنید.

### ۵. بررسی Console در مرورگر:
1. صفحه `login.html` را باز کنید
2. F12 را فشار دهید
3. به تب Console بروید
4. سعی کنید وارد شوید
5. پیام‌های خطا را بررسی کنید

### ۶. تست دستی:
در Console مرورگر تایپ کنید:
```javascript
testAPI()
```

### ۷. اگر همچنان مشکل دارید:
در Console تایپ کنید:
```javascript
testLogin()
```

## اطلاعات ورود پیش‌فرض:
- نام کاربری: `admin`
- رمز عبور: `admin123`

## ساختار فایل‌های مورد نیاز:
```
/
├── config/database.php
├── classes/User.php
├── classes/Session.php
├── api/login.php
├── api/logout.php
├── api/session.php
├── api/users.php
├── css/style.css
├── css/login.css
├── css/dashboard.css
├── js/auth.js
├── js/login.js
├── js/dashboard.js
├── js/session-manager.js
├── login.html
├── dashboard.html
├── index.html
├── test.php
└── schema.sql
```

## رایج‌ترین مشکلات:

### ۱. خطای 404 در API:
- مسیر فایل‌های API اشتباه است
- فایل‌های PHP در جای درست قرار ندارند

### ۲. خطای JSON Parse:
- PHP خطا دارد
- خروجی PHP HTML به جای JSON است

### ۳. خطای CORS:
- از `http://localhost` استفاده کنید نه `file://`

### ۴. خطای پایگاه داده:
- اطلاعات اتصال در `config/database.php` اشتباه است
- پایگاه داده ایجاد نشده
- جدول‌ها ایجاد نشده‌اند