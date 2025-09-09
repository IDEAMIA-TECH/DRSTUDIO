<?php
/**
 * Controlador QuotationController - DT Studio
 * Manejo de peticiones para gestión de cotizaciones
 */

require_once __DIR__ . '/../models/Quotation.php';
require_once __DIR__ . '/../models/QuotationItem.php';
require_once __DIR__ . '/../includes/Auth.php';

class QuotationController {
    private $quotationModel;
    private $quotationItemModel;
    private $auth;

    public function __construct() {
        $this->quotationModel = new Quotation();
        $this->quotationItemModel = new QuotationItem();
        $this->auth = new Auth();
    }

    /**
     * Listar cotizaciones
     */
    public function index() {
        try {
            $this->auth->requirePermission('quotations');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? null;
            $customerId = $_GET['customer_id'] ?? null;
            $userId = $_GET['user_id'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->quotationModel->getAll($page, $limit, $search, $status, $customerId, $userId);
            
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
     * Obtener cotización por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('quotations');
            
            $quotation = $this->quotationModel->getById($id);
            
            if (!$quotation) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ]);
                return;
            }
            
            // Obtener items de la cotización
            $items = $this->quotationItemModel->getByQuotation($id);
            $quotation['items'] = $items;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotation
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
     * Crear nueva cotización
     */
    public function create() {
        try {
            $this->auth->requirePermission('quotations');
            
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
                'customer_id' => $_POST['customer_id'] ?? '',
                'user_id' => $user['id'],
                'quotation_number' => $_POST['quotation_number'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'subtotal' => $_POST['subtotal'] ?? 0.00,
                'tax_rate' => $_POST['tax_rate'] ?? 0.00,
                'valid_until' => $_POST['valid_until'] ?? null,
                'notes' => $_POST['notes'] ?? ''
            ];
            
            // Validar datos
            $errors = $this->quotationModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $quotationId = $this->quotationModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => ['id' => $quotationId]
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
     * Actualizar cotización
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('quotations');
            
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
            $errors = $this->quotationModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->quotationModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente'
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
     * Eliminar cotización
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('quotations');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->quotationModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
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
     * Cambiar estado de la cotización
     */
    public function changeStatus($id) {
        try {
            $this->auth->requirePermission('quotations');
            
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
            
            $this->quotationModel->changeStatus($id, $status);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado de la cotización actualizado'
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
     * Convertir cotización a pedido
     */
    public function convertToOrder($id) {
        try {
            $this->auth->requirePermission('quotations');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $orderNumber = $_POST['order_number'] ?? '';
            if (empty($orderNumber)) {
                throw new Exception('El número de pedido es requerido');
            }
            
            $orderId = $this->quotationModel->convertToOrder($id, $orderNumber);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización convertida a pedido exitosamente',
                'data' => ['order_id' => $orderId]
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
     * Obtener cotizaciones por cliente
     */
    public function byCustomer($customerId) {
        try {
            $this->auth->requirePermission('quotations');
            
            $limit = $_GET['limit'] ?? 20;
            $quotations = $this->quotationModel->getByCustomer($customerId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotations
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
     * Obtener cotizaciones por usuario
     */
    public function byUser($userId) {
        try {
            $this->auth->requirePermission('quotations');
            
            $limit = $_GET['limit'] ?? 20;
            $quotations = $this->quotationModel->getByUser($userId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotations
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
     * Obtener cotizaciones vencidas
     */
    public function expired() {
        try {
            $this->auth->requirePermission('quotations');
            
            $quotations = $this->quotationModel->getExpired();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotations
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
     * Obtener estadísticas de cotizaciones
     */
    public function stats() {
        try {
            $this->auth->requirePermission('quotations');
            
            $stats = $this->quotationModel->getStats();
            
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
     * Duplicar cotización
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('quotations');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $newNumber = $_POST['new_number'] ?? '';
            $newQuotationId = $this->quotationModel->duplicate($id, $newNumber);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización duplicada exitosamente',
                'data' => ['id' => $newQuotationId]
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
     * Agregar item a cotización
     */
    public function addItem($quotationId) {
        try {
            $this->auth->requirePermission('quotations');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $data = [
                'quotation_id' => $quotationId,
                'product_id' => $_POST['product_id'] ?? '',
                'variant_id' => $_POST['variant_id'] ?? null,
                'quantity' => $_POST['quantity'] ?? '',
                'unit_price' => $_POST['unit_price'] ?? '',
                'notes' => $_POST['notes'] ?? ''
            ];
            
            // Validar datos
            $errors = $this->quotationItemModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $itemId = $this->quotationItemModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Item agregado exitosamente',
                'data' => ['id' => $itemId]
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
     * Actualizar item de cotización
     */
    public function updateItem($itemId) {
        try {
            $this->auth->requirePermission('quotations');
            
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
            $errors = $this->quotationItemModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->quotationItemModel->update($itemId, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Item actualizado exitosamente'
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
     * Eliminar item de cotización
     */
    public function deleteItem($itemId) {
        try {
            $this->auth->requirePermission('quotations');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->quotationItemModel->delete($itemId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Item eliminado exitosamente'
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
     * Obtener items de cotización
     */
    public function getItems($quotationId) {
        try {
            $this->auth->requirePermission('quotations');
            
            $items = $this->quotationItemModel->getByQuotation($quotationId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $items
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
