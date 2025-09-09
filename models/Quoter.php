<?php
/**
 * Modelo Quoter - DT Studio
 * Gestión del cotizador público
 */

require_once __DIR__ . '/../includes/Database.php';

class Quoter {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear cotización pública
     */
    public function createPublicQuotation($data) {
        // Validar datos requeridos
        $required = ['customer_name', 'customer_email', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar items
        if (!is_array($data['items']) || empty($data['items'])) {
            throw new Exception("Debe incluir al menos un producto");
        }

        $this->db->beginTransaction();
        
        try {
            // Crear o obtener cliente
            $customerId = $this->getOrCreateCustomer([
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'] ?? '',
                'company' => $data['customer_company'] ?? '',
                'address' => $data['customer_address'] ?? ''
            ]);

            // Generar número de cotización
            $quotationNumber = $this->generateQuotationNumber();

            // Calcular totales
            $subtotal = 0;
            $items = [];

            foreach ($data['items'] as $item) {
                $product = $this->getProductForQuotation($item['product_id'], $item['variant_id'] ?? null);
                if (!$product) {
                    throw new Exception("Producto no encontrado: {$item['product_id']}");
                }

                $quantity = (int)$item['quantity'];
                $unitPrice = $product['price'];
                $itemTotal = $quantity * $unitPrice;

                $items[] = [
                    'product_id' => $product['product_id'],
                    'variant_id' => $product['variant_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal
                ];

                $subtotal += $itemTotal;
            }

            // Calcular impuestos (16% por defecto)
            $taxRate = $data['tax_rate'] ?? 16.0;
            $taxAmount = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $taxAmount;

            // Crear cotización
            $quotationData = [
                'customer_id' => $customerId,
                'user_id' => 1, // Usuario del sistema para cotizaciones públicas
                'quotation_number' => $quotationNumber,
                'status' => 'sent',
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'valid_until' => date('Y-m-d', strtotime('+30 days')),
                'notes' => $data['notes'] ?? '',
                'is_public' => 1
            ];

            $quotationId = $this->createQuotation($quotationData);

            // Crear items de la cotización
            foreach ($items as $item) {
                $this->createQuotationItem($quotationId, $item);
            }

            $this->db->commit();

            return [
                'quotation_id' => $quotationId,
                'quotation_number' => $quotationNumber,
                'total' => $total,
                'valid_until' => $quotationData['valid_until']
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtener cotización pública por número
     */
    public function getPublicQuotation($quotationNumber) {
        $sql = "SELECT q.*, 
                       c.name as customer_name, c.email as customer_email, 
                       c.phone as customer_phone, c.company as customer_company,
                       c.address as customer_address
                FROM quotations q
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE q.quotation_number = ? AND q.is_public = 1";
        
        $quotation = $this->db->fetch($sql, [$quotationNumber]);
        
        if ($quotation) {
            // Obtener items de la cotización
            $items = $this->db->fetchAll(
                "SELECT qi.*, 
                        p.name as product_name, p.sku as product_sku,
                        pv.name as variant_name, pv.sku as variant_sku,
                        pv.attributes as variant_attributes,
                        pi.url as product_image
                 FROM quotation_items qi
                 LEFT JOIN products p ON qi.product_id = p.id
                 LEFT JOIN product_variants pv ON qi.variant_id = pv.id
                 LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                 WHERE qi.quotation_id = ?
                 ORDER BY qi.created_at ASC",
                [$quotation['id']]
            );
            
            $quotation['items'] = $items;
        }
        
        return $quotation;
    }

    /**
     * Obtener o crear cliente
     */
    private function getOrCreateCustomer($data) {
        // Buscar cliente existente por email
        $customer = $this->db->fetch(
            "SELECT id FROM customers WHERE email = ?",
            [$data['email']]
        );

        if ($customer) {
            // Actualizar datos del cliente si es necesario
            $this->db->query(
                "UPDATE customers SET 
                 name = ?, phone = ?, company = ?, address = ?, updated_at = NOW()
                 WHERE id = ?",
                [$data['name'], $data['phone'], $data['company'], $data['address'], $customer['id']]
            );
            return $customer['id'];
        }

        // Crear nuevo cliente
        $sql = "INSERT INTO customers (name, email, phone, company, address, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['company'],
            $data['address'],
            1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener producto para cotización
     */
    private function getProductForQuotation($productId, $variantId = null) {
        if ($variantId) {
            // Obtener variante específica
            $sql = "SELECT p.id as product_id, pv.id as variant_id, pv.name, pv.price, pv.sku
                    FROM products p
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE p.id = ? AND pv.id = ? AND p.status = 'active'";
            
            return $this->db->fetch($sql, [$productId, $variantId]);
        } else {
            // Obtener la variante más barata
            $sql = "SELECT p.id as product_id, pv.id as variant_id, pv.name, pv.price, pv.sku
                    FROM products p
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE p.id = ? AND p.status = 'active'
                    ORDER BY pv.price ASC
                    LIMIT 1";
            
            return $this->db->fetch($sql, [$productId]);
        }
    }

    /**
     * Crear cotización
     */
    private function createQuotation($data) {
        $sql = "INSERT INTO quotations 
                (customer_id, user_id, quotation_number, status, subtotal, tax_rate, tax_amount, total, valid_until, notes, is_public) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['customer_id'],
            $data['user_id'],
            $data['quotation_number'],
            $data['status'],
            $data['subtotal'],
            $data['tax_rate'],
            $data['tax_amount'],
            $data['total'],
            $data['valid_until'],
            $data['notes'],
            $data['is_public']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Crear item de cotización
     */
    private function createQuotationItem($quotationId, $item) {
        $sql = "INSERT INTO quotation_items 
                (quotation_id, product_id, variant_id, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $quotationId,
            $item['product_id'],
            $item['variant_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total']
        ]);
    }

    /**
     * Generar número de cotización
     */
    private function generateQuotationNumber() {
        $prefix = 'COT';
        $year = date('Y');
        $month = date('m');
        
        // Obtener el último número del mes
        $lastNumber = $this->db->fetch(
            "SELECT quotation_number FROM quotations 
             WHERE quotation_number LIKE ? 
             ORDER BY quotation_number DESC 
             LIMIT 1",
            ["{$prefix}-{$year}{$month}%"]
        );
        
        if ($lastNumber) {
            $lastNum = (int)substr($lastNumber['quotation_number'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s%s%04d", $prefix, $year, $month, $newNum);
    }

    /**
     * Calcular precio de cotización
     */
    public function calculateQuotationPrice($items, $taxRate = 16.0) {
        $subtotal = 0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $product = $this->getProductForQuotation($item['product_id'], $item['variant_id'] ?? null);
            if (!$product) {
                throw new Exception("Producto no encontrado: {$item['product_id']}");
            }

            $quantity = (int)$item['quantity'];
            $unitPrice = $product['price'];
            $itemTotal = $quantity * $unitPrice;

            $calculatedItems[] = [
                'product_id' => $product['product_id'],
                'variant_id' => $product['variant_id'],
                'product_name' => $product['name'],
                'product_sku' => $product['sku'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $itemTotal
            ];

            $subtotal += $itemTotal;
        }

        $taxAmount = ($subtotal * $taxRate) / 100;
        $total = $subtotal + $taxAmount;

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total
        ];
    }

    /**
     * Obtener productos sugeridos
     */
    public function getSuggestedProducts($categoryId = null, $limit = 6) {
        $whereClause = $categoryId ? 'AND p.category_id = ?' : '';
        $params = $categoryId ? [$categoryId] : [];

        $sql = "SELECT DISTINCT p.id, p.name, p.description, p.sku, p.slug,
                       c.name as category_name, c.slug as category_slug,
                       MIN(pv.price) as min_price,
                       MAX(pv.price) as max_price,
                       GROUP_CONCAT(DISTINCT pi.url) as images
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' {$whereClause}
                GROUP BY p.id, p.name, p.description, p.sku, p.slug, c.name, c.slug
                ORDER BY RAND()
                LIMIT ?";
        
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener cotizaciones públicas recientes
     */
    public function getRecentPublicQuotations($limit = 10) {
        $sql = "SELECT q.quotation_number, q.total, q.created_at, q.valid_until,
                       c.name as customer_name, c.company as customer_company
                FROM quotations q
                LEFT JOIN customers c ON q.customer_id = c.id
                WHERE q.is_public = 1
                ORDER BY q.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Validar datos de cotización
     */
    public function validateQuotationData($data) {
        $errors = [];

        // Validar datos del cliente
        if (empty($data['customer_name'])) {
            $errors['customer_name'] = 'El nombre del cliente es requerido';
        }

        if (empty($data['customer_email'])) {
            $errors['customer_email'] = 'El email del cliente es requerido';
        } elseif (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'El email del cliente no es válido';
        }

        // Validar items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'Debe incluir al menos un producto';
        } else {
            foreach ($data['items'] as $index => $item) {
                if (empty($item['product_id'])) {
                    $errors["items.{$index}.product_id"] = 'El producto es requerido';
                }

                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    $errors["items.{$index}.quantity"] = 'La cantidad debe ser mayor a 0';
                }
            }
        }

        // Validar tasa de impuestos
        if (isset($data['tax_rate']) && ($data['tax_rate'] < 0 || $data['tax_rate'] > 100)) {
            $errors['tax_rate'] = 'La tasa de impuestos debe estar entre 0 y 100';
        }

        return $errors;
    }

    /**
     * Obtener estadísticas del cotizador
     */
    public function getQuoterStats() {
        $stats = [];

        // Total de cotizaciones públicas
        $stats['total_public_quotations'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM quotations WHERE is_public = 1"
        )['total'];

        // Cotizaciones del mes
        $stats['monthly_quotations'] = $this->db->fetch(
            "SELECT COUNT(*) as total 
             FROM quotations 
             WHERE is_public = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        )['total'];

        // Valor total de cotizaciones
        $stats['total_value'] = $this->db->fetch(
            "SELECT SUM(total) as total_value 
             FROM quotations 
             WHERE is_public = 1 AND status != 'rejected'"
        )['total_value'] ?? 0;

        // Tasa de conversión
        $totalQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total FROM quotations WHERE is_public = 1"
        )['total'];

        $convertedQuotations = $this->db->fetch(
            "SELECT COUNT(*) as total FROM quotations WHERE is_public = 1 AND status = 'converted'"
        )['total'];

        $stats['conversion_rate'] = $totalQuotations > 0 ? 
            round(($convertedQuotations / $totalQuotations) * 100, 2) : 0;

        return $stats;
    }
}
