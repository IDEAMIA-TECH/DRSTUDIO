<?php
/**
 * API Endpoint para Reportes y Analytics - DT Studio
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

require_once __DIR__ . '/../controllers/ReportController.php';

try {
    $controller = new ReportController();
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
                throw new Exception('ID de reporte requerido');
            }
            $controller->show($id);
            break;
            
        case 'create':
            $controller->create();
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID de reporte requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID de reporte requerido');
            }
            $controller->delete($id);
            break;
            
        case 'change-status':
            if (!$id) {
                throw new Exception('ID de reporte requerido');
            }
            $controller->changeStatus($id);
            break;
            
        case 'by-type':
            if (!$id) {
                throw new Exception('Tipo de reporte requerido');
            }
            $controller->byType($id);
            break;
            
        case 'public':
            $controller->public();
            break;
            
        case 'by-user':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->byUser($id);
            break;
            
        case 'duplicate':
            if (!$id) {
                throw new Exception('ID de reporte requerido');
            }
            $controller->duplicate($id);
            break;
            
        case 'types':
            $controller->types();
            break;
            
        case 'templates':
            $controller->templates();
            break;
            
        case 'create-from-template':
            $controller->createFromTemplate();
            break;
            
        case 'dashboard':
            $controller->dashboard();
            break;
            
        case 'sales':
            $controller->sales();
            break;
            
        case 'products':
            $controller->products();
            break;
            
        case 'customers':
            $controller->customers();
            break;
            
        case 'quotations':
            $controller->quotations();
            break;
            
        case 'orders':
            $controller->orders();
            break;
            
        case 'financial':
            $controller->financial();
            break;
            
        case 'trends':
            $controller->trends();
            break;
            
        case 'geographic':
            $controller->geographic();
            break;
            
        case 'performance':
            $controller->performance();
            break;
            
        case 'custom':
            $controller->custom();
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
