<?php
/**
 * Modelo Role - DT Studio
 * Gestión de roles del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Role {
    private $db;
    private $table = 'roles';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los roles
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE name LIKE ? OR description LIKE ?";
            $params = ["%{$search}%", "%{$search}%"];
        }
        
        $sql = "SELECT r.*, 
                       (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.is_active = 1) as user_count
                FROM {$this->table} r 
                {$whereClause}
                ORDER BY r.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $roles = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} r {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $roles,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener rol por ID
     */
    public function getById($id) {
        $sql = "SELECT r.*, 
                       (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.is_active = 1) as user_count
                FROM {$this->table} r 
                WHERE r.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener rol por nombre
     */
    public function getByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE name = ?";
        return $this->db->fetch($sql, [$name]);
    }

    /**
     * Crear nuevo rol
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Verificar si el nombre ya existe
        if ($this->getByName($data['name'])) {
            throw new Exception("El nombre del rol ya existe");
        }

        // Preparar datos para inserción
        $fields = ['name', 'description', 'permissions', 'is_active'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'permissions' && is_array($data[$field])) {
                    $values[] = json_encode($data[$field]);
                } else {
                    $values[] = $data[$field];
                }
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar rol
     */
    public function update($id, $data) {
        // Verificar que el rol existe
        if (!$this->getById($id)) {
            throw new Exception("Rol no encontrado");
        }

        // Si se está cambiando el nombre, verificar que no exista
        if (isset($data['name'])) {
            $existingRole = $this->getByName($data['name']);
            if ($existingRole && $existingRole['id'] != $id) {
                throw new Exception("El nombre del rol ya existe");
            }
        }

        // Preparar datos para actualización
        $fields = ['name', 'description', 'permissions', 'is_active'];
        $setParts = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = ?";
                if ($field === 'permissions' && is_array($data[$field])) {
                    $values[] = json_encode($data[$field]);
                } else {
                    $values[] = $data[$field];
                }
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
     * Eliminar rol
     */
    public function delete($id) {
        // Verificar que el rol existe
        $role = $this->getById($id);
        if (!$role) {
            throw new Exception("Rol no encontrado");
        }

        // No permitir eliminar roles del sistema
        $systemRoles = ['Administrador', 'Ventas', 'Diseñador/Producción', 'Solo Lectura'];
        if (in_array($role['name'], $systemRoles)) {
            throw new Exception("No se puede eliminar un rol del sistema");
        }

        // Verificar si hay usuarios asignados a este rol
        $userCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM users WHERE role_id = ? AND is_active = 1",
            [$id]
        )['count'];

        if ($userCount > 0) {
            throw new Exception("No se puede eliminar un rol que tiene usuarios asignados");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Obtener permisos disponibles
     */
    public function getAvailablePermissions() {
        return [
            'users' => [
                'label' => 'Gestión de Usuarios',
                'permissions' => [
                    'users.create' => 'Crear usuarios',
                    'users.read' => 'Ver usuarios',
                    'users.update' => 'Editar usuarios',
                    'users.delete' => 'Eliminar usuarios'
                ]
            ],
            'roles' => [
                'label' => 'Gestión de Roles',
                'permissions' => [
                    'roles.create' => 'Crear roles',
                    'roles.read' => 'Ver roles',
                    'roles.update' => 'Editar roles',
                    'roles.delete' => 'Eliminar roles'
                ]
            ],
            'products' => [
                'label' => 'Gestión de Productos',
                'permissions' => [
                    'products.create' => 'Crear productos',
                    'products.read' => 'Ver productos',
                    'products.update' => 'Editar productos',
                    'products.delete' => 'Eliminar productos'
                ]
            ],
            'categories' => [
                'label' => 'Gestión de Categorías',
                'permissions' => [
                    'categories.create' => 'Crear categorías',
                    'categories.read' => 'Ver categorías',
                    'categories.update' => 'Editar categorías',
                    'categories.delete' => 'Eliminar categorías'
                ]
            ],
            'customers' => [
                'label' => 'Gestión de Clientes',
                'permissions' => [
                    'customers.create' => 'Crear clientes',
                    'customers.read' => 'Ver clientes',
                    'customers.update' => 'Editar clientes',
                    'customers.delete' => 'Eliminar clientes'
                ]
            ],
            'quotations' => [
                'label' => 'Gestión de Cotizaciones',
                'permissions' => [
                    'quotations.create' => 'Crear cotizaciones',
                    'quotations.read' => 'Ver cotizaciones',
                    'quotations.update' => 'Editar cotizaciones',
                    'quotations.delete' => 'Eliminar cotizaciones',
                    'quotations.approve' => 'Aprobar cotizaciones'
                ]
            ],
            'orders' => [
                'label' => 'Gestión de Pedidos',
                'permissions' => [
                    'orders.create' => 'Crear pedidos',
                    'orders.read' => 'Ver pedidos',
                    'orders.update' => 'Editar pedidos',
                    'orders.delete' => 'Eliminar pedidos',
                    'orders.status' => 'Cambiar estado de pedidos'
                ]
            ],
            'reports' => [
                'label' => 'Reportes y Analytics',
                'permissions' => [
                    'reports.sales' => 'Ver reportes de ventas',
                    'reports.financial' => 'Ver reportes financieros',
                    'reports.products' => 'Ver reportes de productos',
                    'reports.customers' => 'Ver reportes de clientes'
                ]
            ],
            'settings' => [
                'label' => 'Configuración del Sistema',
                'permissions' => [
                    'settings.read' => 'Ver configuración',
                    'settings.update' => 'Modificar configuración',
                    'settings.backup' => 'Realizar respaldos'
                ]
            ],
            'banners' => [
                'label' => 'Gestión de Banners',
                'permissions' => [
                    'banners.create' => 'Crear banners',
                    'banners.read' => 'Ver banners',
                    'banners.update' => 'Editar banners',
                    'banners.delete' => 'Eliminar banners'
                ]
            ]
        ];
    }

    /**
     * Obtener roles para select
     */
    public function getForSelect() {
        $sql = "SELECT id, name FROM {$this->table} WHERE is_active = 1 ORDER BY name";
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener estadísticas de roles
     */
    public function getStats() {
        $stats = [];

        // Total de roles
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Roles activos
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1")['count'];

        // Roles inactivos
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Usuarios por rol
        $roleStats = $this->db->fetchAll(
            "SELECT r.name, COUNT(u.id) as user_count 
             FROM {$this->table} r 
             LEFT JOIN users u ON r.id = u.role_id AND u.is_active = 1 
             GROUP BY r.id, r.name 
             ORDER BY user_count DESC"
        );
        $stats['by_role'] = $roleStats;

        return $stats;
    }

    /**
     * Validar datos de rol
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'El nombre no puede tener más de 100 caracteres';
        }

        // Validar descripción (opcional)
        if (!empty($data['description']) && strlen($data['description']) > 500) {
            $errors['description'] = 'La descripción no puede tener más de 500 caracteres';
        }

        // Validar permisos
        if (isset($data['permissions']) && !is_array($data['permissions'])) {
            $errors['permissions'] = 'Los permisos deben ser un array';
        }

        return $errors;
    }

    /**
     * Duplicar rol
     */
    public function duplicate($id, $newName) {
        $role = $this->getById($id);
        if (!$role) {
            throw new Exception("Rol no encontrado");
        }

        // Verificar que el nuevo nombre no existe
        if ($this->getByName($newName)) {
            throw new Exception("El nombre del rol ya existe");
        }

        $data = [
            'name' => $newName,
            'description' => $role['description'] . ' (Copia)',
            'permissions' => json_decode($role['permissions'], true),
            'is_active' => 1
        ];

        return $this->create($data);
    }
}
