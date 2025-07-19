<?php
require_once '../config/database.php';

class Session {
    private $conn;
    private $session_table = "user_sessions";
    private $activity_table = "user_activity_log";
    
    public function __construct($db) {
        $this->conn = $db;
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function createSession($user_id, $user_data) {
        $session_id = session_id();
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Deactivate any existing sessions for this user
        $this->deactivateUserSessions($user_id);
        
        $query = "INSERT INTO " . $this->session_table . " 
                  SET user_id=:user_id, session_id=:session_id, ip_address=:ip_address, 
                      user_agent=:user_agent, is_active=1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":user_agent", $user_agent);
        
        if($stmt->execute()) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['role'] = $user_data['role'];
            $_SESSION['permissions'] = $user_data['permissions'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            $this->logActivity($user_id, 'login', 'کاربر وارد سیستم شد');
            return true;
        }
        return false;
    }
    
    public function validateSession() {
        if(!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check if session expired (30 minutes)
        if((time() - $_SESSION['last_activity']) > 1800) {
            $this->destroySession();
            return false;
        }
        
        // Verify session exists in database
        $session_id = session_id();
        $query = "SELECT id FROM " . $this->session_table . " 
                  WHERE session_id = :session_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $this->updateActivity();
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    public function destroySession() {
        $session_id = session_id();
        
        if(isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'کاربر از سیستم خارج شد');
        }
        
        // Mark session as inactive in database
        $query = "UPDATE " . $this->session_table . " 
                  SET is_active = 0, logout_time = NOW() 
                  WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();
        
        // Clear PHP session
        session_destroy();
        session_unset();
    }
    
    public function requireLogin() {
        if(!$this->validateSession()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if(!isset($_SESSION['permissions']) || !in_array($permission, $_SESSION['permissions'])) {
            header('HTTP/1.1 403 Forbidden');
            die('دسترسی غیرمجاز');
        }
    }
    
    public function getSessionInfo() {
        if(!$this->validateSession()) {
            return null;
        }
        
        return array(
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'permissions' => $_SESSION['permissions'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity'],
            'session_duration' => time() - $_SESSION['login_time'],
            'time_remaining' => 1800 - (time() - $_SESSION['last_activity'])
        );
    }
    
    public function logActivity($user_id, $action, $details = null) {
        $session_id = session_id();
        $ip_address = $this->getClientIP();
        
        $query = "INSERT INTO " . $this->activity_table . " 
                  SET user_id=:user_id, session_id=:session_id, action=:action, 
                      details=:details, ip_address=:ip_address";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":details", $details);
        $stmt->bindParam(":ip_address", $ip_address);
        
        $stmt->execute();
    }
    
    public function getUserActivities($user_id, $limit = 50) {
        $query = "SELECT action, details, ip_address, timestamp 
                  FROM " . $this->activity_table . " 
                  WHERE user_id = :user_id 
                  ORDER BY timestamp DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveSessions() {
        $query = "SELECT s.*, u.username, u.email 
                  FROM " . $this->session_table . " s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE s.is_active = 1 AND s.last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                  ORDER BY s.last_activity DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function updateActivity() {
        $session_id = session_id();
        $query = "UPDATE " . $this->session_table . " 
                  SET last_activity = NOW() 
                  WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();
    }
    
    private function deactivateUserSessions($user_id) {
        $query = "UPDATE " . $this->session_table . " 
                  SET is_active = 0, logout_time = NOW() 
                  WHERE user_id = :user_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }
    
    private function getClientIP() {
        $ipkeys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ipkeys as $keyword) {
            if (array_key_exists($keyword, $_SERVER) && !empty($_SERVER[$keyword])) {
                return $_SERVER[$keyword];
            }
        }
        return 'unknown';
    }
    
    public function cleanupExpiredSessions() {
        $query = "UPDATE " . $this->session_table . " 
                  SET is_active = 0, logout_time = NOW() 
                  WHERE is_active = 1 AND last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>