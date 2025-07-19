<?php
$pageTitle = 'خلاصه طرف‌حساب‌ها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('contacts_summary');

// فیلترها
$filter_type = $_GET['type'] ?? 'all';
$filter_currency = $_GET['currency'] ?? 'all';
$filter_balance = $_GET['balance'] ?? 'all';

$whereClause = "c.status = 'active'";
$params = [];

if ($filter_type !== 'all') {
    $whereClause .= " AND c.contact_type = ?";
    $params[] = $filter_type;
}

// دریافت لیست طرف‌حساب‌ها با موجودی
$contactsWithBalances = $db->fetchAll("
    SELECT c.*, 
           ca.currency_id, 
           ca.balance,
           cur.currency_code,
           cur.symbol as currency_symbol,
           cur.is_base,
           cur.exchange_rate,
           (CASE WHEN cur.is_base = 1 THEN ca.balance ELSE ca.balance * cur.exchange_rate END) as balance_in_base_currency
    FROM contacts c
    LEFT JOIN contact_accounts ca ON c.id = ca.contact_id
    LEFT JOIN currencies cur ON ca.currency_id = cur.id
    WHERE {$whereClause}
    ORDER BY c.contact_name, cur.currency_name
", $params);

// گروه‌بندی بر اساس طرف‌حساب
$contactsSummary = [];
$totalsByType = [
    'customer' => 0,
    'supplier' => 0,
    'exchanger' => 0,
    'other' => 0
];

foreach ($contactsWithBalances as $row) {
    $contactId = $row['id'];
    
    if (!isset($contactsSummary[$contactId])) {
        $contactsSummary[$contactId] = [
            'contact_info' => $row,
            'balances' => [],
            'total_balance_base' => 0
        ];
    }
    
    if ($row['currency_id']) {
        $contactsSummary[$contactId]['balances'][] = [
            'currency_code' => $row['currency_code'],
            'currency_symbol' => $row['currency_symbol'],
            'balance' => $row['balance'],
            'balance_in_base' => $row['balance_in_base_currency'],
            'is_base' => $row['is_base']
        ];
        
        $contactsSummary[$contactId]['total_balance_base'] += $row['balance_in_base_currency'];
    }
    
    // جمع کل بر اساس نوع
    if (isset($totalsByType[$row['contact_type']])) {
        $totalsByType[$row['contact_type']] += $row['balance_in_base_currency'] ?? 0;
    }
}

// فیلتر بر اساس موجودی
if ($filter_balance === 'positive') {
    $contactsSummary = array_filter($contactsSummary, function($contact) {
        return $contact['total_balance_base'] > 0;
    });
} elseif ($filter_balance === 'negative') {
    $contactsSummary = array_filter($contactsSummary, function($contact) {
        return $contact['total_balance_base'] < 0;
    });
} elseif ($filter_balance === 'zero') {
    $contactsSummary = array_filter($contactsSummary, function($contact) {
        return $contact['total_balance_base'] == 0;
    });
}

// فیلتر بر اساس ارز
if ($filter_currency !== 'all') {
    $contactsSummary = array_filter($contactsSummary, function($contact) use ($filter_currency) {
        foreach ($contact['balances'] as $balance) {
            if ($balance['currency_code'] === $filter_currency) {
                return true;
            }
        }
        return false;
    });
}

// دریافت لیست ارزها
$currencies = $db->fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY is_base DESC, currency_name");

// آمار کلی
$stats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT c.id) as total_contacts,
        SUM(CASE WHEN c.contact_type = 'customer' THEN 1 ELSE 0 END) as customers,
        SUM(CASE WHEN c.contact_type = 'supplier' THEN 1 ELSE 0 END) as suppliers,
        SUM(CASE WHEN c.contact_type = 'exchanger' THEN 1 ELSE 0 END) as exchangers,
        SUM(CASE WHEN c.contact_type = 'other' THEN 1 ELSE 0 END) as others
    FROM contacts c
    WHERE c.status = 'active'
");

