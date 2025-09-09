<?php
/**
 * Tests para el módulo de Sistema de Configuración - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class ConfigurationTest {
    private $db;
    private $testSettingId;
    private $testBannerId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE CONFIGURACIÓN ===\n\n";
        
        $this->testCreateSetting();
        $this->testGetSettingById();
        $this->testGetSettingByKey();
        $this->testGetSettingValue();
        $this->testGetAllSettings();
        $this->testGetSettingsByGroup();
        $this->testGetPublicSettings();
        $this->testUpdateSetting();
        $this->testUpdateSettingByKey();
        $this->testGetSettingGroups();
        $this->testGetSettingStats();
        $this->testCreateBanner();
        $this->testGetBannerById();
        $this->testGetAllBanners();
        $this->testGetActiveBanners();
        $this->testUpdateBanner();
        $this->testToggleBannerStatus();
        $this->testGetBannerStats();
        $this->testValidateSettingData();
        $this->testValidateBannerData();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear configuración
     */
    public function testCreateSetting() {
        echo "Test: Crear configuración... ";
        
        try {
            $sql = "INSERT INTO settings (`key`, `value`, `type`, `description`, `is_public`, `group_name`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['test_setting', 'test_value', 'string', 'Configuración de prueba', 1, 'test', 1]);
            $this->testSettingId = $this->db->lastInsertId();
            
            if ($this->testSettingId) {
                echo "✓ PASSED (ID: {$this->testSettingId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID de la configuración\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener configuración por ID
     */
    public function testGetSettingById() {
        echo "Test: Obtener configuración por ID... ";
        
        try {
            $sql = "SELECT * FROM settings WHERE id = ?";
            $setting = $this->db->fetch($sql, [$this->testSettingId]);
            
            if ($setting && $setting['id'] == $this->testSettingId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Configuración no encontrada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener configuración por clave
     */
    public function testGetSettingByKey() {
        echo "Test: Obtener configuración por clave... ";
        
        try {
            $sql = "SELECT * FROM settings WHERE `key` = ?";
            $setting = $this->db->fetch($sql, ['test_setting']);
            
            if ($setting && $setting['key'] == 'test_setting') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Configuración no encontrada por clave\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener valor de configuración
     */
    public function testGetSettingValue() {
        echo "Test: Obtener valor de configuración... ";
        
        try {
            $sql = "SELECT `value`, `type` FROM settings WHERE `key` = ?";
            $setting = $this->db->fetch($sql, ['test_setting']);
            
            if ($setting) {
                $value = $this->castValue($setting['value'], $setting['type']);
                if ($value === 'test_value') {
                    echo "✓ PASSED (Valor: {$value})\n";
                } else {
                    echo "✗ FAILED - Valor incorrecto\n";
                }
            } else {
                echo "✗ FAILED - Configuración no encontrada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todas las configuraciones
     */
    public function testGetAllSettings() {
        echo "Test: Obtener todas las configuraciones... ";
        
        try {
            $sql = "SELECT * FROM settings ORDER BY group_name ASC, sort_order ASC, `key` ASC LIMIT 20 OFFSET 0";
            $settings = $this->db->fetchAll($sql);
            
            if (is_array($settings)) {
                echo "✓ PASSED (Total: " . count($settings) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron configuraciones\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener configuraciones por grupo
     */
    public function testGetSettingsByGroup() {
        echo "Test: Obtener configuraciones por grupo... ";
        
        try {
            $sql = "SELECT * FROM settings WHERE group_name = ? ORDER BY sort_order ASC, `key` ASC";
            $settings = $this->db->fetchAll($sql, ['test']);
            
            if (is_array($settings) && count($settings) > 0) {
                echo "✓ PASSED (Total: " . count($settings) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron configuraciones del grupo\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener configuraciones públicas
     */
    public function testGetPublicSettings() {
        echo "Test: Obtener configuraciones públicas... ";
        
        try {
            $sql = "SELECT `key`, `value`, `type` FROM settings WHERE is_public = 1 ORDER BY group_name ASC, sort_order ASC, `key` ASC";
            $settings = $this->db->fetchAll($sql);
            
            if (is_array($settings)) {
                echo "✓ PASSED (Total: " . count($settings) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron configuraciones públicas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar configuración
     */
    public function testUpdateSetting() {
        echo "Test: Actualizar configuración... ";
        
        try {
            $sql = "UPDATE settings SET `value` = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['updated_value', $this->testSettingId]);
            
            $setting = $this->db->fetch("SELECT * FROM settings WHERE id = ?", [$this->testSettingId]);
            
            if ($setting && $setting['value'] == 'updated_value') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Configuración no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar configuración por clave
     */
    public function testUpdateSettingByKey() {
        echo "Test: Actualizar configuración por clave... ";
        
        try {
            $sql = "UPDATE settings SET `value` = ?, updated_at = CURRENT_TIMESTAMP WHERE `key` = ?";
            $this->db->query($sql, ['key_updated_value', 'test_setting']);
            
            $setting = $this->db->fetch("SELECT * FROM settings WHERE `key` = ?", ['test_setting']);
            
            if ($setting && $setting['value'] == 'key_updated_value') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Configuración no se actualizó por clave\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener grupos de configuración
     */
    public function testGetSettingGroups() {
        echo "Test: Obtener grupos de configuración... ";
        
        try {
            $sql = "SELECT group_name, COUNT(*) as count FROM settings GROUP BY group_name ORDER BY group_name ASC";
            $groups = $this->db->fetchAll($sql);
            
            if (is_array($groups)) {
                echo "✓ PASSED (Total: " . count($groups) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron grupos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de configuraciones
     */
    public function testGetSettingStats() {
        echo "Test: Obtener estadísticas de configuraciones... ";
        
        try {
            $stats = [];
            
            // Total de configuraciones
            $stats['total_settings'] = $this->db->fetch("SELECT COUNT(*) as total FROM settings")['total'];
            
            // Configuraciones públicas
            $stats['public_settings'] = $this->db->fetch("SELECT COUNT(*) as total FROM settings WHERE is_public = 1")['total'];
            
            if (isset($stats['total_settings']) && isset($stats['public_settings'])) {
                echo "✓ PASSED (Total: {$stats['total_settings']}, Públicas: {$stats['public_settings']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear banner
     */
    public function testCreateBanner() {
        echo "Test: Crear banner... ";
        
        try {
            $sql = "INSERT INTO banners (title, image, link, description, active, sort_order, position, target_blank) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Banner de Prueba', '/images/banner.jpg', 'https://example.com', 'Descripción del banner', 1, 1, 'home', 1]);
            $this->testBannerId = $this->db->lastInsertId();
            
            if ($this->testBannerId) {
                echo "✓ PASSED (ID: {$this->testBannerId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del banner\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener banner por ID
     */
    public function testGetBannerById() {
        echo "Test: Obtener banner por ID... ";
        
        try {
            $sql = "SELECT * FROM banners WHERE id = ?";
            $banner = $this->db->fetch($sql, [$this->testBannerId]);
            
            if ($banner && $banner['id'] == $this->testBannerId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Banner no encontrado\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todos los banners
     */
    public function testGetAllBanners() {
        echo "Test: Obtener todos los banners... ";
        
        try {
            $sql = "SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC LIMIT 20 OFFSET 0";
            $banners = $this->db->fetchAll($sql);
            
            if (is_array($banners)) {
                echo "✓ PASSED (Total: " . count($banners) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron banners\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener banners activos
     */
    public function testGetActiveBanners() {
        echo "Test: Obtener banners activos... ";
        
        try {
            $sql = "SELECT * FROM banners WHERE active = 1 ORDER BY sort_order ASC, created_at DESC";
            $banners = $this->db->fetchAll($sql);
            
            if (is_array($banners)) {
                echo "✓ PASSED (Total: " . count($banners) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron banners activos\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar banner
     */
    public function testUpdateBanner() {
        echo "Test: Actualizar banner... ";
        
        try {
            $sql = "UPDATE banners SET title = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['Banner Actualizado', 'Descripción actualizada', $this->testBannerId]);
            
            $banner = $this->db->fetch("SELECT * FROM banners WHERE id = ?", [$this->testBannerId]);
            
            if ($banner && $banner['title'] == 'Banner Actualizado') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Banner no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Activar/desactivar banner
     */
    public function testToggleBannerStatus() {
        echo "Test: Activar/desactivar banner... ";
        
        try {
            // Obtener estado actual
            $banner = $this->db->fetch("SELECT active FROM banners WHERE id = ?", [$this->testBannerId]);
            $currentStatus = $banner['active'];
            $newStatus = $currentStatus ? 0 : 1;
            
            $sql = "UPDATE banners SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [$newStatus, $this->testBannerId]);
            
            $banner = $this->db->fetch("SELECT active FROM banners WHERE id = ?", [$this->testBannerId]);
            
            if ($banner['active'] == $newStatus) {
                echo "✓ PASSED (Estado: {$newStatus})\n";
            } else {
                echo "✗ FAILED - Estado no cambió correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de banners
     */
    public function testGetBannerStats() {
        echo "Test: Obtener estadísticas de banners... ";
        
        try {
            $stats = [];
            
            // Total de banners
            $stats['total_banners'] = $this->db->fetch("SELECT COUNT(*) as total FROM banners")['total'];
            
            // Banners activos
            $stats['active_banners'] = $this->db->fetch("SELECT COUNT(*) as total FROM banners WHERE active = 1")['total'];
            
            if (isset($stats['total_banners']) && isset($stats['active_banners'])) {
                echo "✓ PASSED (Total: {$stats['total_banners']}, Activos: {$stats['active_banners']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de banners\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de configuración
     */
    public function testValidateSettingData() {
        echo "Test: Validar datos de configuración... ";
        
        try {
            // Test datos válidos
            $validData = [
                'key' => 'valid_setting',
                'value' => 'valid_value',
                'type' => 'string'
            ];
            
            $errors = $this->validateSettingData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'key' => '', // Clave vacía
                    'value' => '', // Valor vacío
                    'type' => 'invalid' // Tipo inválido
                ];
                
                $errors = $this->validateSettingData($invalidData);
                
                if (!empty($errors) && isset($errors['key']) && isset($errors['value']) && isset($errors['type'])) {
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
     * Test: Validar datos de banner
     */
    public function testValidateBannerData() {
        echo "Test: Validar datos de banner... ";
        
        try {
            // Test datos válidos
            $validData = [
                'title' => 'Banner Válido',
                'image' => '/images/banner.jpg'
            ];
            
            $errors = $this->validateBannerData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'title' => '', // Título vacío
                    'image' => '', // Imagen vacía
                    'link' => 'invalid-url' // URL inválida
                ];
                
                $errors = $this->validateBannerData($invalidData);
                
                if (!empty($errors) && isset($errors['title']) && isset($errors['image']) && isset($errors['link'])) {
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
            // Eliminar configuraciones de prueba
            $this->db->query("DELETE FROM settings WHERE `key` LIKE 'test_%' OR `key` LIKE 'valid_%'");
            
            // Eliminar banners de prueba
            $this->db->query("DELETE FROM banners WHERE title LIKE '%Prueba%' OR title LIKE '%Válido%' OR title LIKE '%Actualizado%'");
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Convertir valor según su tipo (función auxiliar)
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true) ?? [];
            default:
                return $value;
        }
    }

    /**
     * Validar datos de configuración (función auxiliar)
     */
    private function validateSettingData($data) {
        $errors = [];

        // Validar clave
        if (empty($data['key'])) {
            $errors['key'] = 'La clave es requerida';
        }

        // Validar valor
        if (!isset($data['value'])) {
            $errors['value'] = 'El valor es requerido';
        }

        // Validar tipo
        if (isset($data['type']) && !in_array($data['type'], ['string', 'integer', 'float', 'boolean', 'array', 'json'])) {
            $errors['type'] = 'Tipo de configuración no válido';
        }

        return $errors;
    }

    /**
     * Validar datos de banner (función auxiliar)
     */
    private function validateBannerData($data) {
        $errors = [];

        // Validar título
        if (empty($data['title'])) {
            $errors['title'] = 'El título es requerido';
        }

        // Validar imagen
        if (empty($data['image'])) {
            $errors['image'] = 'La imagen es requerida';
        }

        // Validar enlace si se proporciona
        if (!empty($data['link']) && !filter_var($data['link'], FILTER_VALIDATE_URL)) {
            $errors['link'] = 'El enlace debe ser una URL válida';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ConfigurationTest();
    $test->runAllTests();
}
