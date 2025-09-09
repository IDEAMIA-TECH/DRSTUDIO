<?php
/**
 * API Endpoint para Pedidos - DT Studio
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

require_once __DIR__ . '/../controllers/OrderController.php';

try {
    $controller = new OrderController();
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
                throw new Exception('ID de pedido requerido');
            }
            $controller->show($id);
            break;
            
        case 'create':
            $controller->create();
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->delete($id);
            break;
            
        case 'change-status':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->changeStatus($id);
            break;
            
        case 'change-payment-status':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->changePaymentStatus($id);
            break;
            
        case 'by-customer':
            if (!$id) {
                throw new Exception('ID de cliente requerido');
            }
            $controller->byCustomer($id);
            break;
            
        case 'by-user':
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            $controller->byUser($id);
            break;
            
        case 'pending':
            $controller->pending();
            break;
            
        case 'to-deliver':
            $controller->toDeliver();
            break;
            
        case 'stats':
            $controller->stats();
            break;
            
        case 'duplicate':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->duplicate($id);
            break;
            
        case 'history':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->history($id);
            break;
            
        case 'add-item':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->addItem($id);
            break;
            
        case 'update-item':
            if (!$id) {
                throw new Exception('ID de item requerido');
            }
            $controller->updateItem($id);
            break;
            
        case 'delete-item':
            if (!$id) {
                throw new Exception('ID de item requerido');
            }
            $controller->deleteItem($id);
            break;
            
        case 'get-items':
            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }
            $controller->getItems($id);
            break;
            
        case 'for-select':
            $controller->forSelect();
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
