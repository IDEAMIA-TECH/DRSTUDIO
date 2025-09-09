<?php
// Detectar la ruta correcta del proyecto
$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/includes/config.php';
$authPath = $projectRoot . '/includes/auth.php';
$functionsPath = $projectRoot . '/includes/functions.php';

// Verificar si los archivos existen, si no, probar rutas alternativas
if (!file_exists($configPath)) {
    // Probar ruta absoluta del servidor
    $configPath = '/home/dtstudio/public_html/includes/config.php';
    $authPath = '/home/dtstudio/public_html/includes/auth.php';
    $functionsPath = '/home/dtstudio/public_html/includes/functions.php';
}

// Incluir archivos
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die('Error: No se pudo encontrar config.php');
}

if (file_exists($authPath)) {
    require_once $authPath;
} else {
    die('Error: No se pudo encontrar auth.php');
}

if (file_exists($functionsPath)) {
    require_once $functionsPath;
} else {
    die('Error: No se pudo encontrar functions.php');
}

// Incluir autoloader de Composer para mPDF
require_once $projectRoot . '/vendor/autoload.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Log para debugging
error_log("PDF Generation - Action: " . $action);
error_log("PDF Generation - Input data: " . print_r($input, true));

if ($action === 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Archivo generate_pdf.php accesible correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action
    ]);
    exit;
}

if ($action === 'generate_cotizacion_pdf') {
    $data = $input['data'] ?? [];
    
    if (empty($data)) {
        error_log("PDF Generation - Error: Datos de cotización vacíos");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Datos de cotización requeridos']);
        exit;
    }
    
    error_log("PDF Generation - Iniciando generación de PDF para cotización: " . $data['numero']);
    
    // Generar PDF
    generateCotizacionPDF($data);
} else {
    error_log("PDF Generation - Error: Acción no válida: " . $action);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

function generateCotizacionPDF($data) {
    error_log("PDF Generation - Iniciando createCotizacionHTML");
    
    // Crear HTML para el PDF
    $html = createCotizacionHTML($data);
    
    error_log("PDF Generation - HTML generado, longitud: " . strlen($html));
    
    // Verificar si mPDF está disponible
    if (class_exists('Mpdf\Mpdf')) {
        error_log("PDF Generation - mPDF disponible, generando PDF");
        
        try {
            // Usar mPDF si está disponible
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
            ]);
            
            // Configurar metadatos del PDF
            $mpdf->SetTitle('Cotización ' . $data['numero']);
            $mpdf->SetAuthor('DT Studio');
            $mpdf->SetCreator('DT Studio - Sistema de Cotizaciones');
            $mpdf->SetSubject('Cotización de productos promocionales');
            
            // Escribir el HTML al PDF
            $mpdf->WriteHTML($html);
            
            // Configurar headers para PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Cotizacion_' . $data['numero'] . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Generar y enviar el PDF
            $mpdf->Output('Cotizacion_' . $data['numero'] . '.pdf', 'D');
            
            error_log("PDF Generation - PDF generado exitosamente con mPDF");
            exit;
            
        } catch (Exception $e) {
            error_log("PDF Generation - Error con mPDF: " . $e->getMessage());
            // Si mPDF falla, continuar con el fallback HTML
        }
    } else {
        error_log("PDF Generation - mPDF no disponible, generando HTML con estilos de impresión");
        
        // Generar HTML con estilos optimizados para impresión/PDF
        $htmlWithStyles = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cotización ' . htmlspecialchars($data['numero']) . '</title>
            <style>
                @media print {
                    body { margin: 0; padding: 0; }
                    .no-print { display: none !important; }
                    .page-break { page-break-before: always; }
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                    background: white;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #7B3F9F;
                    padding-bottom: 20px;
                }
                
                .logo {
                    max-width: 200px;
                    height: auto;
                    margin-bottom: 10px;
                }
                
                .company-info {
                    color: #7B3F9F;
                    font-weight: bold;
                    font-size: 14px;
                }
                
                .quote-title {
                    font-size: 24px;
                    font-weight: bold;
                    color: #7B3F9F;
                    margin: 20px 0;
                }
                
                .quote-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                }
                
                .quote-details, .client-details {
                    flex: 1;
                    margin: 0 10px;
                }
                
                .quote-details h3, .client-details h3 {
                    color: #7B3F9F;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                    margin-bottom: 10px;
                }
                
                .products-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                
                .products-table th,
                .products-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                
                .products-table th {
                    background-color: #7B3F9F;
                    color: white;
                    font-weight: bold;
                }
                
                .products-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                
                .totals {
                    margin-top: 20px;
                    text-align: right;
                }
                
                .total-line {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                    padding: 5px 0;
                }
                
                .total-final {
                    font-size: 16px;
                    font-weight: bold;
                    color: #7B3F9F;
                    border-top: 2px solid #7B3F9F;
                    padding-top: 10px;
                }
                
                .observations {
                    margin-top: 30px;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #7B3F9F;
                }
                
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                }
                
                @media print {
                    body { margin: 0; padding: 10px; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            ' . $html . '
        </body>
        </html>';
        
        // Configurar headers para descarga
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Cotizacion_' . $data['numero'] . '.html"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $htmlWithStyles;
        error_log("PDF Generation - HTML con estilos generado exitosamente");
    }
}

function createCotizacionHTML($data) {
    $logoPath = '../assets/logo/LOGO.png';
    $logoExists = file_exists($logoPath);
    
    $estadoClass = [
        'pendiente' => 'warning',
        'enviada' => 'info',
        'aceptada' => 'success',
        'rechazada' => 'danger',
        'cancelada' => 'secondary'
    ];
    $estadoColor = $estadoClass[$data['estado']] ?? 'secondary';
    
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
            }
            .totals table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals td {
                padding: 5px;
                border: none;
            }
            .totals .total-row {
                font-weight: bold;
                font-size: 14px;
                background-color: #7B3F9F;
                color: white;
            }
            .totals .total-row td {
                color: white !important;
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
?>