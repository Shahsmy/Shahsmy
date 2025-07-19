# 🚀 راهنمای کامل نصب در XAMPP

## 📋 **مرحله 1: نصب XAMPP**

### دانلود XAMPP:
1. به وب‌سایت رسمی XAMPP بروید: https://www.apachefriends.org
2. نسخه مناسب سیستم عامل خود را دانلود کنید:
   - **Windows:** `xampp-windows-x64-installer.exe`
   - **Mac:** `xampp-osx-installer.dmg`
   - **Linux:** `xampp-linux-x64-installer.run`

### نصب XAMPP:
- **Windows:** فایل `.exe` را اجرا کرده و مراحل نصب را دنبال کنید
- **Mac:** فایل `.dmg` را باز کرده و در پوشه Applications کپی کنید
- **Linux:** فایل را قابل اجرا کرده و نصب کنید:
  ```bash
  chmod +x xampp-linux-x64-installer.run
  sudo ./xampp-linux-x64-installer.run
  ```

## 📁 **مرحله 2: آماده‌سازی فایل‌های پروژه**

### کپی فایل‌های پروژه:
1. پوشه XAMPP را پیدا کنید:
   - **Windows:** `C:\xampp\htdocs\`
   - **Mac:** `/Applications/XAMPP/htdocs/`
   - **Linux:** `/opt/lampp/htdocs/`

2. یک پوشه جدید برای پروژه بسازید:
   ```
   htdocs/currency-exchange/
   ```

3. تمام فایل‌های پروژه را در این پوشه کپی کنید:
   ```
   htdocs/currency-exchange/
   ├── assets/
   ├── config/
   ├── includes/
   ├── uploads/
   ├── login.php
   ├── dashboard.php
   ├── install.php
   ├── database_simple.php
   ├── database_setup.sql
   └── ... (سایر فایل‌ها)
   ```

## ⚙️ **مرحله 3: راه‌اندازی XAMPP**

### روشن کردن سرویس‌ها:
1. **XAMPP Control Panel** را اجرا کنید
2. سرویس‌های زیر را **Start** کنید:
   - ✅ **Apache** (وب‌سرور)
   - ✅ **MySQL** (پایگاه داده)

### علائم سبز:
- باید علامت سبز کنار Apache و MySQL نمایش داده شود
- اگر قرمز است، مشکلی در راه‌اندازی وجود دارد

### بررسی کارکرد:
- مرورگر را باز کنید
- آدرس `http://localhost` را تایپ کنید
- باید صفحه خوش‌آمدگویی XAMPP نمایش داده شود

## 🗄️ **مرحله 4: راه‌اندازی پایگاه داده**

### روش 1: استفاده از اسکریپت خودکار (توصیه شده)
1. مرورگر را باز کنید
2. آدرس زیر را تایپ کنید:
   ```
   http://localhost/currency-exchange/database_simple.php
   ```
3. اگر همه چیز سبز شد، به مرحله بعد بروید!

### روش 2: استفاده از اسکریپت نصب کامل
1. آدرس زیر را باز کنید:
   ```
   http://localhost/currency-exchange/install.php
   ```
2. مراحل نصب را دنبال کنید:
   - **Host:** localhost
   - **Username:** root
   - **Password:** (خالی بگذارید)
   - **Database:** exchange_accounting

### روش 3: دستی از طریق phpMyAdmin
1. آدرس `http://localhost/phpmyadmin` را باز کنید
2. روی **"New"** کلیک کنید
3. نام دیتابیس: `exchange_accounting`
4. **Collation:** `utf8mb4_unicode_ci`
5. روی **Create** کلیک کنید
6. فایل `database_setup.sql` را import کنید

## 🔐 **مرحله 5: ورود به سیستم**

### دسترسی به سیستم:
1. آدرس اصلی پروژه:
   ```
   http://localhost/currency-exchange/
   ```

2. صفحه ورود:
   ```
   http://localhost/currency-exchange/login.php
   ```

