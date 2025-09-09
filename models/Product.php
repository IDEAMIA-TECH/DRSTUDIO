<?php
/**
 * Modelo Product - DT Studio
 * Gestión de productos del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Product {
    private $db;
    private $table = 'products';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los productos con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '', $categoryId = null, $status = null) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($categoryId !== null) {
            $whereConditions[] = "p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($status !== null) {
            $whereConditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       u.name as created_by_name,
                       (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as variant_count,
                       (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as min_price,
                       (SELECT MAX(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as max_price,
                       (SELECT SUM(pv.stock) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as total_stock
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                {$whereClause}
                ORDER BY p.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $products = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN users u ON p.created_by = u.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener producto por ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       u.name as created_by_name,
                       (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as variant_count
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener producto por SKU
     */
    public function getBySku($sku) {
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       u.name as created_by_name
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.sku = ?";
        
        return $this->db->fetch($sql, [$sku]);
    }

    /**
     * Crear nuevo producto
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'category_id', 'created_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Generar SKU si no se proporciona
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['name']);
        }

        // Verificar si el SKU ya existe
        if ($this->getBySku($data['sku'])) {
            throw new Exception("El SKU ya está en uso");
        }

        // Preparar datos para inserción
        $fields = ['name', 'description', 'category_id', 'sku', 'status', 'meta_title', 'meta_description', 'created_by'];
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
     * Actualizar producto
     */
    public function update($id, $data) {
        // Verificar que el producto existe
        if (!$this->getById($id)) {
            throw new Exception("Producto no encontrado");
        }

        // Generar SKU si se está cambiando el nombre
        if (isset($data['name']) && empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['name']);
        }

        // Si se está cambiando el SKU, verificar que no exista
        if (isset($data['sku'])) {
            $existingProduct = $this->getBySku($data['sku']);
            if ($existingProduct && $existingProduct['id'] != $id) {
                throw new Exception("El SKU ya está en uso");
            }
        }

        // Preparar datos para actualización
        $fields = ['name', 'description', 'category_id', 'sku', 'status', 'meta_title', 'meta_description'];
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
     * Eliminar producto
     */
    public function delete($id) {
        // Verificar que el producto existe
        if (!$this->getById($id)) {
            throw new Exception("Producto no encontrado");
        }

        // Verificar si tiene variantes asociadas
        $variantCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM product_variants WHERE product_id = ?",
            [$id]
        )['count'];

        if ($variantCount > 0) {
            throw new Exception("No se puede eliminar un producto que tiene variantes asociadas");
        }

        // Verificar si está en cotizaciones o pedidos
        $quotationCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM quotation_items WHERE product_id = ?",
            [$id]
        )['count'];

        $orderCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?",
            [$id]
        )['count'];

        if ($quotationCount > 0 || $orderCount > 0) {
            throw new Exception("No se puede eliminar un producto que está en cotizaciones o pedidos");
        }

        // Eliminar imágenes asociadas
        $this->db->query("DELETE FROM product_images WHERE product_id = ?", [$id]);

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado del producto
     */
    public function changeStatus($id, $status) {
        $validStatuses = ['active', 'inactive', 'draft'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Estado no válido");
        }

        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$status, $id]);
        return true;
    }

    /**
     * Obtener productos destacados
     */
    public function getFeatured($limit = 10) {
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as min_price,
                       (SELECT url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener productos por categoría
     */
    public function getByCategory($categoryId, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as min_price,
                       (SELECT url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$categoryId, $limit, $offset]);
    }

    /**
     * Buscar productos
     */
    public function search($query, $categoryId = null, $limit = 20) {
        $whereConditions = ["(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)"];
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if ($categoryId !== null) {
            $whereConditions[] = "p.category_id = ?";
            $params[] = $categoryId;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.*, 
                       c.name as category_name,
                       (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as min_price,
                       (SELECT url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause} AND p.status = 'active'
                ORDER BY p.name ASC 
                LIMIT ?";
        
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener estadísticas de productos
     */
    public function getStats() {
        $stats = [];

        // Total de productos
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Productos activos
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'active'")['count'];

        // Productos inactivos
        $stats['inactive'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'inactive'")['count'];

        // Productos en borrador
        $stats['draft'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'draft'")['count'];

        // Productos por categoría
        $stats['by_category'] = $this->db->fetchAll(
            "SELECT c.name as category_name, COUNT(p.id) as product_count 
             FROM categories c 
             LEFT JOIN {$this->table} p ON c.id = p.category_id 
             GROUP BY c.id, c.name 
             ORDER BY product_count DESC"
        );

        // Productos más recientes
        $stats['recent'] = $this->db->fetchAll(
            "SELECT p.name, p.sku, p.status, c.name as category_name, u.name as created_by_name, p.created_at 
             FROM {$this->table} p 
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.created_by = u.id
             ORDER BY p.created_at DESC 
             LIMIT 5"
        );

        return $stats;
    }

    /**
     * Generar SKU único
     */
    private function generateSku($name) {
        $sku = strtoupper(trim($name));
        $sku = preg_replace('/[^A-Z0-9]/', '', $sku);
        $sku = substr($sku, 0, 10); // Máximo 10 caracteres
        
        if (empty($sku)) {
            $sku = 'PROD';
        }
        
        $originalSku = $sku;
        $counter = 1;
        
        while ($this->getBySku($sku)) {
            $sku = $originalSku . $counter;
            $counter++;
        }
        
        return $sku;
    }

    /**
     * Validar datos de producto
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

        // Validar SKU
        if (!empty($data['sku'])) {
            if (!preg_match('/^[A-Z0-9-]+$/', $data['sku'])) {
                $errors['sku'] = 'El SKU solo puede contener letras mayúsculas, números y guiones';
            } elseif (strlen($data['sku']) > 100) {
                $errors['sku'] = 'El SKU no puede tener más de 100 caracteres';
            }
        }

        // Validar descripción
        if (!empty($data['description']) && strlen($data['description']) > 2000) {
            $errors['description'] = 'La descripción no puede tener más de 2000 caracteres';
        }

        // Validar categoría
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'La categoría es requerida';
        } else {
            $category = $this->db->fetch("SELECT id FROM categories WHERE id = ?", [$data['category_id']]);
            if (!$category) {
                $errors['category_id'] = 'La categoría seleccionada no existe';
            }
        }

        // Validar estado
        if (isset($data['status'])) {
            $validStatuses = ['active', 'inactive', 'draft'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'El estado no es válido';
            }
        }

        // Validar meta_title
        if (!empty($data['meta_title']) && strlen($data['meta_title']) > 255) {
            $errors['meta_title'] = 'El título meta no puede tener más de 255 caracteres';
        }

        // Validar meta_description
        if (!empty($data['meta_description']) && strlen($data['meta_description']) > 500) {
            $errors['meta_description'] = 'La descripción meta no puede tener más de 500 caracteres';
        }

        return $errors;
    }

    /**
     * Duplicar producto
     */
    public function duplicate($id, $newName) {
        $product = $this->getById($id);
        if (!$product) {
            throw new Exception("Producto no encontrado");
        }

        $data = [
            'name' => $newName,
            'description' => $product['description'],
            'category_id' => $product['category_id'],
            'sku' => $this->generateSku($newName),
            'status' => 'draft',
            'meta_title' => $product['meta_title'],
            'meta_description' => $product['meta_description'],
            'created_by' => $product['created_by']
        ];

        return $this->create($data);
    }
}
