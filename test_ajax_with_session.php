<?php
/**
 * Script para probar el AJAX de SKU con sesión simulada
 */

// Iniciar sesión
session_start();

// Simular un usuario logueado
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['rol'] = 'admin';

// Simular una petición POST
$_POST['categoria_id'] = '1';

// Capturar la salida
ob_start();

// Incluir el archivo AJAX
include 'ajax/generate_sku.php';

$output = ob_get_clean();

echo "Salida del archivo AJAX:\n";
echo $output . "\n";

// Decodificar JSON
$data = json_decode($output, true);

if ($data) {
    if (isset($data['success']) && $data['success']) {
        echo "✅ SKU generado correctamente: " . $data['sku'] . "\n";
    } else {
        echo "❌ Error: " . ($data['message'] ?? 'Error desconocido') . "\n";
    }
} else {
    echo "❌ Error decodificando JSON\n";
}
?>
