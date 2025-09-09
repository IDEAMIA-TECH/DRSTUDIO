<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class DashboardAPI {
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
                    if ($action === 'get_stats') {
                        $this->getStats();
                    } elseif ($action === 'get_recent_activity') {
                        $this->getRecentActivity();
                    } elseif ($action === 'get_sales_chart') {
                        $this->getSalesChart();
                    } else {
                        $this->getStats();
                    }
                    break;
                    
                default:
                    $this->sendResponse(false, 'Método no permitido', null, 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error interno: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getStats() {
        try {
            // Estadísticas generales
            $stats = [];
            
            // Total de productos
            $productQuery = "SELECT COUNT(*) as total FROM products WHERE active = 1";
            $productStmt = $this->db->prepare($productQuery);
            $productStmt->execute();
            $stats['total_products'] = (int)$productStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de clientes
            $customerQuery = "SELECT COUNT(*) as total FROM customers WHERE active = 1";
            $customerStmt = $this->db->prepare($customerQuery);
            $customerStmt->execute();
            $stats['total_customers'] = (int)$customerStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de cotizaciones
            $quotationQuery = "SELECT COUNT(*) as total FROM quotations";
            $quotationStmt = $this->db->prepare($quotationQuery);
            $quotationStmt->execute();
            $stats['total_quotations'] = (int)$quotationStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de pedidos
            $orderQuery = "SELECT COUNT(*) as total FROM orders";
            $orderStmt = $this->db->prepare($orderQuery);
            $orderStmt->execute();
            $stats['total_orders'] = (int)$orderStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ventas del mes actual
            $currentMonth = date('Y-m');
            $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                          WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status IN ('completed', 'shipped')";
            $salesStmt = $this->db->prepare($salesQuery);
            $salesStmt->execute([$currentMonth]);
            $stats['monthly_sales'] = (float)$salesStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ventas del mes anterior
            $lastMonth = date('Y-m', strtotime('-1 month'));
            $lastSalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                              WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status IN ('completed', 'shipped')";
            $lastSalesStmt = $this->db->prepare($lastSalesQuery);
            $lastSalesStmt->execute([$lastMonth]);
            $stats['last_month_sales'] = (float)$lastSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular crecimiento de ventas
            if ($stats['last_month_sales'] > 0) {
                $stats['sales_growth'] = (($stats['monthly_sales'] - $stats['last_month_sales']) / $stats['last_month_sales']) * 100;
            } else {
                $stats['sales_growth'] = $stats['monthly_sales'] > 0 ? 100 : 0;
            }
            
            // Cotizaciones pendientes
            $pendingQuotationsQuery = "SELECT COUNT(*) as total FROM quotations WHERE status = 'pending'";
            $pendingQuotationsStmt = $this->db->prepare($pendingQuotationsQuery);
            $pendingQuotationsStmt->execute();
            $stats['pending_quotations'] = (int)$pendingQuotationsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pedidos pendientes
            $pendingOrdersQuery = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
            $pendingOrdersStmt = $this->db->prepare($pendingOrdersQuery);
            $pendingOrdersStmt->execute();
            $stats['pending_orders'] = (int)$pendingOrdersStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Productos más vendidos
            $topProductsQuery = "SELECT p.name, SUM(op.quantity) as total_sold
                                FROM order_products op
                                JOIN products p ON op.product_id = p.id
                                JOIN orders o ON op.order_id = o.id
                                WHERE o.status IN ('completed', 'shipped')
                                GROUP BY p.id, p.name
                                ORDER BY total_sold DESC
                                LIMIT 5";
            $topProductsStmt = $this->db->prepare($topProductsQuery);
            $topProductsStmt->execute();
            $stats['top_products'] = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Clientes más activos
            $topCustomersQuery = "SELECT c.name, c.company, COUNT(o.id) as total_orders, 
                                 COALESCE(SUM(o.total_amount), 0) as total_spent
                                 FROM customers c
                                 LEFT JOIN orders o ON c.id = o.customer_id
                                 WHERE o.status IN ('completed', 'shipped') OR o.id IS NULL
                                 GROUP BY c.id, c.name, c.company
                                 ORDER BY total_spent DESC
                                 LIMIT 5";
            $topCustomersStmt = $this->db->prepare($topCustomersQuery);
            $topCustomersStmt->execute();
            $stats['top_customers'] = $topCustomersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(true, 'Estadísticas obtenidas exitosamente', $stats);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al obtener estadísticas: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getRecentActivity() {
        try {
            $activities = [];
            
            // Cotizaciones recientes
            $recentQuotationsQuery = "SELECT q.*, c.name as customer_name
                                     FROM quotations q
                                     LEFT JOIN customers c ON q.customer_id = c.id
                                     ORDER BY q.created_at DESC
                                     LIMIT 5";
            $recentQuotationsStmt = $this->db->prepare($recentQuotationsQuery);
            $recentQuotationsStmt->execute();
            $quotations = $recentQuotationsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quotations as $quotation) {
                $activities[] = [
                    'type' => 'quotation',
                    'id' => $quotation['id'],
                    'title' => 'Nueva cotización #' . $quotation['quotation_number'],
                    'description' => 'Cliente: ' . $quotation['customer_name'],
                    'amount' => (float)$quotation['total_amount'],
                    'status' => $quotation['status'],
                    'date' => $quotation['created_at']
                ];
            }
            
            // Pedidos recientes
            $recentOrdersQuery = "SELECT o.*, c.name as customer_name
                                 FROM orders o
                                 LEFT JOIN customers c ON o.customer_id = c.id
                                 ORDER BY o.created_at DESC
                                 LIMIT 5";
            $recentOrdersStmt = $this->db->prepare($recentOrdersQuery);
            $recentOrdersStmt->execute();
            $orders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orders as $order) {
                $activities[] = [
                    'type' => 'order',
                    'id' => $order['id'],
                    'title' => 'Nuevo pedido #' . $order['order_number'],
                    'description' => 'Cliente: ' . $order['customer_name'],
                    'amount' => (float)$order['total_amount'],
                    'status' => $order['status'],
                    'date' => $order['created_at']
                ];
            }
            
            // Ordenar por fecha
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Tomar solo los 10 más recientes
            $activities = array_slice($activities, 0, 10);
            
            $this->sendResponse(true, 'Actividad reciente obtenida exitosamente', $activities);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al obtener actividad reciente: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getSalesChart() {
        try {
            $period = $_GET['period'] ?? 'month'; // month, week, year
            $data = [];
            
            switch ($period) {
                case 'week':
                    // Ventas de los últimos 7 días
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $dayName = date('D', strtotime($date));
                        
                        $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                                      WHERE DATE(created_at) = ? AND status IN ('completed', 'shipped')";
                        $salesStmt = $this->db->prepare($salesQuery);
                        $salesStmt->execute([$date]);
                        $total = (float)$salesStmt->fetch(PDO::FETCH_ASSOC)['total'];
                        
                        $data[] = [
                            'date' => $date,
                            'label' => $dayName,
                            'sales' => $total
                        ];
                    }
                    break;
                    
                case 'year':
                    // Ventas de los últimos 12 meses
                    for ($i = 11; $i >= 0; $i--) {
                        $date = date('Y-m', strtotime("-$i months"));
                        $monthName = date('M', strtotime($date . '-01'));
                        
                        $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                                      WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status IN ('completed', 'shipped')";
                        $salesStmt = $this->db->prepare($salesQuery);
                        $salesStmt->execute([$date]);
                        $total = (float)$salesStmt->fetch(PDO::FETCH_ASSOC)['total'];
                        
                        $data[] = [
                            'date' => $date,
                            'label' => $monthName,
                            'sales' => $total
                        ];
                    }
                    break;
                    
                default: // month
                    // Ventas de los últimos 30 días
                    for ($i = 29; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $dayNumber = date('j', strtotime($date));
                        
                        $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                                      WHERE DATE(created_at) = ? AND status IN ('completed', 'shipped')";
                        $salesStmt = $this->db->prepare($salesQuery);
                        $salesStmt->execute([$date]);
                        $total = (float)$salesStmt->fetch(PDO::FETCH_ASSOC)['total'];
                        
                        $data[] = [
                            'date' => $date,
                            'label' => $dayNumber,
                            'sales' => $total
                        ];
                    }
                    break;
            }
            
            $this->sendResponse(true, 'Datos del gráfico obtenidos exitosamente', [
                'period' => $period,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error al obtener datos del gráfico: ' . $e->getMessage(), null, 500);
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

$api = new DashboardAPI();
$api->handleRequest();
?>
