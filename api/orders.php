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

class OrderAPI {
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
                    if ($action === 'get_orders') {
                        $this->getOrders();
                    } elseif ($action === 'get_order') {
                        $this->getOrder();
                    } else {
                        $this->getOrders();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'create_order') {
                        $this->createOrder();
                    } else {
                        $this->createOrder();
                    }
                    break;
                    
                case 'PUT':
                    $this->updateOrder();
                    break;
                    
                case 'DELETE':
                    $this->deleteOrder();
                    break;
                    
                default:
                    $this->sendResponse(false, 'Método no permitido', null, 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getOrders() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $customer_id = $_GET['customer_id'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = "o.status = ?";
            $params[] = $status;
        }
        
        if (!empty($customer_id)) {
            $whereConditions[] = "o.customer_id = ?";
            $params[] = $customer_id;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM orders o
                      LEFT JOIN customers c ON o.customer_id = c.id
                      $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener pedidos
        $query = "SELECT o.*, c.name as customer_name, c.email as customer_email,
                         c.company as customer_company
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  $whereClause
                  ORDER BY o.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar pedidos
        foreach ($orders as &$order) {
            $order['total_amount'] = (float)$order['total_amount'];
            $order['delivery_date'] = $order['delivery_date'] ? date('Y-m-d', strtotime($order['delivery_date'])) : null;
        }
        
        $this->sendResponse(true, 'Pedidos obtenidos exitosamente', [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getOrder() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de pedido requerido', null, 400);
            return;
        }
        
        $query = "SELECT o.*, c.name as customer_name, c.email as customer_email,
                         c.company as customer_company, c.phone as customer_phone,
                         c.address as customer_address
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE o.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $this->sendResponse(false, 'Pedido no encontrado', null, 404);
            return;
        }
        
        // Obtener productos del pedido
        $productsQuery = "SELECT op.*, p.name as product_name, p.description as product_description
                         FROM order_products op
                         LEFT JOIN products p ON op.product_id = p.id
                         WHERE op.order_id = ?";
        
        $productsStmt = $this->db->prepare($productsQuery);
        $productsStmt->execute([$id]);
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $order['products'] = $products;
        $order['total_amount'] = (float)$order['total_amount'];
        $order['delivery_date'] = $order['delivery_date'] ? date('Y-m-d', strtotime($order['delivery_date'])) : null;
        
        $this->sendResponse(true, 'Pedido obtenido exitosamente', $order);
    }
    
    private function createOrder() {
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
            
            // Generar número de pedido
            $orderNumber = 'PED-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calcular total
            $totalAmount = 0;
            foreach ($data['products'] as $product) {
                $totalAmount += ($product['price'] * $product['quantity']);
            }
            
            // Crear pedido
            $query = "INSERT INTO orders (order_number, customer_id, total_amount, 
                                        status, notes, delivery_date, created_at, updated_at) 
                      VALUES (?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $orderNumber,
                $data['customer_id'],
                $totalAmount,
                $data['notes'] ?? '',
                $data['delivery_date'] ?? date('Y-m-d', strtotime('+15 days'))
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Insertar productos
            $productQuery = "INSERT INTO order_products (order_id, product_id, quantity, price, notes) 
                            VALUES (?, ?, ?, ?, ?)";
            $productStmt = $this->db->prepare($productQuery);
            
            foreach ($data['products'] as $product) {
                $productStmt->execute([
                    $orderId,
                    $product['product_id'],
                    $product['quantity'],
                    $product['price'],
                    $product['notes'] ?? ''
                ]);
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Pedido creado exitosamente', [
                'id' => $orderId,
                'order_number' => $orderNumber
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al crear pedido: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function updateOrder() {
        $id = $_GET['id'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de pedido requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar pedido
            $query = "UPDATE orders SET 
                         status = ?, notes = ?, delivery_date = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['status'],
                $data['notes'] ?? '',
                $data['delivery_date'] ?? null,
                $id
            ]);
            
            // Si se proporcionan productos, actualizarlos
            if (isset($data['products']) && is_array($data['products'])) {
                // Eliminar productos existentes
                $deleteQuery = "DELETE FROM order_products WHERE order_id = ?";
                $deleteStmt = $this->db->prepare($deleteQuery);
                $deleteStmt->execute([$id]);
                
                // Insertar nuevos productos
                $productQuery = "INSERT INTO order_products (order_id, product_id, quantity, price, notes) 
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
                $updateTotalQuery = "UPDATE orders SET total_amount = ? WHERE id = ?";
                $updateTotalStmt = $this->db->prepare($updateTotalQuery);
                $updateTotalStmt->execute([$totalAmount, $id]);
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Pedido actualizado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al actualizar pedido: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function deleteOrder() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de pedido requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Eliminar productos del pedido
            $deleteProductsQuery = "DELETE FROM order_products WHERE order_id = ?";
            $deleteProductsStmt = $this->db->prepare($deleteProductsQuery);
            $deleteProductsStmt->execute([$id]);
            
            // Eliminar pedido
            $deleteQuery = "DELETE FROM orders WHERE id = ?";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$id]);
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Pedido eliminado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al eliminar pedido: ' . $e->getMessage(), null, 500);
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

$api = new OrderAPI();
$api->handleRequest();
?>