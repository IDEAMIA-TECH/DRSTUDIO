<?php
/**
 * Modelo Quotation - DT Studio
 * Gestión de cotizaciones del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Quotation {
    private $db;
    private $table = 'quotations';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las cotizaciones con paginación
     */
    public function getAll($page = 1, $limit = 10, $search = '', $status = null, $customerId = null, $userId = null) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(q.quotation_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($status !== null) {
            $whereConditions[] = "q.status = ?";
            $params[] = $status;
        }
        
        if ($customerId !== null) {
            $whereConditions[] = "q.customer_id = ?";
            $params[] = $customerId;
        }
        
        if ($userId !== null) {
            $whereConditions[] = "q.user_id = ?";
            $params[] = $userId;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       u.name as user_name,
                       (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) as item_count
                FROM {$this->table} q 
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u ON q.user_id = u.id
                {$whereClause}
                ORDER BY q.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $quotations = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} q 
                     LEFT JOIN customers c ON q.customer_id = c.id
                     LEFT JOIN users u ON q.user_id = u.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $quotations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener cotización por ID
     */
    public function getById($id) {
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       c.phone as customer_phone, c.address as customer_address,
                       u.name as user_name,
                       (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) as item_count
                FROM {$this->table} q 
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u ON q.user_id = u.id
                WHERE q.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener cotización por número
     */
    public function getByNumber($quotationNumber) {
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email, c.company as customer_company,
                       u.name as user_name
                FROM {$this->table} q 
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u ON q.user_id = u.id
                WHERE q.quotation_number = ?";
        
        return $this->db->fetch($sql, [$quotationNumber]);
    }

    /**
     * Crear nueva cotización
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['customer_id', 'user_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Generar número de cotización si no se proporciona
        if (empty($data['quotation_number'])) {
            $data['quotation_number'] = $this->generateQuotationNumber();
        }

        // Verificar si el número ya existe
        if ($this->getByNumber($data['quotation_number'])) {
            throw new Exception("El número de cotización ya existe");
        }

        // Calcular totales si no se proporcionan
        if (empty($data['subtotal'])) {
            $data['subtotal'] = 0.00;
        }
        if (empty($data['tax_rate'])) {
            $data['tax_rate'] = 0.00;
        }
        if (empty($data['tax_amount'])) {
            $data['tax_amount'] = $data['subtotal'] * ($data['tax_rate'] / 100);
        }
        if (empty($data['total'])) {
            $data['total'] = $data['subtotal'] + $data['tax_amount'];
        }

        // Preparar datos para inserción
        $fields = ['customer_id', 'user_id', 'quotation_number', 'status', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'valid_until', 'notes'];
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
     * Actualizar cotización
     */
    public function update($id, $data) {
        // Verificar que la cotización existe
        if (!$this->getById($id)) {
            throw new Exception("Cotización no encontrada");
        }

        // Si se está cambiando el número, verificar que no exista
        if (isset($data['quotation_number'])) {
            $existingQuotation = $this->getByNumber($data['quotation_number']);
            if ($existingQuotation && $existingQuotation['id'] != $id) {
                throw new Exception("El número de cotización ya existe");
            }
        }

        // Recalcular totales si se actualizan los montos
        if (isset($data['subtotal']) || isset($data['tax_rate'])) {
            $quotation = $this->getById($id);
            $subtotal = $data['subtotal'] ?? $quotation['subtotal'];
            $taxRate = $data['tax_rate'] ?? $quotation['tax_rate'];
            
            $data['tax_amount'] = $subtotal * ($taxRate / 100);
            $data['total'] = $subtotal + $data['tax_amount'];
        }

        // Preparar datos para actualización
        $fields = ['quotation_number', 'status', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'valid_until', 'notes'];
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
     * Eliminar cotización
     */
    public function delete($id) {
        // Verificar que la cotización existe
        if (!$this->getById($id)) {
            throw new Exception("Cotización no encontrada");
        }

        // Verificar si está convertida a pedido
        $orderCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM orders WHERE quotation_id = ?",
            [$id]
        )['count'];

        if ($orderCount > 0) {
            throw new Exception("No se puede eliminar una cotización que ya fue convertida a pedido");
        }

        // Eliminar items de la cotización
        $this->db->query("DELETE FROM quotation_items WHERE quotation_id = ?", [$id]);

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado de la cotización
     */
    public function changeStatus($id, $status) {
        $validStatuses = ['draft', 'sent', 'reviewed', 'approved', 'rejected', 'converted'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Estado no válido");
        }

        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$status, $id]);
        return true;
    }

    /**
     * Convertir cotización a pedido
     */
    public function convertToOrder($id, $orderNumber) {
        $quotation = $this->getById($id);
        if (!$quotation) {
            throw new Exception("Cotización no encontrada");
        }

        if ($quotation['status'] !== 'approved') {
            throw new Exception("Solo se pueden convertir cotizaciones aprobadas");
        }

        $this->db->beginTransaction();
        
        try {
            // Crear pedido
            $orderData = [
                'quotation_id' => $id,
                'customer_id' => $quotation['customer_id'],
                'order_number' => $orderNumber,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $quotation['subtotal'],
                'tax_amount' => $quotation['tax_amount'],
                'total' => $quotation['total']
            ];

            $orderSql = "INSERT INTO orders (quotation_id, customer_id, order_number, status, payment_status, subtotal, tax_amount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($orderSql, [
                $orderData['quotation_id'],
                $orderData['customer_id'],
                $orderData['order_number'],
                $orderData['status'],
                $orderData['payment_status'],
                $orderData['subtotal'],
                $orderData['tax_amount'],
                $orderData['total']
            ]);

            $orderId = $this->db->lastInsertId();

            // Copiar items de cotización a pedido
            $items = $this->db->fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ?", [$id]);
            foreach ($items as $item) {
                $itemSql = "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)";
                $this->db->query($itemSql, [
                    $orderId,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total']
                ]);
            }

            // Cambiar estado de cotización a convertida
            $this->changeStatus($id, 'converted');

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtener cotizaciones por cliente
     */
    public function getByCustomer($customerId, $limit = 20) {
        $sql = "SELECT q.*, 
                       u.name as user_name,
                       (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) as item_count
                FROM {$this->table} q 
                LEFT JOIN users u ON q.user_id = u.id
                WHERE q.customer_id = ? 
                ORDER BY q.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$customerId, $limit]);
    }

    /**
     * Obtener cotizaciones por usuario
     */
    public function getByUser($userId, $limit = 20) {
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email,
                       (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) as item_count
                FROM {$this->table} q 
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE q.user_id = ? 
                ORDER BY q.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Obtener cotizaciones vencidas
     */
    public function getExpired() {
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email,
                       u.name as user_name
                FROM {$this->table} q 
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u ON q.user_id = u.id
                WHERE q.valid_until < CURDATE() AND q.status IN ('sent', 'reviewed') 
                ORDER BY q.valid_until ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener estadísticas de cotizaciones
     */
    public function getStats() {
        $stats = [];

        // Total de cotizaciones
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Cotizaciones por estado
        $stats['by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table} 
             GROUP BY status 
             ORDER BY count DESC"
        );

        // Cotizaciones este mes
        $stats['this_month'] = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM {$this->table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        )['count'];

        // Valor total de cotizaciones
        $stats['total_value'] = $this->db->fetch(
            "SELECT SUM(total) as total_value FROM {$this->table} WHERE status != 'rejected'"
        )['total_value'] ?? 0;

        // Cotizaciones más valiosas
        $stats['most_valuable'] = $this->db->fetchAll(
            "SELECT q.quotation_number, c.name as customer_name, q.total, q.created_at 
             FROM {$this->table} q 
             LEFT JOIN customers c ON q.customer_id = c.id 
             WHERE q.status != 'rejected' 
             ORDER BY q.total DESC 
             LIMIT 5"
        );

        // Tasa de conversión
        $totalApproved = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'approved'"
        )['count'];
        
        $totalConverted = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'converted'"
        )['count'];

        $stats['conversion_rate'] = $stats['total'] > 0 ? (($totalApproved + $totalConverted) / $stats['total']) * 100 : 0;

        return $stats;
    }

    /**
     * Generar número de cotización único
     */
    private function generateQuotationNumber() {
        $prefix = 'COT';
        $year = date('Y');
        $month = date('m');
        
        // Obtener el último número del mes
        $lastNumber = $this->db->fetch(
            "SELECT quotation_number FROM {$this->table} 
             WHERE quotation_number LIKE ? 
             ORDER BY quotation_number DESC 
             LIMIT 1",
            ["{$prefix}-{$year}{$month}%"]
        );
        
        if ($lastNumber) {
            $lastNum = (int)substr($lastNumber['quotation_number'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s%s%04d", $prefix, $year, $month, $newNum);
    }

    /**
     * Validar datos de cotización
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

        // Validar usuario
        if (empty($data['user_id'])) {
            $errors['user_id'] = 'El usuario es requerido';
        } else {
            $user = $this->db->fetch("SELECT id FROM users WHERE id = ?", [$data['user_id']]);
            if (!$user) {
                $errors['user_id'] = 'El usuario seleccionado no existe';
            }
        }

        // Validar número de cotización
        if (!empty($data['quotation_number'])) {
            if (strlen($data['quotation_number']) > 50) {
                $errors['quotation_number'] = 'El número de cotización no puede tener más de 50 caracteres';
            }
        }

        // Validar estado
        if (isset($data['status'])) {
            $validStatuses = ['draft', 'sent', 'reviewed', 'approved', 'rejected', 'converted'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'El estado no es válido';
            }
        }

        // Validar montos
        if (isset($data['subtotal']) && (!is_numeric($data['subtotal']) || $data['subtotal'] < 0)) {
            $errors['subtotal'] = 'El subtotal debe ser un número mayor o igual a 0';
        }

        if (isset($data['tax_rate']) && (!is_numeric($data['tax_rate']) || $data['tax_rate'] < 0 || $data['tax_rate'] > 100)) {
            $errors['tax_rate'] = 'La tasa de impuestos debe ser un número entre 0 y 100';
        }

        // Validar fecha de validez
        if (!empty($data['valid_until'])) {
            if (!strtotime($data['valid_until'])) {
                $errors['valid_until'] = 'La fecha de validez no es válida';
            }
        }

        return $errors;
    }

    /**
     * Duplicar cotización
     */
    public function duplicate($id, $newNumber = null) {
        $quotation = $this->getById($id);
        if (!$quotation) {
            throw new Exception("Cotización no encontrada");
        }

        $data = [
            'customer_id' => $quotation['customer_id'],
            'user_id' => $quotation['user_id'],
            'quotation_number' => $newNumber ?: $this->generateQuotationNumber(),
            'status' => 'draft',
            'subtotal' => $quotation['subtotal'],
            'tax_rate' => $quotation['tax_rate'],
            'tax_amount' => $quotation['tax_amount'],
            'total' => $quotation['total'],
            'valid_until' => $quotation['valid_until'],
            'notes' => $quotation['notes'] . ' (Copia)'
        ];

        return $this->create($data);
    }
}
