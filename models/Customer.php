<?php
/**
 * Modelo Customer - DT Studio
 * Gestión de clientes del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Customer {
    private $db;
    private $table = 'customers';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los clientes con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '', $status = null) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($status !== null) {
            $whereConditions[] = "c.is_active = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
                       (SELECT SUM(o.total) FROM orders o WHERE o.customer_id = c.id AND o.payment_status = 'paid') as total_spent,
                       (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id = c.id) as last_order_date
                FROM {$this->table} c 
                {$whereClause}
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $customers = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} c 
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $customers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener cliente por ID
     */
    public function getById($id) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
                       (SELECT SUM(o.total) FROM orders o WHERE o.customer_id = c.id AND o.payment_status = 'paid') as total_spent,
                       (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id = c.id) as last_order_date
                FROM {$this->table} c 
                WHERE c.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener cliente por email
     */
    public function getByEmail($email) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
                       (SELECT SUM(o.total) FROM orders o WHERE o.customer_id = c.id AND o.payment_status = 'paid') as total_spent
                FROM {$this->table} c 
                WHERE c.email = ?";
        
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Crear nuevo cliente
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Verificar si el email ya existe
        if ($this->getByEmail($data['email'])) {
            throw new Exception("El email ya está registrado");
        }

        // Preparar datos para inserción
        $fields = ['name', 'email', 'phone', 'company', 'address', 'city', 'state', 'postal_code', 'country', 'notes', 'is_active'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
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
     * Actualizar cliente
     */
    public function update($id, $data) {
        // Verificar que el cliente existe
        if (!$this->getById($id)) {
            throw new Exception("Cliente no encontrado");
        }

        // Si se está cambiando el email, verificar que no exista
        if (isset($data['email'])) {
            $existingCustomer = $this->getByEmail($data['email']);
            if ($existingCustomer && $existingCustomer['id'] != $id) {
                throw new Exception("El email ya está en uso");
            }
        }

        // Preparar datos para actualización
        $fields = ['name', 'email', 'phone', 'company', 'address', 'city', 'state', 'postal_code', 'country', 'notes', 'is_active'];
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
     * Eliminar cliente
     */
    public function delete($id) {
        // Verificar que el cliente existe
        if (!$this->getById($id)) {
            throw new Exception("Cliente no encontrado");
        }

        // Verificar si tiene cotizaciones o pedidos asociados
        $quotationCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM quotations WHERE customer_id = ?",
            [$id]
        )['count'];

        $orderCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?",
            [$id]
        )['count'];

        if ($quotationCount > 0 || $orderCount > 0) {
            throw new Exception("No se puede eliminar un cliente que tiene cotizaciones o pedidos asociados");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado del cliente
     */
    public function changeStatus($id, $status) {
        $validStatuses = [0, 1]; // 0 = inactivo, 1 = activo
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Estado no válido");
        }

        $sql = "UPDATE {$this->table} SET is_active = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$status, $id]);
        return true;
    }

    /**
     * Buscar clientes
     */
    public function search($query, $limit = 20) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                FROM {$this->table} c 
                WHERE (c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.phone LIKE ?) 
                AND c.is_active = 1
                ORDER BY c.name ASC 
                LIMIT ?";
        
        $params = ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%", $limit];
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener clientes más activos
     */
    public function getMostActive($limit = 10) {
        $sql = "SELECT c.*, 
                       COUNT(o.id) as order_count,
                       SUM(o.total) as total_spent,
                       MAX(o.created_at) as last_order_date
                FROM {$this->table} c 
                LEFT JOIN orders o ON c.id = o.customer_id 
                WHERE c.is_active = 1
                GROUP BY c.id 
                ORDER BY order_count DESC, total_spent DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener clientes por ciudad
     */
    public function getByCity($city, $limit = 20) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                FROM {$this->table} c 
                WHERE c.city = ? AND c.is_active = 1
                ORDER BY c.name ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$city, $limit]);
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function getStats() {
        $stats = [];

        // Total de clientes
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Clientes activos
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1")['count'];

        // Clientes inactivos
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Clientes con cotizaciones
        $stats['with_quotations'] = $this->db->fetch(
            "SELECT COUNT(DISTINCT c.id) as count 
             FROM {$this->table} c 
             INNER JOIN quotations q ON c.id = q.customer_id"
        )['count'];

        // Clientes con pedidos
        $stats['with_orders'] = $this->db->fetch(
            "SELECT COUNT(DISTINCT c.id) as count 
             FROM {$this->table} c 
             INNER JOIN orders o ON c.id = o.customer_id"
        )['count'];

        // Clientes por ciudad
        $stats['by_city'] = $this->db->fetchAll(
            "SELECT city, COUNT(*) as customer_count 
             FROM {$this->table} 
             WHERE city IS NOT NULL AND city != '' 
             GROUP BY city 
             ORDER BY customer_count DESC 
             LIMIT 10"
        );

        // Clientes más activos
        $stats['most_active'] = $this->db->fetchAll(
            "SELECT c.name, c.email, COUNT(o.id) as order_count, SUM(o.total) as total_spent 
             FROM {$this->table} c 
             LEFT JOIN orders o ON c.id = o.customer_id 
             WHERE c.is_active = 1
             GROUP BY c.id, c.name, c.email 
             ORDER BY order_count DESC, total_spent DESC 
             LIMIT 5"
        );

        // Nuevos clientes este mes
        $stats['new_this_month'] = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM {$this->table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        )['count'];

        return $stats;
    }

    /**
     * Validar datos de cliente
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        } elseif (strlen($data['email']) > 255) {
            $errors['email'] = 'El email no puede tener más de 255 caracteres';
        }

        // Validar teléfono
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 20) {
                $errors['phone'] = 'El teléfono no puede tener más de 20 caracteres';
            }
        }

        // Validar empresa
        if (!empty($data['company']) && strlen($data['company']) > 255) {
            $errors['company'] = 'La empresa no puede tener más de 255 caracteres';
        }

        // Validar dirección
        if (!empty($data['address']) && strlen($data['address']) > 500) {
            $errors['address'] = 'La dirección no puede tener más de 500 caracteres';
        }

        // Validar ciudad
        if (!empty($data['city']) && strlen($data['city']) > 100) {
            $errors['city'] = 'La ciudad no puede tener más de 100 caracteres';
        }

        // Validar estado
        if (!empty($data['state']) && strlen($data['state']) > 100) {
            $errors['state'] = 'El estado no puede tener más de 100 caracteres';
        }

        // Validar código postal
        if (!empty($data['postal_code']) && strlen($data['postal_code']) > 20) {
            $errors['postal_code'] = 'El código postal no puede tener más de 20 caracteres';
        }

        // Validar país
        if (!empty($data['country']) && strlen($data['country']) > 100) {
            $errors['country'] = 'El país no puede tener más de 100 caracteres';
        }

        // Validar notas
        if (!empty($data['notes']) && strlen($data['notes']) > 1000) {
            $errors['notes'] = 'Las notas no pueden tener más de 1000 caracteres';
        }

        return $errors;
    }

    /**
     * Duplicar cliente
     */
    public function duplicate($id, $newEmail) {
        $customer = $this->getById($id);
        if (!$customer) {
            throw new Exception("Cliente no encontrado");
        }

        $data = [
            'name' => $customer['name'] . ' (Copia)',
            'email' => $newEmail,
            'phone' => $customer['phone'],
            'company' => $customer['company'],
            'address' => $customer['address'],
            'city' => $customer['city'],
            'state' => $customer['state'],
            'postal_code' => $customer['postal_code'],
            'country' => $customer['country'],
            'notes' => $customer['notes'] . ' (Copia)',
            'is_active' => 1
        ];

        return $this->create($data);
    }

    /**
     * Obtener historial del cliente
     */
    public function getHistory($id) {
        $history = [];

        // Cotizaciones
        $quotations = $this->db->fetchAll(
            "SELECT q.*, u.name as created_by_name 
             FROM quotations q 
             LEFT JOIN users u ON q.user_id = u.id 
             WHERE q.customer_id = ? 
             ORDER BY q.created_at DESC",
            [$id]
        );

        // Pedidos
        $orders = $this->db->fetchAll(
            "SELECT o.*, u.name as created_by_name 
             FROM orders o 
             LEFT JOIN users u ON o.created_by = u.id 
             WHERE o.customer_id = ? 
             ORDER BY o.created_at DESC",
            [$id]
        );

        $history['quotations'] = $quotations;
        $history['orders'] = $orders;

        return $history;
    }

    /**
     * Obtener clientes para select
     */
    public function getForSelect() {
        $sql = "SELECT id, name, email, company FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql);
    }
}
