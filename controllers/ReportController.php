<?php
/**
 * Controlador ReportController - DT Studio
 * Manejo de peticiones para gestión de reportes y analytics
 */

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../includes/Auth.php';

class ReportController {
    private $reportModel;
    private $analyticsModel;
    private $auth;

    public function __construct() {
        $this->reportModel = new Report();
        $this->analyticsModel = new Analytics();
        $this->auth = new Auth();
    }

    /**
     * Listar reportes
     */
    public function index() {
        try {
            $this->auth->requirePermission('reports');
            
            $page = $_GET['page'] ?? 1;
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? null;
            $userId = $_GET['user_id'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            $result = $this->reportModel->getAll($page, $limit, $search, $type, $userId);
            
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
     * Obtener reporte por ID
     */
    public function show($id) {
        try {
            $this->auth->requirePermission('reports');
            
            $report = $this->reportModel->getById($id);
            
            if (!$report) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $report
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
     * Crear nuevo reporte
     */
    public function create() {
        try {
            $this->auth->requirePermission('reports');
            
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
                'type' => $_POST['type'] ?? '',
                'user_id' => $user['id'],
                'config' => $_POST['config'] ?? '{}',
                'is_public' => isset($_POST['is_public']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 1
            ];
            
            // Validar datos
            $errors = $this->reportModel->validate($data, false);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $reportId = $this->reportModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reporte creado exitosamente',
                'data' => ['id' => $reportId]
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
     * Actualizar reporte
     */
    public function update($id) {
        try {
            $this->auth->requirePermission('reports');
            
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
            $errors = $this->reportModel->validate($data, true);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $this->reportModel->update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reporte actualizado exitosamente'
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
     * Eliminar reporte
     */
    public function delete($id) {
        try {
            $this->auth->requirePermission('reports');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $this->reportModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente'
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
     * Cambiar estado del reporte
     */
    public function changeStatus($id) {
        try {
            $this->auth->requirePermission('reports');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : false;
            
            $this->reportModel->changeStatus($id, $isActive);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Estado del reporte actualizado'
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
     * Obtener reportes por tipo
     */
    public function byType($type) {
        try {
            $this->auth->requirePermission('reports');
            
            $limit = $_GET['limit'] ?? 20;
            $reports = $this->reportModel->getByType($type, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $reports
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
     * Obtener reportes públicos
     */
    public function public() {
        try {
            $this->auth->requirePermission('reports');
            
            $limit = $_GET['limit'] ?? 20;
            $reports = $this->reportModel->getPublic($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $reports
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
     * Obtener reportes por usuario
     */
    public function byUser($userId) {
        try {
            $this->auth->requirePermission('reports');
            
            $limit = $_GET['limit'] ?? 20;
            $reports = $this->reportModel->getByUser($userId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $reports
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
     * Duplicar reporte
     */
    public function duplicate($id) {
        try {
            $this->auth->requirePermission('reports');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $newName = $_POST['new_name'] ?? '';
            $newReportId = $this->reportModel->duplicate($id, $newName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reporte duplicado exitosamente',
                'data' => ['id' => $newReportId]
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
     * Obtener tipos de reportes disponibles
     */
    public function types() {
        try {
            $this->auth->requirePermission('reports');
            
            $types = $this->reportModel->getAvailableTypes();
            
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

    /**
     * Obtener plantillas de reportes
     */
    public function templates() {
        try {
            $this->auth->requirePermission('reports');
            
            $templates = $this->reportModel->getTemplates();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $templates
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
     * Crear reporte desde plantilla
     */
    public function createFromTemplate() {
        try {
            $this->auth->requirePermission('reports');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->auth->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            $templateKey = $_POST['template_key'] ?? '';
            $customName = $_POST['custom_name'] ?? '';
            
            if (empty($templateKey)) {
                throw new Exception('La plantilla es requerida');
            }
            
            $user = $this->auth->getCurrentUser();
            $reportId = $this->reportModel->createFromTemplate($templateKey, $user['id'], $customName);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Reporte creado desde plantilla exitosamente',
                'data' => ['id' => $reportId]
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
     * Obtener métricas del dashboard
     */
    public function dashboard() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getDashboardMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de ventas
     */
    public function sales() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $groupBy = $_GET['group_by'] ?? 'day';
            $metrics = $this->analyticsModel->getSalesMetrics($period, $groupBy);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de productos
     */
    public function products() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $limit = $_GET['limit'] ?? 10;
            $metrics = $this->analyticsModel->getProductMetrics($period, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de clientes
     */
    public function customers() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $limit = $_GET['limit'] ?? 10;
            $metrics = $this->analyticsModel->getCustomerMetrics($period, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de cotizaciones
     */
    public function quotations() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getQuotationMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de pedidos
     */
    public function orders() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getOrderMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas financieras
     */
    public function financial() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getFinancialMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener tendencias de crecimiento
     */
    public function trends() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'year';
            $trends = $this->analyticsModel->getGrowthTrends($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $trends
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
     * Obtener métricas geográficas
     */
    public function geographic() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getGeographicMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas de rendimiento
     */
    public function performance() {
        try {
            $this->auth->requirePermission('reports');
            
            $period = $_GET['period'] ?? 'month';
            $metrics = $this->analyticsModel->getPerformanceMetrics($period);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
     * Obtener métricas personalizadas
     */
    public function custom() {
        try {
            $this->auth->requirePermission('reports');
            
            $config = $_GET['config'] ?? '{}';
            $config = json_decode($config, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Configuración JSON inválida');
            }
            
            $metrics = $this->analyticsModel->getCustomMetrics($config);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $metrics
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
