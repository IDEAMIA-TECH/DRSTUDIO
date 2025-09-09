<?php
/**
 * Modelo Order - DT Studio
 * Gestión de pedidos del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Order {
    private $db;
    private $table = 'orders';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los pedidos con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '', $status = null, $paymentStatus = null, $customerId = null, $userId = null) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($status !== null) {
            $whereConditions[] = "o.status = ?";
            $params[] = $status;
        }
        
        if ($paymentStatus !== null) {
            $whereConditions[] = "o.payment_status = ?";
            $params[] = $paymentStatus;
        }
        
        if ($customerId !== null) {
            $whereConditions[] = "o.customer_id = ?";
            $params[] = $customerId;
        }
        
        if ($userId !== null) {
            $whereConditions[] = "o.created_by = ?";
            $params[] = $userId;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       c.phone as customer_phone, c.address as customer_address,
                       u.name as user_name,
                       q.quotation_number,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                {$whereClause}
                ORDER BY o.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $orders = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} o 
                     LEFT JOIN customers c ON o.customer_id = c.id
                     LEFT JOIN users u ON o.created_by = u.id
                     LEFT JOIN quotations q ON o.quotation_id = q.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $orders,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener pedido por ID
     */
    public function getById($id) {
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       c.phone as customer_phone, c.address as customer_address,
                       u.name as user_name,
                       q.quotation_number
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener pedido por número
     */
    public function getByNumber($orderNumber) {
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       u.name as user_name,
                       q.quotation_number
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.order_number = ?";
        
        return $this->db->fetch($sql, [$orderNumber]);
    }

    /**
     * Crear nuevo pedido
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['customer_id', 'created_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Generar número de pedido si no se proporciona
        if (empty($data['order_number'])) {
            $data['order_number'] = $this->generateOrderNumber();
        }

        // Verificar si el número ya existe
        if ($this->getByNumber($data['order_number'])) {
            throw new Exception("El número de pedido ya existe");
        }

        // Calcular totales si no se proporcionan
        if (empty($data['subtotal'])) {
            $data['subtotal'] = 0.00;
        }
        if (empty($data['tax_amount'])) {
            $data['tax_amount'] = 0.00;
        }
        if (empty($data['total'])) {
            $data['total'] = $data['subtotal'] + $data['tax_amount'];
        }

        // Preparar datos para inserción
        $fields = ['quotation_id', 'customer_id', 'created_by', 'order_number', 'status', 'payment_status', 'subtotal', 'tax_amount', 'total', 'shipping_address', 'billing_address', 'notes', 'delivery_date'];
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
     * Actualizar pedido
     */
    public function update($id, $data) {
        // Verificar que el pedido existe
        if (!$this->getById($id)) {
            throw new Exception("Pedido no encontrado");
        }

        // Si se está cambiando el número, verificar que no exista
        if (isset($data['order_number'])) {
            $existingOrder = $this->getByNumber($data['order_number']);
            if ($existingOrder && $existingOrder['id'] != $id) {
                throw new Exception("El número de pedido ya existe");
            }
        }

        // Recalcular totales si se actualizan los montos
        if (isset($data['subtotal']) || isset($data['tax_amount'])) {
            $order = $this->getById($id);
            $subtotal = $data['subtotal'] ?? $order['subtotal'];
            $taxAmount = $data['tax_amount'] ?? $order['tax_amount'];
            
            $data['total'] = $subtotal + $taxAmount;
        }

        // Preparar datos para actualización
        $fields = ['order_number', 'status', 'payment_status', 'subtotal', 'tax_amount', 'total', 'shipping_address', 'billing_address', 'notes', 'delivery_date'];
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
     * Eliminar pedido
     */
    public function delete($id) {
        // Verificar que el pedido existe
        if (!$this->getById($id)) {
            throw new Exception("Pedido no encontrado");
        }

        // Verificar si tiene pagos asociados
        $paymentCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM payments WHERE order_id = ?",
            [$id]
        )['count'];

        if ($paymentCount > 0) {
            throw new Exception("No se puede eliminar un pedido que tiene pagos asociados");
        }

        // Eliminar items del pedido
        $this->db->query("DELETE FROM order_items WHERE order_id = ?", [$id]);

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado del pedido
     */
    public function changeStatus($id, $status) {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Estado no válido");
        }

        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$status, $id]);
        return true;
    }

    /**
     * Cambiar estado de pago
     */
    public function changePaymentStatus($id, $paymentStatus) {
        $validStatuses = ['pending', 'paid', 'partial', 'refunded', 'failed'];
        if (!in_array($paymentStatus, $validStatuses)) {
            throw new Exception("Estado de pago no válido");
        }

        $sql = "UPDATE {$this->table} SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$paymentStatus, $id]);
        return true;
    }

    /**
     * Obtener pedidos por cliente
     */
    public function getByCustomer($customerId, $limit = 20) {
        $sql = "SELECT o.*, 
                       u.name as user_name,
                       q.quotation_number,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                FROM {$this->table} o 
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.customer_id = ? 
                ORDER BY o.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$customerId, $limit]);
    }

    /**
     * Obtener pedidos por usuario
     */
    public function getByUser($userId, $limit = 20) {
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email,
                       q.quotation_number,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.created_by = ? 
                ORDER BY o.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Obtener pedidos pendientes
     */
    public function getPending() {
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email,
                       u.name as user_name,
                       q.quotation_number
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.status IN ('pending', 'confirmed', 'processing') 
                ORDER BY o.created_at ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener pedidos por entregar
     */
    public function getToDeliver() {
        $sql = "SELECT o.*, 
                       c.name as customer_name, c.email as customer_email,
                       u.name as user_name,
                       q.quotation_number
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN quotations q ON o.quotation_id = q.id
                WHERE o.status IN ('confirmed', 'processing', 'shipped') 
                AND (o.delivery_date IS NULL OR o.delivery_date >= CURDATE())
                ORDER BY o.delivery_date ASC, o.created_at ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener estadísticas de pedidos
     */
    public function getStats() {
        $stats = [];

        // Total de pedidos
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Pedidos por estado
        $stats['by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table} 
             GROUP BY status 
             ORDER BY count DESC"
        );

        // Pedidos por estado de pago
        $stats['by_payment_status'] = $this->db->fetchAll(
            "SELECT payment_status, COUNT(*) as count 
             FROM {$this->table} 
             GROUP BY payment_status 
             ORDER BY count DESC"
        );

        // Pedidos este mes
        $stats['this_month'] = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM {$this->table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        )['count'];

        // Valor total de pedidos
        $stats['total_value'] = $this->db->fetch(
            "SELECT SUM(total) as total_value FROM {$this->table} WHERE status != 'cancelled'"
        )['total_value'] ?? 0;

        // Pedidos más valiosos
        $stats['most_valuable'] = $this->db->fetchAll(
            "SELECT o.order_number, c.name as customer_name, o.total, o.created_at 
             FROM {$this->table} o 
             LEFT JOIN customers c ON o.customer_id = c.id 
             WHERE o.status != 'cancelled' 
             ORDER BY o.total DESC 
             LIMIT 5"
        );

        // Pedidos pendientes
        $stats['pending'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status IN ('pending', 'confirmed', 'processing')"
        )['count'];

        // Pedidos entregados
        $stats['delivered'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'delivered'"
        )['count'];

        // Tiempo promedio de entrega
        $avgDelivery = $this->db->fetch(
            "SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
             FROM {$this->table} 
             WHERE status = 'delivered' AND updated_at IS NOT NULL"
        )['avg_days'] ?? 0;

        $stats['avg_delivery_days'] = round($avgDelivery, 1);

        return $stats;
    }

    /**
     * Generar número de pedido único
     */
    private function generateOrderNumber() {
        $prefix = 'PED';
        $year = date('Y');
        $month = date('m');
        
        // Obtener el último número del mes
        $lastNumber = $this->db->fetch(
            "SELECT order_number FROM {$this->table} 
             WHERE order_number LIKE ? 
             ORDER BY order_number DESC 
             LIMIT 1",
            ["{$prefix}-{$year}{$month}%"]
        );
        
        if ($lastNumber) {
            $lastNum = (int)substr($lastNumber['order_number'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s%s%04d", $prefix, $year, $month, $newNum);
    }

    /**
     * Validar datos de pedido
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar cliente
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = 'El cliente es requerido';
        } else {
            $customer = $this->db->fetch("SELECT id FROM customers WHERE id = ?", [$data['customer_id']]);
            if (!$customer) {
                $errors['customer_id'] = 'El cliente seleccionado no existe';
            }
        }

        // Validar usuario creador
        if (empty($data['created_by'])) {
            $errors['created_by'] = 'El usuario creador es requerido';
        } else {
            $user = $this->db->fetch("SELECT id FROM users WHERE id = ?", [$data['created_by']]);
            if (!$user) {
                $errors['created_by'] = 'El usuario seleccionado no existe';
            }
        }

        // Validar número de pedido
        if (!empty($data['order_number'])) {
            if (strlen($data['order_number']) > 50) {
                $errors['order_number'] = 'El número de pedido no puede tener más de 50 caracteres';
            }
        }

        // Validar estado
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'El estado no es válido';
            }
        }

        // Validar estado de pago
        if (isset($data['payment_status'])) {
            $validStatuses = ['pending', 'paid', 'partial', 'refunded', 'failed'];
            if (!in_array($data['payment_status'], $validStatuses)) {
                $errors['payment_status'] = 'El estado de pago no es válido';
            }
        }

        // Validar montos
        if (isset($data['subtotal']) && (!is_numeric($data['subtotal']) || $data['subtotal'] < 0)) {
            $errors['subtotal'] = 'El subtotal debe ser un número mayor o igual a 0';
        }

        if (isset($data['tax_amount']) && (!is_numeric($data['tax_amount']) || $data['tax_amount'] < 0)) {
            $errors['tax_amount'] = 'El monto de impuestos debe ser un número mayor o igual a 0';
        }

        // Validar fecha de entrega
        if (!empty($data['delivery_date'])) {
            if (!strtotime($data['delivery_date'])) {
                $errors['delivery_date'] = 'La fecha de entrega no es válida';
            }
        }

        return $errors;
    }

    /**
     * Duplicar pedido
     */
    public function duplicate($id, $newNumber = null) {
        $order = $this->getById($id);
        if (!$order) {
            throw new Exception("Pedido no encontrado");
        }

        $data = [
            'quotation_id' => $order['quotation_id'],
            'customer_id' => $order['customer_id'],
            'created_by' => $order['created_by'],
            'order_number' => $newNumber ?: $this->generateOrderNumber(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $order['subtotal'],
            'tax_amount' => $order['tax_amount'],
            'total' => $order['total'],
            'shipping_address' => $order['shipping_address'],
            'billing_address' => $order['billing_address'],
            'notes' => $order['notes'] . ' (Copia)',
            'delivery_date' => $order['delivery_date']
        ];

        return $this->create($data);
    }

    /**
     * Obtener historial del pedido
     */
    public function getHistory($id) {
        $history = [];

        // Items del pedido
        $items = $this->db->fetchAll(
            "SELECT oi.*, 
                    p.name as product_name, p.sku as product_sku,
                    pv.name as variant_name, pv.sku as variant_sku
             FROM order_items oi 
             LEFT JOIN products p ON oi.product_id = p.id
             LEFT JOIN product_variants pv ON oi.variant_id = pv.id
             WHERE oi.order_id = ? 
             ORDER BY oi.created_at ASC",
            [$id]
        );

        // Pagos del pedido
        $payments = $this->db->fetchAll(
            "SELECT p.*, u.name as created_by_name 
             FROM payments p 
             LEFT JOIN users u ON p.created_by = u.id 
             WHERE p.order_id = ? 
             ORDER BY p.created_at DESC",
            [$id]
        );

        $history['items'] = $items;
        $history['payments'] = $payments;

        return $history;
    }

    /**
     * Obtener pedidos para select
     */
    public function getForSelect() {
        $sql = "SELECT id, order_number, customer_id, total, status FROM {$this->table} 
                WHERE status NOT IN ('cancelled', 'returned') 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
}
