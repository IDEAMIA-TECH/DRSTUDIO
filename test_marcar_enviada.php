<?php
// Archivo de prueba para simular "Marcar como Enviada"
session_start();

// Simular sesión de usuario autenticado
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Probando proceso 'Marcar como Enviada'...\n";

// Simular datos POST
$_POST = [
    'action' => 'change_status',
    'id' => '1',
    'estado' => 'enviada'
];

echo "Datos POST simulados:\n";
print_r($_POST);

// Incluir el archivo AJAX
ob_start();
include 'ajax/cotizaciones.php';
$output = ob_get_clean();

echo "\nRespuesta del servidor:\n";
echo $output;

echo "\nPrueba completada\n";
?>
