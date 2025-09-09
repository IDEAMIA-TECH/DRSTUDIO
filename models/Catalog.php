<?php
/**
 * Modelo Catalog - DT Studio
 * Gestión del catálogo público de productos
 */

require_once __DIR__ . '/../includes/Database.php';

class Catalog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener productos del catálogo público
     */
    public function getProducts($page = 1, $limit = 12, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['p.status = ?'];
        $params = ['active'];
        
        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = $filters['category_id'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filtro por precio mínimo
        if (!empty($filters['min_price'])) {
            $whereConditions[] = 'pv.price >= ?';
            $params[] = $filters['min_price'];
        }
        
        // Filtro por precio máximo
        if (!empty($filters['max_price'])) {
            $whereConditions[] = 'pv.price <= ?';
            $params[] = $filters['max_price'];
        }
        
        // Filtro por material
        if (!empty($filters['material'])) {
            $whereConditions[] = 'pv.attributes LIKE ?';
            $params[] = "%\"material\":\"{$filters['material']}\"%";
        }
        
        // Filtro por color
        if (!empty($filters['color'])) {
            $whereConditions[] = 'pv.attributes LIKE ?';
            $params[] = "%\"color\":\"{$filters['color']}\"%";
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images,
                       GROUP_CONCAT(DISTINCT pv.attributes) as variants
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                {$whereClause}
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $products = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM products p
                     LEFT JOIN product_variants pv ON p.id = pv.product_id
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
     * Obtener producto por slug
     */
    public function getProductBySlug($slug) {
        $sql = "SELECT p.*, 
                       c.name as category_name, c.slug as category_slug,
                       c.description as category_description
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? AND p.status = 'active'";
        
        $product = $this->db->fetch($sql, [$slug]);
        
        if ($product) {
            // Obtener variantes del producto
            $variants = $this->db->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC",
                [$product['id']]
            );
            
            // Obtener imágenes del producto
            $images = $this->db->fetchAll(
                "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, is_primary DESC",
                [$product['id']]
            );
            
            $product['variants'] = $variants;
            $product['images'] = $images;
        }
        
        return $product;
    }

    /**
     * Obtener categorías del catálogo
     */
    public function getCategories($parentId = null) {
        $whereClause = $parentId !== null ? 'WHERE parent_id = ?' : 'WHERE parent_id IS NULL';
        $params = $parentId !== null ? [$parentId] : [];
        
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count
                FROM categories c
                {$whereClause}
                AND c.is_active = 1
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener categoría por slug
     */
    public function getCategoryBySlug($slug) {
        $sql = "SELECT c.*, 
                       parent.name as parent_name, parent.slug as parent_slug
                FROM categories c
                LEFT JOIN categories parent ON c.parent_id = parent.id
                WHERE c.slug = ? AND c.is_active = 1";
        
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * Obtener productos destacados
     */
    public function getFeaturedProducts($limit = 8) {
        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' AND p.is_featured = 1
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY p.updated_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener productos relacionados
     */
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' 
                AND p.id != ? 
                AND p.category_id = ?
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY RAND()
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$productId, $categoryId, $limit]);
    }

    /**
     * Obtener productos más vendidos
     */
    public function getBestSellingProducts($limit = 8) {
        $sql = "SELECT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images,
                       SUM(oi.quantity) as total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN order_items oi ON pv.id = oi.variant_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE p.status = 'active' 
                AND o.status = 'delivered'
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY total_sold DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener productos recientes
     */
    public function getRecentProducts($limit = 8) {
        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active'
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Buscar productos
     */
    public function searchProducts($query, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images,
                       MATCH(p.name, p.description, p.sku) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' 
                AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? 
                     OR c.name LIKE ? OR MATCH(p.name, p.description, p.sku) AGAINST(? IN NATURAL LANGUAGE MODE))
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY relevance DESC, p.name ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        $searchTerm = "%{$query}%";
        $params = [$query, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $query];
        
        $products = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.status = 'active' 
                     AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? 
                          OR c.name LIKE ? OR MATCH(p.name, p.description, p.sku) AGAINST(? IN NATURAL LANGUAGE MODE))";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'query' => $query
        ];
    }

    /**
     * Obtener filtros disponibles
     */
    public function getAvailableFilters($categoryId = null) {
        $filters = [];
        
        // Filtro por categoría
        $whereClause = $categoryId ? 'WHERE c.id = ?' : '';
        $params = $categoryId ? [$categoryId] : [];
        
        // Categorías
        $filters['categories'] = $this->db->fetchAll(
            "SELECT c.id, c.name, c.slug, COUNT(p.id) as product_count
             FROM categories c
             LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
             {$whereClause}
             AND c.is_active = 1
             GROUP BY c.id, c.name, c.slug
             ORDER BY c.name ASC",
            $params
        );
        
        // Rango de precios
        $priceRange = $this->db->fetch(
            "SELECT MIN(pv.price) as min_price, MAX(pv.price) as max_price
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active' {$whereClause}",
            $params
        );
        
        $filters['price_range'] = [
            'min' => $priceRange['min_price'] ?? 0,
            'max' => $priceRange['max_price'] ?? 1000
        ];
        
        // Materiales únicos
        $materials = $this->db->fetchAll(
            "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(pv.attributes, '$.material')) as material
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active' 
             AND pv.attributes IS NOT NULL 
             AND JSON_EXTRACT(pv.attributes, '$.material') IS NOT NULL
             {$whereClause}
             ORDER BY material ASC",
            $params
        );
        
        $filters['materials'] = array_filter(array_column($materials, 'material'));
        
        // Colores únicos
        $colors = $this->db->fetchAll(
            "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(pv.attributes, '$.color')) as color
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active' 
             AND pv.attributes IS NOT NULL 
             AND JSON_EXTRACT(pv.attributes, '$.color') IS NOT NULL
             {$whereClause}
             ORDER BY color ASC",
            $params
        );
        
        $filters['colors'] = array_filter(array_column($colors, 'color'));
        
        return $filters;
    }

    /**
     * Obtener estadísticas del catálogo
     */
    public function getCatalogStats() {
        $stats = [];
        
        // Total de productos activos
        $stats['total_products'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM products WHERE status = 'active'"
        )['total'];
        
        // Total de categorías activas
        $stats['total_categories'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM categories WHERE is_active = 1"
        )['total'];
        
        // Productos por categoría
        $stats['products_by_category'] = $this->db->fetchAll(
            "SELECT c.name, COUNT(p.id) as product_count
             FROM categories c
             LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
             WHERE c.is_active = 1
             GROUP BY c.id, c.name
             ORDER BY product_count DESC
             LIMIT 10"
        );
        
        // Rango de precios
        $priceRange = $this->db->fetch(
            "SELECT MIN(pv.price) as min_price, MAX(pv.price) as max_price
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE p.status = 'active'"
        );
        
        $stats['price_range'] = [
            'min' => $priceRange['min_price'] ?? 0,
            'max' => $priceRange['max_price'] ?? 0
        ];
        
        return $stats;
    }

    /**
     * Obtener productos para sitemap
     */
    public function getProductsForSitemap() {
        $sql = "SELECT p.slug, p.updated_at, c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
                ORDER BY p.updated_at DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener categorías para sitemap
     */
    public function getCategoriesForSitemap() {
        $sql = "SELECT slug, updated_at, parent_id
                FROM categories
                WHERE is_active = 1
                ORDER BY updated_at DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Generar slug único
     */
    public function generateSlug($name, $table = 'products', $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $whereClause = "slug = ?";
            $params = [$slug];
            
            if ($excludeId) {
                $whereClause .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $existing = $this->db->fetch("SELECT id FROM {$table} WHERE {$whereClause}", $params);
            
            if (!$existing) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Validar datos del catálogo
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar slug
        if (!empty($data['slug'])) {
            if (strlen($data['slug']) > 255) {
                $errors['slug'] = 'El slug no puede tener más de 255 caracteres';
            }
        }

        // Validar categoría
        if (!empty($data['category_id'])) {
            $category = $this->db->fetch("SELECT id FROM categories WHERE id = ? AND is_active = 1", [$data['category_id']]);
            if (!$category) {
                $errors['category_id'] = 'La categoría seleccionada no existe o está inactiva';
            }
        }

        return $errors;
    }
}
