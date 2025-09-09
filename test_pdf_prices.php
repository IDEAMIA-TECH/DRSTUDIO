<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Obtener cotización 3
$cotizacion = getRecord('cotizaciones', 3);
if (!$cotizacion) {
    echo "Cotización no encontrada\n";
    exit;
}

echo "Generando PDF para cotización: " . $cotizacion['numero_cotizacion'] . "\n";

// Incluir la función generatePDFForEmail
require_once 'ajax/cotizaciones.php';

$pdfPath = generatePDFForEmail($cotizacion);
if ($pdfPath && file_exists($pdfPath)) {
    echo "PDF generado exitosamente: $pdfPath\n";
    echo "Tamaño del archivo: " . filesize($pdfPath) . " bytes\n";
    
    // Limpiar archivo temporal
    unlink($pdfPath);
} else {
    echo "Error generando PDF\n";
}
?>
