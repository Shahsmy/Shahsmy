<?php
$pageTitle = 'مدیریت کاربران';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('users');

// تعریف لیست دسترسی‌ها
$availablePermissions = [
    'users' => 'مدیریت کاربران',
    'banks' => 'بانک‌ها و حساب‌ها',
    'currencies' => 'مدیریت ارزها',
    'cash_boxes' => 'صندوق‌ها',
    'contacts' => 'طرف‌حساب‌ها',
    'accounts_overview' => 'مرور حساب‌ها',
    'excel_upload' => 'بارگذاری اکسل',
    'currency_sales' => 'فروش ارز',
    'deposit_allocation' => 'تطبیق واریزی‌ها',
    'withdrawal_allocation' => 'تطبیق برداشت‌ها',
    'contacts_summary' => 'خلاصه طرف‌حساب‌ها',
    'profit_loss' => 'سود و زیان',
    'reports' => 'گزارشات',
    'settings' => 'تنظیمات'
];

// پردازش عملیات
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $permissions = $_POST['permissions'] ?? [];
            
            // بررسی وجود نام کاربری
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existingUser) {
                throw new Exception('نام کاربری قبلاً وجود دارد');
            }
            
            $userData = [
                'username' => $username,
                'password' => md5($password),
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'permissions' => json_encode($permissions),
                'status' => 'active'
            ];
            
            $db->insert('users', $userData);
            
            header('Location: users.php?success=کاربر با موفقیت اضافه شد');
            exit;
            
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $username = trim($_POST['username']);
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $permissions = $_POST['permissions'] ?? [];
            
            // بررسی وجود نام کاربری برای کاربران دیگر
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
            if ($existingUser) {
                throw new Exception('نام کاربری قبلاً وجود دارد');
            }
            
            $userData = [
                'username' => $username,
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'permissions' => json_encode($permissions)
            ];
            
            // اگر رمز عبور وارد شده، آن را هم بروزرسانی کن
            if (!empty($_POST['password'])) {
                $userData['password'] = md5($_POST['password']);
            }
            
            $db->update('users', $userData, 'id = ?', ['id' => $id]);
            
            header('Location: users.php?success=کاربر با موفقیت ویرایش شد');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // جلوگیری از حذف کاربر فعلی
            if ($id == $currentUser['id']) {
                throw new Exception('نمی‌توانید خودتان را حذف کنید');
            }
            
            $db->update('users', ['status' => 'inactive'], 'id = ?', ['id' => $id]);
            
            header('Location: users.php?success=کاربر با موفقیت حذف شد');
            exit;
            
        } elseif ($action === 'toggle_status') {
            $id = (int)$_POST['id'];
            $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
            
            if ($id == $currentUser['id']) {
                throw new Exception('نمی‌توانید وضعیت خودتان را تغییر دهید');
            }
            
            $db->update('users', ['status' => $status], 'id = ?', ['id' => $id]);
            
            header('Location: users.php?success=وضعیت کاربر با موفقیت تغییر کرد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: users.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// دریافت لیست کاربران
$users = $db->fetchAll("
    SELECT * FROM users 
    ORDER BY created_at DESC
");
?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users"></i>
        مدیریت کاربران
        <button class="btn btn-success btn-small" style="float: left;" data-modal="addUserModal">
            <i class="fas fa-plus"></i>
            افزودن کاربر جدید
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="usersTable">
                <thead>
                    <tr>
                        <th data-sort="username">نام کاربری</th>
                        <th data-sort="full_name">نام کامل</th>
                        <th data-sort="email">ایمیل</th>
                        <th data-sort="phone">تلفن</th>
                        <th data-sort="role">نقش</th>
                        <th data-sort="status">وضعیت</th>
                        <th data-sort="created_at">تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-danger">مدیر</span>
                            <?php elseif ($user['role'] === 'accountant'): ?>
                                <span class="badge badge-warning">حسابدار</span>
                            <?php else: ?>
                                <span class="badge badge-info">اپراتور</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-success">فعال</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo jalaliDate($user['created_at']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-small edit-user-btn" 
                                    data-user='<?php echo json_encode($user); ?>'>
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </button>
                            
                            <?php if ($user['id'] != $currentUser['id']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                <button type="submit" class="btn btn-secondary btn-small">
                                    <i class="fas fa-power-off"></i>
                                    <?php echo $user['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این کاربر اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
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

<!-- Modal افزودن کاربر -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>افزودن کاربر جدید</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="addUserForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">نام کاربری</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">رمز عبور</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">نام کامل</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">نقش</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="operator">اپراتور</option>
                            <option value="accountant">حسابدار</option>
                            <option value="admin">مدیر</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">ایمیل</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="phone">شماره تلفن</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>دسترسی‌های صفحات</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                        <?php foreach ($availablePermissions as $key => $label): ?>
                        <div style="margin-bottom: 8px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" style="margin-left: 8px;">
                                <?php echo $label; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="all" style="margin-left: 8px;">
                                <strong>دسترسی به همه صفحات</strong>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        ذخیره کاربر
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addUserModal'))">
                        <i class="fas fa-times"></i>
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ویرایش کاربر -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>ویرایش کاربر</h4>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_username">نام کاربری</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">رمز عبور جدید (اختیاری)</label>
                        <input type="password" name="password" id="edit_password" class="form-control" placeholder="برای عدم تغییر خالی بگذارید">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_full_name">نام کامل</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">نقش</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="operator">اپراتور</option>
                            <option value="accountant">حسابدار</option>
                            <option value="admin">مدیر</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">ایمیل</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">شماره تلفن</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>دسترسی‌های صفحات</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                        <?php foreach ($availablePermissions as $key => $label): ?>
                        <div style="margin-bottom: 8px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" 
                                       id="edit_perm_<?php echo $key; ?>" style="margin-left: 8px;">
                                <?php echo $label; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px;">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="permissions[]" value="all" 
                                       id="edit_perm_all" style="margin-left: 8px;">
                                <strong>دسترسی به همه صفحات</strong>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        بروزرسانی کاربر
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editUserModal'))">
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
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const user = JSON.parse(this.dataset.user);
            const modal = document.getElementById('editUserModal');
            
            // پر کردن فرم
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role;
            
            // پاک کردن چک‌باکس‌ها
            modal.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
                cb.checked = false;
            });
            
            // انتخاب دسترسی‌های فعلی
            if (user.permissions) {
                try {
                    const permissions = JSON.parse(user.permissions);
                    permissions.forEach(perm => {
                        const checkbox = document.getElementById('edit_perm_' + perm);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                } catch (e) {
                    console.error('Error parsing permissions:', e);
                }
            }
            
            modal.style.display = 'block';
        });
    });
    
    // مدیریت "دسترسی به همه"
    function handleAllPermissions(isEdit = false) {
        const prefix = isEdit ? 'edit_perm_' : '';
        const allCheckbox = document.getElementById(prefix + 'all');
        const otherCheckboxes = document.querySelectorAll(`input[name="permissions[]"]:not(#${prefix}all)`);
        
        if (allCheckbox) {
            allCheckbox.addEventListener('change', function() {
                otherCheckboxes.forEach(cb => {
                    cb.checked = this.checked;
                    cb.disabled = this.checked;
                });
            });
        }
        
        otherCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked && allCheckbox) {
                    allCheckbox.checked = false;
                }
                
                // اگر همه انتخاب شده‌اند، "همه" را فعال کن
                const checkedCount = Array.from(otherCheckboxes).filter(c => c.checked).length;
                if (checkedCount === otherCheckboxes.length && allCheckbox) {
                    allCheckbox.checked = true;
                }
            });
        });
    }
    
    handleAllPermissions(false); // برای فرم افزودن
    handleAllPermissions(true);  // برای فرم ویرایش
});
</script>

<?php require_once 'includes/footer.php'; ?>