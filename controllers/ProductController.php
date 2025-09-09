<?php
/**
 * Controlador ProductController - DT Studio
 * Manejo de peticiones para gestión de productos
 */

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/Auth.php';

class ProductController {
    private $productModel;
    private $auth;

    public function __construct() {
        $this->productModel = new Product();
        $this->auth = new Auth();
    }

    /**
     * Listar productos
     */
    public function index() {
        try {
            $this->auth->requirePermission('products');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $categoryId = $_GET['category_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->productModel->getAll($page, $limit, $search, $categoryId, $status);
            
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
     * Obtener producto por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('products');
            
            $product = $this->productModel->getById($id);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $product
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
     * Crear nuevo producto
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
            
            $user = $this->auth->getCurrentUser();
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category_id' => $_POST['category_id'] ?? '',
                'sku' => $_POST['sku'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'created_by' => $user['id']
            ];
            
            // Validar datos
            $errors = $this->productModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $productId = $this->productModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => ['id' => $productId]
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
     * Actualizar producto
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
            $errors = $this->productModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->productModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado exitosamente'
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
     * Eliminar producto
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
            
            $this->productModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
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
     * Cambiar estado del producto
     */
    public function changeStatus($id) {
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
            
            $status = $_POST['status'] ?? '';
            if (empty($status)) {
                throw new Exception('El estado es requerido');
            }
            
            $this->productModel->changeStatus($id, $status);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del producto actualizado'
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
     * Obtener productos destacados
     */
    public function featured() {
        try {
            $this->auth->requirePermission('products');
            
            $limit = $_GET['limit'] ?? 10;
            $products = $this->productModel->getFeatured($limit);
            
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
     * Obtener productos por categoría
     */
    public function byCategory($categoryId) {
        try {
            $this->auth->requirePermission('products');
            
            $limit = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;
            $products = $this->productModel->getByCategory($categoryId, $limit, $offset);
            
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
     * Buscar productos
     */
    public function search() {
        try {
            $this->auth->requirePermission('products');
            
            $query = $_GET['q'] ?? '';
            $categoryId = $_GET['category_id'] ?? null;
            $limit = $_GET['limit'] ?? 20;
            
            if (empty($query)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            $products = $this->productModel->search($query, $categoryId, $limit);
            
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
     * Obtener estadísticas de productos
     */
    public function stats() {
        try {
            $this->auth->requirePermission('products');
            
            $stats = $this->productModel->getStats();
            
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
     * Duplicar producto
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
            
            $newProductId = $this->productModel->duplicate($id, $newName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Producto duplicado exitosamente',
                'data' => ['id' => $newProductId]
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
