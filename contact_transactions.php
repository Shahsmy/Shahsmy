<?php
require_once 'includes/auth.php';
checkPermission('view_reports');

$db = getDB();

// Get contacts for filter
$stmt = $db->prepare("SELECT id, name, phone FROM contacts ORDER BY name");
$stmt->execute();
$contacts = $stmt->fetchAll();

// Get currencies for filter
$stmt = $db->prepare("SELECT id, name, symbol FROM currencies ORDER BY name");
$stmt->execute();
$currencies = $stmt->fetchAll();

// Filter parameters
$contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
$currency_id = isset($_GET['currency_id']) ? (int)$_GET['currency_id'] : 0;
$transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$conditions = [];
$params = [];

if ($contact_id) {
    $conditions[] = "ca.contact_id = ?";
    $params[] = $contact_id;
}

if ($currency_id) {
    $conditions[] = "ca.currency_id = ?";
    $params[] = $currency_id;
}

if ($transaction_type) {
    $conditions[] = "ca.transaction_type = ?";
    $params[] = $transaction_type;
}

if ($date_from) {
    $conditions[] = "DATE(ca.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "DATE(ca.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get transactions
$stmt = $db->prepare("
    SELECT ca.*, c.name as contact_name, c.phone, curr.name as currency_name, curr.symbol,
           u.username as created_by_name,
           CASE 
               WHEN ca.reference_type = 'currency_sale' THEN 'فروش ارز'
               WHEN ca.reference_type = 'currency_purchase' THEN 'خرید ارز'
               WHEN ca.reference_type = 'deposit_allocation' THEN 'تخصیص واریز'
               WHEN ca.reference_type = 'withdrawal_allocation' THEN 'تخصیص برداشت'
               ELSE ca.reference_type
           END as reference_type_name,
           @running_balance := @running_balance + ca.amount as running_balance
    FROM contact_accounts ca
    JOIN contacts c ON ca.contact_id = c.id
    JOIN currencies curr ON ca.currency_id = curr.id
    JOIN users u ON ca.created_by = u.id
    CROSS JOIN (SELECT @running_balance := 0) r
    $where_clause
    ORDER BY ca.created_at ASC, ca.id ASC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate balance summary by currency for selected contact
$balance_summary = [];
if ($contact_id) {
    $stmt = $db->prepare("
        SELECT ca.currency_id, curr.name as currency_name, curr.symbol,
               SUM(ca.amount) as balance
        FROM contact_accounts ca
        JOIN currencies curr ON ca.currency_id = curr.id
        WHERE ca.contact_id = ?
        GROUP BY ca.currency_id, curr.name, curr.symbol
        HAVING balance != 0
        ORDER BY curr.name
    ");
    $stmt->execute([$contact_id]);
    $balance_summary = $stmt->fetchAll();
}

$page_title = "تراکنش‌های مخاطبین";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>تراکنش‌های مخاطبین</h2>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">فیلترها</h5>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">مخاطب</label>
                                <select name="contact_id" class="form-select">
                                    <option value="">همه مخاطبین</option>
                                    <?php foreach ($contacts as $contact): ?>
                                    <option value="<?= $contact['id'] ?>" <?= $contact_id == $contact['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($contact['name'] . ' - ' . $contact['phone']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label class="form-label">ارز</label>
                                <select name="currency_id" class="form-select">
                                    <option value="">همه ارزها</option>
                                    <?php foreach ($currencies as $currency): ?>
                                    <option value="<?= $currency['id'] ?>" <?= $currency_id == $currency['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($currency['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label class="form-label">نوع تراکنش</label>
                                <select name="transaction_type" class="form-select">
                                    <option value="">همه انواع</option>
                                    <option value="sale" <?= $transaction_type == 'sale' ? 'selected' : '' ?>>فروش</option>
                                    <option value="purchase" <?= $transaction_type == 'purchase' ? 'selected' : '' ?>>خرید</option>
                                    <option value="deposit" <?= $transaction_type == 'deposit' ? 'selected' : '' ?>>واریز</option>
                                    <option value="withdrawal" <?= $transaction_type == 'withdrawal' ? 'selected' : '' ?>>برداشت</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label class="form-label">از تاریخ</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label class="form-label">تا تاریخ</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            
                            <div class="col-md-1 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">جستجو</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Balance Summary -->
    <?php if ($contact_id && $balance_summary): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">خلاصه موجودی</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($balance_summary as $balance): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert <?= $balance['balance'] >= 0 ? 'alert-success' : 'alert-danger' ?> mb-0">
                                <strong><?= htmlspecialchars($balance['currency_name']) ?>:</strong>
                                <?= number_format($balance['balance'], 2) ?> <?= htmlspecialchars($balance['symbol']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تراکنش‌ها (<?= count($transactions) ?> مورد)</h5>
                    <?php if ($transactions): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> خروجی اکسل
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>مخاطب</th>
                                    <th>ارز</th>
                                    <th>نوع تراکنش</th>
                                    <th>مرجع</th>
                                    <th>مبلغ</th>
                                    <th>موجودی جاری</th>
                                    <th>توضیحات</th>
                                    <th>ثبت شده توسط</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $running_balance = 0;
                                foreach ($transactions as $transaction): 
                                    $running_balance += $transaction['amount'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars(formatJalaliDate($transaction['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($transaction['contact_name']) ?></td>
                                    <td><?= htmlspecialchars($transaction['currency_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getTransactionTypeBadge($transaction['transaction_type']) ?>">
                                            <?= getTransactionTypeName($transaction['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($transaction['reference_type_name']) ?></td>
                                    <td class="<?= $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($transaction['amount'] >= 0 ? '+' : '') . number_format($transaction['amount'], 2) ?>
                                    </td>
                                    <td><?= number_format($running_balance, 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= htmlspecialchars($transaction['created_by_name']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($transactions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">تراکنشی یافت نشد</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    // Create a copy of the table for export
    const table = document.getElementById('transactionsTable').cloneNode(true);
    
    // Create a new workbook
    const wb = XLSX.utils.table_to_book(table, {sheet: "Transactions"});
    
    // Save the file
    const filename = `contact_transactions_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(wb, filename);
}

function getTransactionTypeBadge(type) {
    switch(type) {
        case 'sale': return 'success';
        case 'purchase': return 'info';
        case 'deposit': return 'primary';
        case 'withdrawal': return 'warning';
        default: return 'secondary';
    }
}

function getTransactionTypeName(type) {
    switch(type) {
        case 'sale': return 'فروش';
        case 'purchase': return 'خرید';
        case 'deposit': return 'واریز';
        case 'withdrawal': return 'برداشت';
        default: return type;
    }
}

// Add XLSX library for Excel export
const script = document.createElement('script');
script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
document.head.appendChild(script);
</script>

<?php 
function getTransactionTypeBadge($type) {
    switch($type) {
        case 'sale': return 'success';
        case 'purchase': return 'info';
        case 'deposit': return 'primary';
        case 'withdrawal': return 'warning';
        default: return 'secondary';
    }
}

function getTransactionTypeName($type) {
    switch($type) {
        case 'sale': return 'فروش';
        case 'purchase': return 'خرید';
        case 'deposit': return 'واریز';
        case 'withdrawal': return 'برداشت';
        default: return $type;
    }
}

include 'includes/footer.php'; 
?>