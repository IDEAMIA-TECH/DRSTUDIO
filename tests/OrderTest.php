<?php
/**
 * Tests para el módulo de Pedidos - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class OrderTest {
    private $db;
    private $testOrderId;
    private $testCustomerId;
    private $testUserId;
    private $testProductId;
    private $testCategoryId;
    private $testQuotationId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE PEDIDOS ===\n\n";
        
        $this->testCreateTestData();
        $this->testCreateOrder();
        $this->testGetOrderById();
        $this->testGetOrderByNumber();
        $this->testUpdateOrder();
        $this->testValidateOrder();
        $this->testGetAllOrders();
        $this->testChangeOrderStatus();
        $this->testChangePaymentStatus();
        $this->testGetOrdersByCustomer();
        $this->testGetOrdersByUser();
        $this->testGetPendingOrders();
        $this->testGetToDeliverOrders();
        $this->testGetOrderStats();
        $this->testDuplicateOrder();
        $this->testAddOrderItem();
        $this->testUpdateOrderItem();
        $this->testGetOrderItems();
        $this->testDeleteOrderItem();
        $this->testGetOrderHistory();
        $this->testDeleteOrder();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear datos de prueba
     */
    public function testCreateTestData() {
        echo "Test: Crear datos de prueba... ";
        
        try {
            // Crear categoría
            $sql = "INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Categoría de Prueba', 'categoria-de-prueba', 'Para pruebas', 1, 1]);
            $this->testCategoryId = $this->db->lastInsertId();
            
            // Crear usuario
            $email = 'usuario' . time() . '@example.com';
            $sql = "INSERT INTO users (name, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Usuario de Prueba', $email, password_hash('password123', PASSWORD_DEFAULT), 1, 1]);
            $this->testUserId = $this->db->lastInsertId();
            
            // Crear cliente
            $customerEmail = 'cliente' . time() . '@example.com';
            $sql = "INSERT INTO customers (name, email, phone, company, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Cliente de Prueba', $customerEmail, '5551234567', 'Empresa de Prueba', 1]);
            $this->testCustomerId = $this->db->lastInsertId();
            
            // Crear producto
            $sql = "INSERT INTO products (name, description, category_id, sku, status, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Producto de Prueba', 'Descripción del producto', $this->testCategoryId, 'PROD-TEST-001', 'active', $this->testUserId]);
            $this->testProductId = $this->db->lastInsertId();
            
            // Crear cotización
            $sql = "INSERT INTO quotations (customer_id, user_id, quotation_number, status, subtotal, tax_rate, tax_amount, total, valid_until, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testCustomerId, $this->testUserId, 'COT-TEST-001', 'approved', 100.00, 16.00, 16.00, 116.00, date('Y-m-d', strtotime('+30 days')), 'Cotización de prueba']);
            $this->testQuotationId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear pedido
     */
    public function testCreateOrder() {
        echo "Test: Crear pedido... ";
        
        try {
            $orderData = [
                'quotation_id' => $this->testQuotationId,
                'customer_id' => $this->testCustomerId,
                'created_by' => $this->testUserId,
                'order_number' => 'PED-TEST-001',
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => 100.00,
                'tax_amount' => 16.00,
                'total' => 116.00,
                'shipping_address' => 'Dirección de envío de prueba',
                'billing_address' => 'Dirección de facturación de prueba',
                'notes' => 'Pedido de prueba',
                'delivery_date' => date('Y-m-d', strtotime('+7 days'))
            ];
            
            $sql = "INSERT INTO orders (quotation_id, customer_id, created_by, order_number, status, payment_status, subtotal, tax_amount, total, shipping_address, billing_address, notes, delivery_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $orderData['quotation_id'],
                $orderData['customer_id'],
                $orderData['created_by'],
                $orderData['order_number'],
                $orderData['status'],
                $orderData['payment_status'],
                $orderData['subtotal'],
                $orderData['tax_amount'],
                $orderData['total'],
                $orderData['shipping_address'],
                $orderData['billing_address'],
                $orderData['notes'],
                $orderData['delivery_date']
            ]);
            
            $this->testOrderId = $this->db->lastInsertId();
            
            if ($this->testOrderId) {
                echo "✓ PASSED (ID: {$this->testOrderId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del pedido\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedido por ID
     */
    public function testGetOrderById() {
        echo "Test: Obtener pedido por ID... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.id = ?";
            $order = $this->db->fetch($sql, [$this->testOrderId]);
            
            if ($order && $order['id'] == $this->testOrderId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pedido no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedido por número
     */
    public function testGetOrderByNumber() {
        echo "Test: Obtener pedido por número... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.order_number = ?";
            $order = $this->db->fetch($sql, ['PED-TEST-001']);
            
            if ($order && $order['order_number'] == 'PED-TEST-001') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pedido no encontrado o número incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar pedido
     */
    public function testUpdateOrder() {
        echo "Test: Actualizar pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para actualizar\n";
                return;
            }
            
            $sql = "UPDATE orders SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['confirmed', 'Pedido actualizado', $this->testOrderId]);
            
            // Verificar que se actualizó
            $order = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$this->testOrderId]);
            if ($order && $order['status'] == 'confirmed' && $order['notes'] == 'Pedido actualizado') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de pedido
     */
    public function testValidateOrder() {
        echo "Test: Validar datos de pedido... ";
        
        try {
            // Test datos válidos
            $validData = [
                'customer_id' => $this->testCustomerId,
                'created_by' => $this->testUserId,
                'order_number' => 'PED-VALID-001',
                'status' => 'pending'
            ];
            
            $errors = $this->validateOrderData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'customer_id' => '', // Cliente vacío
                    'created_by' => 999, // Usuario inexistente
                    'order_number' => str_repeat('a', 60), // Número muy largo
                    'status' => 'invalid_status' // Estado inválido
                ];
                
                $errors = $this->validateOrderData($invalidData);
                
                if (!empty($errors) && isset($errors['customer_id']) && isset($errors['created_by']) && isset($errors['status'])) {
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
     * Test: Obtener todos los pedidos
     */
    public function testGetAllOrders() {
        echo "Test: Obtener todos los pedidos... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    ORDER BY o.created_at DESC";
            $orders = $this->db->fetchAll($sql);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado del pedido
     */
    public function testChangeOrderStatus() {
        echo "Test: Cambiar estado del pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para cambiar estado\n";
                return;
            }
            
            // Cambiar a procesando
            $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['processing', $this->testOrderId]);
            
            // Verificar que cambió
            $order = $this->db->fetch("SELECT status FROM orders WHERE id = ?", [$this->testOrderId]);
            if ($order && $order['status'] == 'processing') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado de pago
     */
    public function testChangePaymentStatus() {
        echo "Test: Cambiar estado de pago... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para cambiar estado de pago\n";
                return;
            }
            
            // Cambiar a pagado
            $sql = "UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['paid', $this->testOrderId]);
            
            // Verificar que cambió
            $order = $this->db->fetch("SELECT payment_status FROM orders WHERE id = ?", [$this->testOrderId]);
            if ($order && $order['payment_status'] == 'paid') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado de pago no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedidos por cliente
     */
    public function testGetOrdersByCustomer() {
        echo "Test: Obtener pedidos por cliente... ";
        
        try {
            $sql = "SELECT o.*, 
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.customer_id = ? 
                    ORDER BY o.created_at DESC 
                    LIMIT ?";
            $orders = $this->db->fetchAll($sql, [$this->testCustomerId, 20]);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pedidos por cliente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedidos por usuario
     */
    public function testGetOrdersByUser() {
        echo "Test: Obtener pedidos por usuario... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.created_by = ? 
                    ORDER BY o.created_at DESC 
                    LIMIT ?";
            $orders = $this->db->fetchAll($sql, [$this->testUserId, 20]);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pedidos por usuario\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedidos pendientes
     */
    public function testGetPendingOrders() {
        echo "Test: Obtener pedidos pendientes... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.status IN ('pending', 'confirmed', 'processing') 
                    ORDER BY o.created_at ASC";
            $orders = $this->db->fetchAll($sql);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pedidos pendientes\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener pedidos por entregar
     */
    public function testGetToDeliverOrders() {
        echo "Test: Obtener pedidos por entregar... ";
        
        try {
            $sql = "SELECT o.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name,
                           q.quotation_number
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN users u ON o.created_by = u.id
                    LEFT JOIN quotations q ON o.quotation_id = q.id
                    WHERE o.status IN ('confirmed', 'processing', 'shipped') 
                    AND (o.delivery_date IS NULL OR o.delivery_date >= date('now'))
                    ORDER BY o.delivery_date ASC, o.created_at ASC";
            $orders = $this->db->fetchAll($sql);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron pedidos por entregar\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de pedidos
     */
    public function testGetOrderStats() {
        echo "Test: Obtener estadísticas de pedidos... ";
        
        try {
            $stats = [];
            
            // Total de pedidos
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM orders")['count'];
            
            // Pedidos por estado
            $stats['by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count 
                 FROM orders 
                 GROUP BY status 
                 ORDER BY count DESC"
            );
            
            // Valor total de pedidos
            $stats['total_value'] = $this->db->fetch(
                "SELECT SUM(total) as total_value FROM orders WHERE status != 'cancelled'"
            )['total_value'] ?? 0;
            
            if (isset($stats['total']) && isset($stats['by_status']) && isset($stats['total_value'])) {
                echo "✓ PASSED (Total: {$stats['total']}, Valor: {$stats['total_value']})\n";
            } else {
                echo "✗ FAILED - Estructura de estadísticas incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Duplicar pedido
     */
    public function testDuplicateOrder() {
        echo "Test: Duplicar pedido... ";
        
        try {
            $newNumber = 'PED-DUP-001';
            
            $sql = "INSERT INTO orders (quotation_id, customer_id, created_by, order_number, status, payment_status, subtotal, tax_amount, total, shipping_address, billing_address, notes, delivery_date) 
                    SELECT quotation_id, customer_id, created_by, ?, 'pending', 'pending', subtotal, tax_amount, total, shipping_address, billing_address, CONCAT(notes, ' (Copia)'), delivery_date 
                    FROM orders WHERE id = ?";
            $this->db->query($sql, [$newNumber, $this->testOrderId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó el pedido duplicado
                $duplicated = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['order_number'] == $newNumber) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Pedido duplicado no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar el pedido\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Agregar item a pedido
     */
    public function testAddOrderItem() {
        echo "Test: Agregar item a pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para agregar item\n";
                return;
            }
            
            $itemData = [
                'order_id' => $this->testOrderId,
                'product_id' => $this->testProductId,
                'quantity' => 2,
                'unit_price' => 50.00,
                'total' => 100.00,
                'notes' => 'Item de prueba'
            ];
            
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total, notes) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $itemData['order_id'],
                $itemData['product_id'],
                $itemData['quantity'],
                $itemData['unit_price'],
                $itemData['total'],
                $itemData['notes']
            ]);
            
            $itemId = $this->db->lastInsertId();
            
            if ($itemId) {
                echo "✓ PASSED (ID: {$itemId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del item\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar item de pedido
     */
    public function testUpdateOrderItem() {
        echo "Test: Actualizar item de pedido... ";
        
        try {
            // Obtener el primer item del pedido
            $item = $this->db->fetch("SELECT * FROM order_items WHERE order_id = ? LIMIT 1", [$this->testOrderId]);
            
            if (!$item) {
                echo "✗ FAILED - No hay item para actualizar\n";
                return;
            }
            
            $sql = "UPDATE order_items SET quantity = ?, unit_price = ?, total = ?, notes = ? WHERE id = ?";
            $this->db->query($sql, [3, 60.00, 180.00, 'Item actualizado', $item['id']]);
            
            // Verificar que se actualizó
            $updatedItem = $this->db->fetch("SELECT * FROM order_items WHERE id = ?", [$item['id']]);
            if ($updatedItem && $updatedItem['quantity'] == 3 && $updatedItem['unit_price'] == 60.00) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Item no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener items de pedido
     */
    public function testGetOrderItems() {
        echo "Test: Obtener items de pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para obtener items\n";
                return;
            }
            
            $sql = "SELECT oi.*, 
                           p.name as product_name, p.sku as product_sku
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ? 
                    ORDER BY oi.created_at ASC";
            $items = $this->db->fetchAll($sql, [$this->testOrderId]);
            
            if (is_array($items)) {
                echo "✓ PASSED (Total: " . count($items) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron items del pedido\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar item de pedido
     */
    public function testDeleteOrderItem() {
        echo "Test: Eliminar item de pedido... ";
        
        try {
            // Obtener el primer item del pedido
            $item = $this->db->fetch("SELECT * FROM order_items WHERE order_id = ? LIMIT 1", [$this->testOrderId]);
            
            if (!$item) {
                echo "✗ FAILED - No hay item para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM order_items WHERE id = ?";
            $this->db->query($sql, [$item['id']]);
            
            // Verificar que se eliminó
            $deletedItem = $this->db->fetch("SELECT * FROM order_items WHERE id = ?", [$item['id']]);
            if (!$deletedItem) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Item no se eliminó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener historial del pedido
     */
    public function testGetOrderHistory() {
        echo "Test: Obtener historial del pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para obtener historial\n";
                return;
            }
            
            // Items del pedido
            $items = $this->db->fetchAll(
                "SELECT oi.*, 
                        p.name as product_name, p.sku as product_sku
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ? 
                 ORDER BY oi.created_at ASC",
                [$this->testOrderId]
            );
            
            // Pagos del pedido (simulado)
            $payments = $this->db->fetchAll(
                "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC",
                [$this->testOrderId]
            );
            
            if (is_array($items) && is_array($payments)) {
                echo "✓ PASSED (Items: " . count($items) . ", Pagos: " . count($payments) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvo historial correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar pedido
     */
    public function testDeleteOrder() {
        echo "Test: Eliminar pedido... ";
        
        try {
            if (!$this->testOrderId) {
                echo "✗ FAILED - No hay pedido para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM orders WHERE id = ?";
            $this->db->query($sql, [$this->testOrderId]);
            
            // Verificar que se eliminó
            $order = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$this->testOrderId]);
            if (!$order) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Pedido no se eliminó correctamente\n";
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
            // Eliminar datos de prueba
            if ($this->testQuotationId) {
                $this->db->query("DELETE FROM quotations WHERE id = ?", [$this->testQuotationId]);
            }
            if ($this->testProductId) {
                $this->db->query("DELETE FROM products WHERE id = ?", [$this->testProductId]);
            }
            if ($this->testCategoryId) {
                $this->db->query("DELETE FROM categories WHERE id = ?", [$this->testCategoryId]);
            }
            if ($this->testCustomerId) {
                $this->db->query("DELETE FROM customers WHERE id = ?", [$this->testCustomerId]);
            }
            if ($this->testUserId) {
                $this->db->query("DELETE FROM users WHERE id = ?", [$this->testUserId]);
            }
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de pedido (función auxiliar)
     */
    private function validateOrderData($data) {
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
        if (!empty($data['order_number']) && strlen($data['order_number']) > 50) {
            $errors['order_number'] = 'El número de pedido no puede tener más de 50 caracteres';
        }

        // Validar estado
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'El estado no es válido';
            }
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new OrderTest();
    $test->runAllTests();
}
