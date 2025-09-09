<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_ENCRYPTION;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            
            // Configuración del remitente
            $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
            
        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
            throw new Exception("Error configurando el servidor de correo");
        }
    }
    
    public function sendQuoteEmail($cotizacion, $cliente, $pdfPath = null) {
        try {
            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Agregar destinatario principal (cliente)
            $this->mailer->addAddress($cliente['email'], $cliente['nombre']);
            
            // Agregar copia al administrador
            $this->mailer->addCC(ADMIN_EMAIL, 'Administrador DT Studio');
            
            // Configurar asunto
            $this->mailer->Subject = 'Cotización ' . $cotizacion['numero_cotizacion'] . ' - DT Studio';
            
            // Generar contenido HTML del correo
            $htmlContent = $this->generateQuoteEmailHTML($cotizacion, $cliente);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlContent;
            
            // Agregar PDF como adjunto si existe
            if ($pdfPath && file_exists($pdfPath)) {
                $this->mailer->addAttachment($pdfPath, 'Cotizacion_' . $cotizacion['numero_cotizacion'] . '.pdf');
            }
            
            // Enviar correo
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Correo enviado exitosamente a: " . $cliente['email']);
                return true;
            } else {
                error_log("Error enviando correo a: " . $cliente['email']);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error enviando correo: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateQuoteEmailHTML($cotizacion, $cliente) {
        $acceptUrl = ACCEPT_QUOTE_URL . '?token=' . $this->generateAcceptToken($cotizacion['id']);
        
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cotización ' . $cotizacion['numero_cotizacion'] . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #7B3F9F;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 30px;
                    border-radius: 0 0 8px 8px;
                }
                .quote-info {
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 4px solid #7B3F9F;
                }
                .total {
                    background-color: #7B3F9F;
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                    font-size: 18px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .accept-button {
                    display: inline-block;
                    background-color: #28a745;
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    text-align: center;
                    margin: 20px 0;
                    font-size: 16px;
                }
                .accept-button:hover {
                    background-color: #218838;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .items-table th, .items-table td {
                    border: 1px solid #ddd;
                    padding: 10px;
                    text-align: left;
                }
                .items-table th {
                    background-color: #7B3F9F;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>DT Studio</h1>
                <p>Cotización de Productos Promocionales</p>
            </div>
            
            <div class="content">
                <h2>Estimado/a ' . htmlspecialchars($cliente['nombre']) . ',</h2>
                
                <p>Esperamos que se encuentre muy bien. Adjunto encontrará la cotización solicitada con todos los detalles de los productos y servicios que ofrecemos.</p>
                
                <div class="quote-info">
                    <h3>Información de la Cotización</h3>
                    <p><strong>Número:</strong> ' . htmlspecialchars($cotizacion['numero_cotizacion']) . '</p>
                    <p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($cotizacion['created_at'])) . '</p>
                    <p><strong>Total:</strong> $' . number_format($cotizacion['total'], 2) . '</p>
                    ' . ($cotizacion['fecha_vencimiento'] ? '<p><strong>Válida hasta:</strong> ' . date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) . '</p>' : '') . '
                </div>
                
                <p>Esta cotización incluye todos los productos solicitados con sus especificaciones técnicas, precios y condiciones de entrega.</p>
                
                <div style="text-align: center;">
                    <a href="' . $acceptUrl . '" class="accept-button">
                        ✅ ACEPTAR COTIZACIÓN
                    </a>
                </div>
                
                <p>Al hacer clic en el botón de arriba, confirmará su aceptación de esta cotización y procederemos con los siguientes pasos para la producción de sus productos promocionales.</p>
                
                <p>Si tiene alguna pregunta o necesita modificar algún aspecto de la cotización, no dude en contactarnos.</p>
                
                <div class="footer">
                    <p><strong>DT Studio</strong></p>
                    <p>Tel: ' . COMPANY_PHONE . ' | Email: ' . FROM_EMAIL . '</p>
                    <p>Web: ' . COMPANY_WEBSITE . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function generateAcceptToken($cotizacionId) {
        // Generar token único para aceptar la cotización
        $data = $cotizacionId . '_' . time() . '_' . rand(1000, 9999);
        return base64_encode($data);
    }
    
    public function validateAcceptToken($token) {
        try {
            $decoded = base64_decode($token);
            $parts = explode('_', $decoded);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            $cotizacionId = (int)$parts[0];
            $timestamp = (int)$parts[1];
            
            // Verificar que el token no sea muy antiguo (7 días)
            if (time() - $timestamp > 7 * 24 * 60 * 60) {
                return false;
            }
            
            return $cotizacionId;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function sendAcceptanceConfirmation($cotizacion, $cliente) {
        try {
            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Agregar destinatario principal (cliente)
            $this->mailer->addAddress($cliente['email'], $cliente['nombre']);
            
            // Agregar copia al administrador
            $this->mailer->addCC(ADMIN_EMAIL, 'Administrador DT Studio');
            
            // Configurar asunto
            $this->mailer->Subject = 'Cotización Aceptada - ' . $cotizacion['numero_cotizacion'] . ' - DT Studio';
            
            // Generar contenido HTML del correo de confirmación
            $htmlContent = $this->generateAcceptanceEmailHTML($cotizacion, $cliente);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlContent;
            
            // Enviar correo
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Confirmación de aceptación enviada exitosamente a: " . $cliente['email']);
                return true;
            } else {
                error_log("Error enviando confirmación de aceptación a: " . $cliente['email']);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error enviando confirmación de aceptación: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateAcceptanceEmailHTML($cotizacion, $cliente) {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cotización Aceptada - ' . $cotizacion['numero_cotizacion'] . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #28a745;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 30px;
                    border-radius: 0 0 8px 8px;
                }
                .success-box {
                    background-color: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    text-align: center;
                }
                .next-steps {
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 4px solid #28a745;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>✅ Cotización Aceptada</h1>
                <p>DT Studio - Productos Promocionales</p>
            </div>
            
            <div class="content">
                <h2>¡Gracias por confiar en nosotros!</h2>
                
                <div class="success-box">
                    <h3>Su cotización ha sido aceptada exitosamente</h3>
                    <p><strong>Número de Cotización:</strong> ' . htmlspecialchars($cotizacion['numero_cotizacion']) . '</p>
                    <p><strong>Total:</strong> $' . number_format($cotizacion['total'], 2) . '</p>
                </div>
                
                <p>Estimado/a <strong>' . htmlspecialchars($cliente['nombre']) . '</strong>,</p>
                
                <p>Hemos recibido su aceptación de la cotización y nos complace confirmar que procederemos con la producción de sus productos promocionales.</p>
                
                <div class="next-steps">
                    <h3>Próximos Pasos:</h3>
                    <ol>
                        <li><strong>Coordinación de Pago:</strong> Nuestro equipo se pondrá en contacto con usted para coordinar el pago del 50% de anticipo.</li>
                        <li><strong>Inicio de Producción:</strong> Una vez confirmado el pago, iniciaremos la producción de sus productos.</li>
                        <li><strong>Seguimiento:</strong> Le mantendremos informado sobre el progreso de su pedido.</li>
                        <li><strong>Entrega:</strong> Coordinaremos la entrega según lo acordado en la cotización.</li>
                    </ol>
                </div>
                
                <p>Si tiene alguna pregunta o necesita realizar algún cambio, no dude en contactarnos. Estamos aquí para ayudarle.</p>
                
                <div class="footer">
                    <p><strong>DT Studio</strong></p>
                    <p>Tel: ' . COMPANY_PHONE . ' | Email: ' . FROM_EMAIL . '</p>
                    <p>Web: ' . COMPANY_WEBSITE . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
?>
