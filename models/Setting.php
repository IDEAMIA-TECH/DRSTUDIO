<?php
/**
 * Modelo Setting - DT Studio
 * Gestión de configuraciones del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Setting {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear configuración
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['key', 'value'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar que la clave sea única
        $existing = $this->db->fetch("SELECT id FROM settings WHERE `key` = ?", [$data['key']]);
        if ($existing) {
            throw new Exception("Ya existe una configuración con esa clave");
        }

        $sql = "INSERT INTO settings (`key`, `value`, `type`, `description`, `is_public`, `group_name`, `sort_order`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['key'],
            $data['value'],
            $data['type'] ?? 'string',
            $data['description'] ?? null,
            $data['is_public'] ? 1 : 0,
            $data['group_name'] ?? 'general',
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener configuración por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM settings WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener configuración por clave
     */
    public function getByKey($key) {
        $sql = "SELECT * FROM settings WHERE `key` = ?";
        return $this->db->fetch($sql, [$key]);
    }

    /**
     * Obtener valor de configuración
     */
    public function getValue($key, $default = null) {
        $setting = $this->getByKey($key);
        return $setting ? $this->castValue($setting['value'], $setting['type']) : $default;
    }

    /**
     * Listar configuraciones
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por grupo
        if (!empty($filters['group_name'])) {
            $whereConditions[] = 'group_name = ?';
            $params[] = $filters['group_name'];
        }
        
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $whereConditions[] = 'type = ?';
            $params[] = $filters['type'];
        }
        
        // Filtro por visibilidad
        if (isset($filters['is_public'])) {
            $whereConditions[] = 'is_public = ?';
            $params[] = $filters['is_public'] ? 1 : 0;
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(`key` LIKE ? OR `value` LIKE ? OR description LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM settings 
                {$whereClause}
                ORDER BY group_name ASC, sort_order ASC, `key` ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        $settings = $this->db->fetchAll($sql, $params);
        
        // Convertir valores según su tipo
        foreach ($settings as &$setting) {
            $setting['value'] = $this->castValue($setting['value'], $setting['type']);
        }
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM settings {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $settings,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener configuraciones por grupo
     */
    public function getByGroup($groupName) {
        $sql = "SELECT * FROM settings 
                WHERE group_name = ? 
                ORDER BY sort_order ASC, `key` ASC";
        
        $settings = $this->db->fetchAll($sql, [$groupName]);
        
        // Convertir valores según su tipo
        foreach ($settings as &$setting) {
            $setting['value'] = $this->castValue($setting['value'], $setting['type']);
        }
        
        return $settings;
    }

    /**
     * Obtener configuraciones públicas
     */
    public function getPublic() {
        $sql = "SELECT `key`, `value`, `type` FROM settings 
                WHERE is_public = 1 
                ORDER BY group_name ASC, sort_order ASC, `key` ASC";
        
        $settings = $this->db->fetchAll($sql);
        
        // Convertir valores según su tipo
        foreach ($settings as &$setting) {
            $setting['value'] = $this->castValue($setting['value'], $setting['type']);
        }
        
        return $settings;
    }

    /**
     * Actualizar configuración
     */
    public function update($id, $data) {
        // Validar que la configuración existe
        $setting = $this->getById($id);
        if (!$setting) {
            throw new Exception("La configuración no existe");
        }

        // Validar que la clave sea única (si se está cambiando)
        if (isset($data['key']) && $data['key'] !== $setting['key']) {
            $existing = $this->db->fetch("SELECT id FROM settings WHERE `key` = ? AND id != ?", [$data['key'], $id]);
            if ($existing) {
                throw new Exception("Ya existe una configuración con esa clave");
            }
        }

        $allowedFields = ['key', 'value', 'type', 'description', 'is_public', 'group_name', 'sort_order'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $field === 'is_public' ? ($data[$field] ? 1 : 0) : $data[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE settings SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * Actualizar configuración por clave
     */
    public function updateByKey($key, $value) {
        $setting = $this->getByKey($key);
        if (!$setting) {
            throw new Exception("La configuración no existe");
        }

        $this->db->query(
            "UPDATE settings SET `value` = ?, updated_at = NOW() WHERE `key` = ?",
            [$value, $key]
        );

        return true;
    }

    /**
     * Eliminar configuración
     */
    public function delete($id) {
        // Validar que la configuración existe
        $setting = $this->getById($id);
        if (!$setting) {
            throw new Exception("La configuración no existe");
        }

        $this->db->query("DELETE FROM settings WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Eliminar configuración por clave
     */
    public function deleteByKey($key) {
        $setting = $this->getByKey($key);
        if (!$setting) {
            throw new Exception("La configuración no existe");
        }

        $this->db->query("DELETE FROM settings WHERE `key` = ?", [$key]);

        return true;
    }

    /**
     * Obtener grupos de configuración
     */
    public function getGroups() {
        $sql = "SELECT group_name, COUNT(*) as count 
                FROM settings 
                GROUP BY group_name 
                ORDER BY group_name ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener tipos de configuración
     */
    public function getTypes() {
        $sql = "SELECT type, COUNT(*) as count 
                FROM settings 
                GROUP BY type 
                ORDER BY type ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener estadísticas de configuraciones
     */
    public function getStats() {
        $stats = [];
        
        // Total de configuraciones
        $stats['total_settings'] = $this->db->fetch("SELECT COUNT(*) as total FROM settings")['total'];
        
        // Configuraciones por grupo
        $stats['settings_by_group'] = $this->getGroups();
        
        // Configuraciones por tipo
        $stats['settings_by_type'] = $this->getTypes();
        
        // Configuraciones públicas
        $stats['public_settings'] = $this->db->fetch("SELECT COUNT(*) as total FROM settings WHERE is_public = 1")['total'];
        
        // Configuraciones privadas
        $stats['private_settings'] = $this->db->fetch("SELECT COUNT(*) as total FROM settings WHERE is_public = 0")['total'];
        
        return $stats;
    }

    /**
     * Importar configuraciones
     */
    public function import($configurations) {
        $imported = 0;
        $errors = [];

        foreach ($configurations as $config) {
            try {
                if (isset($config['key']) && isset($config['value'])) {
                    // Verificar si ya existe
                    $existing = $this->getByKey($config['key']);
                    
                    if ($existing) {
                        // Actualizar existente
                        $this->updateByKey($config['key'], $config['value']);
                    } else {
                        // Crear nuevo
                        $this->create($config);
                    }
                    $imported++;
                } else {
                    $errors[] = "Configuración inválida: " . json_encode($config);
                }
            } catch (Exception $e) {
                $errors[] = "Error al importar {$config['key']}: " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Exportar configuraciones
     */
    public function export($filters = []) {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['group_name'])) {
            $whereConditions[] = 'group_name = ?';
            $params[] = $filters['group_name'];
        }
        
        if (isset($filters['is_public'])) {
            $whereConditions[] = 'is_public = ?';
            $params[] = $filters['is_public'] ? 1 : 0;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT `key`, `value`, `type`, `description`, `is_public`, `group_name`, `sort_order` 
                FROM settings 
                {$whereClause}
                ORDER BY group_name ASC, sort_order ASC, `key` ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Convertir valor según su tipo
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true) ?? [];
            default:
                return $value;
        }
    }

    /**
     * Validar datos de configuración
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar clave
        if (!$isUpdate && empty($data['key'])) {
            $errors['key'] = 'La clave es requerida';
        } elseif (strlen($data['key']) > 255) {
            $errors['key'] = 'La clave no puede tener más de 255 caracteres';
        }

        // Validar valor
        if (!isset($data['value'])) {
            $errors['value'] = 'El valor es requerido';
        }

        // Validar tipo
        if (isset($data['type']) && !in_array($data['type'], ['string', 'integer', 'float', 'boolean', 'array', 'json'])) {
            $errors['type'] = 'Tipo de configuración no válido';
        }

        // Validar grupo
        if (isset($data['group_name']) && strlen($data['group_name']) > 100) {
            $errors['group_name'] = 'El nombre del grupo no puede tener más de 100 caracteres';
        }

        return $errors;
    }
}
