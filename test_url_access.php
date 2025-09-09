<?php
// Simular acceso desde admin/cotizaciones_view.php
echo "Probando acceso a ../ajax/cotizaciones.php desde admin/\n";

// Simular POST data
$_POST['action'] = 'change_status';
$_POST['id'] = '3';
$_POST['estado'] = 'rechazada';

// Simular que se está llamando desde admin/
$_SERVER['PHP_SELF'] = 'cotizaciones.php';

// Simular sesión activa
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "POST data: " . print_r($_POST, true) . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

// Incluir el archivo AJAX
echo "Incluyendo ../ajax/cotizaciones.php...\n";
require_once '../ajax/cotizaciones.php';
?>
