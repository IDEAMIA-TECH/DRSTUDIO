<?php
// Archivo de prueba para verificar rutas
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Test de rutas funcionando',
    'project_root' => dirname(__DIR__),
    'config_exists' => file_exists(dirname(__DIR__) . '/includes/config.php'),
    'auth_exists' => file_exists(dirname(__DIR__) . '/includes/auth.php'),
    'functions_exists' => file_exists(dirname(__DIR__) . '/includes/functions.php'),
    'server_config' => file_exists('/home/dtstudio/public_html/includes/config.php'),
    'current_dir' => __DIR__,
    'parent_dir' => dirname(__DIR__)
]);
?>
