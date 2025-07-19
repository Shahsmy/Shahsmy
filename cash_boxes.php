<?php
$pageTitle = 'مدیریت صندوق‌ها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('cash_boxes');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $name = trim($_POST['name']);
            $currency_id = (int)$_POST['currency_id'];
            $balance = (float)$_POST['balance'];
            $location = trim($_POST['location']);
            $responsible_user_id = !empty($_POST['responsible_user_id']) ? (int)$_POST['responsible_user_id'] : null;
            
            $cashBoxData = [
                'name' => $name,
                'currency_id' => $currency_id,
                'balance' => $balance,
                'location' => $location,
                'responsible_user_id' => $responsible_user_id,
                'status' => 'active'
            ];
            
            $db->insert('cash_boxes', $cashBoxData);
            header('Location: cash_boxes.php?success=صندوق با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $currency_id = (int)$_POST['currency_id'];
            $balance = (float)$_POST['balance'];
            $location = trim($_POST['location']);
            $responsible_user_id = !empty($_POST['responsible_user_id']) ? (int)$_POST['responsible_user_id'] : null;
            
            $cashBoxData = [
                'name' => $name,
                'currency_id' => $currency_id,
                'balance' => $balance,
                'location' => $location,
                'responsible_user_id' => $responsible_user_id
            ];
            
            $db->update('cash_boxes', $cashBoxData, 'id = ?', ['id' => $id]);
            header('Location: cash_boxes.php?success=صندوق با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            $db->update('cash_boxes', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            header('Location: cash_boxes.php?success=صندوق با موفقیت حذف شد');
            exit;
            
        } elseif ($action === 'adjust_balance') {
            $id = (int)$_POST['id'];
            $adjustment_amount = (float)$_POST['adjustment_amount'];
            $adjustment_type = $_POST['adjustment_type']; // add or subtract
            $notes = trim($_POST['notes']);
            
            // دریافت اطلاعات صندوق
            $cashBox = $db->fetchOne("SELECT * FROM cash_boxes WHERE id = ?", [$id]);
            if (!$cashBox) {
                throw new Exception('صندوق یافت نشد');
            }
            
            $oldBalance = $cashBox['balance'];
            $newBalance = $adjustment_type === 'add' ? $oldBalance + $adjustment_amount : $oldBalance - $adjustment_amount;
            
            if ($newBalance < 0) {
                throw new Exception('موجودی صندوق نمی‌تواند منفی باشد');
            }
            
            // بروزرسانی موجودی
            $db->update('cash_boxes', ['balance' => $newBalance], 'id = ?', ['id' => $id]);
            
            // ثبت لاگ تغییرات
            $logData = [
                'account_type' => 'cash_box',
                'account_id' => $id,
                'currency_id' => $cashBox['currency_id'],
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'change_amount' => $adjustment_amount,
                'change_type' => 'adjustment',
                'notes' => $notes,
                'created_by' => $currentUser['id']
            ];
            
            $db->insert('balance_logs', $logData);
            
            header('Location: cash_boxes.php?success=موجودی صندوق با موفقیت تعدیل شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: cash_boxes.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// دریافت لیست صندوق‌ها
$cashBoxes = $db->fetchAll("
    SELECT cb.*, c.currency_code, c.symbol, u.full_name as responsible_name
    FROM cash_boxes cb
    JOIN currencies c ON cb.currency_id = c.id
    LEFT JOIN users u ON cb.responsible_user_id = u.id
    WHERE cb.status = 'active'
    ORDER BY cb.name
");

// دریافت لیست ارزها
$currencies = $db->fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY currency_name");

// دریافت لیست کاربران
$users = $db->fetchAll("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name");

// محاسبه کل موجودی هر ارز
$currencyTotals = [];
foreach ($cashBoxes as $box) {
    $currencyCode = $box['currency_code'];
    if (!isset($currencyTotals[$currencyCode])) {
        $currencyTotals[$currencyCode] = 0;
    }
    $currencyTotals[$currencyCode] += $box['balance'];
}
?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-cash-register"></i>
        مدیریت صندوق‌ها
        <button class="btn btn-success btn-small" style="float: left;" data-modal="addCashBoxModal">
            <i class="fas fa-plus"></i>
            افزودن صندوق جدید
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>نام صندوق</th>
                        <th>موجودی</th>
                        <th>ارز</th>
                        <th>مکان</th>
                        <th>مسئول</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cashBoxes as $box): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($box['name']); ?></td>
                        <td class="format-number"><?php echo formatNumber($box['balance']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($box['currency_code']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($box['location']); ?></td>
                        <td><?php echo htmlspecialchars($box['responsible_name'] ?? 'نامشخص'); ?></td>
                        <td><?php echo jalaliDate($box['created_at']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-small edit-cash-box-btn" 
                                    data-box='<?php echo json_encode($box); ?>'>
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </button>
                            
                            <button class="btn btn-info btn-small adjust-balance-btn" 
                                    data-box='<?php echo json_encode($box); ?>'>
                                <i class="fas fa-calculator"></i>
                                تعدیل
                            </button>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این صندوق اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $box['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    <i class="fas fa-trash"></i>
                                    حذف
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

<!-- خلاصه موجودی صندوق‌ها -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-bar"></i>
        خلاصه موجودی صندوق‌ها
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <?php foreach ($currencyTotals as $currencyCode => $total): ?>
            <div class="stat-card success">
                <h3 class="format-number"><?php echo formatNumber($total); ?></h3>
                <p>کل موجودی <?php echo htmlspecialchars($currencyCode); ?></p>
                <i class="fas fa-coins icon"></i>
            </div>
            <?php endforeach; ?>
            
            <div class="stat-card">
                <h3><?php echo count($cashBoxes); ?></h3>
                <p>تعداد صندوق‌های فعال</p>
                <i class="fas fa-cash-register icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Modal افزودن صندوق -->
<div id="addCashBoxModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>افزودن صندوق جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">نام صندوق</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="currency_id">ارز</label>
                        <select name="currency_id" id="currency_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="balance">موجودی اولیه</label>
                        <input type="number" name="balance" id="balance" class="form-control" 
                               step="0.01" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="location">مکان</label>
                        <input type="text" name="location" id="location" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="responsible_user_id">کاربر مسئول</label>
                    <select name="responsible_user_id" id="responsible_user_id" class="form-control">
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره صندوق
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addCashBoxModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش صندوق -->
<div id="editCashBoxModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>ویرایش صندوق</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_name">نام صندوق</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_currency_id">ارز</label>
                        <select name="currency_id" id="edit_currency_id" class="form-control" required>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_balance">موجودی</label>
                        <input type="number" name="balance" id="edit_balance" class="form-control" 
                               step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_location">مکان</label>
                        <input type="text" name="location" id="edit_location" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_responsible_user_id">کاربر مسئول</label>
                    <select name="responsible_user_id" id="edit_responsible_user_id" class="form-control">
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی صندوق
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editCashBoxModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعدیل موجودی -->
<div id="adjustBalanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>تعدیل موجودی صندوق</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="adjust_balance">
                <input type="hidden" name="id" id="adjust_id">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    موجودی فعلی: <span id="current_balance" class="format-number"></span> 
                    <span id="current_currency"></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="adjustment_type">نوع تعدیل</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-control" required>
                            <option value="add">اضافه کردن</option>
                            <option value="subtract">کم کردن</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adjustment_amount">مقدار تعدیل</label>
                        <input type="number" name="adjustment_amount" id="adjustment_amount" 
                               class="form-control" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">توضیحات</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                              placeholder="دلیل تعدیل موجودی را وارد کنید..."></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-info">
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
    // مدیریت دکمه‌های ویرایش
    document.querySelectorAll('.edit-cash-box-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const box = JSON.parse(this.dataset.box);
            const modal = document.getElementById('editCashBoxModal');
            
            document.getElementById('edit_id').value = box.id;
            document.getElementById('edit_name').value = box.name;
            document.getElementById('edit_currency_id').value = box.currency_id;
            document.getElementById('edit_balance').value = box.balance;
            document.getElementById('edit_location').value = box.location || '';
            document.getElementById('edit_responsible_user_id').value = box.responsible_user_id || '';
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت دکمه‌های تعدیل موجودی
    document.querySelectorAll('.adjust-balance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const box = JSON.parse(this.dataset.box);
            const modal = document.getElementById('adjustBalanceModal');
            
            document.getElementById('adjust_id').value = box.id;
            document.getElementById('current_balance').textContent = parseFloat(box.balance).toLocaleString('fa-IR');
            document.getElementById('current_currency').textContent = box.currency_code;
            
            // پاک کردن فرم
            document.getElementById('adjustment_amount').value = '';
            document.getElementById('notes').value = '';
            document.getElementById('adjustment_type').value = 'add';
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>