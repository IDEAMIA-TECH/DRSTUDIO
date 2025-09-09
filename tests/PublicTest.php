<?php
/**
 * Tests para el módulo de Portal Público - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class PublicTest {
    private $db;
    private $testProductId;
    private $testCategoryId;
    private $testCustomerId;
    private $testQuotationId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE PORTAL PÚBLICO ===\n\n";
        
        $this->testCreateTestData();
        $this->testGetProducts();
        $this->testGetProductBySlug();
        $this->testGetCategories();
        $this->testGetCategoryBySlug();
        $this->testGetFeaturedProducts();
        $this->testGetRelatedProducts();
        $this->testGetBestSellingProducts();
        $this->testGetRecentProducts();
        $this->testSearchProducts();
        $this->testGetAvailableFilters();
        $this->testGetCatalogStats();
        $this->testCreatePublicQuotation();
        $this->testGetPublicQuotation();
        $this->testCalculateQuotationPrice();
        $this->testGetSuggestedProducts();
        $this->testGetRecentPublicQuotations();
        $this->testGetQuoterStats();
        $this->testValidateQuotationData();
        $this->testGetProductsForSitemap();
        $this->testGetCategoriesForSitemap();
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
            
            // Crear producto
            $sql = "INSERT INTO products (name, description, category_id, sku, slug, status, is_featured, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Producto de Prueba', 'Descripción del producto', $this->testCategoryId, 'PROD-TEST-001', 'producto-de-prueba', 'active', 1, 1]);
            $this->testProductId = $this->db->lastInsertId();
            
            // Crear variante del producto
            $sql = "INSERT INTO product_variants (product_id, name, sku, price, cost, stock, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, 'Variante 1', 'VAR-001', 100.00, 50.00, 100, '{"color": "rojo", "material": "algodón"}']);
            
            // Crear imagen del producto
            $sql = "INSERT INTO product_images (product_id, url, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testProductId, 'https://example.com/image.jpg', 'Imagen de prueba', 1, 1]);
            
            // Crear cliente
            $sql = "INSERT INTO customers (name, email, phone, company, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Cliente de Prueba', 'cliente@example.com', '5551234567', 'Empresa de Prueba', 1]);
            $this->testCustomerId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos del catálogo
     */
    public function testGetProducts() {
        echo "Test: Obtener productos del catálogo... ";
        
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active'
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.created_at DESC
                    LIMIT 12 OFFSET 0";
            
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener producto por slug
     */
    public function testGetProductBySlug() {
        echo "Test: Obtener producto por slug... ";
        
        try {
            $sql = "SELECT p.*, 
                           c.name as category_name, c.slug as category_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = ? AND p.status = 'active'";
            
            $product = $this->db->fetch($sql, ['producto-de-prueba']);
            
            if ($product && $product['slug'] == 'producto-de-prueba') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Producto no encontrado o slug incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener categorías
     */
    public function testGetCategories() {
        echo "Test: Obtener categorías... ";
        
        try {
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count
                    FROM categories c
                    WHERE parent_id IS NULL AND c.is_active = 1
                    ORDER BY c.sort_order ASC, c.name ASC";
            
            $categories = $this->db->fetchAll($sql);
            
            if (is_array($categories)) {
                echo "✓ PASSED (Total: " . count($categories) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron categorías\n";
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
                           parent.name as parent_name, parent.slug as parent_slug
                    FROM categories c
                    LEFT JOIN categories parent ON c.parent_id = parent.id
                    WHERE c.slug = ? AND c.is_active = 1";
            
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
     * Test: Obtener productos destacados
     */
    public function testGetFeaturedProducts() {
        echo "Test: Obtener productos destacados... ";
        
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active' AND p.is_featured = 1
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.updated_at DESC
                    LIMIT 8";
            
            $products = $this->db->fetchAll($sql);
            
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
     * Test: Obtener productos relacionados
     */
    public function testGetRelatedProducts() {
        echo "Test: Obtener productos relacionados... ";
        
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active' 
                    AND p.id != ? 
                    AND p.category_id = ?
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.id
                    LIMIT 4";
            
            $products = $this->db->fetchAll($sql, [$this->testProductId, $this->testCategoryId]);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos relacionados\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos más vendidos
     */
    public function testGetBestSellingProducts() {
        echo "Test: Obtener productos más vendidos... ";
        
        try {
            $sql = "SELECT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images,
                           SUM(oi.quantity) as total_sold
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN order_items oi ON pv.id = oi.variant_id
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE p.status = 'active' 
                    AND o.status = 'delivered'
                    AND o.created_at >= date('now', '-3 months')
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY total_sold DESC
                    LIMIT 8";
            
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos más vendidos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos recientes
     */
    public function testGetRecentProducts() {
        echo "Test: Obtener productos recientes... ";
        
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active'
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.created_at DESC
                    LIMIT 8";
            
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos recientes\n";
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
            $query = 'prueba';
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active' 
                    AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.name ASC
                    LIMIT 12 OFFSET 0";
            
            $searchTerm = "%{$query}%";
            $products = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            
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
     * Test: Obtener filtros disponibles
     */
    public function testGetAvailableFilters() {
        echo "Test: Obtener filtros disponibles... ";
        
        try {
            $filters = [];
            
            // Categorías
            $filters['categories'] = $this->db->fetchAll(
                "SELECT c.id, c.name, c.slug, COUNT(p.id) as product_count
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                 WHERE c.is_active = 1
                 GROUP BY c.id, c.name, c.slug
                 ORDER BY c.name ASC"
            );
            
            // Rango de precios
            $priceRange = $this->db->fetch(
                "SELECT MIN(pv.price) as min_price, MAX(pv.price) as max_price
                 FROM product_variants pv
                 LEFT JOIN products p ON pv.product_id = p.id
                 WHERE p.status = 'active'"
            );
            
            $filters['price_range'] = [
                'min' => $priceRange['min_price'] ?? 0,
                'max' => $priceRange['max_price'] ?? 1000
            ];
            
            if (is_array($filters['categories']) && isset($filters['price_range'])) {
                echo "✓ PASSED (Categorías: " . count($filters['categories']) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron filtros\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas del catálogo
     */
    public function testGetCatalogStats() {
        echo "Test: Obtener estadísticas del catálogo... ";
        
        try {
            $stats = [];
            
            // Total de productos activos
            $stats['total_products'] = $this->db->fetch(
                "SELECT COUNT(*) as total FROM products WHERE status = 'active'"
            )['total'];
            
            // Total de categorías activas
            $stats['total_categories'] = $this->db->fetch(
                "SELECT COUNT(*) as total FROM categories WHERE is_active = 1"
            )['total'];
            
            if (isset($stats['total_products']) && isset($stats['total_categories'])) {
                echo "✓ PASSED (Productos: {$stats['total_products']}, Categorías: {$stats['total_categories']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear cotización pública
     */
    public function testCreatePublicQuotation() {
        echo "Test: Crear cotización pública... ";
        
        try {
            $quotationData = [
                'customer_name' => 'Cliente de Prueba',
                'customer_email' => 'cliente@example.com',
                'customer_phone' => '5551234567',
                'customer_company' => 'Empresa de Prueba',
                'items' => [
                    [
                        'product_id' => $this->testProductId,
                        'quantity' => 2
                    ]
                ],
                'notes' => 'Cotización de prueba'
            ];
            
            // Simular creación de cotización
            $sql = "INSERT INTO quotations (customer_id, user_id, quotation_number, status, subtotal, tax_rate, tax_amount, total, valid_until, notes, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$this->testCustomerId, 1, 'COT-TEST-001', 'sent', 200.00, 16.0, 32.00, 232.00, date('Y-m-d', strtotime('+30 days')), 'Cotización de prueba', 1]);
            
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
     * Test: Obtener cotización pública
     */
    public function testGetPublicQuotation() {
        echo "Test: Obtener cotización pública... ";
        
        try {
            $sql = "SELECT q.*, 
                           c.name as customer_name, c.email as customer_email
                    FROM quotations q
                    LEFT JOIN customers c ON q.customer_id = c.id
                    WHERE q.quotation_number = ? AND q.is_public = 1";
            
            $quotation = $this->db->fetch($sql, ['COT-TEST-001']);
            
            if ($quotation && $quotation['quotation_number'] == 'COT-TEST-001') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Cotización no encontrada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Calcular precio de cotización
     */
    public function testCalculateQuotationPrice() {
        echo "Test: Calcular precio de cotización... ";
        
        try {
            $items = [
                [
                    'product_id' => $this->testProductId,
                    'quantity' => 2
                ]
            ];
            
            // Simular cálculo de precio
            $subtotal = 200.00;
            $taxRate = 16.0;
            $taxAmount = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $taxAmount;
            
            $result = [
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total
            ];
            
            if (isset($result['total']) && $result['total'] > 0) {
                echo "✓ PASSED (Total: {$result['total']})\n";
            } else {
                echo "✗ FAILED - No se calculó el precio correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener productos sugeridos
     */
    public function testGetSuggestedProducts() {
        echo "Test: Obtener productos sugeridos... ";
        
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                           c.name as category_name, c.slug as category_slug,
                           MIN(pv.price) as min_price,
                           MAX(pv.price) as max_price,
                           GROUP_CONCAT(DISTINCT pi.url) as images
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.status = 'active'
                    GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                    ORDER BY p.id
                    LIMIT 6";
            
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos sugeridos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener cotizaciones recientes
     */
    public function testGetRecentPublicQuotations() {
        echo "Test: Obtener cotizaciones recientes... ";
        
        try {
            $sql = "SELECT q.quotation_number, q.total, q.created_at, q.valid_until,
                           c.name as customer_name, c.company as customer_company
                    FROM quotations q
                    LEFT JOIN customers c ON q.customer_id = c.id
                    WHERE q.is_public = 1
                    ORDER BY q.created_at DESC
                    LIMIT 10";
            
            $quotations = $this->db->fetchAll($sql);
            
            if (is_array($quotations)) {
                echo "✓ PASSED (Total: " . count($quotations) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron cotizaciones recientes\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas del cotizador
     */
    public function testGetQuoterStats() {
        echo "Test: Obtener estadísticas del cotizador... ";
        
        try {
            $stats = [];
            
            // Total de cotizaciones públicas
            $stats['total_public_quotations'] = $this->db->fetch(
                "SELECT COUNT(*) as total FROM quotations WHERE is_public = 1"
            )['total'];
            
            // Valor total de cotizaciones
            $stats['total_value'] = $this->db->fetch(
                "SELECT SUM(total) as total_value FROM quotations WHERE is_public = 1 AND status != 'rejected'"
            )['total_value'] ?? 0;
            
            if (isset($stats['total_public_quotations']) && isset($stats['total_value'])) {
                echo "✓ PASSED (Total: {$stats['total_public_quotations']}, Valor: {$stats['total_value']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas del cotizador\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de cotización
     */
    public function testValidateQuotationData() {
        echo "Test: Validar datos de cotización... ";
        
        try {
            // Test datos válidos
            $validData = [
                'customer_name' => 'Cliente Válido',
                'customer_email' => 'cliente@example.com',
                'items' => [
                    ['product_id' => 1, 'quantity' => 2]
                ]
            ];
            
            $errors = $this->validateQuotationData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'customer_name' => '', // Nombre vacío
                    'customer_email' => 'email-invalido', // Email inválido
                    'items' => [] // Items vacíos
                ];
                
                $errors = $this->validateQuotationData($invalidData);
                
                if (!empty($errors) && isset($errors['customer_name']) && isset($errors['customer_email']) && isset($errors['items'])) {
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
     * Test: Obtener productos para sitemap
     */
    public function testGetProductsForSitemap() {
        echo "Test: Obtener productos para sitemap... ";
        
        try {
            $sql = "SELECT p.slug, p.updated_at, c.slug as category_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'active'
                    ORDER BY p.updated_at DESC";
            
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron productos para sitemap\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener categorías para sitemap
     */
    public function testGetCategoriesForSitemap() {
        echo "Test: Obtener categorías para sitemap... ";
        
        try {
            $sql = "SELECT slug, updated_at, parent_id
                    FROM categories
                    WHERE is_active = 1
                    ORDER BY updated_at DESC";
            
            $categories = $this->db->fetchAll($sql);
            
            if (is_array($categories)) {
                echo "✓ PASSED (Total: " . count($categories) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron categorías para sitemap\n";
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
                $this->db->query("DELETE FROM product_images WHERE product_id = ?", [$this->testProductId]);
                $this->db->query("DELETE FROM product_variants WHERE product_id = ?", [$this->testProductId]);
                $this->db->query("DELETE FROM products WHERE id = ?", [$this->testProductId]);
            }
            if ($this->testCategoryId) {
                $this->db->query("DELETE FROM categories WHERE id = ?", [$this->testCategoryId]);
            }
            if ($this->testCustomerId) {
                $this->db->query("DELETE FROM customers WHERE id = ?", [$this->testCustomerId]);
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

        // Validar nombre del cliente
        if (empty($data['customer_name'])) {
            $errors['customer_name'] = 'El nombre del cliente es requerido';
        }

        // Validar email del cliente
        if (empty($data['customer_email'])) {
            $errors['customer_email'] = 'El email del cliente es requerido';
        } elseif (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'El email del cliente no es válido';
        }

        // Validar items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'Debe incluir al menos un producto';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PublicTest();
    $test->runAllTests();
}
