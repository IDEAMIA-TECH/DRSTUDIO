<?php
/**
 * Controlador RoleController - DT Studio
 * Manejo de peticiones para gestión de roles
 */

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../includes/Auth.php';

class RoleController {
    private $roleModel;
    private $auth;

    public function __construct() {
        $this->roleModel = new Role();
        $this->auth = new Auth();
    }

    /**
     * Listar roles
     */
    public function index() {
        try {
            $this->auth->requirePermission('roles');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->roleModel->getAll($page, $limit, $search);
            
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
     * Obtener rol por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('roles');
            
            $role = $this->roleModel->getById($id);
            
            if (!$role) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ]);
                return;
            }
            
            // Decodificar permisos si es JSON
            if ($role['permissions']) {
                $role['permissions'] = json_decode($role['permissions'], true);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $role
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
     * Crear nuevo rol
     */
    public function create() {
        try {
            $this->auth->requirePermission('roles');
            
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
                'description' => $_POST['description'] ?? '',
                'permissions' => $_POST['permissions'] ?? [],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validar datos
            $errors = $this->roleModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $roleId = $this->roleModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Rol creado exitosamente',
                'data' => ['id' => $roleId]
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
     * Actualizar rol
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('roles');
            
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
            $errors = $this->roleModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->roleModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Rol actualizado exitosamente'
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
     * Eliminar rol
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('roles');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->roleModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
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
     * Obtener permisos disponibles
     */
    public function permissions() {
        try {
            $this->auth->requirePermission('roles');
            
            $permissions = $this->roleModel->getAvailablePermissions();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $permissions
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
     * Obtener roles para select
     */
    public function forSelect() {
        try {
            $this->auth->requirePermission('roles');
            
            $roles = $this->roleModel->getForSelect();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $roles
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
     * Obtener estadísticas de roles
     */
    public function stats() {
        try {
            $this->auth->requirePermission('roles');
            
            $stats = $this->roleModel->getStats();
            
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
     * Duplicar rol
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('roles');
            
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
            
            $newRoleId = $this->roleModel->duplicate($id, $newName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Rol duplicado exitosamente',
                'data' => ['id' => $newRoleId]
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
