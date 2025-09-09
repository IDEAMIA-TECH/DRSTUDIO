<?php
/**
 * Modelo Analytics - DT Studio
 * Gestión de métricas y analytics del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener métricas generales del dashboard
     */
    public function getDashboardMetrics($period = 'month') {
        $metrics = [];

        // Configurar fechas según el período
        $dateCondition = $this->getDateCondition($period);

        // Total de ventas
        $metrics['total_sales'] = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total 
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$dateCondition}"
        )['total'];

        // Total de cotizaciones
        $metrics['total_quotations'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE created_at >= {$dateCondition}"
        )['total'];

        // Total de clientes
        $metrics['total_customers'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM customers 
             WHERE is_active = 1"
        )['total'];

        // Total de productos
        $metrics['total_products'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM products 
             WHERE status = 'active'"
        )['total'];

        // Pedidos pendientes
        $metrics['pending_orders'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM orders 
             WHERE status IN ('pending', 'confirmed', 'processing')"
        )['total'];

        // Cotizaciones pendientes
        $metrics['pending_quotations'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE status IN ('sent', 'reviewed')"
        )['total'];

        // Tasa de conversión
        $totalQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE created_at >= {$dateCondition}"
        )['total'];

        $convertedQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE status = 'converted' AND created_at >= {$dateCondition}"
        )['total'];

        $metrics['conversion_rate'] = $totalQuotations > 0 ? round(($convertedQuotations / $totalQuotations) * 100, 2) : 0;

        // Promedio de valor de pedido
        $avgOrderValue = $this->db->fetch(
            "SELECT COALESCE(AVG(total), 0) as avg_value 
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$dateCondition}"
        )['avg_value'];

        $metrics['avg_order_value'] = round($avgOrderValue, 2);

        return $metrics;
    }

    /**
     * Obtener métricas de ventas
     */
    public function getSalesMetrics($period = 'month', $groupBy = 'day') {
        $dateCondition = $this->getDateCondition($period);
        $groupFormat = $this->getGroupFormat($groupBy);

        $sales = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, 
                    COUNT(*) as order_count,
                    SUM(total) as total_sales,
                    AVG(total) as avg_order_value
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$dateCondition}
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        return $sales;
    }

    /**
     * Obtener métricas de productos
     */
    public function getProductMetrics($period = 'month', $limit = 10) {
        $dateCondition = $this->getDateCondition($period);

        $products = $this->db->fetchAll(
            "SELECT p.name, p.sku, 
                    COUNT(oi.id) as times_ordered,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE o.status != 'cancelled' AND o.created_at >= {$dateCondition}
             GROUP BY p.id, p.name, p.sku
             ORDER BY total_revenue DESC
             LIMIT ?",
            [$limit]
        );

        return $products;
    }

    /**
     * Obtener métricas de clientes
     */
    public function getCustomerMetrics($period = 'month', $limit = 10) {
        $dateCondition = $this->getDateCondition($period);

        $customers = $this->db->fetchAll(
            "SELECT c.name, c.email, c.company,
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(o.total) as total_spent,
                    MAX(o.created_at) as last_order_date
             FROM customers c
             LEFT JOIN orders o ON c.id = o.customer_id
             WHERE o.status != 'cancelled' AND o.created_at >= {$dateCondition}
             GROUP BY c.id, c.name, c.email, c.company
             ORDER BY total_spent DESC
             LIMIT ?",
            [$limit]
        );

        return $customers;
    }

    /**
     * Obtener métricas de cotizaciones
     */
    public function getQuotationMetrics($period = 'month') {
        $dateCondition = $this->getDateCondition($period);

        $quotations = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(total) as total_value
             FROM quotations 
             WHERE created_at >= {$dateCondition}
             GROUP BY status
             ORDER BY count DESC"
        );

        return $quotations;
    }

    /**
     * Obtener métricas de pedidos
     */
    public function getOrderMetrics($period = 'month') {
        $dateCondition = $this->getDateCondition($period);

        $orders = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(total) as total_value
             FROM orders 
             WHERE created_at >= {$dateCondition}
             GROUP BY status
             ORDER BY count DESC"
        );

        return $orders;
    }

    /**
     * Obtener métricas financieras
     */
    public function getFinancialMetrics($period = 'month') {
        $dateCondition = $this->getDateCondition($period);

        $metrics = [];

        // Ingresos totales
        $metrics['total_revenue'] = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total 
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$dateCondition}"
        )['total'];

        // Ingresos por mes (últimos 12 meses)
        $metrics['monthly_revenue'] = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(total) as revenue
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC"
        );

        // Distribución por estado de pago
        $metrics['payment_distribution'] = $this->db->fetchAll(
            "SELECT payment_status, COUNT(*) as count, SUM(total) as total_value
             FROM orders 
             WHERE created_at >= {$dateCondition}
             GROUP BY payment_status
             ORDER BY total_value DESC"
        );

        return $metrics;
    }

    /**
     * Obtener tendencias de crecimiento
     */
    public function getGrowthTrends($period = 'year') {
        $dateCondition = $this->getDateCondition($period);

        $trends = [];

        // Crecimiento de ventas
        $currentPeriod = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total 
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$dateCondition}"
        )['total'];

        $previousPeriod = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as total 
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= {$this->getPreviousPeriodCondition($period)}"
        )['total'];

        $trends['sales_growth'] = $previousPeriod > 0 ? 
            round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2) : 0;

        // Crecimiento de clientes
        $currentCustomers = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM customers 
             WHERE created_at >= {$dateCondition}"
        )['total'];

        $previousCustomers = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM customers 
             WHERE created_at >= {$this->getPreviousPeriodCondition($period)}"
        )['total'];

        $trends['customer_growth'] = $previousCustomers > 0 ? 
            round((($currentCustomers - $previousCustomers) / $previousCustomers) * 100, 2) : 0;

        return $trends;
    }

    /**
     * Obtener métricas geográficas
     */
    public function getGeographicMetrics($period = 'month') {
        $dateCondition = $this->getDateCondition($period);

        $geographic = $this->db->fetchAll(
            "SELECT c.city, c.state, c.country,
                    COUNT(DISTINCT c.id) as customer_count,
                    COUNT(o.id) as order_count,
                    SUM(o.total) as total_sales
             FROM customers c
             LEFT JOIN orders o ON c.id = o.customer_id
             WHERE o.status != 'cancelled' AND o.created_at >= {$dateCondition}
             AND c.city IS NOT NULL AND c.city != ''
             GROUP BY c.city, c.state, c.country
             ORDER BY total_sales DESC
             LIMIT 20"
        );

        return $geographic;
    }

    /**
     * Obtener métricas de rendimiento
     */
    public function getPerformanceMetrics($period = 'month') {
        $dateCondition = $this->getDateCondition($period);

        $metrics = [];

        // Tiempo promedio de entrega
        $avgDeliveryTime = $this->db->fetch(
            "SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
             FROM orders 
             WHERE status = 'delivered' AND created_at >= {$dateCondition}"
        )['avg_days'];

        $metrics['avg_delivery_time'] = round($avgDeliveryTime, 1);

        // Tasa de cancelación
        $totalOrders = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM orders 
             WHERE created_at >= {$dateCondition}"
        )['total'];

        $cancelledOrders = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM orders 
             WHERE status = 'cancelled' AND created_at >= {$dateCondition}"
        )['total'];

        $metrics['cancellation_rate'] = $totalOrders > 0 ? 
            round(($cancelledOrders / $totalOrders) * 100, 2) : 0;

        // Eficiencia de cotizaciones
        $totalQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE created_at >= {$dateCondition}"
        )['total'];

        $convertedQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE status = 'converted' AND created_at >= {$dateCondition}"
        )['total'];

        $metrics['quotation_efficiency'] = $totalQuotations > 0 ? 
            round(($convertedQuotations / $totalQuotations) * 100, 2) : 0;

        return $metrics;
    }

    /**
     * Obtener métricas personalizadas
     */
    public function getCustomMetrics($config) {
        $metrics = [];

        if (isset($config['type'])) {
            switch ($config['type']) {
                case 'sales_by_category':
                    $metrics = $this->getSalesByCategory($config);
                    break;
                case 'customer_lifetime_value':
                    $metrics = $this->getCustomerLifetimeValue($config);
                    break;
                case 'seasonal_analysis':
                    $metrics = $this->getSeasonalAnalysis($config);
                    break;
                case 'product_performance':
                    $metrics = $this->getProductPerformance($config);
                    break;
                default:
                    $metrics = ['error' => 'Tipo de métrica no válido'];
            }
        }

        return $metrics;
    }

    /**
     * Obtener ventas por categoría
     */
    private function getSalesByCategory($config) {
        $period = $config['period'] ?? 'month';
        $dateCondition = $this->getDateCondition($period);

        return $this->db->fetchAll(
            "SELECT cat.name as category_name,
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(oi.total) as total_sales
             FROM categories cat
             LEFT JOIN products p ON cat.id = p.category_id
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE o.status != 'cancelled' AND o.created_at >= {$dateCondition}
             GROUP BY cat.id, cat.name
             ORDER BY total_sales DESC"
        );
    }

    /**
     * Obtener valor de vida del cliente
     */
    private function getCustomerLifetimeValue($config) {
        $limit = $config['limit'] ?? 10;

        return $this->db->fetchAll(
            "SELECT c.name, c.email, c.company,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(o.total) as lifetime_value,
                    AVG(o.total) as avg_order_value,
                    MAX(o.created_at) as last_order_date
             FROM customers c
             LEFT JOIN orders o ON c.id = o.customer_id
             WHERE o.status != 'cancelled'
             GROUP BY c.id, c.name, c.email, c.company
             ORDER BY lifetime_value DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtener análisis estacional
     */
    private function getSeasonalAnalysis($config) {
        return $this->db->fetchAll(
            "SELECT MONTH(created_at) as month,
                    MONTHNAME(created_at) as month_name,
                    COUNT(*) as order_count,
                    SUM(total) as total_sales,
                    AVG(total) as avg_order_value
             FROM orders 
             WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY MONTH(created_at), MONTHNAME(created_at)
             ORDER BY month ASC"
        );
    }

    /**
     * Obtener rendimiento de productos
     */
    private function getProductPerformance($config) {
        $period = $config['period'] ?? 'month';
        $dateCondition = $this->getDateCondition($period);

        return $this->db->fetchAll(
            "SELECT p.name, p.sku, cat.name as category,
                    COUNT(oi.id) as times_ordered,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue,
                    AVG(oi.unit_price) as avg_price
             FROM products p
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE o.status != 'cancelled' AND o.created_at >= {$dateCondition}
             GROUP BY p.id, p.name, p.sku, cat.name
             ORDER BY total_revenue DESC"
        );
    }

    /**
     * Obtener condición de fecha según el período
     */
    private function getDateCondition($period) {
        switch ($period) {
            case 'day':
                return "DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case 'week':
                return "DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return "DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'quarter':
                return "DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            case 'year':
                return "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
    }

    /**
     * Obtener condición del período anterior
     */
    private function getPreviousPeriodCondition($period) {
        switch ($period) {
            case 'day':
                return "DATE_SUB(NOW(), INTERVAL 2 DAY)";
            case 'week':
                return "DATE_SUB(NOW(), INTERVAL 2 WEEK)";
            case 'month':
                return "DATE_SUB(NOW(), INTERVAL 2 MONTH)";
            case 'quarter':
                return "DATE_SUB(NOW(), INTERVAL 6 MONTH)";
            case 'year':
                return "DATE_SUB(NOW(), INTERVAL 2 YEAR)";
            default:
                return "DATE_SUB(NOW(), INTERVAL 2 MONTH)";
        }
    }

    /**
     * Obtener formato de agrupación
     */
    private function getGroupFormat($groupBy) {
        switch ($groupBy) {
            case 'hour':
                return "%Y-%m-%d %H:00:00";
            case 'day':
                return "%Y-%m-%d";
            case 'week':
                return "%Y-%u";
            case 'month':
                return "%Y-%m";
            case 'year':
                return "%Y";
            default:
                return "%Y-%m-%d";
        }
    }
}
