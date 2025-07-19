<?php
$pageTitle = 'بارگذاری فایل اکسل';
require_once 'includes/header.php';

// بررسی دسترسی
requirePermission('excel_upload');

// پردازش بارگذاری فایل
if ($_POST && isset($_FILES['excel_file'])) {
    try {
        $uploadDir = 'uploads/excel/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['excel_file'];
        $fileName = time() . '_' . $file['name'];
        $filePath = $uploadDir . $fileName;
        
        // بررسی نوع فایل
        $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('نوع فایل مجاز نیست. فقط فایل‌های Excel و CSV پذیرفته می‌شوند.');
        }
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // شبیه‌سازی پردازش فایل اکسل (در حالت واقعی از کتابخانه‌هایی مثل PhpSpreadsheet استفاده می‌شود)
            $uploadData = [
                'file_name' => $fileName,
                'original_name' => $file['name'],
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'upload_type' => $_POST['upload_type'],
                'bank_account_id' => !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null,
                'upload_date' => date('Y-m-d'),
                'status' => 'pending',
                'uploaded_by' => $currentUser['id']
            ];
            
            $uploadId = $db->insert('excel_uploads', $uploadData);
            
            header('Location: excel_upload.php?success=فایل با موفقیت بارگذاری شد&upload_id=' . $uploadId);
            exit;
        } else {
            throw new Exception('خطا در بارگذاری فایل');
        }
        
    } catch (Exception $e) {
        header('Location: excel_upload.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// پردازش عملیات
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'process_upload') {
            $uploadId = (int)$_POST['upload_id'];
            
            // شبیه‌سازی پردازش فایل
            // در حالت واقعی، فایل Excel خوانده شده و تراکنش‌ها استخراج می‌شوند
            
            $sampleData = [
                [
                    'transaction_date' => '2024-01-15',
                    'transaction_time' => '10:30:00',
                    'description' => 'واریز حساب',
                    'branch_code' => '001',
                    'user_code' => 'U001',
                    'receipt_number' => 'R123456',
                    'payment_amount' => 0,
                    'deposit_amount' => 5000000,
                    'balance' => 15000000,
                    'additional_info' => 'واریز نقدی',
                    'psp' => 'بانک ملی',
                    'payer_name' => 'احمد محمدی',
                    'iban' => 'IR123456789012345678901234',
                    'card_number' => '6037991234567890',
                    'tracking_code' => 'TRK123456',
                    'serial_number' => 'SER789012',
                    'reference_number' => 'REF345678',
                    'payment_id' => 'PAY901234',
                    'country' => 'ایران',
                    'terminal_number' => 'T567890',
                    'source_account' => '1234567890',
                    'destination_account' => '0987654321'
                ],
                [
                    'transaction_date' => '2024-01-15',
                    'transaction_time' => '14:45:00',
                    'description' => 'برداشت حساب',
                    'branch_code' => '001',
                    'user_code' => 'U002',
                    'receipt_number' => 'R789012',
                    'payment_amount' => 2000000,
                    'deposit_amount' => 0,
                    'balance' => 13000000,
                    'additional_info' => 'برداشت کارتی',
                    'psp' => 'بانک ملی',
                    'payer_name' => 'زهرا احمدی',
                    'iban' => 'IR098765432109876543210987',
                    'card_number' => '6037997654321098',
                    'tracking_code' => 'TRK789012',
                    'serial_number' => 'SER345678',
                    'reference_number' => 'REF901234',
                    'payment_id' => 'PAY567890',
                    'country' => 'ایران',
                    'terminal_number' => 'T123456',
                    'source_account' => '0987654321',
                    'destination_account' => '1234567890'
                ]
            ];
            
            $upload = $db->fetchOne("SELECT * FROM excel_uploads WHERE id = ?", [$uploadId]);
            if (!$upload) {
                throw new Exception('فایل بارگذاری شده یافت نشد');
            }
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($sampleData as $row) {
                try {
                    $transactionData = [
                        'bank_account_id' => $upload['bank_account_id'],
                        'transaction_date' => $row['transaction_date'],
                        'transaction_time' => $row['transaction_time'],
                        'description' => $row['description'],
                        'branch_code' => $row['branch_code'],
                        'user_code' => $row['user_code'],
                        'receipt_number' => $row['receipt_number'],
                        'payment_amount' => $row['payment_amount'],
                        'deposit_amount' => $row['deposit_amount'],
                        'balance' => $row['balance'],
                        'additional_info' => $row['additional_info'],
                        'psp' => $row['psp'],
                        'payer_name' => $row['payer_name'],
                        'iban' => $row['iban'],
                        'card_numbers' => $row['card_number'],
                        'tracking_code' => $row['tracking_code'],
                        'serial_number' => $row['serial_number'],
                        'reference_number' => $row['reference_number'],
                        'payment_id' => $row['payment_id'],
                        'country' => $row['country'],
                        'terminal_number' => $row['terminal_number'],
                        'source_account' => $row['source_account'],
                        'destination_account' => $row['destination_account'],
                        'excel_upload_id' => $uploadId,
                        'status' => 'unallocated'
                    ];
                    
                    $db->insert('bank_transactions', $transactionData);
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errorCount++;
                }
            }
            
            // بروزرسانی وضعیت بارگذاری
            $db->update('excel_uploads', [
                'status' => 'processed',
                'processed_at' => date('Y-m-d H:i:s'),
                'total_records' => $successCount + $errorCount,
                'success_records' => $successCount,
                'error_records' => $errorCount
            ], 'id = ?', ['id' => $uploadId]);
            
            header('Location: excel_upload.php?success=فایل با موفقیت پردازش شد. ' . $successCount . ' رکورد موفق، ' . $errorCount . ' رکورد خطا');
            exit;
            
        } elseif ($action === 'delete_upload') {
            $uploadId = (int)$_POST['upload_id'];
            
            // حذف تراکنش‌های مرتبط
            $db->query("DELETE FROM bank_transactions WHERE excel_upload_id = ?", [$uploadId]);
            
            // حذف فایل
            $upload = $db->fetchOne("SELECT file_path FROM excel_uploads WHERE id = ?", [$uploadId]);
            if ($upload && file_exists($upload['file_path'])) {
                unlink($upload['file_path']);
            }
            
            // حذف رکورد بارگذاری
            $db->query("DELETE FROM excel_uploads WHERE id = ?", [$uploadId]);
            
            header('Location: excel_upload.php?success=فایل و تراکنش‌های مرتبط حذف شد');
            exit;
        }
        
    } catch (Exception $e) {
        header('Location: excel_upload.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// دریافت لیست بارگذاری‌ها
$uploads = $db->fetchAll("
    SELECT eu.*, ba.account_name, b.bank_name, u.full_name as uploaded_by_name
    FROM excel_uploads eu
    LEFT JOIN bank_accounts ba ON eu.bank_account_id = ba.id
    LEFT JOIN banks b ON ba.bank_id = b.id
    LEFT JOIN users u ON eu.uploaded_by = u.id
    ORDER BY eu.created_at DESC
");

// دریافت لیست حساب‌های بانکی
$bankAccounts = $db->fetchAll("
    SELECT ba.*, b.bank_name, c.currency_code
    FROM bank_accounts ba
    JOIN banks b ON ba.bank_id = b.id
    JOIN currencies c ON ba.currency_id = c.id
    WHERE ba.status = 'active'
    ORDER BY b.bank_name, ba.account_name
");

// آمار کلی
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_uploads,
        SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed_uploads,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_uploads,
        COALESCE(SUM(total_records), 0) as total_records,
        COALESCE(SUM(success_records), 0) as success_records
    FROM excel_uploads
");
?>

<!-- آمار -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['total_uploads']; ?></h3>
        <p>کل بارگذاری‌ها</p>
        <i class="fas fa-file-excel icon"></i>
    </div>
    
    <div class="stat-card success">
        <h3><?php echo $stats['processed_uploads']; ?></h3>
        <p>پردازش شده</p>
        <i class="fas fa-check-circle icon"></i>
    </div>
    
    <div class="stat-card warning">
        <h3><?php echo $stats['pending_uploads']; ?></h3>
        <p>در انتظار پردازش</p>
        <i class="fas fa-clock icon"></i>
    </div>
    
    <div class="stat-card info">
        <h3><?php echo $stats['total_records']; ?></h3>
        <p>کل رکوردها</p>
        <i class="fas fa-database icon"></i>
    </div>
</div>

<!-- فرم بارگذاری -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-cloud-upload-alt"></i>
        بارگذاری فایل Excel جدید
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="excel_file">فایل Excel *</label>
                    <input type="file" name="excel_file" id="excel_file" class="form-control" 
                           accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted">فرمت‌های مجاز: Excel (.xlsx, .xls) و CSV</small>
                </div>
                
                <div class="form-group">
                    <label for="upload_type">نوع بارگذاری *</label>
                    <select name="upload_type" id="upload_type" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="deposits_withdrawals">واریزی و برداشت</option>
                        <option value="deposits_only">فقط واریزی</option>
                        <option value="withdrawals_only">فقط برداشت</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bank_account_id">حساب بانکی *</label>
                    <select name="bank_account_id" id="bank_account_id" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($bankAccounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>">
                            <?php echo htmlspecialchars($account['bank_name']); ?> - 
                            <?php echo htmlspecialchars($account['account_name']); ?>
                            (<?php echo htmlspecialchars($account['currency_code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> راهنمای فرمت فایل Excel:</h5>
                <p>فایل Excel باید شامل ستون‌های زیر باشد:</p>
                <ul>
                    <li><strong>تاریخ تراکنش:</strong> فرمت YYYY-MM-DD</li>
                    <li><strong>ساعت تراکنش:</strong> فرمت HH:MM:SS</li>
                    <li><strong>شرح تراکنش:</strong> توضیحات تراکنش</li>
                    <li><strong>کد شعبه:</strong> کد شعبه بانک</li>
                    <li><strong>کد کاربری:</strong> کد کاربر</li>
                    <li><strong>شماره رسید:</strong> شماره رسید</li>
                    <li><strong>مبلغ پرداخت:</strong> مبلغ برداشت</li>
                    <li><strong>مبلغ واریز:</strong> مبلغ واریز</li>
                    <li><strong>موجودی:</strong> موجودی حساب</li>
                    <li><strong>اطلاعات اضافی:</strong> توضیحات اضافی</li>
                    <li><strong>PSP:</strong> ارائه‌دهنده خدمات پرداخت</li>
                    <li><strong>نام واریزکننده:</strong> نام پرداخت‌کننده</li>
                    <li><strong>شماره شبا:</strong> شماره شبا</li>
                    <li><strong>شماره کارت:</strong> شماره کارت‌ها</li>
                    <li><strong>کد پیگیری:</strong> کد پیگیری</li>
                    <li><strong>شماره سریال:</strong> شماره سریال</li>
                    <li><strong>شماره مرجع:</strong> شماره مرجع</li>
                    <li><strong>شناسه پرداخت:</strong> شناسه پرداخت</li>
                    <li><strong>کشور:</strong> کشور</li>
                    <li><strong>شماره ترمینال:</strong> شماره ترمینال</li>
                    <li><strong>حساب مبدا:</strong> حساب مبدا</li>
                    <li><strong>حساب مقصد:</strong> حساب مقصد</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-upload"></i>
                    بارگذاری فایل
                </button>
            </div>
        </form>
    </div>
</div>

<!-- لیست بارگذاری‌ها -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i>
        تاریخچه بارگذاری‌ها
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>نام فایل</th>
                        <th>نوع</th>
                        <th>حساب بانکی</th>
                        <th>تاریخ بارگذاری</th>
                        <th>وضعیت</th>
                        <th>رکوردها</th>
                        <th>موفق/خطا</th>
                        <th>بارگذاری‌کننده</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploads as $upload): ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-excel text-success"></i>
                            <?php echo htmlspecialchars($upload['original_name']); ?>
                            <br><small class="text-muted"><?php echo formatBytes($upload['file_size']); ?></small>
                        </td>
                        <td>
                            <?php if ($upload['upload_type'] === 'deposits_withdrawals'): ?>
                                <span class="badge badge-info">واریزی و برداشت</span>
                            <?php elseif ($upload['upload_type'] === 'deposits_only'): ?>
                                <span class="badge badge-success">فقط واریزی</span>
                            <?php else: ?>
                                <span class="badge badge-warning">فقط برداشت</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($upload['bank_name']): ?>
                                <?php echo htmlspecialchars($upload['bank_name']); ?>
                                <br><small><?php echo htmlspecialchars($upload['account_name']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">نامشخص</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo jalaliDate($upload['upload_date']); ?></td>
                        <td>
                            <?php if ($upload['status'] === 'processed'): ?>
                                <span class="badge badge-success">پردازش شده</span>
                                <?php if ($upload['processed_at']): ?>
                                <br><small class="text-muted"><?php echo jalaliDate($upload['processed_at'], true); ?></small>
                                <?php endif; ?>
                            <?php elseif ($upload['status'] === 'pending'): ?>
                                <span class="badge badge-warning">در انتظار</span>
                            <?php else: ?>
                                <span class="badge badge-danger">خطا</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($upload['total_records']): ?>
                                <span class="badge badge-info"><?php echo $upload['total_records']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($upload['status'] === 'processed'): ?>
                                <span class="text-success"><?php echo $upload['success_records']; ?></span> / 
                                <span class="text-danger"><?php echo $upload['error_records']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($upload['uploaded_by_name']); ?></td>
                        <td>
                            <?php if ($upload['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="process_upload">
                                <input type="hidden" name="upload_id" value="<?php echo $upload['id']; ?>">
                                <button type="submit" class="btn btn-warning btn-small" 
                                        onclick="return confirm('آیا از پردازش این فایل اطمینان دارید؟')">
                                    <i class="fas fa-cog"></i>
                                    پردازش
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($upload['status'] === 'processed'): ?>
                            <a href="bank_transactions.php?upload_id=<?php echo $upload['id']; ?>" 
                               class="btn btn-info btn-small">
                                <i class="fas fa-eye"></i>
                                مشاهده تراکنش‌ها
                            </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirmDelete('آیا از حذف این فایل و تمام تراکنش‌های مرتبط اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete_upload">
                                <input type="hidden" name="upload_id" value="<?php echo $upload['id']; ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // اعتبارسنجی فایل
    document.getElementById('excel_file').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const allowedTypes = [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                alert('نوع فایل مجاز نیست. لطفاً فایل Excel یا CSV انتخاب کنید.');
                this.value = '';
                return;
            }
            
            // بررسی حجم فایل (حداکثر 10 مگابایت)
            if (file.size > 10 * 1024 * 1024) {
                alert('حجم فایل نباید بیشتر از 10 مگابایت باشد.');
                this.value = '';
                return;
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>