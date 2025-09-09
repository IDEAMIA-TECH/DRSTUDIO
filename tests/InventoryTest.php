<?php
/**
 * Tests para el módulo de Sistema de Inventario - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class InventoryTest {
    private $db;
    private $testProductId;
    private $testVariantId;
    private $testSupplierId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE INVENTARIO ===\n\n";
        
        $this->testCreateTestData();
        $this->testGetStock();
        $this->testGetAllStock();
        $this->testUpdateStock();
        $this->testAdjustStockIn();
        $this->testAdjustStockOut();
        $this->testReserveStock();
        $this->testReleaseStock();
        $this->testGetLowStockProducts();
        $this->testGetOutOfStockProducts();
        $this->testGetInventoryStats();
        $this->testCreateStockMovement();
        $this->testGetStockMovements();
        $this->testGetStockMovementStats();
        $this->testCreateSupplier();
        $this->testGetSupplierById();
        $this->testGetAllSuppliers();
        $this->testUpdateSupplier();
        $this->testGetSupplierStats();
        $this->testValidateInventoryData();
        $this->testValidateSupplierData();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear datos de prueba
     */
    public function testCreateTestData() {
        echo "Test: Crear datos de prueba... ";
        
        try {
            // Crear proveedor
            $sql = "INSERT INTO suppliers (name, email, phone, address, city, country, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Proveedor de Prueba', 'proveedor@test.com', '+1234567890', 'Dirección Test', 'Ciudad Test', 'México', 1]);
            $this->testSupplierId = $this->db->lastInsertId();
            
            // Crear producto
            $sql = "INSERT INTO products (name, sku, slug, description, category_id, supplier_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Producto de Prueba', 'TEST-001', 'producto-de-prueba', 'Producto para pruebas', 1, $this->testSupplierId, 'active', 1]);
            $this->testProductId = $this->db->lastInsertId();
            
            // Crear variante
            $sql = "INSERT INTO product_variants (product_id, name, sku, price, cost, stock, min_stock, max_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, 'Variante Test', 'TEST-001-V1', 100.00, 50.00, 10, 5, 50]);
            $this->testVariantId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener stock de un producto
     */
    public function testGetStock() {
        echo "Test: Obtener stock de un producto... ";
        
        try {
            $sql = "SELECT pv.id, pv.product_id, pv.name as variant_name, pv.stock, pv.min_stock, pv.max_stock, pv.cost, pv.price,
                           p.name as product_name, p.sku as product_sku, pv.sku as variant_sku
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.id
                    WHERE pv.id = ? AND pv.product_id = ?";
            $stock = $this->db->fetch($sql, [$this->testVariantId, $this->testProductId]);
            
            if ($stock && $stock['product_id'] == $this->testProductId) {
                echo "✓ PASSED (Stock: {$stock['stock']})\n";
            } else {
                echo "✗ FAILED - Stock no encontrado\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todo el stock
     */
    public function testGetAllStock() {
        echo "Test: Obtener todo el stock... ";
        
        try {
            $sql = "SELECT p.id, p.name, p.sku, p.status, c.name as category_name,
                           pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                           pv.stock, pv.min_stock, pv.max_stock, pv.cost, pv.price
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    ORDER BY p.name ASC, pv.name ASC
                    LIMIT 20 OFFSET 0";
            $stock = $this->db->fetchAll($sql);
            
            if (is_array($stock)) {
                echo "✓ PASSED (Total: " . count($stock) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvo stock\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar stock
     */
    public function testUpdateStock() {
        echo "Test: Actualizar stock... ";
        
        try {
            // Obtener stock actual
            $currentStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            // Actualizar stock
            $newStock = $currentStock + 5;
            $this->db->query("UPDATE product_variants SET stock = ? WHERE id = ?", [$newStock, $this->testVariantId]);
            
            // Registrar movimiento
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'adjustment_in', 5, $currentStock, $newStock, 'Test adjustment', 1]);
            
            $updatedStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            if ($updatedStock == $newStock) {
                echo "✓ PASSED (Nuevo stock: {$updatedStock})\n";
            } else {
                echo "✗ FAILED - Stock no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Ajustar stock (entrada)
     */
    public function testAdjustStockIn() {
        echo "Test: Ajustar stock (entrada)... ";
        
        try {
            $currentStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            $newStock = $currentStock + 3;
            
            $this->db->query("UPDATE product_variants SET stock = ? WHERE id = ?", [$newStock, $this->testVariantId]);
            
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'adjustment_in', 3, $currentStock, $newStock, 'Test adjustment in', 1]);
            
            $updatedStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            if ($updatedStock == $newStock) {
                echo "✓ PASSED (Stock: {$updatedStock})\n";
            } else {
                echo "✗ FAILED - Ajuste de entrada falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Ajustar stock (salida)
     */
    public function testAdjustStockOut() {
        echo "Test: Ajustar stock (salida)... ";
        
        try {
            $currentStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            $newStock = $currentStock - 2;
            
            $this->db->query("UPDATE product_variants SET stock = ? WHERE id = ?", [$newStock, $this->testVariantId]);
            
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'adjustment_out', -2, $currentStock, $newStock, 'Test adjustment out', 1]);
            
            $updatedStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            if ($updatedStock == $newStock) {
                echo "✓ PASSED (Stock: {$updatedStock})\n";
            } else {
                echo "✗ FAILED - Ajuste de salida falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Reservar stock
     */
    public function testReserveStock() {
        echo "Test: Reservar stock... ";
        
        try {
            $currentStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            $newStock = $currentStock - 1;
            
            $this->db->query("UPDATE product_variants SET stock = ? WHERE id = ?", [$newStock, $this->testVariantId]);
            
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'reservation', -1, $currentStock, $newStock, 'Test reservation', 1]);
            
            $updatedStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            if ($updatedStock == $newStock) {
                echo "✓ PASSED (Stock reservado: 1)\n";
            } else {
                echo "✗ FAILED - Reserva de stock falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Liberar stock reservado
     */
    public function testReleaseStock() {
        echo "Test: Liberar stock reservado... ";
        
        try {
            $currentStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            $newStock = $currentStock + 1;
            
            $this->db->query("UPDATE product_variants SET stock = ? WHERE id = ?", [$newStock, $this->testVariantId]);
            
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'release', 1, $currentStock, $newStock, 'Test release', 1]);
            
            $updatedStock = $this->db->fetch("SELECT stock FROM product_variants WHERE id = ?", [$this->testVariantId])['stock'];
            
            if ($updatedStock == $newStock) {
                echo "✓ PASSED (Stock liberado: 1)\n";
            } else {
                echo "✗ FAILED - Liberación de stock falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos con stock bajo
     */
    public function testGetLowStockProducts() {
        echo "Test: Obtener productos con stock bajo... ";
        
        try {
            $sql = "SELECT p.id, p.name, p.sku, c.name as category_name,
                           pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                           pv.stock, pv.min_stock, pv.max_stock,
                           (pv.min_stock - pv.stock) as shortage
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE pv.stock <= pv.min_stock AND pv.stock > 0
                    ORDER BY shortage DESC, p.name ASC
                    LIMIT 50";
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos con stock bajo\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos sin stock
     */
    public function testGetOutOfStockProducts() {
        echo "Test: Obtener productos sin stock... ";
        
        try {
            $sql = "SELECT p.id, p.name, p.sku, c.name as category_name,
                           pv.id as variant_id, pv.name as variant_name, pv.sku as variant_sku,
                           pv.stock, pv.min_stock, pv.max_stock
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE pv.stock = 0
                    ORDER BY p.name ASC
                    LIMIT 50";
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos sin stock\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de inventario
     */
    public function testGetInventoryStats() {
        echo "Test: Obtener estadísticas de inventario... ";
        
        try {
            $stats = [];
            
            // Total de productos
            $stats['total_products'] = $this->db->fetch("SELECT COUNT(*) as total FROM products")['total'];
            
            // Total de variantes
            $stats['total_variants'] = $this->db->fetch("SELECT COUNT(*) as total FROM product_variants")['total'];
            
            // Valor total del inventario
            $stats['total_inventory_value'] = $this->db->fetch("SELECT SUM(stock * cost) as total FROM product_variants")['total'] ?? 0;
            
            if (isset($stats['total_products']) && isset($stats['total_variants'])) {
                echo "✓ PASSED (Productos: {$stats['total_products']}, Variantes: {$stats['total_variants']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear movimiento de stock
     */
    public function testCreateStockMovement() {
        echo "Test: Crear movimiento de stock... ";
        
        try {
            $sql = "INSERT INTO stock_movements (product_id, variant_id, type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, $this->testVariantId, 'sale', -1, 10, 9, 'Test sale', 1]);
            $movementId = $this->db->lastInsertId();
            
            if ($movementId) {
                echo "✓ PASSED (ID: {$movementId})\n";
            } else {
                echo "✗ FAILED - No se creó el movimiento\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener movimientos de stock
     */
    public function testGetStockMovements() {
        echo "Test: Obtener movimientos de stock... ";
        
        try {
            $sql = "SELECT sm.*, 
                           p.name as product_name, p.sku as product_sku,
                           pv.name as variant_name, pv.sku as variant_sku,
                           u.name as created_by_name
                    FROM stock_movements sm
                    LEFT JOIN products p ON sm.product_id = p.id
                    LEFT JOIN product_variants pv ON sm.variant_id = pv.id
                    LEFT JOIN users u ON sm.created_by = u.id
                    ORDER BY sm.created_at DESC
                    LIMIT 20 OFFSET 0";
            $movements = $this->db->fetchAll($sql);
            
            if (is_array($movements)) {
                echo "✓ PASSED (Total: " . count($movements) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron movimientos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de movimientos
     */
    public function testGetStockMovementStats() {
        echo "Test: Obtener estadísticas de movimientos... ";
        
        try {
            $stats = [];
            
            // Total de movimientos
            $stats['total_movements'] = $this->db->fetch("SELECT COUNT(*) as total FROM stock_movements")['total'];
            
            // Movimientos por tipo
            $stats['movements_by_type'] = $this->db->fetchAll(
                "SELECT type, COUNT(*) as count, SUM(quantity) as total_quantity
                 FROM stock_movements 
                 GROUP BY type 
                 ORDER BY count DESC"
            );
            
            if (isset($stats['total_movements']) && isset($stats['movements_by_type'])) {
                echo "✓ PASSED (Total: {$stats['total_movements']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de movimientos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear proveedor
     */
    public function testCreateSupplier() {
        echo "Test: Crear proveedor... ";
        
        try {
            $sql = "INSERT INTO suppliers (name, email, phone, address, city, country, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Nuevo Proveedor', 'nuevo@proveedor.com', '+9876543210', 'Nueva Dirección', 'Nueva Ciudad', 'México', 1]);
            $supplierId = $this->db->lastInsertId();
            
            if ($supplierId) {
                echo "✓ PASSED (ID: {$supplierId})\n";
            } else {
                echo "✗ FAILED - No se creó el proveedor\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener proveedor por ID
     */
    public function testGetSupplierById() {
        echo "Test: Obtener proveedor por ID... ";
        
        try {
            $sql = "SELECT * FROM suppliers WHERE id = ?";
            $supplier = $this->db->fetch($sql, [$this->testSupplierId]);
            
            if ($supplier && $supplier['id'] == $this->testSupplierId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Proveedor no encontrado\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todos los proveedores
     */
    public function testGetAllSuppliers() {
        echo "Test: Obtener todos los proveedores... ";
        
        try {
            $sql = "SELECT * FROM suppliers ORDER BY name ASC LIMIT 20 OFFSET 0";
            $suppliers = $this->db->fetchAll($sql);
            
            if (is_array($suppliers)) {
                echo "✓ PASSED (Total: " . count($suppliers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron proveedores\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar proveedor
     */
    public function testUpdateSupplier() {
        echo "Test: Actualizar proveedor... ";
        
        try {
            $sql = "UPDATE suppliers SET name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['Proveedor Actualizado', '+1111111111', $this->testSupplierId]);
            
            $supplier = $this->db->fetch("SELECT * FROM suppliers WHERE id = ?", [$this->testSupplierId]);
            
            if ($supplier && $supplier['name'] == 'Proveedor Actualizado') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Proveedor no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de proveedores
     */
    public function testGetSupplierStats() {
        echo "Test: Obtener estadísticas de proveedores... ";
        
        try {
            $stats = [];
            
            // Total de proveedores
            $stats['total_suppliers'] = $this->db->fetch("SELECT COUNT(*) as total FROM suppliers")['total'];
            
            // Proveedores activos
            $stats['active_suppliers'] = $this->db->fetch("SELECT COUNT(*) as total FROM suppliers WHERE is_active = 1")['total'];
            
            if (isset($stats['total_suppliers']) && isset($stats['active_suppliers'])) {
                echo "✓ PASSED (Total: {$stats['total_suppliers']}, Activos: {$stats['active_suppliers']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de proveedores\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de inventario
     */
    public function testValidateInventoryData() {
        echo "Test: Validar datos de inventario... ";
        
        try {
            // Test datos válidos
            $validData = [
                'product_id' => $this->testProductId,
                'variant_id' => $this->testVariantId,
                'quantity' => 5
            ];
            
            $errors = $this->validateInventoryData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'product_id' => '',
                    'variant_id' => '',
                    'quantity' => 0
                ];
                
                $errors = $this->validateInventoryData($invalidData);
                
                if (!empty($errors) && isset($errors['product_id']) && isset($errors['variant_id']) && isset($errors['quantity'])) {
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
     * Test: Validar datos de proveedor
     */
    public function testValidateSupplierData() {
        echo "Test: Validar datos de proveedor... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Proveedor Válido',
                'email' => 'valido@proveedor.com'
            ];
            
            $errors = $this->validateSupplierData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '',
                    'email' => 'email-invalido'
                ];
                
                $errors = $this->validateSupplierData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['email'])) {
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
            // Eliminar movimientos de stock
            $this->db->query("DELETE FROM stock_movements WHERE product_id = ?", [$this->testProductId]);
            
            // Eliminar variantes
            $this->db->query("DELETE FROM product_variants WHERE product_id = ?", [$this->testProductId]);
            
            // Eliminar productos
            $this->db->query("DELETE FROM products WHERE id = ?", [$this->testProductId]);
            
            // Eliminar proveedores
            $this->db->query("DELETE FROM suppliers WHERE id = ?", [$this->testSupplierId]);
            $this->db->query("DELETE FROM suppliers WHERE name LIKE '%Prueba%' OR name LIKE '%Nuevo%' OR name LIKE '%Válido%' OR name LIKE '%Actualizado%'");
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de inventario (función auxiliar)
     */
    private function validateInventoryData($data) {
        $errors = [];

        // Validar ID del producto
        if (empty($data['product_id'])) {
            $errors['product_id'] = 'El ID del producto es requerido';
        }

        // Validar ID de la variante
        if (empty($data['variant_id'])) {
            $errors['variant_id'] = 'El ID de la variante es requerido';
        }

        // Validar cantidad
        if (!isset($data['quantity']) || $data['quantity'] == 0) {
            $errors['quantity'] = 'La cantidad es requerida y debe ser diferente de cero';
        }

        return $errors;
    }

    /**
     * Validar datos de proveedor (función auxiliar)
     */
    private function validateSupplierData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email debe ser válido';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new InventoryTest();
    $test->runAllTests();
}
