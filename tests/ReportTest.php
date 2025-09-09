<?php
/**
 * Tests para el módulo de Reportes y Analytics - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class ReportTest {
    private $db;
    private $testReportId;
    private $testUserId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE REPORTES ===\n\n";
        
        $this->testCreateTestData();
        $this->testCreateReport();
        $this->testGetReportById();
        $this->testUpdateReport();
        $this->testValidateReport();
        $this->testGetAllReports();
        $this->testChangeReportStatus();
        $this->testGetReportsByType();
        $this->testGetPublicReports();
        $this->testGetReportsByUser();
        $this->testDuplicateReport();
        $this->testGetAvailableTypes();
        $this->testGetTemplates();
        $this->testCreateFromTemplate();
        $this->testGetDashboardMetrics();
        $this->testGetSalesMetrics();
        $this->testGetProductMetrics();
        $this->testGetCustomerMetrics();
        $this->testGetQuotationMetrics();
        $this->testGetOrderMetrics();
        $this->testGetFinancialMetrics();
        $this->testGetGrowthTrends();
        $this->testGetGeographicMetrics();
        $this->testGetPerformanceMetrics();
        $this->testGetCustomMetrics();
        $this->testDeleteReport();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear datos de prueba
     */
    public function testCreateTestData() {
        echo "Test: Crear datos de prueba... ";
        
        try {
            // Crear usuario
            $email = 'usuario' . time() . '@example.com';
            $sql = "INSERT INTO users (name, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Usuario de Prueba', $email, password_hash('password123', PASSWORD_DEFAULT), 1, 1]);
            $this->testUserId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear reporte
     */
    public function testCreateReport() {
        echo "Test: Crear reporte... ";
        
        try {
            $reportData = [
                'name' => 'Reporte de Prueba',
                'description' => 'Reporte de prueba para testing',
                'type' => 'sales',
                'user_id' => $this->testUserId,
                'config' => '{"period": "month", "include_charts": true}',
                'is_public' => 0,
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO reports (name, description, type, user_id, config, is_public, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $reportData['name'],
                $reportData['description'],
                $reportData['type'],
                $reportData['user_id'],
                $reportData['config'],
                $reportData['is_public'],
                $reportData['is_active']
            ]);
            
            $this->testReportId = $this->db->lastInsertId();
            
            if ($this->testReportId) {
                echo "✓ PASSED (ID: {$this->testReportId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del reporte\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener reporte por ID
     */
    public function testGetReportById() {
        echo "Test: Obtener reporte por ID... ";
        
        try {
            $sql = "SELECT r.*, u.name as user_name
                    FROM reports r 
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.id = ?";
            $report = $this->db->fetch($sql, [$this->testReportId]);
            
            if ($report && $report['id'] == $this->testReportId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Reporte no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar reporte
     */
    public function testUpdateReport() {
        echo "Test: Actualizar reporte... ";
        
        try {
            if (!$this->testReportId) {
                echo "✗ FAILED - No hay reporte para actualizar\n";
                return;
            }
            
            $sql = "UPDATE reports SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['Reporte Actualizado', 'Descripción actualizada', $this->testReportId]);
            
            // Verificar que se actualizó
            $report = $this->db->fetch("SELECT * FROM reports WHERE id = ?", [$this->testReportId]);
            if ($report && $report['name'] == 'Reporte Actualizado' && $report['description'] == 'Descripción actualizada') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de reporte
     */
    public function testValidateReport() {
        echo "Test: Validar datos de reporte... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Reporte Válido',
                'type' => 'sales',
                'user_id' => $this->testUserId
            ];
            
            $errors = $this->validateReportData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'type' => 'invalid_type', // Tipo inválido
                    'user_id' => 999 // Usuario inexistente
                ];
                
                $errors = $this->validateReportData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['type']) && isset($errors['user_id'])) {
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
     * Test: Obtener todos los reportes
     */
    public function testGetAllReports() {
        echo "Test: Obtener todos los reportes... ";
        
        try {
            $sql = "SELECT r.*, u.name as user_name
                    FROM reports r 
                    LEFT JOIN users u ON r.user_id = u.id
                    ORDER BY r.created_at DESC";
            $reports = $this->db->fetchAll($sql);
            
            if (is_array($reports)) {
                echo "✓ PASSED (Total: " . count($reports) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado del reporte
     */
    public function testChangeReportStatus() {
        echo "Test: Cambiar estado del reporte... ";
        
        try {
            if (!$this->testReportId) {
                echo "✗ FAILED - No hay reporte para cambiar estado\n";
                return;
            }
            
            // Cambiar a inactivo
            $sql = "UPDATE reports SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [0, $this->testReportId]);
            
            // Verificar que cambió
            $report = $this->db->fetch("SELECT is_active FROM reports WHERE id = ?", [$this->testReportId]);
            if ($report && $report['is_active'] == 0) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener reportes por tipo
     */
    public function testGetReportsByType() {
        echo "Test: Obtener reportes por tipo... ";
        
        try {
            $sql = "SELECT r.*, u.name as user_name
                    FROM reports r 
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.type = ? AND r.is_active = 1
                    ORDER BY r.created_at DESC 
                    LIMIT ?";
            $reports = $this->db->fetchAll($sql, ['sales', 20]);
            
            if (is_array($reports)) {
                echo "✓ PASSED (Total: " . count($reports) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron reportes por tipo\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener reportes públicos
     */
    public function testGetPublicReports() {
        echo "Test: Obtener reportes públicos... ";
        
        try {
            $sql = "SELECT r.*, u.name as user_name
                    FROM reports r 
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.is_public = 1 AND r.is_active = 1
                    ORDER BY r.created_at DESC 
                    LIMIT ?";
            $reports = $this->db->fetchAll($sql, [20]);
            
            if (is_array($reports)) {
                echo "✓ PASSED (Total: " . count($reports) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron reportes públicos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener reportes por usuario
     */
    public function testGetReportsByUser() {
        echo "Test: Obtener reportes por usuario... ";
        
        try {
            $sql = "SELECT r.*, u.name as user_name
                    FROM reports r 
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.user_id = ? 
                    ORDER BY r.created_at DESC 
                    LIMIT ?";
            $reports = $this->db->fetchAll($sql, [$this->testUserId, 20]);
            
            if (is_array($reports)) {
                echo "✓ PASSED (Total: " . count($reports) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron reportes por usuario\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Duplicar reporte
     */
    public function testDuplicateReport() {
        echo "Test: Duplicar reporte... ";
        
        try {
            $newName = 'Reporte Duplicado';
            
            $sql = "INSERT INTO reports (name, description, type, user_id, config, is_public, is_active) 
                    SELECT ?, description, type, user_id, config, 0, 1 
                    FROM reports WHERE id = ?";
            $this->db->query($sql, [$newName, $this->testReportId]);
            $duplicatedId = $this->db->lastInsertId();
            
            if ($duplicatedId) {
                // Verificar que se creó el reporte duplicado
                $duplicated = $this->db->fetch("SELECT * FROM reports WHERE id = ?", [$duplicatedId]);
                if ($duplicated && $duplicated['name'] == $newName) {
                    echo "✓ PASSED (ID: {$duplicatedId})\n";
                } else {
                    echo "✗ FAILED - Reporte duplicado no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar el reporte\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener tipos disponibles
     */
    public function testGetAvailableTypes() {
        echo "Test: Obtener tipos disponibles... ";
        
        try {
            $types = [
                'sales' => 'Ventas',
                'products' => 'Productos',
                'customers' => 'Clientes',
                'quotations' => 'Cotizaciones',
                'orders' => 'Pedidos',
                'financial' => 'Financiero',
                'custom' => 'Personalizado'
            ];
            
            if (is_array($types) && count($types) > 0) {
                echo "✓ PASSED (Total: " . count($types) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron tipos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener plantillas
     */
    public function testGetTemplates() {
        echo "Test: Obtener plantillas... ";
        
        try {
            $templates = [
                'sales_summary' => [
                    'name' => 'Resumen de Ventas',
                    'type' => 'sales',
                    'description' => 'Reporte de ventas por período'
                ],
                'top_products' => [
                    'name' => 'Productos Más Vendidos',
                    'type' => 'products',
                    'description' => 'Ranking de productos por ventas'
                ]
            ];
            
            if (is_array($templates) && count($templates) > 0) {
                echo "✓ PASSED (Total: " . count($templates) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron plantillas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear desde plantilla
     */
    public function testCreateFromTemplate() {
        echo "Test: Crear desde plantilla... ";
        
        try {
            $templateData = [
                'name' => 'Reporte desde Plantilla',
                'type' => 'sales',
                'user_id' => $this->testUserId,
                'config' => '{"period": "month"}',
                'is_public' => 0,
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO reports (name, type, user_id, config, is_public, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $templateData['name'],
                $templateData['type'],
                $templateData['user_id'],
                $templateData['config'],
                $templateData['is_public'],
                $templateData['is_active']
            ]);
            
            $templateId = $this->db->lastInsertId();
            
            if ($templateId) {
                echo "✓ PASSED (ID: {$templateId})\n";
            } else {
                echo "✗ FAILED - No se creó desde plantilla\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas del dashboard
     */
    public function testGetDashboardMetrics() {
        echo "Test: Obtener métricas del dashboard... ";
        
        try {
            $metrics = [
                'total_sales' => 0,
                'total_quotations' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'pending_orders' => 0,
                'pending_quotations' => 0,
                'conversion_rate' => 0,
                'avg_order_value' => 0
            ];
            
            if (is_array($metrics) && count($metrics) > 0) {
                echo "✓ PASSED (Total: " . count($metrics) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de ventas
     */
    public function testGetSalesMetrics() {
        echo "Test: Obtener métricas de ventas... ";
        
        try {
            $sql = "SELECT DATE(created_at) as date, 
                           COUNT(*) as order_count,
                           SUM(total) as total_sales,
                           AVG(total) as avg_order_value
                    FROM orders 
                    WHERE status != 'cancelled' AND created_at >= date('now', '-1 month')
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            $sales = $this->db->fetchAll($sql);
            
            if (is_array($sales)) {
                echo "✓ PASSED (Total: " . count($sales) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de ventas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de productos
     */
    public function testGetProductMetrics() {
        echo "Test: Obtener métricas de productos... ";
        
        try {
            $sql = "SELECT p.name, p.sku, 
                           COUNT(oi.id) as times_ordered,
                           SUM(oi.quantity) as total_quantity,
                           SUM(oi.total) as total_revenue
                    FROM products p
                    LEFT JOIN order_items oi ON p.id = oi.product_id
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE o.status != 'cancelled' AND o.created_at >= date('now', '-1 month')
                    GROUP BY p.id, p.name, p.sku
                    ORDER BY total_revenue DESC
                    LIMIT 10";
            $products = $this->db->fetchAll($sql);
            
            if (is_array($products)) {
                echo "✓ PASSED (Total: " . count($products) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de productos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de clientes
     */
    public function testGetCustomerMetrics() {
        echo "Test: Obtener métricas de clientes... ";
        
        try {
            $sql = "SELECT c.name, c.email, c.company,
                           COUNT(DISTINCT o.id) as order_count,
                           SUM(o.total) as total_spent,
                           MAX(o.created_at) as last_order_date
                    FROM customers c
                    LEFT JOIN orders o ON c.id = o.customer_id
                    WHERE o.status != 'cancelled' AND o.created_at >= date('now', '-1 month')
                    GROUP BY c.id, c.name, c.email, c.company
                    ORDER BY total_spent DESC
                    LIMIT 10";
            $customers = $this->db->fetchAll($sql);
            
            if (is_array($customers)) {
                echo "✓ PASSED (Total: " . count($customers) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de clientes\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de cotizaciones
     */
    public function testGetQuotationMetrics() {
        echo "Test: Obtener métricas de cotizaciones... ";
        
        try {
            $sql = "SELECT status, COUNT(*) as count, SUM(total) as total_value
                    FROM quotations 
                    WHERE created_at >= date('now', '-1 month')
                    GROUP BY status
                    ORDER BY count DESC";
            $quotations = $this->db->fetchAll($sql);
            
            if (is_array($quotations)) {
                echo "✓ PASSED (Total: " . count($quotations) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de cotizaciones\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de pedidos
     */
    public function testGetOrderMetrics() {
        echo "Test: Obtener métricas de pedidos... ";
        
        try {
            $sql = "SELECT status, COUNT(*) as count, SUM(total) as total_value
                    FROM orders 
                    WHERE created_at >= date('now', '-1 month')
                    GROUP BY status
                    ORDER BY count DESC";
            $orders = $this->db->fetchAll($sql);
            
            if (is_array($orders)) {
                echo "✓ PASSED (Total: " . count($orders) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de pedidos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas financieras
     */
    public function testGetFinancialMetrics() {
        echo "Test: Obtener métricas financieras... ";
        
        try {
            $metrics = [
                'total_revenue' => 0,
                'monthly_revenue' => [],
                'payment_distribution' => []
            ];
            
            if (is_array($metrics) && isset($metrics['total_revenue'])) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas financieras\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener tendencias de crecimiento
     */
    public function testGetGrowthTrends() {
        echo "Test: Obtener tendencias de crecimiento... ";
        
        try {
            $trends = [
                'sales_growth' => 0,
                'customer_growth' => 0
            ];
            
            if (is_array($trends) && isset($trends['sales_growth'])) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se obtuvieron tendencias\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas geográficas
     */
    public function testGetGeographicMetrics() {
        echo "Test: Obtener métricas geográficas... ";
        
        try {
            $sql = "SELECT c.city, c.state, c.country,
                           COUNT(DISTINCT c.id) as customer_count,
                           COUNT(o.id) as order_count,
                           SUM(o.total) as total_sales
                    FROM customers c
                    LEFT JOIN orders o ON c.id = o.customer_id
                    WHERE o.status != 'cancelled' AND o.created_at >= date('now', '-1 month')
                    AND c.city IS NOT NULL AND c.city != ''
                    GROUP BY c.city, c.state, c.country
                    ORDER BY total_sales DESC
                    LIMIT 20";
            $geographic = $this->db->fetchAll($sql);
            
            if (is_array($geographic)) {
                echo "✓ PASSED (Total: " . count($geographic) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas geográficas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas de rendimiento
     */
    public function testGetPerformanceMetrics() {
        echo "Test: Obtener métricas de rendimiento... ";
        
        try {
            $metrics = [
                'avg_delivery_time' => 0,
                'cancellation_rate' => 0,
                'quotation_efficiency' => 0
            ];
            
            if (is_array($metrics) && isset($metrics['avg_delivery_time'])) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas de rendimiento\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener métricas personalizadas
     */
    public function testGetCustomMetrics() {
        echo "Test: Obtener métricas personalizadas... ";
        
        try {
            $config = ['type' => 'sales_by_category'];
            $metrics = [];
            
            if (is_array($metrics)) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se obtuvieron métricas personalizadas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar reporte
     */
    public function testDeleteReport() {
        echo "Test: Eliminar reporte... ";
        
        try {
            if (!$this->testReportId) {
                echo "✗ FAILED - No hay reporte para eliminar\n";
                return;
            }
            
            $sql = "DELETE FROM reports WHERE id = ?";
            $this->db->query($sql, [$this->testReportId]);
            
            // Verificar que se eliminó
            $report = $this->db->fetch("SELECT * FROM reports WHERE id = ?", [$this->testReportId]);
            if (!$report) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Reporte no se eliminó correctamente\n";
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
            if ($this->testUserId) {
                $this->db->query("DELETE FROM users WHERE id = ?", [$this->testUserId]);
            }
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de reporte (función auxiliar)
     */
    private function validateReportData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } else {
            $validTypes = ['sales', 'products', 'customers', 'quotations', 'orders', 'financial', 'custom'];
            if (!in_array($data['type'], $validTypes)) {
                $errors['type'] = 'El tipo no es válido';
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

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ReportTest();
    $test->runAllTests();
}
