<?php
$pageTitle = 'مدیریت بانک‌ها و حساب‌ها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('banks');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_bank') {
            $bank_name = trim($_POST['bank_name']);
            $bank_code = trim($_POST['bank_code']);
            $swift_code = trim($_POST['swift_code']);
            
            $bankData = [
                'bank_name' => $bank_name,
                'bank_code' => $bank_code,
                'swift_code' => $swift_code,
                'status' => 'active'
            ];
            
            $db->insert('banks', $bankData);
            header('Location: banks.php?success=بانک با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit_bank') {
            $id = (int)$_POST['id'];
            $bank_name = trim($_POST['bank_name']);
            $bank_code = trim($_POST['bank_code']);
            $swift_code = trim($_POST['swift_code']);
            
            $bankData = [
                'bank_name' => $bank_name,
                'bank_code' => $bank_code,
                'swift_code' => $swift_code
            ];
            
            $db->update('banks', $bankData, 'id = ?', ['id' => $id]);
            header('Location: banks.php?success=بانک با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete_bank') {
            $id = (int)$_POST['id'];
            
            // بررسی وجود حساب برای این بانک
            $accountsCount = $db->fetchOne("SELECT COUNT(*) as count FROM bank_accounts WHERE bank_id = ?", [$id])['count'];
            if ($accountsCount > 0) {
                throw new Exception('نمی‌توان بانکی را حذف کرد که دارای حساب است');
            }
            
            $db->update('banks', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            header('Location: banks.php?success=بانک با موفقیت حذف شد');
            exit;
            
        } elseif ($action === 'add_account') {
            $bank_id = (int)$_POST['bank_id'];
            $account_number = trim($_POST['account_number']);
            $iban = trim($_POST['iban']);
            $account_name = trim($_POST['account_name']);
            $account_type = $_POST['account_type'];
            $currency_id = (int)$_POST['currency_id'];
            $balance = (float)$_POST['balance'];
            
            $accountData = [
                'bank_id' => $bank_id,
                'account_number' => $account_number,
                'iban' => $iban,
                'account_name' => $account_name,
                'account_type' => $account_type,
                'currency_id' => $currency_id,
                'balance' => $balance,
                'status' => 'active'
            ];
            
            $db->insert('bank_accounts', $accountData);
            header('Location: banks.php?success=حساب بانکی با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit_account') {
            $id = (int)$_POST['id'];
            $bank_id = (int)$_POST['bank_id'];
            $account_number = trim($_POST['account_number']);
            $iban = trim($_POST['iban']);
            $account_name = trim($_POST['account_name']);
            $account_type = $_POST['account_type'];
            $currency_id = (int)$_POST['currency_id'];
            $balance = (float)$_POST['balance'];
            
            $accountData = [
                'bank_id' => $bank_id,
                'account_number' => $account_number,
                'iban' => $iban,
                'account_name' => $account_name,
                'account_type' => $account_type,
                'currency_id' => $currency_id,
                'balance' => $balance
            ];
            
            $db->update('bank_accounts', $accountData, 'id = ?', ['id' => $id]);
            header('Location: banks.php?success=حساب بانکی با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete_account') {
            $id = (int)$_POST['id'];
            
            $db->update('bank_accounts', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            header('Location: banks.php?success=حساب بانکی با موفقیت حذف شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: banks.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// دریافت لیست بانک‌ها
$banks = $db->fetchAll("SELECT * FROM banks WHERE status = 'active' ORDER BY bank_name");

// دریافت لیست حساب‌های بانکی
$accounts = $db->fetchAll("
    SELECT ba.*, b.bank_name, c.currency_code, c.symbol 
    FROM bank_accounts ba
    JOIN banks b ON ba.bank_id = b.id
    JOIN currencies c ON ba.currency_id = c.id
    WHERE ba.status = 'active'
    ORDER BY b.bank_name, ba.account_name
");

// دریافت لیست ارزها
$currencies = $db->fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY currency_name");
?>

<div class="form-row">
    <!-- بخش بانک‌ها -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-university"></i>
                مدیریت بانک‌ها
                <button class="btn btn-success btn-small" style="float: left;" data-modal="addBankModal">
                    <i class="fas fa-plus"></i>
                    افزودن بانک
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>نام بانک</th>
                                <th>کد بانک</th>
                                <th>کد SWIFT</th>
                                <th>تعداد حساب‌ها</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banks as $bank): ?>
                            <?php
                            $accountsCount = $db->fetchOne("SELECT COUNT(*) as count FROM bank_accounts WHERE bank_id = ? AND status = 'active'", [$bank['id']])['count'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                                <td><?php echo htmlspecialchars($bank['bank_code']); ?></td>
                                <td><?php echo htmlspecialchars($bank['swift_code']); ?></td>
                                <td><span class="badge badge-info"><?php echo $accountsCount; ?></span></td>
                                <td>
                                    <button class="btn btn-warning btn-small edit-bank-btn" 
                                            data-bank='<?php echo json_encode($bank); ?>'>
                                        <i class="fas fa-edit"></i>
                                        ویرایش
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirmDelete('آیا از حذف این بانک اطمینان دارید؟')">
                                        <input type="hidden" name="action" value="delete_bank">
                                        <input type="hidden" name="id" value="<?php echo $bank['id']; ?>">
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
    </div>

    <!-- بخش حساب‌های بانکی -->
    <div class="form-group">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-credit-card"></i>
                حساب‌های بانکی
                <button class="btn btn-success btn-small" style="float: left;" data-modal="addAccountModal">
                    <i class="fas fa-plus"></i>
                    افزودن حساب
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>بانک</th>
                                <th>نام حساب</th>
                                <th>شماره حساب</th>
                                <th>شبا</th>
                                <th>نوع</th>
                                <th>موجودی</th>
                                <th>ارز</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                                <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                <td><?php echo htmlspecialchars($account['iban']); ?></td>
                                <td>
                                    <?php if ($account['account_type'] === 'current'): ?>
                                        <span class="badge badge-info">جاری</span>
                                    <?php elseif ($account['account_type'] === 'savings'): ?>
                                        <span class="badge badge-success">پس‌انداز</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">تجاری</span>
                                    <?php endif; ?>
                                </td>
                                <td class="format-number"><?php echo formatNumber($account['balance']); ?></td>
                                <td><?php echo htmlspecialchars($account['currency_code']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-small edit-account-btn" 
                                            data-account='<?php echo json_encode($account); ?>'>
                                        <i class="fas fa-edit"></i>
                                        ویرایش
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirmDelete('آیا از حذف این حساب اطمینان دارید؟')">
                                        <input type="hidden" name="action" value="delete_account">
                                        <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
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
    </div>
</div>

<!-- Modal افزودن بانک -->
<div id="addBankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>افزودن بانک جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_bank">
                
                <div class="form-group">
                    <label for="bank_name">نام بانک</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bank_code">کد بانک</label>
                        <input type="text" name="bank_code" id="bank_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="swift_code">کد SWIFT</label>
                        <input type="text" name="swift_code" id="swift_code" class="form-control">
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره بانک
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addBankModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش بانک -->
<div id="editBankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>ویرایش بانک</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit_bank">
                <input type="hidden" name="id" id="edit_bank_id">
                
                <div class="form-group">
                    <label for="edit_bank_name">نام بانک</label>
                    <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_bank_code">کد بانک</label>
                        <input type="text" name="bank_code" id="edit_bank_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_swift_code">کد SWIFT</label>
                        <input type="text" name="swift_code" id="edit_swift_code" class="form-control">
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی بانک
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editBankModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal افزودن حساب بانکی -->
<div id="addAccountModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>افزودن حساب بانکی جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_account">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bank_id">بانک</label>
                        <select name="bank_id" id="bank_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo $bank['id']; ?>"><?php echo htmlspecialchars($bank['bank_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="account_name">نام حساب</label>
                        <input type="text" name="account_name" id="account_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_number">شماره حساب</label>
                        <input type="text" name="account_number" id="account_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="iban">شماره شبا</label>
                        <input type="text" name="iban" id="iban" class="form-control" placeholder="IR...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_type">نوع حساب</label>
                        <select name="account_type" id="account_type" class="form-control" required>
                            <option value="current">جاری</option>
                            <option value="savings">پس‌انداز</option>
                            <option value="business">تجاری</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="currency_id">ارز</label>
                        <select name="currency_id" id="currency_id" class="form-control" required>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>" <?php echo $currency['is_base'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="balance">موجودی اولیه</label>
                    <input type="number" name="balance" id="balance" class="form-control" step="0.01" value="0">
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره حساب
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addAccountModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش حساب بانکی -->
<div id="editAccountModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>ویرایش حساب بانکی</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit_account">
                <input type="hidden" name="id" id="edit_account_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_bank_id">بانک</label>
                        <select name="bank_id" id="edit_bank_id" class="form-control" required>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo $bank['id']; ?>"><?php echo htmlspecialchars($bank['bank_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_account_name">نام حساب</label>
                        <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_account_number">شماره حساب</label>
                        <input type="text" name="account_number" id="edit_account_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_iban">شماره شبا</label>
                        <input type="text" name="iban" id="edit_iban" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_account_type">نوع حساب</label>
                        <select name="account_type" id="edit_account_type" class="form-control" required>
                            <option value="current">جاری</option>
                            <option value="savings">پس‌انداز</option>
                            <option value="business">تجاری</option>
                        </select>
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
                
                <div class="form-group">
                    <label for="edit_balance">موجودی</label>
                    <input type="number" name="balance" id="edit_balance" class="form-control" step="0.01">
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی حساب
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editAccountModal'))">
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
    // مدیریت دکمه‌های ویرایش بانک
    document.querySelectorAll('.edit-bank-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bank = JSON.parse(this.dataset.bank);
            const modal = document.getElementById('editBankModal');
            
            document.getElementById('edit_bank_id').value = bank.id;
            document.getElementById('edit_bank_name').value = bank.bank_name;
            document.getElementById('edit_bank_code').value = bank.bank_code || '';
            document.getElementById('edit_swift_code').value = bank.swift_code || '';
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت دکمه‌های ویرایش حساب
    document.querySelectorAll('.edit-account-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const account = JSON.parse(this.dataset.account);
            const modal = document.getElementById('editAccountModal');
            
            document.getElementById('edit_account_id').value = account.id;
            document.getElementById('edit_bank_id').value = account.bank_id;
            document.getElementById('edit_account_name').value = account.account_name;
            document.getElementById('edit_account_number').value = account.account_number;
            document.getElementById('edit_iban').value = account.iban || '';
            document.getElementById('edit_account_type').value = account.account_type;
            document.getElementById('edit_currency_id').value = account.currency_id;
            document.getElementById('edit_balance').value = account.balance;
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>