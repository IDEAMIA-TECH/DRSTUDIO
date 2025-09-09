<?php
/**
 * API Endpoint para Sistema de Configuración - DT Studio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/ConfigurationController.php';

try {
    $controller = new ConfigurationController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'list';
    $id = $pathParts[1] ?? null;
    $subAction = $pathParts[2] ?? null;
    
    switch ($action) {
        // ===== CONFIGURACIONES =====
        case 'settings':
            if ($subAction === 'create') {
                $controller->createSetting();
            } elseif ($subAction === 'list') {
                $controller->getAllSettings();
            } elseif ($subAction === 'groups') {
                $controller->getSettingGroups();
            } elseif ($subAction === 'public') {
                $controller->getPublicSettings();
            } elseif ($subAction === 'stats') {
                $controller->getSettingStats();
            } elseif ($subAction === 'import') {
                $controller->importSettings();
            } elseif ($subAction === 'export') {
                $controller->exportSettings();
            } else {
                throw new Exception('Acción de configuración no válida');
            }
            break;
            
        case 'setting':
            if (!$id) {
                throw new Exception('ID o clave de configuración requerido');
            }
            
            if ($subAction === 'get') {
                $controller->getSettingById($id);
            } elseif ($subAction === 'key') {
                $controller->getSettingByKey($id);
            } elseif ($subAction === 'value') {
                $controller->getSettingValue($id);
            } elseif ($subAction === 'update') {
                $controller->updateSetting($id);
            } elseif ($subAction === 'update-key') {
                $controller->updateSettingByKey($id);
            } elseif ($subAction === 'delete') {
                $controller->deleteSetting($id);
            } elseif ($subAction === 'delete-key') {
                $controller->deleteSettingByKey($id);
            } else {
                throw new Exception('Acción de configuración no válida');
            }
            break;
            
        case 'group':
            if (!$id) {
                throw new Exception('Nombre del grupo requerido');
            }
            $controller->getSettingsByGroup($id);
            break;
            
        // ===== BANNERS =====
        case 'banners':
            if ($subAction === 'create') {
                $controller->createBanner();
            } elseif ($subAction === 'list') {
                $controller->getAllBanners();
            } elseif ($subAction === 'active') {
                $controller->getActiveBanners();
            } elseif ($subAction === 'stats') {
                $controller->getBannerStats();
            } elseif ($subAction === 'expiring') {
                $controller->getExpiringBanners();
            } elseif ($subAction === 'expired') {
                $controller->getExpiredBanners();
            } elseif ($subAction === 'reorder') {
                $controller->reorderBanners();
            } else {
                throw new Exception('Acción de banner no válida');
            }
            break;
            
        case 'banner':
            if (!$id) {
                throw new Exception('ID de banner requerido');
            }
            
            if ($subAction === 'get') {
                $controller->getBannerById($id);
            } elseif ($subAction === 'update') {
                $controller->updateBanner($id);
            } elseif ($subAction === 'delete') {
                $controller->deleteBanner($id);
            } elseif ($subAction === 'toggle') {
                $controller->toggleBannerStatus($id);
            } else {
                throw new Exception('Acción de banner no válida');
            }
            break;
            
        case 'position':
            if (!$id) {
                throw new Exception('Posición requerida');
            }
            $controller->getBannersByPosition($id);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado',
                'available_endpoints' => [
                    'CONFIGURACIONES' => [
                        'POST /api/configuration.php?path=settings/create' => 'Crear configuración',
                        'GET /api/configuration.php?path=settings/list' => 'Listar configuraciones',
                        'GET /api/configuration.php?path=settings/groups' => 'Obtener grupos',
                        'GET /api/configuration.php?path=settings/public' => 'Configuraciones públicas',
                        'GET /api/configuration.php?path=settings/stats' => 'Estadísticas',
                        'POST /api/configuration.php?path=settings/import' => 'Importar configuraciones',
                        'GET /api/configuration.php?path=settings/export' => 'Exportar configuraciones',
                        'GET /api/configuration.php?path=setting/{id}/get' => 'Obtener configuración por ID',
                        'GET /api/configuration.php?path=setting/{key}/key' => 'Obtener configuración por clave',
                        'GET /api/configuration.php?path=setting/{key}/value' => 'Obtener valor de configuración',
                        'PUT /api/configuration.php?path=setting/{id}/update' => 'Actualizar configuración',
                        'PUT /api/configuration.php?path=setting/{key}/update-key' => 'Actualizar por clave',
                        'DELETE /api/configuration.php?path=setting/{id}/delete' => 'Eliminar configuración',
                        'DELETE /api/configuration.php?path=setting/{key}/delete-key' => 'Eliminar por clave',
                        'GET /api/configuration.php?path=group/{group}' => 'Configuraciones por grupo'
                    ],
                    'BANNERS' => [
                        'POST /api/configuration.php?path=banners/create' => 'Crear banner',
                        'GET /api/configuration.php?path=banners/list' => 'Listar banners',
                        'GET /api/configuration.php?path=banners/active' => 'Banners activos',
                        'GET /api/configuration.php?path=banners/stats' => 'Estadísticas de banners',
                        'GET /api/configuration.php?path=banners/expiring' => 'Banners próximos a expirar',
                        'GET /api/configuration.php?path=banners/expired' => 'Banners expirados',
                        'POST /api/configuration.php?path=banners/reorder' => 'Reordenar banners',
                        'GET /api/configuration.php?path=banner/{id}/get' => 'Obtener banner por ID',
                        'PUT /api/configuration.php?path=banner/{id}/update' => 'Actualizar banner',
                        'DELETE /api/configuration.php?path=banner/{id}/delete' => 'Eliminar banner',
                        'POST /api/configuration.php?path=banner/{id}/toggle' => 'Activar/desactivar banner',
                        'GET /api/configuration.php?path=position/{position}' => 'Banners por posición'
                    ]
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
