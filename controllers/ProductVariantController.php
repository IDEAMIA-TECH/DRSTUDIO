<?php
/**
 * Controlador ProductVariantController - DT Studio
 * Manejo de peticiones para gestión de variantes de productos
 */

require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../includes/Auth.php';

class ProductVariantController {
    private $variantModel;
    private $auth;

    public function __construct() {
        $this->variantModel = new ProductVariant();
        $this->auth = new Auth();
    }

    /**
     * Obtener variantes de un producto
     */
    public function byProduct($productId) {
        try {
            $this->auth->requirePermission('products');
            
            $variants = $this->variantModel->getByProduct($productId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $variants
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
     * Obtener variante por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('products');
            
            $variant = $this->variantModel->getById($id);
            
            if (!$variant) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Variante no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $variant
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
     * Crear nueva variante
     */
    public function create() {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $data = [
                'product_id' => $_POST['product_id'] ?? '',
                'name' => $_POST['name'] ?? '',
                'sku' => $_POST['sku'] ?? '',
                'price' => $_POST['price'] ?? '',
                'cost' => $_POST['cost'] ?? '',
                'stock' => $_POST['stock'] ?? 0,
                'attributes' => $_POST['attributes'] ?? [],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validar datos
            $errors = $this->variantModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $variantId = $this->variantModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Variante creada exitosamente',
                'data' => ['id' => $variantId]
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
     * Actualizar variante
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $data = $input ?? $_POST;
            unset($data['csrf_token']);
            
            // Validar datos
            $errors = $this->variantModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->variantModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Variante actualizada exitosamente'
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
     * Eliminar variante
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->variantModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Variante eliminada exitosamente'
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
     * Cambiar estado de la variante
     */
    public function toggleStatus($id) {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->variantModel->toggleStatus($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado de la variante actualizado'
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
     * Actualizar stock de variante
     */
    public function updateStock($id) {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $quantity = $_POST['quantity'] ?? 0;
            $operation = $_POST['operation'] ?? 'set';
            
            if (!is_numeric($quantity)) {
                throw new Exception('La cantidad debe ser un número');
            }
            
            $newStock = $this->variantModel->updateStock($id, $quantity, $operation);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'data' => ['new_stock' => $newStock]
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
     * Obtener variantes con stock bajo
     */
    public function lowStock() {
        try {
            $this->auth->requirePermission('products');
            
            $threshold = $_GET['threshold'] ?? 10;
            $variants = $this->variantModel->getLowStock($threshold);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $variants
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
     * Obtener estadísticas de variantes
     */
    public function stats() {
        try {
            $this->auth->requirePermission('products');
            
            $stats = $this->variantModel->getStats();
            
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
     * Duplicar variante
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('products');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $newName = $_POST['new_name'] ?? '';
            if (empty($newName)) {
                throw new Exception('El nuevo nombre es requerido');
            }
            
            $newVariantId = $this->variantModel->duplicate($id, $newName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Variante duplicada exitosamente',
                'data' => ['id' => $newVariantId]
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
