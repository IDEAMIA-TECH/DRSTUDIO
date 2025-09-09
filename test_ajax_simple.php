<?php
// Archivo de prueba simple para verificar AJAX
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'AJAX funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
]);
?>