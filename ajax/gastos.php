<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar permisos de administrador
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT rol FROM usuarios WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user || $user['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);


switch ($action) {
    case 'aprobar':
        aprobarGasto($id);
        break;
    case 'rechazar':
        rechazarGasto($id);
        break;
    case 'eliminar':
        eliminarGasto($id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function aprobarGasto($id) {
    global $conn;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    // Verificar que el gasto existe y está pendiente
    $gasto = getRecord('gastos', ['id' => $id]);
    if (!$gasto) {
        echo json_encode(['success' => false, 'message' => 'Gasto no encontrado']);
        return;
    }
    
    if ($gasto['estado'] != 'pendiente') {
        echo json_encode(['success' => false, 'message' => 'El gasto no está pendiente']);
        return;
    }
    
    // Actualizar estado a aprobado
    $update_data = [
        'estado' => 'aprobado',
        'aprobado_por' => $_SESSION['user_id'],
        'fecha_aprobacion' => date('Y-m-d H:i:s')
    ];
    
    if (updateRecord('gastos', $update_data, ['id' => $id])) {
        echo json_encode(['success' => true, 'message' => 'Gasto aprobado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al aprobar el gasto']);
    }
}

function rechazarGasto($id) {
    global $conn;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    // Verificar que el gasto existe y está pendiente
    $gasto = getRecord('gastos', ['id' => $id]);
    if (!$gasto) {
        echo json_encode(['success' => false, 'message' => 'Gasto no encontrado']);
        return;
    }
    
    if ($gasto['estado'] != 'pendiente') {
        echo json_encode(['success' => false, 'message' => 'El gasto no está pendiente']);
        return;
    }
    
    // Actualizar estado a rechazado
    $update_data = [
        'estado' => 'rechazado',
        'aprobado_por' => $_SESSION['user_id'],
        'fecha_aprobacion' => date('Y-m-d H:i:s')
    ];
    
    if (updateRecord('gastos', $update_data, ['id' => $id])) {
        echo json_encode(['success' => true, 'message' => 'Gasto rechazado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al rechazar el gasto']);
    }
}

function eliminarGasto($id) {
    global $conn;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    // Verificar que el gasto existe
    $gasto = getRecord('gastos', ['id' => $id]);
    if (!$gasto) {
        echo json_encode(['success' => false, 'message' => 'Gasto no encontrado']);
        return;
    }
    
    // Eliminar archivo de comprobante si existe
    if ($gasto['comprobante']) {
        $filePath = '../uploads/gastos/' . $gasto['comprobante'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Eliminar registro de la base de datos
    if (deleteRecord('gastos', ['id' => $id])) {
        echo json_encode(['success' => true, 'message' => 'Gasto eliminado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el gasto']);
    }
}
?>
