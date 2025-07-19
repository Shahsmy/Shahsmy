# 🏦 سیستم مدیریت صرافی
## نرم‌افزار حسابداری جامع برای صرافی‌ها

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🚀 **ویژگی‌های کلیدی**

### 📊 **مدیریت کامل**
- ✅ **مدیریت کاربران** با سطوح دسترسی مختلف
- ✅ **مدیریت بانک‌ها و حساب‌های بانکی**
- ✅ **مدیریت ارزها** با نرخ‌های تبدیل لحظه‌ای
- ✅ **مدیریت صندوق‌های نقدی**
- ✅ **مدیریت طرف‌حساب‌ها** (مشتریان، تامین‌کنندگان، صرافان)

### 💰 **عملیات صرافی**
- ✅ **فروش ارز** با محاسبه خودکار سود/زیان و روش‌های پرداخت متعدد
- ✅ **خرید ارز** از تامین‌کنندگان
- ✅ **تخصیص واریزی‌ها** به فروش‌ها با اعتبارسنجی و پیگیری وضعیت
- ✅ **تخصیص برداشت‌ها** به طرف‌حساب‌ها با مدیریت موجودی
- ✅ **محاسبه کمیسیون** و سود نرخ ارز
- ✅ **پیگیری تراکنش‌های مخاطبین** با فیلترهای پیشرفته

### 📁 **مدیریت اسناد**
- ✅ **بارگذاری فایل‌های Excel** با پردازش خودکار و تاریخچه
- ✅ **پشتیبانی از فرمت‌های** Excel (.xlsx, .xls) و CSV
- ✅ **شبیه‌سازی پردازش** فایل‌ها با نمایش پیشرفت
- ✅ **مدیریت تاریخچه بارگذاری** فایل‌ها

### 📈 **گزارش‌گیری**
- ✅ **داشبورد تحلیلی** با نمودارهای تعاملی
- ✅ **گزارش سود و زیان** تفصیلی با Chart.js
- ✅ **خلاصه موجودی طرف‌حساب‌ها** با فیلترهای پیشرفته
- ✅ **تراکنش‌های مخاطبین** با قابلیت صدور Excel
- ✅ **آمار عملکرد** روزانه، ماهانه، سالانه

### ⚙️ **تنظیمات سیستم**
- ✅ **پیکربندی اطلاعات شرکت** و تنظیمات پیش‌فرض
- ✅ **پشتیبان‌گیری** و بازیابی پایگاه داده
- ✅ **مدیریت کش سیستم** و پاک‌سازی فایل‌های موقت
- ✅ **آمار سیستم** و اطلاعات سرور

---

## 🛠️ **نصب و راه‌اندازی**

### پیش‌نیازها
- **PHP 7.4+**
- **MySQL 5.7+**
- **Apache/Nginx**
- **XAMPP** (برای محیط توسعه)

### مراحل نصب

#### 1️⃣ **کلون پروژه**
```bash
git clone https://github.com/your-repo/currency-exchange-system.git
cd currency-exchange-system
```

#### 2️⃣ **تنظیم پایگاه داده**
1. ایجاد پایگاه داده جدید:
```sql
CREATE DATABASE currency_exchange_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. اجرای فایل‌های پایگاه داده:
```bash
mysql -u root -p currency_exchange_db < database_setup.sql
```

#### 3️⃣ **تنظیم فایل پیکربندی**
فایل `config/database.php` را ویرایش کنید:
```php
private $host = 'localhost';
private $database = 'currency_exchange_db';
private $username = 'your_username';
private $password = 'your_password';
```

#### 4️⃣ **تنظیم مجوزها**
```bash
chmod 755 -R .
chmod 777 uploads/
```

#### 5️⃣ **دسترسی به سیستم**
- **URL:** `http://localhost/currency-exchange-system`
- **نام کاربری:** `admin`
- **رمز عبور:** `admin123`

---

## 📁 **ساختار پروژه**

