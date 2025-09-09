<?php
/**
 * API Endpoint para Sistema de Inventario - DT Studio
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

require_once __DIR__ . '/../controllers/InventoryController.php';

try {
    $controller = new InventoryController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'list';
    $id = $pathParts[1] ?? null;
    $subAction = $pathParts[2] ?? null;
    
    switch ($action) {
        // ===== INVENTARIO =====
        case 'stock':
            if ($subAction === 'get') {
                if (!$id) {
                    throw new Exception('ID del producto requerido');
                }
                $variantId = $_GET['variant_id'] ?? null;
                $controller->getStock($id, $variantId);
            } elseif ($subAction === 'list') {
                $controller->getAllStock();
            } elseif ($subAction === 'update') {
                $controller->updateStock();
            } elseif ($subAction === 'adjust-in') {
                $controller->adjustStockIn();
            } elseif ($subAction === 'adjust-out') {
                $controller->adjustStockOut();
            } elseif ($subAction === 'reserve') {
                $controller->reserveStock();
            } elseif ($subAction === 'release') {
                $controller->releaseStock();
            } elseif ($subAction === 'transfer') {
                $controller->transferStock();
            } elseif ($subAction === 'low-stock') {
                $controller->getLowStockProducts();
            } elseif ($subAction === 'out-of-stock') {
                $controller->getOutOfStockProducts();
            } elseif ($subAction === 'overstock') {
                $controller->getOverstockProducts();
            } elseif ($subAction === 'stats') {
                $controller->getInventoryStats();
            } elseif ($subAction === 'history') {
                if (!$id) {
                    throw new Exception('ID del producto requerido');
                }
                $variantId = $_GET['variant_id'] ?? null;
                $controller->getProductStockHistory($id, $variantId);
            } else {
                throw new Exception('Acción de stock no válida');
            }
            break;
            
        // ===== MOVIMIENTOS DE STOCK =====
        case 'movements':
            if ($subAction === 'create') {
                $controller->createStockMovement();
            } elseif ($subAction === 'list') {
                $controller->getAllStockMovements();
            } elseif ($subAction === 'stats') {
                $controller->getStockMovementStats();
            } elseif ($subAction === 'daily-summary') {
                $controller->getDailySummary();
            } elseif ($subAction === 'monthly-summary') {
                $controller->getMonthlySummary();
            } elseif ($subAction === 'types') {
                $controller->getMovementTypes();
            } else {
                throw new Exception('Acción de movimientos no válida');
            }
            break;
            
        case 'movement':
            if (!$id) {
                throw new Exception('ID del movimiento requerido');
            }
            
            if ($subAction === 'get') {
                $controller->getStockMovementById($id);
            } elseif ($subAction === 'delete') {
                $controller->deleteStockMovement($id);
            } else {
                throw new Exception('Acción de movimiento no válida');
            }
            break;
            
        case 'type':
            if (!$id) {
                throw new Exception('Tipo de movimiento requerido');
            }
            $controller->getStockMovementsByType($id);
            break;
            
        case 'product':
            if (!$id) {
                throw new Exception('ID del producto requerido');
            }
            $variantId = $_GET['variant_id'] ?? null;
            $controller->getStockMovementsByProduct($id, $variantId);
            break;
            
        case 'date-range':
            $controller->getStockMovementsByDateRange();
            break;
            
        // ===== PROVEEDORES =====
        case 'suppliers':
            if ($subAction === 'create') {
                $controller->createSupplier();
            } elseif ($subAction === 'list') {
                $controller->getAllSuppliers();
            } elseif ($subAction === 'active') {
                $controller->getActiveSuppliers();
            } elseif ($subAction === 'stats') {
                $controller->getSuppliersStats();
            } elseif ($subAction === 'countries') {
                $controller->getSupplierCountries();
            } elseif ($subAction === 'cities') {
                $controller->getSupplierCities();
            } elseif ($subAction === 'search') {
                $controller->searchSuppliers();
            } else {
                throw new Exception('Acción de proveedores no válida');
            }
            break;
            
        case 'supplier':
            if (!$id) {
                throw new Exception('ID del proveedor requerido');
            }
            
            if ($subAction === 'get') {
                $controller->getSupplierById($id);
            } elseif ($subAction === 'update') {
                $controller->updateSupplier($id);
            } elseif ($subAction === 'delete') {
                $controller->deleteSupplier($id);
            } elseif ($subAction === 'toggle') {
                $controller->toggleSupplierStatus($id);
            } elseif ($subAction === 'products') {
                $controller->getSupplierProducts($id);
            } elseif ($subAction === 'stats') {
                $controller->getSupplierStats($id);
            } else {
                throw new Exception('Acción de proveedor no válida');
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado',
                'available_endpoints' => [
                    'INVENTARIO' => [
                        'GET /api/inventory.php?path=stock/get/{product_id}' => 'Obtener stock de producto',
                        'GET /api/inventory.php?path=stock/list' => 'Listar todo el stock',
                        'POST /api/inventory.php?path=stock/update' => 'Actualizar stock',
                        'POST /api/inventory.php?path=stock/adjust-in' => 'Ajustar stock (entrada)',
                        'POST /api/inventory.php?path=stock/adjust-out' => 'Ajustar stock (salida)',
                        'POST /api/inventory.php?path=stock/reserve' => 'Reservar stock',
                        'POST /api/inventory.php?path=stock/release' => 'Liberar stock',
                        'POST /api/inventory.php?path=stock/transfer' => 'Transferir stock',
                        'GET /api/inventory.php?path=stock/low-stock' => 'Productos con stock bajo',
                        'GET /api/inventory.php?path=stock/out-of-stock' => 'Productos sin stock',
                        'GET /api/inventory.php?path=stock/overstock' => 'Productos con sobrestock',
                        'GET /api/inventory.php?path=stock/stats' => 'Estadísticas de inventario',
                        'GET /api/inventory.php?path=stock/history/{product_id}' => 'Historial de stock'
                    ],
                    'MOVIMIENTOS DE STOCK' => [
                        'POST /api/inventory.php?path=movements/create' => 'Crear movimiento de stock',
                        'GET /api/inventory.php?path=movements/list' => 'Listar movimientos',
                        'GET /api/inventory.php?path=movements/stats' => 'Estadísticas de movimientos',
                        'GET /api/inventory.php?path=movements/daily-summary' => 'Resumen diario',
                        'GET /api/inventory.php?path=movements/monthly-summary' => 'Resumen mensual',
                        'GET /api/inventory.php?path=movements/types' => 'Tipos de movimiento',
                        'GET /api/inventory.php?path=movement/{id}/get' => 'Obtener movimiento por ID',
                        'DELETE /api/inventory.php?path=movement/{id}/delete' => 'Eliminar movimiento',
                        'GET /api/inventory.php?path=type/{type}' => 'Movimientos por tipo',
                        'GET /api/inventory.php?path=product/{product_id}' => 'Movimientos por producto',
                        'GET /api/inventory.php?path=date-range' => 'Movimientos por rango de fechas'
                    ],
                    'PROVEEDORES' => [
                        'POST /api/inventory.php?path=suppliers/create' => 'Crear proveedor',
                        'GET /api/inventory.php?path=suppliers/list' => 'Listar proveedores',
                        'GET /api/inventory.php?path=suppliers/active' => 'Proveedores activos',
                        'GET /api/inventory.php?path=suppliers/stats' => 'Estadísticas de proveedores',
                        'GET /api/inventory.php?path=suppliers/countries' => 'Países de proveedores',
                        'GET /api/inventory.php?path=suppliers/cities' => 'Ciudades de proveedores',
                        'GET /api/inventory.php?path=suppliers/search' => 'Buscar proveedores',
                        'GET /api/inventory.php?path=supplier/{id}/get' => 'Obtener proveedor por ID',
                        'PUT /api/inventory.php?path=supplier/{id}/update' => 'Actualizar proveedor',
                        'DELETE /api/inventory.php?path=supplier/{id}/delete' => 'Eliminar proveedor',
                        'POST /api/inventory.php?path=supplier/{id}/toggle' => 'Activar/desactivar proveedor',
                        'GET /api/inventory.php?path=supplier/{id}/products' => 'Productos del proveedor',
                        'GET /api/inventory.php?path=supplier/{id}/stats' => 'Estadísticas del proveedor'
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
