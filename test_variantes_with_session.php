<?php
/**
 * Script para probar el AJAX de variantes con sesión simulada
 */

// Iniciar sesión
session_start();

// Simular un usuario logueado
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['rol'] = 'admin';

// Simular una petición POST
$_POST['action'] = 'get_variantes';
$_POST['producto_id'] = '1'; // Playera Básica Algodón que tiene variantes

// Capturar la salida
ob_start();

// Incluir el archivo AJAX
include 'ajax/productos.php';

$output = ob_get_clean();

echo "Salida del archivo AJAX:\n";
echo $output . "\n";

// Decodificar JSON
$data = json_decode($output, true);

if ($data) {
    if (isset($data['success']) && $data['success']) {
        echo "✅ Variantes obtenidas correctamente: " . count($data['data']) . " variantes\n";
        foreach ($data['data'] as $variante) {
            echo "  - ID: {$variante['id']}, Talla: {$variante['talla']}, Color: {$variante['color']}, Material: {$variante['material']}, Precio Extra: {$variante['precio_extra']}\n";
        }
    } else {
        echo "❌ Error: " . ($data['message'] ?? 'Error desconocido') . "\n";
    }
} else {
    echo "❌ Error decodificando JSON\n";
}
?>
