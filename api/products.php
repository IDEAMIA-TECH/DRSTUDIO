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

class ProductAPI {
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
                    if ($action === 'get_products') {
                        $this->getProducts();
                    } elseif ($action === 'get_product') {
                        $this->getProduct();
                    } else {
                        $this->getProducts();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'create_product') {
                        $this->createProduct();
                    } else {
                        $this->createProduct();
                    }
                    break;
                    
                case 'PUT':
                    $this->updateProduct();
                    break;
                    
                case 'DELETE':
                    $this->deleteProduct();
                    break;
                    
                default:
                    $this->sendResponse(false, 'Método no permitido', null, 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getProducts() {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category)) {
            $whereConditions[] = "p.category = ?";
            $params[] = $category;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener productos
        $query = "SELECT p.*, 
                         GROUP_CONCAT(DISTINCT pi.image_url) as images
                  FROM products p
                  LEFT JOIN product_images pi ON p.id = pi.product_id
                  $whereClause
                  GROUP BY p.id
                  ORDER BY p.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar productos
        foreach ($products as &$product) {
            $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
            $product['price'] = (float)$product['price'];
            $product['featured'] = (bool)$product['featured'];
            $product['active'] = (bool)$product['active'];
        }
        
        $this->sendResponse(true, 'Productos obtenidos exitosamente', [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getProduct() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de producto requerido', null, 400);
            return;
        }
        
        $query = "SELECT p.*, 
                         GROUP_CONCAT(DISTINCT pi.image_url) as images
                  FROM products p
                  LEFT JOIN product_images pi ON p.id = pi.product_id
                  WHERE p.id = ?
                  GROUP BY p.id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $this->sendResponse(false, 'Producto no encontrado', null, 404);
            return;
        }
        
        $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
        $product['price'] = (float)$product['price'];
        $product['featured'] = (bool)$product['featured'];
        $product['active'] = (bool)$product['active'];
        
        $this->sendResponse(true, 'Producto obtenido exitosamente', $product);
    }
    
    private function createProduct() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'description', 'price', 'category', 'material'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->sendResponse(false, "Campo requerido: $field", null, 400);
                return;
            }
        }
        
        try {
            $this->db->beginTransaction();
            
            $query = "INSERT INTO products (name, description, price, category, material, 
                                          featured, active, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $data['category'],
                $data['material'],
                $data['featured'] ?? 0
            ]);
            
            $productId = $this->db->lastInsertId();
            
            // Insertar imágenes si existen
            if (!empty($data['images'])) {
                $imageQuery = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
                $imageStmt = $this->db->prepare($imageQuery);
                
                foreach ($data['images'] as $index => $imageUrl) {
                    $imageStmt->execute([$productId, $imageUrl, $index === 0 ? 1 : 0]);
                }
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Producto creado exitosamente', ['id' => $productId]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al crear producto: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function updateProduct() {
        $id = $_GET['id'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de producto requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            $query = "UPDATE products SET 
                         name = ?, description = ?, price = ?, category = ?, 
                         material = ?, featured = ?, active = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $data['category'],
                $data['material'],
                $data['featured'] ?? 0,
                $data['active'] ?? 1,
                $id
            ]);
            
            // Actualizar imágenes
            if (isset($data['images'])) {
                $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = ?";
                $deleteImagesStmt = $this->db->prepare($deleteImagesQuery);
                $deleteImagesStmt->execute([$id]);
                
                if (!empty($data['images'])) {
                    $imageQuery = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
                    $imageStmt = $this->db->prepare($imageQuery);
                    
                    foreach ($data['images'] as $index => $imageUrl) {
                        $imageStmt->execute([$id, $imageUrl, $index === 0 ? 1 : 0]);
                    }
                }
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Producto actualizado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al actualizar producto: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function deleteProduct() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(false, 'ID de producto requerido', null, 400);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Eliminar imágenes
            $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = ?";
            $deleteImagesStmt = $this->db->prepare($deleteImagesQuery);
            $deleteImagesStmt->execute([$id]);
            
            // Eliminar producto
            $deleteProductQuery = "DELETE FROM products WHERE id = ?";
            $deleteProductStmt = $this->db->prepare($deleteProductQuery);
            $deleteProductStmt->execute([$id]);
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Producto eliminado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendResponse(false, 'Error al eliminar producto: ' . $e->getMessage(), null, 500);
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

$api = new ProductAPI();
$api->handleRequest();
?>