<?php
/**
 * Modelo Banner - DT Studio
 * Gestión de banners del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Banner {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear banner
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['title', 'image'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        $sql = "INSERT INTO banners (title, image, link, description, active, sort_order, start_date, end_date, target_blank) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['title'],
            $data['image'],
            $data['link'] ?? null,
            $data['description'] ?? null,
            $data['active'] ? 1 : 0,
            $data['sort_order'] ?? 0,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['target_blank'] ? 1 : 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener banner por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM banners WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Listar banners
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por estado activo
        if (isset($filters['active'])) {
            $whereConditions[] = 'active = ?';
            $params[] = $filters['active'] ? 1 : 0;
        }
        
        // Filtro por fecha
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(title LIKE ? OR description LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM banners 
                {$whereClause}
                ORDER BY sort_order ASC, created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $banners = $this->db->fetchAll($sql, $params);
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM banners {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $banners,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Obtener banners activos
     */
    public function getActive() {
        $sql = "SELECT * FROM banners 
                WHERE active = 1 
                AND (start_date IS NULL OR start_date <= NOW()) 
                AND (end_date IS NULL OR end_date >= NOW())
                ORDER BY sort_order ASC, created_at DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener banners por posición
     */
    public function getByPosition($position = 'home') {
        $sql = "SELECT * FROM banners 
                WHERE active = 1 
                AND (start_date IS NULL OR start_date <= NOW()) 
                AND (end_date IS NULL OR end_date >= NOW())
                AND position = ?
                ORDER BY sort_order ASC, created_at DESC";
        
        return $this->db->fetchAll($sql, [$position]);
    }

    /**
     * Actualizar banner
     */
    public function update($id, $data) {
        // Validar que el banner existe
        $banner = $this->getById($id);
        if (!$banner) {
            throw new Exception("El banner no existe");
        }

        $allowedFields = ['title', 'image', 'link', 'description', 'active', 'sort_order', 'start_date', 'end_date', 'target_blank'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $field === 'active' || $field === 'target_blank' ? 
                    ($data[$field] ? 1 : 0) : $data[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE banners SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * Eliminar banner
     */
    public function delete($id) {
        // Validar que el banner existe
        $banner = $this->getById($id);
        if (!$banner) {
            throw new Exception("El banner no existe");
        }

        $this->db->query("DELETE FROM banners WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Activar/desactivar banner
     */
    public function toggleStatus($id) {
        $banner = $this->getById($id);
        if (!$banner) {
            throw new Exception("El banner no existe");
        }

        $newStatus = $banner['active'] ? 0 : 1;
        
        $this->db->query(
            "UPDATE banners SET active = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $id]
        );

        return $newStatus;
    }

    /**
     * Reordenar banners
     */
    public function reorder($bannerIds) {
        foreach ($bannerIds as $index => $bannerId) {
            $this->db->query(
                "UPDATE banners SET sort_order = ?, updated_at = NOW() WHERE id = ?",
                [$index + 1, $bannerId]
            );
        }

        return true;
    }

    /**
     * Obtener estadísticas de banners
     */
    public function getStats() {
        $stats = [];
        
        // Total de banners
        $stats['total_banners'] = $this->db->fetch("SELECT COUNT(*) as total FROM banners")['total'];
        
        // Banners activos
        $stats['active_banners'] = $this->db->fetch("SELECT COUNT(*) as total FROM banners WHERE active = 1")['total'];
        
        // Banners inactivos
        $stats['inactive_banners'] = $this->db->fetch("SELECT COUNT(*) as total FROM banners WHERE active = 0")['total'];
        
        // Banners por mes
        $stats['banners_by_month'] = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
             FROM banners 
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
             ORDER BY month DESC"
        );
        
        return $stats;
    }

    /**
     * Obtener banners próximos a expirar
     */
    public function getExpiringSoon($days = 7) {
        $sql = "SELECT * FROM banners 
                WHERE active = 1 
                AND end_date IS NOT NULL 
                AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                ORDER BY end_date ASC";
        
        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Obtener banners expirados
     */
    public function getExpired() {
        $sql = "SELECT * FROM banners 
                WHERE active = 1 
                AND end_date IS NOT NULL 
                AND end_date < NOW()
                ORDER BY end_date DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Validar datos de banner
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar título
        if (!$isUpdate && empty($data['title'])) {
            $errors['title'] = 'El título es requerido';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'El título no puede tener más de 255 caracteres';
        }

        // Validar imagen
        if (!$isUpdate && empty($data['image'])) {
            $errors['image'] = 'La imagen es requerida';
        }

        // Validar enlace si se proporciona
        if (!empty($data['link']) && !filter_var($data['link'], FILTER_VALIDATE_URL)) {
            $errors['link'] = 'El enlace debe ser una URL válida';
        }

        // Validar fechas
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);
            
            if ($startDate >= $endDate) {
                $errors['end_date'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }
}
