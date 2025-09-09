<?php
/**
 * Modelo SMSService - DT Studio
 * Servicio de envío de SMS
 */

require_once __DIR__ . '/../includes/Database.php';

class SMSService {
    private $db;
    private $smsConfig;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSMSConfig();
    }

    /**
     * Cargar configuración SMS
     */
    private function loadSMSConfig() {
        $config = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM sms_settings WHERE is_active = 1"
        );
        
        $this->smsConfig = [];
        foreach ($config as $setting) {
            $this->smsConfig[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Configuración por defecto si no hay configuración en BD
        if (empty($this->smsConfig)) {
            $this->smsConfig = [
                'provider' => 'twilio',
                'account_sid' => '',
                'auth_token' => '',
                'from_number' => '+1234567890',
                'api_url' => 'https://api.twilio.com/2010-04-01/Accounts',
                'max_length' => 160,
                'unicode_support' => true
            ];
        }
    }

    /**
     * Enviar SMS
     */
    public function sendSMS($to, $message, $options = []) {
        try {
            // Validar número de teléfono
            $to = $this->formatPhoneNumber($to);
            if (!$this->isValidPhoneNumber($to)) {
                throw new Exception("Número de teléfono inválido");
            }

            // Validar longitud del mensaje
            if (strlen($message) > $this->smsConfig['max_length']) {
                throw new Exception("El mensaje excede la longitud máxima de {$this->smsConfig['max_length']} caracteres");
            }

            // Preparar datos del SMS
            $smsData = [
                'to' => $to,
                'message' => $message,
                'from_number' => $options['from_number'] ?? $this->smsConfig['from_number'],
                'template_id' => $options['template_id'] ?? null,
                'priority' => $options['priority'] ?? 'normal',
                'scheduled_at' => $options['scheduled_at'] ?? null
            ];

            // Crear notificación
            $notificationId = $this->createNotification($smsData);

            // Intentar envío
            $result = $this->attemptSend($smsData);

            if ($result['success']) {
                // Marcar como enviada
                $this->markAsSent($notificationId, $result['message_id']);
                return [
                    'success' => true,
                    'message' => 'SMS enviado exitosamente',
                    'notification_id' => $notificationId,
                    'message_id' => $result['message_id']
                ];
            } else {
                // Marcar como fallida
                $this->markAsFailed($notificationId, $result['error']);
                return [
                    'success' => false,
                    'message' => 'Error al enviar SMS',
                    'error' => $result['error'],
                    'notification_id' => $notificationId
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar SMS',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar SMS con plantilla
     */
    public function sendTemplateSMS($to, $templateId, $data = [], $options = []) {
        try {
            // Obtener plantilla
            $template = $this->getTemplate($templateId);
            if (!$template) {
                throw new Exception("Plantilla no encontrada");
            }

            // Procesar plantilla
            $message = $this->processTemplate($template['body'], $data);

            // Enviar SMS
            return $this->sendSMS($to, $message, array_merge($options, [
                'template_id' => $templateId
            ]));

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar SMS con plantilla',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar SMS masivo
     */
    public function sendBulkSMS($recipients, $message, $options = []) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendSMS($recipient, $message, $options);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
            
            $results[] = $result;
        }

        return [
            'success' => $failureCount === 0,
            'message' => "Enviados: {$successCount}, Fallidos: {$failureCount}",
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Intentar envío de SMS
     */
    private function attemptSend($smsData) {
        try {
            // Simular envío de SMS (en producción usar Twilio, AWS SNS, etc.)
            $this->simulateSMSSending($smsData);
            
            return [
                'success' => true,
                'message_id' => 'sms_' . mt_rand(100000, 999999)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Simular envío de SMS
     */
    private function simulateSMSSending($smsData) {
        $phoneNumber = $smsData['to'];
        
        // Simular fallos para ciertos números
        $blockedNumbers = ['+1234567890', '+0987654321'];
        if (in_array($phoneNumber, $blockedNumbers)) {
            throw new Exception("Número bloqueado: {$phoneNumber}");
        }
        
        // Simular fallos aleatorios (3% de probabilidad)
        if (mt_rand(1, 100) <= 3) {
            throw new Exception("Error de conexión con el proveedor SMS");
        }
        
        // Simular delay de envío
        usleep(50000); // 0.05 segundos
    }

    /**
     * Crear notificación de SMS
     */
    private function createNotification($smsData) {
        $notificationData = [
            'type' => 'sms',
            'recipient' => $smsData['to'],
            'subject' => 'SMS',
            'message' => $smsData['message'],
            'template_id' => $smsData['template_id'],
            'data' => [
                'from_number' => $smsData['from_number'],
                'provider' => $this->smsConfig['provider']
            ],
            'status' => 'pending',
            'priority' => $smsData['priority'],
            'scheduled_at' => $smsData['scheduled_at'],
            'created_by' => 1
        ];

        $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, template_id, data, status, priority, scheduled_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $this->generateNotificationId(),
            $notificationData['type'],
            $notificationData['recipient'],
            $notificationData['subject'],
            $notificationData['message'],
            $notificationData['template_id'],
            json_encode($notificationData['data']),
            $notificationData['status'],
            $notificationData['priority'],
            $notificationData['scheduled_at'],
            $notificationData['created_by']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Marcar como enviada
     */
    private function markAsSent($notificationId, $messageId) {
        $this->db->query(
            "UPDATE notifications SET status = 'sent', sent_at = NOW(), data = JSON_SET(data, '$.message_id', ?) WHERE id = ?",
            [$messageId, $notificationId]
        );
    }

    /**
     * Marcar como fallida
     */
    private function markAsFailed($notificationId, $errorMessage) {
        $this->db->query(
            "UPDATE notifications SET status = 'failed', error_message = ? WHERE id = ?",
            [$errorMessage, $notificationId]
        );
    }

    /**
     * Obtener plantilla
     */
    private function getTemplate($templateId) {
        $sql = "SELECT * FROM sms_templates WHERE id = ? AND is_active = 1";
        return $this->db->fetch($sql, [$templateId]);
    }

    /**
     * Procesar plantilla
     */
    private function processTemplate($template, $data) {
        $processed = $template;
        
        foreach ($data as $key => $value) {
            $processed = str_replace("{{$key}}", $value, $processed);
        }
        
        return $processed;
    }

    /**
     * Formatear número de teléfono
     */
    private function formatPhoneNumber($phone) {
        // Remover caracteres no numéricos
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Agregar + si no tiene código de país
        if (!str_starts_with($phone, '+')) {
            // Asumir México si no tiene código de país
            if (strlen($phone) === 10) {
                $phone = '+52' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Validar número de teléfono
     */
    private function isValidPhoneNumber($phone) {
        // Validar formato internacional
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone);
    }

    /**
     * Obtener plantillas de SMS
     */
    public function getTemplates($filters = []) {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['category'])) {
            $whereConditions[] = 'category = ?';
            $params[] = $filters['category'];
        }
        
        if (isset($filters['is_active'])) {
            $whereConditions[] = 'is_active = ?';
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM sms_templates 
                {$whereClause}
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Crear plantilla de SMS
     */
    public function createTemplate($data) {
        $required = ['name', 'body'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        // Validar longitud
        if (strlen($data['body']) > $this->smsConfig['max_length']) {
            throw new Exception("La plantilla excede la longitud máxima de {$this->smsConfig['max_length']} caracteres");
        }

        $sql = "INSERT INTO sms_templates (name, body, category, variables, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['body'],
            $data['category'] ?? 'general',
            json_encode($data['variables'] ?? []),
            $data['is_active'] ?? 1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener configuración SMS
     */
    public function getSMSConfig() {
        return $this->smsConfig;
    }

    /**
     * Actualizar configuración SMS
     */
    public function updateSMSConfig($config) {
        foreach ($config as $key => $value) {
            $this->db->query(
                "INSERT INTO sms_settings (setting_key, setting_value, is_active) 
                 VALUES (?, ?, 1) 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                [$key, $value, $value]
            );
        }
        
        $this->loadSMSConfig();
        return true;
    }

    /**
     * Obtener estadísticas de SMS
     */
    public function getSMSStats($filters = []) {
        $whereConditions = ['n.type = "sms"'];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(n.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(n.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $stats = [];
        
        // Total de SMS
        $stats['total_sms'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause}",
            $params
        )['total'];
        
        // SMS por estado
        $stats['sms_by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count
             FROM notifications {$whereClause}
             GROUP BY status
             ORDER BY count DESC",
            $params
        );
        
        // SMS por plantilla
        $stats['sms_by_template'] = $this->db->fetchAll(
            "SELECT st.name as template_name, COUNT(n.id) as count
             FROM notifications n
             LEFT JOIN sms_templates st ON n.template_id = st.id
             {$whereClause}
             GROUP BY n.template_id, st.name
             ORDER BY count DESC",
            $params
        );
        
        // Tasa de éxito
        $totalSMS = $stats['total_sms'];
        $sentSMS = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause} AND status = 'sent'",
            $params
        )['total'];
        
        $stats['success_rate'] = $totalSMS > 0 ? 
            round(($sentSMS / $totalSMS) * 100, 2) : 0;
        
        return $stats;
    }

    /**
     * Obtener balance de SMS
     */
    public function getSMSBalance() {
        // Simular consulta de balance (en producción consultar API del proveedor)
        return [
            'balance' => mt_rand(100, 10000),
            'currency' => 'USD',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Obtener historial de SMS
     */
    public function getSMSHistory($phoneNumber, $limit = 50) {
        $sql = "SELECT n.*, 
                       u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.type = 'sms' AND n.recipient = ?
                ORDER BY n.created_at DESC
                LIMIT ?";
        
        $notifications = $this->db->fetchAll($sql, [$phoneNumber, $limit]);
        
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
        $prefix = 'SMS';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Validar datos de SMS
     */
    public function validateSMSData($data) {
        $errors = [];

        // Validar destinatario
        if (empty($data['to'])) {
            $errors['to'] = 'El destinatario es requerido';
        } else {
            $formattedPhone = $this->formatPhoneNumber($data['to']);
            if (!$this->isValidPhoneNumber($formattedPhone)) {
                $errors['to'] = 'El destinatario debe ser un número de teléfono válido';
            }
        }

        // Validar mensaje
        if (empty($data['message'])) {
            $errors['message'] = 'El mensaje es requerido';
        } elseif (strlen($data['message']) > $this->smsConfig['max_length']) {
            $errors['message'] = "El mensaje excede la longitud máxima de {$this->smsConfig['max_length']} caracteres";
        }

        return $errors;
    }
}
