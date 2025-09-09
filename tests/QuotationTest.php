<?php
/**
 * Tests para el módulo de Cotizaciones - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class QuotationTest {
    private $db;
    private $testQuotationId;
    private $testCustomerId;
    private $testUserId;
    private $testProductId;
    private $testCategoryId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE COTIZACIONES ===\n\n";
        
        $this->testCreateTestData();
        $this->testCreateQuotation();
        $this->testGetQuotationById();
        $this->testGetQuotationByNumber();
        $this->testUpdateQuotation();
        $this->testValidateQuotation();
        $this->testGetAllQuotations();
        $this->testChangeQuotationStatus();
        $this->testGetQuotationsByCustomer();
        $this->testGetQuotationsByUser();
        $this->testGetQuotationStats();
        $this->testDuplicateQuotation();
        $this->testAddQuotationItem();
        $this->testUpdateQuotationItem();
        $this->testGetQuotationItems();
        $this->testDeleteQuotationItem();
        $this->testDeleteQuotation();
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
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear cotización
     */
    public function testCreateQuotation() {
        echo "Test: Crear cotización... ";
        
        try {
            $quotationData = [
                'customer_id' => $this->testCustomerId,
                'user_id' => $this->testUserId,
                'quotation_number' => 'COT-TEST-001',
                'status' => 'draft',
                'subtotal' => 100.00,
                'tax_rate' => 16.00,
                'tax_amount' => 16.00,
                'total' => 116.00,
                'valid_until' => date('Y-m-d', strtotime('+30 days')),
                'notes' => 'Cotización de prueba'
            ];
            
            $sql = "INSERT INTO quotations (customer_id, user_id, quotation_number, status, subtotal, tax_rate, tax_amount, total, valid_until, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $quotationData['customer_id'],
                $quotationData['user_id'],
                $quotationData['quotation_number'],
                $quotationData['status'],
                $quotationData['subtotal'],
                $quotationData['tax_rate'],
                $quotationData['tax_amount'],
                $quotationData['total'],
                $quotationData['valid_until'],
                $quotationData['notes']
            ]);
            
            $this->testQuotationId = $this->db->lastInsertId();
            
            if ($this->testQuotationId) {
                echo "✓ PASSED (ID: {$this->testQuotationId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID de la cotización\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cotización por ID
     */
    public function testGetQuotationById() {
        echo "Test: Obtener cotización por ID... ";
        
        try {
            $sql = "SELECT q.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name
                    FROM quotations q 
                    LEFT JOIN customers c ON q.customer_id = c.id
                    LEFT JOIN users u ON q.user_id = u.id
                    WHERE q.id = ?";
            $quotation = $this->db->fetch($sql, [$this->testQuotationId]);
            
            if ($quotation && $quotation['id'] == $this->testQuotationId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cotización no encontrada o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cotización por número
     */
    public function testGetQuotationByNumber() {
        echo "Test: Obtener cotización por número... ";
        
        try {
            $sql = "SELECT q.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name
                    FROM quotations q 
                    LEFT JOIN customers c ON q.customer_id = c.id
                    LEFT JOIN users u ON q.user_id = u.id
                    WHERE q.quotation_number = ?";
            $quotation = $this->db->fetch($sql, ['COT-TEST-001']);
            
            if ($quotation && $quotation['quotation_number'] == 'COT-TEST-001') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cotización no encontrada o número incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar cotización
     */
    public function testUpdateQuotation() {
        echo "Test: Actualizar cotización... ";
        
        try {
            if (!$this->testQuotationId) {
                echo "✗ FAILED - No hay cotización para actualizar\n";
                return;
            }
            
            $sql = "UPDATE quotations SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['sent', 'Cotización actualizada', $this->testQuotationId]);
            
            // Verificar que se actualizó
            $quotation = $this->db->fetch("SELECT * FROM quotations WHERE id = ?", [$this->testQuotationId]);
            if ($quotation && $quotation['status'] == 'sent' && $quotation['notes'] == 'Cotización actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de cotización
     */
    public function testValidateQuotation() {
        echo "Test: Validar datos de cotización... ";
        
        try {
            // Test datos válidos
            $validData = [
                'customer_id' => $this->testCustomerId,
                'user_id' => $this->testUserId,
                'quotation_number' => 'COT-VALID-001',
                'status' => 'draft'
            ];
            
            $errors = $this->validateQuotationData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'customer_id' => '', // Cliente vacío
                    'user_id' => 999, // Usuario inexistente
                    'quotation_number' => str_repeat('a', 60), // Número muy largo
                    'status' => 'invalid_status' // Estado inválido
                ];
                
                $errors = $this->validateQuotationData($invalidData);
                
                if (!empty($errors) && isset($errors['customer_id']) && isset($errors['user_id']) && isset($errors['status'])) {
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
     * Test: Obtener todas las cotizaciones
     */
    public function testGetAllQuotations() {
        echo "Test: Obtener todas las cotizaciones... ";
        
        try {
            $sql = "SELECT q.*, 
                           c.name as customer_name, c.email as customer_email,
                           u.name as user_name
                    FROM quotations q 
                    LEFT JOIN customers c ON q.customer_id = c.id
                    LEFT JOIN users u ON q.user_id = u.id
                    ORDER BY q.created_at DESC";
            $quotations = $this->db->fetchAll($sql);
            
            if (is_array($quotations)) {
                echo "✓ PASSED (Total: " . count($quotations) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado de la cotización
     */
    public function testChangeQuotationStatus() {
        echo "Test: Cambiar estado de la cotización... ";
        
        try {
            if (!$this->testQuotationId) {
                echo "✗ FAILED - No hay cotización para cambiar estado\n";
                return;
            }
            
            // Cambiar a aprobada
            $sql = "UPDATE quotations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['approved', $this->testQuotationId]);
            
            // Verificar que cambió
            $quotation = $this->db->fetch("SELECT status FROM quotations WHERE id = ?", [$this->testQuotationId]);
            if ($quotation && $quotation['status'] == 'approved') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cotizaciones por cliente
     */
    public function testGetQuotationsByCustomer() {
        echo "Test: Obtener cotizaciones por cliente... ";
        
        try {
            $sql = "SELECT q.*, 
                           u.name as user_name
                    FROM quotations q 
                    LEFT JOIN users u ON q.user_id = u.id
                    WHERE q.customer_id = ? 
                    ORDER BY q.created_at DESC 
                    LIMIT ?";
            $quotations = $this->db->fetchAll($sql, [$this->testCustomerId, 20]);
            
            if (is_array($quotations)) {
                echo "✓ PASSED (Total: " . count($quotations) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron cotizaciones por cliente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cotizaciones por usuario
     */
    public function testGetQuotationsByUser() {
        echo "Test: Obtener cotizaciones por usuario... ";
        
        try {
            $sql = "SELECT q.*, 
                           c.name as customer_name, c.email as customer_email
                    FROM quotations q 
                    LEFT JOIN customers c ON q.customer_id = c.id
                    WHERE q.user_id = ? 
                    ORDER BY q.created_at DESC 
                    LIMIT ?";
            $quotations = $this->db->fetchAll($sql, [$this->testUserId, 20]);
            
            if (is_array($quotations)) {
                echo "✓ PASSED (Total: " . count($quotations) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron cotizaciones por usuario\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de cotizaciones
     */
    public function testGetQuotationStats() {
        echo "Test: Obtener estadísticas de cotizaciones... ";
        
        try {
            $stats = [];
            
            // Total de cotizaciones
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM quotations")['count'];
            
            // Cotizaciones por estado
            $stats['by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count 
                 FROM quotations 
                 GROUP BY status 
                 ORDER BY count DESC"
            );
            
            // Valor total de cotizaciones
            $stats['total_value'] = $this->db->fetch(
                "SELECT SUM(total) as total_value FROM quotations WHERE status != 'rejected'"
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
     * Test: Duplicar cotización
     */
    public function testDuplicateQuotation() {
        echo "Test: Duplicar cotización... ";
        
        try {
            $newNumber = 'COT-DUP-001';
            
            $sql = "INSERT INTO quotations (customer_id, user_id, quotation_number, status, subtotal, tax_rate, tax_amount, total, valid_until, notes) 
                    SELECT customer_id, user_id, ?, 'draft', subtotal, tax_rate, tax_amount, total, valid_until, CONCAT(notes, ' (Copia)') 
                    FROM quotations WHERE id = ?";
            $this->db->query($sql, [$newNumber, $this->testQuotationId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó la cotización duplicada
                $duplicated = $this->db->fetch("SELECT * FROM quotations WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['quotation_number'] == $newNumber) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Cotización duplicada no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar la cotización\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Agregar item a cotización
     */
    public function testAddQuotationItem() {
        echo "Test: Agregar item a cotización... ";
        
        try {
            if (!$this->testQuotationId) {
                echo "✗ FAILED - No hay cotización para agregar item\n";
                return;
            }
            
            $itemData = [
                'quotation_id' => $this->testQuotationId,
                'product_id' => $this->testProductId,
                'quantity' => 2,
                'unit_price' => 50.00,
                'total' => 100.00,
                'notes' => 'Item de prueba'
            ];
            
            $sql = "INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price, total, notes) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $itemData['quotation_id'],
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
     * Test: Actualizar item de cotización
     */
    public function testUpdateQuotationItem() {
        echo "Test: Actualizar item de cotización... ";
        
        try {
            // Obtener el primer item de la cotización
            $item = $this->db->fetch("SELECT * FROM quotation_items WHERE quotation_id = ? LIMIT 1", [$this->testQuotationId]);
            
            if (!$item) {
                echo "✗ FAILED - No hay item para actualizar\n";
                return;
            }
            
            $sql = "UPDATE quotation_items SET quantity = ?, unit_price = ?, total = ?, notes = ? WHERE id = ?";
            $this->db->query($sql, [3, 60.00, 180.00, 'Item actualizado', $item['id']]);
            
            // Verificar que se actualizó
            $updatedItem = $this->db->fetch("SELECT * FROM quotation_items WHERE id = ?", [$item['id']]);
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
     * Test: Obtener items de cotización
     */
    public function testGetQuotationItems() {
        echo "Test: Obtener items de cotización... ";
        
        try {
            if (!$this->testQuotationId) {
                echo "✗ FAILED - No hay cotización para obtener items\n";
                return;
            }
            
            $sql = "SELECT qi.*, 
                           p.name as product_name, p.sku as product_sku
                    FROM quotation_items qi 
                    LEFT JOIN products p ON qi.product_id = p.id
                    WHERE qi.quotation_id = ? 
                    ORDER BY qi.created_at ASC";
            $items = $this->db->fetchAll($sql, [$this->testQuotationId]);
            
            if (is_array($items)) {
                echo "✓ PASSED (Total: " . count($items) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron items de la cotización\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar item de cotización
     */
    public function testDeleteQuotationItem() {
        echo "Test: Eliminar item de cotización... ";
        
        try {
            // Obtener el primer item de la cotización
            $item = $this->db->fetch("SELECT * FROM quotation_items WHERE quotation_id = ? LIMIT 1", [$this->testQuotationId]);
            
            if (!$item) {
                echo "✗ FAILED - No hay item para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM quotation_items WHERE id = ?";
            $this->db->query($sql, [$item['id']]);
            
            // Verificar que se eliminó
            $deletedItem = $this->db->fetch("SELECT * FROM quotation_items WHERE id = ?", [$item['id']]);
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
     * Test: Eliminar cotización
     */
    public function testDeleteQuotation() {
        echo "Test: Eliminar cotización... ";
        
        try {
            if (!$this->testQuotationId) {
                echo "✗ FAILED - No hay cotización para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM quotations WHERE id = ?";
            $this->db->query($sql, [$this->testQuotationId]);
            
            // Verificar que se eliminó
            $quotation = $this->db->fetch("SELECT * FROM quotations WHERE id = ?", [$this->testQuotationId]);
            if (!$quotation) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cotización no se eliminó correctamente\n";
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
     * Validar datos de cotización (función auxiliar)
     */
    private function validateQuotationData($data) {
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
        if (!empty($data['quotation_number']) && strlen($data['quotation_number']) > 50) {
            $errors['quotation_number'] = 'El número de cotización no puede tener más de 50 caracteres';
        }

        // Validar estado
        if (isset($data['status'])) {
            $validStatuses = ['draft', 'sent', 'reviewed', 'approved', 'rejected', 'converted'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'El estado no es válido';
            }
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new QuotationTest();
    $test->runAllTests();
}
