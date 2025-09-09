<?php
/**
 * Modelo ProductVariant - DT Studio
 * Gestión de variantes de productos
 */

require_once __DIR__ . '/../includes/Database.php';

class ProductVariant {
    private $db;
    private $table = 'product_variants';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las variantes de un producto
     */
    public function getByProduct($productId) {
        $sql = "SELECT pv.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pv 
                LEFT JOIN products p ON pv.product_id = p.id
                WHERE pv.product_id = ? 
                ORDER BY pv.name ASC";
        
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Obtener variante por ID
     */
    public function getById($id) {
        $sql = "SELECT pv.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pv 
                LEFT JOIN products p ON pv.product_id = p.id
                WHERE pv.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Obtener variante por SKU
     */
    public function getBySku($sku) {
        $sql = "SELECT pv.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pv 
                LEFT JOIN products p ON pv.product_id = p.id
                WHERE pv.sku = ?";
        
        return $this->db->fetch($sql, [$sku]);
    }

    /**
     * Crear nueva variante
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['product_id', 'name', 'price', 'cost'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Generar SKU si no se proporciona
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['product_id'], $data['name']);
        }

        // Verificar si el SKU ya existe
        if ($this->getBySku($data['sku'])) {
            throw new Exception("El SKU ya está en uso");
        }

        // Preparar datos para inserción
        $fields = ['product_id', 'name', 'sku', 'price', 'cost', 'stock', 'attributes', 'is_active'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'attributes' && is_array($data[$field])) {
                    $values[] = json_encode($data[$field]);
                } else {
                    $values[] = $data[$field];
                }
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar variante
     */
    public function update($id, $data) {
        // Verificar que la variante existe
        if (!$this->getById($id)) {
            throw new Exception("Variante no encontrada");
        }

        // Generar SKU si se está cambiando el nombre
        if (isset($data['name']) && empty($data['sku'])) {
            $variant = $this->getById($id);
            $data['sku'] = $this->generateSku($variant['product_id'], $data['name']);
        }

        // Si se está cambiando el SKU, verificar que no exista
        if (isset($data['sku'])) {
            $existingVariant = $this->getBySku($data['sku']);
            if ($existingVariant && $existingVariant['id'] != $id) {
                throw new Exception("El SKU ya está en uso");
            }
        }

        // Preparar datos para actualización
        $fields = ['name', 'sku', 'price', 'cost', 'stock', 'attributes', 'is_active'];
        $setParts = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = ?";
                if ($field === 'attributes' && is_array($data[$field])) {
                    $values[] = json_encode($data[$field]);
                } else {
                    $values[] = $data[$field];
                }
            }
        }

        if (empty($setParts)) {
            throw new Exception("No hay datos para actualizar");
        }

        $values[] = $id; // Para el WHERE
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = ?";

        $this->db->query($sql, $values);
        return true;
    }

    /**
     * Eliminar variante
     */
    public function delete($id) {
        // Verificar que la variante existe
        if (!$this->getById($id)) {
            throw new Exception("Variante no encontrada");
        }

        // Verificar si está en cotizaciones o pedidos
        $quotationCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM quotation_items WHERE variant_id = ?",
            [$id]
        )['count'];

