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
}
?>
