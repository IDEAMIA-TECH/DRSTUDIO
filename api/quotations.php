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

class QuotationAPI {
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
                    if ($action === 'get_quotations') {
                        $this->getQuotations();
                    } elseif ($action === 'get_quotation') {
                        $this->getQuotation();
                    } else {
                        $this->getQuotations();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'create_quotation') {
                        $this->createQuotation();
                    } else {
                        $this->createQuotation();
                    }
                    break;
                    
                case 'PUT':
                    $this->updateQuotation();
                    break;
                    
                case 'DELETE':
                    $this->deleteQuotation();
                    break;
                    
                default:
                    $this->sendResponse(false, 'Método no permitido', null, 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getQuotations() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $customer_id = $_GET['customer_id'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(q.quotation_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = "q.status = ?";
            $params[] = $status;
        }
        
        if (!empty($customer_id)) {
            $whereConditions[] = "q.customer_id = ?";
            $params[] = $customer_id;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM quotations q
                      LEFT JOIN customers c ON q.customer_id = c.id
                      $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener cotizaciones
        $query = "SELECT q.*, c.name as customer_name, c.email as customer_email,
                         c.company as customer_company
                  FROM quotations q
                  LEFT JOIN customers c ON q.customer_id = c.id
                  $whereClause
                  ORDER BY q.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar cotizaciones
        foreach ($quotations as &$quotation) {
            $quotation['total_amount'] = (float)$quotation['total_amount'];
            $quotation['valid_until'] = $quotation['valid_until'] ? date('Y-m-d', strtotime($quotation['valid_until'])) : null;
        }
        
        $this->sendResponse(true, 'Cotizaciones obtenidas exitosamente', [
            'quotations' => $quotations,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getQuotation() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cotización requerido', null, 400);
            return;
        }
        
        $query = "SELECT q.*, c.name as customer_name, c.email as customer_email,
                         c.company as customer_company, c.phone as customer_phone,
                         c.address as customer_address
                  FROM quotations q
                  LEFT JOIN customers c ON q.customer_id = c.id
                  WHERE q.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quotation) {
            $this->sendResponse(false, 'Cotización no encontrada', null, 404);
            return;
        }
        
        // Obtener productos de la cotización
        $productsQuery = "SELECT qp.*, p.name as product_name, p.description as product_description
                         FROM quotation_products qp
                         LEFT JOIN products p ON qp.product_id = p.id
                         WHERE qp.quotation_id = ?";
        
        $productsStmt = $this->db->prepare($productsQuery);
        $productsStmt->execute([$id]);
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotation['products'] = $products;
        $quotation['total_amount'] = (float)$quotation['total_amount'];
        $quotation['valid_until'] = $quotation['valid_until'] ? date('Y-m-d', strtotime($quotation['valid_until'])) : null;
        
        $this->sendResponse(true, 'Cotización obtenida exitosamente', $quotation);
    }
    
    private function createQuotation() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['customer_id', 'products'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->sendResponse(false, "Campo requerido: $field", null, 400);
                return;
            }
        }
        
        if (empty($data['products']) || !is_array($data['products'])) {
            $this->sendResponse(false, 'Debe incluir al menos un producto', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Generar número de cotización
            $quotationNumber = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calcular total
            $totalAmount = 0;
            foreach ($data['products'] as $product) {
                $totalAmount += ($product['price'] * $product['quantity']);
            }
            
            // Crear cotización
            $query = "INSERT INTO quotations (quotation_number, customer_id, total_amount, 
                                            status, notes, valid_until, created_at, updated_at) 
                      VALUES (?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $quotationNumber,
                $data['customer_id'],
                $totalAmount,
                $data['notes'] ?? '',
                $data['valid_until'] ?? date('Y-m-d', strtotime('+30 days'))
            ]);
            
            $quotationId = $this->db->lastInsertId();
            
            // Insertar productos
            $productQuery = "INSERT INTO quotation_products (quotation_id, product_id, quantity, price, notes) 
                            VALUES (?, ?, ?, ?, ?)";
            $productStmt = $this->db->prepare($productQuery);
            
            foreach ($data['products'] as $product) {
                $productStmt->execute([
                    $quotationId,
                    $product['product_id'],
                    $product['quantity'],
                    $product['price'],
                    $product['notes'] ?? ''
                ]);
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Cotización creada exitosamente', [
                'id' => $quotationId,
                'quotation_number' => $quotationNumber
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al crear cotización: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function updateQuotation() {
        $id = $_GET['id'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cotización requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar cotización
            $query = "UPDATE quotations SET 
                         status = ?, notes = ?, valid_until = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['status'],
                $data['notes'] ?? '',
                $data['valid_until'] ?? null,
                $id
            ]);
            
            // Si se proporcionan productos, actualizarlos
            if (isset($data['products']) && is_array($data['products'])) {
                // Eliminar productos existentes
                $deleteQuery = "DELETE FROM quotation_products WHERE quotation_id = ?";
                $deleteStmt = $this->db->prepare($deleteQuery);
                $deleteStmt->execute([$id]);
                
                // Insertar nuevos productos
                $productQuery = "INSERT INTO quotation_products (quotation_id, product_id, quantity, price, notes) 
                                VALUES (?, ?, ?, ?, ?)";
                $productStmt = $this->db->prepare($productQuery);
                
                $totalAmount = 0;
                foreach ($data['products'] as $product) {
                    $productStmt->execute([
                        $id,
                        $product['product_id'],
                        $product['quantity'],
                        $product['price'],
                        $product['notes'] ?? ''
                    ]);
                    $totalAmount += ($product['price'] * $product['quantity']);
                }
                
                // Actualizar total
                $updateTotalQuery = "UPDATE quotations SET total_amount = ? WHERE id = ?";
                $updateTotalStmt = $this->db->prepare($updateTotalQuery);
                $updateTotalStmt->execute([$totalAmount, $id]);
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Cotización actualizada exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al actualizar cotización: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function deleteQuotation() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de cotización requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Eliminar productos de la cotización
            $deleteProductsQuery = "DELETE FROM quotation_products WHERE quotation_id = ?";
            $deleteProductsStmt = $this->db->prepare($deleteProductsQuery);
            $deleteProductsStmt->execute([$id]);
            
            // Eliminar cotización
            $deleteQuery = "DELETE FROM quotations WHERE id = ?";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$id]);
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Cotización eliminada exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al eliminar cotización: ' . $e->getMessage(), null, 500);
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

$api = new QuotationAPI();
$api->handleRequest();
?>