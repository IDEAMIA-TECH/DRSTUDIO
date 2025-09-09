<?php
/**
 * Clase Auth - DT Studio
 * Sistema de autenticación y autorización
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    private $session_name = 'dtstudio_user';

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Autenticar usuario
     */
    public function login($email, $password) {
        $sql = "SELECT u.*, r.name as role_name, r.permissions 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = ? AND u.is_active = 1";
        
        $user = $this->db->fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último login
            $this->db->query(
                "UPDATE users SET last_login_at = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Guardar en sesión
            $_SESSION[$this->session_name] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'permissions' => json_decode($user['permissions'], true),
                'login_time' => time()
            ];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        unset($_SESSION[$this->session_name]);
        session_destroy();
        return true;
    }

    /**
     * Verificar si está autenticado
     */
    public function isAuthenticated() {
        return isset($_SESSION[$this->session_name]);
    }

    /**
     * Obtener usuario actual
     */
    public function getCurrentUser() {
        return $_SESSION[$this->session_name] ?? null;
    }

    /**
     * Verificar permisos
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        $permissions = $user['permissions'];
        
        // Si tiene permisos de administrador
        if (isset($permissions['all']) && $permissions['all']) {
            return true;
        }
        
        // Verificar permiso específico
        return isset($permissions[$permission]) && $permissions[$permission];
    }

    /**
     * Middleware de autenticación
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Middleware de permisos
     */
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            die('Acceso denegado');
        }
    }

    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar token CSRF
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [$hashedPassword, $userId]
        );
        
        return true;
    }

    /**
     * Generar token de recuperación
     */
    public function generateResetToken($email) {
        $user = $this->db->fetch("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if (!$user) return false;
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->query(
            "UPDATE users SET remember_token = ?, updated_at = ? WHERE id = ?",
            [$token, $expires, $user['id']]
        );
        
        return $token;
    }

    /**
     * Resetear contraseña con token
     */
    public function resetPassword($token, $newPassword) {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE remember_token = ? AND updated_at > NOW()",
            [$token]
        );
        
        if (!$user) return false;
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE users SET password = ?, remember_token = NULL, updated_at = NOW() WHERE id = ?",
            [$hashedPassword, $user['id']]
        );
        
        return true;
    }
}
