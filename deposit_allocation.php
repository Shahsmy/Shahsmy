<?php
$pageTitle = 'تطبیق واریزی‌ها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('deposit_allocation');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'allocate') {
            $transaction_id = (int)$_POST['transaction_id'];
            $sale_id = (int)$_POST['sale_id'];
            $allocated_amount = (float)$_POST['allocated_amount'];
            $notes = trim($_POST['notes']);
            
            // دریافت اطلاعات تراکنش
            $transaction = $db->fetchOne("SELECT * FROM bank_transactions WHERE id = ?", [$transaction_id]);
            if (!$transaction) {
                throw new Exception('تراکنش یافت نشد');
            }
            
            // دریافت اطلاعات فروش
            $sale = $db->fetchOne("SELECT * FROM currency_sales WHERE id = ?", [$sale_id]);
            if (!$sale) {
                throw new Exception('فروش یافت نشد');
            }
            
            // بررسی مقدار تطبیق
            if ($allocated_amount <= 0 || $allocated_amount > $transaction['deposit_amount']) {
                throw new Exception('مقدار تطبیق نامعتبر است');
            }
            
            // بررسی مانده فروش
            $currentAllocated = $db->fetchOne("SELECT COALESCE(SUM(allocated_amount), 0) as total FROM deposit_allocations WHERE currency_sale_id = ?", [$sale_id])['total'];
            $remaining = $sale['total_received'] - $currentAllocated;
            
            if ($allocated_amount > $remaining) {
                throw new Exception('مقدار تطبیق بیشتر از مانده فروش است');
            }
            
            // ثبت تطبیق
            $allocationData = [
                'bank_transaction_id' => $transaction_id,
                'currency_sale_id' => $sale_id,
                'allocated_amount' => $allocated_amount,
                'allocation_date' => date('Y-m-d'),
                'notes' => $notes,
                'created_by' => $currentUser['id']
            ];
            
            $db->insert('deposit_allocations', $allocationData);
            
            // بروزرسانی وضعیت تراکنش
            $totalAllocatedToTransaction = $db->fetchOne("SELECT COALESCE(SUM(allocated_amount), 0) as total FROM deposit_allocations WHERE bank_transaction_id = ?", [$transaction_id])['total'];
            
            if ($totalAllocatedToTransaction >= $transaction['deposit_amount']) {
                $db->update('bank_transactions', ['status' => 'fully_allocated'], 'id = ?', ['id' => $transaction_id]);
            } else {
                $db->update('bank_transactions', ['status' => 'partially_allocated'], 'id = ?', ['id' => $transaction_id]);
            }
            
            header('Location: deposit_allocation.php?success=تطبیق با موفقیت ثبت شد');
            exit;
            
        } elseif ($action === 'delete_allocation') {
            $allocation_id = (int)$_POST['allocation_id'];
            
            // دریافت اطلاعات تطبیق
            $allocation = $db->fetchOne("SELECT * FROM deposit_allocations WHERE id = ?", [$allocation_id]);
            if (!$allocation) {
                throw new Exception('تطبیق یافت نشد');
            }
            
            // حذف تطبیق
            $db->query("DELETE FROM deposit_allocations WHERE id = ?", [$allocation_id]);
            
            // بروزرسانی وضعیت تراکنش
            $totalAllocated = $db->fetchOne("SELECT COALESCE(SUM(allocated_amount), 0) as total FROM deposit_allocations WHERE bank_transaction_id = ?", [$allocation['bank_transaction_id']])['total'];
            $transaction = $db->fetchOne("SELECT deposit_amount FROM bank_transactions WHERE id = ?", [$allocation['bank_transaction_id']]);
            
            if ($totalAllocated >= $transaction['deposit_amount']) {
                $status = 'fully_allocated';
            } elseif ($totalAllocated > 0) {
                $status = 'partially_allocated';
            } else {
                $status = 'unallocated';
            }
            
            $db->update('bank_transactions', ['status' => $status], 'id = ?', ['id' => $allocation['bank_transaction_id']]);
            
            header('Location: deposit_allocation.php?success=تطبیق حذف شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: deposit_allocation.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// فیلترها
$filter_status = $_GET['status'] ?? 'unallocated';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$whereClause = "bt.deposit_amount > 0";
$params = [];

if ($filter_status === 'unallocated') {
    $whereClause .= " AND bt.status = 'unallocated'";
} elseif ($filter_status === 'partially_allocated') {
    $whereClause .= " AND bt.status = 'partially_allocated'";
} elseif ($filter_status === 'fully_allocated') {
    $whereClause .= " AND bt.status = 'fully_allocated'";
}

if ($filter_date_from) {
    $whereClause .= " AND bt.transaction_date >= ?";
    $params[] = $filter_date_from;
}

if ($filter_date_to) {
    $whereClause .= " AND bt.transaction_date <= ?";
    $params[] = $filter_date_to;
}

// دریافت لیست واریزی‌ها
$deposits = $db->fetchAll("
    SELECT bt.*, ba.account_name, b.bank_name, c.currency_code,
           COALESCE((SELECT SUM(allocated_amount) FROM deposit_allocations WHERE bank_transaction_id = bt.id), 0) as allocated_amount
    FROM bank_transactions bt
    LEFT JOIN bank_accounts ba ON bt.bank_account_id = ba.id
    LEFT JOIN banks b ON ba.bank_id = b.id
    LEFT JOIN currencies c ON ba.currency_id = c.id
    WHERE {$whereClause}
    ORDER BY bt.transaction_date DESC, bt.id DESC
", $params);

// دریافت لیست فروش‌های تکمیل نشده
$sales = $db->fetchAll("
    SELECT cs.*, 
           cf.currency_code as from_currency,
           ct.currency_code as to_currency,
           c.contact_name as customer_name,
           COALESCE((SELECT SUM(allocated_amount) FROM deposit_allocations WHERE currency_sale_id = cs.id), 0) as allocated_amount,
           (cs.total_received - COALESCE((SELECT SUM(allocated_amount) FROM deposit_allocations WHERE currency_sale_id = cs.id), 0)) as remaining_amount
    FROM currency_sales cs
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    LEFT JOIN contacts c ON cs.customer_id = c.id
    WHERE cs.status = 'completed'
    HAVING remaining_amount > 0
    ORDER BY cs.sale_date DESC
");

// دریافت تطبیق‌های اخیر
$recentAllocations = $db->fetchAll("
    SELECT da.*, 
           bt.payer_name, bt.deposit_amount as transaction_amount, bt.transaction_date,
           cs.total_received as sale_amount,
           cf.currency_code as from_currency,
           ct.currency_code as to_currency,
           c.contact_name as customer_name,
           u.full_name as created_by_name
    FROM deposit_allocations da
    JOIN bank_transactions bt ON da.bank_transaction_id = bt.id
    JOIN currency_sales cs ON da.currency_sale_id = cs.id
    LEFT JOIN currencies cf ON cs.currency_from_id = cf.id
    LEFT JOIN currencies ct ON cs.currency_to_id = ct.id
    LEFT JOIN contacts c ON cs.customer_id = c.id
    LEFT JOIN users u ON da.created_by = u.id
    ORDER BY da.created_at DESC
    LIMIT 20
");

// آمار کلی
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_deposits,
        SUM(CASE WHEN status = 'unallocated' THEN 1 ELSE 0 END) as unallocated_count,
        SUM(CASE WHEN status = 'partially_allocated' THEN 1 ELSE 0 END) as partial_count,
        SUM(CASE WHEN status = 'fully_allocated' THEN 1 ELSE 0 END) as full_count,
        SUM(CASE WHEN status = 'unallocated' THEN deposit_amount ELSE 0 END) as unallocated_amount
    FROM bank_transactions
    WHERE deposit_amount > 0
");
?>

<!-- فیلترها -->
<div class="card">
    <div class="card-body p-20">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <div class="form-group mb-0">
                <label>وضعیت تطبیق</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="unallocated" <?php echo $filter_status === 'unallocated' ? 'selected' : ''; ?>>تطبیق نیافته</option>
                    <option value="partially_allocated" <?php echo $filter_status === 'partially_allocated' ? 'selected' : ''; ?>>تطبیق جزئی</option>
                    <option value="fully_allocated" <?php echo $filter_status === 'fully_allocated' ? 'selected' : ''; ?>>تطبیق کامل</option>
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
            
            <a href="deposit_allocation.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                پاک کردن فیلتر
            </a>
        </form>
    </div>
</div>

<!-- آمار -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['total_deposits']; ?></h3>
        <p>کل واریزی‌ها</p>
        <i class="fas fa-arrow-down icon"></i>
    </div>
    
    <div class="stat-card danger">
        <h3><?php echo $stats['unallocated_count']; ?></h3>
        <p>تطبیق نیافته</p>
        <i class="fas fa-exclamation-circle icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3><?php echo $stats['partial_count']; ?></h3>
        <p>تطبیق جزئی</p>
        <i class="fas fa-clock icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3><?php echo $stats['full_count']; ?></h3>
        <p>تطبیق کامل</p>
        <i class="fas fa-check-circle icon"></i>
    </div>
</div>

<div class="form-row">
    <!-- لیست واریزی‌ها -->
    <div class="form-group" style="flex: 2;">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-arrow-down text-success"></i>
                واریزی‌های نیازمند تطبیق
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead style="position: sticky; top: 0; background: #f8f9fa;">
                            <tr>
                                <th>تاریخ</th>
                                <th>واریزکننده</th>
                                <th>مبلغ</th>
                                <th>تطبیق یافته</th>
                                <th>مانده</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit): ?>
                            <?php $remaining = $deposit['deposit_amount'] - $deposit['allocated_amount']; ?>
                            <tr class="<?php echo $deposit['status'] === 'unallocated' ? 'table-warning' : ''; ?>">
                                <td><?php echo jalaliDate($deposit['transaction_date']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($deposit['payer_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($deposit['bank_name']); ?></small>
                                </td>
                                <td class="format-number"><?php echo formatNumber($deposit['deposit_amount']); ?></td>
                                <td class="format-number"><?php echo formatNumber($deposit['allocated_amount']); ?></td>
                                <td class="format-number text-<?php echo $remaining > 0 ? 'danger' : 'success'; ?>">
                                    <?php echo formatNumber($remaining); ?>
                                </td>
                                <td>
                                    <?php if ($deposit['status'] === 'unallocated'): ?>
                                        <span class="badge badge-danger">تطبیق نیافته</span>
                                    <?php elseif ($deposit['status'] === 'partially_allocated'): ?>
                                        <span class="badge badge-warning">تطبیق جزئی</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">تطبیق کامل</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($remaining > 0): ?>
                                    <button class="btn btn-success btn-small allocate-btn" 
                                            data-transaction='<?php echo json_encode($deposit); ?>'>
                                        <i class="fas fa-link"></i>
                                        تطبیق
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-info btn-small view-transaction-btn" 
                                            data-transaction='<?php echo json_encode($deposit); ?>'>
                                        <i class="fas fa-eye"></i>
                                        جزئیات
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- لیست فروش‌های تکمیل نشده -->
    <div class="form-group" style="flex: 1;">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-area text-primary"></i>
                فروش‌های نیازمند تطبیق
            </div>
            <div class="card-body">
                <div style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($sales as $sale): ?>
                    <div class="card mb-3 sale-item" data-sale-id="<?php echo $sale['id']; ?>" style="cursor: pointer; border: 2px solid #dee2e6;">
                        <div class="card-body p-3">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($sale['customer_name'] ?: 'نامشخص'); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo jalaliDate($sale['sale_date']); ?>
                                    </small>
                                </div>
                                <span class="badge badge-primary"><?php echo formatNumber($sale['remaining_amount']); ?></span>
                            </div>
                            
                            <div class="mt-2">
                                <small>
                                    <strong><?php echo htmlspecialchars($sale['from_currency']); ?></strong>
                                    →
                                    <strong><?php echo htmlspecialchars($sale['to_currency']); ?></strong>
                                </small>
                                <br>
                                <small class="text-muted">
                                    کل: <?php echo formatNumber($sale['total_received']); ?> |
                                    تطبیق: <?php echo formatNumber($sale['allocated_amount']); ?>
                                </small>
                            </div>
                            
                            <div class="progress mt-2" style="height: 5px;">
                                <?php $percentage = ($sale['allocated_amount'] / $sale['total_received']) * 100; ?>
                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تطبیق‌های اخیر -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i>
        تطبیق‌های اخیر
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>تاریخ تطبیق</th>
                        <th>واریزکننده</th>
                        <th>مشتری</th>
                        <th>مبلغ تطبیق</th>
                        <th>ارز</th>
                        <th>تطبیق‌کننده</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAllocations as $allocation): ?>
                    <tr>
                        <td><?php echo jalaliDate($allocation['allocation_date']); ?></td>
                        <td><?php echo htmlspecialchars($allocation['payer_name']); ?></td>
                        <td><?php echo htmlspecialchars($allocation['customer_name'] ?: 'نامشخص'); ?></td>
                        <td class="format-number"><?php echo formatNumber($allocation['allocated_amount']); ?></td>
                        <td><?php echo htmlspecialchars($allocation['to_currency']); ?></td>
                        <td><?php echo htmlspecialchars($allocation['created_by_name']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این تطبیق اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete_allocation">
                                <input type="hidden" name="allocation_id" value="<?php echo $allocation['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    <i class="fas fa-unlink"></i>
                                    حذف تطبیق
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal تطبیق -->
<div id="allocateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>تطبیق واریزی با فروش</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="allocateForm">
                <input type="hidden" name="action" value="allocate">
                <input type="hidden" name="transaction_id" id="modal_transaction_id">
                <input type="hidden" name="sale_id" id="modal_sale_id">
                
                <div class="alert alert-info" id="transaction_info">
                    <!-- اطلاعات تراکنش -->
                </div>
                
                <div class="alert alert-warning" id="sale_info">
                    <!-- اطلاعات فروش -->
                </div>
                
                <div class="form-group">
                    <label for="allocated_amount">مبلغ تطبیق *</label>
                    <input type="number" name="allocated_amount" id="allocated_amount" 
                           class="form-control" step="0.01" required>
                    <small class="text-muted">حداکثر مبلغ قابل تطبیق: <span id="max_amount"></span></small>
                </div>
                
                <div class="form-group">
                    <label for="allocation_notes">توضیحات</label>
                    <textarea name="notes" id="allocation_notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-link"></i>
                        ثبت تطبیق
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('allocateModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal جزئیات تراکنش -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>جزئیات تراکنش</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="transactionDetails">
            <!-- محتوا توسط JavaScript پر می‌شود -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedTransaction = null;
    let selectedSale = null;
    
    // انتخاب فروش
    document.querySelectorAll('.sale-item').forEach(item => {
        item.addEventListener('click', function() {
            // حذف انتخاب قبلی
            document.querySelectorAll('.sale-item').forEach(s => {
                s.style.borderColor = '#dee2e6';
                s.style.backgroundColor = '';
            });
            
            // انتخاب فروش جدید
            this.style.borderColor = '#007bff';
            this.style.backgroundColor = '#f8f9fa';
            selectedSale = this.dataset.saleId;
        });
    });
    
    // دکمه‌های تطبیق
    document.querySelectorAll('.allocate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!selectedSale) {
                alert('لطفاً ابتدا یک فروش انتخاب کنید');
                return;
            }
            
            const transaction = JSON.parse(this.dataset.transaction);
            selectedTransaction = transaction;
            
            // نمایش modal
            const modal = document.getElementById('allocateModal');
            document.getElementById('modal_transaction_id').value = transaction.id;
            document.getElementById('modal_sale_id').value = selectedSale;
            
            // نمایش اطلاعات تراکنش
            const transactionInfo = document.getElementById('transaction_info');
            transactionInfo.innerHTML = `
                <strong>واریزی:</strong> ${transaction.payer_name}<br>
                <strong>مبلغ:</strong> ${parseFloat(transaction.deposit_amount).toLocaleString('fa-IR')}<br>
                <strong>تطبیق یافته:</strong> ${parseFloat(transaction.allocated_amount).toLocaleString('fa-IR')}<br>
                <strong>مانده:</strong> ${(parseFloat(transaction.deposit_amount) - parseFloat(transaction.allocated_amount)).toLocaleString('fa-IR')}
            `;
            
            // محاسبه حداکثر مبلغ قابل تطبیق
            const transactionRemaining = parseFloat(transaction.deposit_amount) - parseFloat(transaction.allocated_amount);
            document.getElementById('max_amount').textContent = transactionRemaining.toLocaleString('fa-IR');
            document.getElementById('allocated_amount').max = transactionRemaining;
            document.getElementById('allocated_amount').value = transactionRemaining;
            
            modal.style.display = 'block';
        });
    });
    
    // دکمه‌های مشاهده جزئیات
    document.querySelectorAll('.view-transaction-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transaction = JSON.parse(this.dataset.transaction);
            const modal = document.getElementById('transactionModal');
            const detailsDiv = document.getElementById('transactionDetails');
            
            detailsDiv.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <strong>تاریخ:</strong> ${transaction.transaction_date}
                    </div>
                    <div class="form-group">
                        <strong>ساعت:</strong> ${transaction.transaction_time || '-'}
                    </div>
                </div>
                
                <div class="form-group">
                    <strong>شرح:</strong> ${transaction.description}
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>واریزکننده:</strong> ${transaction.payer_name}
                    </div>
                    <div class="form-group">
                        <strong>مبلغ واریز:</strong> ${parseFloat(transaction.deposit_amount).toLocaleString('fa-IR')}
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <strong>حساب بانکی:</strong> ${transaction.bank_name} - ${transaction.account_name}
                    </div>
                    <div class="form-group">
                        <strong>ارز:</strong> ${transaction.currency_code}
                    </div>
                </div>
                
                ${transaction.iban ? `
                <div class="form-group">
                    <strong>شماره شبا:</strong> ${transaction.iban}
                </div>
                ` : ''}
                
                ${transaction.card_numbers ? `
                <div class="form-group">
                    <strong>شماره کارت:</strong> ${transaction.card_numbers}
                </div>
                ` : ''}
                
                ${transaction.tracking_code ? `
                <div class="form-group">
                    <strong>کد پیگیری:</strong> ${transaction.tracking_code}
                </div>
                ` : ''}
                
                ${transaction.reference_number ? `
                <div class="form-group">
                    <strong>شماره مرجع:</strong> ${transaction.reference_number}
                </div>
                ` : ''}
                
                ${transaction.additional_info ? `
                <div class="form-group">
                    <strong>اطلاعات اضافی:</strong> ${transaction.additional_info}
                </div>
                ` : ''}
                
                <div class="form-group" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <strong>وضعیت تطبیق:</strong> 
                    <span class="badge badge-${transaction.status === 'unallocated' ? 'danger' : transaction.status === 'partially_allocated' ? 'warning' : 'success'}">
                        ${transaction.status === 'unallocated' ? 'تطبیق نیافته' : transaction.status === 'partially_allocated' ? 'تطبیق جزئی' : 'تطبیق کامل'}
                    </span>
                    <br>
                    <strong>مقدار تطبیق یافته:</strong> ${parseFloat(transaction.allocated_amount).toLocaleString('fa-IR')}
                </div>
            `;
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>