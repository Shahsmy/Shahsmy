<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'روش غیرمجاز']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'نام کاربری و رمز عبور الزامی است']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $session = new Session($db);
    
    // Clean up expired sessions
    $session->cleanupExpiredSessions();
    
    $result = $user->login($input['username'], $input['password']);
    
    if ($result['success']) {
        // Create session
        if ($session->createSession($result['user']['id'], $result['user'])) {
            $sessionInfo = $session->getSessionInfo();
            
            echo json_encode([
                'success' => true,
                'message' => 'ورود موفقیت‌آمیز',
                'user' => $result['user'],
                'session' => $sessionInfo
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'خطا در ایجاد جلسه']);
        }
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()]);
}
?>