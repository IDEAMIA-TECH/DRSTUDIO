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
}

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'generate_cotizacion_pdf') {
    $data = $input['data'] ?? [];
    
    if (empty($data)) {
        http_response_code(400);
        echo 'Datos de cotización requeridos';
        exit;
    }
    
    // Generar PDF
    generateCotizacionPDF($data);
} else {
    http_response_code(400);
    echo 'Acción no válida';
    exit;
}

function generateCotizacionPDF($data) {
    // Crear HTML para el PDF
    $html = createCotizacionHTML($data);
    
    // Configurar headers para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Cotizacion_' . $data['numero'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Generar PDF usando mPDF (si está disponible) o HTML simple
    if (class_exists('Mpdf\Mpdf')) {
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
        
        $mpdf->WriteHTML($html);
        $mpdf->Output();
    } else {
        // Fallback: generar HTML que se puede convertir a PDF
        echo $html;
    }
}

function createCotizacionHTML($data) {
    $logoPath = '../assets/images/logo-dt-studio.svg';
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
            .info-section {
                flex: 1;
                margin: 0 10px;
            }
            .info-section h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #7B3F9F;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            .info-section p {
                margin: 5px 0;
                font-size: 12px;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-pendiente { background: #fff3cd; color: #856404; }
            .status-enviada { background: #d1ecf1; color: #0c5460; }
            .status-aceptada { background: #d4edda; color: #155724; }
            .status-rechazada { background: #f8d7da; color: #721c24; }
            .status-cancelada { background: #e2e3e5; color: #383d41; }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .items-table th,
            .items-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .items-table th {
                background: #7B3F9F;
                color: white;
                font-weight: bold;
                text-align: center;
            }
            .items-table tr:nth-child(even) {
                background: #f9f9f9;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .totals-section {
                margin-top: 20px;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
            }
            .totals-table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals-table td {
                padding: 5px 10px;
                border: none;
            }
            .totals-table .total-row {
                font-weight: bold;
                font-size: 14px;
                background: #7B3F9F;
                color: white;
            }
            .observations {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #7B3F9F;
            }
            .observations h3 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
                font-size: 14px;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            .variante-badge {
                background: #e9ecef;
                color: #495057;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                ' . ($logoExists ? '<img src="' . $logoPath . '" alt="DR Studio" class="logo">' : '') . '
            </div>
            <div class="company-info">
                <h1 class="company-name">DR Studio</h1>
                <p class="company-subtitle">Promocionales y Merchandising</p>
                <p>Tel: +52 1 234 567 8900</p>
                <p>Email: info@drstudio.com</p>
            </div>
        </div>
        
        <h2 class="document-title">COTIZACIÓN</h2>
        
        <div class="document-info">
            <div class="info-section">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> ' . htmlspecialchars($data['cliente']['nombre']) . '</p>
                ' . ($data['cliente']['empresa'] ? '<p><strong>Empresa:</strong> ' . htmlspecialchars($data['cliente']['empresa']) . '</p>' : '') . '
                ' . ($data['cliente']['email'] ? '<p><strong>Email:</strong> ' . htmlspecialchars($data['cliente']['email']) . '</p>' : '') . '
                ' . ($data['cliente']['telefono'] ? '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['cliente']['telefono']) . '</p>' : '') . '
            </div>
            <div class="info-section">
                <h3>Información de la Cotización</h3>
                <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                <p><strong>Fecha:</strong> ' . htmlspecialchars($data['fecha']) . '</p>
                <p><strong>Estado:</strong> <span class="status-badge status-' . $data['estado'] . '">' . ucfirst($data['estado']) . '</span></p>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Producto</th>
                    <th style="width: 20%;">Variante</th>
                    <th style="width: 10%;">Cantidad</th>
                    <th style="width: 15%;">Precio Unitario</th>
                    <th style="width: 15%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['items'] as $item) {
        $varianteText = '';
        if (isset($item['variante']) && $item['variante']) {
            $varianteParts = array_filter([
                $item['variante']['talla'],
                $item['variante']['color'],
                $item['variante']['material']
            ]);
            $varianteText = '<span class="variante-badge">' . htmlspecialchars(implode(' - ', $varianteParts)) . '</span>';
        } else {
            $varianteText = '<span style="color: #999;">Sin variante</span>';
        }
        
        $html .= '
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($item['producto']['nombre']) . '</strong><br>
                        <small style="color: #666;">SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small>
                    </td>
                    <td class="text-center">' . $varianteText . '</td>
                    <td class="text-center">' . $item['cantidad'] . '</td>
                    <td class="text-right">$' . number_format($item['precio_unitario'], 2) . '</td>
                    <td class="text-right"><strong>$' . number_format($item['subtotal'], 2) . '</strong></td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="text-right" style="width: 80%;"><strong>Subtotal:</strong></td>
                    <td class="text-right"><strong>$' . number_format($data['subtotal'], 2) . '</strong></td>
                </tr>';
    
    if ($data['descuento'] > 0) {
        $html .= '
                <tr>
                    <td class="text-right"><strong>Descuento:</strong></td>
                    <td class="text-right" style="color: #dc3545;"><strong>-$' . number_format($data['descuento'], 2) . '</strong></td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>$' . number_format($data['total'], 2) . '</strong></td>
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
        <div class="footer">
            <p>Esta cotización es válida por 30 días a partir de la fecha de emisión.</p>
            <p>DR Studio - Promocionales y Merchandising | www.drstudio.com</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
