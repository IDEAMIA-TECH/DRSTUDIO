<?php
// Sistema de rutas robusto
function getProjectRoot() {
    $currentDir = __DIR__;
    $projectRoot = dirname($currentDir);
    return $projectRoot;
}

$projectRoot = getProjectRoot();
require_once $projectRoot . '/includes/config.php';
require_once $projectRoot . '/includes/auth.php';
require_once $projectRoot . '/includes/functions.php';

header('Content-Type: application/json');

// Verificar autenticación
error_log('AJAX Cotizaciones - Verificando autenticación');
error_log('AJAX Cotizaciones - Session ID: ' . session_id());
error_log('AJAX Cotizaciones - User ID en sesión: ' . ($_SESSION['user_id'] ?? 'No definido'));

if (!isLoggedIn()) {
    error_log('AJAX Cotizaciones - Usuario no autenticado');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

error_log('AJAX Cotizaciones - Usuario autenticado correctamente');

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
        error_log("AJAX Cotizaciones - Intentando eliminar cotización ID: $id");
        
        // Obtener cotización
        $cotizacion = getRecord('cotizaciones', $id);
        if (!$cotizacion) {
            error_log("AJAX Cotizaciones - Cotización ID $id no encontrada");
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }
        
        error_log("AJAX Cotizaciones - Cotización encontrada: " . $cotizacion['numero_cotizacion']);
        
        // Eliminar items de la cotización
        $itemsResult = $conn->query("DELETE FROM cotizacion_items WHERE cotizacion_id = $id");
        error_log("AJAX Cotizaciones - Items eliminados: " . ($itemsResult ? 'Sí' : 'No'));
        
        // Eliminar cotización
        $deleteResult = deleteRecord('cotizaciones', $id, false);
        error_log("AJAX Cotizaciones - Resultado eliminación: " . ($deleteResult ? 'Éxito' : 'Error'));
        
        if ($deleteResult) {
            echo json_encode(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cotización']);
        }
        break;
        
    case 'change_status':
        $id = (int)$_POST['id'];
        $estado = $_POST['estado'];
        
        $estadosValidos = ['pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada', 'en_espera_deposito'];
        if (!in_array($estado, $estadosValidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit;
        }
        
        $data = ['estado' => $estado];
        if (updateRecord('cotizaciones', $id, $data)) {
            $estadoTexto = ucfirst($estado);
            
            // Si se marca como "enviada", enviar correo con PDF
            if ($estado === 'enviada') {
                try {
                    require_once $projectRoot . '/includes/EmailSender.php';
                    
                    // Obtener información de la cotización y cliente
                    $cotizacion = getRecord('cotizaciones', $id);
                    $cliente = getRecord('clientes', $cotizacion['cliente_id']);
                    
                    // Generar PDF temporal
                    $pdfPath = generatePDFForEmail($cotizacion);
                    
                    // Enviar correo
                    $emailSender = new EmailSender();
                    $emailSent = $emailSender->sendQuoteEmail($cotizacion, $cliente, $pdfPath);
                    
                    // Limpiar PDF temporal
                    if ($pdfPath && file_exists($pdfPath)) {
                        unlink($pdfPath);
                    }
                    
                    if ($emailSent) {
                        $estadoTexto .= ' y correo enviado exitosamente';
                    } else {
                        $estadoTexto .= ' (Error al enviar correo)';
                    }
                    
                } catch (Exception $e) {
                    error_log("Error enviando correo: " . $e->getMessage());
                    $estadoTexto .= ' (Error al enviar correo)';
                }
            }
            
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

// Función para generar PDF temporal para correo
function generatePDFForEmail($cotizacion) {
    try {
        require_once $projectRoot . '/vendor/autoload.php';
        
        // Obtener items de la cotización
        $items = readRecords('cotizacion_items', ["cotizacion_id = {$cotizacion['id']}"], null, 'id ASC');
        foreach ($items as &$item) {
            $producto = getRecord('productos', $item['producto_id']);
            $item['producto'] = $producto;
            
            if ($item['variante_id']) {
                $variante = getRecord('variantes_producto', $item['variante_id']);
                $item['variante'] = $variante;
            }
        }
        
        // Obtener cliente
        $cliente = getRecord('clientes', $cotizacion['cliente_id']);
        
        // Preparar datos para el PDF
        $pdfData = [
            'numero' => $cotizacion['numero_cotizacion'],
            'fecha' => date('d/m/Y H:i', strtotime($cotizacion['created_at'])),
            'cliente' => [
                'nombre' => $cliente['nombre'],
                'empresa' => $cliente['empresa'] ?? '',
                'email' => $cliente['email'],
                'telefono' => $cliente['telefono'] ?? ''
            ],
            'items' => $items,
            'subtotal' => $cotizacion['subtotal'],
            'descuento' => $cotizacion['descuento'],
            'total' => $cotizacion['total'],
            'observaciones' => $cotizacion['observaciones'] ?? '',
            'estado' => $cotizacion['estado']
        ];
        
        // Generar HTML usando la función del generate_pdf.php
        require_once $projectRoot . '/ajax/generate_pdf.php';
        $html = createCotizacionHTML($pdfData);
        
        // Crear instancia de mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $cotizacion['numero_cotizacion']);
        $mpdf->SetAuthor('DT Studio');
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar archivo temporal
        $tempPath = sys_get_temp_dir() . '/cotizacion_' . $cotizacion['numero_cotizacion'] . '_' . time() . '.pdf';
        $mpdf->Output($tempPath, 'F');
        
        return $tempPath;
        
    } catch (Exception $e) {
        error_log("Error generando PDF para correo: " . $e->getMessage());
        return false;
    }
}
?>
