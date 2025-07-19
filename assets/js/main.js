// نرم‌افزار حسابداری صرافی - JavaScript اصلی

document.addEventListener('DOMContentLoaded', function() {
    // تنظیمات اولیه
    initializeSidebar();
    initializeModals();
    initializeForms();
    initializeDataTables();
    initializeCharts();
    
    // اعداد را فرمت کن
    formatNumbers();
});

// مدیریت Sidebar
function initializeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    
    // Toggle sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // بازیابی وضعیت sidebar از localStorage
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    // فعال کردن لینک فعلی
    const currentPage = window.location.pathname.split('/').pop();
    menuLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

// مدیریت Modal ها
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeBtns = document.querySelectorAll('.close');
    
    // باز کردن modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // بستن modal
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // بستن با کلیک روی پس‌زمینه
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // بستن با ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="block"]');
            openModals.forEach(modal => closeModal(modal));
        }
    });
}

function closeModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// مدیریت فرم‌ها
function initializeForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // اعتبارسنجی real-time
        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
        
        // ارسال فرم
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // محاسبه خودکار در فرم‌های مربوط به ارز
    initializeCurrencyCalculations();
}

// اعتبارسنجی فیلد
function validateField(field) {
    const value = field.value.trim();
    const required = field.hasAttribute('required');
    const type = field.type;
    
    // حذف پیام خطای قبلی
    removeFieldError(field);
    
    // بررسی فیلد اجباری
    if (required && !value) {
        showFieldError(field, 'این فیلد اجباری است');
        return false;
    }
    
    // اعتبارسنجی email
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'ایمیل معتبر وارد کنید');
            return false;
        }
    }
    
    // اعتبارسنجی شماره تلفن
    if (field.name === 'phone' && value) {
        const phoneRegex = /^09\d{9}$/;
        if (!phoneRegex.test(value)) {
            showFieldError(field, 'شماره تلفن معتبر وارد کنید (09xxxxxxxxx)');
            return false;
        }
    }
    
    // اعتبارسنجی کدملی
    if (field.name === 'national_id' && value) {
        if (!validateNationalId(value)) {
            showFieldError(field, 'کد ملی معتبر وارد کنید');
            return false;
        }
    }
    
    field.classList.remove('error');
    return true;
}

// نمایش خطا در فیلد
function showFieldError(field, message) {
    field.classList.add('error');
    
    // حذف پیام خطای قبلی
    removeFieldError(field);
    
    // افزودن پیام خطای جدید
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// حذف پیام خطای فیلد
function removeFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// اعتبارسنجی کل فرم
function validateForm(form) {
    const inputs = form.querySelectorAll('.form-control');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// اعتبارسنجی کد ملی
function validateNationalId(nationalId) {
    if (!/^\d{10}$/.test(nationalId)) return false;
    
    const check = parseInt(nationalId[9]);
    let sum = 0;
    
    for (let i = 0; i < 9; i++) {
        sum += parseInt(nationalId[i]) * (10 - i);
    }
    
    const remainder = sum % 11;
    return (remainder < 2 && check === remainder) || (remainder >= 2 && check === 11 - remainder);
}

// محاسبات ارز
function initializeCurrencyCalculations() {
    const amountFromInputs = document.querySelectorAll('input[name="amount_from"]');
    const exchangeRateInputs = document.querySelectorAll('input[name="exchange_rate"]');
    const amountToInputs = document.querySelectorAll('input[name="amount_to"]');
    
    // محاسبه مقدار ارز مقصد
    function calculateAmountTo() {
        const amountFrom = parseFloat(document.querySelector('input[name="amount_from"]')?.value) || 0;
        const exchangeRate = parseFloat(document.querySelector('input[name="exchange_rate"]')?.value) || 0;
        const amountToField = document.querySelector('input[name="amount_to"]');
        
        if (amountToField) {
            amountToField.value = (amountFrom * exchangeRate).toFixed(2);
            calculateTotal();
        }
    }
    
    // محاسبه کل مبلغ
    function calculateTotal() {
        const amountTo = parseFloat(document.querySelector('input[name="amount_to"]')?.value) || 0;
        const commission = parseFloat(document.querySelector('input[name="commission"]')?.value) || 0;
        const totalField = document.querySelector('input[name="total_received"]');
        
        if (totalField) {
            totalField.value = (amountTo + commission).toFixed(2);
        }
    }
    
    // اتصال event listener ها
    amountFromInputs.forEach(input => {
        input.addEventListener('input', calculateAmountTo);
    });
    
    exchangeRateInputs.forEach(input => {
        input.addEventListener('input', calculateAmountTo);
    });
    
    const commissionInputs = document.querySelectorAll('input[name="commission"]');
    commissionInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
}

// مدیریت جداول
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // مرتب‌سازی ستون‌ها
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, this.dataset.sort);
            });
        });
        
        // جستجو در جدول
        const searchInput = document.querySelector(`#search-${table.id}`);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterTable(table, this.value);
            });
        }
    });
}

