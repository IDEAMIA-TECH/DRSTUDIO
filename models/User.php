<?php
/**
 * Modelo User - DT Studio
 * Gestión de usuarios del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los usuarios con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE u.name LIKE ? OR u.email LIKE ?";
            $params = ["%{$search}%", "%{$search}%"];
        }
        
        $sql = "SELECT u.*, r.name as role_name 
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                {$whereClause}
                ORDER BY u.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $users = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} u 
                     JOIN roles r ON u.role_id = r.id 
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $sql = "SELECT u.*, r.name as role_name, r.permissions 
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener usuario por email
     */
    public function getByEmail($email) {
        $sql = "SELECT u.*, r.name as role_name, r.permissions 
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = ?";
        
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Crear nuevo usuario
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'email', 'password', 'role_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Verificar si el email ya existe
        if ($this->getByEmail($data['email'])) {
            throw new Exception("El email ya está registrado");
        }

        // Hash de la contraseña
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Preparar datos para inserción
        $fields = [];
        $values = [];
        $placeholders = [];

        foreach (['name', 'email', 'password', 'role_id', 'phone', 'avatar', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $values[] = $data[$field];
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $data) {
        // Verificar que el usuario existe
        if (!$this->getById($id)) {
            throw new Exception("Usuario no encontrado");
        }

        // Si se está cambiando el email, verificar que no exista
        if (isset($data['email'])) {
            $existingUser = $this->getByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception("El email ya está registrado");
            }
        }

        // Si se está cambiando la contraseña, hacer hash
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']); // No actualizar si está vacía
        }

        // Preparar datos para actualización
        $fields = ['name', 'email', 'password', 'role_id', 'phone', 'avatar', 'is_active'];
        $setParts = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($setParts)) {
            throw new Exception("No hay datos para actualizar");
        }

        $values[] = $id; // Para el WHERE
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = ?";

        $this->db->query($sql, $values);
        return true;
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function delete($id) {
        // Verificar que el usuario existe
        if (!$this->getById($id)) {
            throw new Exception("Usuario no encontrado");
        }

        // No permitir eliminar el último administrador
        $adminCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} u 
             JOIN roles r ON u.role_id = r.id 
             WHERE r.name = 'Administrador' AND u.is_active = 1"
        )['count'];

        $user = $this->getById($id);
        if ($user['role_name'] === 'Administrador' && $adminCount <= 1) {
            throw new Exception("No se puede eliminar el último administrador");
        }

        // Soft delete
        $sql = "UPDATE {$this->table} SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore($id) {
        $sql = "UPDATE {$this->table} SET is_active = 1, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Eliminar usuario permanentemente
     */
    public function permanentDelete($id) {
        // Verificar que el usuario existe
        if (!$this->getById($id)) {
            throw new Exception("Usuario no encontrado");
        }

        // No permitir eliminar el último administrador
        $adminCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} u 
             JOIN roles r ON u.role_id = r.id 
             WHERE r.name = 'Administrador' AND u.is_active = 1"
        )['count'];

        $user = $this->getById($id);
        if ($user['role_name'] === 'Administrador' && $adminCount <= 1) {
            throw new Exception("No se puede eliminar el último administrador");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleStatus($id) {
        $user = $this->getById($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        // No permitir desactivar el último administrador
        if ($user['role_name'] === 'Administrador') {
            $adminCount = $this->db->fetch(
                "SELECT COUNT(*) as count FROM {$this->table} u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE r.name = 'Administrador' AND u.is_active = 1"
            )['count'];

            if ($adminCount <= 1 && $user['is_active']) {
                throw new Exception("No se puede desactivar el último administrador");
            }
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $sql = "UPDATE {$this->table} SET is_active = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$newStatus, $id]);
        return true;
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function getStats() {
        $stats = [];

        // Total de usuarios
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Usuarios activos
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1")['count'];

        // Usuarios inactivos
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Usuarios por rol
        $roleStats = $this->db->fetchAll(
            "SELECT r.name as role_name, COUNT(u.id) as count 
             FROM roles r 
             LEFT JOIN {$this->table} u ON r.id = u.role_id AND u.is_active = 1 
             GROUP BY r.id, r.name"
        );
        $stats['by_role'] = $roleStats;

        // Últimos usuarios registrados
        $stats['recent'] = $this->db->fetchAll(
            "SELECT u.name, u.email, u.created_at, r.name as role_name 
             FROM {$this->table} u 
             JOIN roles r ON u.role_id = r.id 
             ORDER BY u.created_at DESC 
             LIMIT 5"
        );

        return $stats;
    }

    /**
     * Validar datos de usuario
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Validar contraseña (solo para creación o si se proporciona)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es requerida';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
            }
        }

        // Validar teléfono (opcional)
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'El teléfono no es válido';
        }

        // Validar rol
        if (empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es requerido';
        } else {
            $role = $this->db->fetch("SELECT id FROM roles WHERE id = ?", [$data['role_id']]);
            if (!$role) {
                $errors['role_id'] = 'El rol seleccionado no existe';
            }
        }

        return $errors;
    }
}
