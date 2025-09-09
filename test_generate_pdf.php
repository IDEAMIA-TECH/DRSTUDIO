<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

error_log('Test Generate PDF - Iniciando prueba');

try {
    // Obtener una cotización de prueba
    $cotizacion = getRecord('cotizaciones', 1);
    if (!$cotizacion) {
        echo json_encode(['success' => false, 'message' => 'No hay cotizaciones en la base de datos']);
        exit;
    }
    
    error_log('Test Generate PDF - Cotización obtenida: ' . $cotizacion['numero_cotizacion']);
    
    // Incluir la función generatePDFForEmail
    require_once 'ajax/cotizaciones.php';
    
    error_log('Test Generate PDF - Generando PDF');
    $pdfPath = generatePDFForEmail($cotizacion);
    
    if ($pdfPath && file_exists($pdfPath)) {
        error_log('Test Generate PDF - PDF generado exitosamente: ' . $pdfPath);
        unlink($pdfPath); // Limpiar archivo temporal
        echo json_encode(['success' => true, 'message' => 'PDF generado correctamente']);
    } else {
        error_log('Test Generate PDF - Error generando PDF');
        echo json_encode(['success' => false, 'message' => 'Error generando PDF']);
    }
    
} catch (Exception $e) {
    error_log('Test Generate PDF - Error: ' . $e->getMessage());
    error_log('Test Generate PDF - Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
