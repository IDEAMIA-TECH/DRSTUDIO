<?php
// Archivo de generación de PDF mejorado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir dependencias
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$action = $data['action'];
$cotizacionData = $data['data'] ?? [];

// Función para crear HTML de cotización
function createCotizacionHTML($data) {
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

// Función para generar PDF con mPDF
function generateCotizacionPDF($data) {
    try {
        // Cargar mPDF
        require_once __DIR__ . '/../vendor/autoload.php';
        
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
            'margin_footer' => 9,
        ]);
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $data['numero']);
        $mpdf->SetAuthor('DT Studio');
        $mpdf->SetCreator('DT Studio System');
        $mpdf->SetSubject('Cotización de productos promocionales');
        
        // Generar HTML
        $html = createCotizacionHTML($data);
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar PDF
        $mpdf->Output('Cotizacion_' . $data['numero'] . '.pdf', 'D');
        
        return true;
        
    } catch (Exception $e) {
        error_log('Error generando PDF: ' . $e->getMessage());
        return false;
    }
}

// Procesar acción
if ($action === 'generate_cotizacion_pdf') {
    if (empty($cotizacionData)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron datos de cotización']);
        exit;
    }
    
    // Generar PDF
    $success = generateCotizacionPDF($cotizacionData);
    
    if (!$success) {
        // Fallback: mostrar HTML
        header('Content-Type: text/html; charset=UTF-8');
        echo createCotizacionHTML($cotizacionData);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
}
?>
