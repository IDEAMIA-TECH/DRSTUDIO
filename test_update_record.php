<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Simular datos de prueba
$data = ['estado' => 'enviada'];
$id = 1; // ID de prueba

error_log("Test - Iniciando prueba de updateRecord");
error_log("Test - Datos: " . print_r($data, true));
error_log("Test - ID: $id");

$result = updateRecord('cotizaciones', $data, $id);

if ($result) {
    error_log("Test - updateRecord exitoso");
    echo json_encode(['success' => true, 'message' => 'Test exitoso']);
} else {
    error_log("Test - updateRecord falló");
    echo json_encode(['success' => false, 'message' => 'Test falló']);
}
?>
