<?php
/**
 * Tests para el módulo de Categorías - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class CategoryTest {
    private $db;
    private $testCategoryId;
    private $testSubcategoryId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE CATEGORÍAS ===\n\n";
        
        $this->testCreateCategory();
        $this->testGetCategoryById();
        $this->testGetCategoryBySlug();
        $this->testUpdateCategory();
        $this->testValidateCategory();
        $this->testGetAllCategories();
        $this->testGetForSelect();
        $this->testGetMainCategories();
        $this->testGetSubcategories();
        $this->testGetCategoryStats();
        $this->testDuplicateCategory();
        $this->testReorderCategories();
        $this->testDeleteCategory();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear categoría
     */
    public function testCreateCategory() {
        echo "Test: Crear categoría... ";
        
        try {
            $categoryData = [
                'name' => 'Categoría de Prueba',
                'slug' => 'categoria-de-prueba',
                'description' => 'Descripción de prueba',
                'parent_id' => null,
                'image' => 'test-image.jpg',
                'sort_order' => 1,
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO categories (name, slug, description, parent_id, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $categoryData['name'],
                $categoryData['slug'],
                $categoryData['description'],
                $categoryData['parent_id'],
                $categoryData['image'],
                $categoryData['sort_order'],
                $categoryData['is_active']
            ]);
            
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
     * Test: Obtener categoría por ID
     */
    public function testGetCategoryById() {
        echo "Test: Obtener categoría por ID... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
                           parent.name as parent_name
                    FROM categories c 
                    LEFT JOIN categories parent ON c.parent_id = parent.id
                    WHERE c.id = ?";
            $category = $this->db->fetch($sql, [$this->testCategoryId]);
            
            if ($category && $category['id'] == $this->testCategoryId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Categoría no encontrada o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener categoría por slug
     */
    public function testGetCategoryBySlug() {
        echo "Test: Obtener categoría por slug... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
                           parent.name as parent_name
                    FROM categories c 
                    LEFT JOIN categories parent ON c.parent_id = parent.id
                    WHERE c.slug = ?";
            $category = $this->db->fetch($sql, ['categoria-de-prueba']);
            
            if ($category && $category['slug'] == 'categoria-de-prueba') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Categoría no encontrada o slug incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar categoría
     */
    public function testUpdateCategory() {
        echo "Test: Actualizar categoría... ";
        
        try {
            $sql = "UPDATE categories SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['Categoría Actualizada', 'Descripción actualizada', $this->testCategoryId]);
            
            // Verificar que se actualizó
            $category = $this->db->fetch("SELECT * FROM categories WHERE id = ?", [$this->testCategoryId]);
            if ($category['name'] == 'Categoría Actualizada' && $category['description'] == 'Descripción actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de categoría
     */
    public function testValidateCategory() {
        echo "Test: Validar datos de categoría... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Categoría Válida',
                'slug' => 'categoria-valida',
                'description' => 'Descripción válida'
            ];
            
            $errors = $this->validateCategoryData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'slug' => 'invalid slug!', // Slug inválido
                    'description' => str_repeat('a', 1001) // Descripción muy larga
                ];
                
                $errors = $this->validateCategoryData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['slug']) && isset($errors['description'])) {
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
     * Test: Obtener todas las categorías
     */
    public function testGetAllCategories() {
        echo "Test: Obtener todas las categorías... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
                           parent.name as parent_name
                    FROM categories c 
                    LEFT JOIN categories parent ON c.parent_id = parent.id
                    ORDER BY c.sort_order ASC, c.name ASC";
            $categories = $this->db->fetchAll($sql);
            
            if (is_array($categories)) {
                echo "✓ PASSED (Total: " . count($categories) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener categorías para select
     */
    public function testGetForSelect() {
        echo "Test: Obtener categorías para select... ";
        
        try {
            $sql = "SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
            $categories = $this->db->fetchAll($sql);
            
            if (is_array($categories) && !empty($categories)) {
                echo "✓ PASSED (Total: " . count($categories) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron categorías\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener categorías principales
     */
    public function testGetMainCategories() {
        echo "Test: Obtener categorías principales... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
                    FROM categories c 
                    WHERE c.parent_id IS NULL AND c.is_active = 1
                    ORDER BY c.sort_order ASC, c.name ASC";
            $categories = $this->db->fetchAll($sql);
            
            if (is_array($categories)) {
                echo "✓ PASSED (Total: " . count($categories) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron categorías principales\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener subcategorías
     */
    public function testGetSubcategories() {
        echo "Test: Obtener subcategorías... ";
        
        try {
            // Crear subcategoría
            $sql = "INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Subcategoría de Prueba', 'subcategoria-de-prueba', 'Descripción de subcategoría', $this->testCategoryId, 1, 1]);
            $this->testSubcategoryId = $this->db->lastInsertId();
            
            // Obtener subcategorías
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
                    FROM categories c 
                    WHERE c.parent_id = ? AND c.is_active = 1
                    ORDER BY c.sort_order ASC, c.name ASC";
            $subcategories = $this->db->fetchAll($sql, [$this->testCategoryId]);
            
            if (is_array($subcategories) && count($subcategories) > 0) {
                echo "✓ PASSED (Total: " . count($subcategories) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron subcategorías\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de categorías
     */
    public function testGetCategoryStats() {
        echo "Test: Obtener estadísticas de categorías... ";
        
        try {
            $stats = [];
            
            // Total de categorías
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM categories")['count'];
            
            // Categorías activas
            $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'];
            
            // Categorías inactivas
            $stats['inactive'] = $stats['total'] - $stats['active'];
            
            // Categorías principales
            $stats['main'] = $this->db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id IS NULL")['count'];
            
            if (isset($stats['total']) && isset($stats['active']) && isset($stats['inactive']) && isset($stats['main'])) {
                echo "✓ PASSED (Total: {$stats['total']}, Activas: {$stats['active']})\n";
            } else {
                echo "✗ FAILED - Estructura de estadísticas incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Duplicar categoría
     */
    public function testDuplicateCategory() {
        echo "Test: Duplicar categoría... ";
        
        try {
            $newName = 'Categoría Duplicada';
            $newSlug = 'categoria-duplicada';
            
            $sql = "INSERT INTO categories (name, slug, description, parent_id, image, sort_order, is_active) 
                    SELECT ?, ?, description, parent_id, image, sort_order, 1 
                    FROM categories WHERE id = ?";
            $this->db->query($sql, [$newName, $newSlug, $this->testCategoryId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó la categoría duplicada
                $duplicated = $this->db->fetch("SELECT * FROM categories WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['name'] == $newName) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Categoría duplicada no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar la categoría\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Reordenar categorías
     */
    public function testReorderCategories() {
        echo "Test: Reordenar categorías... ";
        
        try {
            $this->db->beginTransaction();
            
            // Obtener categorías existentes
            $categories = $this->db->fetchAll("SELECT id FROM categories ORDER BY sort_order ASC");
            $categoryIds = array_column($categories, 'id');
            
            // Revertir el orden
            $reversedIds = array_reverse($categoryIds);
            
            // Actualizar orden
            foreach ($reversedIds as $index => $categoryId) {
                $this->db->query("UPDATE categories SET sort_order = ? WHERE id = ?", [$index + 1, $categoryId]);
            }
            
            $this->db->commit();
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            $this->db->rollback();
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar categoría
     */
    public function testDeleteCategory() {
        echo "Test: Eliminar categoría... ";
        
        try {
            // Primero eliminar subcategoría
            if ($this->testSubcategoryId) {
                $this->db->query("DELETE FROM categories WHERE id = ?", [$this->testSubcategoryId]);
            }
            
            // Luego eliminar categoría principal
            $sql = "DELETE FROM categories WHERE id = ?";
            $this->db->query($sql, [$this->testCategoryId]);
            
            // Verificar que se eliminó
            $category = $this->db->fetch("SELECT * FROM categories WHERE id = ?", [$this->testCategoryId]);
            if (!$category) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Categoría no se eliminó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de categoría (función auxiliar)
     */
    private function validateCategoryData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar slug
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $errors['slug'] = 'El slug solo puede contener letras minúsculas, números y guiones';
            } elseif (strlen($data['slug']) > 255) {
                $errors['slug'] = 'El slug no puede tener más de 255 caracteres';
            }
        }

        // Validar descripción
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La descripción no puede tener más de 1000 caracteres';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new CategoryTest();
    $test->runAllTests();
}
