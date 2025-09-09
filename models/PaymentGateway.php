<?php
/**
 * Modelo PaymentGateway - DT Studio
 * Gestión de pasarelas de pago
 */

require_once __DIR__ . '/../includes/Database.php';

class PaymentGateway {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear pasarela de pago
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['name', 'type', 'is_active'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar que el nombre sea único
        $existing = $this->db->fetch("SELECT id FROM payment_gateways WHERE name = ?", [$data['name']]);
        if ($existing) {
            throw new Exception("Ya existe una pasarela con ese nombre");
        }

        $sql = "INSERT INTO payment_gateways (name, type, description, config, is_active, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['type'],
            $data['description'] ?? null,
            json_encode($data['config'] ?? []),
            $data['is_active'] ? 1 : 0,
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener pasarela por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM payment_gateways WHERE id = ?";
        $gateway = $this->db->fetch($sql, [$id]);
        
        if ($gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        
        return $gateway;
    }

    /**
     * Obtener pasarela por nombre
     */
    public function getByName($name) {
        $sql = "SELECT * FROM payment_gateways WHERE name = ?";
        $gateway = $this->db->fetch($sql, [$name]);
        
        if ($gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        
        return $gateway;
    }

    /**
     * Listar pasarelas
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $whereConditions[] = 'type = ?';
            $params[] = $filters['type'];
        }
        
        // Filtro por estado
        if (isset($filters['is_active'])) {
            $whereConditions[] = 'is_active = ?';
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(name LIKE ? OR description LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM payment_gateways 
                {$whereClause}
                ORDER BY sort_order ASC, name ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        $gateways = $this->db->fetchAll($sql, $params);
        
        // Decodificar configuraciones
        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM payment_gateways {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $gateways,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener pasarelas activas
     */
    public function getActive() {
        $sql = "SELECT * FROM payment_gateways 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, name ASC";
        
        $gateways = $this->db->fetchAll($sql);
        
        // Decodificar configuraciones
        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        
        return $gateways;
    }

    /**
     * Actualizar pasarela
     */
    public function update($id, $data) {
        // Validar que la pasarela existe
        $gateway = $this->getById($id);
        if (!$gateway) {
            throw new Exception("La pasarela no existe");
        }

        // Validar que el nombre sea único (si se está cambiando)
        if (isset($data['name']) && $data['name'] !== $gateway['name']) {
            $existing = $this->db->fetch("SELECT id FROM payment_gateways WHERE name = ? AND id != ?", [$data['name'], $id]);
            if ($existing) {
                throw new Exception("Ya existe una pasarela con ese nombre");
            }
        }

        $allowedFields = ['name', 'type', 'description', 'config', 'is_active', 'sort_order'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'config') {
                    $updateFields[] = "{$field} = ?";
                    $params[] = json_encode($data[$field]);
                } else {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $field === 'is_active' ? ($data[$field] ? 1 : 0) : $data[$field];
                }
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE payment_gateways SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * Eliminar pasarela
     */
    public function delete($id) {
        // Validar que la pasarela existe
        $gateway = $this->getById($id);
        if (!$gateway) {
            throw new Exception("La pasarela no existe");
        }

        // Validar que no esté siendo usada en pagos
        $payments = $this->db->fetch("SELECT COUNT(*) as total FROM payments WHERE gateway = ?", [$gateway['name']]);
        if ($payments['total'] > 0) {
            throw new Exception("No se puede eliminar una pasarela que tiene pagos asociados");
        }

        $this->db->query("DELETE FROM payment_gateways WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Activar/desactivar pasarela
     */
    public function toggleStatus($id) {
        $gateway = $this->getById($id);
        if (!$gateway) {
            throw new Exception("La pasarela no existe");
        }

        $newStatus = $gateway['is_active'] ? 0 : 1;
        
        $this->db->query(
            "UPDATE payment_gateways SET is_active = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $id]
        );

        return $newStatus;
    }

    /**
     * Procesar pago con pasarela
     */
    public function processPayment($gatewayName, $paymentData) {
        $gateway = $this->getByName($gatewayName);
        if (!$gateway) {
            throw new Exception("La pasarela no existe");
        }

        if (!$gateway['is_active']) {
            throw new Exception("La pasarela está desactivada");
        }

        // Simular procesamiento según el tipo de pasarela
        switch ($gateway['type']) {
            case 'stripe':
                return $this->processStripePayment($gateway, $paymentData);
            case 'paypal':
                return $this->processPayPalPayment($gateway, $paymentData);
            case 'oxxo':
                return $this->processOXXOPayment($gateway, $paymentData);
            case 'transfer':
                return $this->processTransferPayment($gateway, $paymentData);
            default:
                throw new Exception("Tipo de pasarela no soportado");
        }
    }

    /**
     * Procesar pago con Stripe
     */
    private function processStripePayment($gateway, $paymentData) {
        // Simular integración con Stripe
        $config = $gateway['config'];
        
        // Validar configuración
        if (empty($config['api_key']) || empty($config['secret_key'])) {
            throw new Exception("Configuración de Stripe incompleta");
        }

        // Simular respuesta de Stripe
        $success = mt_rand(1, 10) > 1; // 90% éxito
        
        if ($success) {
            return [
                'status' => 'completed',
                'transaction_id' => 'stripe_' . mt_rand(100000, 999999),
                'gateway_response' => json_encode([
                    'id' => 'stripe_' . mt_rand(100000, 999999),
                    'status' => 'succeeded',
                    'amount' => $paymentData['amount'],
                    'currency' => 'mxn'
                ]),
                'message' => 'Pago procesado exitosamente con Stripe'
            ];
        } else {
            return [
                'status' => 'failed',
                'transaction_id' => null,
                'gateway_response' => json_encode([
                    'error' => 'card_declined',
                    'message' => 'Tarjeta rechazada'
                ]),
                'message' => 'Error en el procesamiento con Stripe'
            ];
        }
    }

    /**
     * Procesar pago con PayPal
     */
    private function processPayPalPayment($gateway, $paymentData) {
        // Simular integración con PayPal
        $config = $gateway['config'];
        
        // Validar configuración
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new Exception("Configuración de PayPal incompleta");
        }

        // Simular respuesta de PayPal
        $success = mt_rand(1, 10) > 2; // 80% éxito
        
        if ($success) {
            return [
                'status' => 'completed',
                'transaction_id' => 'paypal_' . mt_rand(100000, 999999),
                'gateway_response' => json_encode([
                    'id' => 'paypal_' . mt_rand(100000, 999999),
                    'state' => 'approved',
                    'amount' => $paymentData['amount'],
                    'currency' => 'MXN'
                ]),
                'message' => 'Pago procesado exitosamente con PayPal'
            ];
        } else {
            return [
                'status' => 'failed',
                'transaction_id' => null,
                'gateway_response' => json_encode([
                    'error' => 'payment_failed',
                    'message' => 'Pago rechazado'
                ]),
                'message' => 'Error en el procesamiento con PayPal'
            ];
        }
    }

    /**
     * Procesar pago con OXXO
     */
    private function processOXXOPayment($gateway, $paymentData) {
        // Simular integración con OXXO
        $config = $gateway['config'];
        
        // Validar configuración
        if (empty($config['merchant_id']) || empty($config['api_key'])) {
            throw new Exception("Configuración de OXXO incompleta");
        }

        // OXXO siempre genera una referencia para pago en tienda
        return [
            'status' => 'pending',
            'transaction_id' => 'oxxo_' . mt_rand(100000, 999999),
            'gateway_response' => json_encode([
                'reference' => 'OXXO-' . mt_rand(100000, 999999),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'amount' => $paymentData['amount'],
                'currency' => 'MXN'
            ]),
            'message' => 'Referencia OXXO generada. Vence en 7 días.'
        ];
    }

    /**
     * Procesar pago por transferencia
     */
    private function processTransferPayment($gateway, $paymentData) {
        // Simular procesamiento de transferencia
        $config = $gateway['config'];
        
        // Validar configuración
        if (empty($config['account_number']) || empty($config['bank'])) {
            throw new Exception("Configuración de transferencia incompleta");
        }

        // Transferencia siempre queda pendiente hasta confirmación manual
        return [
            'status' => 'pending',
            'transaction_id' => 'transfer_' . mt_rand(100000, 999999),
            'gateway_response' => json_encode([
                'account_number' => $config['account_number'],
                'bank' => $config['bank'],
                'amount' => $paymentData['amount'],
                'currency' => 'MXN',
                'reference' => 'TRF-' . mt_rand(100000, 999999)
            ]),
            'message' => 'Instrucciones de transferencia generadas'
        ];
    }

    /**
     * Obtener estadísticas de pasarelas
     */
    public function getStats() {
        $stats = [];
        
        // Total de pasarelas
        $stats['total_gateways'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM payment_gateways"
        )['total'];
        
        // Pasarelas activas
        $stats['active_gateways'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM payment_gateways WHERE is_active = 1"
        )['total'];
        
        // Pasarelas por tipo
        $stats['gateways_by_type'] = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count 
             FROM payment_gateways 
             GROUP BY type 
             ORDER BY count DESC"
        );
        
        // Uso de pasarelas en pagos
        $stats['gateway_usage'] = $this->db->fetchAll(
            "SELECT p.gateway, COUNT(*) as payment_count, SUM(p.amount) as total_amount
             FROM payments p
             WHERE p.gateway IS NOT NULL
             GROUP BY p.gateway
             ORDER BY payment_count DESC"
        );
        
        return $stats;
    }

    /**
     * Validar configuración de pasarela
     */
    public function validateConfig($type, $config) {
        $errors = [];
        
        switch ($type) {
            case 'stripe':
                if (empty($config['api_key'])) {
                    $errors['api_key'] = 'API Key es requerida';
                }
                if (empty($config['secret_key'])) {
                    $errors['secret_key'] = 'Secret Key es requerida';
                }
                break;
                
            case 'paypal':
                if (empty($config['client_id'])) {
                    $errors['client_id'] = 'Client ID es requerido';
                }
                if (empty($config['client_secret'])) {
                    $errors['client_secret'] = 'Client Secret es requerido';
                }
                break;
                
            case 'oxxo':
                if (empty($config['merchant_id'])) {
                    $errors['merchant_id'] = 'Merchant ID es requerido';
                }
                if (empty($config['api_key'])) {
                    $errors['api_key'] = 'API Key es requerida';
                }
                break;
                
            case 'transfer':
                if (empty($config['bank'])) {
                    $errors['bank'] = 'Banco es requerido';
                }
                if (empty($config['account_number'])) {
                    $errors['account_number'] = 'Número de cuenta es requerido';
                }
                break;
        }
        
        return $errors;
    }

    /**
     * Validar datos de pasarela
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (!$isUpdate && empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'El nombre no puede tener más de 100 caracteres';
        }

        // Validar tipo
        if (!$isUpdate && empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } elseif ($data['type'] && !in_array($data['type'], ['stripe', 'paypal', 'oxxo', 'transfer', 'cash'])) {
            $errors['type'] = 'Tipo de pasarela no válido';
        }

        // Validar configuración
        if (isset($data['config']) && is_array($data['config'])) {
            $configErrors = $this->validateConfig($data['type'], $data['config']);
            $errors = array_merge($errors, $configErrors);
        }

        return $errors;
    }
}
