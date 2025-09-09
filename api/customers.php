<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class CustomerAPI {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    if ($action === 'get_customers') {
                        $this->getCustomers();
                    } elseif ($action === 'get_customer') {
                        $this->getCustomer();
                    } else {
                        $this->getCustomers();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'create_customer') {
                        $this->createCustomer();
                    } else {
                        $this->createCustomer();
                    }
                    break;
                    
                case 'PUT':
                    $this->updateCustomer();
                    break;
                    
                case 'DELETE':
                    $this->deleteCustomer();
                    break;
                    
                default:
                    $this->sendResponse(false, 'Método no permitido', null, 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getCustomers() {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM customers c $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener clientes
        $query = "SELECT c.*, 
                         COUNT(DISTINCT q.id) as total_quotations,
                         COUNT(DISTINCT o.id) as total_orders
                  FROM customers c
                  LEFT JOIN quotations q ON c.id = q.customer_id
                  LEFT JOIN orders o ON c.id = o.customer_id
                  $whereClause
                  GROUP BY c.id
                  ORDER BY c.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar clientes
        foreach ($customers as &$customer) {
            $customer['active'] = (bool)$customer['active'];
            $customer['total_quotations'] = (int)$customer['total_quotations'];
            $customer['total_orders'] = (int)$customer['total_orders'];
        }
        
        $this->sendResponse(true, 'Clientes obtenidos exitosamente', [
            'customers' => $customers,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getCustomer() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cliente requerido', null, 400);
            return;
        }
        
        $query = "SELECT c.*, 
                         COUNT(DISTINCT q.id) as total_quotations,
                         COUNT(DISTINCT o.id) as total_orders
                  FROM customers c
                  LEFT JOIN quotations q ON c.id = q.customer_id
                  LEFT JOIN orders o ON c.id = o.customer_id
                  WHERE c.id = ?
                  GROUP BY c.id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            $this->sendResponse(false, 'Cliente no encontrado', null, 404);
            return;
        }
        
        $customer['active'] = (bool)$customer['active'];
        $customer['total_quotations'] = (int)$customer['total_quotations'];
        $customer['total_orders'] = (int)$customer['total_orders'];
        
        $this->sendResponse(true, 'Cliente obtenido exitosamente', $customer);
    }
    
    private function createCustomer() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->sendResponse(false, "Campo requerido: $field", null, 400);
                return;
            }
        }
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(false, 'Email inválido', null, 400);
            return;
        }
        
        try {
            $query = "INSERT INTO customers (name, email, phone, company, address, 
                                           notes, active, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['company'] ?? '',
                $data['address'] ?? '',
                $data['notes'] ?? '',
            ]);
            
            $customerId = $this->db->lastInsertId();
            
            $this->sendResponse(true, 'Cliente creado exitosamente', ['id' => $customerId]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al crear cliente: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function updateCustomer() {
        $id = $_GET['id'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cliente requerido', null, 400);
            return;
        }
        
        // Validar email si se proporciona
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(false, 'Email inválido', null, 400);
            return;
        }
        
        try {
            $query = "UPDATE customers SET 
                         name = ?, email = ?, phone = ?, company = ?, 
                         address = ?, notes = ?, active = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['company'] ?? '',
                $data['address'] ?? '',
                $data['notes'] ?? '',
                $data['active'] ?? 1,
                $id
            ]);
            
            $this->sendResponse(true, 'Cliente actualizado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al actualizar cliente: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function deleteCustomer() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cliente requerido', null, 400);
            return;
        }
        
        try {
            // Verificar si tiene cotizaciones o pedidos
            $checkQuery = "SELECT COUNT(*) as count FROM quotations WHERE customer_id = ? 
                          UNION ALL 
                          SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$id, $id]);
            $results = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalReferences = $results[0]['count'] + $results[1]['count'];
            
            if ($totalReferences > 0) {
                $this->sendResponse(false, 'No se puede eliminar el cliente porque tiene cotizaciones o pedidos asociados', null, 400);
                return;
            }
            
            $query = "DELETE FROM customers WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            $this->sendResponse(true, 'Cliente eliminado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al eliminar cliente: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function sendResponse($success, $message, $data = null, $httpCode = 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
}

$api = new CustomerAPI();
$api->handleRequest();
?>