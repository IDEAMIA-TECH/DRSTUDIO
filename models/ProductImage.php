<?php
/**
 * Modelo ProductImage - DT Studio
 * Gestión de imágenes de productos
 */

require_once __DIR__ . '/../includes/Database.php';

class ProductImage {
    private $db;
    private $table = 'product_images';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las imágenes de un producto
     */
    public function getByProduct($productId) {
        $sql = "SELECT pi.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pi 
                LEFT JOIN products p ON pi.product_id = p.id
                WHERE pi.product_id = ? 
                ORDER BY pi.sort_order ASC, pi.created_at ASC";
        
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Obtener todas las imágenes de una variante
     */
    public function getByVariant($variantId) {
        $sql = "SELECT pi.*, p.name as product_name, p.sku as product_sku, pv.name as variant_name
                FROM {$this->table} pi 
                LEFT JOIN products p ON pi.product_id = p.id
                LEFT JOIN product_variants pv ON pi.variant_id = pv.id
                WHERE pi.variant_id = ? 
                ORDER BY pi.sort_order ASC, pi.created_at ASC";
        
        return $this->db->fetchAll($sql, [$variantId]);
    }

    /**
     * Obtener imagen por ID
     */
    public function getById($id) {
        $sql = "SELECT pi.*, p.name as product_name, p.sku as product_sku, pv.name as variant_name
                FROM {$this->table} pi 
                LEFT JOIN products p ON pi.product_id = p.id
                LEFT JOIN product_variants pv ON pi.variant_id = pv.id
                WHERE pi.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Crear nueva imagen
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['product_id', 'url'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Si es imagen primaria, desmarcar otras como primarias
        if (isset($data['is_primary']) && $data['is_primary']) {
            $this->db->query(
                "UPDATE {$this->table} SET is_primary = 0 WHERE product_id = ?",
                [$data['product_id']]
            );
        }

        // Preparar datos para inserción
        $fields = ['product_id', 'variant_id', 'url', 'alt_text', 'sort_order', 'is_primary'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = $data[$field];
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar imagen
     */
    public function update($id, $data) {
        // Verificar que la imagen existe
        if (!$this->getById($id)) {
            throw new Exception("Imagen no encontrada");
        }

        // Si se está marcando como primaria, desmarcar otras
        if (isset($data['is_primary']) && $data['is_primary']) {
            $image = $this->getById($id);
            $this->db->query(
                "UPDATE {$this->table} SET is_primary = 0 WHERE product_id = ? AND id != ?",
                [$image['product_id'], $id]
            );
        }

        // Preparar datos para actualización
        $fields = ['url', 'alt_text', 'sort_order', 'is_primary'];
        $setParts = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($setParts)) {
            throw new Exception("No hay datos para actualizar");
        }

        $values[] = $id; // Para el WHERE
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?";

        $this->db->query($sql, $values);
        return true;
    }

    /**
     * Eliminar imagen
     */
    public function delete($id) {
        // Verificar que la imagen existe
        if (!$this->getById($id)) {
            throw new Exception("Imagen no encontrada");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Establecer imagen como primaria
     */
    public function setPrimary($id) {
        $image = $this->getById($id);
        if (!$image) {
            throw new Exception("Imagen no encontrada");
        }

        $this->db->beginTransaction();
        
        try {
            // Desmarcar todas las imágenes del producto como primarias
            $this->db->query(
                "UPDATE {$this->table} SET is_primary = 0 WHERE product_id = ?",
                [$image['product_id']]
            );
            
            // Marcar la imagen seleccionada como primaria
            $this->db->query(
                "UPDATE {$this->table} SET is_primary = 1 WHERE id = ?",
                [$id]
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Reordenar imágenes
     */
    public function reorder($imageIds) {
        $this->db->beginTransaction();
        
        try {
            foreach ($imageIds as $index => $imageId) {
                $this->db->query(
                    "UPDATE {$this->table} SET sort_order = ? WHERE id = ?",
                    [$index + 1, $imageId]
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtener imagen primaria de un producto
     */
    public function getPrimaryByProduct($productId) {
        $sql = "SELECT pi.*, p.name as product_name, p.sku as product_sku
                FROM {$this->table} pi 
                LEFT JOIN products p ON pi.product_id = p.id
                WHERE pi.product_id = ? AND pi.is_primary = 1
                LIMIT 1";
        
        return $this->db->fetch($sql, [$productId]);
    }

    /**
     * Obtener imagen primaria de una variante
     */
    public function getPrimaryByVariant($variantId) {
        $sql = "SELECT pi.*, p.name as product_name, p.sku as product_sku, pv.name as variant_name
                FROM {$this->table} pi 
                LEFT JOIN products p ON pi.product_id = p.id
                LEFT JOIN product_variants pv ON pi.variant_id = pv.id
                WHERE pi.variant_id = ? AND pi.is_primary = 1
                LIMIT 1";
        
        return $this->db->fetch($sql, [$variantId]);
    }

    /**
     * Obtener estadísticas de imágenes
     */
    public function getStats() {
        $stats = [];

        // Total de imágenes
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table}")['count'];

        // Imágenes por producto
        $stats['by_product'] = $this->db->fetchAll(
            "SELECT p.name as product_name, COUNT(pi.id) as image_count 
             FROM products p 
             LEFT JOIN {$this->table} pi ON p.id = pi.product_id 
             GROUP BY p.id, p.name 
             ORDER BY image_count DESC 
             LIMIT 10"
        );

        // Productos sin imágenes
        $stats['products_without_images'] = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM products p 
             LEFT JOIN {$this->table} pi ON p.id = pi.product_id 
             WHERE pi.id IS NULL"
        )['count'];

        // Imágenes primarias
        $stats['primary_images'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE is_primary = 1"
        )['count'];

        return $stats;
    }

    /**
     * Validar datos de imagen
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar URL
        if (empty($data['url'])) {
            $errors['url'] = 'La URL es requerida';
        } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'La URL no es válida';
        } elseif (strlen($data['url']) > 500) {
            $errors['url'] = 'La URL no puede tener más de 500 caracteres';
        }

        // Validar alt_text
        if (!empty($data['alt_text']) && strlen($data['alt_text']) > 255) {
            $errors['alt_text'] = 'El texto alternativo no puede tener más de 255 caracteres';
        }

        // Validar sort_order
        if (isset($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'] = 'El orden debe ser un número mayor o igual a 0';
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

        // Validar variante (opcional)
        if (!empty($data['variant_id'])) {
            $variant = $this->db->fetch("SELECT id FROM product_variants WHERE id = ?", [$data['variant_id']]);
            if (!$variant) {
                $errors['variant_id'] = 'La variante seleccionada no existe';
            }
        }

        return $errors;
    }

    /**
     * Subir múltiples imágenes
     */
    public function uploadMultiple($productId, $images, $variantId = null) {
        $this->db->beginTransaction();
        
        try {
            $uploadedImages = [];
            $sortOrder = $this->getNextSortOrder($productId, $variantId);
            
            foreach ($images as $index => $imageData) {
                $imageData['product_id'] = $productId;
                $imageData['variant_id'] = $variantId;
                $imageData['sort_order'] = $sortOrder + $index;
                
                if ($index === 0 && !isset($imageData['is_primary'])) {
                    $imageData['is_primary'] = 1; // Primera imagen como primaria
                }
                
                $imageId = $this->create($imageData);
                $uploadedImages[] = $imageId;
            }
            
            $this->db->commit();
            return $uploadedImages;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtener siguiente orden de clasificación
     */
    private function getNextSortOrder($productId, $variantId = null) {
        $sql = "SELECT MAX(sort_order) as max_order FROM {$this->table} WHERE product_id = ?";
        $params = [$productId];
        
        if ($variantId !== null) {
            $sql .= " AND variant_id = ?";
            $params[] = $variantId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ($result['max_order'] ?? 0) + 1;
    }

    /**
     * Eliminar todas las imágenes de un producto
     */
    public function deleteByProduct($productId) {
        $sql = "DELETE FROM {$this->table} WHERE product_id = ?";
        $this->db->query($sql, [$productId]);
        return true;
    }

    /**
     * Eliminar todas las imágenes de una variante
     */
    public function deleteByVariant($variantId) {
        $sql = "DELETE FROM {$this->table} WHERE variant_id = ?";
        $this->db->query($sql, [$variantId]);
        return true;
    }
}
