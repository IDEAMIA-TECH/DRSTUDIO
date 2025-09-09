<?php
/**
 * Controlador NotificationController - DT Studio
 * Manejo de peticiones para el sistema de notificaciones
 */

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/EmailService.php';
require_once __DIR__ . '/../models/SMSService.php';

class NotificationController {
    private $notificationModel;
    private $emailService;
    private $smsService;

    public function __construct() {
        $this->notificationModel = new Notification();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
    }

    /**
     * Crear notificación
     */
    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->notificationModel->validate($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $notificationId = $this->notificationModel->create($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación creada exitosamente',
                'data' => ['notification_id' => $notificationId]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificación por ID
     */
    public function getById($id) {
        try {
            $notification = $this->notificationModel->getById($id);
            
            if (!$notification) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $notification
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificación por notification_id
     */
    public function getByNotificationId($notificationId) {
        try {
            $notification = $this->notificationModel->getByNotificationId($notificationId);
            
            if (!$notification) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $notification
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar notificaciones
     */
    public function getAll() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            
            $filters = [
                'type' => $_GET['type'] ?? null,
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'recipient' => $_GET['recipient'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $result = $this->notificationModel->getAll($filters, $page, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar notificación
     */
    public function update($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->notificationModel->update($id, $input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar notificación
     */
    public function delete($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Método no permitido');
            }
            
            $this->notificationModel->delete($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar como enviada
     */
    public function markAsSent($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $sentAt = $input['sent_at'] ?? null;
            
            $this->notificationModel->markAsSent($id, $sentAt);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como enviada'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar como fallida
     */
    public function markAsFailed($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $errorMessage = $input['error_message'] ?? '';
            
            $this->notificationModel->markAsFailed($id, $errorMessage);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como fallida'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar como entregada
     */
    public function markAsDelivered($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $this->notificationModel->markAsDelivered($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como entregada'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar como leída
     */
    public function markAsRead($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $this->notificationModel->markAsRead($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificaciones pendientes
     */
    public function getPending() {
        try {
            $limit = $_GET['limit'] ?? 50;
            $notifications = $this->notificationModel->getPending($limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificaciones por destinatario
     */
    public function getByRecipient($recipient) {
        try {
            $type = $_GET['type'] ?? null;
            $limit = $_GET['limit'] ?? 20;
            
            $notifications = $this->notificationModel->getByRecipient($recipient, $type, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificaciones por tipo
     */
    public function getByType($type) {
        try {
            $limit = $_GET['limit'] ?? 20;
            $notifications = $this->notificationModel->getByType($type, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getStats() {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $stats = $this->notificationModel->getStats($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reintentar notificación
     */
    public function retry($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $this->notificationModel->retry($id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación programada para reintento'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Programar notificación
     */
    public function schedule($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $scheduledAt = $input['scheduled_at'] ?? null;
            
            if (!$scheduledAt) {
                throw new Exception('Fecha programada requerida');
            }
            
            $this->notificationModel->schedule($id, $scheduledAt);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Notificación programada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA EMAIL =====

    /**
     * Enviar email
     */
    public function sendEmail() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->emailService->validateEmailData($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $result = $this->emailService->sendEmail(
                $input['to'],
                $input['subject'],
                $input['message'],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar email con plantilla
     */
    public function sendTemplateEmail() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['to']) || empty($input['template_id'])) {
                throw new Exception('Destinatario y plantilla son requeridos');
            }
            
            $result = $this->emailService->sendTemplateEmail(
                $input['to'],
                $input['template_id'],
                $input['data'] ?? [],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar email masivo
     */
    public function sendBulkEmail() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['recipients']) || !is_array($input['recipients'])) {
                throw new Exception('Lista de destinatarios requerida');
            }
            
            $result = $this->emailService->sendBulkEmail(
                $input['recipients'],
                $input['subject'],
                $input['message'],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener plantillas de email
     */
    public function getEmailTemplates() {
        try {
            $filters = [
                'category' => $_GET['category'] ?? null,
                'is_active' => $_GET['is_active'] ?? null
            ];
            
            $templates = $this->emailService->getTemplates($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear plantilla de email
     */
    public function createEmailTemplate() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $templateId = $this->emailService->createTemplate($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Plantilla creada exitosamente',
                'data' => ['template_id' => $templateId]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuración SMTP
     */
    public function getSMTPConfig() {
        try {
            $config = $this->emailService->getSMTPConfig();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $config
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración SMTP
     */
    public function updateSMTPConfig() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->emailService->updateSMTPConfig($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración SMTP actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de email
     */
    public function getEmailStats() {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $stats = $this->emailService->getEmailStats($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== MÉTODOS PARA SMS =====

    /**
     * Enviar SMS
     */
    public function sendSMS() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar datos
            $errors = $this->smsService->validateSMSData($input);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $errors
                ]);
                return;
            }
            
            $result = $this->smsService->sendSMS(
                $input['to'],
                $input['message'],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar SMS con plantilla
     */
    public function sendTemplateSMS() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['to']) || empty($input['template_id'])) {
                throw new Exception('Destinatario y plantilla son requeridos');
            }
            
            $result = $this->smsService->sendTemplateSMS(
                $input['to'],
                $input['template_id'],
                $input['data'] ?? [],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar SMS masivo
     */
    public function sendBulkSMS() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            if (empty($input['recipients']) || !is_array($input['recipients'])) {
                throw new Exception('Lista de destinatarios requerida');
            }
            
            $result = $this->smsService->sendBulkSMS(
                $input['recipients'],
                $input['message'],
                $input
            );
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener plantillas de SMS
     */
    public function getSMSTemplates() {
        try {
            $filters = [
                'category' => $_GET['category'] ?? null,
                'is_active' => $_GET['is_active'] ?? null
            ];
            
            $templates = $this->smsService->getTemplates($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear plantilla de SMS
     */
    public function createSMSTemplate() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $templateId = $this->smsService->createTemplate($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Plantilla creada exitosamente',
                'data' => ['template_id' => $templateId]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener configuración SMS
     */
    public function getSMSConfig() {
        try {
            $config = $this->smsService->getSMSConfig();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $config
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración SMS
     */
    public function updateSMSConfig() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $this->smsService->updateSMSConfig($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Configuración SMS actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de SMS
     */
    public function getSMSStats() {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $stats = $this->smsService->getSMSStats($filters);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener balance de SMS
     */
    public function getSMSBalance() {
        try {
            $balance = $this->smsService->getSMSBalance();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $balance
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener historial de SMS
     */
    public function getSMSHistory($phoneNumber) {
        try {
            $limit = $_GET['limit'] ?? 50;
            $history = $this->smsService->getSMSHistory($phoneNumber, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $history
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
