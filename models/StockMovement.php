<?php
/**
 * Modelo StockMovement - DT Studio
 * Gestión de movimientos de stock
 */

require_once __DIR__ . '/../includes/Database.php';

class StockMovement {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear movimiento de stock
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['product_id', 'variant_id', 'type', 'quantity'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar tipo de movimiento
        $validTypes = ['adjustment_in', 'adjustment_out', 'sale', 'purchase', 'return', 'reservation', 'release', 'transfer_in', 'transfer_out', 'damage', 'loss'];
        if (!in_array($data['type'], $validTypes)) {
            throw new Exception("Tipo de movimiento no válido");
        }

        $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, reference_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['product_id'],
            $data['variant_id'],
            $data['type'],
            $data['quantity'],
            $data['old_stock'] ?? 0,
            $data['new_stock'] ?? 0,
            $data['notes'] ?? null,
            $data['reference_id'] ?? null,
            $data['created_by'] ?? 1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener movimiento por ID
     */
    public function getById($id) {
        $sql = "SELECT sm.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Listar movimientos de stock
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
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
        
        // Filtro por usuario
        if (!empty($filters['created_by'])) {
            $whereConditions[] = 'sm.created_by = ?';
            $params[] = $filters['created_by'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(p.name LIKE ? OR p.sku LIKE ? OR pv.name LIKE ? OR pv.sku LIKE ? OR sm.notes LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
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
        $countSql = "SELECT COUNT(*) as total 
                     FROM stock_movements sm
                     LEFT JOIN products p ON sm.product_id = p.id
                     LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                     {$whereClause}";
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
     * Obtener movimientos por tipo
     */
    public function getByType($type, $limit = 50) {
        $sql = "SELECT sm.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.type = ?
                ORDER BY sm.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$type, $limit]);
    }

    /**
     * Obtener movimientos por producto
     */
    public function getByProduct($productId, $variantId = null, $limit = 50) {
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
     * Obtener movimientos por rango de fechas
     */
    public function getByDateRange($dateFrom, $dateTo, $limit = 100) {
        $sql = "SELECT sm.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE DATE(sm.created_at) BETWEEN ? AND ?
                ORDER BY sm.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo, $limit]);
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function getStats($filters = []) {
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $stats = [];
        
        // Total de movimientos
        $stats['total_movements'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM stock_movements {$whereClause}",
            $params
        )['total'];
        
        // Movimientos por tipo
        $stats['movements_by_type'] = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count, SUM(quantity) as total_quantity
             FROM stock_movements {$whereClause}
             GROUP BY type
             ORDER BY count DESC",
            $params
        );
        
        // Movimientos por mes
        $stats['movements_by_month'] = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
             FROM stock_movements {$whereClause}
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12",
            $params
        );
        
        // Movimientos por usuario
        $stats['movements_by_user'] = $this->db->fetchAll(
            "SELECT u.name as user_name, COUNT(sm.id) as count
             FROM stock_movements sm
             LEFT JOIN users u ON sm.created_by = u.id
             {$whereClause}
             GROUP BY sm.created_by, u.name
             ORDER BY count DESC",
            $params
        );
        
        // Productos más movidos
        $stats['most_moved_products'] = $this->db->fetchAll(
            "SELECT p.name as product_name, p.sku, COUNT(sm.id) as movement_count
             FROM stock_movements sm
             LEFT JOIN products p ON sm.product_id = p.id
             {$whereClause}
             GROUP BY sm.product_id, p.name, p.sku
             ORDER BY movement_count DESC
             LIMIT 10",
            $params
        );
        
        return $stats;
    }

    /**
     * Obtener resumen de movimientos por día
     */
    public function getDailySummary($date = null) {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    type,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity,
                    SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_out
                FROM stock_movements 
                WHERE DATE(created_at) = ?
                GROUP BY DATE(created_at), type
                ORDER BY type";
        
        return $this->db->fetchAll($sql, [$date]);
    }

    /**
     * Obtener resumen de movimientos por mes
     */
    public function getMonthlySummary($year = null, $month = null) {
        $year = $year ?: date('Y');
        $month = $month ?: date('m');
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    type,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity,
                    SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_out
                FROM stock_movements 
                WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
                GROUP BY DATE_FORMAT(created_at, '%Y-%m'), type
                ORDER BY type";
        
        return $this->db->fetchAll($sql, [$year, $month]);
    }

    /**
     * Eliminar movimiento de stock
     */
    public function delete($id) {
        // Validar que el movimiento existe
        $movement = $this->getById($id);
        if (!$movement) {
            throw new Exception("El movimiento de stock no existe");
        }

        $this->db->query("DELETE FROM stock_movements WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Obtener tipos de movimiento
     */
    public function getMovementTypes() {
        return [
            'adjustment_in' => 'Ajuste de Entrada',
            'adjustment_out' => 'Ajuste de Salida',
            'sale' => 'Venta',
            'purchase' => 'Compra',
            'return' => 'Devolución',
            'reservation' => 'Reserva',
            'release' => 'Liberación',
            'transfer_in' => 'Transferencia Entrada',
            'transfer_out' => 'Transferencia Salida',
            'damage' => 'Daño',
            'loss' => 'Pérdida'
        ];
    }

    /**
     * Validar datos de movimiento
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar producto
        if (!$isUpdate && empty($data['product_id'])) {
            $errors['product_id'] = 'El ID del producto es requerido';
        }

        // Validar variante
        if (!$isUpdate && empty($data['variant_id'])) {
            $errors['variant_id'] = 'El ID de la variante es requerido';
        }

        // Validar tipo
        if (!$isUpdate && empty($data['type'])) {
            $errors['type'] = 'El tipo de movimiento es requerido';
        } elseif ($data['type'] && !in_array($data['type'], array_keys($this->getMovementTypes()))) {
            $errors['type'] = 'Tipo de movimiento no válido';
        }

        // Validar cantidad
        if (!$isUpdate && (!isset($data['quantity']) || $data['quantity'] == 0)) {
            $errors['quantity'] = 'La cantidad es requerida y debe ser diferente de cero';
        }

        return $errors;
    }
}
