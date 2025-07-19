<?php
$pageTitle = 'فروش ارز';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('currency_sales');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $sale_date = $_POST['sale_date'];
            $sale_time = $_POST['sale_time'] ?: null;
            $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
            $currency_from_id = (int)$_POST['currency_from_id'];
            $currency_to_id = (int)$_POST['currency_to_id'];
            $amount_from = (float)$_POST['amount_from'];
            $exchange_rate = (float)$_POST['exchange_rate'];
            $commission = (float)$_POST['commission'];
            $payment_method = $_POST['payment_method'];
            $bank_account_id = ($payment_method === 'bank_transfer' && !empty($_POST['bank_account_id'])) ? (int)$_POST['bank_account_id'] : null;
            $cash_box_id = ($payment_method === 'cash' && !empty($_POST['cash_box_id'])) ? (int)$_POST['cash_box_id'] : null;
            $notes = trim($_POST['notes']);
            
            // محاسبه مقادیر
            $amount_to = $amount_from * $exchange_rate;
            $total_received = $amount_to + $commission;
            
            $saleData = [
                'sale_date' => $sale_date,
                'sale_time' => $sale_time,
                'customer_id' => $customer_id,
                'currency_from_id' => $currency_from_id,
                'currency_to_id' => $currency_to_id,
                'amount_from' => $amount_from,
                'amount_to' => $amount_to,
                'exchange_rate' => $exchange_rate,
                'commission' => $commission,
                'total_received' => $total_received,
                'payment_method' => $payment_method,
                'bank_account_id' => $bank_account_id,
                'cash_box_id' => $cash_box_id,
                'notes' => $notes,
                'status' => 'completed',
                'created_by' => $currentUser['id']
            ];
            
            $saleId = $db->insert('currency_sales', $saleData);
            
            // بروزرسانی موجودی حساب/صندوق
            if ($payment_method === 'bank_transfer' && $bank_account_id) {
                $account = $db->fetchOne("SELECT balance FROM bank_accounts WHERE id = ?", [$bank_account_id]);
                $newBalance = $account['balance'] + $total_received;
                $db->update('bank_accounts', ['balance' => $newBalance], 'id = ?', ['id' => $bank_account_id]);
                
                // ثبت لاگ
                $db->insert('balance_logs', [
                    'account_type' => 'bank_account',
                    'account_id' => $bank_account_id,
                    'currency_id' => $currency_to_id,
                    'old_balance' => $account['balance'],
                    'new_balance' => $newBalance,
                    'change_amount' => $total_received,
                    'change_type' => 'deposit',
                    'reference_type' => 'currency_sale',
                    'reference_id' => $saleId,
                    'notes' => "فروش ارز شماره {$saleId}",
                    'created_by' => $currentUser['id']
                ]);
                
            } elseif ($payment_method === 'cash' && $cash_box_id) {
                $cashBox = $db->fetchOne("SELECT balance FROM cash_boxes WHERE id = ?", [$cash_box_id]);
                $newBalance = $cashBox['balance'] + $total_received;
                $db->update('cash_boxes', ['balance' => $newBalance], 'id = ?', ['id' => $cash_box_id]);
                
                // ثبت لاگ
                $db->insert('balance_logs', [
                    'account_type' => 'cash_box',
                    'account_id' => $cash_box_id,
                    'currency_id' => $currency_to_id,
                    'old_balance' => $cashBox['balance'],
                    'new_balance' => $newBalance,
                    'change_amount' => $total_received,
                    'change_type' => 'deposit',
                    'reference_type' => 'currency_sale',
                    'reference_id' => $saleId,
                    'notes' => "فروش ارز شماره {$saleId}",
                    'created_by' => $currentUser['id']
                ]);
            }
            
            // بروزرسانی حساب طرف‌حساب
            if ($customer_id) {
                $contactAccount = $db->fetchOne("SELECT * FROM contact_accounts WHERE contact_id = ? AND currency_id = ?", [$customer_id, $currency_from_id]);
                
                if ($contactAccount) {
                    $newBalance = $contactAccount['balance'] - $amount_from;
                    $db->update('contact_accounts', 
                        ['balance' => $newBalance, 'last_transaction_date' => date('Y-m-d H:i:s')], 
                        'id = ?', ['id' => $contactAccount['id']]);
                } else {
                    $db->insert('contact_accounts', [
                        'contact_id' => $customer_id,
                        'currency_id' => $currency_from_id,
                        'balance' => -$amount_from,
                        'last_transaction_date' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            header('Location: currency_sales.php?success=فروش ارز با موفقیت ثبت شد');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // بررسی وجود تطبیق واریزی
            $hasAllocations = $db->fetchOne("SELECT COUNT(*) as count FROM deposit_allocations WHERE currency_sale_id = ?", [$id])['count'];
            if ($hasAllocations > 0) {
                throw new Exception('نمی‌توان فروشی را حذف کرد که واریزی‌های تطبیق یافته دارد');
            }
            
            $db->update('currency_sales', ['status' => 'cancelled'], 'id = ?', ['id' => $id]);
            header('Location: currency_sales.php?success=فروش ارز لغو شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: currency_sales.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// فیلترها
$filter_status = $_GET['status'] ?? 'all';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$whereClause = "1=1";
$params = [];

if ($filter_status !== 'all') {
    $whereClause .= " AND cs.status = ?";
    $params[] = $filter_status;
}

if ($filter_date_from) {
    $whereClause .= " AND cs.sale_date >= ?";
    $params[] = $filter_date_from;
}

if ($filter_date_to) {
    $whereClause .= " AND cs.sale_date <= ?";
    $params[] = $filter_date_to;
}

// دریافت لیست فروش‌ها
$sales = $db->fetchAll("
    SELECT cs.*, 
           cf.currency_code as from_currency, cf.symbol as from_symbol,
           ct.currency_code as to_currency, ct.symbol as to_symbol,
           c.contact_name as customer_name,
           ba.account_name as bank_account_name,
           b.bank_name,
           cb.name as cash_box_name,
           u.full_name as created_by_name,
           COALESCE((SELECT SUM(allocated_amount) FROM deposit_allocations WHERE currency_sale_id = cs.id), 0) as allocated_amount
    FROM currency_sales cs
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    LEFT JOIN contacts c ON cs.customer_id = c.id
    LEFT JOIN bank_accounts ba ON cs.bank_account_id = ba.id
    LEFT JOIN banks b ON ba.bank_id = b.id
    LEFT JOIN cash_boxes cb ON cs.cash_box_id = cb.id
    LEFT JOIN users u ON cs.created_by = u.id
    WHERE {$whereClause}
    ORDER BY cs.sale_date DESC, cs.id DESC
", $params);

// دریافت لیست ارزها
$currencies = $db->fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY currency_name");

// دریافت لیست مشتریان
$customers = $db->fetchAll("SELECT * FROM contacts WHERE status = 'active' AND contact_type IN ('customer', 'other') ORDER BY contact_name");

// دریافت لیست حساب‌های بانکی
$bankAccounts = $db->fetchAll("
    SELECT ba.*, b.bank_name, c.currency_code
    FROM bank_accounts ba
    JOIN banks b ON ba.bank_id = b.id
    JOIN currencies c ON ba.currency_id = c.id
    WHERE ba.status = 'active'
    ORDER BY b.bank_name, ba.account_name
");

// دریافت لیست صندوق‌ها
$cashBoxes = $db->fetchAll("
    SELECT cb.*, c.currency_code
    FROM cash_boxes cb
    JOIN currencies c ON cb.currency_id = c.id
    WHERE cb.status = 'active'
    ORDER BY cb.name
");

// آمار کلی
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_sales,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sales,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_sales,
        SUM(CASE WHEN status = 'completed' THEN total_received ELSE 0 END) as total_revenue
    FROM currency_sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
?>

<!-- فیلترها -->
<div class="card">
    <div class="card-body p-20">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <div class="form-group mb-0">
                <label>وضعیت</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <label>از تاریخ</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $filter_date_from; ?>">
            </div>
            
            <div class="form-group mb-0">
                <label>تا تاریخ</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $filter_date_to; ?>">
            </div>
            
            <button type="submit" class="btn btn-info">
                <i class="fas fa-filter"></i>
                فیلتر
            </button>
            
            <a href="currency_sales.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                پاک کردن فیلتر
            </a>
        </form>
    </div>
</div>

<!-- آمار -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['total_sales']; ?></h3>
        <p>کل فروش‌ها (30 روز اخیر)</p>
        <i class="fas fa-chart-line icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3><?php echo $stats['completed_sales']; ?></h3>
        <p>فروش‌های تکمیل شده</p>
        <i class="fas fa-check-circle icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3><?php echo $stats['pending_sales']; ?></h3>
        <p>فروش‌های در انتظار</p>
        <i class="fas fa-clock icon"></i>
    </div>
    
    <div class="stat-card danger">
        <h3 class="format-number"><?php echo formatNumber($stats['total_revenue']); ?></h3>
        <p>کل درآمد (ریال)</p>
        <i class="fas fa-money-bill-wave icon"></i>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-area"></i>
        فروش ارز
        <button class="btn btn-success btn-small" style="float: left;" data-modal="addSaleModal">
            <i class="fas fa-plus"></i>
            ثبت فروش جدید
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>از ارز</th>
                        <th>به ارز</th>
                        <th>مقدار</th>
                        <th>نرخ</th>
                        <th>کمیسیون</th>
                        <th>کل دریافتی</th>
                        <th>تطبیق یافته</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <?php echo jalaliDate($sale['sale_date']); ?>
                            <?php if ($sale['sale_time']): ?>
                            <br><small class="text-muted"><?php echo date('H:i', strtotime($sale['sale_time'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sale['customer_name']): ?>
                                <?php echo htmlspecialchars($sale['customer_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">نامشخص</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($sale['from_currency']); ?>
                            </span>
                            <br><small class="format-number"><?php echo formatNumber($sale['amount_from']); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <?php echo htmlspecialchars($sale['to_currency']); ?>
                            </span>
                            <br><small class="format-number"><?php echo formatNumber($sale['amount_to']); ?></small>
                        </td>
                        <td class="format-number"><?php echo formatNumber($sale['amount_from']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['exchange_rate']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['commission']); ?></td>
                        <td class="format-number"><?php echo formatNumber($sale['total_received']); ?></td>
                        <td>
                            <?php 
                            $remaining = $sale['total_received'] - $sale['allocated_amount'];
                            ?>
                            <span class="format-number"><?php echo formatNumber($sale['allocated_amount']); ?></span>
                            <?php if ($remaining > 0): ?>
                            <br><small class="text-warning">مانده: <?php echo formatNumber($remaining); ?></small>
                            <?php else: ?>
                            <br><small class="text-success">کامل</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sale['status'] === 'completed'): ?>
                                <span class="badge badge-success">تکمیل شده</span>
                            <?php elseif ($sale['status'] === 'pending'): ?>
                                <span class="badge badge-warning">در انتظار</span>
                            <?php else: ?>
                                <span class="badge badge-danger">لغو شده</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-small view-sale-btn" 
                                    data-sale='<?php echo json_encode($sale); ?>'>
                                <i class="fas fa-eye"></i>
                                جزئیات
                            </button>
                            
                            <?php if ($sale['status'] !== 'cancelled'): ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از لغو این فروش اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    <i class="fas fa-times"></i>
                                    لغو
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal افزودن فروش -->
<div id="addSaleModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h4>ثبت فروش ارز جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="saleForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sale_date">تاریخ فروش *</label>
                        <input type="date" name="sale_date" id="sale_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sale_time">ساعت فروش</label>
                        <input type="time" name="sale_time" id="sale_time" class="form-control" 
                               value="<?php echo date('H:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="customer_id">مشتری</label>
                        <select name="customer_id" id="customer_id" class="form-control">
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['contact_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="currency_from_id">از ارز *</label>
                        <select name="currency_from_id" id="currency_from_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>" 
                                    data-rate="<?php echo $currency['exchange_rate']; ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                                (<?php echo htmlspecialchars($currency['currency_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="currency_to_id">به ارز *</label>
                        <select name="currency_to_id" id="currency_to_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>" 
                                    data-rate="<?php echo $currency['exchange_rate']; ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                                (<?php echo htmlspecialchars($currency['currency_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount_from">مقدار فروش *</label>
                        <input type="number" name="amount_from" id="amount_from" class="form-control" 
                               step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="exchange_rate">نرخ تبدیل *</label>
                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" 
                               step="0.0001" required>
                        <small class="text-muted">نرخ تبدیل از ارز مبدا به ارز مقصد</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="commission">کمیسیون</label>
                        <input type="number" name="commission" id="commission" class="form-control" 
                               step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>کل مبلغ دریافتی</label>
                        <input type="text" id="total_display" class="form-control" readonly 
                               style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_method">روش پرداخت *</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <option value="cash">نقدی</option>
                            <option value="bank_transfer">انتقال بانکی</option>
                            <option value="card">کارتی</option>
                        </select>
                    </div>
                    <div class="form-group" id="payment_details" style="display: none;">
                        <label id="payment_details_label">جزئیات پرداخت</label>
                        <select name="bank_account_id" id="bank_account_id" class="form-control">
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($bankAccounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>" data-currency="<?php echo $account['currency_id']; ?>">
                                <?php echo htmlspecialchars($account['bank_name']); ?> - 
                                <?php echo htmlspecialchars($account['account_name']); ?>
                                (<?php echo htmlspecialchars($account['currency_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="cash_box_id" id="cash_box_id" class="form-control" style="display: none;">
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($cashBoxes as $cashBox): ?>
                            <option value="<?php echo $cashBox['id']; ?>" data-currency="<?php echo $cashBox['currency_id']; ?>">
                                <?php echo htmlspecialchars($cashBox['name']); ?>
                                (<?php echo htmlspecialchars($cashBox['currency_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">توضیحات</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ثبت فروش
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addSaleModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal مشاهده جزئیات -->
<div id="viewSaleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>جزئیات فروش ارز</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="saleDetails">
            <!-- محتوا توسط JavaScript پر می‌شود -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // محاسبه خودکار
    function calculateTotal() {
        const amountFrom = parseFloat(document.getElementById('amount_from').value) || 0;
        const exchangeRate = parseFloat(document.getElementById('exchange_rate').value) || 0;
        const commission = parseFloat(document.getElementById('commission').value) || 0;
        
        const amountTo = amountFrom * exchangeRate;
        const total = amountTo + commission;
        
        document.getElementById('total_display').value = total.toLocaleString('fa-IR') + ' ریال';
    }
    
    // اتصال event listener ها
    ['amount_from', 'exchange_rate', 'commission'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', calculateTotal);
        }
    });
    
    // مدیریت تغییر ارزها
    document.getElementById('currency_from_id').addEventListener('change', function() {
        updateExchangeRate();
    });
    
    document.getElementById('currency_to_id').addEventListener('change', function() {
        updateExchangeRate();
    });
    
    function updateExchangeRate() {
        const fromSelect = document.getElementById('currency_from_id');
        const toSelect = document.getElementById('currency_to_id');
        const rateInput = document.getElementById('exchange_rate');
        
        if (fromSelect.value && toSelect.value) {
            const fromOption = fromSelect.options[fromSelect.selectedIndex];
            const toOption = toSelect.options[toSelect.selectedIndex];
            
            const fromRate = parseFloat(fromOption.dataset.rate) || 1;
            const toRate = parseFloat(toOption.dataset.rate) || 1;
            
            // محاسبه نرخ تبدیل
            const exchangeRate = toRate / fromRate;
            rateInput.value = exchangeRate.toFixed(4);
            
            calculateTotal();
        }
    }
    
    // مدیریت روش پرداخت
    document.getElementById('payment_method').addEventListener('change', function() {
        const paymentDetails = document.getElementById('payment_details');
        const bankAccountSelect = document.getElementById('bank_account_id');
        const cashBoxSelect = document.getElementById('cash_box_id');
        const label = document.getElementById('payment_details_label');
        
        if (this.value === 'bank_transfer' || this.value === 'card') {
            paymentDetails.style.display = 'block';
            bankAccountSelect.style.display = 'block';
            cashBoxSelect.style.display = 'none';
            label.textContent = 'حساب بانکی';
        } else if (this.value === 'cash') {
            paymentDetails.style.display = 'block';
            bankAccountSelect.style.display = 'none';
            cashBoxSelect.style.display = 'block';
            label.textContent = 'صندوق';
        } else {
            paymentDetails.style.display = 'none';
        }
    });
    
    // مدیریت دکمه‌های مشاهده
    document.querySelectorAll('.view-sale-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sale = JSON.parse(this.dataset.sale);
            const modal = document.getElementById('viewSaleModal');
            const detailsDiv = document.getElementById('saleDetails');
            
            const paymentMethodLabels = {
                'cash': 'نقدی',
                'bank_transfer': 'انتقال بانکی',
                'card': 'کارتی'
            };
            
            const statusLabels = {
                'completed': 'تکمیل شده',
                'pending': 'در انتظار',
                'cancelled': 'لغو شده'
            };
            
            detailsDiv.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <strong>تاریخ:</strong> ${sale.sale_date}
                        ${sale.sale_time ? ` - ${sale.sale_time}` : ''}
                    </div>
                    <div class="form-group">
                        <strong>مشتری:</strong> ${sale.customer_name || 'نامشخص'}
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>از ارز:</strong> ${sale.from_currency}
                    </div>
                    <div class="form-group">
                        <strong>به ارز:</strong> ${sale.to_currency}
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>مقدار فروش:</strong> ${parseFloat(sale.amount_from).toLocaleString('fa-IR')}
                    </div>
                    <div class="form-group">
                        <strong>نرخ تبدیل:</strong> ${parseFloat(sale.exchange_rate).toLocaleString('fa-IR')}
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>مقدار دریافتی:</strong> ${parseFloat(sale.amount_to).toLocaleString('fa-IR')}
                    </div>
                    <div class="form-group">
                        <strong>کمیسیون:</strong> ${parseFloat(sale.commission).toLocaleString('fa-IR')}
                    </div>
                </div>
                
                <div class="form-group">
                    <strong>کل مبلغ دریافتی:</strong> 
                    <span style="font-size: 1.2rem; color: #27ae60; font-weight: bold;">
                        ${parseFloat(sale.total_received).toLocaleString('fa-IR')} ${sale.to_currency}
                    </span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>روش پرداخت:</strong> ${paymentMethodLabels[sale.payment_method] || sale.payment_method}
                    </div>
                    <div class="form-group">
                        <strong>وضعیت:</strong> 
                        <span class="badge badge-${sale.status === 'completed' ? 'success' : sale.status === 'pending' ? 'warning' : 'danger'}">
                            ${statusLabels[sale.status] || sale.status}
                        </span>
                    </div>
                </div>
                
                ${(sale.bank_account_name || sale.cash_box_name) ? `
                <div class="form-group">
                    <strong>جزئیات پرداخت:</strong> 
                    ${sale.bank_account_name ? `${sale.bank_name} - ${sale.bank_account_name}` : ''}
                    ${sale.cash_box_name ? sale.cash_box_name : ''}
                </div>
                ` : ''}
                
                <div class="form-group">
                    <strong>مقدار تطبیق یافته:</strong> ${parseFloat(sale.allocated_amount).toLocaleString('fa-IR')}
                    <br>
                    <strong>مانده:</strong> ${(parseFloat(sale.total_received) - parseFloat(sale.allocated_amount)).toLocaleString('fa-IR')}
                </div>
                
                ${sale.notes ? `
                <div class="form-group">
                    <strong>توضیحات:</strong> ${sale.notes}
                </div>
                ` : ''}
                
                <div class="form-group" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <strong>ثبت شده توسط:</strong> ${sale.created_by_name}
                    <br>
                    <strong>تاریخ ثبت:</strong> ${sale.created_at}
                </div>
            `;
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>