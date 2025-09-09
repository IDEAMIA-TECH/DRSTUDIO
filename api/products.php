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
            $whereConditions[] = "c.name = ?";
            $params[] = $category;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Contar total
        $countQuery = "SELECT COUNT(DISTINCT p.id) as total 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener productos
        $query = "SELECT p.*, 
                         c.name as category_name,
                         GROUP_CONCAT(DISTINCT pi.url) as images,
                         MIN(pv.price) as min_price,
                         MAX(pv.price) as max_price
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN product_images pi ON p.id = pi.product_id
                  LEFT JOIN product_variants pv ON p.id = pv.product_id
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
            $product['min_price'] = (float)$product['min_price'];
            $product['max_price'] = (float)$product['max_price'];
            $product['price'] = $product['min_price']; // Precio principal
            $product['featured'] = false; // No existe en el esquema actual
            $product['active'] = $product['status'] === 'active';
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
                         c.name as category_name,
                         GROUP_CONCAT(DISTINCT pi.url) as images
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
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
        
        // Obtener variantes del producto
        $variantsQuery = "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1";
        $variantsStmt = $this->db->prepare($variantsQuery);
        $variantsStmt->execute([$id]);
        $variants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
        $product['variants'] = $variants;
        $product['active'] = $product['status'] === 'active';
        
        $this->sendResponse(true, 'Producto obtenido exitosamente', $product);
    }
    
    private function createProduct() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar campos requeridos del producto
        $required = ['name', 'description', 'category_id', 'sku', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->sendResponse(false, "Campo requerido del producto: $field", null, 400);
                return;
            }
        }
        
        // Validar campos requeridos de la variante
        $variantRequired = ['variant_name', 'variant_sku', 'variant_price', 'variant_cost', 'variant_stock'];
        foreach ($variantRequired as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->sendResponse(false, "Campo requerido de la variante: $field", null, 400);
                return;
            }
        }
        
        try {
            $this->db->beginTransaction();
            
            // Insertar producto
            $query = "INSERT INTO products (name, description, category_id, sku, status, 
                                          meta_title, meta_description, created_by, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['sku'],
                $data['status'],
                $data['meta_title'] ?? null,
                $data['meta_description'] ?? null
            ]);
            
            $productId = $this->db->lastInsertId();
            
            // Insertar variante principal
            $variantQuery = "INSERT INTO product_variants (product_id, name, sku, price, cost, stock, attributes, is_active, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            // Procesar atributos JSON
            $attributes = null;
            if (!empty($data['variant_attributes'])) {
                $attributes = json_encode(json_decode($data['variant_attributes'], true));
            }
            
            $variantStmt = $this->db->prepare($variantQuery);
            $variantStmt->execute([
                $productId,
                $data['variant_name'],
                $data['variant_sku'],
                $data['variant_price'],
                $data['variant_cost'],
                $data['variant_stock'],
                $attributes
            ]);
            
            $variantId = $this->db->lastInsertId();
            
            // Insertar imágenes del producto
            if (!empty($data['product_images'])) {
                $imageUrls = array_filter(array_map('trim', explode("\n", $data['product_images'])));
                
                if (!empty($imageUrls)) {
                    $imageQuery = "INSERT INTO product_images (product_id, variant_id, url, alt_text, sort_order, is_primary, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $imageStmt = $this->db->prepare($imageQuery);
                    
                    foreach ($imageUrls as $index => $imageUrl) {
                        if (!empty($imageUrl)) {
                            $isPrimary = ($index === 0) ? 1 : 0;
                            $altText = $data['name'] . ' - Imagen ' . ($index + 1);
                            
                            $imageStmt->execute([
                                $productId,
                                $variantId,
                                $imageUrl,
                                $altText,
                                $index,
                                $isPrimary
                            ]);
                        }
                    }
                }
            }
            
            // Insertar imagen principal si se especifica
            if (!empty($data['primary_image'])) {
                $primaryImageQuery = "INSERT INTO product_images (product_id, variant_id, url, alt_text, sort_order, is_primary, created_at) 
                                     VALUES (?, ?, ?, ?, 0, 1, NOW())";
                $primaryImageStmt = $this->db->prepare($primaryImageQuery);
                $primaryImageStmt->execute([
                    $productId,
                    $variantId,
                    $data['primary_image'],
                    $data['name'] . ' - Imagen Principal'
                ]);
            }
            
            $this->db->commit();
            $this->sendResponse(true, 'Producto creado exitosamente', [
                'product_id' => $productId,
                'variant_id' => $variantId
            ]);
            
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
            
            // Actualizar producto
            $query = "UPDATE products SET 
                         name = ?, description = ?, category_id = ?, sku = ?, 
                         status = ?, meta_title = ?, meta_description = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['sku'],
                $data['status'] ?? 'active',
                $data['meta_title'] ?? '',
                $data['meta_description'] ?? '',
                $id
            ]);
            
            // Actualizar imágenes si se proporcionan
            if (isset($data['images'])) {
                // Eliminar imágenes existentes
                $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = ?";
                $deleteImagesStmt = $this->db->prepare($deleteImagesQuery);
                $deleteImagesStmt->execute([$id]);
                
                if (!empty($data['images'])) {
                    $imageQuery = "INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)";
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
            
            // Eliminar variantes
            $deleteVariantsQuery = "DELETE FROM product_variants WHERE product_id = ?";
            $deleteVariantsStmt = $this->db->prepare($deleteVariantsQuery);
            $deleteVariantsStmt->execute([$id]);
            
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