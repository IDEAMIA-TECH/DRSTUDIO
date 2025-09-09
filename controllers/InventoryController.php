<?php
/**
 * Controlador InventoryController - DT Studio
 * Manejo de peticiones para el sistema de inventario
 */

require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/StockMovement.php';
require_once __DIR__ . '/../models/Supplier.php';

class InventoryController {
    private $inventoryModel;
    private $stockMovementModel;
    private $supplierModel;

    public function __construct() {
        $this->inventoryModel = new Inventory();
        $this->stockMovementModel = new StockMovement();
        $this->supplierModel = new Supplier();
    }

    // ===== MÉTODOS PARA INVENTARIO =====

    /**
     * Obtener stock de un producto
     */
    public function getStock($productId, $variantId = null) {
        try {
            $stock = $this->inventoryModel->getStock($productId, $variantId);
            
            if (!$stock) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto o variante no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stock
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
     * Obtener todo el stock
     */
    public function getAllStock() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'category_id' => $_GET['category_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'low_stock' => $_GET['low_stock'] ?? null,
                'out_of_stock' => $_GET['out_of_stock'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->inventoryModel->getAllStock($filters, $page, $limit);
            
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
     * Actualizar stock
     */
    public function updateStock() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->inventoryModel->validateInventoryData($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $result = $this->inventoryModel->updateStock(
                $input['product_id'],
                $input['variant_id'],
                $input['quantity'],
                $input['type'] ?? 'adjustment',
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
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
     * Ajustar stock (entrada)
     */
    public function adjustStockIn() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->inventoryModel->adjustStockIn(
                $input['product_id'],
                $input['variant_id'],
                $input['quantity'],
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock ajustado (entrada) exitosamente',
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
     * Ajustar stock (salida)
     */
    public function adjustStockOut() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->inventoryModel->adjustStockOut(
                $input['product_id'],
                $input['variant_id'],
                $input['quantity'],
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock ajustado (salida) exitosamente',
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
     * Reservar stock
     */
    public function reserveStock() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->inventoryModel->reserveStock(
                $input['product_id'],
                $input['variant_id'],
                $input['quantity'],
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock reservado exitosamente',
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
     * Liberar stock reservado
     */
    public function releaseStock() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->inventoryModel->releaseStock(
                $input['product_id'],
                $input['variant_id'],
                $input['quantity'],
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock liberado exitosamente',
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
     * Transferir stock
     */
    public function transferStock() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->inventoryModel->transferStock(
                $input['from_variant_id'],
                $input['to_variant_id'],
                $input['quantity'],
                $input['notes'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock transferido exitosamente',
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
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts() {
        try {
            $limit = $_GET['limit'] ?? 50;
            $products = $this->inventoryModel->getLowStockProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener productos sin stock
     */
    public function getOutOfStockProducts() {
        try {
            $limit = $_GET['limit'] ?? 50;
            $products = $this->inventoryModel->getOutOfStockProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener productos con sobrestock
     */
    public function getOverstockProducts() {
        try {
            $limit = $_GET['limit'] ?? 50;
            $products = $this->inventoryModel->getOverstockProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener estadísticas de inventario
     */
    public function getInventoryStats() {
        try {
            $stats = $this->inventoryModel->getInventoryStats();
            
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
     * Obtener historial de stock de un producto
     */
    public function getProductStockHistory($productId, $variantId = null) {
        try {
            $limit = $_GET['limit'] ?? 50;
            $history = $this->inventoryModel->getProductStockHistory($productId, $variantId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $history
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA MOVIMIENTOS DE STOCK =====

    /**
     * Crear movimiento de stock
     */
    public function createStockMovement() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->stockMovementModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $movementId = $this->stockMovementModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Movimiento de stock creado exitosamente',
                'data' => ['movement_id' => $movementId]
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
     * Obtener movimiento por ID
     */
    public function getStockMovementById($id) {
        try {
            $movement = $this->stockMovementModel->getById($id);
            
            if (!$movement) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Movimiento de stock no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movement
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
     * Listar movimientos de stock
     */
    public function getAllStockMovements() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'product_id' => $_GET['product_id'] ?? null,
                'variant_id' => $_GET['variant_id'] ?? null,
                'type' => $_GET['type'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'created_by' => $_GET['created_by'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->stockMovementModel->getAll($filters, $page, $limit);
            
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
     * Obtener movimientos por tipo
     */
    public function getStockMovementsByType($type) {
        try {
            $limit = $_GET['limit'] ?? 50;
            $movements = $this->stockMovementModel->getByType($type, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movements
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
     * Obtener movimientos por producto
     */
    public function getStockMovementsByProduct($productId, $variantId = null) {
        try {
            $limit = $_GET['limit'] ?? 50;
            $movements = $this->stockMovementModel->getByProduct($productId, $variantId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movements
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
     * Obtener movimientos por rango de fechas
     */
    public function getStockMovementsByDateRange() {
        try {
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $limit = $_GET['limit'] ?? 100;
            
            if (!$dateFrom || !$dateTo) {
                throw new Exception('Rango de fechas requerido');
            }
            
            $movements = $this->stockMovementModel->getByDateRange($dateFrom, $dateTo, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movements
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
     * Obtener estadísticas de movimientos
     */
    public function getStockMovementStats() {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $stats = $this->stockMovementModel->getStats($filters);
            
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
     * Obtener resumen diario de movimientos
     */
    public function getDailySummary() {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $summary = $this->stockMovementModel->getDailySummary($date);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $summary
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
     * Obtener resumen mensual de movimientos
     */
    public function getMonthlySummary() {
        try {
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('m');
            $summary = $this->stockMovementModel->getMonthlySummary($year, $month);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $summary
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
     * Eliminar movimiento de stock
     */
    public function deleteStockMovement($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->stockMovementModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Movimiento de stock eliminado exitosamente'
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
     * Obtener tipos de movimiento
     */
    public function getMovementTypes() {
        try {
            $types = $this->stockMovementModel->getMovementTypes();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $types
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA PROVEEDORES =====

    /**
     * Crear proveedor
     */
    public function createSupplier() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->supplierModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $supplierId = $this->supplierModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => ['supplier_id' => $supplierId]
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
     * Obtener proveedor por ID
     */
    public function getSupplierById($id) {
        try {
            $supplier = $this->supplierModel->getById($id);
            
            if (!$supplier) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $supplier
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
     * Listar proveedores
     */
    public function getAllSuppliers() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'is_active' => $_GET['is_active'] ?? null,
                'country' => $_GET['country'] ?? null,
                'city' => $_GET['city'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->supplierModel->getAll($filters, $page, $limit);
            
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
     * Obtener proveedores activos
     */
    public function getActiveSuppliers() {
        try {
            $suppliers = $this->supplierModel->getActive();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $suppliers
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
     * Actualizar proveedor
     */
    public function updateSupplier($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->supplierModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente'
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
     * Eliminar proveedor
     */
    public function deleteSupplier($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->supplierModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
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
     * Activar/desactivar proveedor
     */
    public function toggleSupplierStatus($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $newStatus = $this->supplierModel->toggleStatus($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del proveedor actualizado',
                'data' => ['is_active' => $newStatus]
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
     * Obtener productos del proveedor
     */
    public function getSupplierProducts($supplierId) {
        try {
            $limit = $_GET['limit'] ?? 50;
            $products = $this->supplierModel->getProducts($supplierId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener estadísticas del proveedor
     */
    public function getSupplierStats($supplierId) {
        try {
            $stats = $this->supplierModel->getSupplierStats($supplierId);
            
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
     * Obtener estadísticas generales de proveedores
     */
    public function getSuppliersStats() {
        try {
            $stats = $this->supplierModel->getStats();
            
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
     * Obtener países de proveedores
     */
    public function getSupplierCountries() {
        try {
            $countries = $this->supplierModel->getCountries();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $countries
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
     * Obtener ciudades de proveedores
     */
    public function getSupplierCities() {
        try {
            $country = $_GET['country'] ?? null;
            $cities = $this->supplierModel->getCities($country);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $cities
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
     * Buscar proveedores
     */
    public function searchSuppliers() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = $_GET['limit'] ?? 20;
            
            if (empty($query)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            $suppliers = $this->supplierModel->search($query, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $suppliers
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
