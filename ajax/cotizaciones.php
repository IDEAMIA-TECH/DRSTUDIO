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
        $cliente_id = (int)$_POST['cliente_id'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $observaciones = sanitizeInput($_POST['observaciones']);
        $descuento = (float)$_POST['descuento'];
        
        // Validar datos
        if (!$cliente_id) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar un cliente']);
            exit;
        }
        
        // Generar número de cotización
        $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Verificar que el número no exista
        while (readRecords('cotizaciones', ["numero_cotizacion = '$numero_cotizacion'"])) {
            $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Calcular totales
        $subtotal = 0;
        $items = [];
        
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if ($item['producto_id'] && $item['cantidad'] > 0) {
                    $producto = getRecord('productos', $item['producto_id']);
                    if ($producto) {
                        $precio_unitario = $producto['precio_venta'];
                        $precio_extra = 0;
                        
                        // Si hay variante, obtener precio extra
                        if ($item['variante_id']) {
                            $variante = getRecord('variantes_producto', $item['variante_id']);
                            if ($variante) {
                                $precio_extra = $variante['precio_extra'];
                            }
                        }
                        
                        $precio_final = $precio_unitario + $precio_extra;
                        $item_subtotal = $precio_final * $item['cantidad'];
                        
                        $items[] = [
                            'producto_id' => $item['producto_id'],
                            'variante_id' => $item['variante_id'] ?: null,
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $precio_final,
                            'subtotal' => $item_subtotal
                        ];
                        
                        $subtotal += $item_subtotal;
                    }
                }
            }
        }
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un producto a la cotización']);
            exit;
        }
        
        $total = $subtotal - $descuento;
        
        // Crear cotización
        $data = [
            'cliente_id' => $cliente_id,
            'usuario_id' => $_SESSION['user_id'],
            'numero_cotizacion' => $numero_cotizacion,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'estado' => 'pendiente',
            'fecha_vencimiento' => $fecha_vencimiento ?: null,
            'observaciones' => $observaciones
        ];
        
        if (createRecord('cotizaciones', $data)) {
            $cotizacion_id = $conn->insert_id;
            
            // Crear items de la cotización
            foreach ($items as $item) {
                $item['cotizacion_id'] = $cotizacion_id;
                createRecord('cotizacion_items', $item);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cotización creada exitosamente', 'id' => $cotizacion_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la cotización']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $cliente_id = (int)$_POST['cliente_id'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $observaciones = sanitizeInput($_POST['observaciones']);
        $descuento = (float)$_POST['descuento'];
        
        // Validar datos
        if (!$cliente_id) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar un cliente']);
            exit;
        }
        
        // Obtener cotización actual
        $cotizacion = getRecord('cotizaciones', $id);
        if (!$cotizacion) {
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }
        
        // Calcular totales
        $subtotal = 0;
        $items = [];
        
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if ($item['producto_id'] && $item['cantidad'] > 0) {
                    $producto = getRecord('productos', $item['producto_id']);
                    if ($producto) {
                        $precio_unitario = $producto['precio_venta'];
                        $precio_extra = 0;
                        
                        // Si hay variante, obtener precio extra
                        if ($item['variante_id']) {
                            $variante = getRecord('variantes_producto', $item['variante_id']);
                            if ($variante) {
                                $precio_extra = $variante['precio_extra'];
                            }
                        }
                        
                        $precio_final = $precio_unitario + $precio_extra;
                        $item_subtotal = $precio_final * $item['cantidad'];
                        
                        $items[] = [
                            'producto_id' => $item['producto_id'],
                            'variante_id' => $item['variante_id'] ?: null,
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $precio_final,
                            'subtotal' => $item_subtotal
                        ];
                        
                        $subtotal += $item_subtotal;
                    }
                }
            }
        }
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un producto a la cotización']);
            exit;
        }
        
        $total = $subtotal - $descuento;
        
        // Actualizar cotización
        $data = [
            'cliente_id' => $cliente_id,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'fecha_vencimiento' => $fecha_vencimiento ?: null,
            'observaciones' => $observaciones
        ];
        
        if (updateRecord('cotizaciones', $id, $data)) {
            // Eliminar items existentes
            $conn->query("DELETE FROM cotizacion_items WHERE cotizacion_id = $id");
            
            // Crear nuevos items
            foreach ($items as $item) {
                $item['cotizacion_id'] = $id;
                createRecord('cotizacion_items', $item);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cotización actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la cotización']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener cotización
        $cotizacion = getRecord('cotizaciones', $id);
        if (!$cotizacion) {
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }
        
        // Eliminar items de la cotización
        $conn->query("DELETE FROM cotizacion_items WHERE cotizacion_id = $id");
        
        // Eliminar cotización
        if (deleteRecord('cotizaciones', $id, false)) {
            echo json_encode(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cotización']);
        }
        break;
        
    case 'change_status':
        $id = (int)$_POST['id'];
        $estado = $_POST['estado'];
        
        $estadosValidos = ['pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada'];
        if (!in_array($estado, $estadosValidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit;
        }
        
        $data = ['estado' => $estado];
        if (updateRecord('cotizaciones', $id, $data)) {
            $estadoTexto = ucfirst($estado);
            echo json_encode(['success' => true, 'message' => "Cotización marcada como $estadoTexto exitosamente"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $cotizacion = getRecord('cotizaciones', $id);
        
        if ($cotizacion) {
            // Obtener items
            $items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');
            $cotizacion['items'] = $items;
            
            echo json_encode(['success' => true, 'data' => $cotizacion]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
        }
        break;
        
    case 'list':
        $cliente_id = $_POST['cliente_id'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $busqueda = $_POST['busqueda'] ?? '';
        $fecha_desde = $_POST['fecha_desde'] ?? '';
        $fecha_hasta = $_POST['fecha_hasta'] ?? '';
        
        // Construir condiciones de búsqueda
        $conditions = [];
        if ($cliente_id) {
            $conditions[] = "c.cliente_id = $cliente_id";
        }
        if ($estado) {
            $conditions[] = "c.estado = '$estado'";
        }
        if ($busqueda) {
            $conditions[] = "(c.numero_cotizacion LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR cl.empresa LIKE '%$busqueda%')";
        }
        if ($fecha_desde) {
            $conditions[] = "DATE(c.created_at) >= '$fecha_desde'";
        }
        if ($fecha_hasta) {
            $conditions[] = "DATE(c.created_at) <= '$fecha_hasta'";
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.empresa as cliente_empresa, u.username as creado_por
                FROM cotizaciones c 
                LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                $whereClause 
                ORDER BY c.created_at DESC";
        
        $result = $conn->query($sql);
        $cotizaciones = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        echo json_encode(['success' => true, 'data' => $cotizaciones]);
        break;
        
    case 'get_variantes':
        $producto_id = (int)$_POST['producto_id'];
        $variantes = readRecords('variantes_producto', ["producto_id = $producto_id", "activo = 1"], null, 'id ASC');
        echo json_encode(['success' => true, 'data' => $variantes]);
        break;
        
    case 'duplicate':
        $id = (int)$_POST['id'];
        
        // Obtener cotización original
        $cotizacion = getRecord('cotizaciones', $id);
        if (!$cotizacion) {
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }
        
        // Generar nuevo número
        $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        while (readRecords('cotizaciones', ["numero_cotizacion = '$numero_cotizacion'"])) {
            $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Crear nueva cotización
        $data = [
            'cliente_id' => $cotizacion['cliente_id'],
            'usuario_id' => $_SESSION['user_id'],
            'numero_cotizacion' => $numero_cotizacion,
            'subtotal' => $cotizacion['subtotal'],
            'descuento' => $cotizacion['descuento'],
            'total' => $cotizacion['total'],
            'estado' => 'pendiente',
            'fecha_vencimiento' => $cotizacion['fecha_vencimiento'],
            'observaciones' => $cotizacion['observaciones']
        ];
        
        if (createRecord('cotizaciones', $data)) {
            $nueva_cotizacion_id = $conn->insert_id;
            
            // Copiar items
            $items = readRecords('cotizacion_items', ["cotizacion_id = $id"]);
            foreach ($items as $item) {
                unset($item['id']);
                $item['cotizacion_id'] = $nueva_cotizacion_id;
                createRecord('cotizacion_items', $item);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cotización duplicada exitosamente', 'id' => $nueva_cotizacion_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al duplicar la cotización']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
