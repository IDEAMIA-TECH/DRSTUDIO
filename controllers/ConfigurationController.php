<?php
/**
 * Controlador ConfigurationController - DT Studio
 * Manejo de peticiones para el sistema de configuración
 */

require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Banner.php';

class ConfigurationController {
    private $settingModel;
    private $bannerModel;

    public function __construct() {
        $this->settingModel = new Setting();
        $this->bannerModel = new Banner();
    }

    // ===== MÉTODOS PARA CONFIGURACIONES =====

    /**
     * Crear configuración
     */
    public function createSetting() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->settingModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $settingId = $this->settingModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración creada exitosamente',
                'data' => ['setting_id' => $settingId]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuración por ID
     */
    public function getSettingById($id) {
        try {
            $setting = $this->settingModel->getById($id);
            
            if (!$setting) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $setting
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuración por clave
     */
    public function getSettingByKey($key) {
        try {
            $setting = $this->settingModel->getByKey($key);
            
            if (!$setting) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $setting
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener valor de configuración
     */
    public function getSettingValue($key) {
        try {
            $default = $_GET['default'] ?? null;
            $value = $this->settingModel->getValue($key, $default);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => ['value' => $value]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar configuraciones
     */
    public function getAllSettings() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'group_name' => $_GET['group_name'] ?? null,
                'type' => $_GET['type'] ?? null,
                'is_public' => $_GET['is_public'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->settingModel->getAll($filters, $page, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuraciones por grupo
     */
    public function getSettingsByGroup($groupName) {
        try {
            $settings = $this->settingModel->getByGroup($groupName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $settings
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuraciones públicas
     */
    public function getPublicSettings() {
        try {
            $settings = $this->settingModel->getPublic();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $settings
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración
     */
    public function updateSetting($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->settingModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración por clave
     */
    public function updateSettingByKey($key) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (!isset($input['value'])) {
                throw new Exception('El valor es requerido');
            }
            
            $this->settingModel->updateByKey($key, $input['value']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar configuración
     */
    public function deleteSetting($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->settingModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar configuración por clave
     */
    public function deleteSettingByKey($key) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->settingModel->deleteByKey($key);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener grupos de configuración
     */
    public function getSettingGroups() {
        try {
            $groups = $this->settingModel->getGroups();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $groups
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de configuraciones
     */
    public function getSettingStats() {
        try {
            $stats = $this->settingModel->getStats();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Importar configuraciones
     */
    public function importSettings() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['configurations'])) {
                throw new Exception('Lista de configuraciones requerida');
            }
            
            $result = $this->settingModel->import($input['configurations']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Importadas: {$result['imported']}, Errores: " . count($result['errors']),
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Exportar configuraciones
     */
    public function exportSettings() {
        try {
            $filters = [
                'group_name' => $_GET['group_name'] ?? null,
                'is_public' => $_GET['is_public'] ?? null
            ];
            
            $configurations = $this->settingModel->export($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $configurations
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA BANNERS =====

    /**
     * Crear banner
     */
    public function createBanner() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->bannerModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $bannerId = $this->bannerModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Banner creado exitosamente',
                'data' => ['banner_id' => $bannerId]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener banner por ID
     */
    public function getBannerById($id) {
        try {
            $banner = $this->bannerModel->getById($id);
            
            if (!$banner) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Banner no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $banner
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar banners
     */
    public function getAllBanners() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'active' => $_GET['active'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->bannerModel->getAll($filters, $page, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener banners activos
     */
    public function getActiveBanners() {
        try {
            $banners = $this->bannerModel->getActive();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $banners
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener banners por posición
     */
    public function getBannersByPosition($position) {
        try {
            $banners = $this->bannerModel->getByPosition($position);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $banners
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar banner
     */
    public function updateBanner($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->bannerModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Banner actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar banner
     */
    public function deleteBanner($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->bannerModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Banner eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Activar/desactivar banner
     */
    public function toggleBannerStatus($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $newStatus = $this->bannerModel->toggleStatus($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del banner actualizado',
                'data' => ['active' => $newStatus]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reordenar banners
     */
    public function reorderBanners() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['banner_ids']) || !is_array($input['banner_ids'])) {
                throw new Exception('Lista de IDs de banners requerida');
            }
            
            $this->bannerModel->reorder($input['banner_ids']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Banners reordenados exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de banners
     */
    public function getBannerStats() {
        try {
            $stats = $this->bannerModel->getStats();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener banners próximos a expirar
     */
    public function getExpiringBanners() {
        try {
            $days = $_GET['days'] ?? 7;
            $banners = $this->bannerModel->getExpiringSoon($days);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $banners
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener banners expirados
     */
    public function getExpiredBanners() {
        try {
            $banners = $this->bannerModel->getExpired();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $banners
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