// مرتب‌سازی جدول
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(table.querySelectorAll('th')).findIndex(th => th.dataset.sort === column);
    
    if (columnIndex === -1) return;
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        // تشخیص نوع داده
        if (!isNaN(aVal) && !isNaN(bVal)) {
            return parseFloat(aVal) - parseFloat(bVal);
        } else {
            return aVal.localeCompare(bVal, 'fa');
        }
    });
    
    // پاک کردن جدول و افزودن ردیف‌های مرتب شده
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

// فیلتر جدول
function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

// نمودارها
function initializeCharts() {
    // نمودار داشبورد - موجودی ارزها
    const currencyChartCanvas = document.getElementById('currencyChart');
    if (currencyChartCanvas && typeof Chart !== 'undefined') {
        createCurrencyChart(currencyChartCanvas);
    }
    
    // نمودار فروش و خرید ماهانه
    const salesChartCanvas = document.getElementById('salesChart');
    if (salesChartCanvas && typeof Chart !== 'undefined') {
        createSalesChart(salesChartCanvas);
    }
}

// نمودار موجودی ارزها
function createCurrencyChart(canvas) {
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['دلار آمریکا', 'یورو', 'پوند انگلیس', 'درهم امارات'],
            datasets: [{
                data: [45, 25, 15, 15],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#f5576c'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// نمودار فروش و خرید
function createSalesChart(canvas) {
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
            datasets: [{
                label: 'فروش',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
            }, {
                label: 'خرید',
                data: [8, 15, 12, 20, 18, 25],
                borderColor: '#764ba2',
                backgroundColor: 'rgba(118, 75, 162, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// فرمت کردن اعداد
function formatNumbers() {
    const numberElements = document.querySelectorAll('.format-number');
    
    numberElements.forEach(element => {
        const number = parseFloat(element.textContent);
        if (!isNaN(number)) {
            element.textContent = number.toLocaleString('fa-IR');
        }
    });
}

// فانکشن‌های کمکی
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.innerHTML = `
        <span>${message}</span>
        <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    const container = document.querySelector('.content-area');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // حذف خودکار پس از 5 ثانیه
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

function confirmDelete(message = 'آیا از حذف این آیتم اطمینان دارید؟') {
    return confirm(message);
}

// Ajax helpers
function sendAjaxRequest(url, data = {}, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        showAlert('خطا در ارتباط با سرور', 'danger');
    });
}

// Export functions for global use
window.showAlert = showAlert;
window.confirmDelete = confirmDelete;
window.sendAjaxRequest = sendAjaxRequest;
window.closeModal = closeModal;

// بارگذاری فایل اکسل
function initializeExcelUpload() {
    const uploadForm = document.getElementById('excelUploadForm');
    const fileInput = document.getElementById('excelFile');
    const progressBar = document.querySelector('.upload-progress');
    
    if (uploadForm && fileInput) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) {
                showAlert('لطفاً فایل اکسل را انتخاب کنید', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('bank_account_id', document.getElementById('bank_account_id').value);
            
            // نمایش progress bar
            if (progressBar) {
                progressBar.style.display = 'block';
            }
            
            fetch('upload_excel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (progressBar) {
                    progressBar.style.display = 'none';
                }
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                if (progressBar) {
                    progressBar.style.display = 'none';
                }
                console.error('Error:', error);
                showAlert('خطا در بارگذاری فایل', 'danger');
            });
        });
    }
}

// اتوکامپلیت برای طرف‌حساب‌ها
function initializeAutocomplete() {
    const contactInputs = document.querySelectorAll('.contact-autocomplete');
    
    contactInputs.forEach(input => {
        let timeout;
        const dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(dropdown);
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchContacts(this.value, dropdown, input);
            }, 300);
        });
        
        input.addEventListener('blur', function() {
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        });
    });
}

function searchContacts(query, dropdown, input) {
    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    fetch(`search_contacts.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            dropdown.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(contact => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;';
                    item.textContent = contact.contact_name;
                    item.addEventListener('click', function() {
                        input.value = contact.contact_name;
                        input.dataset.contactId = contact.id;
                        dropdown.style.display = 'none';
                    });
                    dropdown.appendChild(item);
                });
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            dropdown.style.display = 'none';
        });
}