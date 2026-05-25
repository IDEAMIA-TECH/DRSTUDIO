<?php
/**
 * Plantilla SMTP — copiar a email_config.local.php
 * cp includes/email_config.example.php includes/email_config.local.php
 */

define('SMTP_HOST', 'mail.tu-dominio.com');
define('SMTP_PORT', '465');
define('SMTP_USERNAME', 'cotizaciones@tu-dominio.com');
define('SMTP_PASSWORD', 'tu_contraseña_smtp');
define('SMTP_SECURE', 'ssl');

define('FROM_EMAIL', 'cotizaciones@tu-dominio.com');
define('FROM_NAME', 'DT Studio');
define('ADMIN_EMAIL', 'cotizaciones@tu-dominio.com');
define('BASE_URL', 'https://tu-dominio.com');
define('COMPANY_PHONE', '+52 (000) 000-0000');
define('COMPANY_WEBSITE', 'https://tu-dominio.com');