```
currency-exchange-system/
├── 📂 assets/
│   ├── 📂 css/
│   │   └── style.css          # استایل‌های اصلی
│   └── 📂 js/
│       └── main.js            # جاوااسکریپت‌های تعاملی
├── 📂 config/
│   └── database.php           # تنظیمات پایگاه داده
├── 📂 includes/
│   ├── auth.php              # سیستم احراز هویت
│   ├── header.php            # هدر و منوی کناری
│   └── footer.php            # فوتر
├── 📂 uploads/
│   └── 📂 excel/             # فایل‌های اکسل بارگذاری شده
├── 📄 login.php              # صفحه ورود
├── 📄 dashboard.php          # داشبورد اصلی
├── 📄 users.php              # مدیریت کاربران
├── 📄 banks.php              # مدیریت بانک‌ها
├── 📄 currencies.php         # مدیریت ارزها
├── 📄 cash_boxes.php         # مدیریت صندوق‌ها
├── 📄 contacts.php           # مدیریت طرف‌حساب‌ها
├── 📄 currency_sales.php     # فروش ارز
├── 📄 excel_upload.php       # بارگذاری فایل‌ها
├── 📄 deposit_allocation.php # تخصیص واریزی‌ها
├── 📄 withdrawal_allocation.php # تخصیص برداشت‌ها
├── 📄 contact_transactions.php # تراکنش‌های مخاطبین
├── 📄 contacts_summary.php   # خلاصه طرف‌حساب‌ها
├── 📄 profit_loss.php        # گزارش سود و زیان
├── 📄 settings.php           # تنظیمات سیستم
└── 📄 database_setup.sql     # فایل ایجاد پایگاه داده
```

---

## 👥 **مدیریت کاربران**

### سطوح دسترسی
- **👑 Admin:** دسترسی کامل به تمامی بخش‌ها
- **📊 Manager:** دسترسی به گزارش‌ها و مدیریت محدود
- **💰 Cashier:** دسترسی به عملیات روزانه
- **👀 Viewer:** فقط مشاهده گزارش‌ها

### مجوزهای سیستم
```json
{
  "dashboard": "داشبورد اصلی",
  "users": "مدیریت کاربران", 
  "banks": "مدیریت بانک‌ها",
  "currencies": "مدیریت ارزها",
  "cash_boxes": "مدیریت صندوق‌ها",
  "contacts": "مدیریت طرف‌حساب‌ها",
  "currency_sales": "فروش ارز",
  "excel_upload": "بارگذاری فایل",
  "deposit_allocation": "تطبیق واریزی‌ها",
  "contacts_summary": "خلاصه طرف‌حساب‌ها",
  "profit_loss": "گزارش سود و زیان"
}
```

---

## 💱 **مدیریت ارزها**

### ویژگی‌های کلیدی
- ✅ **تعریف ارز پایه** سیستم
- ✅ **بروزرسانی نرخ‌ها** به صورت دسته‌ای
- ✅ **تنظیم تعداد اعشار** برای هر ارز
- ✅ **محاسبه خودکار** نرخ‌های متقابل

### ارزهای پیش‌فرض
| کد | نام | نماد | وضعیت |
|---|---|---|---|
| IRR | ریال ایران | ﷼ | ارز پایه |
| USD | دلار آمریکا | $ | فعال |
| EUR | یورو | € | فعال |
| GBP | پوند انگلیس | £ | فعال |
| AED | درهم امارات | د.إ | فعال |
| TRY | لیر ترکیه | ₺ | فعال |

---

## 📊 **گزارش‌گیری پیشرفته**

### داشبورد تحلیلی
- 📈 **نمودار فروش** ماهانه
- 💰 **آمار موجودی** بانک‌ها و صندوق‌ها
- 👥 **فعالیت کاربران**
- 📋 **آخرین تراکنش‌ها**

### گزارش سود و زیان
- 💹 **تحلیل سود** بر اساس جفت ارز
- 📅 **گزارش روزانه/ماهانه**
- 📊 **نمودار روند سودآوری**
- 🎯 **محاسبه مارژین سود**

---

## 🔒 **امنیت سیستم**

### اقدامات امنیتی
- ✅ **هش کردن رمزهای عبور** با MD5
- ✅ **جلوگیری از SQL Injection** با PDO
- ✅ **کنترل دسترسی** مبتنی بر نقش
- ✅ **اعتبارسنجی ورودی‌ها**
- ✅ **مدیریت نشست** امن

### حفاظت از داده‌ها
- 🛡️ **رمزگذاری اطلاعات حساس**
- 📝 **لاگ تمامی تغییرات**
- 🔐 **کنترل دسترسی فایل‌ها**

---

## 🎨 **رابط کاربری**

