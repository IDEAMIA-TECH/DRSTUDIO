<?php
/**
 * Modelo Category - DT Studio
 * Gestión de categorías de productos
 */

require_once __DIR__ . '/../includes/Database.php';

class Category {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las categorías con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE c.name LIKE ? OR c.description LIKE ?";
            $params = ["%{$search}%", "%{$search}%"];
        }
        
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status != 'draft') as product_count,
                       parent.name as parent_name
                FROM {$this->table} c 
                LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                {$whereClause}
                ORDER BY c.sort_order ASC, c.name ASC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $categories = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} c 
                     LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $categories,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener categoría por ID
     */
    public function getById($id) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status != 'draft') as product_count,
                       parent.name as parent_name
                FROM {$this->table} c 
                LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                WHERE c.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener categoría por slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status != 'draft') as product_count,
                       parent.name as parent_name
                FROM {$this->table} c 
                LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                WHERE c.slug = ?";
        
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * Crear nueva categoría
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Generar slug si no se proporciona
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Verificar si el slug ya existe
        if ($this->getBySlug($data['slug'])) {
            throw new Exception("El slug ya está en uso");
        }

        // Preparar datos para inserción
        $fields = ['name', 'slug', 'description', 'parent_id', 'image', 'sort_order', 'is_active'];
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
     * Actualizar categoría
     */
    public function update($id, $data) {
        // Verificar que la categoría existe
        if (!$this->getById($id)) {
            throw new Exception("Categoría no encontrada");
        }

        // Generar slug si se está cambiando el nombre
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Si se está cambiando el slug, verificar que no exista
        if (isset($data['slug'])) {
            $existingCategory = $this->getBySlug($data['slug']);
            if ($existingCategory && $existingCategory['id'] != $id) {
                throw new Exception("El slug ya está en uso");
            }
        }

        // Preparar datos para actualización
        $fields = ['name', 'slug', 'description', 'parent_id', 'image', 'sort_order', 'is_active'];
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
     * Eliminar categoría
     */
    public function delete($id) {
        // Verificar que la categoría existe
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception("Categoría no encontrada");
        }

        // Verificar si tiene productos asociados
        $productCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
            [$id]
        )['count'];

        if ($productCount > 0) {
            throw new Exception("No se puede eliminar una categoría que tiene productos asociados");
        }

        // Verificar si tiene subcategorías
        $subcategoryCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE parent_id = ?",
            [$id]
        )['count'];

        if ($subcategoryCount > 0) {
            throw new Exception("No se puede eliminar una categoría que tiene subcategorías");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Obtener categorías para select (árbol jerárquico)
     */
    public function getForSelect($parentId = null, $level = 0) {
        $sql = "SELECT id, name, parent_id FROM {$this->table} 
                WHERE is_active = 1 AND parent_id " . ($parentId === null ? 'IS NULL' : '= ?') . "
                ORDER BY sort_order ASC, name ASC";
        
        $params = $parentId === null ? [] : [$parentId];
        $categories = $this->db->fetchAll($sql, $params);
        
        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category['id'],
                'name' => str_repeat('— ', $level) . $category['name'],
                'parent_id' => $category['parent_id']
            ];
            
            // Obtener subcategorías recursivamente
            $subcategories = $this->getForSelect($category['id'], $level + 1);
            $result = array_merge($result, $subcategories);
        }
        
        return $result;
    }

    /**
     * Obtener categorías principales (sin padre)
     */
    public function getMainCategories() {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status != 'draft') as product_count
                FROM {$this->table} c 
                WHERE c.parent_id IS NULL AND c.is_active = 1
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener subcategorías de una categoría
     */
    public function getSubcategories($parentId) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status != 'draft') as product_count
                FROM {$this->table} c 
                WHERE c.parent_id = ? AND c.is_active = 1
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->db->fetchAll($sql, [$parentId]);
    }

    /**
     * Obtener estadísticas de categorías
     */
    public function getStats() {
        $stats = [];

        // Total de categorías
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Categorías activas
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1")['count'];

        // Categorías inactivas
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Categorías principales
        $stats['main'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE parent_id IS NULL")['count'];

        // Categorías con productos
        $stats['with_products'] = $this->db->fetch(
            "SELECT COUNT(DISTINCT c.id) as count 
             FROM {$this->table} c 
             INNER JOIN products p ON c.id = p.category_id 
             WHERE p.status != 'draft'"
        )['count'];

        // Top categorías por productos
        $stats['top_by_products'] = $this->db->fetchAll(
            "SELECT c.name, COUNT(p.id) as product_count 
             FROM {$this->table} c 
             LEFT JOIN products p ON c.id = p.category_id AND p.status != 'draft'
             GROUP BY c.id, c.name 
             ORDER BY product_count DESC 
             LIMIT 5"
        );

        return $stats;
    }

    /**
     * Generar slug único
     */
    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->getBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Validar datos de categoría
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

        // Validar slug
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $errors['slug'] = 'El slug solo puede contener letras minúsculas, números y guiones';
            } elseif (strlen($data['slug']) > 255) {
                $errors['slug'] = 'El slug no puede tener más de 255 caracteres';
            }
        }

        // Validar descripción
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La descripción no puede tener más de 1000 caracteres';
        }

        // Validar parent_id
        if (isset($data['parent_id']) && !empty($data['parent_id'])) {
            $parent = $this->getById($data['parent_id']);
            if (!$parent) {
                $errors['parent_id'] = 'La categoría padre no existe';
            } elseif ($parent['parent_id'] !== null) {
                $errors['parent_id'] = 'No se pueden crear subcategorías de subcategorías';
            }
        }

        // Validar sort_order
        if (isset($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'] = 'El orden debe ser un número mayor o igual a 0';
        }

        return $errors;
    }

    /**
     * Duplicar categoría
     */
    public function duplicate($id, $newName) {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception("Categoría no encontrada");
        }

        $data = [
            'name' => $newName,
            'slug' => $this->generateSlug($newName),
            'description' => $category['description'] . ' (Copia)',
            'parent_id' => $category['parent_id'],
            'image' => $category['image'],
            'sort_order' => $category['sort_order'],
            'is_active' => 1
        ];

        return $this->create($data);
    }

    /**
     * Reordenar categorías
     */
    public function reorder($categoryIds) {
        $this->db->beginTransaction();
        
        try {
            foreach ($categoryIds as $index => $categoryId) {
                $this->db->query(
                    "UPDATE {$this->table} SET sort_order = ? WHERE id = ?",
                    [$index + 1, $categoryId]
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
