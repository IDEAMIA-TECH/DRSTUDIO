<?php
/**
 * API Endpoint para Portal Público - DT Studio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/PublicController.php';

try {
    $controller = new PublicController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'home';
    $id = $pathParts[1] ?? null;
    
    switch ($action) {
        case 'home':
            $controller->getHomePage();
            break;
            
        case 'products':
            $controller->getProducts();
            break;
            
        case 'product':
            if (!$id) {
                throw new Exception('Slug del producto requerido');
            }
            $controller->getProduct($id);
            break;
            
        case 'categories':
            $controller->getCategories();
            break;
            
        case 'category':
            if (!$id) {
                throw new Exception('Slug de la categoría requerido');
            }
            $controller->getCategory($id);
            break;
            
        case 'featured':
            $controller->getFeaturedProducts();
            break;
            
        case 'related':
            if (!$id) {
                throw new Exception('ID del producto requerido');
            }
            $controller->getRelatedProducts($id);
            break;
            
        case 'best-selling':
            $controller->getBestSellingProducts();
            break;
            
        case 'recent':
            $controller->getRecentProducts();
            break;
            
        case 'search':
            $controller->searchProducts();
            break;
            
        case 'filters':
            $controller->getFilters();
            break;
            
        case 'stats':
            $controller->getCatalogStats();
            break;
            
        case 'quotation':
            if ($method === 'POST') {
                $controller->createQuotation();
            } else {
                if (!$id) {
                    throw new Exception('Número de cotización requerido');
                }
                $controller->getQuotation($id);
            }
            break;
            
        case 'calculate-quotation':
            $controller->calculateQuotationPrice();
            break;
            
        case 'suggested-products':
            $controller->getSuggestedProducts();
            break;
            
        case 'recent-quotations':
            $controller->getRecentQuotations();
            break;
            
        case 'quoter-stats':
            $controller->getQuoterStats();
            break;
            
        case 'sitemap-products':
            $controller->getProductsSitemap();
            break;
            
        case 'sitemap-categories':
            $controller->getCategoriesSitemap();
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
