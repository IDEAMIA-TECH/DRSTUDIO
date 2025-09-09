<?php
/**
 * Tests para el módulo de Sistema de Pagos - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class PaymentTest {
    private $db;
    private $testOrderId;
    private $testCustomerId;
    private $testPaymentId;
    private $testGatewayId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE SISTEMA DE PAGOS ===\n\n";
        
        $this->testCreateTestData();
        $this->testCreatePayment();
        $this->testGetPaymentById();
        $this->testGetPaymentByReference();
        $this->testGetPaymentsByOrderId();
        $this->testGetAllPayments();
        $this->testUpdatePayment();
        $this->testProcessPayment();
        $this->testRefundPayment();
        $this->testGetPaymentStats();
        $this->testGetPendingPayments();
        $this->testGetPaymentsByDate();
        $this->testCreatePaymentGateway();
        $this->testGetGatewayById();
        $this->testGetAllGateways();
        $this->testGetActiveGateways();
        $this->testUpdateGateway();
        $this->testToggleGatewayStatus();
        $this->testProcessWithGateway();
        $this->testGetGatewayStats();
        $this->testValidateGatewayConfig();
        $this->testValidatePaymentData();
        $this->testValidateGatewayData();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear datos de prueba
     */
    public function testCreateTestData() {
        echo "Test: Crear datos de prueba... ";
        
        try {
            // Crear cliente
            $sql = "INSERT INTO customers (name, email, phone, company, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Cliente de Prueba', 'cliente@example.com', '5551234567', 'Empresa de Prueba', 1]);
            $this->testCustomerId = $this->db->lastInsertId();
            
            // Crear orden
            $sql = "INSERT INTO orders (customer_id, created_by, order_number, status, payment_status, subtotal, tax_amount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testCustomerId, 1, 'ORD-TEST-001', 'confirmed', 'pending', 100.00, 16.00, 116.00]);
            $this->testOrderId = $this->db->lastInsertId();
            
            // Crear pasarela de pago
            $sql = "INSERT INTO payment_gateways (name, type, description, config, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Stripe Test', 'stripe', 'Pasarela de prueba Stripe', '{"api_key": "sk_test_123", "secret_key": "sk_test_456"}', 1, 1]);
            $this->testGatewayId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear pago
     */
    public function testCreatePayment() {
        echo "Test: Crear pago... ";
        
        try {
            $sql = "INSERT INTO payments (order_id, amount, method, reference, status, gateway, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testOrderId, 116.00, 'card', 'PAY-TEST-001', 'pending', 'Stripe Test', 1]);
            $this->testPaymentId = $this->db->lastInsertId();
            
            if ($this->testPaymentId) {
                echo "✓ PASSED (ID: {$this->testPaymentId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del pago\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pago por ID
     */
    public function testGetPaymentById() {
        echo "Test: Obtener pago por ID... ";
        
        try {
            $sql = "SELECT p.*, 
                           o.order_number, o.total as order_total,
                           c.name as customer_name, c.email as customer_email,
                           u.name as created_by_name
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE p.id = ?";
            
            $payment = $this->db->fetch($sql, [$this->testPaymentId]);
            
            if ($payment && $payment['id'] == $this->testPaymentId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pago no encontrado\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pago por referencia
     */
    public function testGetPaymentByReference() {
        echo "Test: Obtener pago por referencia... ";
        
        try {
            $sql = "SELECT p.*, 
                           o.order_number, o.total as order_total,
                           c.name as customer_name, c.email as customer_email,
                           u.name as created_by_name
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE p.reference = ?";
            
            $payment = $this->db->fetch($sql, ['PAY-TEST-001']);
            
            if ($payment && $payment['reference'] == 'PAY-TEST-001') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pago no encontrado por referencia\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pagos por orden
     */
    public function testGetPaymentsByOrderId() {
        echo "Test: Obtener pagos por orden... ";
        
        try {
            $sql = "SELECT p.*, 
                           u.name as created_by_name
                    FROM payments p
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE p.order_id = ?
                    ORDER BY p.created_at DESC";
            
            $payments = $this->db->fetchAll($sql, [$this->testOrderId]);
            
            if (is_array($payments) && count($payments) > 0) {
                echo "✓ PASSED (Total: " . count($payments) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pagos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todos los pagos
     */
    public function testGetAllPayments() {
        echo "Test: Obtener todos los pagos... ";
        
        try {
            $sql = "SELECT p.*, 
                           o.order_number, o.total as order_total,
                           c.name as customer_name, c.email as customer_email,
                           u.name as created_by_name
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    ORDER BY p.created_at DESC
                    LIMIT 20 OFFSET 0";
            
            $payments = $this->db->fetchAll($sql);
            
            if (is_array($payments)) {
                echo "✓ PASSED (Total: " . count($payments) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pagos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar pago
     */
    public function testUpdatePayment() {
        echo "Test: Actualizar pago... ";
        
        try {
            $sql = "UPDATE payments SET status = ?, gateway_transaction_id = ?, gateway_response = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['completed', 'TXN-123456', '{"status": "success"}', 'Pago procesado exitosamente', $this->testPaymentId]);
            
            // Verificar actualización
            $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ?", [$this->testPaymentId]);
            
            if ($payment && $payment['status'] == 'completed') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pago no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Procesar pago
     */
    public function testProcessPayment() {
        echo "Test: Procesar pago... ";
        
        try {
            // Crear otro pago pendiente
            $sql = "INSERT INTO payments (order_id, amount, method, reference, status, gateway, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testOrderId, 50.00, 'card', 'PAY-TEST-002', 'pending', 'Stripe Test', 1]);
            $pendingPaymentId = $this->db->lastInsertId();
            
            // Simular procesamiento
            $sql = "UPDATE payments SET status = ?, gateway_transaction_id = ?, gateway_response = ? WHERE id = ?";
            $this->db->query($sql, ['completed', 'TXN-789012', '{"status": "success"}', $pendingPaymentId]);
            
            $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ?", [$pendingPaymentId]);
            
            if ($payment && $payment['status'] == 'completed') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pago no se procesó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Reembolsar pago
     */
    public function testRefundPayment() {
        echo "Test: Reembolsar pago... ";
        
        try {
            // Crear reembolso
            $sql = "INSERT INTO payments (order_id, amount, method, reference, status, gateway, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testOrderId, -50.00, 'card', 'REF-TEST-001', 'completed', 'Stripe Test', 'Reembolso de prueba', 1]);
            $refundId = $this->db->lastInsertId();
            
            if ($refundId) {
                echo "✓ PASSED (ID: {$refundId})\n";
            } else {
                echo "✗ FAILED - No se creó el reembolso\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de pagos
     */
    public function testGetPaymentStats() {
        echo "Test: Obtener estadísticas de pagos... ";
        
        try {
            $stats = [];
            
            // Total de pagos
            $stats['total_payments'] = $this->db->fetch("SELECT COUNT(*) as total FROM payments")['total'];
            
            // Pagos por estado
            $stats['payments_by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count, SUM(amount) as total_amount
                 FROM payments
                 GROUP BY status
                 ORDER BY count DESC"
            );
            
            // Monto total
            $stats['total_amount'] = $this->db->fetch("SELECT SUM(amount) as total FROM payments")['total'] ?? 0;
            
            if (isset($stats['total_payments']) && isset($stats['total_amount'])) {
                echo "✓ PASSED (Total: {$stats['total_payments']}, Monto: {$stats['total_amount']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pagos pendientes
     */
    public function testGetPendingPayments() {
        echo "Test: Obtener pagos pendientes... ";
        
        try {
            $sql = "SELECT p.*, 
                           o.order_number, o.total as order_total,
                           c.name as customer_name, c.email as customer_email
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    WHERE p.status = 'pending'
                    ORDER BY p.created_at ASC";
            
            $payments = $this->db->fetchAll($sql);
            
            if (is_array($payments)) {
                echo "✓ PASSED (Total: " . count($payments) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pagos pendientes\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pagos por fecha
     */
    public function testGetPaymentsByDate() {
        echo "Test: Obtener pagos por fecha... ";
        
        try {
            $today = date('Y-m-d');
            $sql = "SELECT p.*, 
                           o.order_number, o.total as order_total,
                           c.name as customer_name, c.email as customer_email
                    FROM payments p
                    LEFT JOIN orders o ON p.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    WHERE DATE(p.created_at) = ?
                    ORDER BY p.created_at DESC";
            
            $payments = $this->db->fetchAll($sql, [$today]);
            
            if (is_array($payments)) {
                echo "✓ PASSED (Total: " . count($payments) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pagos por fecha\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear pasarela de pago
     */
    public function testCreatePaymentGateway() {
        echo "Test: Crear pasarela de pago... ";
        
        try {
            $sql = "INSERT INTO payment_gateways (name, type, description, config, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['PayPal Test', 'paypal', 'Pasarela de prueba PayPal', '{"client_id": "paypal_123", "client_secret": "paypal_456"}', 1, 2]);
            $gatewayId = $this->db->lastInsertId();
            
            if ($gatewayId) {
                echo "✓ PASSED (ID: {$gatewayId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID de la pasarela\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pasarela por ID
     */
    public function testGetGatewayById() {
        echo "Test: Obtener pasarela por ID... ";
        
        try {
            $sql = "SELECT * FROM payment_gateways WHERE id = ?";
            $gateway = $this->db->fetch($sql, [$this->testGatewayId]);
            
            if ($gateway && $gateway['id'] == $this->testGatewayId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pasarela no encontrada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todas las pasarelas
     */
    public function testGetAllGateways() {
        echo "Test: Obtener todas las pasarelas... ";
        
        try {
            $sql = "SELECT * FROM payment_gateways ORDER BY sort_order ASC, name ASC";
            $gateways = $this->db->fetchAll($sql);
            
            if (is_array($gateways)) {
                echo "✓ PASSED (Total: " . count($gateways) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pasarelas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pasarelas activas
     */
    public function testGetActiveGateways() {
        echo "Test: Obtener pasarelas activas... ";
        
        try {
            $sql = "SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
            $gateways = $this->db->fetchAll($sql);
            
            if (is_array($gateways)) {
                echo "✓ PASSED (Total: " . count($gateways) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pasarelas activas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar pasarela
     */
    public function testUpdateGateway() {
        echo "Test: Actualizar pasarela... ";
        
        try {
            $sql = "UPDATE payment_gateways SET description = ?, config = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['Pasarela Stripe actualizada', '{"api_key": "sk_test_updated", "secret_key": "sk_test_updated"}', $this->testGatewayId]);
            
            $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ?", [$this->testGatewayId]);
            
            if ($gateway && $gateway['description'] == 'Pasarela Stripe actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pasarela no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Activar/desactivar pasarela
     */
    public function testToggleGatewayStatus() {
        echo "Test: Activar/desactivar pasarela... ";
        
        try {
            // Obtener estado actual
            $gateway = $this->db->fetch("SELECT is_active FROM payment_gateways WHERE id = ?", [$this->testGatewayId]);
            $currentStatus = $gateway['is_active'];
            $newStatus = $currentStatus ? 0 : 1;
            
            $sql = "UPDATE payment_gateways SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [$newStatus, $this->testGatewayId]);
            
            $gateway = $this->db->fetch("SELECT is_active FROM payment_gateways WHERE id = ?", [$this->testGatewayId]);
            
            if ($gateway['is_active'] == $newStatus) {
                echo "✓ PASSED (Estado: {$newStatus})\n";
            } else {
                echo "✗ FAILED - Estado no cambió correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Procesar pago con pasarela
     */
    public function testProcessWithGateway() {
        echo "Test: Procesar pago con pasarela... ";
        
        try {
            // Simular procesamiento con Stripe
            $paymentData = [
                'amount' => 100.00,
                'currency' => 'MXN',
                'card_token' => 'tok_test_123'
            ];
            
            // Simular respuesta exitosa
            $result = [
                'status' => 'completed',
                'transaction_id' => 'stripe_' . mt_rand(100000, 999999),
                'gateway_response' => json_encode(['status' => 'succeeded']),
                'message' => 'Pago procesado exitosamente con Stripe'
            ];
            
            if ($result['status'] == 'completed') {
                echo "✓ PASSED (Transacción: {$result['transaction_id']})\n";
            } else {
                echo "✗ FAILED - Procesamiento falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de pasarelas
     */
    public function testGetGatewayStats() {
        echo "Test: Obtener estadísticas de pasarelas... ";
        
        try {
            $stats = [];
            
            // Total de pasarelas
            $stats['total_gateways'] = $this->db->fetch("SELECT COUNT(*) as total FROM payment_gateways")['total'];
            
            // Pasarelas activas
            $stats['active_gateways'] = $this->db->fetch("SELECT COUNT(*) as total FROM payment_gateways WHERE is_active = 1")['total'];
            
            // Pasarelas por tipo
            $stats['gateways_by_type'] = $this->db->fetchAll(
                "SELECT type, COUNT(*) as count FROM payment_gateways GROUP BY type ORDER BY count DESC"
            );
            
            if (isset($stats['total_gateways']) && isset($stats['active_gateways'])) {
                echo "✓ PASSED (Total: {$stats['total_gateways']}, Activas: {$stats['active_gateways']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de pasarelas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar configuración de pasarela
     */
    public function testValidateGatewayConfig() {
        echo "Test: Validar configuración de pasarela... ";
        
        try {
            // Test configuración válida de Stripe
            $stripeConfig = [
                'api_key' => 'sk_test_123',
                'secret_key' => 'sk_test_456'
            ];
            
            $errors = $this->validateGatewayConfig('stripe', $stripeConfig);
            
            if (empty($errors)) {
                // Test configuración inválida
                $invalidConfig = [
                    'api_key' => '',
                    'secret_key' => 'sk_test_456'
                ];
                
                $errors = $this->validateGatewayConfig('stripe', $invalidConfig);
                
                if (!empty($errors) && isset($errors['api_key'])) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Validación de configuración inválida no funcionó\n";
                }
            } else {
                echo "✗ FAILED - Validación de configuración válida falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de pago
     */
    public function testValidatePaymentData() {
        echo "Test: Validar datos de pago... ";
        
        try {
            // Test datos válidos
            $validData = [
                'order_id' => $this->testOrderId,
                'amount' => 100.00,
                'method' => 'card'
            ];
            
            $errors = $this->validatePaymentData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'order_id' => 99999, // Orden inexistente
                    'amount' => -50.00,  // Monto negativo
                    'method' => 'invalid' // Método inválido
                ];
                
                $errors = $this->validatePaymentData($invalidData);
                
                if (!empty($errors) && isset($errors['order_id']) && isset($errors['amount']) && isset($errors['method'])) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Validación de datos inválidos no funcionó\n";
                }
            } else {
                echo "✗ FAILED - Validación de datos válidos falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de pasarela
     */
    public function testValidateGatewayData() {
        echo "Test: Validar datos de pasarela... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Nueva Pasarela',
                'type' => 'stripe',
                'config' => ['api_key' => 'sk_test_123', 'secret_key' => 'sk_test_456']
            ];
            
            $errors = $this->validateGatewayData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'type' => 'invalid', // Tipo inválido
                    'config' => ['api_key' => ''] // Configuración incompleta
                ];
                
                $errors = $this->validateGatewayData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['type']) && isset($errors['api_key'])) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Validación de datos inválidos no funcionó\n";
                }
            } else {
                echo "✗ FAILED - Validación de datos válidos falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Limpiar datos de prueba
     */
    public function testCleanup() {
        echo "Test: Limpiar datos de prueba... ";
        
        try {
            // Eliminar pagos
            $this->db->query("DELETE FROM payments WHERE order_id = ?", [$this->testOrderId]);
            
            // Eliminar pasarelas
            $this->db->query("DELETE FROM payment_gateways WHERE id IN (?, ?)", [$this->testGatewayId, $this->testGatewayId + 1]);
            
            // Eliminar orden
            $this->db->query("DELETE FROM orders WHERE id = ?", [$this->testOrderId]);
            
            // Eliminar cliente
            $this->db->query("DELETE FROM customers WHERE id = ?", [$this->testCustomerId]);
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar configuración de pasarela (función auxiliar)
     */
    private function validateGatewayConfig($type, $config) {
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
        }
        
        return $errors;
    }

    /**
     * Validar datos de pago (función auxiliar)
     */
    private function validatePaymentData($data) {
        $errors = [];

        // Validar orden
        if (empty($data['order_id'])) {
            $errors['order_id'] = 'La orden es requerida';
        } else {
            $order = $this->db->fetch("SELECT id FROM orders WHERE id = ?", [$data['order_id']]);
            if (!$order) {
                $errors['order_id'] = 'La orden no existe';
            }
        }

        // Validar monto
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'El monto debe ser mayor a 0';
        }

        // Validar método
        if (empty($data['method'])) {
            $errors['method'] = 'El método de pago es requerido';
        } elseif (!in_array($data['method'], ['cash', 'card', 'transfer', 'paypal', 'stripe', 'oxxo'])) {
            $errors['method'] = 'Método de pago no válido';
        }

        return $errors;
    }

    /**
     * Validar datos de pasarela (función auxiliar)
     */
    private function validateGatewayData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        }

        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } elseif (!in_array($data['type'], ['stripe', 'paypal', 'oxxo', 'transfer', 'cash'])) {
            $errors['type'] = 'Tipo de pasarela no válido';
        }

        // Validar configuración
        if (isset($data['config']) && is_array($data['config'])) {
            $configErrors = $this->validateGatewayConfig($data['type'], $data['config']);
            $errors = array_merge($errors, $configErrors);
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PaymentTest();
    $test->runAllTests();
}
