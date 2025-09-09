<?php
/**
 * Modelo Inventory - DT Studio
 * Gestión de inventario y stock
 */

require_once __DIR__ . '/../includes/Database.php';

class Inventory {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener stock de un producto
     */
    public function getStock($productId, $variantId = null) {
        if ($variantId) {
            // Stock de variante específica
            $sql = "SELECT pv.id, pv.product_id, pv.name as variant_name, pv.stock, pv.min_stock, pv.max_stock, pv.cost, pv.price,
                           p.name as product_name, p.sku as product_sku, pv.sku as variant_sku
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.id
                    WHERE pv.id = ? AND pv.product_id = ?";
            return $this->db->fetch($sql, [$variantId, $productId]);
        } else {
            // Stock total del producto
            $sql = "SELECT p.id, p.name, p.sku, p.status,
                           SUM(pv.stock) as total_stock,
                           MIN(pv.min_stock) as min_stock,
                           MAX(pv.max_stock) as max_stock,
                           AVG(pv.cost) as avg_cost,
                           AVG(pv.price) as avg_price
                    FROM products p
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE p.id = ?
                    GROUP BY p.id";
            return $this->db->fetch($sql, [$productId]);
        }
    }

    /**
     * Obtener stock de todos los productos
     */
    public function getAllStock($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = $filters['category_id'];
        }
        
        // Filtro por estado
        if (!empty($filters['status'])) {
            $whereConditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        
        // Filtro por stock bajo
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $whereConditions[] = 'pv.stock <= pv.min_stock';
        }
        
