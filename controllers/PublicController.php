<?php
/**
 * Controlador PublicController - DT Studio
 * Manejo de peticiones para el portal público
 */

require_once __DIR__ . '/../models/Catalog.php';
require_once __DIR__ . '/../models/Quoter.php';

class PublicController {
    private $catalogModel;
    private $quoterModel;

    public function __construct() {
        $this->catalogModel = new Catalog();
        $this->quoterModel = new Quoter();
    }

    /**
     * Obtener productos del catálogo
     */
    public function getProducts() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 12;
            
            $filters = [
                'category_id' => $_GET['category_id'] ?? null,
                'search' => $_GET['search'] ?? '',
                'min_price' => $_GET['min_price'] ?? null,
                'max_price' => $_GET['max_price'] ?? null,
                'material' => $_GET['material'] ?? null,
                'color' => $_GET['color'] ?? null
            ];
            
            $result = $this->catalogModel->getProducts($page, $limit, $filters);
            
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
     * Obtener producto por slug
     */
    public function getProduct($slug) {
        try {
            $product = $this->catalogModel->getProductBySlug($slug);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $product
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
     * Obtener categorías
     */
    public function getCategories() {
        try {
            $parentId = $_GET['parent_id'] ?? null;
            $categories = $this->catalogModel->getCategories($parentId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $categories
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
     * Obtener categoría por slug
     */
    public function getCategory($slug) {
        try {
            $category = $this->catalogModel->getCategoryBySlug($slug);
            
            if (!$category) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $category
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
     * Obtener productos destacados
     */
    public function getFeaturedProducts() {
        try {
            $limit = $_GET['limit'] ?? 8;
            $products = $this->catalogModel->getFeaturedProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener productos relacionados
     */
    public function getRelatedProducts($productId) {
        try {
            $limit = $_GET['limit'] ?? 4;
            $categoryId = $_GET['category_id'] ?? null;
            
            if (!$categoryId) {
                // Obtener categoría del producto
                $product = $this->catalogModel->getProductBySlug($productId);
                if (!$product) {
                    throw new Exception('Producto no encontrado');
                }
                $categoryId = $product['category_id'];
            }
            
            $products = $this->catalogModel->getRelatedProducts($productId, $categoryId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener productos más vendidos
     */
    public function getBestSellingProducts() {
        try {
            $limit = $_GET['limit'] ?? 8;
            $products = $this->catalogModel->getBestSellingProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener productos recientes
     */
    public function getRecentProducts() {
        try {
            $limit = $_GET['limit'] ?? 8;
            $products = $this->catalogModel->getRecentProducts($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Buscar productos
     */
    public function searchProducts() {
        try {
            $query = $_GET['q'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 12;
            
            if (empty($query)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El término de búsqueda es requerido'
                ]);
                return;
            }
            
            $result = $this->catalogModel->searchProducts($query, $page, $limit);
            
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
     * Obtener filtros disponibles
     */
    public function getFilters() {
        try {
            $categoryId = $_GET['category_id'] ?? null;
            $filters = $this->catalogModel->getAvailableFilters($categoryId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $filters
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
     * Obtener estadísticas del catálogo
     */
    public function getCatalogStats() {
        try {
            $stats = $this->catalogModel->getCatalogStats();
            
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
     * Crear cotización pública
     */
    public function createQuotation() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->quoterModel->validateQuotationData($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $result = $this->quoterModel->createPublicQuotation($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
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
     * Obtener cotización pública
     */
    public function getQuotation($quotationNumber) {
        try {
            $quotation = $this->quoterModel->getPublicQuotation($quotationNumber);
            
            if (!$quotation) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotation
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
     * Calcular precio de cotización
     */
    public function calculateQuotationPrice() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['items'])) {
                throw new Exception('Los items son requeridos');
            }
            
            $taxRate = $input['tax_rate'] ?? 16.0;
            $result = $this->quoterModel->calculateQuotationPrice($input['items'], $taxRate);
            
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
     * Obtener productos sugeridos
     */
    public function getSuggestedProducts() {
        try {
            $categoryId = $_GET['category_id'] ?? null;
            $limit = $_GET['limit'] ?? 6;
            
            $products = $this->quoterModel->getSuggestedProducts($categoryId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener cotizaciones recientes
     */
    public function getRecentQuotations() {
        try {
            $limit = $_GET['limit'] ?? 10;
            $quotations = $this->quoterModel->getRecentPublicQuotations($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $quotations
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
     * Obtener estadísticas del cotizador
     */
    public function getQuoterStats() {
        try {
            $stats = $this->quoterModel->getQuoterStats();
            
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
     * Obtener sitemap de productos
     */
    public function getProductsSitemap() {
        try {
            $products = $this->catalogModel->getProductsForSitemap();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $products
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
     * Obtener sitemap de categorías
     */
    public function getCategoriesSitemap() {
        try {
            $categories = $this->catalogModel->getCategoriesForSitemap();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $categories
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
     * Obtener página de inicio
     */
    public function getHomePage() {
        try {
            $data = [];
            
            // Productos destacados
            $data['featured_products'] = $this->catalogModel->getFeaturedProducts(8);
            
            // Productos más vendidos
            $data['best_selling'] = $this->catalogModel->getBestSellingProducts(6);
            
            // Productos recientes
            $data['recent_products'] = $this->catalogModel->getRecentProducts(6);
            
            // Categorías principales
            $data['categories'] = $this->catalogModel->getCategories();
            
            // Estadísticas
            $data['stats'] = $this->catalogModel->getCatalogStats();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
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
