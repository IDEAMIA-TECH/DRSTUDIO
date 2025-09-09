<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'registrar_pago':
        registrarPago();
        break;
    case 'obtener_pagos':
        obtenerPagos();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function registrarPago() {
    global $conn;
    
    $cotizacion_id = intval($_POST['cotizacion_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
    $referencia = trim($_POST['referencia'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $usuario_id = $_SESSION['user_id'];
    
    // Validaciones
    if (!$cotizacion_id || $monto <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    // Verificar que la cotización existe
    $cotizacion = getRecord('cotizaciones', $cotizacion_id);
    if (!$cotizacion) {
        echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
        return;
    }
    
    // Insertar pago
    $sql = "INSERT INTO pagos_cotizacion (cotizacion_id, monto, metodo_pago, referencia, observaciones, usuario_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('idsssi', $cotizacion_id, $monto, $metodo_pago, $referencia, $observaciones, $usuario_id);
    
    if ($stmt->execute()) {
        // Actualizar estado de la cotización a 'pagada'
        updateRecord('cotizaciones', $cotizacion_id, ['estado' => 'pagada']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pago registrado exitosamente',
            'pago_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar el pago: ' . $conn->error]);
    }
    
    $stmt->close();
}

function obtenerPagos() {
    global $conn;
    
    $cotizacion_id = intval($_POST['cotizacion_id'] ?? 0);
    
    if (!$cotizacion_id) {
        echo json_encode(['success' => false, 'message' => 'ID de cotización requerido']);
        return;
    }
    
    $sql = "SELECT p.*, u.username as usuario_nombre 
            FROM pagos_cotizacion p 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.cotizacion_id = ? 
            ORDER BY p.fecha_pago DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $cotizacion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pagos = [];
    while ($row = $result->fetch_assoc()) {
        $pagos[] = $row;
    }
    
    echo json_encode(['success' => true, 'pagos' => $pagos]);
    
    $stmt->close();
}
?>
