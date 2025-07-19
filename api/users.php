<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Session.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $session = new Session($db);
    
    // Validate session for all requests
    $session->requireLogin();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get users list
            $session->requirePermission('users_view');
            $users = $user->getAllUsers();
            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        case 'POST':
            // Create new user
            $session->requirePermission('users_create');
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'اطلاعات کاربر ناقص است']);
                exit();
            }
            
            $user->username = $input['username'];
            $user->email = $input['email'];
            $user->password_hash = $input['password'];
            $user->role = $input['role'] ?? 'user';
            $user->permissions = $input['permissions'] ?? [];
            $user->status = $input['status'] ?? 'active';
            
            if ($user->create()) {
                $session->logActivity($_SESSION['user_id'], 'create_user', 'کاربر جدید ایجاد شد: ' . $input['username']);
                echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت ایجاد شد']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'خطا در ایجاد کاربر']);
            }
            break;
            
        case 'PUT':
            // Update user
            $session->requirePermission('users_edit');
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'شناسه کاربر الزامی است']);
                exit();
            }
            
            $updateData = array();
            if (isset($input['username'])) $updateData['username'] = $input['username'];
            if (isset($input['email'])) $updateData['email'] = $input['email'];
            if (isset($input['role'])) $updateData['role'] = $input['role'];
            if (isset($input['permissions'])) $updateData['permissions'] = $input['permissions'];
            if (isset($input['status'])) $updateData['status'] = $input['status'];
            if (isset($input['password']) && !empty($input['password'])) {
                $updateData['password'] = $input['password'];
            }
            
            if ($user->updateUser($input['id'], $updateData)) {
                $session->logActivity($_SESSION['user_id'], 'update_user', 'کاربر بروزرسانی شد: ' . $input['id']);
                echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت بروزرسانی شد']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی کاربر']);
            }
            break;
            
        case 'DELETE':
            // Delete user
            $session->requirePermission('users_delete');
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'شناسه کاربر الزامی است']);
                exit();
            }
            
            // Prevent self-deletion
            if ($input['id'] == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'نمی‌توانید خودتان را حذف کنید']);
                exit();
            }
            
            if ($user->deleteUser($input['id'])) {
                $session->logActivity($_SESSION['user_id'], 'delete_user', 'کاربر حذف شد: ' . $input['id']);
                echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت حذف شد']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'خطا در حذف کاربر']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'روش غیرمجاز']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()]);
}
?>