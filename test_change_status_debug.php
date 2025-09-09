<?php
// Simular una petición AJAX para cambiar estado
$_POST['action'] = 'change_status';
$_POST['id'] = '1';
$_POST['estado'] = 'enviada';

error_log("TEST - Simulando cambio de estado");
error_log("TEST - POST data: " . print_r($_POST, true));

// Simular que se está llamando directamente
$_SERVER['PHP_SELF'] = 'cotizaciones.php';

// Simular sesión activa
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

error_log("TEST - Sesión simulada: " . print_r($_SESSION, true));

// Incluir el archivo AJAX
require_once 'ajax/cotizaciones.php';
?>
