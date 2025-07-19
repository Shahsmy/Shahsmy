<?php
require_once 'includes/auth.php';
checkPermission('manage_transactions');

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_allocation':
                $contact_id = (int)$_POST['contact_id'];
                $withdrawal_id = (int)$_POST['withdrawal_id'];
                $amount = floatval($_POST['amount']);
                $description = trim($_POST['description']);
                
                // Validate withdrawal exists and has available amount
                $stmt = $db->prepare("SELECT * FROM withdrawals WHERE id = ?");
                $stmt->execute([$withdrawal_id]);
                $withdrawal = $stmt->fetch();
                
                if (!$withdrawal) {
                    $error = "برداشت مورد نظر یافت نشد";
                    break;
                }
                
                // Calculate allocated amount for this withdrawal
                $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as allocated FROM withdrawal_allocations WHERE withdrawal_id = ?");
                $stmt->execute([$withdrawal_id]);
                $allocated = $stmt->fetchColumn();
                
                $available = $withdrawal['amount'] - $allocated;
                
                if ($amount > $available) {
                    $error = "مبلغ تخصیص یافته بیشتر از مبلغ قابل تخصیص است";
                    break;
                }
                
                // Insert allocation
                $stmt = $db->prepare("INSERT INTO withdrawal_allocations (contact_id, withdrawal_id, amount, description, allocated_by, allocated_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$contact_id, $withdrawal_id, $amount, $description, $_SESSION['user_id']]);
                
                // Update contact account balance
                $stmt = $db->prepare("INSERT INTO contact_accounts (contact_id, currency_id, amount, transaction_type, reference_type, reference_id, description, created_by) VALUES (?, ?, ?, 'withdrawal', 'withdrawal_allocation', ?, ?, ?)");
                $stmt->execute([$contact_id, $withdrawal['currency_id'], -$amount, $db->lastInsertId(), $description, $_SESSION['user_id']]);
                
                $success = "تخصیص با موفقیت ثبت شد";
                break;
                
            case 'delete_allocation':
                $allocation_id = (int)$_POST['allocation_id'];
                
                // Get allocation details
                $stmt = $db->prepare("SELECT wa.*, w.currency_id FROM withdrawal_allocations wa JOIN withdrawals w ON wa.withdrawal_id = w.id WHERE wa.id = ?");
                $stmt->execute([$allocation_id]);
                $allocation = $stmt->fetch();
                
                if ($allocation) {
                    // Delete from contact accounts
                    $stmt = $db->prepare("DELETE FROM contact_accounts WHERE reference_type = 'withdrawal_allocation' AND reference_id = ?");
                    $stmt->execute([$allocation_id]);
                    
                    // Delete allocation
                    $stmt = $db->prepare("DELETE FROM withdrawal_allocations WHERE id = ?");
                    $stmt->execute([$allocation_id]);
                    
                    $success = "تخصیص با موفقیت حذف شد";
                }
                break;
        }
    }
}

