<?php
/**
 * Modelo QuotationItem - DT Studio
 * Gestión de items de cotizaciones
 */

require_once __DIR__ . '/../includes/Database.php';

class QuotationItem {
    private $db;
    private $table = 'quotation_items';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los items de una cotización
     */
    public function getByQuotation($quotationId) {
        $sql = "SELECT qi.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       pv.price as variant_price
                FROM {$this->table} qi 
                LEFT JOIN products p ON qi.product_id = p.id
                LEFT JOIN product_variants pv ON qi.variant_id = pv.id
                WHERE qi.quotation_id = ? 
                ORDER BY qi.created_at ASC";
        
        return $this->db->fetchAll($sql, [$quotationId]);
    }

    /**
     * Obtener item por ID
     */
    public function getById($id) {
        $sql = "SELECT qi.*, 
                       p.name as product_name, p.sku as product_sku,
                       pv.name as variant_name, pv.sku as variant_sku,
                       q.quotation_number, c.name as customer_name
                FROM {$this->table} qi 
                LEFT JOIN products p ON qi.product_id = p.id
                LEFT JOIN product_variants pv ON qi.variant_id = pv.id
                LEFT JOIN quotations q ON qi.quotation_id = q.id
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE qi.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Crear nuevo item
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['quotation_id', 'product_id', 'quantity', 'unit_price'];
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
        $fields = ['quotation_id', 'product_id', 'variant_id', 'quantity', 'unit_price', 'total', 'notes'];
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

        // Recalcular totales de la cotización
        $this->recalculateQuotationTotals($data['quotation_id']);

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

        // Recalcular totales de la cotización
        $this->recalculateQuotationTotals($item['quotation_id']);

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

        $quotationId = $item['quotation_id'];

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);

        // Recalcular totales de la cotización
        $this->recalculateQuotationTotals($quotationId);

        return true;
    }

    /**
     * Eliminar todos los items de una cotización
     */
    public function deleteByQuotation($quotationId) {
        $sql = "DELETE FROM {$this->table} WHERE quotation_id = ?";
        $this->db->query($sql, [$quotationId]);
        return true;
    }

    /**
     * Agregar múltiples items
     */
    public function addMultiple($quotationId, $items) {
        $this->db->beginTransaction();
        
        try {
            $addedItems = [];
            
            foreach ($items as $itemData) {
                $itemData['quotation_id'] = $quotationId;
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
     * Recalcular totales de la cotización
     */
    private function recalculateQuotationTotals($quotationId) {
        // Obtener subtotal de todos los items
        $subtotal = $this->db->fetch(
            "SELECT SUM(total) as subtotal FROM {$this->table} WHERE quotation_id = ?",
            [$quotationId]
        )['subtotal'] ?? 0;

        // Obtener tasa de impuestos de la cotización
        $quotation = $this->db->fetch(
            "SELECT tax_rate FROM quotations WHERE id = ?",
            [$quotationId]
        );
        
        $taxRate = $quotation['tax_rate'] ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        // Actualizar totales en la cotización
        $this->db->query(
            "UPDATE quotations SET subtotal = ?, tax_amount = ?, total = ?, updated_at = NOW() WHERE id = ?",
            [$subtotal, $taxAmount, $total, $quotationId]
        );
    }

    /**
     * Obtener estadísticas de items
     */
    public function getStats($quotationId = null) {
        $stats = [];

        $whereClause = $quotationId ? "WHERE qi.quotation_id = ?" : "";
        $params = $quotationId ? [$quotationId] : [];

        // Total de items
        $stats['total_items'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} qi {$whereClause}",
            $params
        )['count'];

        // Valor total de items
        $stats['total_value'] = $this->db->fetch(
            "SELECT SUM(qi.total) as total_value FROM {$this->table} qi {$whereClause}",
            $params
        )['total_value'] ?? 0;

        // Cantidad total de productos
        $stats['total_quantity'] = $this->db->fetch(
            "SELECT SUM(qi.quantity) as total_quantity FROM {$this->table} qi {$whereClause}",
            $params
        )['total_quantity'] ?? 0;

        // Productos más cotizados
        $stats['most_quoted_products'] = $this->db->fetchAll(
            "SELECT p.name as product_name, p.sku, 
                    COUNT(qi.id) as times_quoted, 
                    SUM(qi.quantity) as total_quantity,
                    SUM(qi.total) as total_value
             FROM {$this->table} qi 
             LEFT JOIN products p ON qi.product_id = p.id 
             {$whereClause}
             GROUP BY qi.product_id, p.name, p.sku 
             ORDER BY times_quoted DESC, total_value DESC 
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

        // Validar cotización
        if (empty($data['quotation_id'])) {
            $errors['quotation_id'] = 'La cotización es requerida';
        } else {
            $quotation = $this->db->fetch("SELECT id FROM quotations WHERE id = ?", [$data['quotation_id']]);
            if (!$quotation) {
                $errors['quotation_id'] = 'La cotización seleccionada no existe';
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
    public function duplicate($id, $newQuotationId) {
        $item = $this->getById($id);
        if (!$item) {
            throw new Exception("Item no encontrado");
        }

        $data = [
            'quotation_id' => $newQuotationId,
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
        $sql = "SELECT qi.*, 
                       q.quotation_number, q.status as quotation_status,
                       c.name as customer_name, c.email as customer_email
                FROM {$this->table} qi 
                LEFT JOIN quotations q ON qi.quotation_id = q.id
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE qi.product_id = ? 
                ORDER BY qi.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$productId, $limit]);
    }

    /**
     * Obtener items por variante
     */
    public function getByVariant($variantId, $limit = 20) {
        $sql = "SELECT qi.*, 
                       q.quotation_number, q.status as quotation_status,
                       c.name as customer_name, c.email as customer_email
                FROM {$this->table} qi 
                LEFT JOIN quotations q ON qi.quotation_id = q.id
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE qi.variant_id = ? 
                ORDER BY qi.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$variantId, $limit]);
    }
}
