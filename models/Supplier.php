<?php
/**
 * Modelo Supplier - DT Studio
 * Gestión de proveedores
 */

require_once __DIR__ . '/../includes/Database.php';

class Supplier {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear proveedor
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar email único
        $existing = $this->db->fetch("SELECT id FROM suppliers WHERE email = ?", [$data['email']]);
        if ($existing) {
            throw new Exception("Ya existe un proveedor con ese email");
        }

        $sql = "INSERT INTO suppliers (name, email, phone, address, city, state, country, postal_code, 
                contact_person, website, tax_id, payment_terms, notes, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['postal_code'] ?? null,
            $data['contact_person'] ?? null,
            $data['website'] ?? null,
            $data['tax_id'] ?? null,
            $data['payment_terms'] ?? null,
            $data['notes'] ?? null,
            $data['is_active'] ? 1 : 1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener proveedor por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM suppliers WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener proveedor por email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM suppliers WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Listar proveedores
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por estado activo
        if (isset($filters['is_active'])) {
            $whereConditions[] = 'is_active = ?';
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        
        // Filtro por país
        if (!empty($filters['country'])) {
            $whereConditions[] = 'country = ?';
            $params[] = $filters['country'];
        }
        
        // Filtro por ciudad
        if (!empty($filters['city'])) {
            $whereConditions[] = 'city = ?';
            $params[] = $filters['city'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(name LIKE ? OR email LIKE ? OR contact_person LIKE ? OR phone LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM suppliers 
                {$whereClause}
                ORDER BY name ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        $suppliers = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM suppliers {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $suppliers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener proveedores activos
     */
    public function getActive() {
        $sql = "SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Actualizar proveedor
     */
    public function update($id, $data) {
        // Validar que el proveedor existe
        $supplier = $this->getById($id);
        if (!$supplier) {
            throw new Exception("El proveedor no existe");
        }

        // Validar email único (si se está cambiando)
        if (isset($data['email']) && $data['email'] !== $supplier['email']) {
            $existing = $this->db->fetch("SELECT id FROM suppliers WHERE email = ? AND id != ?", [$data['email'], $id]);
            if ($existing) {
                throw new Exception("Ya existe un proveedor con ese email");
            }
        }

        $allowedFields = ['name', 'email', 'phone', 'address', 'city', 'state', 'country', 'postal_code', 
                         'contact_person', 'website', 'tax_id', 'payment_terms', 'notes', 'is_active'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $field === 'is_active' ? ($data[$field] ? 1 : 0) : $data[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE suppliers SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * Eliminar proveedor
     */
    public function delete($id) {
        // Validar que el proveedor existe
        $supplier = $this->getById($id);
        if (!$supplier) {
            throw new Exception("El proveedor no existe");
        }

        // Verificar si tiene productos asociados
        $products = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE supplier_id = ?", [$id]);
        if ($products['count'] > 0) {
            throw new Exception("No se puede eliminar el proveedor porque tiene productos asociados");
        }

        $this->db->query("DELETE FROM suppliers WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Activar/desactivar proveedor
     */
    public function toggleStatus($id) {
        $supplier = $this->getById($id);
        if (!$supplier) {
            throw new Exception("El proveedor no existe");
        }

        $newStatus = $supplier['is_active'] ? 0 : 1;
        
        $this->db->query(
            "UPDATE suppliers SET is_active = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $id]
        );

        return $newStatus;
    }

    /**
     * Obtener productos del proveedor
     */
    public function getProducts($supplierId, $limit = 50) {
        $sql = "SELECT p.*, c.name as category_name, pv.name as variant_name, pv.stock, pv.cost, pv.price
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                WHERE p.supplier_id = ?
                ORDER BY p.name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$supplierId, $limit]);
    }

    /**
     * Obtener estadísticas del proveedor
     */
    public function getSupplierStats($supplierId) {
        $stats = [];
        
        // Total de productos
        $stats['total_products'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM products WHERE supplier_id = ?",
            [$supplierId]
        )['total'];
        
        // Valor total del inventario
        $stats['total_inventory_value'] = $this->db->fetch(
            "SELECT SUM(pv.stock * pv.cost) as total 
             FROM products p
             LEFT JOIN product_variants pv ON p.id = pv.product_id
             WHERE p.supplier_id = ?",
            [$supplierId]
        )['total'] ?? 0;
        
        // Productos con stock bajo
        $stats['low_stock_products'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM products p
             LEFT JOIN product_variants pv ON p.id = pv.product_id
             WHERE p.supplier_id = ? AND pv.stock <= pv.min_stock",
            [$supplierId]
        )['total'];
        
        // Productos sin stock
        $stats['out_of_stock_products'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM products p
             LEFT JOIN product_variants pv ON p.id = pv.product_id
             WHERE p.supplier_id = ? AND pv.stock = 0",
            [$supplierId]
        )['total'];
        
        return $stats;
    }

    /**
     * Obtener estadísticas generales de proveedores
     */
    public function getStats() {
        $stats = [];
        
        // Total de proveedores
        $stats['total_suppliers'] = $this->db->fetch("SELECT COUNT(*) as total FROM suppliers")['total'];
        
        // Proveedores activos
        $stats['active_suppliers'] = $this->db->fetch("SELECT COUNT(*) as total FROM suppliers WHERE is_active = 1")['total'];
        
        // Proveedores inactivos
        $stats['inactive_suppliers'] = $this->db->fetch("SELECT COUNT(*) as total FROM suppliers WHERE is_active = 0")['total'];
        
        // Proveedores por país
        $stats['suppliers_by_country'] = $this->db->fetchAll(
            "SELECT country, COUNT(*) as count 
             FROM suppliers 
             WHERE country IS NOT NULL 
             GROUP BY country 
             ORDER BY count DESC"
        );
        
        // Proveedores por ciudad
        $stats['suppliers_by_city'] = $this->db->fetchAll(
            "SELECT city, country, COUNT(*) as count 
             FROM suppliers 
             WHERE city IS NOT NULL 
             GROUP BY city, country 
             ORDER BY count DESC
             LIMIT 10"
        );
        
        return $stats;
    }

    /**
     * Obtener países de proveedores
     */
    public function getCountries() {
        $sql = "SELECT DISTINCT country FROM suppliers WHERE country IS NOT NULL ORDER BY country ASC";
        $countries = $this->db->fetchAll($sql);
        
        return array_column($countries, 'country');
    }

    /**
     * Obtener ciudades de proveedores
     */
    public function getCities($country = null) {
        $whereConditions = ['city IS NOT NULL'];
        $params = [];
        
        if ($country) {
            $whereConditions[] = 'country = ?';
            $params[] = $country;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT DISTINCT city, country 
                FROM suppliers 
                {$whereClause}
                ORDER BY country ASC, city ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Buscar proveedores
     */
    public function search($query, $limit = 20) {
        $sql = "SELECT * FROM suppliers 
                WHERE (name LIKE ? OR email LIKE ? OR contact_person LIKE ? OR phone LIKE ?)
                AND is_active = 1
                ORDER BY name ASC
                LIMIT ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
    }

    /**
     * Validar datos de proveedor
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (!$isUpdate && empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar email
        if (!$isUpdate && empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email debe ser válido';
        }

        // Validar teléfono
        if (!empty($data['phone']) && strlen($data['phone']) > 20) {
            $errors['phone'] = 'El teléfono no puede tener más de 20 caracteres';
        }

        // Validar sitio web
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = 'El sitio web debe ser una URL válida';
        }

        // Validar términos de pago
        if (!empty($data['payment_terms']) && strlen($data['payment_terms']) > 100) {
            $errors['payment_terms'] = 'Los términos de pago no pueden tener más de 100 caracteres';
        }

        return $errors;
    }
}
