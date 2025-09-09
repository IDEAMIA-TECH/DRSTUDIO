<?php
/**
 * Tests para el módulo de Sistema de Notificaciones - DT Studio
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class NotificationTest {
    private $db;
    private $testNotificationId;
    private $testEmailTemplateId;
    private $testSMSTemplateId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE NOTIFICACIONES ===\n\n";
        
        $this->testCreateTestData();
        $this->testCreateNotification();
        $this->testGetNotificationById();
        $this->testGetAllNotifications();
        $this->testUpdateNotification();
        $this->testMarkAsSent();
        $this->testMarkAsFailed();
        $this->testGetPendingNotifications();
        $this->testGetNotificationStats();
        $this->testSendEmail();
        $this->testSendSMS();
        $this->testCreateEmailTemplate();
        $this->testCreateSMSTemplate();
        $this->testGetEmailTemplates();
        $this->testGetSMSTemplates();
        $this->testGetEmailStats();
        $this->testGetSMSStats();
        $this->testValidateNotificationData();
        $this->testCleanup();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear datos de prueba
     */
    public function testCreateTestData() {
        echo "Test: Crear datos de prueba... ";
        
        try {
            // Crear plantilla de email
            $sql = "INSERT INTO email_templates (name, subject, body, category, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Email de Prueba', 'Asunto de Prueba', 'Mensaje de prueba {{name}}', 'test', 1]);
            $this->testEmailTemplateId = $this->db->lastInsertId();
            
            // Crear plantilla de SMS
            $sql = "INSERT INTO sms_templates (name, body, category, is_active) VALUES (?, ?, ?, ?)";
            $this->db->query($sql, ['SMS de Prueba', 'Mensaje SMS {{name}}', 'test', 1]);
            $this->testSMSTemplateId = $this->db->lastInsertId();
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear notificación
     */
    public function testCreateNotification() {
        echo "Test: Crear notificación... ";
        
        try {
            $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, status, priority, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['NOT-TEST-001', 'email', 'test@example.com', 'Test Subject', 'Test Message', 'pending', 'normal', 1]);
            $this->testNotificationId = $this->db->lastInsertId();
            
            if ($this->testNotificationId) {
                echo "✓ PASSED (ID: {$this->testNotificationId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID de la notificación\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener notificación por ID
     */
    public function testGetNotificationById() {
        echo "Test: Obtener notificación por ID... ";
        
        try {
            $sql = "SELECT n.*, u.name as created_by_name FROM notifications n LEFT JOIN users u ON n.created_by = u.id WHERE n.id = ?";
            $notification = $this->db->fetch($sql, [$this->testNotificationId]);
            
            if ($notification && $notification['id'] == $this->testNotificationId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Notificación no encontrada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todas las notificaciones
     */
    public function testGetAllNotifications() {
        echo "Test: Obtener todas las notificaciones... ";
        
        try {
            $sql = "SELECT n.*, u.name as created_by_name FROM notifications n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC LIMIT 20 OFFSET 0";
            $notifications = $this->db->fetchAll($sql);
            
            if (is_array($notifications)) {
                echo "✓ PASSED (Total: " . count($notifications) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron notificaciones\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar notificación
     */
    public function testUpdateNotification() {
        echo "Test: Actualizar notificación... ";
        
        try {
            $sql = "UPDATE notifications SET status = ?, sent_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, ['sent', $this->testNotificationId]);
            
            $notification = $this->db->fetch("SELECT * FROM notifications WHERE id = ?", [$this->testNotificationId]);
            
            if ($notification && $notification['status'] == 'sent') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Notificación no se actualizó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Marcar como enviada
     */
    public function testMarkAsSent() {
        echo "Test: Marcar como enviada... ";
        
        try {
            $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['NOT-TEST-002', 'email', 'test2@example.com', 'Test Subject 2', 'Test Message 2', 'pending', 1]);
            $notificationId = $this->db->lastInsertId();
            
            $sql = "UPDATE notifications SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [$notificationId]);
            
            $notification = $this->db->fetch("SELECT * FROM notifications WHERE id = ?", [$notificationId]);
            
            if ($notification && $notification['status'] == 'sent') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se marcó como enviada\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Marcar como fallida
     */
    public function testMarkAsFailed() {
        echo "Test: Marcar como fallida... ";
        
        try {
            $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['NOT-TEST-003', 'sms', '+1234567890', 'SMS Test', 'SMS Message', 'pending', 1]);
            $notificationId = $this->db->lastInsertId();
            
            $sql = "UPDATE notifications SET status = 'failed', error_message = ? WHERE id = ?";
            $this->db->query($sql, ['Error de conexión', $notificationId]);
            
            $notification = $this->db->fetch("SELECT * FROM notifications WHERE id = ?", [$notificationId]);
            
            if ($notification && $notification['status'] == 'failed') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - No se marcó como fallida\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener notificaciones pendientes
     */
    public function testGetPendingNotifications() {
        echo "Test: Obtener notificaciones pendientes... ";
        
        try {
            $sql = "SELECT * FROM notifications WHERE status = 'pending' ORDER BY priority DESC, created_at ASC LIMIT 50";
            $notifications = $this->db->fetchAll($sql);
            
            if (is_array($notifications)) {
                echo "✓ PASSED (Total: " . count($notifications) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron notificaciones pendientes\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de notificaciones
     */
    public function testGetNotificationStats() {
        echo "Test: Obtener estadísticas de notificaciones... ";
        
        try {
            $stats = [];
            
            // Total de notificaciones
            $stats['total_notifications'] = $this->db->fetch("SELECT COUNT(*) as total FROM notifications")['total'];
            
            // Notificaciones por estado
            $stats['notifications_by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count FROM notifications GROUP BY status ORDER BY count DESC"
            );
            
            if (isset($stats['total_notifications']) && isset($stats['notifications_by_status'])) {
                echo "✓ PASSED (Total: {$stats['total_notifications']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Enviar email
     */
    public function testSendEmail() {
        echo "Test: Enviar email... ";
        
        try {
            // Simular envío de email
            $emailData = [
                'to' => 'test@example.com',
                'subject' => 'Test Email',
                'message' => 'This is a test email'
            ];
            
            // Crear notificación de email
            $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['EMAIL-TEST-001', 'email', $emailData['to'], $emailData['subject'], $emailData['message'], 'sent', 1]);
            $notificationId = $this->db->lastInsertId();
            
            if ($notificationId) {
                echo "✓ PASSED (ID: {$notificationId})\n";
            } else {
                echo "✗ FAILED - No se creó la notificación de email\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Enviar SMS
     */
    public function testSendSMS() {
        echo "Test: Enviar SMS... ";
        
        try {
            // Simular envío de SMS
            $smsData = [
                'to' => '+1234567890',
                'message' => 'This is a test SMS'
            ];
            
            // Crear notificación de SMS
            $sql = "INSERT INTO notifications (notification_id, type, recipient, subject, message, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['SMS-TEST-001', 'sms', $smsData['to'], 'SMS', $smsData['message'], 'sent', 1]);
            $notificationId = $this->db->lastInsertId();
            
            if ($notificationId) {
                echo "✓ PASSED (ID: {$notificationId})\n";
            } else {
                echo "✗ FAILED - No se creó la notificación de SMS\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear plantilla de email
     */
    public function testCreateEmailTemplate() {
        echo "Test: Crear plantilla de email... ";
        
        try {
            $sql = "INSERT INTO email_templates (name, subject, body, category, variables, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Nueva Plantilla', 'Nuevo Asunto', 'Nuevo cuerpo {{variable}}', 'general', '{"variable": "valor"}', 1]);
            $templateId = $this->db->lastInsertId();
            
            if ($templateId) {
                echo "✓ PASSED (ID: {$templateId})\n";
            } else {
                echo "✗ FAILED - No se creó la plantilla de email\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear plantilla de SMS
     */
    public function testCreateSMSTemplate() {
        echo "Test: Crear plantilla de SMS... ";
        
        try {
            $sql = "INSERT INTO sms_templates (name, body, category, variables, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, ['Nueva Plantilla SMS', 'Nuevo mensaje {{variable}}', 'general', '{"variable": "valor"}', 1]);
            $templateId = $this->db->lastInsertId();
            
            if ($templateId) {
                echo "✓ PASSED (ID: {$templateId})\n";
            } else {
                echo "✗ FAILED - No se creó la plantilla de SMS\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener plantillas de email
     */
    public function testGetEmailTemplates() {
        echo "Test: Obtener plantillas de email... ";
        
        try {
            $sql = "SELECT * FROM email_templates ORDER BY name ASC";
            $templates = $this->db->fetchAll($sql);
            
            if (is_array($templates)) {
                echo "✓ PASSED (Total: " . count($templates) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron plantillas de email\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener plantillas de SMS
     */
    public function testGetSMSTemplates() {
        echo "Test: Obtener plantillas de SMS... ";
        
        try {
            $sql = "SELECT * FROM sms_templates ORDER BY name ASC";
            $templates = $this->db->fetchAll($sql);
            
            if (is_array($templates)) {
                echo "✓ PASSED (Total: " . count($templates) . ")\n";
            } else {
                echo "✗ FAILED - No se obtuvieron plantillas de SMS\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de email
     */
    public function testGetEmailStats() {
        echo "Test: Obtener estadísticas de email... ";
        
        try {
            $stats = [];
            
            // Total de emails
            $stats['total_emails'] = $this->db->fetch("SELECT COUNT(*) as total FROM notifications WHERE type = 'email'")['total'];
            
            // Emails por estado
            $stats['emails_by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count FROM notifications WHERE type = 'email' GROUP BY status ORDER BY count DESC"
            );
            
            if (isset($stats['total_emails']) && isset($stats['emails_by_status'])) {
                echo "✓ PASSED (Total: {$stats['total_emails']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de email\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de SMS
     */
    public function testGetSMSStats() {
        echo "Test: Obtener estadísticas de SMS... ";
        
        try {
            $stats = [];
            
            // Total de SMS
            $stats['total_sms'] = $this->db->fetch("SELECT COUNT(*) as total FROM notifications WHERE type = 'sms'")['total'];
            
            // SMS por estado
            $stats['sms_by_status'] = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count FROM notifications WHERE type = 'sms' GROUP BY status ORDER BY count DESC"
            );
            
            if (isset($stats['total_sms']) && isset($stats['sms_by_status'])) {
                echo "✓ PASSED (Total: {$stats['total_sms']})\n";
            } else {
                echo "✗ FAILED - No se obtuvieron estadísticas de SMS\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de notificación
     */
    public function testValidateNotificationData() {
        echo "Test: Validar datos de notificación... ";
        
        try {
            // Test datos válidos
            $validData = [
                'type' => 'email',
                'recipient' => 'test@example.com',
                'subject' => 'Test Subject',
                'message' => 'Test Message'
            ];
            
            $errors = $this->validateNotificationData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'type' => 'invalid',
                    'recipient' => 'invalid-email',
                    'subject' => '',
                    'message' => ''
                ];
                
                $errors = $this->validateNotificationData($invalidData);
                
                if (!empty($errors) && isset($errors['type']) && isset($errors['recipient']) && isset($errors['subject']) && isset($errors['message'])) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Validación de datos inválidos no funcionó\n";
                }
            } else {
                echo "✗ FAILED - Validación de datos válidos falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Limpiar datos de prueba
     */
    public function testCleanup() {
        echo "Test: Limpiar datos de prueba... ";
        
        try {
            // Eliminar notificaciones
            $this->db->query("DELETE FROM notifications WHERE notification_id LIKE 'NOT-TEST-%' OR notification_id LIKE 'EMAIL-TEST-%' OR notification_id LIKE 'SMS-TEST-%'");
            
            // Eliminar plantillas
            $this->db->query("DELETE FROM email_templates WHERE name LIKE '%Prueba%' OR name LIKE '%Nueva%'");
            $this->db->query("DELETE FROM sms_templates WHERE name LIKE '%Prueba%' OR name LIKE '%Nueva%'");
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de notificación (función auxiliar)
     */
    private function validateNotificationData($data) {
        $errors = [];

        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es requerido';
        } elseif (!in_array($data['type'], ['email', 'sms', 'push', 'system'])) {
            $errors['type'] = 'Tipo de notificación no válido';
        }

        // Validar destinatario
        if (empty($data['recipient'])) {
            $errors['recipient'] = 'El destinatario es requerido';
        } elseif ($data['type'] == 'email' && !filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
            $errors['recipient'] = 'El destinatario debe ser un email válido';
        }

        // Validar asunto
        if (empty($data['subject'])) {
            $errors['subject'] = 'El asunto es requerido';
        }

        // Validar mensaje
        if (empty($data['message'])) {
            $errors['message'] = 'El mensaje es requerido';
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new NotificationTest();
    $test->runAllTests();
}
