<?php
$pageTitle = 'گزارش سود و زیان';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('profit_loss');

// فیلترها
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01'); // اول ماه جاری
$filter_date_to = $_GET['date_to'] ?? date('Y-m-d'); // امروز
$filter_currency = $_GET['currency'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';

$whereClause = "1=1";
$params = [];

if ($filter_date_from) {
    $whereClause .= " AND cs.sale_date >= ?";
    $params[] = $filter_date_from;
}

if ($filter_date_to) {
    $whereClause .= " AND cs.sale_date <= ?";
    $params[] = $filter_date_to;
}

if ($filter_currency !== 'all') {
    $whereClause .= " AND (cf.currency_code = ? OR ct.currency_code = ?)";
    $params[] = $filter_currency;
    $params[] = $filter_currency;
}

// دریافت فروش‌های ارز
$sales = $db->fetchAll("
    SELECT cs.*, 
           cf.currency_code as from_currency, cf.currency_name as from_currency_name, cf.exchange_rate as from_rate,
           ct.currency_code as to_currency, ct.currency_name as to_currency_name, ct.exchange_rate as to_rate,
           c.contact_name as customer_name,
           u.full_name as created_by_name,
           -- محاسبه نرخ بازار
           (CASE WHEN cf.is_base = 1 THEN ct.exchange_rate ELSE ct.exchange_rate / cf.exchange_rate END) as market_rate,
           -- محاسبه سود/زیان
           (cs.amount_to - (cs.amount_from * (CASE WHEN cf.is_base = 1 THEN ct.exchange_rate ELSE ct.exchange_rate / cf.exchange_rate END))) as exchange_profit,
           cs.commission as commission_profit,
           (cs.amount_to - (cs.amount_from * (CASE WHEN cf.is_base = 1 THEN ct.exchange_rate ELSE ct.exchange_rate / cf.exchange_rate END)) + cs.commission) as total_profit
    FROM currency_sales cs
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    LEFT JOIN contacts c ON cs.customer_id = c.id
    LEFT JOIN users u ON cs.created_by = u.id
    WHERE {$whereClause} AND cs.status = 'completed'
    ORDER BY cs.sale_date DESC, cs.id DESC
", $params);

// محاسبه خرید ارز (شبیه‌سازی - در صورت وجود جدول currency_purchases)
$purchases = $db->fetchAll("
    SELECT cp.*, 
           cf.currency_code as from_currency, cf.currency_name as from_currency_name,
           ct.currency_code as to_currency, ct.currency_name as to_currency_name,
           s.contact_name as supplier_name
    FROM currency_purchases cp
    LEFT JOIN currencies cf ON cp.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cp.currency_to_id = ct.id
    LEFT JOIN contacts s ON cp.supplier_id = s.id
    WHERE cp.purchase_date BETWEEN ? AND ? AND cp.status = 'completed'
    ORDER BY cp.purchase_date DESC
", [$filter_date_from, $filter_date_to]);

// آمار کلی
$totalStats = [
    'total_sales_count' => count($sales),
    'total_sales_amount' => array_sum(array_column($sales, 'total_received')),
    'total_exchange_profit' => array_sum(array_column($sales, 'exchange_profit')),
    'total_commission_profit' => array_sum(array_column($sales, 'commission_profit')),
    'total_profit' => array_sum(array_column($sales, 'total_profit')),
    'avg_profit_per_sale' => count($sales) > 0 ? array_sum(array_column($sales, 'total_profit')) / count($sales) : 0
];

// گروه‌بندی بر اساس ارز
$profitByCurrency = [];
foreach ($sales as $sale) {
    $currencyPair = $sale['from_currency'] . '/' . $sale['to_currency'];
    
    if (!isset($profitByCurrency[$currencyPair])) {
        $profitByCurrency[$currencyPair] = [
            'from_currency' => $sale['from_currency'],
            'to_currency' => $sale['to_currency'],
            'sales_count' => 0,
            'total_amount' => 0,
            'total_exchange_profit' => 0,
            'total_commission' => 0,
            'total_profit' => 0
        ];
    }
    
    $profitByCurrency[$currencyPair]['sales_count']++;
    $profitByCurrency[$currencyPair]['total_amount'] += $sale['total_received'];
    $profitByCurrency[$currencyPair]['total_exchange_profit'] += $sale['exchange_profit'];
    $profitByCurrency[$currencyPair]['total_commission'] += $sale['commission_profit'];
    $profitByCurrency[$currencyPair]['total_profit'] += $sale['total_profit'];
}

// آمار روزانه
$dailyProfit = $db->fetchAll("
    SELECT 
        cs.sale_date,
        COUNT(*) as sales_count,
        SUM(cs.total_received) as total_sales,
        SUM(cs.commission) as total_commission,
        SUM(cs.amount_to - (cs.amount_from * (CASE WHEN cf.is_base = 1 THEN ct.exchange_rate ELSE ct.exchange_rate / cf.exchange_rate END))) as exchange_profit,
        SUM(cs.amount_to - (cs.amount_from * (CASE WHEN cf.is_base = 1 THEN ct.exchange_rate ELSE ct.exchange_rate / cf.exchange_rate END)) + cs.commission) as total_profit
    FROM currency_sales cs
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    WHERE {$whereClause} AND cs.status = 'completed'
    GROUP BY cs.sale_date
    ORDER BY cs.sale_date DESC
", $params);

// دریافت لیست ارزها
$currencies = $db->fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY currency_name");
?>

<!-- فیلترها -->
<div class="card">
    <div class="card-body p-20">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <div class="form-group mb-0">
                <label>از تاریخ</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $filter_date_from; ?>" required>
            </div>
            
            <div class="form-group mb-0">
                <label>تا تاریخ</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $filter_date_to; ?>" required>
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
            
            <button type="submit" class="btn btn-info">
                <i class="fas fa-filter"></i>
                اعمال فیلتر
            </button>
            
            <a href="profit_loss.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                پاک کردن
            </a>
            
            <a href="profit_loss.php?<?php echo http_build_query($_GET); ?>&export=excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i>
                خروجی اکسل
            </a>
        </form>
    </div>
</div>

<!-- آمار کلی -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $totalStats['total_sales_count']; ?></h3>
        <p>تعداد فروش‌ها</p>
        <i class="fas fa-chart-line icon"></i>
    </div>
    
    <div class="stat-card info">
        <h3 class="format-number"><?php echo formatNumber($totalStats['total_sales_amount']); ?></h3>
        <p>کل فروش (ریال)</p>
        <i class="fas fa-money-bill-wave icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3 class="format-number"><?php echo formatNumber($totalStats['total_commission_profit']); ?></h3>
        <p>سود کمیسیون</p>
        <i class="fas fa-percent icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3 class="format-number"><?php echo formatNumber($totalStats['total_exchange_profit']); ?></h3>
        <p>سود نرخ ارز</p>
        <i class="fas fa-exchange-alt icon"></i>
    </div>
    
    <div class="stat-card <?php echo $totalStats['total_profit'] >= 0 ? 'success' : 'danger'; ?>">
        <h3 class="format-number"><?php echo formatNumber($totalStats['total_profit']); ?></h3>
        <p>کل سود/زیان</p>
        <i class="fas fa-<?php echo $totalStats['total_profit'] >= 0 ? 'arrow-up' : 'arrow-down'; ?> icon"></i>
    </div>
    
    <div class="stat-card">
        <h3 class="format-number"><?php echo formatNumber($totalStats['avg_profit_per_sale']); ?></h3>
        <p>میانگین سود هر فروش</p>
        <i class="fas fa-calculator icon"></i>
    </div>
</div>

<!-- نمودار سود روزانه -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-area"></i>
        نمودار سود روزانه
    </div>
    <div class="card-body">
        <canvas id="dailyProfitChart" width="400" height="100"></canvas>
    </div>
</div>

<!-- سود بر اساس جفت ارز -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-coins"></i>
        سود/زیان بر اساس جفت ارز
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>جفت ارز</th>
                        <th>تعداد معاملات</th>
                        <th>کل حجم معاملات</th>
                        <th>سود نرخ ارز</th>
                        <th>سود کمیسیون</th>
                        <th>کل سود/زیان</th>
                        <th>میانگین سود</th>
                        <th>مارژین سود (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profitByCurrency as $currencyPair => $data): ?>
                    <tr>
                        <td>
                            <strong><?php echo $data['from_currency'] . ' → ' . $data['to_currency']; ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo $data['sales_count']; ?></span>
                        </td>
                        <td class="format-number"><?php echo formatNumber($data['total_amount']); ?></td>
                        <td class="format-number <?php echo $data['total_exchange_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatNumber($data['total_exchange_profit']); ?>
                        </td>
                        <td class="format-number text-success">
                            <?php echo formatNumber($data['total_commission']); ?>
                        </td>
                        <td class="format-number <?php echo $data['total_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo formatNumber($data['total_profit']); ?></strong>
                        </td>
                        <td class="format-number">
                            <?php echo formatNumber($data['total_profit'] / $data['sales_count']); ?>
                        </td>
                        <td>
                            <?php if ($data['total_amount'] > 0): ?>
                            <?php $margin = ($data['total_profit'] / $data['total_amount']) * 100; ?>
                            <span class="<?php echo $margin >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($margin, 2); ?>%
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- آمار روزانه -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-calendar-day"></i>
        گزارش روزانه
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>تعداد فروش</th>
                        <th>کل فروش</th>
                        <th>سود نرخ ارز</th>
                        <th>سود کمیسیون</th>
                        <th>کل سود/زیان</th>
                        <th>میانگین سود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyProfit as $daily): ?>
                    <tr>
                        <td><?php echo jalaliDate($daily['sale_date']); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo $daily['sales_count']; ?></span>
                        </td>
                        <td class="format-number"><?php echo formatNumber($daily['total_sales']); ?></td>
                        <td class="format-number <?php echo $daily['exchange_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatNumber($daily['exchange_profit']); ?>
                        </td>
                        <td class="format-number text-success">
                            <?php echo formatNumber($daily['total_commission']); ?>
                        </td>
                        <td class="format-number <?php echo $daily['total_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo formatNumber($daily['total_profit']); ?></strong>
                        </td>
                        <td class="format-number">
                            <?php echo formatNumber($daily['total_profit'] / $daily['sales_count']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- جزئیات معاملات -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i>
        جزئیات معاملات
        <button class="btn btn-info btn-small" style="float: left;" onclick="toggleDetails()">
            <i class="fas fa-eye" id="toggleIcon"></i>
            <span id="toggleText">نمایش جزئیات</span>
        </button>
    </div>
    <div class="card-body" id="transactionDetails" style="display: none;">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>جفت ارز</th>
                        <th>مقدار</th>
                        <th>نرخ فروش</th>
                        <th>نرخ بازار</th>
                        <th>سود نرخ</th>
                        <th>کمیسیون</th>
                        <th>کل سود</th>
                        <th>مارژین</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo jalaliDate($sale['sale_date']); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'نامشخص'); ?></td>
                        <td>
                            <small>
                                <strong><?php echo $sale['from_currency']; ?></strong> → 
                                <strong><?php echo $sale['to_currency']; ?></strong>
                            </small>
                        </td>
                        <td class="format-number">
                            <?php echo formatNumber($sale['amount_from']); ?>
                            <small class="text-muted"><?php echo $sale['from_currency']; ?></small>
                        </td>
                        <td class="format-number"><?php echo formatNumber($sale['exchange_rate']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['market_rate']); ?></td>
                        <td class="format-number <?php echo $sale['exchange_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatNumber($sale['exchange_profit']); ?>
                        </td>
                        <td class="format-number text-success">
                            <?php echo formatNumber($sale['commission_profit']); ?>
                        </td>
                        <td class="format-number <?php echo $sale['total_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo formatNumber($sale['total_profit']); ?></strong>
                        </td>
                        <td>
                            <?php if ($sale['total_received'] > 0): ?>
                            <?php $margin = ($sale['total_profit'] / $sale['total_received']) * 100; ?>
                            <small class="<?php echo $margin >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($margin, 2); ?>%
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // رسم نمودار سود روزانه
    const ctx = document.getElementById('dailyProfitChart').getContext('2d');
    const dailyData = <?php echo json_encode(array_reverse($dailyProfit)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.sale_date),
            datasets: [{
                label: 'سود روزانه',
                data: dailyData.map(item => parseFloat(item.total_profit)),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }, {
                label: 'سود کمیسیون',
                data: dailyData.map(item => parseFloat(item.total_commission)),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: false
            }, {
                label: 'سود نرخ ارز',
                data: dailyData.map(item => parseFloat(item.exchange_profit)),
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 2,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'روند سود روزانه'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fa-IR');
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});

function toggleDetails() {
    const details = document.getElementById('transactionDetails');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.className = 'fas fa-eye-slash';
        text.textContent = 'مخفی کردن جزئیات';
    } else {
        details.style.display = 'none';
        icon.className = 'fas fa-eye';
        text.textContent = 'نمایش جزئیات';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>