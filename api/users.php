<?php
/**
 * API Endpoint para Usuarios - DT Studio
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

require_once __DIR__ . '/../controllers/UserController.php';

try {
    $controller = new UserController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'index';
    $id = $pathParts[1] ?? null;
    
    switch ($action) {
        case 'index':
        case 'list':
            $controller->index();
            break;
            
        case 'show':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->show($id);
            break;
            
        case 'create':
            $controller->create();
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->delete($id);
            break;
            
        case 'toggle-status':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->toggleStatus($id);
            break;
            
        case 'stats':
            $controller->stats();
            break;
            
        case 'change-password':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->changePassword($id);
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
