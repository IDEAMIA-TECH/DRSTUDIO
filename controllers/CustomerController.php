<?php
/**
 * Controlador CustomerController - DT Studio
 * Manejo de peticiones para gestión de clientes
 */

require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../includes/Auth.php';

class CustomerController {
    private $customerModel;
    private $auth;

    public function __construct() {
        $this->customerModel = new Customer();
        $this->auth = new Auth();
    }

    /**
     * Listar clientes
     */
    public function index() {
        try {
            $this->auth->requirePermission('customers');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->customerModel->getAll($page, $limit, $search, $status);
            
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
     * Obtener cliente por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('customers');
            
            $customer = $this->customerModel->getById($id);
            
            if (!$customer) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $customer
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
     * Crear nuevo cliente
     */
    public function create() {
        try {
            $this->auth->requirePermission('customers');
            
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
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'company' => $_POST['company'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
                'country' => $_POST['country'] ?? 'México',
                'notes' => $_POST['notes'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 1
            ];
            
            // Validar datos
            $errors = $this->customerModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $customerId = $this->customerModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => ['id' => $customerId]
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
     * Actualizar cliente
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('customers');
            
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
            $errors = $this->customerModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->customerModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente'
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
     * Eliminar cliente
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('customers');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->customerModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
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
     * Cambiar estado del cliente
     */
    public function changeStatus($id) {
        try {
            $this->auth->requirePermission('customers');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['0', '1'])) {
                throw new Exception('Estado no válido');
            }
            
            $this->customerModel->changeStatus($id, (int)$status);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del cliente actualizado'
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
     * Buscar clientes
     */
    public function search() {
        try {
            $this->auth->requirePermission('customers');
            
            $query = $_GET['q'] ?? '';
            $limit = $_GET['limit'] ?? 20;
            
            if (empty($query)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            $customers = $this->customerModel->search($query, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $customers
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
     * Obtener clientes más activos
     */
    public function mostActive() {
        try {
            $this->auth->requirePermission('customers');
            
            $limit = $_GET['limit'] ?? 10;
            $customers = $this->customerModel->getMostActive($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $customers
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
     * Obtener clientes por ciudad
     */
    public function byCity($city) {
        try {
            $this->auth->requirePermission('customers');
            
            $limit = $_GET['limit'] ?? 20;
            $customers = $this->customerModel->getByCity($city, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $customers
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
     * Obtener estadísticas de clientes
     */
    public function stats() {
        try {
            $this->auth->requirePermission('customers');
            
            $stats = $this->customerModel->getStats();
            
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
     * Duplicar cliente
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('customers');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $newEmail = $_POST['new_email'] ?? '';
            if (empty($newEmail)) {
                throw new Exception('El nuevo email es requerido');
            }
            
            $newCustomerId = $this->customerModel->duplicate($id, $newEmail);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cliente duplicado exitosamente',
                'data' => ['id' => $newCustomerId]
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
     * Obtener historial del cliente
     */
    public function history($id) {
        try {
            $this->auth->requirePermission('customers');
            
            $history = $this->customerModel->getHistory($id);
            
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
     * Obtener clientes para select
     */
    public function forSelect() {
        try {
            $this->auth->requirePermission('customers');
            
            $customers = $this->customerModel->getForSelect();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $customers
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
