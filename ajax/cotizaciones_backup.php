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
require_once $projectRoot . '/includes/pdf_generator.php';

// Solo ejecutar la lógica principal si se llama directamente (no si se incluye)
if (basename($_SERVER['PHP_SELF']) === 'cotizaciones.php') {
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
        
        if (updateRecord('cotizaciones', $data, $id)) {
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
        error_log("AJAX Cotizaciones - ===== INICIANDO CHANGE_STATUS =====");
        error_log("AJAX Cotizaciones - POST data: " . print_r($_POST, true));
        
        $id = (int)$_POST['id'];
        $estado = $_POST['estado'];
        
        error_log("AJAX Cotizaciones - ID extraído: $id");
        error_log("AJAX Cotizaciones - Estado extraído: $estado");
        
        $estadosValidos = ['pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada', 'en_espera_deposito'];
        if (!in_array($estado, $estadosValidos)) {
            error_log("AJAX Cotizaciones - ERROR: Estado no válido: $estado");
            error_log("AJAX Cotizaciones - Estados válidos: " . implode(', ', $estadosValidos));
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit;
        }
        
        // Verificar el estado actual de la cotización
        $cotizacionActual = getRecord('cotizaciones', $id);
        if (!$cotizacionActual) {
            error_log("AJAX Cotizaciones - ERROR: Cotización no encontrada");
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }
        
        error_log("AJAX Cotizaciones - Estado actual: " . $cotizacionActual['estado']);
        
        // Si ya tiene el mismo estado, no hacer nada
        if ($cotizacionActual['estado'] === $estado) {
            error_log("AJAX Cotizaciones - La cotización ya tiene el estado '$estado'");
            echo json_encode(['success' => true, 'message' => "La cotización ya tiene el estado '$estado'"]);
            exit;
        }
        
        $data = ['estado' => $estado];
        error_log("AJAX Cotizaciones - Datos para actualizar: " . print_r($data, true));
        error_log("AJAX Cotizaciones - Llamando a updateRecord...");
        
        $updateResult = updateRecord('cotizaciones', $data, $id);
        error_log("AJAX Cotizaciones - Resultado de updateRecord: " . ($updateResult ? 'TRUE' : 'FALSE'));
        
        if ($updateResult) {
            error_log("AJAX Cotizaciones - Estado actualizado exitosamente");
            $estadoTexto = ucfirst($estado);
            
            // Si se marca como "enviada", enviar correo con PDF
            if ($estado === 'enviada') {
                error_log("AJAX Cotizaciones - Iniciando proceso de envío de correo");
                try {
                    require_once $projectRoot . '/includes/EmailSender.php';
                    error_log("AJAX Cotizaciones - EmailSender incluido correctamente");
                    
                    // Obtener información de la cotización y cliente
                    $cotizacion = getRecord('cotizaciones', $id);
                    if (!$cotizacion) {
                        error_log("AJAX Cotizaciones - Error: Cotización no encontrada");
                        throw new Exception("Cotización no encontrada");
                    }
                    error_log("AJAX Cotizaciones - Cotización obtenida: " . $cotizacion['numero_cotizacion']);
                    
                    $cliente = getRecord('clientes', $cotizacion['cliente_id']);
                    if (!$cliente) {
                        error_log("AJAX Cotizaciones - Error: Cliente no encontrado");
                        throw new Exception("Cliente no encontrado");
                    }
                    error_log("AJAX Cotizaciones - Cliente obtenido: " . $cliente['nombre']);
                    
                    // Generar PDF temporal
                    error_log("AJAX Cotizaciones - Generando PDF temporal");
                    $pdfPath = generateCotizacionPDF($cotizacion['id']);
                    if ($pdfPath) {
                        error_log("AJAX Cotizaciones - PDF generado: $pdfPath");
                    } else {
                        error_log("AJAX Cotizaciones - Error generando PDF");
                    }
                    
                    // Enviar correo
                    error_log("AJAX Cotizaciones - Enviando correo");
                    $emailSender = new EmailSender();
                    $emailSent = $emailSender->sendQuoteEmail($cotizacion, $cliente, $pdfPath);
                    
                    // Limpiar PDF temporal
                    if ($pdfPath && file_exists($pdfPath)) {
                        unlink($pdfPath);
                        error_log("AJAX Cotizaciones - PDF temporal eliminado");
                    }
                    
                    if ($emailSent) {
                        error_log("AJAX Cotizaciones - Correo enviado exitosamente");
                        $estadoTexto .= ' y correo enviado exitosamente';
                    } else {
                        error_log("AJAX Cotizaciones - Error al enviar correo");
                        $estadoTexto .= ' (Error al enviar correo)';
                    }
                    
                } catch (Exception $e) {
                    error_log("AJAX Cotizaciones - Excepción enviando correo: " . $e->getMessage());
                    error_log("AJAX Cotizaciones - Stack trace: " . $e->getTraceAsString());
                    $estadoTexto .= ' (Error al enviar correo: ' . $e->getMessage() . ')';
                }
            }
            
            error_log("AJAX Cotizaciones - Respuesta exitosa: $estadoTexto");
            echo json_encode(['success' => true, 'message' => "Cotización marcada como $estadoTexto exitosamente"]);
        } else {
            error_log("AJAX Cotizaciones - ERROR: updateRecord falló");
            error_log("AJAX Cotizaciones - ID de cotización: $id");
            error_log("AJAX Cotizaciones - Datos enviados: " . print_r($data, true));
            error_log("AJAX Cotizaciones - Verificando si la cotización existe...");
            
            $cotizacionCheck = getRecord('cotizaciones', $id);
            if ($cotizacionCheck) {
                error_log("AJAX Cotizaciones - La cotización existe: " . print_r($cotizacionCheck, true));
            } else {
                error_log("AJAX Cotizaciones - ERROR: La cotización no existe en la base de datos");
            }
            
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado de la cotización']);
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
    global $projectRoot;
    
    error_log("generatePDFForEmail - Iniciando generación de PDF para cotización: " . $cotizacion['numero_cotizacion']);
    
    try {
        require_once $projectRoot . '/vendor/autoload.php';
        error_log("generatePDFForEmail - Vendor autoload incluido");
        
        // Obtener items de la cotización
        $items = readRecords('cotizacion_items', ["cotizacion_id = {$cotizacion['id']}"], null, 'id ASC');
        error_log("generatePDFForEmail - Items obtenidos: " . count($items));
        
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
        error_log("generatePDFForEmail - Cliente obtenido: " . $cliente['nombre']);
        
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
        
        error_log("generatePDFForEmail - Datos preparados para PDF");
        
        // Generar HTML directamente (sin depender de generate_pdf.php)
        error_log("generatePDFForEmail - Generando HTML directamente");
        
        $html = createCotizacionHTMLForEmail($pdfData);
        error_log("generatePDFForEmail - HTML generado, longitud: " . strlen($html));
        
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
        error_log("generatePDFForEmail - Instancia mPDF creada");
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $cotizacion['numero_cotizacion']);
        $mpdf->SetAuthor('DT Studio');
        error_log("generatePDFForEmail - Metadatos configurados");
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        error_log("generatePDFForEmail - HTML escrito en mPDF");
        
        // Generar archivo temporal
        $tempPath = sys_get_temp_dir() . '/cotizacion_' . $cotizacion['numero_cotizacion'] . '_' . time() . '.pdf';
        $mpdf->Output($tempPath, 'F');
        error_log("generatePDFForEmail - PDF generado en: $tempPath");
        
        return $tempPath;
        
    } catch (Exception $e) {
        error_log("generatePDFForEmail - Error: " . $e->getMessage());
        error_log("generatePDFForEmail - Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Función para crear HTML de cotización para correo
function createCotizacionHTMLForEmail($data) {
    $logoPath = '../assets/logo/LOGO.png';
    $logoExists = file_exists($logoPath);
    
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #7B3F9F;
            }
            .logo {
                max-height: 60px;
            }
            .company-info {
                text-align: right;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #7B3F9F;
                margin: 0;
            }
            .company-subtitle {
                font-size: 14px;
                color: #666;
                margin: 5px 0;
            }
            .document-title {
                font-size: 28px;
                font-weight: bold;
                color: #333;
                margin: 20px 0;
                text-align: center;
            }
            .document-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
            }
            .client-info, .quote-info {
                flex: 1;
            }
            .client-info h3, .quote-info h3 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
                font-size: 16px;
            }
            .client-info p, .quote-info p {
                margin: 5px 0;
                font-size: 12px;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .items-table th, .items-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .items-table th {
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
            }
            .items-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .totals {
                margin-left: auto;
                width: 300px;
                margin-top: 20px;
            }
            .totals table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals td {
                padding: 8px;
                border: none;
            }
            .totals .total-row {
                font-weight: bold;
                font-size: 16px;
                background-color: #7B3F9F;
                color: white !important;
            }
            .totals .total-row td {
                color: white !important;
                background-color: #7B3F9F !important;
                padding: 10px;
            }
            .observations {
                margin-top: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border-left: 4px solid #7B3F9F;
            }
            .observations h3 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-pendiente { background-color: #ffc107; color: #000; }
            .status-enviada { background-color: #17a2b8; color: #fff; }
            .status-aceptada { background-color: #28a745; color: #fff; }
            .status-rechazada { background-color: #dc3545; color: #fff; }
            .status-cancelada { background-color: #6c757d; color: #fff; }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                ' . ($logoExists ? '<img src="' . $logoPath . '" alt="DT Studio" class="logo">' : '') . '
            </div>
            <div class="company-info">
                <h1 class="company-name">DT Studio</h1>
                <p class="company-subtitle">DT Studio</p>
                <p>Tel: (55) 1234-5678</p>
                <p>Email: info@dtstudio.com.mx</p>
                <p>Web: www.dtstudio.com.mx</p>
            </div>
        </div>
        
        <h2 class="document-title">COTIZACIÓN</h2>
        
        <div class="document-info">
            <div class="client-info">
                <h3>Cliente</h3>
                <p><strong>Nombre:</strong> ' . htmlspecialchars($data['cliente']['nombre']) . '</p>
                ' . ($data['cliente']['empresa'] ? '<p><strong>Empresa:</strong> ' . htmlspecialchars($data['cliente']['empresa']) . '</p>' : '') . '
                ' . ($data['cliente']['email'] ? '<p><strong>Email:</strong> ' . htmlspecialchars($data['cliente']['email']) . '</p>' : '') . '
                ' . ($data['cliente']['telefono'] ? '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['cliente']['telefono']) . '</p>' : '') . '
            </div>
            <div class="quote-info">
                <h3>Información de Cotización</h3>
                <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                <p><strong>Fecha:</strong> ' . htmlspecialchars($data['fecha']) . '</p>
                <p><strong>Estado:</strong> <span class="status-badge status-' . $data['estado'] . '">' . ucfirst($data['estado']) . '</span></p>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Variante</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['items'] as $item) {
        $variante = '';
        if (isset($item['variante']) && $item['variante']) {
            $variante_parts = array_filter([
                $item['variante']['talla'] ?? '',
                $item['variante']['color'] ?? '',
                $item['variante']['material'] ?? ''
            ]);
            $variante = implode(' - ', $variante_parts);
        }
        
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['producto']['nombre']) . '<br><small>SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small></td>
                    <td>' . ($variante ? htmlspecialchars($variante) : 'Sin variante') . '</td>
                    <td>' . $item['cantidad'] . '</td>
                    <td>$' . number_format($item['precio_unitario'], 2) . '</td>
                    <td>$' . number_format($item['subtotal'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td style="text-align: right;">$' . number_format($data['subtotal'], 2) . '</td>
                </tr>';
    
    if ($data['descuento'] > 0) {
        $html .= '
                <tr>
                    <td>Descuento:</td>
                    <td style="text-align: right; color: #dc3545;">-$' . number_format($data['descuento'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">$' . number_format($data['total'], 2) . '</td>
                </tr>
            </table>
        </div>';
    
    if (!empty($data['observaciones'])) {
        $html .= '
        <div class="observations">
            <h3>Observaciones</h3>
            <p>' . nl2br(htmlspecialchars($data['observaciones'])) . '</p>
        </div>';
    }
    
    $html .= '
    </body>
    </html>';
    
    return $html;
}

// Cerrar el if principal
}

// Funciones auxiliares (disponibles siempre)
function generatePDFForEmail($cotizacion) {
    global $projectRoot;
    
    error_log("generatePDFForEmail - Iniciando generación de PDF para cotización: " . $cotizacion['numero_cotizacion']);
    
    try {
        require_once $projectRoot . '/vendor/autoload.php';
        error_log("generatePDFForEmail - Vendor autoload incluido");
        
        // Obtener items de la cotización
        $items = readRecords('cotizacion_items', ["cotizacion_id = {$cotizacion['id']}"], null, 'id ASC');
        error_log("generatePDFForEmail - Items obtenidos: " . count($items));
        
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
        error_log("generatePDFForEmail - Cliente obtenido: " . $cliente['nombre']);
        
        // Preparar datos para el PDF
        $pdfData = [
            'cotizacion' => $cotizacion,
            'cliente' => $cliente,
            'items' => $items
        ];
        
        error_log("generatePDFForEmail - Generando HTML directamente");
        $html = createCotizacionHTMLForEmail($pdfData);
        error_log("generatePDFForEmail - HTML generado, longitud: " . strlen($html));
        
        // Crear instancia de mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $cotizacion['numero_cotizacion']);
        $mpdf->SetAuthor('DT Studio');
        $mpdf->SetSubject('Cotización de productos promocionales');
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar archivo temporal
        $tempPath = sys_get_temp_dir() . '/cotizacion_' . $cotizacion['id'] . '_' . time() . '.pdf';
        $mpdf->Output($tempPath, 'F');
        
        error_log("generatePDFForEmail - PDF generado en: $tempPath");
        return $tempPath;
        
    } catch (Exception $e) {
        error_log("generatePDFForEmail - Error: " . $e->getMessage());
        error_log("generatePDFForEmail - Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

function createCotizacionHTMLForEmail($data) {
    $cotizacion = $data['cotizacion'];
    $cliente = $data['cliente'];
    $items = $data['items'];
    
    // Verificar si el logo existe
    $logoPath = '../assets/logo/LOGO.png';
    $logoExists = file_exists($logoPath);
    
    // Calcular totales
    $subtotal = 0;
    foreach ($items as $item) {
        $precio = ($item['precio_unitario'] ?? 0) + ($item['precio_extra'] ?? 0);
        $subtotal += $precio * $item['cantidad'];
    }
    
    $descuento = $cotizacion['descuento'] ?? 0;
    $total = $subtotal - $descuento;
    
    $html = '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cotización ' . htmlspecialchars($cotizacion['numero_cotizacion']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #7B3F9F;
            }
            .logo {
                max-width: 150px;
                max-height: 80px;
            }
            .company-info {
                text-align: right;
                color: #7B3F9F;
            }
            .company-name {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .quote-info {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .quote-title {
                font-size: 24px;
                font-weight: bold;
                color: #7B3F9F;
                margin-bottom: 10px;
            }
            .client-section {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            .client-info, .quote-details {
                flex: 1;
                margin-right: 20px;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #7B3F9F;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #ddd;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .items-table th,
            .items-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .items-table th {
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
            }
            .items-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .totals {
                margin-top: 20px;
                text-align: right;
            }
            .total-row {
                background-color: #7B3F9F !important;
                color: white !important;
            }
            .total-row td {
                color: white !important;
                font-weight: bold;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #666;
                font-size: 10px;
            }
            .accept-button {
                display: inline-block;
                background-color: #28a745;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 0;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div>';
    
    if ($logoExists) {
        $html .= '<img src="' . $logoPath . '" alt="DT Studio" class="logo">';
    } else {
        $html .= '<div class="company-name">DT Studio</div>';
    }
    
    $html .= '</div>
            <div class="company-info">
                <div class="company-name">DT Studio</div>
                <div>Promocionales y Merchandising</div>
                <div>www.dtstudio.com.mx</div>
            </div>
        </div>
        
        <div class="quote-info">
            <div class="quote-title">COTIZACIÓN</div>
            <div><strong>Número:</strong> ' . htmlspecialchars($cotizacion['numero_cotizacion']) . '</div>
            <div><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($cotizacion['created_at'])) . '</div>
            <div><strong>Válida hasta:</strong> ' . ($cotizacion['fecha_vencimiento'] ? date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) : 'No especificada') . '</div>
        </div>
        
        <div class="client-section">
            <div class="client-info">
                <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
                <div><strong>' . htmlspecialchars($cliente['nombre']) . '</strong></div>';
    
    if ($cliente['empresa']) {
        $html .= '<div>' . htmlspecialchars($cliente['empresa']) . '</div>';
    }
    
    if ($cliente['email']) {
        $html .= '<div>Email: ' . htmlspecialchars($cliente['email']) . '</div>';
    }
    
    if ($cliente['telefono']) {
        $html .= '<div>Teléfono: ' . htmlspecialchars($cliente['telefono']) . '</div>';
    }
    
    $html .= '</div>
            <div class="quote-details">
                <div class="section-title">INFORMACIÓN DE COTIZACIÓN</div>
                <div><strong>Subtotal:</strong> $' . number_format($subtotal, 2) . '</div>';
    
    if ($descuento > 0) {
        $html .= '<div><strong>Descuento:</strong> -$' . number_format($descuento, 2) . '</div>';
    }
    
    $html .= '<div><strong>Total:</strong> $' . number_format($total, 2) . '</div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($items as $item) {
        $precio = ($item['precio_unitario'] ?? 0) + ($item['precio_extra'] ?? 0);
        $totalItem = $precio * $item['cantidad'];
        
        $html .= '<tr>
                    <td>' . htmlspecialchars($item['producto']['nombre']) . '</td>
                    <td>';
        
        if (isset($item['variante']) && $item['variante']) {
            $varianteInfo = [];
            if ($item['variante']['talla']) $varianteInfo[] = 'Talla: ' . $item['variante']['talla'];
            if ($item['variante']['color']) $varianteInfo[] = 'Color: ' . $item['variante']['color'];
            if ($item['variante']['material']) $varianteInfo[] = 'Material: ' . $item['variante']['material'];
            $html .= implode(', ', $varianteInfo);
        }
        
        $html .= '</td>
                    <td>' . $item['cantidad'] . '</td>
                    <td>$' . number_format($precio, 2) . '</td>
                    <td>$' . number_format($totalItem, 2) . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="totals">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Subtotal:</strong></td>
                    <td style="text-align: right; padding: 5px; width: 100px;">$' . number_format($subtotal, 2) . '</td>
                </tr>';
    
    if ($descuento > 0) {
        $html .= '<tr>
                    <td style="text-align: right; padding: 5px;"><strong>Descuento:</strong></td>
                    <td style="text-align: right; padding: 5px;">-$' . number_format($descuento, 2) . '</td>
                </tr>';
    }
    
    $html .= '<tr class="total-row">
                    <td style="text-align: right; padding: 10px; background-color: #7B3F9F; color: white;"><strong>TOTAL:</strong></td>
                    <td style="text-align: right; padding: 10px; background-color: #7B3F9F; color: white; font-size: 16px;"><strong>$' . number_format($total, 2) . '</strong></td>
                </tr>
            </table>
        </div>';
    
    if ($cotizacion['observaciones']) {
        $html .= '<div style="margin-top: 20px;">
                    <div class="section-title">OBSERVACIONES</div>
                    <div>' . nl2br(htmlspecialchars($cotizacion['observaciones'])) . '</div>
                </div>';
    }
    
    $html .= '<div class="footer">
            <p>Esta cotización es válida por 30 días a partir de la fecha de emisión.</p>
            <p>Para aceptar esta cotización, haga clic en el siguiente enlace:</p>
            <a href="' . (defined('BASE_URL') ? BASE_URL : 'https://dtstudio.com.mx') . '/aceptar-cotizacion.php?token=' . ($cotizacion['token_aceptacion'] ?? '') . '" class="accept-button">
                ACEPTAR COTIZACIÓN
            </a>
            <p>DT Studio - Promocionales y Merchandising | www.dtstudio.com.mx</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