// محاسبه مجموع موجودی‌ها بر اساس ارز
$balancesByCurrency = $db->fetchAll("
    SELECT 
        cur.currency_code,
        cur.currency_name,
        cur.symbol,
        cur.is_base,
        cur.exchange_rate,
        SUM(ca.balance) as total_balance,
        SUM(CASE WHEN cur.is_base = 1 THEN ca.balance ELSE ca.balance * cur.exchange_rate END) as total_in_base,
        COUNT(DISTINCT ca.contact_id) as contacts_count
    FROM contact_accounts ca
    JOIN currencies cur ON ca.currency_id = cur.id
    JOIN contacts c ON ca.contact_id = c.id
    WHERE c.status = 'active' AND ca.balance != 0
    GROUP BY cur.id
    ORDER BY cur.is_base DESC, cur.currency_name
");
?>

<!-- فیلترها -->
<div class="card">
    <div class="card-body p-20">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <div class="form-group mb-0">
                <label>نوع طرف‌حساب</label>
                <select name="type" class="form-control">
                    <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="customer" <?php echo $filter_type === 'customer' ? 'selected' : ''; ?>>مشتریان</option>
                    <option value="supplier" <?php echo $filter_type === 'supplier' ? 'selected' : ''; ?>>تامین‌کنندگان</option>
                    <option value="exchanger" <?php echo $filter_type === 'exchanger' ? 'selected' : ''; ?>>صرافان</option>
                    <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>سایر</option>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <label>ارز</label>
                <select name="currency" class="form-control">
                    <option value="all" <?php echo $filter_currency === 'all' ? 'selected' : ''; ?>>همه ارزها</option>
                    <?php foreach ($currencies as $currency): ?>
                    <option value="<?php echo $currency['currency_code']; ?>" <?php echo $filter_currency === $currency['currency_code'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($currency['currency_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <label>وضعیت موجودی</label>
                <select name="balance" class="form-control">
                    <option value="all" <?php echo $filter_balance === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="positive" <?php echo $filter_balance === 'positive' ? 'selected' : ''; ?>>بدهکار (موجودی مثبت)</option>
                    <option value="negative" <?php echo $filter_balance === 'negative' ? 'selected' : ''; ?>>بستانکار (موجودی منفی)</option>
                    <option value="zero" <?php echo $filter_balance === 'zero' ? 'selected' : ''; ?>>تسویه شده</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-info">
                <i class="fas fa-filter"></i>
                فیلتر
            </button>
            
            <a href="contacts_summary.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                پاک کردن فیلتر
            </a>
            
            <a href="contacts_summary.php?export=excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i>
                خروجی اکسل
            </a>
        </form>
    </div>
</div>

<!-- آمار کلی -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo count($contactsSummary); ?></h3>
        <p>طرف‌حساب‌های فیلتر شده</p>
        <i class="fas fa-users icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3 class="format-number"><?php echo formatNumber($totalsByType['customer']); ?></h3>
        <p>کل موجودی مشتریان</p>
        <i class="fas fa-user-plus icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3 class="format-number"><?php echo formatNumber($totalsByType['supplier']); ?></h3>
        <p>کل موجودی تامین‌کنندگان</p>
        <i class="fas fa-truck icon"></i>
    </div>
    
    <div class="stat-card info">
        <h3 class="format-number"><?php echo formatNumber($totalsByType['exchanger']); ?></h3>
        <p>کل موجودی صرافان</p>
        <i class="fas fa-exchange-alt icon"></i>
    </div>
</div>

<!-- خلاصه موجودی بر اساس ارز -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-coins"></i>
        خلاصه موجودی بر اساس ارز
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ارز</th>
                        <th>کل موجودی</th>
                        <th>معادل ریال</th>
                        <th>تعداد طرف‌حساب</th>
                        <th>درصد</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalBaseAmount = array_sum(array_column($balancesByCurrency, 'total_in_base'));
                    foreach ($balancesByCurrency as $currencyBalance): 
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($currencyBalance['currency_code']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($currencyBalance['currency_name']); ?></small>
                        </td>
                        <td class="format-number">
                            <?php echo formatNumber($currencyBalance['total_balance']); ?>
                            <?php echo htmlspecialchars($currencyBalance['symbol']); ?>
                        </td>
                        <td class="format-number">
                            <?php if (!$currencyBalance['is_base']): ?>
                                <?php echo formatNumber($currencyBalance['total_in_base']); ?> ریال
                            <?php else: ?>
                                <span class="text-muted">ارز پایه</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo $currencyBalance['contacts_count']; ?></span>
                        </td>
                        <td>
                            <?php if ($totalBaseAmount > 0): ?>
                            <?php $percentage = ($currencyBalance['total_in_base'] / $totalBaseAmount) * 100; ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="width: <?php echo abs($percentage); ?>%; background-color: <?php echo $percentage >= 0 ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo number_format($percentage, 1); ?>%
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- لیست طرف‌حساب‌ها -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-address-book"></i>
        جزئیات موجودی طرف‌حساب‌ها
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>طرف‌حساب</th>
                        <th>نوع</th>
                        <th>تلفن</th>
                        <th>موجودی‌ها</th>
                        <th>کل موجودی (ریال)</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactsSummary as $contactId => $contactData): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($contactData['contact_info']['contact_name']); ?></strong>
                            <?php if ($contactData['contact_info']['company_name']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($contactData['contact_info']['company_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $typeLabels = [
                                'customer' => ['مشتری', 'success'],
                                'supplier' => ['تامین‌کننده', 'warning'],
                                'exchanger' => ['صراف', 'info'],
                                'other' => ['سایر', 'secondary']
                            ];
                            $typeInfo = $typeLabels[$contactData['contact_info']['contact_type']] ?? ['نامشخص', 'dark'];
                            ?>
                            <span class="badge badge-<?php echo $typeInfo[1]; ?>"><?php echo $typeInfo[0]; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($contactData['contact_info']['phone']); ?></td>
                        <td>
                            <?php if (empty($contactData['balances'])): ?>
                                <span class="text-muted">بدون موجودی</span>
                            <?php else: ?>
                                <?php foreach ($contactData['balances'] as $balance): ?>
                                <div class="mb-1">
                                    <span class="format-number <?php echo $balance['balance'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatNumber($balance['balance']); ?>
                                    </span>
                                    <small class="text-muted"><?php echo htmlspecialchars($balance['currency_code']); ?></small>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="format-number <?php echo $contactData['total_balance_base'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatNumber($contactData['total_balance_base']); ?>
                            </span>
                            <small class="text-muted">ریال</small>
                        </td>
                        <td>
                            <?php if ($contactData['total_balance_base'] > 0): ?>
                                <span class="badge badge-success">بدهکار</span>
                            <?php elseif ($contactData['total_balance_base'] < 0): ?>
                                <span class="badge badge-danger">بستانکار</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">تسویه</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-small view-contact-details-btn" 
                                    data-contact='<?php echo json_encode($contactData); ?>'>
                                <i class="fas fa-eye"></i>
                                جزئیات
                            </button>
                            
                            <a href="contact_transactions.php?contact_id=<?php echo $contactId; ?>" 
                               class="btn btn-primary btn-small">
                                <i class="fas fa-list"></i>
                                تراکنش‌ها
                            </a>
                            
                            <button class="btn btn-warning btn-small adjust-balance-btn" 
                                    data-contact-id="<?php echo $contactId; ?>"
                                    data-contact-name="<?php echo htmlspecialchars($contactData['contact_info']['contact_name']); ?>">
                                <i class="fas fa-calculator"></i>
                                تعدیل
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal جزئیات طرف‌حساب -->
<div id="contactDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>جزئیات موجودی طرف‌حساب</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="contactDetailsContent">
            <!-- محتوا توسط JavaScript پر می‌شود -->
        </div>
    </div>
</div>

<!-- Modal تعدیل موجودی -->
<div id="adjustBalanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>تعدیل موجودی طرف‌حساب</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="contact_balance_adjustment.php">
                <input type="hidden" name="contact_id" id="adjust_contact_id">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    تعدیل موجودی برای: <strong><span id="adjust_contact_name"></span></strong>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="currency_id">ارز *</label>
                        <select name="currency_id" id="currency_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                                (<?php echo htmlspecialchars($currency['currency_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adjustment_type">نوع تعدیل *</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-control" required>
                            <option value="increase">افزایش موجودی</option>
                            <option value="decrease">کاهش موجودی</option>
                            <option value="set">تنظیم موجودی</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="adjustment_amount">مقدار تعدیل *</label>
                    <input type="number" name="adjustment_amount" id="adjustment_amount" 
                           class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="adjustment_notes">توضیحات *</label>
                    <textarea name="adjustment_notes" id="adjustment_notes" class="form-control" 
                              rows="3" required placeholder="دلیل تعدیل موجودی را وارد کنید..."></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-calculator"></i>
                        اعمال تعدیل
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('adjustBalanceModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // مدیریت دکمه‌های مشاهده جزئیات
    document.querySelectorAll('.view-contact-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const contactData = JSON.parse(this.dataset.contact);
            const modal = document.getElementById('contactDetailsModal');
            const content = document.getElementById('contactDetailsContent');
            
            const typeLabels = {
                'customer': 'مشتری',
                'supplier': 'تامین‌کننده',
                'exchanger': 'صراف',
                'other': 'سایر'
            };
            
            let balancesHTML = '';
            if (contactData.balances.length > 0) {
                balancesHTML = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ارز</th><th>موجودی</th><th>معادل ریال</th></tr></thead><tbody>';
                contactData.balances.forEach(balance => {
                    balancesHTML += `
                        <tr>
                            <td>${balance.currency_code}</td>
                            <td class="format-number ${balance.balance >= 0 ? 'text-success' : 'text-danger'}">
                                ${parseFloat(balance.balance).toLocaleString('fa-IR')}
                            </td>
                            <td class="format-number">
                                ${balance.is_base ? 'ارز پایه' : parseFloat(balance.balance_in_base).toLocaleString('fa-IR')}
                            </td>
                        </tr>
                    `;
                });
                balancesHTML += '</tbody></table></div>';
            } else {
                balancesHTML = '<p class="text-muted">بدون موجودی</p>';
            }
            
            content.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <strong>نام:</strong> ${contactData.contact_info.contact_name}
                    </div>
                    <div class="form-group">
                        <strong>نوع:</strong> ${typeLabels[contactData.contact_info.contact_type] || contactData.contact_info.contact_type}
                    </div>
                </div>
                
                ${contactData.contact_info.company_name ? `
                <div class="form-group">
                    <strong>شرکت:</strong> ${contactData.contact_info.company_name}
                </div>
                ` : ''}
                
                <div class="form-row">
                    ${contactData.contact_info.phone ? `<div class="form-group"><strong>تلفن:</strong> ${contactData.contact_info.phone}</div>` : ''}
                    ${contactData.contact_info.email ? `<div class="form-group"><strong>ایمیل:</strong> ${contactData.contact_info.email}</div>` : ''}
                </div>
                
                <h5 style="margin: 20px 0 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    موجودی‌های طرف‌حساب
                </h5>
                
                ${balancesHTML}
                
                <div class="form-group" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <strong>کل موجودی (ریال):</strong> 
                    <span class="format-number ${contactData.total_balance_base >= 0 ? 'text-success' : 'text-danger'}" style="font-size: 1.2rem; font-weight: bold;">
                        ${parseFloat(contactData.total_balance_base).toLocaleString('fa-IR')}
                    </span>
                </div>
            `;
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت دکمه‌های تعدیل موجودی
    document.querySelectorAll('.adjust-balance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const contactId = this.dataset.contactId;
            const contactName = this.dataset.contactName;
            const modal = document.getElementById('adjustBalanceModal');
            
            document.getElementById('adjust_contact_id').value = contactId;
            document.getElementById('adjust_contact_name').textContent = contactName;
            
            // پاک کردن فرم
            document.getElementById('currency_id').value = '';
            document.getElementById('adjustment_type').value = 'increase';
            document.getElementById('adjustment_amount').value = '';
            document.getElementById('adjustment_notes').value = '';
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>