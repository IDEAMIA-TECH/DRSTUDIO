<?php
/**
 * Controlador PaymentController - DT Studio
 * Manejo de peticiones para el sistema de pagos
 */

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/PaymentGateway.php';

class PaymentController {
    private $paymentModel;
    private $gatewayModel;

    public function __construct() {
        $this->paymentModel = new Payment();
        $this->gatewayModel = new PaymentGateway();
    }

    /**
     * Crear pago
     */
    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->paymentModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $paymentId = $this->paymentModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pago creado exitosamente',
                'data' => ['payment_id' => $paymentId]
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
     * Obtener pago por ID
     */
    public function getById($id) {
        try {
            $payment = $this->paymentModel->getById($id);
            
            if (!$payment) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $payment
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
     * Obtener pago por referencia
     */
    public function getByReference($reference) {
        try {
            $payment = $this->paymentModel->getByReference($reference);
            
            if (!$payment) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $payment
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
     * Obtener pagos por orden
     */
    public function getByOrderId($orderId) {
        try {
            $payments = $this->paymentModel->getByOrderId($orderId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $payments
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
     * Listar pagos
     */
    public function getAll() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'method' => $_GET['method'] ?? null,
                'gateway' => $_GET['gateway'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'min_amount' => $_GET['min_amount'] ?? null,
                'max_amount' => $_GET['max_amount'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->paymentModel->getAll($filters, $page, $limit);
            
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
     * Actualizar pago
     */
    public function update($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->paymentModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pago actualizado exitosamente'
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
     * Eliminar pago
     */
    public function delete($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->paymentModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
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
     * Procesar pago
     */
    public function process($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $this->paymentModel->processPayment($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pago procesado',
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
     * Reembolsar pago
     */
    public function refund($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $refundId = $this->paymentModel->refund(
                $id, 
                $input['amount'] ?? null, 
                $input['reason'] ?? ''
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reembolso procesado exitosamente',
                'data' => ['refund_id' => $refundId]
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
     * Obtener estadísticas de pagos
     */
    public function getStats() {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $stats = $this->paymentModel->getStats($filters);
            
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
     * Obtener pagos pendientes
     */
    public function getPending() {
        try {
            $payments = $this->paymentModel->getPendingPayments();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $payments
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
     * Obtener pagos por fecha
     */
    public function getByDate($date) {
        try {
            $payments = $this->paymentModel->getPaymentsByDate($date);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $payments
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA PASARELAS DE PAGO =====

    /**
     * Crear pasarela de pago
     */
    public function createGateway() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->gatewayModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $gatewayId = $this->gatewayModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pasarela creada exitosamente',
                'data' => ['gateway_id' => $gatewayId]
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
     * Obtener pasarela por ID
     */
    public function getGatewayById($id) {
        try {
            $gateway = $this->gatewayModel->getById($id);
            
            if (!$gateway) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pasarela no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $gateway
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
     * Listar pasarelas
     */
    public function getAllGateways() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'type' => $_GET['type'] ?? null,
                'is_active' => $_GET['is_active'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->gatewayModel->getAll($filters, $page, $limit);
            
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
     * Obtener pasarelas activas
     */
    public function getActiveGateways() {
        try {
            $gateways = $this->gatewayModel->getActive();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $gateways
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
     * Actualizar pasarela
     */
    public function updateGateway($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->gatewayModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pasarela actualizada exitosamente'
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
     * Eliminar pasarela
     */
    public function deleteGateway($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->gatewayModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pasarela eliminada exitosamente'
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
     * Activar/desactivar pasarela
     */
    public function toggleGatewayStatus($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $newStatus = $this->gatewayModel->toggleStatus($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado de pasarela actualizado',
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
     * Procesar pago con pasarela
     */
    public function processWithGateway() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['gateway_name']) || empty($input['payment_data'])) {
                throw new Exception('Gateway y datos de pago son requeridos');
            }
            
            $result = $this->gatewayModel->processPayment($input['gateway_name'], $input['payment_data']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pago procesado con pasarela',
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
     * Obtener estadísticas de pasarelas
     */
    public function getGatewayStats() {
        try {
            $stats = $this->gatewayModel->getStats();
            
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
     * Validar configuración de pasarela
     */
    public function validateGatewayConfig() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['type']) || empty($input['config'])) {
                throw new Exception('Tipo y configuración son requeridos');
            }
            
            $errors = $this->gatewayModel->validateConfig($input['type'], $input['config']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => empty($errors),
                'message' => empty($errors) ? 'Configuración válida' : 'Configuración inválida',
                'errors' => $errors
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
