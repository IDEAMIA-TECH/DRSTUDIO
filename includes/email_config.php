<?php
/**
 * Cargador de configuración de correo — credenciales en email_config.local.php (no versionado).
 */

$localEmailConfig = __DIR__ . '/email_config.local.php';

if (is_readable($localEmailConfig)) {
    require_once $localEmailConfig;
    return;
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', '');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', '465');
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', '');
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', '');
}
if (!defined('SMTP_SECURE')) {
    define('SMTP_SECURE', 'ssl');
}
if (!defined('FROM_EMAIL')) {
    define('FROM_EMAIL', '');
}
if (!defined('FROM_NAME')) {
    define('FROM_NAME', 'DT Studio');
}
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', '');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
if (!defined('COMPANY_PHONE')) {
    define('COMPANY_PHONE', '');
}
if (!defined('COMPANY_WEBSITE')) {
    define('COMPANY_WEBSITE', '');
}
