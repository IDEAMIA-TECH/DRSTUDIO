<?php
// Archivo AJAX para manejo de cotizaciones
function getProjectRoot() {
    $currentDir = dirname(__FILE__);
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    error_log("AJAX Cotizaciones - Verificando autenticación");
    error_log("AJAX Cotizaciones - Session ID: " . session_id());
    error_log("AJAX Cotizaciones - User ID en sesión: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO'));
    
    if (isset($_SESSION['user_id'])) {
        error_log("AJAX Cotizaciones - Usuario autenticado correctamente");
    } else {
        error_log("AJAX Cotizaciones - Usuario NO autenticado");
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    error_log("AJAX Cotizaciones - Acción recibida: " . $action);
    
    if ($action === 'change_status') {
        error_log("AJAX Cotizaciones - ===== INICIANDO CHANGE_STATUS =====");
        error_log("AJAX Cotizaciones - POST data: " . print_r($_POST, true));
        
        $id = intval($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        
        error_log("AJAX Cotizaciones - ID extraído: $id");
        error_log("AJAX Cotizaciones - Estado extraído: $estado");
        
        if (!$id || !$estado) {
            error_log("AJAX Cotizaciones - ERROR: ID o estado faltante");
            echo json_encode(['success' => false, 'message' => 'ID o estado faltante']);
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
        
        // Actualizar estado
        $updateData = ['estado' => $estado];
        error_log("AJAX Cotizaciones - Datos para actualizar: " . print_r($updateData, true));
        
        error_log("AJAX Cotizaciones - Llamando a updateRecord...");
        $result = updateRecord('cotizaciones', $updateData, $id);
        error_log("AJAX Cotizaciones - Resultado de updateRecord: " . ($result ? 'TRUE' : 'FALSE'));
        
        if ($result) {
            error_log("AJAX Cotizaciones - Estado actualizado exitosamente");
            
            // Si el estado es 'enviada', enviar correo
            if ($estado === 'enviada') {
                error_log("AJAX Cotizaciones - Enviando correo para cotización enviada");
                
                try {
                    require_once $projectRoot . '/includes/SimpleEmailSender.php';
                    
                    // Obtener datos completos de la cotización
                    $cotizacion = getRecord('cotizaciones', $id);
                    $cliente = getRecord('clientes', $cotizacion['cliente_id']);
                    
                    error_log("AJAX Cotizaciones - Cliente obtenido: " . $cliente['nombre']);
                    
                    // Obtener items de la cotización (productos del catálogo)
                    $items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');
                    
                    foreach ($items as &$item) {
                        $producto = getRecord('productos', $item['producto_id']);
                        $item['producto'] = $producto;
                        
                        if ($item['variante_id']) {
                            $variante = getRecord('variantes_producto', $item['variante_id']);
                            $item['variante'] = $variante;
                        }
                    }
                    unset($item);
                    
                    // Obtener productos personalizados
                    $productos_personalizados = readRecords('cotizacion_productos_personalizados', ["cotizacion_id = $id"], null, 'id ASC');
                    
                    // Calcular subtotal
                    $subtotal = 0;
                    foreach ($items as $item) {
                        $subtotal += $item['subtotal'];
                    }
                    foreach ($productos_personalizados as $producto) {
                        $subtotal += $producto['subtotal'];
                    }
                    
                    // Preparar datos para el correo
                    $cotizacionData = [
                        'numero' => $cotizacion['numero_cotizacion'],
                        'fecha' => date('d/m/Y H:i', strtotime($cotizacion['created_at'])),
                        'cliente' => [
                            'nombre' => $cliente['nombre'],
                            'empresa' => $cliente['empresa'] ?? '',
                            'email' => $cliente['email'] ?? '',
                            'telefono' => $cliente['telefono'] ?? ''
                        ],
                        'items' => $items,
                        'productos_personalizados' => $productos_personalizados,
                        'subtotal' => $subtotal,
                        'descuento' => $cotizacion['descuento'] ?? 0,
                        'total' => $subtotal - ($cotizacion['descuento'] ?? 0),
                        'observaciones' => $cotizacion['observaciones'] ?? '',
                        'notas' => $cotizacion['notas'] ?? '',
                        'estado' => $cotizacion['estado']
                    ];
                    
                    // Generar PDF temporal
                    error_log("AJAX Cotizaciones - Generando PDF temporal");
                    $pdfPath = generateCotizacionPDF($cotizacion['id']);
                    if ($pdfPath) {
                        error_log("AJAX Cotizaciones - PDF generado: $pdfPath");
                    } else {
                        error_log("AJAX Cotizaciones - Error generando PDF");
                    }
                    
                    // Enviar correo usando SimpleEmailSender
                    $emailSender = new SimpleEmailSender();
                    $emailResult = $emailSender->sendQuoteEmail($cotizacionData, $pdfPath);
                    
                    if ($emailResult) {
                        error_log("AJAX Cotizaciones - Correo enviado exitosamente");
                    } else {
                        error_log("AJAX Cotizaciones - Error enviando correo");
                    }
                    
                    // Limpiar archivo temporal
                    if ($pdfPath && file_exists($pdfPath)) {
                        unlink($pdfPath);
                        error_log("AJAX Cotizaciones - Archivo temporal eliminado");
                    }
                    
                } catch (Exception $e) {
                    error_log("AJAX Cotizaciones - Error en envío de correo: " . $e->getMessage());
                }
            }
            
            $estadoTexto = [
                'enviada' => 'Enviada',
                'aceptada' => 'Aceptada',
                'rechazada' => 'Rechazada',
                'pagada' => 'Pagada',
                'entregada' => 'Entregada'
            ];
            
            $mensaje = $estadoTexto[$estado] ?? ucfirst($estado);
            error_log("AJAX Cotizaciones - Respuesta exitosa: $mensaje");
            echo json_encode(['success' => true, 'message' => "Cotización marcada como $mensaje exitosamente"]);
        } else {
            error_log("AJAX Cotizaciones - ERROR: No se pudo actualizar el estado");
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de la cotización']);
        }
        
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de cotización requerido']);
            exit;
        }
        
        // Eliminar items de la cotización primero
        $items = readRecords('cotizacion_items', ["cotizacion_id = $id"]);
        foreach ($items as $item) {
            deleteRecord('cotizacion_items', $item['id'], false); // Eliminación física
        }
        
        // Eliminar la cotización
        if (deleteRecord('cotizaciones', $id, false)) { // Eliminación física
            echo json_encode(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cotización']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

// Función para crear HTML de cotización para correo (DEPRECATED - usar includes/pdf_generator.php)
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
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
                font-size: 16px;
            }
            .observations {
                margin-top: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border-left: 4px solid #7B3F9F;
            }
            .observations h4 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
            }
            .footer {
                margin-top: 50px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-info">
                <h1 class="company-name">DT Studio</h1>
                <p class="company-subtitle">Productos Promocionales y Merchandising</p>
            </div>
            ' . ($logoExists ? '<img src="' . $logoPath . '" alt="Logo DT Studio" class="logo">' : '') . '
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
                <p><strong>Estado:</strong> ' . ucfirst($data['estado']) . '</p>
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
        $varianteText = '';
        if (isset($item['variante']) && $item['variante']) {
            $varianteParts = array_filter([
                $item['variante']['talla'] ?? '',
                $item['variante']['color'] ?? '',
                $item['variante']['material'] ?? ''
            ]);
            $varianteText = implode(' - ', $varianteParts);
        }
        
        $html .= '
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($item['producto']['nombre']) . '</strong><br>
                        <small>SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small>
                    </td>
                    <td>' . htmlspecialchars($varianteText ?: 'Sin variante') . '</td>
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
                    <td style="text-align: right; color: red;">-$' . number_format($data['descuento'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">$' . number_format($data['total'], 2) . '</td>
                </tr>
            </table>
        </div>';
        
    if ($data['observaciones']) {
        $html .= '
        <div class="observations">
            <h4>Observaciones</h4>
            <p>' . nl2br(htmlspecialchars($data['observaciones'])) . '</p>
        </div>';
    }
    
    $html .= '
        <div class="footer">
            <p>DT Studio - Productos Promocionales y Merchandising</p>
            <p>Gracias por su confianza en nuestros servicios</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
