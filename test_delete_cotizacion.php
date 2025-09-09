<?php
// Archivo de prueba para verificar la eliminación de cotizaciones
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Simular datos POST
$_POST['action'] = 'delete';
$_POST['id'] = '2'; // ID de prueba

// Incluir el archivo AJAX
ob_start();
include 'ajax/cotizaciones.php';
$output = ob_get_clean();

echo "Respuesta del servidor:\n";
echo $output;
echo "\n\n";

// Verificar si la cotización existe
$cotizacion = getRecord('cotizaciones', 2);
if ($cotizacion) {
    echo "La cotización con ID 2 aún existe:\n";
    print_r($cotizacion);
} else {
    echo "La cotización con ID 2 fue eliminada correctamente.\n";
}
?>
