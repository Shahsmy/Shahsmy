<?php
require_once '../config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $role;
    public $permissions;
    public $status;
    public $created_at;
    public $last_login;
    public $failed_login_attempts;
    public $locked_until;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET username=:username, email=:email, password_hash=:password_hash, 
                     role=:role, permissions=:permissions, status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->permissions = json_encode($this->permissions);
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":permissions", $this->permissions);
        $stmt->bindParam(":status", $this->status);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, password_hash, role, permissions, status, 
                         failed_login_attempts, locked_until 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account is locked
            if($row['locked_until'] && strtotime($row['locked_until']) > time()) {
                return array('success' => false, 'message' => 'حساب شما قفل شده است');
            }
            
            if(password_verify($password, $row['password_hash'])) {
                // Reset failed attempts on successful login
                $this->resetFailedAttempts($row['id']);
                $this->updateLastLogin($row['id']);
                
                return array(
                    'success' => true,
                    'user' => array(
                        'id' => $row['id'],
                        'username' => $row['username'],
                        'email' => $row['email'],
                        'role' => $row['role'],
                        'permissions' => json_decode($row['permissions'], true)
                    )
                );
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts($row['id']);
                return array('success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است');
            }
        }
        
        return array('success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است');
    }
    
    public function getAllUsers() {
        $query = "SELECT id, username, email, role, status, created_at, last_login 
                  FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserById($id) {
        $query = "SELECT id, username, email, role, permissions, status, created_at, last_login 
                  FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateUser($id, $data) {
        $setClause = array();
        $params = array();
        
        if(isset($data['username'])) {
            $setClause[] = "username = :username";
            $params[':username'] = htmlspecialchars(strip_tags($data['username']));
        }
        if(isset($data['email'])) {
            $setClause[] = "email = :email";
            $params[':email'] = htmlspecialchars(strip_tags($data['email']));
        }
        if(isset($data['role'])) {
            $setClause[] = "role = :role";
            $params[':role'] = htmlspecialchars(strip_tags($data['role']));
        }
        if(isset($data['permissions'])) {
            $setClause[] = "permissions = :permissions";
            $params[':permissions'] = json_encode($data['permissions']);
        }
        if(isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = htmlspecialchars(strip_tags($data['status']));
        }
        if(isset($data['password'])) {
            $setClause[] = "password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if(empty($setClause)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $setClause) . " WHERE id = :id";
        $params[':id'] = $id;
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }
    
    public function deleteUser($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
    private function incrementFailedAttempts($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET failed_login_attempts = failed_login_attempts + 1,
                      locked_until = CASE 
                          WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                          ELSE NULL 
                      END
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }
    
    private function resetFailedAttempts($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET failed_login_attempts = 0, locked_until = NULL 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }
    
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }
    
    public function hasPermission($user_id, $permission) {
        $user = $this->getUserById($user_id);
        if($user && $user['permissions']) {
            $permissions = json_decode($user['permissions'], true);
            return in_array($permission, $permissions);
        }
        return false;
    }
}
?>