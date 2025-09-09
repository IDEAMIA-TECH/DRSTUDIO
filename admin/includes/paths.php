<?php
/**
 * Archivo para manejar las rutas del proyecto de manera centralizada
 */

// Detectar la ruta correcta del proyecto
function getProjectRoot() {
    // Probar diferentes rutas posibles
    $possibleRoots = [
        dirname(dirname(__DIR__)), // Desde admin/includes/
        dirname(__DIR__), // Desde admin/
        __DIR__, // Desde includes/
        '/home/dtstudio/public_html', // Ruta absoluta del servidor
        $_SERVER['DOCUMENT_ROOT'] . '/DRSTUDIO', // Desde document root
        $_SERVER['DOCUMENT_ROOT'] // Document root directo
    ];
    
    foreach ($possibleRoots as $root) {
        if (file_exists($root . '/includes/config.php')) {
            return $root;
        }
    }
    
    return null;
}

// Obtener la ruta del proyecto
$projectRoot = getProjectRoot();

if (!$projectRoot) {
    die('Error: No se pudo determinar la ruta del proyecto. Verifique que los archivos includes/config.php existan.');
}

// Definir rutas
define('PROJECT_ROOT', $projectRoot);
define('CONFIG_PATH', PROJECT_ROOT . '/includes/config.php');
define('AUTH_PATH', PROJECT_ROOT . '/includes/auth.php');
define('FUNCTIONS_PATH', PROJECT_ROOT . '/includes/functions.php');

// Incluir archivos principales
if (file_exists(CONFIG_PATH)) {
    require_once CONFIG_PATH;
} else {
    die('Error: No se pudo encontrar config.php en: ' . CONFIG_PATH);
}

if (file_exists(AUTH_PATH)) {
    require_once AUTH_PATH;
} else {
    die('Error: No se pudo encontrar auth.php en: ' . AUTH_PATH);
}

// Incluir functions.php si existe
if (file_exists(FUNCTIONS_PATH)) {
    require_once FUNCTIONS_PATH;
}
?>
