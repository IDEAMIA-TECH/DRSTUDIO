<?php
// Configuración de correo electrónico
// Configuración SMTP para envío de correos

// Configuración del servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com'); // Cambiar por tu servidor SMTP
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu-email@dtstudio.com.mx'); // Cambiar por tu email
define('SMTP_PASSWORD', 'tu-password-app'); // Cambiar por tu contraseña de aplicación
define('SMTP_ENCRYPTION', 'tls');

// Configuración del remitente
define('FROM_EMAIL', 'cotizaciones@dtstudio.com.mx');
define('FROM_NAME', 'DT Studio');
define('ADMIN_EMAIL', 'admin@dtstudio.com.mx'); // Email del administrador

// Configuración de la empresa
define('COMPANY_NAME', 'DT Studio');
define('COMPANY_WEBSITE', 'https://dtstudio.com.mx');
define('COMPANY_PHONE', '(55) 1234-5678');
define('COMPANY_ADDRESS', 'Tu dirección aquí');

// URLs de la aplicación
define('BASE_URL', 'https://dtstudio.com.mx');
define('ACCEPT_QUOTE_URL', BASE_URL . '/aceptar-cotizacion.php');
?>
