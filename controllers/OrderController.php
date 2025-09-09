<?php
/**
 * Controlador OrderController - DT Studio
 * Manejo de peticiones para gestión de pedidos
 */

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/Auth.php';

class OrderController {
    private $orderModel;
    private $orderItemModel;
    private $auth;

    public function __construct() {
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->auth = new Auth();
    }

    /**
     * Listar pedidos
     */
    public function index() {
        try {
            $this->auth->requirePermission('orders');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? null;
            $paymentStatus = $_GET['payment_status'] ?? null;
            $customerId = $_GET['customer_id'] ?? null;
            $userId = $_GET['user_id'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->orderModel->getAll($page, $limit, $search, $status, $paymentStatus, $customerId, $userId);
            
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
     * Obtener pedido por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('orders');
            
            $order = $this->orderModel->getById($id);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ]);
                return;
            }
            
            // Obtener items del pedido
            $items = $this->orderItemModel->getByOrder($id);
            $order['items'] = $items;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $order
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
     * Crear nuevo pedido
     */
    public function create() {
        try {
            $this->auth->requirePermission('orders');
            
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
                'quotation_id' => $_POST['quotation_id'] ?? null,
                'customer_id' => $_POST['customer_id'] ?? '',
                'created_by' => $user['id'],
                'order_number' => $_POST['order_number'] ?? '',
                'status' => $_POST['status'] ?? 'pending',
                'payment_status' => $_POST['payment_status'] ?? 'pending',
                'subtotal' => $_POST['subtotal'] ?? 0.00,
                'tax_amount' => $_POST['tax_amount'] ?? 0.00,
                'shipping_address' => $_POST['shipping_address'] ?? '',
                'billing_address' => $_POST['billing_address'] ?? '',
                'notes' => $_POST['notes'] ?? '',
                'delivery_date' => $_POST['delivery_date'] ?? null
            ];
            
            // Validar datos
            $errors = $this->orderModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $orderId = $this->orderModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'data' => ['id' => $orderId]
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
     * Actualizar pedido
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('orders');
            
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
            $errors = $this->orderModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->orderModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente'
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
     * Eliminar pedido
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->orderModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pedido eliminado exitosamente'
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
     * Cambiar estado del pedido
     */
    public function changeStatus($id) {
        try {
            $this->auth->requirePermission('orders');
            
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
            
            $this->orderModel->changeStatus($id, $status);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del pedido actualizado'
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
     * Cambiar estado de pago
     */
    public function changePaymentStatus($id) {
        try {
            $this->auth->requirePermission('orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $paymentStatus = $_POST['payment_status'] ?? '';
            if (empty($paymentStatus)) {
                throw new Exception('El estado de pago es requerido');
            }
            
            $this->orderModel->changePaymentStatus($id, $paymentStatus);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado de pago actualizado'
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
     * Obtener pedidos por cliente
     */
    public function byCustomer($customerId) {
        try {
            $this->auth->requirePermission('orders');
            
            $limit = $_GET['limit'] ?? 20;
            $orders = $this->orderModel->getByCustomer($customerId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orders
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
     * Obtener pedidos por usuario
     */
    public function byUser($userId) {
        try {
            $this->auth->requirePermission('orders');
            
            $limit = $_GET['limit'] ?? 20;
            $orders = $this->orderModel->getByUser($userId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orders
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
     * Obtener pedidos pendientes
     */
    public function pending() {
        try {
            $this->auth->requirePermission('orders');
            
            $orders = $this->orderModel->getPending();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orders
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
     * Obtener pedidos por entregar
     */
    public function toDeliver() {
        try {
            $this->auth->requirePermission('orders');
            
            $orders = $this->orderModel->getToDeliver();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orders
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
     * Obtener estadísticas de pedidos
     */
    public function stats() {
        try {
            $this->auth->requirePermission('orders');
            
            $stats = $this->orderModel->getStats();
            
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
     * Duplicar pedido
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $newNumber = $_POST['new_number'] ?? '';
            $newOrderId = $this->orderModel->duplicate($id, $newNumber);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pedido duplicado exitosamente',
                'data' => ['id' => $newOrderId]
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
     * Obtener historial del pedido
     */
    public function history($id) {
        try {
            $this->auth->requirePermission('orders');
            
            $history = $this->orderModel->getHistory($id);
            
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

    /**
     * Agregar item a pedido
     */
    public function addItem($orderId) {
        try {
            $this->auth->requirePermission('orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $data = [
                'order_id' => $orderId,
                'product_id' => $_POST['product_id'] ?? '',
                'variant_id' => $_POST['variant_id'] ?? null,
                'quantity' => $_POST['quantity'] ?? '',
                'unit_price' => $_POST['unit_price'] ?? '',
                'notes' => $_POST['notes'] ?? ''
            ];
            
            // Validar datos
            $errors = $this->orderItemModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $itemId = $this->orderItemModel->create($data);
            
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
     * Actualizar item de pedido
     */
    public function updateItem($itemId) {
        try {
            $this->auth->requirePermission('orders');
            
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
            $errors = $this->orderItemModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->orderItemModel->update($itemId, $data);
            
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
     * Eliminar item de pedido
     */
    public function deleteItem($itemId) {
        try {
            $this->auth->requirePermission('orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->orderItemModel->delete($itemId);
            
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
     * Obtener items de pedido
     */
    public function getItems($orderId) {
        try {
            $this->auth->requirePermission('orders');
            
            $items = $this->orderItemModel->getByOrder($orderId);
            
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

    /**
     * Obtener pedidos para select
     */
    public function forSelect() {
        try {
            $this->auth->requirePermission('orders');
            
            $orders = $this->orderModel->getForSelect();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orders
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
