<?php
/**
 * Modelo EmailService - DT Studio
 * Servicio de envío de emails
 */

require_once __DIR__ . '/../includes/Database.php';

class EmailService {
    private $db;
    private $smtpConfig;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSMTPConfig();
    }

    /**
     * Cargar configuración SMTP
     */
    private function loadSMTPConfig() {
        $config = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM email_settings WHERE is_active = 1"
        );
        
        $this->smtpConfig = [];
        foreach ($config as $setting) {
            $this->smtpConfig[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Configuración por defecto si no hay configuración en BD
        if (empty($this->smtpConfig)) {
            $this->smtpConfig = [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'from_email' => 'noreply@dtstudio.com',
                'from_name' => 'DT Studio',
                'reply_to' => 'info@dtstudio.com'
            ];
        }
    }

    /**
     * Enviar email
     */
    public function sendEmail($to, $subject, $message, $options = []) {
        try {
            // Validar destinatario
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email de destinatario inválido");
            }

            // Preparar datos del email
            $emailData = [
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'from_email' => $options['from_email'] ?? $this->smtpConfig['from_email'],
                'from_name' => $options['from_name'] ?? $this->smtpConfig['from_name'],
                'reply_to' => $options['reply_to'] ?? $this->smtpConfig['reply_to'],
                'cc' => $options['cc'] ?? null,
                'bcc' => $options['bcc'] ?? null,
                'attachments' => $options['attachments'] ?? [],
                'template_id' => $options['template_id'] ?? null,
                'priority' => $options['priority'] ?? 'normal'
            ];

            // Crear notificación
            $notificationId = $this->createNotification($emailData);

            // Intentar envío
            $result = $this->attemptSend($emailData);

            if ($result['success']) {
                // Marcar como enviada
                $this->markAsSent($notificationId, $result['message_id']);
                return [
                    'success' => true,
                    'message' => 'Email enviado exitosamente',
                    'notification_id' => $notificationId,
                    'message_id' => $result['message_id']
                ];
            } else {
                // Marcar como fallida
                $this->markAsFailed($notificationId, $result['error']);
                return [
                    'success' => false,
                    'message' => 'Error al enviar email',
                    'error' => $result['error'],
                    'notification_id' => $notificationId
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email con plantilla
     */
    public function sendTemplateEmail($to, $templateId, $data = [], $options = []) {
        try {
            // Obtener plantilla
            $template = $this->getTemplate($templateId);
            if (!$template) {
                throw new Exception("Plantilla no encontrada");
            }

            // Procesar plantilla
            $subject = $this->processTemplate($template['subject'], $data);
            $message = $this->processTemplate($template['body'], $data);

            // Enviar email
            return $this->sendEmail($to, $subject, $message, array_merge($options, [
                'template_id' => $templateId
            ]));

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar email con plantilla',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email masivo
     */
    public function sendBulkEmail($recipients, $subject, $message, $options = []) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendEmail($recipient, $subject, $message, $options);
            
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
     * Intentar envío de email
     */
    private function attemptSend($emailData) {
        try {
            // Simular envío de email (en producción usar PHPMailer o similar)
            $this->simulateEmailSending($emailData);
            
            return [
                'success' => true,
                'message_id' => 'msg_' . mt_rand(100000, 999999)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Simular envío de email
     */
    private function simulateEmailSending($emailData) {
        // Simular diferentes resultados basados en el destinatario
        $email = $emailData['to'];
        
        // Simular fallos para ciertos dominios
        $blockedDomains = ['test.com', 'example.com', 'invalid.com'];
        foreach ($blockedDomains as $domain) {
            if (strpos($email, $domain) !== false) {
                throw new Exception("Dominio bloqueado: {$domain}");
            }
        }
        
        // Simular fallos aleatorios (5% de probabilidad)
        if (mt_rand(1, 100) <= 5) {
            throw new Exception("Error de conexión SMTP");
        }
        
        // Simular delay de envío
        usleep(100000); // 0.1 segundos
    }

    /**
     * Crear notificación de email
     */
    private function createNotification($emailData) {
        $notificationData = [
            'type' => 'email',
            'recipient' => $emailData['to'],
            'subject' => $emailData['subject'],
            'message' => $emailData['message'],
            'template_id' => $emailData['template_id'],
            'data' => [
                'from_email' => $emailData['from_email'],
                'from_name' => $emailData['from_name'],
                'reply_to' => $emailData['reply_to'],
                'cc' => $emailData['cc'],
                'bcc' => $emailData['bcc'],
                'attachments' => $emailData['attachments']
            ],
            'status' => 'pending',
            'priority' => $emailData['priority'],
            'created_by' => 1
        ];

        $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, template_id, data, status, priority, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
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
        $sql = "SELECT * FROM email_templates WHERE id = ? AND is_active = 1";
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
     * Obtener plantillas de email
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
        
        $sql = "SELECT * FROM email_templates 
                {$whereClause}
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Crear plantilla de email
     */
    public function createTemplate($data) {
        $required = ['name', 'subject', 'body'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }

        $sql = "INSERT INTO email_templates (name, subject, body, category, variables, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['subject'],
            $data['body'],
            $data['category'] ?? 'general',
            json_encode($data['variables'] ?? []),
            $data['is_active'] ?? 1
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener configuración SMTP
     */
    public function getSMTPConfig() {
        return $this->smtpConfig;
    }

    /**
     * Actualizar configuración SMTP
     */
    public function updateSMTPConfig($config) {
        foreach ($config as $key => $value) {
            $this->db->query(
                "INSERT INTO email_settings (setting_key, setting_value, is_active) 
                 VALUES (?, ?, 1) 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                [$key, $value, $value]
            );
        }
        
        $this->loadSMTPConfig();
        return true;
    }

    /**
     * Obtener estadísticas de emails
     */
    public function getEmailStats($filters = []) {
        $whereConditions = ['n.type = "email"'];
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
        
        // Total de emails
        $stats['total_emails'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause}",
            $params
        )['total'];
        
        // Emails por estado
        $stats['emails_by_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count
             FROM notifications {$whereClause}
             GROUP BY status
             ORDER BY count DESC",
            $params
        );
        
        // Emails por plantilla
        $stats['emails_by_template'] = $this->db->fetchAll(
            "SELECT et.name as template_name, COUNT(n.id) as count
             FROM notifications n
             LEFT JOIN email_templates et ON n.template_id = et.id
             {$whereClause}
             GROUP BY n.template_id, et.name
             ORDER BY count DESC",
            $params
        );
        
        // Tasa de éxito
        $totalEmails = $stats['total_emails'];
        $sentEmails = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notifications {$whereClause} AND status = 'sent'",
            $params
        )['total'];
        
        $stats['success_rate'] = $totalEmails > 0 ? 
            round(($sentEmails / $totalEmails) * 100, 2) : 0;
        
        return $stats;
    }

    /**
     * Generar ID único de notificación
     */
    private function generateNotificationId() {
        $prefix = 'EMAIL';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Validar datos de email
     */
    public function validateEmailData($data) {
        $errors = [];

        // Validar destinatario
        if (empty($data['to'])) {
            $errors['to'] = 'El destinatario es requerido';
        } elseif (!filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {
            $errors['to'] = 'El destinatario debe ser un email válido';
        }

        // Validar asunto
        if (empty($data['subject'])) {
            $errors['subject'] = 'El asunto es requerido';
        }

        // Validar mensaje
        if (empty($data['message'])) {
            $errors['message'] = 'El mensaje es requerido';
        }

        // Validar CC si se proporciona
        if (!empty($data['cc']) && !filter_var($data['cc'], FILTER_VALIDATE_EMAIL)) {
            $errors['cc'] = 'El CC debe ser un email válido';
        }

        // Validar BCC si se proporciona
        if (!empty($data['bcc']) && !filter_var($data['bcc'], FILTER_VALIDATE_EMAIL)) {
            $errors['bcc'] = 'El BCC debe ser un email válido';
        }

        return $errors;
    }
}
