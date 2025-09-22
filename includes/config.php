<?php
// Configuración de la base de datos
define('DB_HOST', '173.231.22.109');
define('DB_NAME', 'dtstudio_main');
define('DB_USER', 'dtstudio_main');
define('DB_PASS', 'm&9!9ejG!5D6A$p&');

// Configuración del sitio
define('SITE_URL', 'http://localhost/DRSTUDIO');
define('ADMIN_URL', SITE_URL . '/admin');

// Configuración de archivos
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset UTF-8 completo
$conn->set_charset("utf8mb4");

// Incluir funciones comunes
require_once 'functions.php';
?>
