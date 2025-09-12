<?php
/**
 * Simple Email Sender usando la función mail() de PHP
 * Más confiable que SMTP en servidores compartidos
 */
class SimpleEmailSender {
    
    public function sendEmail($to, $subject, $message, $isHTML = true) {
        try {
            error_log("SimpleEmailSender - Enviando email a: $to");
            error_log("SimpleEmailSender - Asunto: $subject");
            
            // Verificar que el destinatario tenga email
            if (empty($to)) {
                error_log("SimpleEmailSender - Error: No se especificó destinatario");
                return false;
            }
            
            // Configurar headers
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: " . ($isHTML ? "text/html" : "text/plain") . "; charset=UTF-8";
            $headers[] = "From: DT Studio <noreply@dtstudio.com.mx>";
            $headers[] = "Reply-To: cotizaciones@dtstudio.com.mx";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            $headerString = implode("\r\n", $headers);
            
            // Enviar email
            $result = mail($to, $subject, $message, $headerString);
            
            if ($result) {
                error_log("SimpleEmailSender - Email enviado exitosamente a: $to");
                return true;
            } else {
                error_log("SimpleEmailSender - Error enviando email a: $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SimpleEmailSender - Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendQuoteEmail($cotizacionData, $pdfPath = null) {
        try {
            error_log("SimpleEmailSender - Enviando correo de cotización");
            
            // Verificar que el cliente tenga email
            if (empty($cotizacionData['cliente']['email'])) {
                error_log("SimpleEmailSender - Error: Cliente no tiene email");
                return false;
            }
            
            $cliente = $cotizacionData['cliente'];
            $cotizacion = $cotizacionData;
            
            // Generar token de aceptación
            $acceptToken = $this->generateAcceptToken($cotizacionData['numero']);
            $this->saveAcceptToken($cotizacionData['numero'], $acceptToken);
            
            // Generar contenido HTML del correo con botón de aceptación
            $htmlContent = $this->generateQuoteEmailHTML($cotizacionData, $acceptToken);
            
            // Configurar asunto
            $subject = 'Cotización ' . $cotizacion['numero'] . ' - DT Studio';
            
            // Enviar correo con PDF adjunto
            $result = $this->sendEmailWithAttachment($cliente['email'], $subject, $htmlContent, $pdfPath);
            
            if ($result) {
                error_log("SimpleEmailSender - Correo de cotización enviado exitosamente a: " . $cliente['email']);
                
                // Enviar copia a administradores
                $this->sendAdminNotification($cotizacionData);
                
                return true;
            } else {
                error_log("SimpleEmailSender - Error enviando correo de cotización");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SimpleEmailSender - Error en sendQuoteEmail: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendEmailWithAttachment($to, $subject, $message, $pdfPath = null) {
        try {
            error_log("SimpleEmailSender - Enviando email con adjunto a: $to");
            
            // Verificar que el destinatario tenga email
            if (empty($to)) {
                error_log("SimpleEmailSender - Error: No se especificó destinatario");
                return false;
            }
            
            // Configurar headers para email con adjunto
            $boundary = md5(uniqid(time()));
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
            $headers[] = "From: DT Studio <noreply@dtstudio.com.mx>";
            $headers[] = "Reply-To: cotizaciones@dtstudio.com.mx";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            // Construir el cuerpo del mensaje
            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $message . "\r\n";
            
            // Agregar PDF como adjunto si existe
            if ($pdfPath && file_exists($pdfPath)) {
                $filename = basename($pdfPath);
                $fileContent = file_get_contents($pdfPath);
                $fileContentEncoded = base64_encode($fileContent);
                
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: application/pdf; name=\"$filename\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
                $body .= $fileContentEncoded . "\r\n";
                
                error_log("SimpleEmailSender - PDF adjunto: $filename (" . strlen($fileContent) . " bytes)");
            }
            
            $body .= "--$boundary--\r\n";
            
            $headerString = implode("\r\n", $headers);
            
            // Enviar email
            $result = mail($to, $subject, $body, $headerString);
            
            if ($result) {
                error_log("SimpleEmailSender - Email con adjunto enviado exitosamente a: $to");
                return true;
            } else {
                error_log("SimpleEmailSender - Error enviando email con adjunto a: $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SimpleEmailSender - Error en sendEmailWithAttachment: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateAcceptToken($numeroCotizacion) {
        // Generar token único para aceptar la cotización
        $data = $numeroCotizacion . '_' . time() . '_' . rand(1000, 9999);
        return base64_encode($data);
    }
    
    private function saveAcceptToken($numeroCotizacion, $token) {
        // Guardar token en la base de datos
        global $conn;
        $stmt = $conn->prepare("UPDATE cotizaciones SET token_aceptacion = ? WHERE numero_cotizacion = ?");
        $stmt->bind_param("ss", $token, $numeroCotizacion);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            error_log("SimpleEmailSender - Token guardado exitosamente para cotización $numeroCotizacion");
        } else {
            error_log("SimpleEmailSender - Error guardando token para cotización $numeroCotizacion");
        }
        
        return $result;
    }
    
    private function generateQuoteEmailHTML($data, $acceptToken = null) {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cotización ' . $data['numero'] . '</title>
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
                <h1>DT Studio</h1>
                <p>Cotización de Productos Promocionales</p>
            </div>
            
            <div class="content">
                <h2>Estimado/a ' . htmlspecialchars($data['cliente']['nombre']) . ',</h2>
                
                <p>Esperamos que se encuentre muy bien. Adjunto encontrará la cotización solicitada con todos los detalles de los productos y servicios que ofrecemos.</p>
                
                <div class="quote-info">
                    <h3>Información de la Cotización</h3>
                    <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                    <p><strong>Fecha:</strong> ' . htmlspecialchars($data['fecha']) . '</p>
                    <p><strong>Total:</strong> $' . number_format($data['total'], 2) . '</p>
                </div>
                
                <h3>Productos Cotizados</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Talla</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $contador = 1;
        
        // Mostrar productos del catálogo
        foreach ($data['items'] as $item) {
            $varianteText = '';
            if (isset($item['variante']) && $item['variante']) {
                $varianteParts = array_filter([
                    $item['variante']['talla'] ?? '',
                    $item['variante']['color'] ?? '',
                    $item['variante']['material'] ?? ''
                ]);
                $varianteText = implode(' - ', $varianteParts);
            }
            
            $html .= '
                        <tr>
                            <td><span style="background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">CAT</span></td>
                            <td>
                                <strong>' . htmlspecialchars($item['producto']['nombre']) . '</strong><br>
                                <small>SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small>
                            </td>
                            <td>' . htmlspecialchars($varianteText ?: 'Sin variante') . '</td>
                            <td>' . $item['cantidad'] . '</td>
                            <td>$' . number_format($item['precio_unitario'], 2) . '</td>
                            <td>$' . number_format($item['subtotal'], 2) . '</td>
                        </tr>';
        }
        
        // Mostrar productos personalizados
        if (isset($data['productos_personalizados']) && !empty($data['productos_personalizados'])) {
            foreach ($data['productos_personalizados'] as $producto) {
                $html .= '
                        <tr>
                            <td><span style="background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">PER</span></td>
                            <td>
                                <strong>' . htmlspecialchars($producto['nombre_producto']) . '</strong><br>
                                <small>Producto personalizado</small>
                            </td>
                            <td>' . htmlspecialchars($producto['talla'] ?: 'Sin talla') . '</td>
                            <td>' . $producto['cantidad'] . '</td>
                            <td>$' . number_format($producto['precio_venta'], 2) . '</td>
                            <td>$' . number_format($producto['subtotal'], 2) . '</td>
                        </tr>';
            }
        }
        
        $html .= '
                    </tbody>
                </table>
                
                <div class="total">
                    <p>Total: $' . number_format($data['total'], 2) . '</p>
                </div>
                
                <p>Esta cotización incluye todos los productos solicitados con sus especificaciones técnicas, precios y condiciones de entrega.</p>';
        
        // Agregar botón de aceptación si hay token
        if ($acceptToken) {
            $baseUrl = 'https://dtstudio.com.mx';
            $acceptUrl = $baseUrl . '/aceptar-cotizacion.php?token=' . $acceptToken;
            
            $html .= '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $acceptUrl . '" style="display: inline-block; background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                        ✅ ACEPTAR COTIZACIÓN
                    </a>
                </div>
                
                <p style="text-align: center; color: #666; font-size: 14px;">
                    Al hacer clic en el botón de arriba, confirmará su aceptación de esta cotización y procederemos con los siguientes pasos para la producción de sus productos promocionales.
                </p>';
        }
        
        $html .= '
                <p>Si tiene alguna pregunta o necesita modificar algún aspecto de la cotización, no dude en contactarnos.</p>
                
                <div class="footer">
                    <p><strong>DT Studio</strong></p>
                    <p>Productos Promocionales y Merchandising</p>
                    <p>Email: cotizaciones@dtstudio.com.mx</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function sendAdminNotification($cotizacionData) {
        try {
            // Obtener usuarios administradores
            $adminUsers = $this->getAdminUsers();
            
            if (empty($adminUsers)) {
                error_log("SimpleEmailSender - No hay usuarios administradores para notificar");
                return false;
            }
            
            $subject = 'Nueva Cotización Enviada - ' . $cotizacionData['numero'];
            $message = $this->generateAdminNotificationHTML($cotizacionData);
            
            foreach ($adminUsers as $admin) {
                $this->sendEmail($admin['email'], $subject, $message, true);
            }
            
            error_log("SimpleEmailSender - Notificaciones enviadas a " . count($adminUsers) . " administradores");
            return true;
            
        } catch (Exception $e) {
            error_log("SimpleEmailSender - Error enviando notificación a administradores: " . $e->getMessage());
            return false;
        }
    }
    
    private function getAdminUsers() {
        global $conn;
        
        try {
            $sql = "SELECT username as nombre, email FROM usuarios WHERE rol = 'admin' AND email IS NOT NULL AND email != ''";
            $result = $conn->query($sql);
            
            $admins = [];
            while ($row = $result->fetch_assoc()) {
                $admins[] = $row;
            }
            
            return $admins;
            
        } catch (Exception $e) {
            error_log("SimpleEmailSender - Error obteniendo usuarios administradores: " . $e->getMessage());
            return [];
        }
    }
    
    private function generateAdminNotificationHTML($data) {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Notificación de Cotización Enviada</title>
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
                .info-box {
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 4px solid #28a745;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📧 Cotización Enviada</h1>
                <p>DT Studio - Sistema de Notificaciones</p>
            </div>
            
            <div class="content">
                <h2>Se ha enviado una cotización al cliente</h2>
                
                <div class="info-box">
                    <h3>Detalles de la Cotización</h3>
                    <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                    <p><strong>Cliente:</strong> ' . htmlspecialchars($data['cliente']['nombre']) . '</p>
                    <p><strong>Email:</strong> ' . htmlspecialchars($data['cliente']['email']) . '</p>
                    <p><strong>Total:</strong> $' . number_format($data['total'], 2) . '</p>
                    <p><strong>Fecha de envío:</strong> ' . date('d/m/Y H:i') . '</p>
                </div>
                
                <p>La cotización ha sido enviada exitosamente al cliente y está esperando su respuesta.</p>
                
                <p>Puede revisar el estado de la cotización en el panel de administración.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
?>
