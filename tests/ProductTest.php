<?php
/**
 * Tests para el módulo de Productos - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class ProductTest {
    private $db;
    private $testProductId;
    private $testCategoryId;
    private $testUserId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE PRODUCTOS ===\n\n";
        
        $this->testCreateCategory();
        $this->testCreateUser();
        $this->testCreateProduct();
        $this->testGetProductById();
        $this->testGetProductBySku();
        $this->testUpdateProduct();
        $this->testValidateProduct();
        $this->testGetAllProducts();
        $this->testChangeProductStatus();
        $this->testGetFeaturedProducts();
        $this->testGetProductsByCategory();
        $this->testSearchProducts();
        $this->testGetProductStats();
        $this->testDuplicateProduct();
        $this->testDeleteProduct();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear categoría de prueba
     */
    public function testCreateCategory() {
        echo "Test: Crear categoría de prueba... ";
        
        try {
            $sql = "INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Categoría de Prueba', 'categoria-de-prueba', 'Para pruebas', 1, 1]);
            $this->testCategoryId = $this->db->lastInsertId();
            
            if ($this->testCategoryId) {
                echo "✓ PASSED (ID: {$this->testCategoryId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID de la categoría\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear usuario de prueba
     */
    public function testCreateUser() {
        echo "Test: Crear usuario de prueba... ";
        
        try {
            // Usar email único con timestamp
            $email = 'test' . time() . '@example.com';
            $sql = "INSERT INTO users (name, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Usuario de Prueba', $email, password_hash('password123', PASSWORD_DEFAULT), 1, 1]);
            $this->testUserId = $this->db->lastInsertId();
            
            if ($this->testUserId) {
                echo "✓ PASSED (ID: {$this->testUserId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del usuario\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear producto
     */
    public function testCreateProduct() {
        echo "Test: Crear producto... ";
        
        try {
            $productData = [
                'name' => 'Producto de Prueba',
                'description' => 'Descripción del producto de prueba',
                'category_id' => $this->testCategoryId,
                'sku' => 'PROD-TEST-001',
                'status' => 'active',
                'meta_title' => 'Producto de Prueba - Meta',
                'meta_description' => 'Meta descripción del producto',
                'created_by' => $this->testUserId
            ];
            
            $sql = "INSERT INTO products (name, description, category_id, sku, status, meta_title, meta_description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $productData['name'],
                $productData['description'],
                $productData['category_id'],
                $productData['sku'],
                $productData['status'],
                $productData['meta_title'],
                $productData['meta_description'],
                $productData['created_by']
            ]);
            
            $this->testProductId = $this->db->lastInsertId();
            
            if ($this->testProductId) {
                echo "✓ PASSED (ID: {$this->testProductId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del producto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener producto por ID
     */
    public function testGetProductById() {
        echo "Test: Obtener producto por ID... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           u.name as created_by_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE p.id = ?";
            $product = $this->db->fetch($sql, [$this->testProductId]);
            
            if ($product && $product['id'] == $this->testProductId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Producto no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener producto por SKU
     */
    public function testGetProductBySku() {
        echo "Test: Obtener producto por SKU... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           u.name as created_by_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE p.sku = ?";
            $product = $this->db->fetch($sql, ['PROD-TEST-001']);
            
            if ($product && $product['sku'] == 'PROD-TEST-001') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Producto no encontrado o SKU incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar producto
     */
    public function testUpdateProduct() {
        echo "Test: Actualizar producto... ";
        
        try {
            if (!$this->testProductId) {
                echo "✗ FAILED - No hay producto para actualizar\n";
                return;
            }
            
            $sql = "UPDATE products SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['Producto Actualizado', 'Descripción actualizada', $this->testProductId]);
            
            // Verificar que se actualizó
            $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$this->testProductId]);
            if ($product && $product['name'] == 'Producto Actualizado' && $product['description'] == 'Descripción actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de producto
     */
    public function testValidateProduct() {
        echo "Test: Validar datos de producto... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Producto Válido',
                'sku' => 'PROD-VALID-001',
                'description' => 'Descripción válida',
                'category_id' => $this->testCategoryId
            ];
            
            $errors = $this->validateProductData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'sku' => 'invalid sku!', // SKU inválido
                    'description' => str_repeat('a', 2001), // Descripción muy larga
                    'category_id' => 999 // Categoría inexistente
                ];
                
                $errors = $this->validateProductData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['sku']) && isset($errors['description'])) {
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
     * Test: Obtener todos los productos
     */
    public function testGetAllProducts() {
        echo "Test: Obtener todos los productos... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           u.name as created_by_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.created_by = u.id
                    ORDER BY p.created_at DESC";
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado del producto
     */
    public function testChangeProductStatus() {
        echo "Test: Cambiar estado del producto... ";
        
        try {
            if (!$this->testProductId) {
                echo "✗ FAILED - No hay producto para cambiar estado\n";
                return;
            }
            
            // Cambiar a inactivo
            $sql = "UPDATE products SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['inactive', $this->testProductId]);
            
            // Verificar que cambió
            $product = $this->db->fetch("SELECT status FROM products WHERE id = ?", [$this->testProductId]);
            if ($product && $product['status'] == 'inactive') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos destacados
     */
    public function testGetFeaturedProducts() {
        echo "Test: Obtener productos destacados... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'active'
                    ORDER BY p.created_at DESC 
                    LIMIT ?";
            $products = $this->db->fetchAll($sql, [10]);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos destacados\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos por categoría
     */
    public function testGetProductsByCategory() {
        echo "Test: Obtener productos por categoría... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.category_id = ? AND p.status = 'active'
                    ORDER BY p.created_at DESC 
                    LIMIT ? OFFSET ?";
            $products = $this->db->fetchAll($sql, [$this->testCategoryId, 20, 0]);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos por categoría\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Buscar productos
     */
    public function testSearchProducts() {
        echo "Test: Buscar productos... ";
        
        try {
            $query = 'Producto';
            $sql = "SELECT p.*, 
                           c.name as category_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?) AND p.status = 'active'
                    ORDER BY p.name ASC 
                    LIMIT ?";
            $products = $this->db->fetchAll($sql, ["%{$query}%", "%{$query}%", "%{$query}%", 20]);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron resultados de búsqueda\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de productos
     */
    public function testGetProductStats() {
        echo "Test: Obtener estadísticas de productos... ";
        
        try {
            $stats = [];
            
            // Total de productos
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM products")['count'];
            
            // Productos activos
            $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'];
            
            // Productos inactivos
            $stats['inactive'] = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'inactive'")['count'];
            
            // Productos en borrador
            $stats['draft'] = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'draft'")['count'];
            
            if (isset($stats['total']) && isset($stats['active']) && isset($stats['inactive']) && isset($stats['draft'])) {
                echo "✓ PASSED (Total: {$stats['total']}, Activos: {$stats['active']})\n";
            } else {
                echo "✗ FAILED - Estructura de estadísticas incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Duplicar producto
     */
    public function testDuplicateProduct() {
        echo "Test: Duplicar producto... ";
        
        try {
            $newName = 'Producto Duplicado';
            $newSku = 'PROD-DUP-001';
            
            $sql = "INSERT INTO products (name, description, category_id, sku, status, meta_title, meta_description, created_by) 
                    SELECT ?, description, category_id, ?, 'draft', meta_title, meta_description, created_by 
                    FROM products WHERE id = ?";
            $this->db->query($sql, [$newName, $newSku, $this->testProductId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó el producto duplicado
                $duplicated = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['name'] == $newName) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Producto duplicado no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar el producto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar producto
     */
    public function testDeleteProduct() {
        echo "Test: Eliminar producto... ";
        
        try {
            $sql = "DELETE FROM products WHERE id = ?";
            $this->db->query($sql, [$this->testProductId]);
            
            // Verificar que se eliminó
            $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$this->testProductId]);
            if (!$product) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Producto no se eliminó correctamente\n";
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
            // Eliminar categoría de prueba
            if ($this->testCategoryId) {
                $this->db->query("DELETE FROM categories WHERE id = ?", [$this->testCategoryId]);
            }
            
            // Eliminar usuario de prueba
            if ($this->testUserId) {
                $this->db->query("DELETE FROM users WHERE id = ?", [$this->testUserId]);
            }
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de producto (función auxiliar)
     */
    private function validateProductData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar SKU
        if (!empty($data['sku'])) {
            if (!preg_match('/^[A-Z0-9-]+$/', $data['sku'])) {
                $errors['sku'] = 'El SKU solo puede contener letras mayúsculas, números y guiones';
            } elseif (strlen($data['sku']) > 100) {
                $errors['sku'] = 'El SKU no puede tener más de 100 caracteres';
            }
        }

        // Validar descripción
        if (!empty($data['description']) && strlen($data['description']) > 2000) {
            $errors['description'] = 'La descripción no puede tener más de 2000 caracteres';
        }

        // Validar categoría
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'La categoría es requerida';
        } else {
            $category = $this->db->fetch("SELECT id FROM categories WHERE id = ?", [$data['category_id']]);
            if (!$category) {
                $errors['category_id'] = 'La categoría seleccionada no existe';
            }
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ProductTest();
    $test->runAllTests();
}
