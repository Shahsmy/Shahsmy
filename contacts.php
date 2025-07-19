<?php
$pageTitle = 'مدیریت طرف‌حساب‌ها';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('contacts');

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $contact_name = trim($_POST['contact_name']);
            $contact_type = $_POST['contact_type'];
            $company_name = trim($_POST['company_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            $national_id = trim($_POST['national_id']);
            $economic_code = trim($_POST['economic_code']);
            $credit_limit = (float)$_POST['credit_limit'];
            
            // اطلاعات بانکی
            $bank_account_info = [];
            if (!empty($_POST['bank_name'])) {
                $bank_account_info = [
                    'bank_name' => trim($_POST['bank_name']),
                    'account_number' => trim($_POST['bank_account_number']),
                    'iban' => trim($_POST['bank_iban']),
                    'card_number' => trim($_POST['card_number'])
                ];
            }
            
            $contactData = [
                'contact_name' => $contact_name,
                'contact_type' => $contact_type,
                'company_name' => $company_name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'national_id' => $national_id,
                'economic_code' => $economic_code,
                'bank_account_info' => json_encode($bank_account_info),
                'credit_limit' => $credit_limit,
                'status' => 'active'
            ];
            
            $db->insert('contacts', $contactData);
            header('Location: contacts.php?success=طرف‌حساب با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $contact_name = trim($_POST['contact_name']);
            $contact_type = $_POST['contact_type'];
            $company_name = trim($_POST['company_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            $national_id = trim($_POST['national_id']);
            $economic_code = trim($_POST['economic_code']);
            $credit_limit = (float)$_POST['credit_limit'];
            
            // اطلاعات بانکی
            $bank_account_info = [];
            if (!empty($_POST['bank_name'])) {
                $bank_account_info = [
                    'bank_name' => trim($_POST['bank_name']),
                    'account_number' => trim($_POST['bank_account_number']),
                    'iban' => trim($_POST['bank_iban']),
                    'card_number' => trim($_POST['card_number'])
                ];
            }
            
            $contactData = [
                'contact_name' => $contact_name,
                'contact_type' => $contact_type,
                'company_name' => $company_name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'national_id' => $national_id,
                'economic_code' => $economic_code,
                'bank_account_info' => json_encode($bank_account_info),
                'credit_limit' => $credit_limit
            ];
            
            $db->update('contacts', $contactData, 'id = ?', ['id' => $id]);
            header('Location: contacts.php?success=طرف‌حساب با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // بررسی استفاده در تراکنش‌ها
            $usageCount = $db->fetchOne("
                SELECT (
                    SELECT COUNT(*) FROM currency_sales WHERE customer_id = ?
                ) + (
                    SELECT COUNT(*) FROM currency_purchases WHERE supplier_id = ?
                ) + (
                    SELECT COUNT(*) FROM deposit_allocations da 
                    JOIN bank_transactions bt ON da.bank_transaction_id = bt.id 
                    WHERE bt.payer_name LIKE CONCAT('%', (SELECT contact_name FROM contacts WHERE id = ?), '%')
                ) as total
            ", [$id, $id, $id])['total'];
            
            if ($usageCount > 0) {
                throw new Exception('نمی‌توان طرف‌حسابی را حذف کرد که در تراکنش‌ها استفاده شده است');
            }
            
            $db->update('contacts', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            header('Location: contacts.php?success=طرف‌حساب با موفقیت حذف شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: contacts.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// فیلتر بر اساس نوع
$filter_type = $_GET['type'] ?? 'all';
$whereClause = "c.status = 'active'";
$params = [];

if ($filter_type !== 'all') {
    $whereClause .= " AND c.contact_type = ?";
    $params[] = $filter_type;
}

// دریافت لیست طرف‌حساب‌ها
$contacts = $db->fetchAll("
    SELECT c.*,
           (SELECT COUNT(*) FROM currency_sales WHERE customer_id = c.id) as sales_count,
           (SELECT COUNT(*) FROM currency_purchases WHERE supplier_id = c.id) as purchases_count
    FROM contacts c
    WHERE {$whereClause}
    ORDER BY c.contact_name
", $params);

// آمار کلی
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_contacts,
        SUM(CASE WHEN contact_type = 'customer' THEN 1 ELSE 0 END) as customers,
        SUM(CASE WHEN contact_type = 'supplier' THEN 1 ELSE 0 END) as suppliers,
        SUM(CASE WHEN contact_type = 'exchanger' THEN 1 ELSE 0 END) as exchangers,
        SUM(CASE WHEN contact_type = 'other' THEN 1 ELSE 0 END) as others
    FROM contacts 
    WHERE status = 'active'
");
?>

<!-- فیلترها -->
<div class="card">
    <div class="card-body p-20">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="contacts.php?type=all" class="btn <?php echo $filter_type === 'all' ? 'btn-primary' : 'btn-secondary'; ?> btn-small">
                    <i class="fas fa-users"></i>
                    همه (<?php echo $stats['total_contacts']; ?>)
                </a>
                <a href="contacts.php?type=customer" class="btn <?php echo $filter_type === 'customer' ? 'btn-primary' : 'btn-secondary'; ?> btn-small">
                    <i class="fas fa-user"></i>
                    مشتریان (<?php echo $stats['customers']; ?>)
                </a>
                <a href="contacts.php?type=supplier" class="btn <?php echo $filter_type === 'supplier' ? 'btn-primary' : 'btn-secondary'; ?> btn-small">
                    <i class="fas fa-truck"></i>
                    تامین‌کنندگان (<?php echo $stats['suppliers']; ?>)
                </a>
                <a href="contacts.php?type=exchanger" class="btn <?php echo $filter_type === 'exchanger' ? 'btn-primary' : 'btn-secondary'; ?> btn-small">
                    <i class="fas fa-exchange-alt"></i>
                    صرافان (<?php echo $stats['exchangers']; ?>)
                </a>
                <a href="contacts.php?type=other" class="btn <?php echo $filter_type === 'other' ? 'btn-primary' : 'btn-secondary'; ?> btn-small">
                    <i class="fas fa-ellipsis-h"></i>
                    سایر (<?php echo $stats['others']; ?>)
                </a>
            </div>
            
            <button class="btn btn-success btn-small" data-modal="addContactModal">
                <i class="fas fa-plus"></i>
                افزودن طرف‌حساب جدید
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-address-book"></i>
        لیست طرف‌حساب‌ها
        <?php if ($filter_type !== 'all'): ?>
        - <?php 
            $types = [
                'customer' => 'مشتریان',
                'supplier' => 'تامین‌کنندگان', 
                'exchanger' => 'صرافان',
                'other' => 'سایر'
            ];
            echo $types[$filter_type] ?? $filter_type;
        ?>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>نام طرف‌حساب</th>
                        <th>نوع</th>
                        <th>شرکت</th>
                        <th>تلفن</th>
                        <th>ایمیل</th>
                        <th>حد اعتبار</th>
                        <th>فروش</th>
                        <th>خرید</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($contact['contact_name']); ?></strong>
                            <?php if (!empty($contact['national_id'])): ?>
                            <br><small class="text-muted">کدملی: <?php echo htmlspecialchars($contact['national_id']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contact['contact_type'] === 'customer'): ?>
                                <span class="badge badge-success">مشتری</span>
                            <?php elseif ($contact['contact_type'] === 'supplier'): ?>
                                <span class="badge badge-warning">تامین‌کننده</span>
                            <?php elseif ($contact['contact_type'] === 'exchanger'): ?>
                                <span class="badge badge-info">صراف</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">سایر</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($contact['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                        <td class="format-number"><?php echo formatNumber($contact['credit_limit']); ?></td>
                        <td>
                            <?php if ($contact['sales_count'] > 0): ?>
                                <span class="badge badge-success"><?php echo $contact['sales_count']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contact['purchases_count'] > 0): ?>
                                <span class="badge badge-warning"><?php echo $contact['purchases_count']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-small view-contact-btn" 
                                    data-contact='<?php echo json_encode($contact); ?>'>
                                <i class="fas fa-eye"></i>
                                مشاهده
                            </button>
                            
                            <button class="btn btn-warning btn-small edit-contact-btn" 
                                    data-contact='<?php echo json_encode($contact); ?>'>
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </button>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این طرف‌حساب اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
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

<!-- Modal افزودن طرف‌حساب -->
<div id="addContactModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h4>افزودن طرف‌حساب جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_name">نام طرف‌حساب *</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_type">نوع طرف‌حساب *</label>
                        <select name="contact_type" id="contact_type" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            <option value="customer">مشتری</option>
                            <option value="supplier">تامین‌کننده</option>
                            <option value="exchanger">صراف</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name">نام شرکت</label>
                        <input type="text" name="company_name" id="company_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="phone">شماره تلفن</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">ایمیل</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="national_id">کد ملی</label>
                        <input type="text" name="national_id" id="national_id" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="economic_code">کد اقتصادی</label>
                        <input type="text" name="economic_code" id="economic_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="credit_limit">حد اعتبار</label>
                        <input type="number" name="credit_limit" id="credit_limit" class="form-control" 
                               step="0.01" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">آدرس</label>
                    <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                </div>
                
                <!-- اطلاعات بانکی -->
                <h5 style="margin: 20px 0 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    <i class="fas fa-university"></i>
                    اطلاعات بانکی (اختیاری)
                </h5>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bank_name">نام بانک</label>
                        <input type="text" name="bank_name" id="bank_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bank_account_number">شماره حساب</label>
                        <input type="text" name="bank_account_number" id="bank_account_number" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bank_iban">شماره شبا</label>
                        <input type="text" name="bank_iban" id="bank_iban" class="form-control" placeholder="IR...">
                    </div>
                    <div class="form-group">
                        <label for="card_number">شماره کارت</label>
                        <input type="text" name="card_number" id="card_number" class="form-control">
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره طرف‌حساب
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addContactModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش طرف‌حساب -->
<div id="editContactModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h4>ویرایش طرف‌حساب</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_contact_name">نام طرف‌حساب *</label>
                        <input type="text" name="contact_name" id="edit_contact_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_contact_type">نوع طرف‌حساب *</label>
                        <select name="contact_type" id="edit_contact_type" class="form-control" required>
                            <option value="customer">مشتری</option>
                            <option value="supplier">تامین‌کننده</option>
                            <option value="exchanger">صراف</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_company_name">نام شرکت</label>
                        <input type="text" name="company_name" id="edit_company_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">شماره تلفن</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">ایمیل</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_national_id">کد ملی</label>
                        <input type="text" name="national_id" id="edit_national_id" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_economic_code">کد اقتصادی</label>
                        <input type="text" name="economic_code" id="edit_economic_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_credit_limit">حد اعتبار</label>
                        <input type="number" name="credit_limit" id="edit_credit_limit" class="form-control" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">آدرس</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                </div>
                
                <!-- اطلاعات بانکی -->
                <h5 style="margin: 20px 0 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    <i class="fas fa-university"></i>
                    اطلاعات بانکی (اختیاری)
                </h5>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_bank_name">نام بانک</label>
                        <input type="text" name="bank_name" id="edit_bank_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_bank_account_number">شماره حساب</label>
                        <input type="text" name="bank_account_number" id="edit_bank_account_number" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_bank_iban">شماره شبا</label>
                        <input type="text" name="bank_iban" id="edit_bank_iban" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_card_number">شماره کارت</label>
                        <input type="text" name="card_number" id="edit_card_number" class="form-control">
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی طرف‌حساب
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editContactModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal مشاهده جزئیات -->
<div id="viewContactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>جزئیات طرف‌حساب</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="contactDetails">
            <!-- محتوا توسط JavaScript پر می‌شود -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // مدیریت دکمه‌های ویرایش
    document.querySelectorAll('.edit-contact-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const contact = JSON.parse(this.dataset.contact);
            const modal = document.getElementById('editContactModal');
            
            document.getElementById('edit_id').value = contact.id;
            document.getElementById('edit_contact_name').value = contact.contact_name;
            document.getElementById('edit_contact_type').value = contact.contact_type;
            document.getElementById('edit_company_name').value = contact.company_name || '';
            document.getElementById('edit_phone').value = contact.phone || '';
            document.getElementById('edit_email').value = contact.email || '';
            document.getElementById('edit_national_id').value = contact.national_id || '';
            document.getElementById('edit_economic_code').value = contact.economic_code || '';
            document.getElementById('edit_credit_limit').value = contact.credit_limit || 0;
            document.getElementById('edit_address').value = contact.address || '';
            
            // اطلاعات بانکی
            let bankInfo = {};
            try {
                bankInfo = contact.bank_account_info ? JSON.parse(contact.bank_account_info) : {};
            } catch (e) {
                bankInfo = {};
            }
            
            document.getElementById('edit_bank_name').value = bankInfo.bank_name || '';
            document.getElementById('edit_bank_account_number').value = bankInfo.account_number || '';
            document.getElementById('edit_bank_iban').value = bankInfo.iban || '';
            document.getElementById('edit_card_number').value = bankInfo.card_number || '';
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت دکمه‌های مشاهده
    document.querySelectorAll('.view-contact-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const contact = JSON.parse(this.dataset.contact);
            const modal = document.getElementById('viewContactModal');
            const detailsDiv = document.getElementById('contactDetails');
            
            let bankInfo = {};
            try {
                bankInfo = contact.bank_account_info ? JSON.parse(contact.bank_account_info) : {};
            } catch (e) {
                bankInfo = {};
            }
            
            const typeLabels = {
                'customer': 'مشتری',
                'supplier': 'تامین‌کننده',
                'exchanger': 'صراف',
                'other': 'سایر'
            };
            
            detailsDiv.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <strong>نام:</strong> ${contact.contact_name}
                    </div>
                    <div class="form-group">
                        <strong>نوع:</strong> ${typeLabels[contact.contact_type] || contact.contact_type}
                    </div>
                </div>
                
                ${contact.company_name ? `
                <div class="form-group">
                    <strong>شرکت:</strong> ${contact.company_name}
                </div>
                ` : ''}
                
                <div class="form-row">
                    ${contact.phone ? `<div class="form-group"><strong>تلفن:</strong> ${contact.phone}</div>` : ''}
                    ${contact.email ? `<div class="form-group"><strong>ایمیل:</strong> ${contact.email}</div>` : ''}
                </div>
                
                <div class="form-row">
                    ${contact.national_id ? `<div class="form-group"><strong>کد ملی:</strong> ${contact.national_id}</div>` : ''}
                    ${contact.economic_code ? `<div class="form-group"><strong>کد اقتصادی:</strong> ${contact.economic_code}</div>` : ''}
                </div>
                
                <div class="form-group">
                    <strong>حد اعتبار:</strong> ${parseFloat(contact.credit_limit || 0).toLocaleString('fa-IR')} ریال
                </div>
                
                ${contact.address ? `
                <div class="form-group">
                    <strong>آدرس:</strong> ${contact.address}
                </div>
                ` : ''}
                
                ${(bankInfo.bank_name || bankInfo.account_number || bankInfo.iban || bankInfo.card_number) ? `
                <h5 style="margin: 20px 0 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    اطلاعات بانکی
                </h5>
                
                ${bankInfo.bank_name ? `<div class="form-group"><strong>بانک:</strong> ${bankInfo.bank_name}</div>` : ''}
                ${bankInfo.account_number ? `<div class="form-group"><strong>شماره حساب:</strong> ${bankInfo.account_number}</div>` : ''}
                ${bankInfo.iban ? `<div class="form-group"><strong>شبا:</strong> ${bankInfo.iban}</div>` : ''}
                ${bankInfo.card_number ? `<div class="form-group"><strong>شماره کارت:</strong> ${bankInfo.card_number}</div>` : ''}
                ` : ''}
                
                <div class="form-row" style="margin-top: 20px;">
                    <div class="form-group">
                        <strong>تعداد فروش:</strong> <span class="badge badge-success">${contact.sales_count}</span>
                    </div>
                    <div class="form-group">
                        <strong>تعداد خرید:</strong> <span class="badge badge-warning">${contact.purchases_count}</span>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>