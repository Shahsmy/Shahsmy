<?php
$pageTitle = 'داشبورد اصلی';
require_once 'includes/header.php';

// دریافت آمار کلی
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];
$totalCurrencies = $db->fetchOne("SELECT COUNT(*) as count FROM currencies WHERE status = 'active'")['count'];
$totalBanks = $db->fetchOne("SELECT COUNT(*) as count FROM banks WHERE status = 'active'")['count'];
$totalContacts = $db->fetchOne("SELECT COUNT(*) as count FROM contacts WHERE status = 'active'")['count'];

// آمار معاملات امروز
$today = date('Y-m-d');
$todaySales = $db->fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(total_received), 0) as total FROM currency_sales WHERE sale_date = ?", [$today]);
$todayDeposits = $db->fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(deposit), 0) as total FROM bank_transactions WHERE transaction_date = ? AND deposit > 0", [$today]);
$todayWithdrawals = $db->fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(withdrawal), 0) as total FROM bank_transactions WHERE transaction_date = ? AND withdrawal > 0", [$today]);

// موجودی کل حساب‌های بانکی
$bankBalances = $db->fetchAll("
    SELECT b.bank_name, ba.account_name, ba.balance, c.currency_code, c.symbol 
    FROM bank_accounts ba 
    JOIN banks b ON ba.bank_id = b.id 
    JOIN currencies c ON ba.currency_id = c.id 
    WHERE ba.status = 'active' AND b.status = 'active'
    ORDER BY b.bank_name, ba.account_name
");

// موجودی صندوق‌ها
$cashBoxBalances = $db->fetchAll("
    SELECT cb.name, cb.balance, c.currency_code, c.symbol 
    FROM cash_boxes cb 
    JOIN currencies c ON cb.currency_id = c.id 
    WHERE cb.status = 'active'
    ORDER BY cb.name
");

// آخرین معاملات
$recentSales = $db->fetchAll("
    SELECT cs.*, 
           cf.currency_code as from_currency, 
           ct.currency_code as to_currency,
           con.contact_name as customer_name
    FROM currency_sales cs 
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    LEFT JOIN contacts con ON cs.customer_id = con.id
    ORDER BY cs.created_at DESC 
    LIMIT 10
");

// تراکنش‌های بانکی آخر
$recentTransactions = $db->fetchAll("
    SELECT bt.*, ba.account_name, b.bank_name 
    FROM bank_transactions bt 
    JOIN bank_accounts ba ON bt.bank_account_id = ba.id 
    JOIN banks b ON ba.bank_id = b.id 
    ORDER BY bt.transaction_date DESC, bt.id DESC 
    LIMIT 10
");

// طرف‌حساب‌های بدهکار/بستانکار
$contactBalances = $db->fetchAll("
    SELECT ca.*, c.contact_name, cur.currency_code, cur.symbol
    FROM contact_accounts ca
    JOIN contacts c ON ca.contact_id = c.id
    JOIN currencies cur ON ca.currency_id = cur.id
    WHERE ca.balance != 0
    ORDER BY ABS(ca.balance) DESC
    LIMIT 10
");
?>

<!-- آمار کلی -->
<div class="stats-grid">
    <div class="stat-card">
        <h3 class="format-number"><?php echo $totalUsers; ?></h3>
        <p>کاربران فعال</p>
        <i class="fas fa-users icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3 class="format-number"><?php echo $todaySales['count']; ?></h3>
        <p>فروش امروز</p>
        <i class="fas fa-chart-line icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3 class="format-number"><?php echo $todayDeposits['count']; ?></h3>
        <p>واریزی‌های امروز</p>
        <i class="fas fa-arrow-down icon"></i>
    </div>
    
    <div class="stat-card danger">
        <h3 class="format-number"><?php echo $todayWithdrawals['count']; ?></h3>
        <p>برداشت‌های امروز</p>
        <i class="fas fa-arrow-up icon"></i>
    </div>
</div>

<div class="form-row">
    <!-- نمودار آمار -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i>
                توزیع موجودی ارزها
            </div>
            <div class="card-body">
                <canvas id="currencyChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- نمودار فروش ماهانه -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i>
                روند فروش و خرید
            </div>
            <div class="card-body">
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="form-row">
    <!-- موجودی حساب‌های بانکی -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-university"></i>
                موجودی حساب‌های بانکی
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>بانک</th>
                                <th>حساب</th>
                                <th>موجودی</th>
                                <th>ارز</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bankBalances as $balance): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($balance['bank_name']); ?></td>
                                <td><?php echo htmlspecialchars($balance['account_name']); ?></td>
                                <td class="format-number"><?php echo formatNumber($balance['balance']); ?></td>
                                <td><?php echo htmlspecialchars($balance['currency_code']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- موجودی صندوق‌ها -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cash-register"></i>
                موجودی صندوق‌ها
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>نام صندوق</th>
                                <th>موجودی</th>
                                <th>ارز</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cashBoxBalances as $balance): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($balance['name']); ?></td>
                                <td class="format-number"><?php echo formatNumber($balance['balance']); ?></td>
                                <td><?php echo htmlspecialchars($balance['currency_code']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- آخرین معاملات -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i>
        آخرین فروش‌های ارز
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>از ارز</th>
                        <th>به ارز</th>
                        <th>مقدار</th>
                        <th>نرخ</th>
                        <th>مبلغ کل</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                    <tr>
                        <td><?php echo jalaliDate($sale['sale_date']); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'نامشخص'); ?></td>
                        <td><?php echo htmlspecialchars($sale['from_currency']); ?></td>
                        <td><?php echo htmlspecialchars($sale['to_currency']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['amount_from']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['exchange_rate']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['total_received']); ?></td>
                        <td>
                            <?php if ($sale['status'] === 'completed'): ?>
                                <span class="badge badge-success">تکمیل شده</span>
                            <?php elseif ($sale['status'] === 'pending'): ?>
                                <span class="badge badge-warning">در انتظار</span>
                            <?php else: ?>
                                <span class="badge badge-danger">لغو شده</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- آخرین تراکنش‌های بانکی -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-exchange-alt"></i>
        آخرین تراکنش‌های بانکی
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>بانک</th>
                        <th>حساب</th>
                        <th>شرح</th>
                        <th>واریز</th>
                        <th>برداشت</th>
                        <th>مانده</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?php echo jalaliDate($transaction['transaction_date']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['bank_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['account_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($transaction['description'], 0, 50)); ?></td>
                        <td class="format-number"><?php echo $transaction['deposit'] > 0 ? formatNumber($transaction['deposit']) : '-'; ?></td>
                        <td class="format-number"><?php echo $transaction['withdrawal'] > 0 ? formatNumber($transaction['withdrawal']) : '-'; ?></td>
                        <td class="format-number"><?php echo formatNumber($transaction['balance']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- طرف‌حساب‌های بدهکار/بستانکار -->
<?php if (!empty($contactBalances)): ?>
<div class="card">
    <div class="card-header">
        <i class="fas fa-balance-scale"></i>
        طرف‌حساب‌های بدهکار/بستانکار
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام طرف‌حساب</th>
                        <th>مانده</th>
                        <th>ارز</th>
                        <th>نوع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactBalances as $contact): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
                        <td class="format-number"><?php echo formatNumber($contact['balance']); ?></td>
                        <td><?php echo htmlspecialchars($contact['currency_code']); ?></td>
                        <td>
                            <?php if ($contact['balance'] > 0): ?>
                                <span class="badge badge-success">بستانکار</span>
                            <?php else: ?>
                                <span class="badge badge-danger">بدهکار</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// اضافه کردن داده‌های واقعی به نمودارها
document.addEventListener('DOMContentLoaded', function() {
    // نمودار موجودی ارزها
    const currencyChartCanvas = document.getElementById('currencyChart');
    if (currencyChartCanvas) {
        const currencyData = <?php 
            $currencyStats = $db->fetchAll("
                SELECT c.currency_name, COALESCE(SUM(ba.balance), 0) + COALESCE(SUM(cb.balance), 0) as total_balance
                FROM currencies c 
                LEFT JOIN bank_accounts ba ON c.id = ba.currency_id AND ba.status = 'active'
                LEFT JOIN cash_boxes cb ON c.id = cb.currency_id AND cb.status = 'active'
                WHERE c.status = 'active'
                GROUP BY c.id, c.currency_name
                HAVING total_balance > 0
                ORDER BY total_balance DESC
                LIMIT 6
            ");
            echo json_encode($currencyStats);
        ?>;
        
        const labels = currencyData.map(item => item.currency_name);
        const data = currencyData.map(item => parseFloat(item.total_balance));
        
        new Chart(currencyChartCanvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#f5576c',
                        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
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
    
    // نمودار فروش ماهانه
    const salesChartCanvas = document.getElementById('salesChart');
    if (salesChartCanvas) {
        const salesData = <?php
            $monthlyStats = $db->fetchAll("
                SELECT 
                    DATE_FORMAT(sale_date, '%Y-%m') as month,
                    COUNT(*) as sales_count,
                    COALESCE(SUM(total_received), 0) as total_sales
                FROM currency_sales 
                WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
                ORDER BY month ASC
            ");
            echo json_encode($monthlyStats);
        ?>;
        
        const months = salesData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('fa-IR', { year: 'numeric', month: 'long' });
        });
        const salesCounts = salesData.map(item => parseInt(item.sales_count));
        const salesAmounts = salesData.map(item => parseFloat(item.total_sales));
        
        new Chart(salesChartCanvas, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'تعداد فروش',
                    data: salesCounts,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'مبلغ فروش',
                    data: salesAmounts,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
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
                        type: 'linear',
                        display: true,
                        position: 'right'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>