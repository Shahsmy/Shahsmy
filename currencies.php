<?php
$pageTitle = 'مدیریت ارزها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('currencies');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $currency_code = strtoupper(trim($_POST['currency_code']));
            $currency_name = trim($_POST['currency_name']);
            $symbol = trim($_POST['symbol']);
            $decimal_places = (int)$_POST['decimal_places'];
            $exchange_rate = (float)$_POST['exchange_rate'];
            $is_base = isset($_POST['is_base']) ? 1 : 0;
            
            // بررسی وجود کد ارز
            $existingCurrency = $db->fetchOne("SELECT id FROM currencies WHERE currency_code = ?", [$currency_code]);
            if ($existingCurrency) {
                throw new Exception('کد ارز قبلاً وجود دارد');
            }
            
            // اگر این ارز به عنوان ارز پایه انتخاب شده، سایر ارزها را غیرپایه کن
            if ($is_base) {
                $db->query("UPDATE currencies SET is_base = 0");
            }
            
            $currencyData = [
                'currency_code' => $currency_code,
                'currency_name' => $currency_name,
                'symbol' => $symbol,
                'decimal_places' => $decimal_places,
                'exchange_rate' => $exchange_rate,
                'is_base' => $is_base,
                'status' => 'active'
            ];
            
            $db->insert('currencies', $currencyData);
            header('Location: currencies.php?success=ارز با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $currency_code = strtoupper(trim($_POST['currency_code']));
            $currency_name = trim($_POST['currency_name']);
            $symbol = trim($_POST['symbol']);
            $decimal_places = (int)$_POST['decimal_places'];
            $exchange_rate = (float)$_POST['exchange_rate'];
            $is_base = isset($_POST['is_base']) ? 1 : 0;
            
            // بررسی وجود کد ارز برای ارزهای دیگر
            $existingCurrency = $db->fetchOne("SELECT id FROM currencies WHERE currency_code = ? AND id != ?", [$currency_code, $id]);
            if ($existingCurrency) {
                throw new Exception('کد ارز قبلاً وجود دارد');
            }
            
            // اگر این ارز به عنوان ارز پایه انتخاب شده، سایر ارزها را غیرپایه کن
            if ($is_base) {
                $db->query("UPDATE currencies SET is_base = 0");
            }
            
            $currencyData = [
                'currency_code' => $currency_code,
                'currency_name' => $currency_name,
                'symbol' => $symbol,
                'decimal_places' => $decimal_places,
                'exchange_rate' => $exchange_rate,
                'is_base' => $is_base
            ];
            
            $db->update('currencies', $currencyData, 'id = ?', ['id' => $id]);
            header('Location: currencies.php?success=ارز با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // بررسی استفاده در حساب‌ها یا تراکنش‌ها
            $usageCount = $db->fetchOne("
                SELECT (
                    SELECT COUNT(*) FROM bank_accounts WHERE currency_id = ?
                ) + (
                    SELECT COUNT(*) FROM cash_boxes WHERE currency_id = ?
                ) + (
                    SELECT COUNT(*) FROM currency_sales WHERE currency_from_id = ? OR currency_to_id = ?
                ) as total
            ", [$id, $id, $id, $id])['total'];
            
            if ($usageCount > 0) {
                throw new Exception('نمی‌توان ارزی را حذف کرد که در تراکنش‌ها استفاده شده است');
            }
            
            $db->update('currencies', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            header('Location: currencies.php?success=ارز با موفقیت حذف شد');
            exit;
            
        } elseif ($action === 'update_rates') {
            $rates = $_POST['rates'] ?? [];
            
            foreach ($rates as $id => $rate) {
                $rate = (float)$rate;
                if ($rate > 0) {
                    $db->update('currencies', ['exchange_rate' => $rate], 'id = ?', ['id' => (int)$id]);
                }
            }
            
            header('Location: currencies.php?success=نرخ‌های ارز با موفقیت بروزرسانی شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: currencies.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// دریافت لیست ارزها
$currencies = $db->fetchAll("
    SELECT *, 
           (SELECT COUNT(*) FROM bank_accounts WHERE currency_id = currencies.id AND status = 'active') as bank_accounts_count,
           (SELECT COUNT(*) FROM cash_boxes WHERE currency_id = currencies.id AND status = 'active') as cash_boxes_count
    FROM currencies 
    WHERE status = 'active' 
    ORDER BY is_base DESC, currency_name
");
?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-coins"></i>
        مدیریت ارزها
        <div style="float: left;">
            <button class="btn btn-warning btn-small" data-modal="updateRatesModal">
                <i class="fas fa-sync-alt"></i>
                بروزرسانی نرخ‌ها
            </button>
            <button class="btn btn-success btn-small" data-modal="addCurrencyModal">
                <i class="fas fa-plus"></i>
                افزودن ارز جدید
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>کد ارز</th>
                        <th>نام ارز</th>
                        <th>نماد</th>
                        <th>اعشار</th>
                        <th>نرخ تبدیل</th>
                        <th>نوع</th>
                        <th>حساب‌ها</th>
                        <th>صندوق‌ها</th>
                        <th>آخرین بروزرسانی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currencies as $currency): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($currency['currency_code']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($currency['currency_name']); ?></td>
                        <td><?php echo htmlspecialchars($currency['symbol']); ?></td>
                        <td><?php echo $currency['decimal_places']; ?></td>
                        <td class="format-number">
                            <?php if ($currency['is_base']): ?>
                                <span class="badge badge-success">پایه</span>
                            <?php else: ?>
                                <?php echo formatNumber($currency['exchange_rate']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($currency['is_base']): ?>
                                <span class="badge badge-success">ارز پایه</span>
                            <?php else: ?>
                                <span class="badge badge-info">ارز تبدیل</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo $currency['bank_accounts_count']; ?></span>
                        </td>
                        <td>
                            <span class="badge badge-warning"><?php echo $currency['cash_boxes_count']; ?></span>
                        </td>
                        <td><?php echo jalaliDate($currency['updated_at']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-small edit-currency-btn" 
                                    data-currency='<?php echo json_encode($currency); ?>'>
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </button>
                            
                            <?php if (!$currency['is_base'] && $currency['bank_accounts_count'] == 0 && $currency['cash_boxes_count'] == 0): ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این ارز اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $currency['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    <i class="fas fa-trash"></i>
                                    حذف
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

<!-- نمایش آمار کلی -->
<div class="stats-grid">
    <?php foreach ($currencies as $currency): ?>
    <?php
    $totalBalance = $db->fetchOne("
        SELECT COALESCE(
            (SELECT SUM(balance) FROM bank_accounts WHERE currency_id = ? AND status = 'active'), 0
        ) + COALESCE(
            (SELECT SUM(balance) FROM cash_boxes WHERE currency_id = ? AND status = 'active'), 0
        ) as total
    ", [$currency['id'], $currency['id']])['total'];
    ?>
    
    <div class="stat-card">
        <h3 class="format-number"><?php echo formatNumber($totalBalance, $currency['decimal_places']); ?></h3>
        <p>موجودی کل <?php echo htmlspecialchars($currency['currency_name']); ?></p>
        <i class="fas fa-coins icon"></i>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal افزودن ارز -->
<div id="addCurrencyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>افزودن ارز جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="currency_code">کد ارز (3 حرف)</label>
                        <input type="text" name="currency_code" id="currency_code" class="form-control" 
                               required maxlength="3" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label for="currency_name">نام ارز</label>
                        <input type="text" name="currency_name" id="currency_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="symbol">نماد ارز</label>
                        <input type="text" name="symbol" id="symbol" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="decimal_places">تعداد اعشار</label>
                        <select name="decimal_places" id="decimal_places" class="form-control" required>
                            <option value="0">0</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="8">8</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="exchange_rate">نرخ تبدیل (به ریال)</label>
                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" 
                               step="0.0001" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="is_base">نوع ارز</label>
                        <div style="margin-top: 12px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="is_base" id="is_base" style="margin-left: 8px;">
                                ارز پایه سیستم
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره ارز
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addCurrencyModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش ارز -->
<div id="editCurrencyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>ویرایش ارز</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_currency_code">کد ارز (3 حرف)</label>
                        <input type="text" name="currency_code" id="edit_currency_code" class="form-control" 
                               required maxlength="3" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label for="edit_currency_name">نام ارز</label>
                        <input type="text" name="currency_name" id="edit_currency_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_symbol">نماد ارز</label>
                        <input type="text" name="symbol" id="edit_symbol" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_decimal_places">تعداد اعشار</label>
                        <select name="decimal_places" id="edit_decimal_places" class="form-control" required>
                            <option value="0">0</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="8">8</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_exchange_rate">نرخ تبدیل (به ریال)</label>
                        <input type="number" name="exchange_rate" id="edit_exchange_rate" class="form-control" 
                               step="0.0001" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_is_base">نوع ارز</label>
                        <div style="margin-top: 12px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="is_base" id="edit_is_base" style="margin-left: 8px;">
                                ارز پایه سیستم
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی ارز
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editCurrencyModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal بروزرسانی نرخ‌ها -->
<div id="updateRatesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>بروزرسانی نرخ ارزها</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_rates">
                
                <p class="text-center" style="margin-bottom: 20px; color: #7f8c8d;">
                    نرخ‌های جدید ارزها را وارد کنید (بر اساس ریال)
                </p>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($currencies as $currency): ?>
                    <?php if (!$currency['is_base']): ?>
                    <div class="form-group">
                        <label for="rate_<?php echo $currency['id']; ?>">
                            <?php echo htmlspecialchars($currency['currency_name']); ?> 
                            (<?php echo htmlspecialchars($currency['currency_code']); ?>)
                        </label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" 
                                   name="rates[<?php echo $currency['id']; ?>]" 
                                   id="rate_<?php echo $currency['id']; ?>" 
                                   class="form-control" 
                                   step="0.0001" 
                                   value="<?php echo $currency['exchange_rate']; ?>"
                                   style="flex: 1;">
                            <span style="color: #7f8c8d; font-size: 0.9rem;">
                                فعلی: <?php echo formatNumber($currency['exchange_rate']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt"></i>
                        بروزرسانی نرخ‌ها
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('updateRatesModal'))">
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
    document.querySelectorAll('.edit-currency-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const currency = JSON.parse(this.dataset.currency);
            const modal = document.getElementById('editCurrencyModal');
            
            document.getElementById('edit_id').value = currency.id;
            document.getElementById('edit_currency_code').value = currency.currency_code;
            document.getElementById('edit_currency_name').value = currency.currency_name;
            document.getElementById('edit_symbol').value = currency.symbol || '';
            document.getElementById('edit_decimal_places').value = currency.decimal_places;
            document.getElementById('edit_exchange_rate').value = currency.exchange_rate;
            document.getElementById('edit_is_base').checked = currency.is_base == 1;
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت چک‌باکس ارز پایه
    function handleBaseCheckbox(checkboxId, rateInputId) {
        const checkbox = document.getElementById(checkboxId);
        const rateInput = document.getElementById(rateInputId);
        
        if (checkbox && rateInput) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    rateInput.value = 1;
                    rateInput.disabled = true;
                } else {
                    rateInput.disabled = false;
                }
            });
        }
    }
    
    handleBaseCheckbox('is_base', 'exchange_rate');
    handleBaseCheckbox('edit_is_base', 'edit_exchange_rate');
    
    // تبدیل خودکار کد ارز به حروف بزرگ
    document.getElementById('currency_code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    document.getElementById('edit_currency_code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>