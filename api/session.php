<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/Session.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $session = new Session($db);
    
    $sessionInfo = $session->getSessionInfo();
    
    if ($sessionInfo) {
        echo json_encode([
            'success' => true,
            'session' => $sessionInfo,
            'authenticated' => true
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'message' => 'جلسه منقضی شده'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()]);
}
?>