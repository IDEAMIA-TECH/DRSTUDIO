<?php
// Sistema de rutas robusto
function getProjectRoot() {
    $currentDir = __DIR__;
    $projectRoot = dirname($currentDir);
    return $projectRoot;
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Simular datos POST
$_POST['action'] = 'change_status';
$_POST['id'] = '1';
$_POST['estado'] = 'enviada';

error_log('Test Change Status - Iniciando prueba');

$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id = (int)$_POST['id'];
    $estado = $_POST['estado'];
    
    error_log("Test Change Status - Cambiando estado: ID=$id, Estado=$estado");
    
    $estadosValidos = ['pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada', 'en_espera_deposito'];
    if (!in_array($estado, $estadosValidos)) {
        error_log("Test Change Status - Estado no válido: $estado");
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        exit;
    }
    
    $data = ['estado' => $estado];
    error_log("Test Change Status - Datos para actualizar: " . print_r($data, true));
    
    if (updateRecord('cotizaciones', $data, $id)) {
        error_log("Test Change Status - Estado actualizado exitosamente");
        echo json_encode(['success' => true, 'message' => "Cotización marcada como $estado exitosamente"]);
    } else {
        error_log("Test Change Status - Error actualizando estado en base de datos");
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