### اطلاعات ورود پیش‌فرض:
- **نام کاربری:** `admin`
- **رمز عبور:** `admin123`

## 🛠️ **حل مشکلات رایج**

### مشکل 1: Apache شروع نمی‌شود
**علت:** پورت 80 اشغال است
**راه‌حل:**
- Skype را ببندید
- IIS را غیرفعال کنید  
- پورت Apache را تغییر دهید (Config → httpd.conf)

### مشکل 2: MySQL شروع نمی‌شود
**علت:** پورت 3306 اشغال است
**راه‌حل:**
- سرویس‌های MySQL دیگر را ببندید
- پورت MySQL را تغییر دهید (Config → my.ini)

### مشکل 3: صفحه سفید نمایش داده می‌شود
**راه‌حل:**
- فایل `php.ini` را باز کنید
- `display_errors = On` قرار دهید
- Apache را restart کنید

### مشکل 4: خطای 404
**علت:** مسیر فایل اشتباه است
**راه‌حل:**
- مطمئن شوید فایل‌ها در `htdocs/currency-exchange/` هستند
- آدرس را بررسی کنید

## 📁 **ساختار نهایی در XAMPP**

```
C:\xampp\htdocs\currency-exchange\
├── 📂 assets/
│   ├── 📂 css/
│   │   └── style.css
│   └── 📂 js/
│       └── main.js
├── 📂 config/
│   └── database.php
├── 📂 includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── 📂 uploads/
│   └── 📂 excel/
├── 📄 login.php
├── 📄 dashboard.php
├── 📄 users.php
├── 📄 banks.php
├── 📄 currencies.php
├── 📄 cash_boxes.php
├── 📄 contacts.php
├── 📄 currency_sales.php
├── 📄 excel_upload.php
├── 📄 deposit_allocation.php
├── 📄 withdrawal_allocation.php
├── 📄 contact_transactions.php
├── 📄 contacts_summary.php
├── 📄 profit_loss.php
├── 📄 settings.php
├── 📄 install.php
├── 📄 database_simple.php
├── 📄 test_db.php
└── 📄 database_setup.sql
```

## 🌐 **آدرس‌های مهم**

| صفحه | آدرس |
|------|--------|
| **صفحه اصلی** | `http://localhost/currency-exchange/` |
| **ورود** | `http://localhost/currency-exchange/login.php` |
| **داشبورد** | `http://localhost/currency-exchange/dashboard.php` |
| **نصب** | `http://localhost/currency-exchange/install.php` |
| **تست دیتابیس** | `http://localhost/currency-exchange/test_db.php` |
| **phpMyAdmin** | `http://localhost/phpmyadmin` |
| **XAMPP Control** | `http://localhost/dashboard` |

## ⚡ **نکات مهم**

### امنیت:
- ✅ رمز عبور پیش‌فرض را تغییر دهید
- ✅ فایل `install.php` را پس از نصب حذف کنید
- ✅ مجوزهای فایل‌ها را بررسی کنید

### عملکرد:
- ✅ پوشه `uploads/` باید قابل نوشتن باشد
- ✅ PHP Error Reporting را فعال کنید
- ✅ حجم آپلود فایل را بررسی کنید

### پشتیبان‌گیری:
- ✅ از پایگاه داده بک‌آپ بگیرید
- ✅ فایل‌های پروژه را ذخیره کنید

## 🎯 **مراحل سریع (خلاصه)**

1. **XAMPP نصب کنید**
2. **Apache و MySQL را روشن کنید**
3. **فایل‌ها را در `htdocs/currency-exchange/` کپی کنید**
4. **`http://localhost/currency-exchange/database_simple.php` اجرا کنید**
5. **با `admin/admin123` وارد شوید**

## 🆘 **راه‌های دریافت کمک**

اگر مشکلی داشتید:
1. فایل `test_db.php` را اجرا کنید
2. پیام خطا را بفرستید
3. اسکرین‌شات XAMPP Control Panel بگیرید
4. نسخه XAMPP و سیستم عامل را اعلام کنید

**موفق باشید! 🚀**