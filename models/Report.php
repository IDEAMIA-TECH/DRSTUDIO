<?php
/**
 * Modelo Report - DT Studio
 * Gestión de reportes del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Report {
    private $db;
    private $table = 'reports';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los reportes con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '', $type = null, $userId = null) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(r.name LIKE ? OR r.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($type !== null) {
            $whereConditions[] = "r.type = ?";
            $params[] = $type;
        }
        
        if ($userId !== null) {
            $whereConditions[] = "r.user_id = ?";
            $params[] = $userId;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT r.*, 
                       u.name as user_name,
                       (SELECT COUNT(*) FROM report_data rd WHERE rd.report_id = r.id) as data_count
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id
                {$whereClause}
                ORDER BY r.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $reports = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} r 
                     LEFT JOIN users u ON r.user_id = u.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $reports,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener reporte por ID
     */
    public function getById($id) {
        $sql = "SELECT r.*, 
                       u.name as user_name
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Crear nuevo reporte
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'type', 'user_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Preparar datos para inserción
        $fields = ['name', 'description', 'type', 'user_id', 'config', 'is_public', 'is_active'];
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
     * Actualizar reporte
     */
    public function update($id, $data) {
        // Verificar que el reporte existe
        if (!$this->getById($id)) {
            throw new Exception("Reporte no encontrado");
        }

        // Preparar datos para actualización
        $fields = ['name', 'description', 'type', 'config', 'is_public', 'is_active'];
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
     * Eliminar reporte
     */
    public function delete($id) {
        // Verificar que el reporte existe
        if (!$this->getById($id)) {
            throw new Exception("Reporte no encontrado");
        }

        // Eliminar datos del reporte
        $this->db->query("DELETE FROM report_data WHERE report_id = ?", [$id]);

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado del reporte
     */
    public function changeStatus($id, $isActive) {
        $sql = "UPDATE {$this->table} SET is_active = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$isActive ? 1 : 0, $id]);
        return true;
    }

    /**
     * Obtener reportes por tipo
     */
    public function getByType($type, $limit = 20) {
        $sql = "SELECT r.*, 
                       u.name as user_name
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.type = ? AND r.is_active = 1
                ORDER BY r.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$type, $limit]);
    }

    /**
     * Obtener reportes públicos
     */
    public function getPublic($limit = 20) {
        $sql = "SELECT r.*, 
                       u.name as user_name
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.is_public = 1 AND r.is_active = 1
                ORDER BY r.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener reportes por usuario
     */
    public function getByUser($userId, $limit = 20) {
        $sql = "SELECT r.*, 
                       u.name as user_name
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.user_id = ? 
                ORDER BY r.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Duplicar reporte
     */
    public function duplicate($id, $newName = null) {
        $report = $this->getById($id);
        if (!$report) {
            throw new Exception("Reporte no encontrado");
        }

        $data = [
            'name' => $newName ?: $report['name'] . ' (Copia)',
            'description' => $report['description'],
            'type' => $report['type'],
            'user_id' => $report['user_id'],
            'config' => $report['config'],
            'is_public' => 0, // Las copias son privadas por defecto
            'is_active' => 1
        ];

        return $this->create($data);
    }

    /**
     * Validar datos de reporte
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } else {
            $validTypes = ['sales', 'products', 'customers', 'quotations', 'orders', 'financial', 'custom'];
            if (!in_array($data['type'], $validTypes)) {
                $errors['type'] = 'El tipo no es válido';
            }
        }

        // Validar usuario
        if (empty($data['user_id'])) {
            $errors['user_id'] = 'El usuario es requerido';
        } else {
            $user = $this->db->fetch("SELECT id FROM users WHERE id = ?", [$data['user_id']]);
            if (!$user) {
                $errors['user_id'] = 'El usuario seleccionado no existe';
            }
        }

        // Validar descripción
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La descripción no puede tener más de 1000 caracteres';
        }

        // Validar configuración JSON
        if (!empty($data['config'])) {
            if (is_string($data['config'])) {
                json_decode($data['config']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors['config'] = 'La configuración debe ser un JSON válido';
                }
            }
        }

        return $errors;
    }

    /**
     * Obtener tipos de reportes disponibles
     */
    public function getAvailableTypes() {
        return [
            'sales' => 'Ventas',
            'products' => 'Productos',
            'customers' => 'Clientes',
            'quotations' => 'Cotizaciones',
            'orders' => 'Pedidos',
            'financial' => 'Financiero',
            'custom' => 'Personalizado'
        ];
    }

    /**
     * Obtener plantillas de reportes
     */
    public function getTemplates() {
        return [
            'sales_summary' => [
                'name' => 'Resumen de Ventas',
                'type' => 'sales',
                'description' => 'Reporte de ventas por período',
                'config' => json_encode([
                    'period' => 'month',
                    'group_by' => 'day',
                    'include_charts' => true
                ])
            ],
            'top_products' => [
                'name' => 'Productos Más Vendidos',
                'type' => 'products',
                'description' => 'Ranking de productos por ventas',
                'config' => json_encode([
                    'limit' => 10,
                    'period' => 'month',
                    'include_variants' => true
                ])
            ],
            'customer_analysis' => [
                'name' => 'Análisis de Clientes',
                'type' => 'customers',
                'description' => 'Análisis de comportamiento de clientes',
                'config' => json_encode([
                    'include_orders' => true,
                    'include_quotations' => true,
                    'group_by' => 'city'
                ])
            ],
            'quotation_conversion' => [
                'name' => 'Conversión de Cotizaciones',
                'type' => 'quotations',
                'description' => 'Tasa de conversión de cotizaciones a pedidos',
                'config' => json_encode([
                    'period' => 'month',
                    'include_reasons' => true
                ])
            ],
            'order_status' => [
                'name' => 'Estado de Pedidos',
                'type' => 'orders',
                'description' => 'Distribución de pedidos por estado',
                'config' => json_encode([
                    'include_delivery' => true,
                    'group_by' => 'status'
                ])
            ],
            'financial_summary' => [
                'name' => 'Resumen Financiero',
                'type' => 'financial',
                'description' => 'Ingresos, gastos y utilidades',
                'config' => json_encode([
                    'period' => 'month',
                    'include_trends' => true
                ])
            ]
        ];
    }

    /**
     * Crear reporte desde plantilla
     */
    public function createFromTemplate($templateKey, $userId, $customName = null) {
        $templates = $this->getTemplates();
        
        if (!isset($templates[$templateKey])) {
            throw new Exception("Plantilla no encontrada");
        }

        $template = $templates[$templateKey];
        
        $data = [
            'name' => $customName ?: $template['name'],
            'description' => $template['description'],
            'type' => $template['type'],
            'user_id' => $userId,
            'config' => $template['config'],
            'is_public' => 0,
            'is_active' => 1
        ];

        return $this->create($data);
    }
}
