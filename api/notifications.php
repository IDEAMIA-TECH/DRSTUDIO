<?php
/**
 * API Endpoint para Sistema de Notificaciones - DT Studio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/NotificationController.php';

try {
    $controller = new NotificationController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    // Parsear la ruta
    $pathParts = explode('/', trim($path, '/'));
    $action = $pathParts[0] ?? 'list';
    $id = $pathParts[1] ?? null;
    $subAction = $pathParts[2] ?? null;
    
    switch ($action) {
        // ===== NOTIFICACIONES =====
        case 'create':
            $controller->create();
            break;
            
        case 'list':
            $controller->getAll();
            break;
            
        case 'get':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->getById($id);
            break;
            
        case 'notification-id':
            if (!$id) {
                throw new Exception('Notification ID requerido');
            }
            $controller->getByNotificationId($id);
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->delete($id);
            break;
            
        case 'mark-sent':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->markAsSent($id);
            break;
            
        case 'mark-failed':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->markAsFailed($id);
            break;
            
        case 'mark-delivered':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->markAsDelivered($id);
            break;
            
        case 'mark-read':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->markAsRead($id);
            break;
            
        case 'pending':
            $controller->getPending();
            break;
            
        case 'recipient':
            if (!$id) {
                throw new Exception('Destinatario requerido');
            }
            $controller->getByRecipient($id);
            break;
            
        case 'type':
            if (!$id) {
                throw new Exception('Tipo de notificación requerido');
            }
            $controller->getByType($id);
            break;
            
        case 'stats':
            $controller->getStats();
            break;
            
        case 'retry':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->retry($id);
            break;
            
        case 'schedule':
            if (!$id) {
                throw new Exception('ID de la notificación requerido');
            }
            $controller->schedule($id);
            break;
            
        // ===== EMAIL =====
        case 'email':
            if ($subAction === 'send') {
                $controller->sendEmail();
            } elseif ($subAction === 'send-template') {
                $controller->sendTemplateEmail();
            } elseif ($subAction === 'send-bulk') {
                $controller->sendBulkEmail();
            } elseif ($subAction === 'templates') {
                $controller->getEmailTemplates();
            } elseif ($subAction === 'create-template') {
                $controller->createEmailTemplate();
            } elseif ($subAction === 'smtp-config') {
                $controller->getSMTPConfig();
            } elseif ($subAction === 'update-smtp-config') {
                $controller->updateSMTPConfig();
            } elseif ($subAction === 'stats') {
                $controller->getEmailStats();
            } else {
                throw new Exception('Acción de email no válida');
            }
            break;
            
        // ===== SMS =====
        case 'sms':
            if ($subAction === 'send') {
                $controller->sendSMS();
            } elseif ($subAction === 'send-template') {
                $controller->sendTemplateSMS();
            } elseif ($subAction === 'send-bulk') {
                $controller->sendBulkSMS();
            } elseif ($subAction === 'templates') {
                $controller->getSMSTemplates();
            } elseif ($subAction === 'create-template') {
                $controller->createSMSTemplate();
            } elseif ($subAction === 'config') {
                $controller->getSMSConfig();
            } elseif ($subAction === 'update-config') {
                $controller->updateSMSConfig();
            } elseif ($subAction === 'stats') {
                $controller->getSMSStats();
            } elseif ($subAction === 'balance') {
                $controller->getSMSBalance();
            } elseif ($subAction === 'history') {
                if (!$id) {
                    throw new Exception('Número de teléfono requerido');
                }
                $controller->getSMSHistory($id);
            } else {
                throw new Exception('Acción de SMS no válida');
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado',
                'available_endpoints' => [
                    'NOTIFICACIONES' => [
                        'POST /api/notifications.php?path=create' => 'Crear notificación',
                        'GET /api/notifications.php?path=list' => 'Listar notificaciones',
                        'GET /api/notifications.php?path=get/{id}' => 'Obtener notificación por ID',
                        'GET /api/notifications.php?path=notification-id/{id}' => 'Obtener por notification_id',
                        'PUT /api/notifications.php?path=update/{id}' => 'Actualizar notificación',
                        'DELETE /api/notifications.php?path=delete/{id}' => 'Eliminar notificación',
                        'POST /api/notifications.php?path=mark-sent/{id}' => 'Marcar como enviada',
                        'POST /api/notifications.php?path=mark-failed/{id}' => 'Marcar como fallida',
                        'POST /api/notifications.php?path=mark-delivered/{id}' => 'Marcar como entregada',
                        'POST /api/notifications.php?path=mark-read/{id}' => 'Marcar como leída',
                        'GET /api/notifications.php?path=pending' => 'Notificaciones pendientes',
                        'GET /api/notifications.php?path=recipient/{recipient}' => 'Por destinatario',
                        'GET /api/notifications.php?path=type/{type}' => 'Por tipo',
                        'GET /api/notifications.php?path=stats' => 'Estadísticas',
                        'POST /api/notifications.php?path=retry/{id}' => 'Reintentar',
                        'POST /api/notifications.php?path=schedule/{id}' => 'Programar'
                    ],
                    'EMAIL' => [
                        'POST /api/notifications.php?path=email/send' => 'Enviar email',
                        'POST /api/notifications.php?path=email/send-template' => 'Enviar email con plantilla',
                        'POST /api/notifications.php?path=email/send-bulk' => 'Enviar email masivo',
                        'GET /api/notifications.php?path=email/templates' => 'Plantillas de email',
                        'POST /api/notifications.php?path=email/create-template' => 'Crear plantilla de email',
                        'GET /api/notifications.php?path=email/smtp-config' => 'Configuración SMTP',
                        'POST /api/notifications.php?path=email/update-smtp-config' => 'Actualizar SMTP',
                        'GET /api/notifications.php?path=email/stats' => 'Estadísticas de email'
                    ],
                    'SMS' => [
                        'POST /api/notifications.php?path=sms/send' => 'Enviar SMS',
                        'POST /api/notifications.php?path=sms/send-template' => 'Enviar SMS con plantilla',
                        'POST /api/notifications.php?path=sms/send-bulk' => 'Enviar SMS masivo',
                        'GET /api/notifications.php?path=sms/templates' => 'Plantillas de SMS',
                        'POST /api/notifications.php?path=sms/create-template' => 'Crear plantilla de SMS',
                        'GET /api/notifications.php?path=sms/config' => 'Configuración SMS',
                        'POST /api/notifications.php?path=sms/update-config' => 'Actualizar configuración SMS',
                        'GET /api/notifications.php?path=sms/stats' => 'Estadísticas de SMS',
                        'GET /api/notifications.php?path=sms/balance' => 'Balance de SMS',
                        'GET /api/notifications.php?path=sms/history/{phone}' => 'Historial de SMS'
                    ]
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