        // Filtro por stock cero
        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $whereConditions[] = 'pv.stock = 0';
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(p.name LIKE ? OR p.sku LIKE ? OR pv.name LIKE ? OR pv.sku LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.id, p.name, p.sku, p.status, c.name as category_name,
                       pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                       pv.stock, pv.min_stock, pv.max_stock, pv.cost, pv.price,
                       CASE 
                           WHEN pv.stock = 0 THEN 'out_of_stock'
                           WHEN pv.stock <= pv.min_stock THEN 'low_stock'
                           WHEN pv.stock >= pv.max_stock THEN 'overstock'
                           ELSE 'normal'
                       END as stock_status
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                {$whereClause}
                ORDER BY p.name ASC, pv.name ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        $stock = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM products p
                     LEFT JOIN product_variants pv ON p.id = pv.product_id
                     {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $stock,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Actualizar stock
     */
    public function updateStock($productId, $variantId, $quantity, $type = 'adjustment', $notes = null) {
        // Validar que la variante existe
        $variant = $this->db->fetch(
            "SELECT * FROM product_variants WHERE id = ? AND product_id = ?",
            [$variantId, $productId]
        );
        
        if (!$variant) {
            throw new Exception("La variante del producto no existe");
        }

        // Obtener stock actual
        $currentStock = $variant['stock'];
        $newStock = $currentStock + $quantity;

        // Validar que el stock no sea negativo
        if ($newStock < 0) {
            throw new Exception("El stock no puede ser negativo");
        }

        // Actualizar stock
        $this->db->query(
            "UPDATE product_variants SET stock = ?, updated_at = NOW() WHERE id = ?",
            [$newStock, $variantId]
        );

        // Registrar movimiento de stock
        $this->recordStockMovement($productId, $variantId, $type, $quantity, $currentStock, $newStock, $notes);

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'old_stock' => $currentStock,
            'new_stock' => $newStock,
            'change' => $quantity
        ];
    }

    /**
     * Registrar movimiento de stock
     */
    public function recordStockMovement($productId, $variantId, $type, $quantity, $oldStock, $newStock, $notes = null) {
        $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $productId,
            $variantId,
            $type,
            $quantity,
            $oldStock,
            $newStock,
            $notes,
            1 // Usuario del sistema
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener movimientos de stock
     */
    public function getStockMovements($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por producto
        if (!empty($filters['product_id'])) {
            $whereConditions[] = 'sm.product_id = ?';
            $params[] = $filters['product_id'];
        }
        
        // Filtro por variante
        if (!empty($filters['variant_id'])) {
            $whereConditions[] = 'sm.variant_id = ?';
            $params[] = $filters['variant_id'];
        }
        
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $whereConditions[] = 'sm.type = ?';
            $params[] = $filters['type'];
        }
        
        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(sm.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(sm.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT sm.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                LEFT JOIN users u ON sm.created_by = u.id
                {$whereClause}
                ORDER BY sm.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $movements = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM stock_movements sm {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $movements,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts($limit = 50) {
        $sql = "SELECT p.id, p.name, p.sku, c.name as category_name,
                       pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                       pv.stock, pv.min_stock, pv.max_stock,
                       (pv.min_stock - pv.stock) as shortage
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                WHERE pv.stock <= pv.min_stock AND pv.stock > 0
                ORDER BY shortage DESC, p.name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener productos sin stock
     */
    public function getOutOfStockProducts($limit = 50) {
        $sql = "SELECT p.id, p.name, p.sku, c.name as category_name,
                       pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                       pv.stock, pv.min_stock, pv.max_stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                WHERE pv.stock = 0
                ORDER BY p.name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener productos con sobrestock
     */
    public function getOverstockProducts($limit = 50) {
        $sql = "SELECT p.id, p.name, p.sku, c.name as category_name,
                       pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                       pv.stock, pv.min_stock, pv.max_stock,
                       (pv.stock - pv.max_stock) as excess
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                WHERE pv.stock > pv.max_stock
                ORDER BY excess DESC, p.name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener estadísticas de inventario
     */
    public function getInventoryStats() {
        $stats = [];
        
        // Total de productos
        $stats['total_products'] = $this->db->fetch("SELECT COUNT(*) as total FROM products")['total'];
        
        // Total de variantes
        $stats['total_variants'] = $this->db->fetch("SELECT COUNT(*) as total FROM product_variants")['total'];
        
        // Valor total del inventario
        $stats['total_inventory_value'] = $this->db->fetch(
            "SELECT SUM(stock * cost) as total FROM product_variants"
        )['total'] ?? 0;
        
        // Productos con stock bajo
        $stats['low_stock_count'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM product_variants WHERE stock <= min_stock AND stock > 0"
        )['total'];
        
        // Productos sin stock
        $stats['out_of_stock_count'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM product_variants WHERE stock = 0"
        )['total'];
        
        // Productos con sobrestock
        $stats['overstock_count'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM product_variants WHERE stock > max_stock"
        )['total'];
        
        // Movimientos de stock por tipo
        $stats['movements_by_type'] = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, SUM(quantity) as total_quantity
             FROM stock_movements 
             GROUP BY type 
             ORDER BY count DESC"
        );
        
        // Movimientos de stock por mes
        $stats['movements_by_month'] = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
             FROM stock_movements 
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12"
        );
        
        return $stats;
    }

    /**
     * Obtener historial de stock de un producto
     */
    public function getProductStockHistory($productId, $variantId = null, $limit = 50) {
        $whereConditions = ['sm.product_id = ?'];
        $params = [$productId];
        
        if ($variantId) {
            $whereConditions[] = 'sm.variant_id = ?';
            $params[] = $variantId;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT sm.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                LEFT JOIN users u ON sm.created_by = u.id
                {$whereClause}
                ORDER BY sm.created_at DESC
                LIMIT ?";
        
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Ajustar stock (entrada)
     */
    public function adjustStockIn($productId, $variantId, $quantity, $notes = null) {
        return $this->updateStock($productId, $variantId, abs($quantity), 'adjustment_in', $notes);
    }

    /**
     * Ajustar stock (salida)
     */
    public function adjustStockOut($productId, $variantId, $quantity, $notes = null) {
        return $this->updateStock($productId, $variantId, -abs($quantity), 'adjustment_out', $notes);
    }

    /**
     * Reservar stock
     */
    public function reserveStock($productId, $variantId, $quantity, $notes = null) {
        return $this->updateStock($productId, $variantId, -abs($quantity), 'reservation', $notes);
    }

    /**
     * Liberar stock reservado
     */
    public function releaseStock($productId, $variantId, $quantity, $notes = null) {
        return $this->updateStock($productId, $variantId, abs($quantity), 'release', $notes);
    }

    /**
     * Transferir stock
     */
    public function transferStock($fromVariantId, $toVariantId, $quantity, $notes = null) {
        // Obtener información de las variantes
        $fromVariant = $this->db->fetch("SELECT * FROM product_variants WHERE id = ?", [$fromVariantId]);
        $toVariant = $this->db->fetch("SELECT * FROM product_variants WHERE id = ?", [$toVariantId]);
        
        if (!$fromVariant || !$toVariant) {
            throw new Exception("Una o ambas variantes no existen");
        }

        // Validar que hay suficiente stock
        if ($fromVariant['stock'] < $quantity) {
            throw new Exception("No hay suficiente stock para transferir");
        }

        // Realizar transferencia
        $this->db->beginTransaction();
        
        try {
            // Reducir stock de origen
            $this->updateStock($fromVariant['product_id'], $fromVariantId, -$quantity, 'transfer_out', $notes);
            
            // Aumentar stock de destino
            $this->updateStock($toVariant['product_id'], $toVariantId, $quantity, 'transfer_in', $notes);
            
            $this->db->commit();
            
            return [
                'from_variant_id' => $fromVariantId,
                'to_variant_id' => $toVariantId,
                'quantity' => $quantity,
                'success' => true
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Validar datos de inventario
     */
    public function validateInventoryData($data) {
        $errors = [];

        // Validar ID del producto
        if (empty($data['product_id'])) {
            $errors['product_id'] = 'El ID del producto es requerido';
        }

        // Validar ID de la variante
        if (empty($data['variant_id'])) {
            $errors['variant_id'] = 'El ID de la variante es requerido';
        }

        // Validar cantidad
        if (!isset($data['quantity']) || $data['quantity'] == 0) {
            $errors['quantity'] = 'La cantidad es requerida y debe ser diferente de cero';
        }

        return $errors;
    }
}