        $orderCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM order_items WHERE variant_id = ?",
            [$id]
        )['count'];

        if ($quotationCount > 0 || $orderCount > 0) {
            throw new Exception("No se puede eliminar una variante que está en cotizaciones o pedidos");
        }

        // Eliminar imágenes asociadas
        $this->db->query("DELETE FROM product_images WHERE variant_id = ?", [$id]);

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Cambiar estado de la variante
     */
    public function toggleStatus($id) {
        $variant = $this->getById($id);
        if (!$variant) {
            throw new Exception("Variante no encontrada");
        }

        $newStatus = $variant['is_active'] ? 0 : 1;
        $sql = "UPDATE {$this->table} SET is_active = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$newStatus, $id]);
        return true;
    }

    /**
     * Actualizar stock de variante
     */
    public function updateStock($id, $quantity, $operation = 'set') {
        $variant = $this->getById($id);
        if (!$variant) {
            throw new Exception("Variante no encontrada");
        }

        $currentStock = $variant['stock'];
        
        switch ($operation) {
            case 'add':
                $newStock = $currentStock + $quantity;
                break;
            case 'subtract':
                $newStock = $currentStock - $quantity;
                if ($newStock < 0) {
                    throw new Exception("No hay suficiente stock disponible");
                }
                break;
            case 'set':
            default:
                $newStock = $quantity;
                break;
        }

        $sql = "UPDATE {$this->table} SET stock = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$newStock, $id]);
        return $newStock;
    }

    /**
     * Obtener variantes con stock bajo
     */
    public function getLowStock($threshold = 10) {
        $sql = "SELECT pv.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pv 
                LEFT JOIN products p ON pv.product_id = p.id
                WHERE pv.stock <= ? AND pv.is_active = 1
                ORDER BY pv.stock ASC";
        
        return $this->db->fetchAll($sql, [$threshold]);
    }

    /**
     * Obtener estadísticas de variantes
     */
    public function getStats() {
        $stats = [];

        // Total de variantes
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Variantes activas
        $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1")['count'];

        // Variantes inactivas
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Variantes con stock
        $stats['with_stock'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE stock > 0 AND is_active = 1")['count'];

        // Variantes sin stock
        $stats['out_of_stock'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} WHERE stock = 0 AND is_active = 1")['count'];

        // Stock total
        $stockResult = $this->db->fetch("SELECT SUM(stock) as total_stock FROM {$this->table} WHERE is_active = 1");
        $stats['total_stock'] = $stockResult['total_stock'] ?? 0;

        // Valor total del inventario
        $inventoryResult = $this->db->fetch("SELECT SUM(stock * cost) as total_value FROM {$this->table} WHERE is_active = 1");
        $stats['inventory_value'] = $inventoryResult['total_value'] ?? 0;

        return $stats;
    }

    /**
     * Generar SKU único para variante
     */
    private function generateSku($productId, $name) {
        // Obtener SKU del producto padre
        $product = $this->db->fetch("SELECT sku FROM products WHERE id = ?", [$productId]);
        if (!$product) {
            throw new Exception("Producto no encontrado");
        }

        $baseSku = $product['sku'];
        $variantSku = strtoupper(trim($name));
        $variantSku = preg_replace('/[^A-Z0-9]/', '', $variantSku);
        $variantSku = substr($variantSku, 0, 5); // Máximo 5 caracteres
        
        if (empty($variantSku)) {
            $variantSku = 'VAR';
        }
        
        $sku = $baseSku . '-' . $variantSku;
        $originalSku = $sku;
        $counter = 1;
        
        while ($this->getBySku($sku)) {
            $sku = $originalSku . $counter;
            $counter++;
        }
        
        return $sku;
    }

    /**
     * Validar datos de variante
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre no puede tener más de 255 caracteres';
        }

        // Validar SKU
        if (!empty($data['sku'])) {
            if (!preg_match('/^[A-Z0-9-]+$/', $data['sku'])) {
                $errors['sku'] = 'El SKU solo puede contener letras mayúsculas, números y guiones';
            } elseif (strlen($data['sku']) > 100) {
                $errors['sku'] = 'El SKU no puede tener más de 100 caracteres';
            }
        }

        // Validar precio
        if (empty($data['price'])) {
            $errors['price'] = 'El precio es requerido';
        } elseif (!is_numeric($data['price']) || $data['price'] < 0) {
            $errors['price'] = 'El precio debe ser un número mayor o igual a 0';
        }

        // Validar costo
        if (empty($data['cost'])) {
            $errors['cost'] = 'El costo es requerido';
        } elseif (!is_numeric($data['cost']) || $data['cost'] < 0) {
            $errors['cost'] = 'El costo debe ser un número mayor o igual a 0';
        }

        // Validar stock
        if (isset($data['stock'])) {
            if (!is_numeric($data['stock']) || $data['stock'] < 0) {
                $errors['stock'] = 'El stock debe ser un número mayor o igual a 0';
            }
        }

        // Validar producto
        if (empty($data['product_id'])) {
            $errors['product_id'] = 'El producto es requerido';
        } else {
            $product = $this->db->fetch("SELECT id FROM products WHERE id = ?", [$data['product_id']]);
            if (!$product) {
                $errors['product_id'] = 'El producto seleccionado no existe';
            }
        }

        // Validar atributos
        if (isset($data['attributes']) && !is_array($data['attributes'])) {
            $errors['attributes'] = 'Los atributos deben ser un array';
        }

        return $errors;
    }

    /**
     * Duplicar variante
     */
    public function duplicate($id, $newName) {
        $variant = $this->getById($id);
        if (!$variant) {
            throw new Exception("Variante no encontrada");
        }

        $data = [
            'product_id' => $variant['product_id'],
            'name' => $newName,
            'sku' => $this->generateSku($variant['product_id'], $newName),
            'price' => $variant['price'],
            'cost' => $variant['cost'],
            'stock' => 0, // Stock inicial en 0 para duplicados
            'attributes' => json_decode($variant['attributes'], true),
            'is_active' => 1
        ];

        return $this->create($data);
    }
}
