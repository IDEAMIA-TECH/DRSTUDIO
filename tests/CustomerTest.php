<?php
/**
 * Tests para el módulo de Clientes - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class CustomerTest {
    private $db;
    private $testCustomerId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE CLIENTES ===\n\n";
        
        $this->testCreateCustomer();
        $this->testGetCustomerById();
        $this->testGetCustomerByEmail();
        $this->testUpdateCustomer();
        $this->testValidateCustomer();
        $this->testGetAllCustomers();
        $this->testChangeCustomerStatus();
        $this->testSearchCustomers();
        $this->testGetMostActiveCustomers();
        $this->testGetCustomersByCity();
        $this->testGetCustomerStats();
        $this->testDuplicateCustomer();
        $this->testGetCustomerHistory();
        $this->testGetCustomersForSelect();
        $this->testDeleteCustomer();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear cliente
     */
    public function testCreateCustomer() {
        echo "Test: Crear cliente... ";
        
        try {
            $customerData = [
                'name' => 'Cliente de Prueba',
                'email' => 'cliente' . time() . '@example.com',
                'phone' => '5551234567',
                'company' => 'Empresa de Prueba',
                'address' => 'Calle de Prueba 123',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '01000',
                'country' => 'México',
                'notes' => 'Cliente de prueba para testing',
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO customers (name, email, phone, company, address, city, state, postal_code, country, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $customerData['name'],
                $customerData['email'],
                $customerData['phone'],
                $customerData['company'],
                $customerData['address'],
                $customerData['city'],
                $customerData['state'],
                $customerData['postal_code'],
                $customerData['country'],
                $customerData['notes'],
                $customerData['is_active']
            ]);
            
            $this->testCustomerId = $this->db->lastInsertId();
            
            if ($this->testCustomerId) {
                echo "✓ PASSED (ID: {$this->testCustomerId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del cliente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cliente por ID
     */
    public function testGetCustomerById() {
        echo "Test: Obtener cliente por ID... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                    FROM customers c 
                    WHERE c.id = ?";
            $customer = $this->db->fetch($sql, [$this->testCustomerId]);
            
            if ($customer && $customer['id'] == $this->testCustomerId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cliente no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cliente por email
     */
    public function testGetCustomerByEmail() {
        echo "Test: Obtener cliente por email... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                    FROM customers c 
                    WHERE c.email = ?";
            $customer = $this->db->fetch($sql, ['cliente' . time() . '@example.com']);
            
            if ($customer && $customer['email'] == 'cliente' . time() . '@example.com') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cliente no encontrado o email incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar cliente
     */
    public function testUpdateCustomer() {
        echo "Test: Actualizar cliente... ";
        
        try {
            if (!$this->testCustomerId) {
                echo "✗ FAILED - No hay cliente para actualizar\n";
                return;
            }
            
            $sql = "UPDATE customers SET name = ?, company = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['Cliente Actualizado', 'Empresa Actualizada', $this->testCustomerId]);
            
            // Verificar que se actualizó
            $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$this->testCustomerId]);
            if ($customer && $customer['name'] == 'Cliente Actualizado' && $customer['company'] == 'Empresa Actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de cliente
     */
    public function testValidateCustomer() {
        echo "Test: Validar datos de cliente... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Cliente Válido',
                'email' => 'valido@example.com',
                'phone' => '5551234567',
                'company' => 'Empresa Válida'
            ];
            
            $errors = $this->validateCustomerData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'email' => 'email-invalido', // Email inválido
                    'phone' => str_repeat('1', 25), // Teléfono muy largo
                    'company' => str_repeat('a', 300) // Empresa muy larga
                ];
                
                $errors = $this->validateCustomerData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['email']) && isset($errors['phone'])) {
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
     * Test: Obtener todos los clientes
     */
    public function testGetAllCustomers() {
        echo "Test: Obtener todos los clientes... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                    FROM customers c 
                    ORDER BY c.created_at DESC";
            $customers = $this->db->fetchAll($sql);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado del cliente
     */
    public function testChangeCustomerStatus() {
        echo "Test: Cambiar estado del cliente... ";
        
        try {
            if (!$this->testCustomerId) {
                echo "✗ FAILED - No hay cliente para cambiar estado\n";
                return;
            }
            
            // Cambiar a inactivo
            $sql = "UPDATE customers SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [0, $this->testCustomerId]);
            
            // Verificar que cambió
            $customer = $this->db->fetch("SELECT is_active FROM customers WHERE id = ?", [$this->testCustomerId]);
            if ($customer && $customer['is_active'] == 0) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Buscar clientes
     */
    public function testSearchCustomers() {
        echo "Test: Buscar clientes... ";
        
        try {
            $query = 'Cliente';
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                    FROM customers c 
                    WHERE (c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.phone LIKE ?) 
                    AND c.is_active = 1
                    ORDER BY c.name ASC 
                    LIMIT ?";
            $customers = $this->db->fetchAll($sql, ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%", 20]);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron resultados de búsqueda\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener clientes más activos
     */
    public function testGetMostActiveCustomers() {
        echo "Test: Obtener clientes más activos... ";
        
        try {
            $sql = "SELECT c.*, 
                           COUNT(o.id) as order_count,
                           SUM(o.total) as total_spent,
                           MAX(o.created_at) as last_order_date
                    FROM customers c 
                    LEFT JOIN orders o ON c.id = o.customer_id 
                    WHERE c.is_active = 1
                    GROUP BY c.id 
                    ORDER BY order_count DESC, total_spent DESC 
                    LIMIT ?";
            $customers = $this->db->fetchAll($sql, [10]);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron clientes activos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener clientes por ciudad
     */
    public function testGetCustomersByCity() {
        echo "Test: Obtener clientes por ciudad... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM quotations q WHERE q.customer_id = c.id) as quotation_count,
                           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count
                    FROM customers c 
                    WHERE c.city = ? AND c.is_active = 1
                    ORDER BY c.name ASC 
                    LIMIT ?";
            $customers = $this->db->fetchAll($sql, ['Ciudad de México', 20]);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron clientes por ciudad\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de clientes
     */
    public function testGetCustomerStats() {
        echo "Test: Obtener estadísticas de clientes... ";
        
        try {
            $stats = [];
            
            // Total de clientes
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM customers")['count'];
            
            // Clientes activos
            $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM customers WHERE is_active = 1")['count'];
            
            // Clientes inactivos
            $stats['inactive'] = $stats['total'] - $stats['active'];
            
            // Clientes por ciudad
            $stats['by_city'] = $this->db->fetchAll(
                "SELECT city, COUNT(*) as customer_count 
                 FROM customers 
                 WHERE city IS NOT NULL AND city != '' 
                 GROUP BY city 
                 ORDER BY customer_count DESC 
                 LIMIT 10"
            );
            
            if (isset($stats['total']) && isset($stats['active']) && isset($stats['inactive'])) {
                echo "✓ PASSED (Total: {$stats['total']}, Activos: {$stats['active']})\n";
            } else {
                echo "✗ FAILED - Estructura de estadísticas incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Duplicar cliente
     */
    public function testDuplicateCustomer() {
        echo "Test: Duplicar cliente... ";
        
        try {
            $newEmail = 'cliente-duplicado' . time() . '@example.com';
            
            $sql = "INSERT INTO customers (name, email, phone, company, address, city, state, postal_code, country, notes, is_active) 
                    SELECT CONCAT(name, ' (Copia)'), ?, phone, company, address, city, state, postal_code, country, CONCAT(notes, ' (Copia)'), 1 
                    FROM customers WHERE id = ?";
            $this->db->query($sql, [$newEmail, $this->testCustomerId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó el cliente duplicado
                $duplicated = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['email'] == $newEmail) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Cliente duplicado no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar el cliente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener historial del cliente
     */
    public function testGetCustomerHistory() {
        echo "Test: Obtener historial del cliente... ";
        
        try {
            if (!$this->testCustomerId) {
                echo "✗ FAILED - No hay cliente para obtener historial\n";
                return;
            }
            
            // Cotizaciones
            $quotations = $this->db->fetchAll(
                "SELECT q.*, u.name as created_by_name 
                 FROM quotations q 
                 LEFT JOIN users u ON q.user_id = u.id 
                 WHERE q.customer_id = ? 
                 ORDER BY q.created_at DESC",
                [$this->testCustomerId]
            );
            
            // Pedidos
            $orders = $this->db->fetchAll(
                "SELECT o.* 
                 FROM orders o 
                 WHERE o.customer_id = ? 
                 ORDER BY o.created_at DESC",
                [$this->testCustomerId]
            );
            
            if (is_array($quotations) && is_array($orders)) {
                echo "✓ PASSED (Cotizaciones: " . count($quotations) . ", Pedidos: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvo historial correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener clientes para select
     */
    public function testGetCustomersForSelect() {
        echo "Test: Obtener clientes para select... ";
        
        try {
            $sql = "SELECT id, name, email, company FROM customers 
                    WHERE is_active = 1 
                    ORDER BY name ASC";
            $customers = $this->db->fetchAll($sql);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron clientes para select\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar cliente
     */
    public function testDeleteCustomer() {
        echo "Test: Eliminar cliente... ";
        
        try {
            if (!$this->testCustomerId) {
                echo "✗ FAILED - No hay cliente para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM customers WHERE id = ?";
            $this->db->query($sql, [$this->testCustomerId]);
            
            // Verificar que se eliminó
            $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$this->testCustomerId]);
            if (!$customer) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cliente no se eliminó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de cliente (función auxiliar)
     */
    private function validateCustomerData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        } elseif (strlen($data['email']) > 255) {
            $errors['email'] = 'El email no puede tener más de 255 caracteres';
        }

        // Validar teléfono
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 20) {
                $errors['phone'] = 'El teléfono no puede tener más de 20 caracteres';
            }
        }

        // Validar empresa
        if (!empty($data['company']) && strlen($data['company']) > 255) {
            $errors['company'] = 'La empresa no puede tener más de 255 caracteres';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new CustomerTest();
    $test->runAllTests();
}
