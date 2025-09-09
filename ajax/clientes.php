<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $nombre = sanitizeInput($_POST['nombre']);
        $email = sanitizeInput($_POST['email']);
        $telefono = sanitizeInput($_POST['telefono']);
        $empresa = sanitizeInput($_POST['empresa']);
        $direccion = sanitizeInput($_POST['direccion']);
        
        // Validar datos
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }
        
        if ($email && !validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'El email no tiene un formato válido']);
            exit;
        }
        
        // Verificar si ya existe un cliente con el mismo email
        if ($email) {
            $existing = readRecords('clientes', ["email = '$email'"]);
            if (!empty($existing)) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un cliente con este email']);
                exit;
            }
        }
        
        // Crear cliente
        $data = [
            'nombre' => $nombre,
            'email' => $email ?: null,
            'telefono' => $telefono ?: null,
            'empresa' => $empresa ?: null,
            'direccion' => $direccion ?: null
        ];
        
        if (createRecord('clientes', $data)) {
            echo json_encode(['success' => true, 'message' => 'Cliente creado exitosamente', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el cliente']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $nombre = sanitizeInput($_POST['nombre']);
        $email = sanitizeInput($_POST['email']);
        $telefono = sanitizeInput($_POST['telefono']);
        $empresa = sanitizeInput($_POST['empresa']);
        $direccion = sanitizeInput($_POST['direccion']);
        
        // Validar datos
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }
        
        if ($email && !validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'El email no tiene un formato válido']);
            exit;
        }
        
        // Verificar si ya existe otro cliente con el mismo email
        if ($email) {
            $existing = readRecords('clientes', ["email = '$email'", "id != $id"]);
            if (!empty($existing)) {
                echo json_encode(['success' => false, 'message' => 'Ya existe otro cliente con este email']);
                exit;
            }
        }
        
        // Obtener cliente actual
        $cliente = getRecord('clientes', $id);
        if (!$cliente) {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
            exit;
        }
        
        // Actualizar cliente
        $data = [
            'nombre' => $nombre,
            'email' => $email ?: null,
            'telefono' => $telefono ?: null,
            'empresa' => $empresa ?: null,
            'direccion' => $direccion ?: null
        ];
        
        if (updateRecord('clientes', $id, $data)) {
            echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el cliente']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener cliente
        $cliente = getRecord('clientes', $id);
        if (!$cliente) {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
            exit;
        }
        
        // Verificar si tiene cotizaciones
        $cotizaciones = readRecords('cotizaciones', ["cliente_id = $id"]);
        if (!empty($cotizaciones)) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar el cliente porque tiene cotizaciones asociadas']);
            exit;
        }
        
        // Eliminar cliente
        if (deleteRecord('clientes', $id, false)) {
            echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el cliente']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $cliente = getRecord('clientes', $id);
        
        if ($cliente) {
            // Obtener estadísticas del cliente
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total_cotizaciones,
                    SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END) as total_ventas
                FROM cotizaciones 
                WHERE cliente_id = $id
            ")->fetch_assoc();
            
            $cliente['stats'] = $stats;
            echo json_encode(['success' => true, 'data' => $cliente]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        }
        break;
        
    case 'list':
        $busqueda = $_POST['busqueda'] ?? '';
        
        // Construir condiciones de búsqueda
        $conditions = [];
        if ($busqueda) {
            $conditions[] = "(nombre LIKE '%$busqueda%' OR email LIKE '%$busqueda%' OR empresa LIKE '%$busqueda%' OR telefono LIKE '%$busqueda%')";
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT c.*, 
                COUNT(cot.id) as total_cotizaciones,
                SUM(CASE WHEN cot.estado = 'aceptada' THEN cot.total ELSE 0 END) as total_ventas
                FROM clientes c 
                LEFT JOIN cotizaciones cot ON c.id = cot.cliente_id 
                $whereClause 
                GROUP BY c.id 
                ORDER BY c.created_at DESC";
        
        $result = $conn->query($sql);
        $clientes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        echo json_encode(['success' => true, 'data' => $clientes]);
        break;
        
    case 'search':
        $query = sanitizeInput($_POST['query']);
        
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        
        $sql = "SELECT id, nombre, email, empresa 
                FROM clientes 
                WHERE (nombre LIKE '%$query%' OR email LIKE '%$query%' OR empresa LIKE '%$query%') 
                ORDER BY nombre ASC 
                LIMIT 10";
        
        $result = $conn->query($sql);
        $clientes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        echo json_encode(['success' => true, 'data' => $clientes]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
