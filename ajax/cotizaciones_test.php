<?php
// Archivo de prueba para verificar eliminación sin autenticación
// Sistema de rutas robusto
function getProjectRoot() {
    $currentDir = __DIR__;
    $projectRoot = dirname($currentDir);
    return $projectRoot;
}

$projectRoot = getProjectRoot();
require_once $projectRoot . '/includes/config.php';
require_once $projectRoot . '/includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    
    // Obtener cotización
    $cotizacion = getRecord('cotizaciones', $id);
    if (!$cotizacion) {
        echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
        exit;
    }
    
    // Eliminar items de la cotización
    $conn->query("DELETE FROM cotizacion_items WHERE cotizacion_id = $id");
    
    // Eliminar cotización
    if (deleteRecord('cotizaciones', $id, false)) {
        echo json_encode(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la cotización']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
