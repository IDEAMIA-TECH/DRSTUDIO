<?php
/**
 * API Endpoint para Sistema de Pagos - DT Studio
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

require_once __DIR__ . '/../controllers/PaymentController.php';

try {
    $controller = new PaymentController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'list';
    $id = $pathParts[1] ?? null;
    $subAction = $pathParts[2] ?? null;
    
    switch ($action) {
        // ===== PAGOS =====
        case 'create':
            $controller->create();
            break;
            
        case 'list':
            $controller->getAll();
            break;
            
        case 'get':
            if (!$id) {
                throw new Exception('ID del pago requerido');
            }
            $controller->getById($id);
            break;
            
        case 'reference':
            if (!$id) {
                throw new Exception('Referencia del pago requerida');
            }
            $controller->getByReference($id);
            break;
            
        case 'order':
            if (!$id) {
                throw new Exception('ID de la orden requerido');
            }
            $controller->getByOrderId($id);
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID del pago requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID del pago requerido');
            }
            $controller->delete($id);
            break;
            
        case 'process':
            if (!$id) {
                throw new Exception('ID del pago requerido');
            }
            $controller->process($id);
            break;
            
        case 'refund':
            if (!$id) {
                throw new Exception('ID del pago requerido');
            }
            $controller->refund($id);
            break;
            
        case 'stats':
            $controller->getStats();
            break;
            
        case 'pending':
            $controller->getPending();
            break;
            
        case 'date':
            if (!$id) {
                throw new Exception('Fecha requerida (YYYY-MM-DD)');
            }
            $controller->getByDate($id);
            break;
            
        // ===== PASARELAS DE PAGO =====
        case 'gateways':
            if ($subAction === 'create') {
                $controller->createGateway();
            } elseif ($subAction === 'list') {
                $controller->getAllGateways();
            } elseif ($subAction === 'active') {
                $controller->getActiveGateways();
            } elseif ($subAction === 'stats') {
                $controller->getGatewayStats();
            } elseif ($subAction === 'validate-config') {
                $controller->validateGatewayConfig();
            } elseif ($subAction === 'process') {
                $controller->processWithGateway();
            } else {
                throw new Exception('Acción de pasarela no válida');
            }
            break;
            
        case 'gateway':
            if (!$id) {
                throw new Exception('ID de la pasarela requerido');
            }
            
            if ($subAction === 'get') {
                $controller->getGatewayById($id);
            } elseif ($subAction === 'update') {
                $controller->updateGateway($id);
            } elseif ($subAction === 'delete') {
                $controller->deleteGateway($id);
            } elseif ($subAction === 'toggle') {
                $controller->toggleGatewayStatus($id);
            } else {
                throw new Exception('Acción de pasarela no válida');
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado',
                'available_endpoints' => [
                    'PAGOS' => [
                        'POST /api/payments.php?path=create' => 'Crear pago',
                        'GET /api/payments.php?path=list' => 'Listar pagos',
                        'GET /api/payments.php?path=get/{id}' => 'Obtener pago por ID',
                        'GET /api/payments.php?path=reference/{reference}' => 'Obtener pago por referencia',
                        'GET /api/payments.php?path=order/{order_id}' => 'Obtener pagos por orden',
                        'PUT /api/payments.php?path=update/{id}' => 'Actualizar pago',
                        'DELETE /api/payments.php?path=delete/{id}' => 'Eliminar pago',
                        'POST /api/payments.php?path=process/{id}' => 'Procesar pago',
                        'POST /api/payments.php?path=refund/{id}' => 'Reembolsar pago',
                        'GET /api/payments.php?path=stats' => 'Estadísticas de pagos',
                        'GET /api/payments.php?path=pending' => 'Pagos pendientes',
                        'GET /api/payments.php?path=date/{date}' => 'Pagos por fecha'
                    ],
                    'PASARELAS' => [
                        'POST /api/payments.php?path=gateways/create' => 'Crear pasarela',
                        'GET /api/payments.php?path=gateways/list' => 'Listar pasarelas',
                        'GET /api/payments.php?path=gateways/active' => 'Pasarelas activas',
                        'GET /api/payments.php?path=gateways/stats' => 'Estadísticas de pasarelas',
                        'POST /api/payments.php?path=gateways/validate-config' => 'Validar configuración',
                        'POST /api/payments.php?path=gateways/process' => 'Procesar con pasarela',
                        'GET /api/payments.php?path=gateway/{id}/get' => 'Obtener pasarela',
                        'PUT /api/payments.php?path=gateway/{id}/update' => 'Actualizar pasarela',
                        'DELETE /api/payments.php?path=gateway/{id}/delete' => 'Eliminar pasarela',
                        'POST /api/payments.php?path=gateway/{id}/toggle' => 'Activar/desactivar pasarela'
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
