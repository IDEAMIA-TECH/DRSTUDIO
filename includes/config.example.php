<?php
/**
 * Plantilla de configuración — copiar a config.local.php y completar valores reales.
 * cp includes/config.example.php includes/config.local.php
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'dtstudio_main');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');

define('SITE_URL', 'https://tu-dominio.com');
define('ADMIN_URL', SITE_URL . '/admin');

define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