### ویژگی‌های UI/UX
- 🎯 **طراحی ریسپانسیو** برای موبایل و دسکتاپ
- 🌙 **پشتیبانی کامل از RTL** برای فارسی
- 💫 **انیمیشن‌های روان**
- 🎨 **تم مدرن** با رنگ‌بندی حرفه‌ای
- ⚡ **بارگذاری سریع** صفحات

### فونت‌ها و آیکون‌ها
- 📝 **فونت IRANSans** برای متن‌های فارسی
- 🎪 **FontAwesome 5** برای آیکون‌ها
- 📊 **Chart.js** برای نمودارها

---

## 📱 **تکنولوژی‌های استفاده شده**

### Backend
- ![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white) **PHP 7.4+**
- ![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white) **MySQL 5.7+**
- ![PDO](https://img.shields.io/badge/PDO-Database-orange) **PDO** برای امنیت پایگاه داده

### Frontend
- ![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white) **HTML5**
- ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white) **CSS3** با Flexbox و Grid
- ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black) **Vanilla JavaScript**
- ![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=flat&logo=chartdotjs&logoColor=white) **Chart.js** برای نمودارها

---

## 🧪 **تست و دیباگ**

### محیط توسعه
```bash
# فعال‌سازی حالت دیباگ
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### لاگ‌های سیستم
- 📋 **لاگ تغییرات موجودی**
- 🔍 **ردیابی تراکنش‌ها**
- ⚠️ **لاگ خطاها**

---

## 🚀 **بهبودهای آینده**

### ویژگی‌های در دست توسعه
- [ ] **API RESTful** برای اتصال به سیستم‌های خارجی
- [ ] **اتصال به سامانه‌های بانکی**
- [ ] **گزارش‌گیری PDF**
- [ ] **ارسال SMS** و ایمیل
- [ ] **تقویم شمسی** کامل
- [ ] **چندزبانه بودن**
- [ ] **پشتیبان‌گیری خودکار**

### بهینه‌سازی‌ها
- [ ] **کش کردن** نتایج
- [ ] **فشرده‌سازی** فایل‌ها
- [ ] **بهینه‌سازی** پایگاه داده

---

## 🤝 **مشارکت در پروژه**

### راهنمای مشارکت
1. **Fork** کردن پروژه
2. ایجاد **برنچ** جدید (`git checkout -b feature/AmazingFeature`)
3. **Commit** کردن تغییرات (`git commit -m 'Add some AmazingFeature'`)
4. **Push** کردن به برنچ (`git push origin feature/AmazingFeature`)
5. ایجاد **Pull Request**

### استانداردهای کدنویسی
- استفاده از **PSR-4** برای namespacing
- **کامنت‌گذاری** کامل کد
- **رعایت اصول SOLID**

---

## 📞 **پشتیبانی**

### راه‌های ارتباط
- 📧 **ایمیل:** support@exchange-system.com
- 💬 **تلگرام:** @ExchangeSystemSupport
- 🐛 **گزارش باگ:** [GitHub Issues](https://github.com/your-repo/issues)

### مستندات
- 📖 **راهنمای کاربر:** [User Guide](docs/user-guide.md)
- 👨‍💻 **راهنمای توسعه‌دهنده:** [Developer Guide](docs/developer-guide.md)
- 🔧 **API Documentation:** [API Docs](docs/api.md)

---

## 📄 **مجوز**

این پروژه تحت مجوز **MIT** منتشر شده است. برای جزئیات بیشتر فایل [LICENSE](LICENSE) را مطالعه کنید.

---

## 🙏 **تشکر**

از تمامی توسعه‌دهندگان و کسانی که در ایجاد این پروژه مشارکت داشته‌اند، تشکر می‌کنیم.

### کتابخانه‌های استفاده شده
- **Chart.js** برای نمودارها
- **FontAwesome** برای آیکون‌ها
- **Google Fonts** برای فونت‌های فارسی

---

<div align="center">

**🏦 سیستم مدیریت صرافی - راه‌حل جامع برای صرافی‌های مدرن**

[![⭐ Star on GitHub](https://img.shields.io/github/stars/your-repo/currency-exchange-system?style=social)](https://github.com/your-repo/currency-exchange-system)
[![🍴 Fork on GitHub](https://img.shields.io/github/forks/your-repo/currency-exchange-system?style=social)](https://github.com/your-repo/currency-exchange-system/fork)

---

**ساخته شده با ❤️ در ایران**

</div>