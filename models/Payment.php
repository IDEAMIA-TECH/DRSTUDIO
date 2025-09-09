<?php
/**
 * Modelo Payment - DT Studio
 * Gestión de pagos y transacciones
 */

require_once __DIR__ . '/../includes/Database.php';

class Payment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear pago
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['order_id', 'amount', 'method', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar orden
        $order = $this->db->fetch("SELECT id, total FROM orders WHERE id = ?", [$data['order_id']]);
        if (!$order) {
            throw new Exception("La orden no existe");
        }

        // Validar monto
        if ($data['amount'] <= 0) {
            throw new Exception("El monto debe ser mayor a 0");
        }

        if ($data['amount'] > $order['total']) {
            throw new Exception("El monto no puede ser mayor al total de la orden");
        }

        // Generar referencia única
        $data['reference'] = $data['reference'] ?? $this->generateReference();

        $sql = "INSERT INTO payments (order_id, amount, method, reference, status, gateway, gateway_transaction_id, gateway_response, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['order_id'],
            $data['amount'],
            $data['method'],
            $data['reference'],
            $data['status'],
            $data['gateway'] ?? null,
            $data['gateway_transaction_id'] ?? null,
            $data['gateway_response'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? 1
        ]);

        $paymentId = $this->db->lastInsertId();

        // Actualizar estado de la orden si es necesario
        $this->updateOrderPaymentStatus($data['order_id']);

        return $paymentId;
    }

    /**
     * Obtener pago por ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       o.order_number, o.total as order_total,
                       c.name as customer_name, c.email as customer_email,
                       u.name as created_by_name
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener pago por referencia
     */
    public function getByReference($reference) {
        $sql = "SELECT p.*, 
                       o.order_number, o.total as order_total,
                       c.name as customer_name, c.email as customer_email,
                       u.name as created_by_name
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.reference = ?";
        
        return $this->db->fetch($sql, [$reference]);
    }

    /**
     * Obtener pagos por orden
     */
    public function getByOrderId($orderId) {
        $sql = "SELECT p.*, 
                       u.name as created_by_name
                FROM payments p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.order_id = ?
                ORDER BY p.created_at DESC";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }

    /**
     * Listar pagos con filtros
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por estado
        if (!empty($filters['status'])) {
            $whereConditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        
        // Filtro por método
        if (!empty($filters['method'])) {
            $whereConditions[] = 'p.method = ?';
            $params[] = $filters['method'];
        }
        
        // Filtro por pasarela
        if (!empty($filters['gateway'])) {
            $whereConditions[] = 'p.gateway = ?';
            $params[] = $filters['gateway'];
        }
        
        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(p.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(p.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        // Filtro por monto mínimo
        if (!empty($filters['min_amount'])) {
            $whereConditions[] = 'p.amount >= ?';
            $params[] = $filters['min_amount'];
        }
        
        // Filtro por monto máximo
        if (!empty($filters['max_amount'])) {
            $whereConditions[] = 'p.amount <= ?';
            $params[] = $filters['max_amount'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(p.reference LIKE ? OR o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.*, 
                       o.order_number, o.total as order_total,
                       c.name as customer_name, c.email as customer_email,
                       u.name as created_by_name
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $payments = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total 
                     FROM payments p
                     LEFT JOIN orders o ON p.order_id = o.id
                     LEFT JOIN customers c ON o.customer_id = c.id
                     {$whereClause}";
        
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $payments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Actualizar pago
     */
    public function update($id, $data) {
        // Validar que el pago existe
        $payment = $this->getById($id);
        if (!$payment) {
            throw new Exception("El pago no existe");
        }

        // Validar que no se esté actualizando un pago completado
        if ($payment['status'] === 'completed' && isset($data['status']) && $data['status'] !== 'completed') {
            throw new Exception("No se puede modificar un pago completado");
        }

        $allowedFields = ['status', 'gateway_transaction_id', 'gateway_response', 'notes'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        // Actualizar estado de la orden si cambió el estado del pago
        if (isset($data['status'])) {
            $this->updateOrderPaymentStatus($payment['order_id']);
        }

        return true;
    }

    /**
     * Eliminar pago
     */
    public function delete($id) {
        // Validar que el pago existe
        $payment = $this->getById($id);
        if (!$payment) {
            throw new Exception("El pago no existe");
        }

        // Validar que no se esté eliminando un pago completado
        if ($payment['status'] === 'completed') {
            throw new Exception("No se puede eliminar un pago completado");
        }

        $this->db->query("DELETE FROM payments WHERE id = ?", [$id]);

        // Actualizar estado de la orden
        $this->updateOrderPaymentStatus($payment['order_id']);

        return true;
    }

    /**
     * Procesar pago
     */
    public function processPayment($paymentId, $gatewayData = []) {
        $payment = $this->getById($paymentId);
        if (!$payment) {
            throw new Exception("El pago no existe");
        }

        if ($payment['status'] !== 'pending') {
            throw new Exception("El pago no está pendiente");
        }

        // Simular procesamiento del pago
        $gatewayResponse = $this->simulateGatewayResponse($gatewayData);
        
        // Actualizar pago con respuesta de la pasarela
        $this->update($paymentId, [
            'status' => $gatewayResponse['status'],
            'gateway_transaction_id' => $gatewayResponse['transaction_id'],
            'gateway_response' => json_encode($gatewayResponse)
        ]);

        return $gatewayResponse;
    }

    /**
     * Reembolsar pago
     */
    public function refund($id, $amount = null, $reason = '') {
        $payment = $this->getById($id);
        if (!$payment) {
            throw new Exception("El pago no existe");
        }

        if ($payment['status'] !== 'completed') {
            throw new Exception("Solo se pueden reembolsar pagos completados");
        }

        $refundAmount = $amount ?? $payment['amount'];

        if ($refundAmount > $payment['amount']) {
            throw new Exception("El monto del reembolso no puede ser mayor al pago original");
        }

        // Crear registro de reembolso
        $refundData = [
            'order_id' => $payment['order_id'],
            'amount' => -$refundAmount, // Monto negativo para reembolso
            'method' => $payment['method'],
            'reference' => $this->generateReference('REF'),
            'status' => 'completed',
            'gateway' => $payment['gateway'],
            'notes' => "Reembolso: {$reason}",
            'created_by' => 1
        ];

        $refundId = $this->create($refundData);

        return $refundId;
    }

    /**
     * Obtener estadísticas de pagos
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
        
        // Total de pagos
        $stats['total_payments'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM payments {$whereClause}",
            $params
        )['total'];
        
        // Pagos por estado
        $stats['payments_by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(amount) as total_amount
             FROM payments {$whereClause}
             GROUP BY status
             ORDER BY count DESC",
            $params
        );
        
        // Pagos por método
        $stats['payments_by_method'] = $this->db->fetchAll(
            "SELECT method, COUNT(*) as count, SUM(amount) as total_amount
             FROM payments {$whereClause}
             GROUP BY method
             ORDER BY count DESC",
            $params
        );
        
        // Pagos por pasarela
        $stats['payments_by_gateway'] = $this->db->fetchAll(
            "SELECT gateway, COUNT(*) as count, SUM(amount) as total_amount
             FROM payments {$whereClause}
             WHERE gateway IS NOT NULL
             GROUP BY gateway
             ORDER BY count DESC",
            $params
        );
        
        // Monto total
        $stats['total_amount'] = $this->db->fetch(
            "SELECT SUM(amount) as total FROM payments {$whereClause}",
            $params
        )['total'] ?? 0;
        
        // Monto promedio
        $stats['average_amount'] = $this->db->fetch(
            "SELECT AVG(amount) as average FROM payments {$whereClause}",
            $params
        )['average'] ?? 0;
        
        // Tasa de éxito
        $totalPayments = $stats['total_payments'];
        $completedPayments = $this->db->fetch(
            "SELECT COUNT(*) as total FROM payments {$whereClause} AND status = 'completed'",
            $params
        )['total'];
        
        $stats['success_rate'] = $totalPayments > 0 ? 
            round(($completedPayments / $totalPayments) * 100, 2) : 0;
        
        return $stats;
    }

    /**
     * Obtener pagos pendientes
     */
    public function getPendingPayments() {
        $sql = "SELECT p.*, 
                       o.order_number, o.total as order_total,
                       c.name as customer_name, c.email as customer_email
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE p.status = 'pending'
                ORDER BY p.created_at ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener pagos por fecha
     */
    public function getPaymentsByDate($date) {
        $sql = "SELECT p.*, 
                       o.order_number, o.total as order_total,
                       c.name as customer_name, c.email as customer_email
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE DATE(p.created_at) = ?
                ORDER BY p.created_at DESC";
        
        return $this->db->fetchAll($sql, [$date]);
    }

    /**
     * Generar referencia única
     */
    private function generateReference($prefix = 'PAY') {
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Simular respuesta de pasarela
     */
    private function simulateGatewayResponse($gatewayData) {
        // Simular diferentes respuestas basadas en datos
        $success = $gatewayData['success'] ?? (mt_rand(1, 10) > 2); // 80% éxito
        
        if ($success) {
            return [
                'status' => 'completed',
                'transaction_id' => 'TXN-' . mt_rand(100000, 999999),
                'gateway_response' => 'SUCCESS',
                'message' => 'Pago procesado exitosamente'
            ];
        } else {
            return [
                'status' => 'failed',
                'transaction_id' => null,
                'gateway_response' => 'FAILED',
                'message' => 'Error en el procesamiento del pago'
            ];
        }
    }

    /**
     * Actualizar estado de pago de la orden
     */
    private function updateOrderPaymentStatus($orderId) {
        // Obtener total de pagos completados
        $totalPaid = $this->db->fetch(
            "SELECT SUM(amount) as total FROM payments WHERE order_id = ? AND status = 'completed'",
            [$orderId]
        )['total'] ?? 0;
        
        // Obtener total de la orden
        $orderTotal = $this->db->fetch(
            "SELECT total FROM orders WHERE id = ?",
            [$orderId]
        )['total'];
        
        // Determinar estado de pago
        $paymentStatus = 'pending';
        if ($totalPaid >= $orderTotal) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
        }
        
        // Actualizar orden
        $this->db->query(
            "UPDATE orders SET payment_status = ? WHERE id = ?",
            [$paymentStatus, $orderId]
        );
    }

    /**
     * Validar datos de pago
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar orden
        if (!$isUpdate && empty($data['order_id'])) {
            $errors['order_id'] = 'La orden es requerida';
        } elseif (!$isUpdate && $data['order_id']) {
            $order = $this->db->fetch("SELECT id FROM orders WHERE id = ?", [$data['order_id']]);
            if (!$order) {
                $errors['order_id'] = 'La orden no existe';
            }
        }

        // Validar monto
        if (!$isUpdate && (empty($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'El monto debe ser mayor a 0';
        }

        // Validar método
        if (!$isUpdate && empty($data['method'])) {
            $errors['method'] = 'El método de pago es requerido';
        } elseif ($data['method'] && !in_array($data['method'], ['cash', 'card', 'transfer', 'paypal', 'stripe', 'oxxo'])) {
            $errors['method'] = 'Método de pago no válido';
        }

        // Validar estado
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])) {
            $errors['status'] = 'Estado de pago no válido';
        }

        return $errors;
    }
}
