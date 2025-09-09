<?php
/**
 * Modelo OrderItem - DT Studio
 * Gestión de items de pedidos
 */

require_once __DIR__ . '/../includes/Database.php';

class OrderItem {
    private $db;
    private $table = 'order_items';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los items de un pedido
     */
    public function getByOrder($orderId) {
        $sql = "SELECT oi.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       pv.price as variant_price
                FROM {$this->table} oi 
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE oi.order_id = ? 
                ORDER BY oi.created_at ASC";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }

    /**
     * Obtener item por ID
     */
    public function getById($id) {
        $sql = "SELECT oi.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       o.order_number, c.name as customer_name
                FROM {$this->table} oi 
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                LEFT JOIN orders o ON oi.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE oi.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Crear nuevo item
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['order_id', 'product_id', 'quantity', 'unit_price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Calcular total si no se proporciona
        if (empty($data['total'])) {
            $data['total'] = $data['quantity'] * $data['unit_price'];
        }

        // Preparar datos para inserción
        $fields = ['order_id', 'product_id', 'variant_id', 'quantity', 'unit_price', 'total', 'notes'];
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
        $itemId = $this->db->lastInsertId();

        // Recalcular totales del pedido
        $this->recalculateOrderTotals($data['order_id']);

        return $itemId;
    }

    /**
     * Actualizar item
     */
    public function update($id, $data) {
        // Verificar que el item existe
        $item = $this->getById($id);
        if (!$item) {
            throw new Exception("Item no encontrado");
        }

        // Recalcular total si se actualizan cantidad o precio
        if (isset($data['quantity']) || isset($data['unit_price'])) {
            $quantity = $data['quantity'] ?? $item['quantity'];
            $unitPrice = $data['unit_price'] ?? $item['unit_price'];
            $data['total'] = $quantity * $unitPrice;
        }

        // Preparar datos para actualización
        $fields = ['product_id', 'variant_id', 'quantity', 'unit_price', 'total', 'notes'];
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
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?";

        $this->db->query($sql, $values);

        // Recalcular totales del pedido
        $this->recalculateOrderTotals($item['order_id']);

        return true;
    }

    /**
     * Eliminar item
     */
    public function delete($id) {
        // Verificar que el item existe
        $item = $this->getById($id);
        if (!$item) {
            throw new Exception("Item no encontrado");
        }

        $orderId = $item['order_id'];

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);

        // Recalcular totales del pedido
        $this->recalculateOrderTotals($orderId);

        return true;
    }

    /**
     * Eliminar todos los items de un pedido
     */
    public function deleteByOrder($orderId) {
        $sql = "DELETE FROM {$this->table} WHERE order_id = ?";
        $this->db->query($sql, [$orderId]);
        return true;
    }

    /**
     * Agregar múltiples items
     */
    public function addMultiple($orderId, $items) {
        $this->db->beginTransaction();
        
        try {
            $addedItems = [];
            
            foreach ($items as $itemData) {
                $itemData['order_id'] = $orderId;
                $itemId = $this->create($itemData);
                $addedItems[] = $itemId;
            }
            
            $this->db->commit();
            return $addedItems;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Recalcular totales del pedido
     */
    private function recalculateOrderTotals($orderId) {
        // Obtener subtotal de todos los items
        $subtotal = $this->db->fetch(
            "SELECT SUM(total) as subtotal FROM {$this->table} WHERE order_id = ?",
            [$orderId]
        )['subtotal'] ?? 0;

        // Obtener monto de impuestos del pedido
        $order = $this->db->fetch(
            "SELECT tax_amount FROM orders WHERE id = ?",
            [$orderId]
        );
        
        $taxAmount = $order['tax_amount'] ?? 0;
        $total = $subtotal + $taxAmount;

        // Actualizar totales en el pedido
        $this->db->query(
            "UPDATE orders SET subtotal = ?, total = ?, updated_at = NOW() WHERE id = ?",
            [$subtotal, $total, $orderId]
        );
    }

    /**
     * Obtener estadísticas de items
     */
    public function getStats($orderId = null) {
        $stats = [];

        $whereClause = $orderId ? "WHERE oi.order_id = ?" : "";
        $params = $orderId ? [$orderId] : [];

        // Total de items
        $stats['total_items'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} oi {$whereClause}",
            $params
        )['count'];

        // Valor total de items
        $stats['total_value'] = $this->db->fetch(
            "SELECT SUM(oi.total) as total_value FROM {$this->table} oi {$whereClause}",
            $params
        )['total_value'] ?? 0;

        // Cantidad total de productos
        $stats['total_quantity'] = $this->db->fetch(
            "SELECT SUM(oi.quantity) as total_quantity FROM {$this->table} oi {$whereClause}",
            $params
        )['total_quantity'] ?? 0;

        // Productos más pedidos
        $stats['most_ordered_products'] = $this->db->fetchAll(
            "SELECT p.name as product_name, p.sku, 
                    COUNT(oi.id) as times_ordered, 
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_value
             FROM {$this->table} oi 
             LEFT JOIN products p ON oi.product_id = p.id 
             {$whereClause}
             GROUP BY oi.product_id, p.name, p.sku 
             ORDER BY times_ordered DESC, total_value DESC 
             LIMIT 5",
            $params
        );

        return $stats;
    }

    /**
     * Validar datos de item
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar pedido
        if (empty($data['order_id'])) {
            $errors['order_id'] = 'El pedido es requerido';
        } else {
            $order = $this->db->fetch("SELECT id FROM orders WHERE id = ?", [$data['order_id']]);
            if (!$order) {
                $errors['order_id'] = 'El pedido seleccionado no existe';
            }
        }

        // Validar producto
        if (empty($data['product_id'])) {
            $errors['product_id'] = 'El producto es requerido';
        } else {
            $product = $this->db->fetch("SELECT id FROM products WHERE id = ?", [$data['product_id']]);
            if (!$product) {
                $errors['product_id'] = 'El producto seleccionado no existe';
            }
        }

        // Validar variante (opcional)
        if (!empty($data['variant_id'])) {
            $variant = $this->db->fetch("SELECT id FROM product_variants WHERE id = ?", [$data['variant_id']]);
            if (!$variant) {
                $errors['variant_id'] = 'La variante seleccionada no existe';
            }
        }

        // Validar cantidad
        if (empty($data['quantity'])) {
            $errors['quantity'] = 'La cantidad es requerida';
        } elseif (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors['quantity'] = 'La cantidad debe ser un número mayor a 0';
        }

        // Validar precio unitario
        if (empty($data['unit_price'])) {
            $errors['unit_price'] = 'El precio unitario es requerido';
        } elseif (!is_numeric($data['unit_price']) || $data['unit_price'] < 0) {
            $errors['unit_price'] = 'El precio unitario debe ser un número mayor o igual a 0';
        }

        // Validar total
        if (isset($data['total']) && (!is_numeric($data['total']) || $data['total'] < 0)) {
            $errors['total'] = 'El total debe ser un número mayor o igual a 0';
        }

        return $errors;
    }

    /**
     * Duplicar item
     */
    public function duplicate($id, $newOrderId) {
        $item = $this->getById($id);
        if (!$item) {
            throw new Exception("Item no encontrado");
        }

        $data = [
            'order_id' => $newOrderId,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total' => $item['total'],
            'notes' => $item['notes']
        ];

        return $this->create($data);
    }

    /**
     * Obtener items por producto
     */
    public function getByProduct($productId, $limit = 20) {
        $sql = "SELECT oi.*, 
                       o.order_number, o.status as order_status,
                       c.name as customer_name, c.email as customer_email
                FROM {$this->table} oi 
                LEFT JOIN orders o ON oi.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE oi.product_id = ? 
                ORDER BY oi.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$productId, $limit]);
    }

    /**
     * Obtener items por variante
     */
    public function getByVariant($variantId, $limit = 20) {
        $sql = "SELECT oi.*, 
                       o.order_number, o.status as order_status,
                       c.name as customer_name, c.email as customer_email
                FROM {$this->table} oi 
                LEFT JOIN orders o ON oi.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE oi.variant_id = ? 
                ORDER BY oi.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$variantId, $limit]);
    }

    /**
     * Obtener items por pedido con información completa
     */
    public function getByOrderWithDetails($orderId) {
        $sql = "SELECT oi.*, 
                       p.name as product_name, p.sku as product_sku, p.description as product_description,
                       pv.name as variant_name, pv.sku as variant_sku, pv.price as variant_price,
                       pv.attributes as variant_attributes,
                       pi.url as product_image
                FROM {$this->table} oi 
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ? 
                ORDER BY oi.created_at ASC";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }
}
