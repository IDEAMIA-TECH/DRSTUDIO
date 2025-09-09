<?php
// Prueba simple de eliminación
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificar si existe una cotización con ID 2
$cotizacion = getRecord('cotizaciones', 2);
if ($cotizacion) {
    echo "Cotización encontrada:\n";
    echo "ID: " . $cotizacion['id'] . "\n";
    echo "Número: " . $cotizacion['numero_cotizacion'] . "\n";
    echo "Cliente ID: " . $cotizacion['cliente_id'] . "\n";
    
    // Intentar eliminar
    echo "\nIntentando eliminar...\n";
    $result = deleteRecord('cotizaciones', 2, false);
    
    if ($result) {
        echo "✅ Eliminación exitosa\n";
        
        // Verificar que se eliminó
        $cotizacion_after = getRecord('cotizaciones', 2);
        if (!$cotizacion_after) {
            echo "✅ Confirmado: La cotización fue eliminada\n";
        } else {
            echo "❌ Error: La cotización aún existe\n";
        }
    } else {
        echo "❌ Error en la eliminación\n";
    }
} else {
    echo "No se encontró cotización con ID 2\n";
}
?>
