<?php
/**
 * Cargador de configuración — las credenciales van en config.local.php (no versionado).
 */

$localConfig = __DIR__ . '/config.local.php';

if (!is_readable($localConfig)) {
    $message = 'Falta includes/config.local.php. Copie includes/config.example.php y configure sus credenciales.';
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

require_once $localConfig;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('Error de conexión a base de datos: ' . $conn->connect_error);
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "Error de conexión a la base de datos.\n");
            exit(1);
        }
        http_response_code(503);
        exit('Error de conexión. Revise includes/config.local.php');
    }
    $conn->set_charset('utf8mb4');
}

require_once __DIR__ . '/functions.php';
