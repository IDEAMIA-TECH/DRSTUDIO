<?php
/**
 * Carga config, auth y functions para endpoints ajax/ (sin rutas hardcodeadas del servidor).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/includes/config.php';
$authPath = $projectRoot . '/includes/auth.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    die('Error: No se pudo encontrar la configuración del proyecto.');
}

require_once $configPath;

if (file_exists($authPath)) {
    require_once $authPath;
}