// Get unallocated or partially allocated withdrawals
$stmt = $db->prepare("
    SELECT w.*, b.name as bank_name, ba.account_number, c.name as currency_name,
           COALESCE(SUM(wa.amount), 0) as allocated_amount,
           (w.amount - COALESCE(SUM(wa.amount), 0)) as available_amount
    FROM withdrawals w
    LEFT JOIN bank_accounts ba ON w.bank_account_id = ba.id
    LEFT JOIN banks b ON ba.bank_id = b.id
    LEFT JOIN currencies c ON w.currency_id = c.id
    LEFT JOIN withdrawal_allocations wa ON w.id = wa.withdrawal_id
    GROUP BY w.id
    HAVING available_amount > 0
    ORDER BY w.withdrawal_date DESC
");
$stmt->execute();
$available_withdrawals = $stmt->fetchAll();

// Get contacts
$stmt = $db->prepare("SELECT id, name, phone FROM contacts ORDER BY name");
$stmt->execute();
$contacts = $stmt->fetchAll();

// Get recent allocations
$stmt = $db->prepare("
    SELECT wa.*, c.name as contact_name, w.amount as withdrawal_amount, 
           w.withdrawal_date, curr.name as currency_name, u.username as allocated_by_name
    FROM withdrawal_allocations wa
    JOIN contacts c ON wa.contact_id = c.id
    JOIN withdrawals w ON wa.withdrawal_id = w.id
    JOIN currencies curr ON w.currency_id = curr.id
    JOIN users u ON wa.allocated_by = u.id
    ORDER BY wa.allocated_at DESC
    LIMIT 20
");
$stmt->execute();
$recent_allocations = $stmt->fetchAll();

$page_title = "تخصیص برداشت";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>تخصیص برداشت</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allocationModal">
                    <i class="fas fa-plus"></i> تخصیص جدید
                </button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Available Withdrawals -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">برداشت‌های قابل تخصیص</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>بانک</th>
                                    <th>حساب</th>
                                    <th>ارز</th>
                                    <th>مبلغ کل</th>
                                    <th>تخصیص یافته</th>
                                    <th>قابل تخصیص</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?= htmlspecialchars(formatJalaliDate($withdrawal['withdrawal_date'])) ?></td>
                                    <td><?= htmlspecialchars($withdrawal['bank_name']) ?></td>
                                    <td><?= htmlspecialchars($withdrawal['account_number']) ?></td>
                                    <td><?= htmlspecialchars($withdrawal['currency_name']) ?></td>
                                    <td><?= number_format($withdrawal['amount'], 2) ?></td>
                                    <td><?= number_format($withdrawal['allocated_amount'], 2) ?></td>
                                    <td><?= number_format($withdrawal['available_amount'], 2) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="selectWithdrawal(<?= $withdrawal['id'] ?>, '<?= htmlspecialchars($withdrawal['bank_name'] . ' - ' . $withdrawal['account_number']) ?>', <?= $withdrawal['available_amount'] ?>)">
                                            تخصیص
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
    </div>
    
    <!-- Recent Allocations -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">تخصیص‌های اخیر</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>تاریخ تخصیص</th>
                                    <th>مخاطب</th>
                                    <th>تاریخ برداشت</th>
                                    <th>ارز</th>
                                    <th>مبلغ</th>
                                    <th>توضیحات</th>
                                    <th>توسط</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_allocations as $allocation): ?>
                                <tr>
                                    <td><?= htmlspecialchars(formatJalaliDate($allocation['allocated_at'])) ?></td>
                                    <td><?= htmlspecialchars($allocation['contact_name']) ?></td>
                                    <td><?= htmlspecialchars(formatJalaliDate($allocation['withdrawal_date'])) ?></td>
                                    <td><?= htmlspecialchars($allocation['currency_name']) ?></td>
                                    <td><?= number_format($allocation['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($allocation['description']) ?></td>
                                    <td><?= htmlspecialchars($allocation['allocated_by_name']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('آیا از حذف این تخصیص اطمینان دارید؟')">
                                            <input type="hidden" name="action" value="delete_allocation">
                                            <input type="hidden" name="allocation_id" value="<?= $allocation['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<!-- Allocation Modal -->
<div class="modal fade" id="allocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تخصیص برداشت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_allocation">
                    
                    <div class="mb-3">
                        <label class="form-label">برداشت</label>
                        <select name="withdrawal_id" id="withdrawalSelect" class="form-select" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($available_withdrawals as $withdrawal): ?>
                            <option value="<?= $withdrawal['id'] ?>" 
                                    data-available="<?= $withdrawal['available_amount'] ?>"
                                    data-info="<?= htmlspecialchars($withdrawal['bank_name'] . ' - ' . $withdrawal['account_number']) ?>">
                                <?= htmlspecialchars($withdrawal['bank_name'] . ' - ' . $withdrawal['account_number'] . ' - ' . number_format($withdrawal['available_amount'], 2) . ' ' . $withdrawal['currency_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="withdrawalInfo" class="form-text"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">مخاطب</label>
                        <select name="contact_id" class="form-select" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($contacts as $contact): ?>
                            <option value="<?= $contact['id'] ?>">
                                <?= htmlspecialchars($contact['name'] . ' - ' . $contact['phone']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">مبلغ</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                        <div class="form-text">حداکثر مبلغ قابل تخصیص: <span id="maxAmount">-</span></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">توضیحات</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ثبت تخصیص</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectWithdrawal(withdrawalId, info, availableAmount) {
    document.getElementById('withdrawalSelect').value = withdrawalId;
    updateWithdrawalInfo();
    
    var modal = new bootstrap.Modal(document.getElementById('allocationModal'));
    modal.show();
}

document.getElementById('withdrawalSelect').addEventListener('change', updateWithdrawalInfo);

function updateWithdrawalInfo() {
    const select = document.getElementById('withdrawalSelect');
    const option = select.options[select.selectedIndex];
    const info = document.getElementById('withdrawalInfo');
    const maxAmount = document.getElementById('maxAmount');
    
    if (option.value) {
        const available = option.dataset.available;
        const infoText = option.dataset.info;
        
        info.textContent = `برداشت: ${infoText}`;
        maxAmount.textContent = parseFloat(available).toLocaleString('fa-IR');
        
        // Set max attribute on amount input
        const amountInput = document.querySelector('input[name="amount"]');
        amountInput.max = available;
    } else {
        info.textContent = '';
        maxAmount.textContent = '-';
    }
}
</script>

<?php include 'includes/footer.php'; ?>