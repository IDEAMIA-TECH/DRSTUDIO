<?php
/**
 * API Endpoint para Imágenes de Productos - DT Studio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/ProductImageController.php';

try {
    $controller = new ProductImageController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'index';
    $id = $pathParts[1] ?? null;
    
    switch ($action) {
        case 'by-product':
            if (!$id) {
                throw new Exception('ID de producto requerido');
            }
            $controller->byProduct($id);
            break;
            
        case 'by-variant':
            if (!$id) {
                throw new Exception('ID de variante requerido');
            }
            $controller->byVariant($id);
            break;
            
        case 'show':
            if (!$id) {
                throw new Exception('ID de imagen requerido');
            }
            $controller->show($id);
            break;
            
        case 'create':
            $controller->create();
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID de imagen requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID de imagen requerido');
            }
            $controller->delete($id);
            break;
            
        case 'set-primary':
            if (!$id) {
                throw new Exception('ID de imagen requerido');
            }
            $controller->setPrimary($id);
            break;
            
        case 'reorder':
            $controller->reorder();
            break;
            
        case 'upload-multiple':
            $controller->uploadMultiple();
            break;
            
        case 'primary-by-product':
            if (!$id) {
                throw new Exception('ID de producto requerido');
            }
            $controller->primaryByProduct($id);
            break;
            
        case 'primary-by-variant':
            if (!$id) {
                throw new Exception('ID de variante requerido');
            }
            $controller->primaryByVariant($id);
            break;
            
        case 'stats':
            $controller->stats();
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado'
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
