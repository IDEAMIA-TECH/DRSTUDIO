<?php
/**
 * Controlador ProductImageController - DT Studio
 * Manejo de peticiones para gestión de imágenes de productos
 */

require_once __DIR__ . '/../models/ProductImage.php';
require_once __DIR__ . '/../includes/Auth.php';

class ProductImageController {
    private $imageModel;
    private $auth;

    public function __construct() {
        $this->imageModel = new ProductImage();
        $this->auth = new Auth();
    }

    /**
     * Obtener imágenes de un producto
     */
    public function byProduct($productId) {
        try {
            $this->auth->requirePermission('products');
            
            $images = $this->imageModel->getByProduct($productId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $images
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
     * Obtener imágenes de una variante
     */
    public function byVariant($variantId) {
        try {
            $this->auth->requirePermission('products');
            
            $images = $this->imageModel->getByVariant($variantId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $images
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
     * Obtener imagen por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('products');
            
            $image = $this->imageModel->getById($id);
            
            if (!$image) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $image
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
     * Crear nueva imagen
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
                'variant_id' => $_POST['variant_id'] ?? null,
                'url' => $_POST['url'] ?? '',
                'alt_text' => $_POST['alt_text'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_primary' => isset($_POST['is_primary']) ? 1 : 0
            ];
            
            // Validar datos
            $errors = $this->imageModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $imageId = $this->imageModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imagen creada exitosamente',
                'data' => ['id' => $imageId]
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
     * Actualizar imagen
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
            $errors = $this->imageModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->imageModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imagen actualizada exitosamente'
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
     * Eliminar imagen
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
            
            $this->imageModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente'
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
     * Establecer imagen como primaria
     */
    public function setPrimary($id) {
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
            
            $this->imageModel->setPrimary($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imagen establecida como primaria'
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
     * Reordenar imágenes
     */
    public function reorder() {
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
            
            $imageIds = $_POST['image_ids'] ?? [];
            if (empty($imageIds) || !is_array($imageIds)) {
                throw new Exception('Lista de imágenes requerida');
            }
            
            $this->imageModel->reorder($imageIds);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imágenes reordenadas exitosamente'
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
     * Subir múltiples imágenes
     */
    public function uploadMultiple() {
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
            
            $productId = $_POST['product_id'] ?? '';
            $variantId = $_POST['variant_id'] ?? null;
            $images = $_POST['images'] ?? [];
            
            if (empty($productId)) {
                throw new Exception('ID del producto requerido');
            }
            
            if (empty($images) || !is_array($images)) {
                throw new Exception('Lista de imágenes requerida');
            }
            
            $uploadedImages = $this->imageModel->uploadMultiple($productId, $images, $variantId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Imágenes subidas exitosamente',
                'data' => ['uploaded_images' => $uploadedImages]
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
     * Obtener imagen primaria de un producto
     */
    public function primaryByProduct($productId) {
        try {
            $this->auth->requirePermission('products');
            
            $image = $this->imageModel->getPrimaryByProduct($productId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $image
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
     * Obtener imagen primaria de una variante
     */
    public function primaryByVariant($variantId) {
        try {
            $this->auth->requirePermission('products');
            
            $image = $this->imageModel->getPrimaryByVariant($variantId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $image
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
     * Obtener estadísticas de imágenes
     */
    public function stats() {
        try {
            $this->auth->requirePermission('products');
            
            $stats = $this->imageModel->getStats();
            
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
}
