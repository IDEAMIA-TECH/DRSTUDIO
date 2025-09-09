<?php
/**
 * Controlador CategoryController - DT Studio
 * Manejo de peticiones para gestión de categorías
 */

require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../includes/Auth.php';

class CategoryController {
    private $categoryModel;
    private $auth;

    public function __construct() {
        $this->categoryModel = new Category();
        $this->auth = new Auth();
    }

    /**
     * Listar categorías
     */
    public function index() {
        try {
            $this->auth->requirePermission('categories');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->categoryModel->getAll($page, $limit, $search);
            
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
     * Obtener categoría por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('categories');
            
            $category = $this->categoryModel->getById($id);
            
            if (!$category) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $category
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
     * Crear nueva categoría
     */
    public function create() {
        try {
            $this->auth->requirePermission('categories');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'description' => $_POST['description'] ?? '',
                'parent_id' => $_POST['parent_id'] ?? null,
                'image' => $_POST['image'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validar datos
            $errors = $this->categoryModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $categoryId = $this->categoryModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => ['id' => $categoryId]
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
     * Actualizar categoría
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('categories');
            
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
            $errors = $this->categoryModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->categoryModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente'
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
     * Eliminar categoría
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('categories');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->categoryModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
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
     * Obtener categorías para select
     */
    public function forSelect() {
        try {
            $this->auth->requirePermission('categories');
            
            $categories = $this->categoryModel->getForSelect();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $categories
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
     * Obtener categorías principales
     */
    public function main() {
        try {
            $this->auth->requirePermission('categories');
            
            $categories = $this->categoryModel->getMainCategories();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $categories
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
     * Obtener subcategorías
     */
    public function subcategories($parentId) {
        try {
            $this->auth->requirePermission('categories');
            
            $subcategories = $this->categoryModel->getSubcategories($parentId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $subcategories
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
     * Obtener estadísticas de categorías
     */
    public function stats() {
        try {
            $this->auth->requirePermission('categories');
            
            $stats = $this->categoryModel->getStats();
            
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
     * Duplicar categoría
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('categories');
            
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
            
            $newCategoryId = $this->categoryModel->duplicate($id, $newName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Categoría duplicada exitosamente',
                'data' => ['id' => $newCategoryId]
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
     * Reordenar categorías
     */
    public function reorder() {
        try {
            $this->auth->requirePermission('categories');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $categoryIds = $_POST['category_ids'] ?? [];
            if (empty($categoryIds) || !is_array($categoryIds)) {
                throw new Exception('Lista de categorías requerida');
            }
            
            $this->categoryModel->reorder($categoryIds);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Categorías reordenadas exitosamente'
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
