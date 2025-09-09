<?php
/**
 * Modelo Notification - DT Studio
 * Gestión de notificaciones del sistema
 */

require_once __DIR__ . '/../includes/Database.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear notificación
     */
    public function create($data) {
        // Validar datos requeridos
        $required = ['type', 'recipient', 'subject', 'message'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar tipo de notificación
        $validTypes = ['email', 'sms', 'push', 'system'];
        if (!in_array($data['type'], $validTypes)) {
            throw new Exception("Tipo de notificación no válido");
        }

        // Validar estado
        $data['status'] = $data['status'] ?? 'pending';
        $validStatuses = ['pending', 'sent', 'failed', 'delivered', 'read'];
        if (!in_array($data['status'], $validStatuses)) {
            throw new Exception("Estado de notificación no válido");
        }

        // Generar ID único si no se proporciona
        $data['notification_id'] = $data['notification_id'] ?? $this->generateNotificationId();

        $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, template_id, data, status, priority, scheduled_at, sent_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['notification_id'],
            $data['type'],
            $data['recipient'],
            $data['subject'],
            $data['message'],
            $data['template_id'] ?? null,
            json_encode($data['data'] ?? []),
            $data['status'],
            $data['priority'] ?? 'normal',
            $data['scheduled_at'] ?? null,
            $data['sent_at'] ?? null,
            $data['created_by'] ?? 1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener notificación por ID
     */
    public function getById($id) {
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.id = ?";
        
        $notification = $this->db->fetch($sql, [$id]);
        
        if ($notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notification;
    }

    /**
     * Obtener notificación por notification_id
     */
    public function getByNotificationId($notificationId) {
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.notification_id = ?";
        
        $notification = $this->db->fetch($sql, [$notificationId]);
        
        if ($notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notification;
    }

    /**
     * Listar notificaciones con filtros
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $whereConditions[] = 'n.type = ?';
            $params[] = $filters['type'];
        }
        
        // Filtro por estado
        if (!empty($filters['status'])) {
            $whereConditions[] = 'n.status = ?';
            $params[] = $filters['status'];
        }
        
        // Filtro por prioridad
        if (!empty($filters['priority'])) {
            $whereConditions[] = 'n.priority = ?';
            $params[] = $filters['priority'];
        }
        
        // Filtro por destinatario
        if (!empty($filters['recipient'])) {
            $whereConditions[] = 'n.recipient LIKE ?';
            $params[] = "%{$filters['recipient']}%";
        }
        
        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(n.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(n.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $whereConditions[] = '(n.subject LIKE ? OR n.message LIKE ? OR n.recipient LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                {$whereClause}
                ORDER BY n.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $notifications = $this->db->fetchAll($sql, $params);
        
        // Decodificar datos JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) as total FROM notifications n {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        return [
            'data' => $notifications,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Actualizar notificación
     */
    public function update($id, $data) {
        // Validar que la notificación existe
        $notification = $this->getById($id);
        if (!$notification) {
            throw new Exception("La notificación no existe");
        }

        $allowedFields = ['status', 'sent_at', 'error_message', 'retry_count', 'data'];
        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'data') {
                    $updateFields[] = "{$field} = ?";
                    $params[] = json_encode($data[$field]);
                } else {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No hay campos para actualizar");
        }

        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE notifications SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * Eliminar notificación
     */
    public function delete($id) {
        // Validar que la notificación existe
        $notification = $this->getById($id);
        if (!$notification) {
            throw new Exception("La notificación no existe");
        }

        $this->db->query("DELETE FROM notifications WHERE id = ?", [$id]);

        return true;
    }

    /**
     * Marcar como enviada
     */
    public function markAsSent($id, $sentAt = null) {
        $sentAt = $sentAt ?? date('Y-m-d H:i:s');
        
        return $this->update($id, [
            'status' => 'sent',
            'sent_at' => $sentAt
        ]);
    }

    /**
     * Marcar como fallida
     */
    public function markAsFailed($id, $errorMessage = '') {
        return $this->update($id, [
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Marcar como entregada
     */
    public function markAsDelivered($id) {
        return $this->update($id, [
            'status' => 'delivered'
        ]);
    }

    /**
     * Marcar como leída
     */
    public function markAsRead($id) {
        return $this->update($id, [
            'status' => 'read'
        ]);
    }

    /**
     * Obtener notificaciones pendientes
     */
    public function getPending($limit = 50) {
        $sql = "SELECT * FROM notifications 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY priority DESC, created_at ASC
                LIMIT ?";
        
        $notifications = $this->db->fetchAll($sql, [$limit]);
        
        // Decodificar datos JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notifications;
    }

    /**
     * Obtener notificaciones por destinatario
     */
    public function getByRecipient($recipient, $type = null, $limit = 20) {
        $whereConditions = ['n.recipient = ?'];
        $params = [$recipient];
        
        if ($type) {
            $whereConditions[] = 'n.type = ?';
            $params[] = $type;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                {$whereClause}
                ORDER BY n.created_at DESC
                LIMIT ?";
        
        $params[] = $limit;
        $notifications = $this->db->fetchAll($sql, $params);
        
        // Decodificar datos JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notifications;
    }

    /**
     * Obtener notificaciones por tipo
     */
    public function getByType($type, $limit = 20) {
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.type = ?
                ORDER BY n.created_at DESC
                LIMIT ?";
        
        $notifications = $this->db->fetchAll($sql, [$type, $limit]);
        
        // Decodificar datos JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notifications;
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getStats($filters = []) {
        $whereConditions = ['1=1'];
        $params = [];
        
        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $stats = [];
        
        // Total de notificaciones
        $stats['total_notifications'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause}",
            $params
        )['total'];
        
        // Notificaciones por estado
        $stats['notifications_by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count
             FROM notifications {$whereClause}
             GROUP BY status
             ORDER BY count DESC",
            $params
        );
        
        // Notificaciones por tipo
        $stats['notifications_by_type'] = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count
             FROM notifications {$whereClause}
             GROUP BY type
             ORDER BY count DESC",
            $params
        );
        
        // Notificaciones por prioridad
        $stats['notifications_by_priority'] = $this->db->fetchAll(
            "SELECT priority, COUNT(*) as count
             FROM notifications {$whereClause}
             GROUP BY priority
             ORDER BY count DESC",
            $params
        );
        
        // Tasa de éxito
        $totalNotifications = $stats['total_notifications'];
        $sentNotifications = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause} AND status IN ('sent', 'delivered')",
            $params
        )['total'];
        
        $stats['success_rate'] = $totalNotifications > 0 ? 
            round(($sentNotifications / $totalNotifications) * 100, 2) : 0;
        
        // Notificaciones pendientes
        $stats['pending_notifications'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause} AND status = 'pending'",
            $params
        )['total'];
        
        return $stats;
    }

    /**
     * Reintentar notificación fallida
     */
    public function retry($id) {
        $notification = $this->getById($id);
        if (!$notification) {
            throw new Exception("La notificación no existe");
        }

        if ($notification['status'] !== 'failed') {
            throw new Exception("Solo se pueden reintentar notificaciones fallidas");
        }

        $retryCount = ($notification['retry_count'] ?? 0) + 1;
        $maxRetries = 3;

        if ($retryCount > $maxRetries) {
            throw new Exception("Se ha alcanzado el número máximo de reintentos");
        }

        return $this->update($id, [
            'status' => 'pending',
            'retry_count' => $retryCount,
            'error_message' => null
        ]);
    }

    /**
     * Programar notificación
     */
    public function schedule($id, $scheduledAt) {
        $scheduledAt = date('Y-m-d H:i:s', strtotime($scheduledAt));
        
        if ($scheduledAt <= date('Y-m-d H:i:s')) {
            throw new Exception("La fecha programada debe ser futura");
        }

        return $this->update($id, [
            'status' => 'pending',
            'scheduled_at' => $scheduledAt
        ]);
    }

    /**
     * Obtener notificaciones programadas
     */
    public function getScheduled($limit = 50) {
        $sql = "SELECT * FROM notifications 
                WHERE status = 'pending' 
                AND scheduled_at IS NOT NULL 
                AND scheduled_at <= NOW()
                ORDER BY scheduled_at ASC
                LIMIT ?";
        
        $notifications = $this->db->fetchAll($sql, [$limit]);
        
        // Decodificar datos JSON
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }
        
        return $notifications;
    }

    /**
     * Generar ID único de notificación
     */
    private function generateNotificationId() {
        $prefix = 'NOT';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Validar datos de notificación
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Validar tipo
        if (!$isUpdate && empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } elseif ($data['type'] && !in_array($data['type'], ['email', 'sms', 'push', 'system'])) {
            $errors['type'] = 'Tipo de notificación no válido';
        }

        // Validar destinatario
        if (!$isUpdate && empty($data['recipient'])) {
            $errors['recipient'] = 'El destinatario es requerido';
        } elseif ($data['recipient'] && !filter_var($data['recipient'], FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?[1-9]\d{1,14}$/', $data['recipient'])) {
            $errors['recipient'] = 'El destinatario debe ser un email válido o número de teléfono';
        }

        // Validar asunto
        if (!$isUpdate && empty($data['subject'])) {
            $errors['subject'] = 'El asunto es requerido';
        } elseif (strlen($data['subject']) > 255) {
            $errors['subject'] = 'El asunto no puede tener más de 255 caracteres';
        }

        // Validar mensaje
        if (!$isUpdate && empty($data['message'])) {
            $errors['message'] = 'El mensaje es requerido';
        }

        // Validar estado
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'sent', 'failed', 'delivered', 'read'])) {
            $errors['status'] = 'Estado de notificación no válido';
        }

        // Validar prioridad
        if (isset($data['priority']) && !in_array($data['priority'], ['low', 'normal', 'high', 'urgent'])) {
            $errors['priority'] = 'Prioridad no válida';
        }

        return $errors;
    }
}
